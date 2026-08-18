<?php

namespace App\Services;

use App\Models\IspRadiusAccount;
use App\Models\IspRadiusSyncState;
use App\Models\OutboxEvent;
use App\Models\RadCheck;
use App\Models\RadReply;
use App\Models\RadUserGroup;
use App\Models\Subscription;
use App\Models\SyncOperation;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * Synchronisation des abonnements vers FreeRADIUS.
 *
 * Consommateur du pattern Outbox (étape 5) : chaque événement métier
 * publié est traduit en écritures sur la base freeradius
 * (radcheck, radreply, radusergroup, isp_radius_accounts) et tracé
 * dans sync_operations (isp_application) + isp_radius_sync_state.
 *
 * Règles d'accès :
 *  - actif       → radcheck Cleartext-Password présent, pas de rejet
 *  - grace       → service maintenu (aucune écriture bloquante)
 *  - suspendu    → radcheck Auth-Type := Reject ajouté
 *  - résilié     → compte supprimé du NAS (radcheck/radreply/radusergroup)
 */
class RadiusSyncService
{
    public const MAX_ATTEMPTS = 3;

    public function __construct(
        private readonly OutboxService $outbox,
        private readonly int $maxAttempts = self::MAX_ATTEMPTS,
    ) {}

    /**
     * Traite un événement outbox en une opération de synchronisation.
     *
     * @throws RuntimeException en cas d'échec (le worker gère les retries)
     */
    public function handle(OutboxEvent $event): void
    {
        $subscriptionUuid = (string) $event->aggregate_uuid;

        $operation = $this->recordOperation($event, $subscriptionUuid);

        try {
            match ($event->event_type) {
                OutboxService::EVENT_SUBSCRIPTION_ACTIVATED,
                OutboxService::EVENT_SUBSCRIPTION_REACTIVATED => $this->activate($subscriptionUuid),
                OutboxService::EVENT_SUBSCRIPTION_SUSPENDED => $this->suspend($subscriptionUuid),
                OutboxService::EVENT_SUBSCRIPTION_EXPIRED => $this->grace($subscriptionUuid),
                OutboxService::EVENT_SUBSCRIPTION_TERMINATED => $this->terminate($subscriptionUuid),
                default => throw new RuntimeException("Événement outbox non géré : {$event->event_type}"),
            };

            $operation->update([
                'status' => SyncOperation::STATUS_SUCCESS,
                'completed_at' => now(),
                'response' => ['event_type' => $event->event_type],
            ]);
        } catch (\Throwable $e) {
            $operation->update([
                'status' => SyncOperation::STATUS_FAILED,
                'error_code' => $e->getCode() ?: 500,
                'error_message' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    private function recordOperation(OutboxEvent $event, string $subscriptionUuid): SyncOperation
    {
        return SyncOperation::create([
            'operation_type' => 'radius:'.$event->event_type,
            'entity_type' => 'subscription',
            'entity_uuid' => $subscriptionUuid,
            'source' => 'outbox',
            'destination' => 'freeradius',
            'status' => SyncOperation::STATUS_PROCESSING,
            'attempts' => $event->attempts,
            'payload' => $event->payload,
            'started_at' => now(),
        ]);
    }

    /**
     * Active (ou réactive) un compte réseau sur FreeRADIUS.
     */
    public function activate(string $subscriptionUuid): void
    {
        $subscription = $this->subscriptionOrFail($subscriptionUuid);
        $networkAccount = $subscription->networkAccountLinks()->first()?->networkAccount;

        if ($networkAccount === null) {
            throw new RuntimeException("Aucun compte réseau rattaché à l'abonnement {$subscriptionUuid}");
        }

        $username = $networkAccount->username;
        $profileUuid = $subscription->offer->network_profile_id !== null
            ? $subscription->offer->networkProfile?->uuid
            : null;

        $radiusAccount = IspRadiusAccount::firstOrNew(['username' => $username]);
        $radiusAccount->forceFill([
            'network_account_uuid' => $networkAccount->uuid,
            'customer_uuid' => $networkAccount->customer_id !== null ? $networkAccount->customer?->uuid : null,
            'subscription_uuid' => $subscription->uuid,
            'network_profile_uuid' => $profileUuid,
            'status' => 'active',
            'synced_at' => now(),
        ])->save();

        $this->ensurePassword($username);
        $this->removeReject($username);
        $this->syncGroup($username, $subscription);

        $this->recordSyncState($networkAccount->uuid, $subscription->uuid, 'active', 'active');
    }

    /**
     * Suspend l'accès réseau (Auth-Type := Reject), sans supprimer le compte.
     */
    public function suspend(string $subscriptionUuid): void
    {
        $subscription = $this->subscriptionOrFail($subscriptionUuid);
        $networkAccount = $subscription->networkAccountLinks()->first()?->networkAccount;

        if ($networkAccount === null) {
            throw new RuntimeException("Aucun compte réseau rattaché à l'abonnement {$subscriptionUuid}");
        }

        $this->addReject($networkAccount->username);

        IspRadiusAccount::where('username', $networkAccount->username)->update(['status' => 'suspended', 'synced_at' => now()]);

        $this->recordSyncState($networkAccount->uuid, $subscription->uuid, 'suspended', 'suspended');
    }

    /**
     * Période de grâce : le service réseau est maintenu.
     */
    public function grace(string $subscriptionUuid): void
    {
        $subscription = $this->subscriptionOrFail($subscriptionUuid);
        $networkAccount = $subscription->networkAccountLinks()->first()?->networkAccount;

        if ($networkAccount === null) {
            throw new RuntimeException("Aucun compte réseau rattaché à l'abonnement {$subscriptionUuid}");
        }

        $this->removeReject($networkAccount->username);

        IspRadiusAccount::where('username', $networkAccount->username)->update(['status' => 'active', 'synced_at' => now()]);

        $this->recordSyncState($networkAccount->uuid, $subscription->uuid, 'grace_period', 'active');
    }

    /**
     * Résilie le compte : suppression du compte sur le NAS.
     */
    public function terminate(string $subscriptionUuid): void
    {
        $subscription = $this->subscriptionOrFail($subscriptionUuid);
        $networkAccount = $subscription->networkAccountLinks()->first()?->networkAccount;

        if ($networkAccount === null) {
            throw new RuntimeException("Aucun compte réseau rattaché à l'abonnement {$subscriptionUuid}");
        }

        $username = $networkAccount->username;

        RadCheck::where('username', $username)->delete();
        RadReply::where('username', $username)->delete();
        RadUserGroup::where('username', $username)->delete();

        IspRadiusAccount::where('username', $username)->update(['status' => 'terminated', 'synced_at' => now()]);

        $this->recordSyncState($networkAccount->uuid, $subscription->uuid, 'terminated', 'terminated');
    }

    /**
     * Traite un lot d'événements outbox en attente, avec retries.
     *
     * @return array{processed: int, failed: int}
     */
    public function processPending(int $limit = 10): array
    {
        $events = $this->outbox->claimBatch($limit);
        $processed = 0;
        $failed = 0;

        foreach ($events as $event) {
            try {
                $this->handle($event);
                $this->outbox->markCompleted($event);
                $processed++;
            } catch (\Throwable $e) {
                $this->outbox->markFailed($event, $e->getMessage());
                $failed++;
            }
        }

        return ['processed' => $processed, 'failed' => $failed];
    }

    private function subscriptionOrFail(string $uuid): Subscription
    {
        $subscription = Subscription::where('uuid', $uuid)->first();

        if ($subscription === null) {
            throw new RuntimeException("Abonnement introuvable : {$uuid}");
        }

        return $subscription;
    }

    private function ensurePassword(string $username): void
    {
        $exists = RadCheck::where('username', $username)->where('attribute', 'Cleartext-Password')->exists();

        if (! $exists) {
            RadCheck::create([
                'username' => $username,
                'attribute' => 'Cleartext-Password',
                'op' => ':=',
                'value' => Str::random(16),
            ]);
        }
    }

    private function addReject(string $username): void
    {
        RadCheck::updateOrCreate(
            ['username' => $username, 'attribute' => 'Auth-Type'],
            ['op' => ':=', 'value' => 'Reject'],
        );
    }

    private function removeReject(string $username): void
    {
        RadCheck::where('username', $username)->where('attribute', 'Auth-Type')->where('value', 'Reject')->delete();
    }

    private function syncGroup(string $username, Subscription $subscription): void
    {
        $profileCode = $subscription->offer->networkProfile?->code;

        if ($profileCode === null) {
            return;
        }

        $existing = RadUserGroup::where('username', $username)->value('groupname');

        if ($existing !== $profileCode) {
            RadUserGroup::where('username', $username)->delete();
            RadUserGroup::create([
                'username' => $username,
                'groupname' => $profileCode,
                'priority' => 1,
            ]);
        }
    }

    private function recordSyncState(string $networkAccountUuid, string $subscriptionUuid, string $desired, string $actual): void
    {
        $state = IspRadiusSyncState::firstOrNew(['network_account_uuid' => $networkAccountUuid]);
        $state->forceFill([
            'subscription_uuid' => $subscriptionUuid,
            'desired_status' => $desired,
            'actual_status' => $actual,
            'sync_status' => 'synced',
            'last_sync_at' => now(),
            'last_success_at' => now(),
            'last_error' => null,
        ])->save();
    }
}
