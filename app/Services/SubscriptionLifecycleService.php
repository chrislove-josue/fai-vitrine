<?php

namespace App\Services;

use App\Models\Subscription;
use App\Models\SubscriptionEvent;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * Machine à états du cycle de vie d'un abonnement.
 *
 * PENDING → ACTIVE → GRACE_PERIOD → SUSPENDED
 *   ↑         │  │              │
 *   │         │  └── REACTIVATED
 *   │         └──→ RENEW (ACTIVE)
 *   └── TERMINATED (depuis n'importe quel état non terminal)
 *
 * Chaque transition est validée (machine à états), journalisée
 * (subscription_events) puis publiée dans l'outbox (Step 6) pour
 * synchroniser FreeRADIUS.
 */
class SubscriptionLifecycleService
{
    public function __construct(
        private readonly OutboxService $outbox,
        private readonly int $gracePeriodDays = 3,
    ) {}

    /**
     * Transitions autorisées par la machine à états.
     *
     * @var array<string, list<string>>
     */
    private const TRANSITIONS = [
        Subscription::STATUS_PENDING => [Subscription::STATUS_ACTIVE, Subscription::STATUS_TERMINATED, Subscription::STATUS_CANCELLED],
        Subscription::STATUS_ACTIVE => [Subscription::STATUS_GRACE_PERIOD, Subscription::STATUS_SUSPENDED, Subscription::STATUS_TERMINATED, Subscription::STATUS_CANCELLED, Subscription::STATUS_EXPIRED],
        Subscription::STATUS_GRACE_PERIOD => [Subscription::STATUS_ACTIVE, Subscription::STATUS_SUSPENDED, Subscription::STATUS_TERMINATED, Subscription::STATUS_EXPIRED, Subscription::STATUS_CANCELLED],
        Subscription::STATUS_SUSPENDED => [Subscription::STATUS_ACTIVE, Subscription::STATUS_TERMINATED, Subscription::STATUS_CANCELLED],
        Subscription::STATUS_EXPIRED => [Subscription::STATUS_TERMINATED, Subscription::STATUS_ACTIVE],
    ];

    public function activate(Subscription $subscription, ?string $source = 'system', ?string $actorType = null, ?int $actorId = null): Subscription
    {
        return $this->transition($subscription, Subscription::STATUS_ACTIVE, function (Subscription $sub) {
            $start = now();
            $sub->forceFill([
                'starts_at' => $start,
                'activated_at' => $start,
                'expires_at' => $start->copy()->addDays($sub->offer->duration_days),
                'next_renewal_at' => $start->copy()->addDays($sub->offer->duration_days),
                'suspended_at' => null,
                'suspension_reason' => null,
            ]);
        }, 'activated', OutboxService::EVENT_SUBSCRIPTION_ACTIVATED, $source, $actorType, $actorId);
    }

    public function expire(Subscription $subscription, ?string $source = 'system', ?string $actorType = null, ?int $actorId = null): Subscription
    {
        $target = $this->gracePeriodDays > 0
            ? Subscription::STATUS_GRACE_PERIOD
            : Subscription::STATUS_EXPIRED;

        return $this->transition($subscription, $target, fn () => null, 'expired', OutboxService::EVENT_SUBSCRIPTION_EXPIRED, $source, $actorType, $actorId);
    }

    public function suspend(Subscription $subscription, string $reason = 'unpaid', ?string $source = 'system', ?string $actorType = null, ?int $actorId = null): Subscription
    {
        return $this->transition($subscription, Subscription::STATUS_SUSPENDED, function (Subscription $sub) use ($reason) {
            $sub->forceFill([
                'suspended_at' => now(),
                'suspension_reason' => $reason,
            ]);
        }, 'suspended', OutboxService::EVENT_SUBSCRIPTION_SUSPENDED, $source, $actorType, $actorId);
    }

    public function reactivate(Subscription $subscription, ?string $source = 'system', ?string $actorType = null, ?int $actorId = null): Subscription
    {
        return $this->transition($subscription, Subscription::STATUS_ACTIVE, function (Subscription $sub) {
            $now = now();
            $sub->forceFill([
                'suspended_at' => null,
                'suspension_reason' => null,
                'expires_at' => $now->copy()->addDays($sub->offer->duration_days),
                'next_renewal_at' => $now->copy()->addDays($sub->offer->duration_days),
                'starts_at' => $sub->starts_at ?? $now,
                'activated_at' => $sub->activated_at ?? $now,
            ]);
        }, 'reactivated', OutboxService::EVENT_SUBSCRIPTION_REACTIVATED, $source, $actorType, $actorId);
    }

    public function renew(Subscription $subscription, ?string $source = 'system', ?string $actorType = null, ?int $actorId = null): Subscription
    {
        if ($subscription->status !== Subscription::STATUS_ACTIVE) {
            throw new InvalidArgumentException('Impossible de renouveler un abonnement qui n\'est pas actif.');
        }

        return DB::connection('isp_core')->transaction(function () use ($subscription, $source, $actorType, $actorId) {
            $oldExpiry = $subscription->expires_at ?? now();
            $newExpiry = $oldExpiry->copy()->addDays($subscription->offer->duration_days);

            $subscription->forceFill([
                'expires_at' => $newExpiry,
                'next_renewal_at' => $newExpiry->copy(),
            ])->save();

            $subscription->renewals()->create([
                'old_expires_at' => $oldExpiry,
                'new_expires_at' => $newExpiry,
                'amount' => $subscription->price,
                'currency' => $subscription->currency,
                'status' => 'completed',
            ]);

            $this->recordEvent($subscription, 'renewed', $subscription->status, $subscription->status, null, $source, $actorType, $actorId);

            return $subscription;
        });
    }

    public function terminate(Subscription $subscription, string $reason = 'customer_request', ?string $source = 'system', ?string $actorType = null, ?int $actorId = null): Subscription
    {
        return $this->transition($subscription, Subscription::STATUS_TERMINATED, function (Subscription $sub) use ($reason) {
            $sub->forceFill([
                'terminated_at' => now(),
                'termination_reason' => $reason,
            ]);
        }, 'terminated', OutboxService::EVENT_SUBSCRIPTION_TERMINATED, $source, $actorType, $actorId);
    }

    public function cancel(Subscription $subscription, string $reason = 'customer_request', ?string $source = 'system', ?string $actorType = null, ?int $actorId = null): Subscription
    {
        return $this->transition($subscription, Subscription::STATUS_CANCELLED, function (Subscription $sub) use ($reason) {
            $sub->forceFill([
                'cancelled_at' => now(),
                'cancellation_reason' => $reason,
            ]);
        }, 'cancelled', null, $source, $actorType, $actorId);
    }

    /**
     * Vérifie si une transition est autorisée par la machine à états.
     */
    public function canTransition(string $from, string $to): bool
    {
        return in_array($to, self::TRANSITIONS[$from] ?? [], true);
    }

    /**
     * Applique une transition validée par la machine à états.
     */
    private function transition(
        Subscription $subscription,
        string $targetStatus,
        callable $apply,
        string $eventType,
        ?string $outboxEventType,
        string $source,
        ?string $actorType,
        ?int $actorId,
    ): Subscription {
        $allowed = self::TRANSITIONS[$subscription->status] ?? [];

        if (! in_array($targetStatus, $allowed, true)) {
            throw new InvalidArgumentException(
                "Transition invalide : {$subscription->status} → {$targetStatus}"
            );
        }

        return DB::connection('isp_core')->transaction(function () use ($subscription, $targetStatus, $apply, $eventType, $outboxEventType, $source, $actorType, $actorId) {
            $oldStatus = $subscription->status;

            $apply($subscription);
            $subscription->forceFill(['status' => $targetStatus])->save();

            $this->recordEvent($subscription, $eventType, $oldStatus, $targetStatus, null, $source, $actorType, $actorId);

            if ($outboxEventType !== null) {
                $this->outbox->publish(
                    $outboxEventType,
                    'subscription',
                    (string) $subscription->uuid,
                    ['status' => $targetStatus],
                );
            }

            return $subscription;
        });
    }

    private function recordEvent(Subscription $subscription, string $eventType, ?string $oldStatus, ?string $newStatus, ?string $reason, string $source, ?string $actorType, ?int $actorId): void
    {
        SubscriptionEvent::create([
            'subscription_id' => $subscription->id,
            'event_type' => $eventType,
            'old_status' => $oldStatus,
            'new_status' => $newStatus,
            'reason' => $reason,
            'source' => $source,
            'actor_type' => $actorType,
            'actor_id' => $actorId,
        ]);
    }
}
