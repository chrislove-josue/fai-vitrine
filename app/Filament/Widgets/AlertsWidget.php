<?php

namespace App\Filament\Widgets;

use App\Models\IspRadiusSyncState;
use App\Models\Invoice;
use App\Models\Subscription;
use Filament\Widgets\Widget;

class AlertsWidget extends Widget
{
    protected string $view = 'filament.widgets.alerts-widget';

    protected int | string | array $columnSpan = 1;

    protected static ?int $sort = 6;

    public function getAlerts(): array
    {
        $alerts = [];

        $overdueCount = Invoice::query()
            ->where('status', Invoice::STATUS_OVERDUE)
            ->count();

        if ($overdueCount > 0) {
            $alerts[] = [
                'type' => 'danger',
                'title' => "{$overdueCount} facture(s) en retard",
                'text' => 'Des factures sont impayées et nécessitent un relancement.',
            ];
        }

        $expiringCount = Subscription::query()
            ->where('status', Subscription::STATUS_ACTIVE)
            ->where('expires_at', '<=', now()->addDays(7))
            ->where('expires_at', '>', now())
            ->count();

        if ($expiringCount > 0) {
            $alerts[] = [
                'type' => 'warning',
                'title' => "{$expiringCount} abonnement(s) expirent sous 7 jours",
                'text' => 'Renouvelez ou contactez les clients concernés.',
            ];
        }

        $failedSync = IspRadiusSyncState::query()
            ->where('sync_status', 'failed')
            ->count();

        if ($failedSync > 0) {
            $alerts[] = [
                'type' => 'danger',
                'title' => "{$failedSync} synchronisation(s) FreeRADIUS en échec",
                'text' => 'Vérifiez l\'état de la synchronisation réseau.',
            ];
        }

        if (empty($alerts)) {
            $alerts[] = [
                'type' => 'success',
                'title' => 'Tout est en ordre',
                'text' => 'Aucune alerte active pour le moment.',
            ];
        }

        return $alerts;
    }
}
