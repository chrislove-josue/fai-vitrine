<?php

namespace App\Services;

use App\Models\OutboxEvent;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Publication et traitement des événements Outbox.
 *
 * Le pattern Outbox garantit qu'aucun événement métier n'est perdu :
 * l'écriture métier et la publication de l'événement sont faites dans
 * la même opération (au niveau applicatif), puis un worker traite les
 * événements en attente (voir étape 6 — synchronisation réseau).
 */
class OutboxService
{
    public const EVENT_SUBSCRIPTION_ACTIVATED = 'SubscriptionActivated';

    public const EVENT_SUBSCRIPTION_SUSPENDED = 'SubscriptionSuspended';

    public const EVENT_SUBSCRIPTION_REACTIVATED = 'SubscriptionReactivated';

    public const EVENT_SUBSCRIPTION_TERMINATED = 'SubscriptionTerminated';

    public const EVENT_SUBSCRIPTION_EXPIRED = 'SubscriptionExpired';

    public function publish(
        string $eventType,
        string $aggregateType,
        string $aggregateUuid,
        array $payload = [],
        ?\DateTimeInterface $availableAt = null,
    ): OutboxEvent {
        return OutboxEvent::create([
            'event_type' => $eventType,
            'aggregate_type' => $aggregateType,
            'aggregate_uuid' => $aggregateUuid,
            'payload' => $payload,
            'status' => OutboxEvent::STATUS_PENDING,
            'available_at' => $availableAt,
        ]);
    }

    /**
     * Récupère un lot d'événements en attente à traiter.
     *
     * @return Collection<int, OutboxEvent>
     */
    public function claimBatch(int $limit = 10): Collection
    {
        $events = OutboxEvent::pending()
            ->orderBy('available_at')
            ->orderBy('id')
            ->limit($limit)
            ->get();

        OutboxEvent::whereIn('id', $events->pluck('id'))
            ->where('status', OutboxEvent::STATUS_PENDING)
            ->update(['status' => OutboxEvent::STATUS_PROCESSING]);

        return $events;
    }

    public function markCompleted(OutboxEvent $event, array $result = []): void
    {
        $event->update([
            'status' => OutboxEvent::STATUS_COMPLETED,
            'attempts' => $event->attempts + 1,
            'processed_at' => now(),
            'error_message' => null,
            'payload' => array_merge((array) $event->payload, ['result' => $result]),
        ]);
    }

    public function markFailed(OutboxEvent $event, string $error): void
    {
        $event->update([
            'status' => OutboxEvent::STATUS_FAILED,
            'attempts' => $event->attempts + 1,
            'failed_at' => now(),
            'error_message' => $error,
        ]);
    }

    /**
     * Réinitialise les événements restés en processing (crash) pour relance.
     */
    public function requeueStaleEvents(): int
    {
        return DB::connection('isp_application')
            ->table('outbox_events')
            ->where('status', OutboxEvent::STATUS_PROCESSING)
            ->where('updated_at', '<', now()->subMinutes(5))
            ->update(['status' => OutboxEvent::STATUS_PENDING, 'attempts' => DB::raw('attempts + 1')]);
    }
}
