<?php

namespace App\Filament\Widgets;

use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Subscription;
use Filament\Support\Icons\Heroicon;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class PlatformOverview extends StatsOverviewWidget
{
    protected int | string | array $columnSpan = 'full';

    protected static ?int $sort = 2;

    protected function getStats(): array
    {
        $revenue = (int) Payment::query()
            ->where('status', Payment::STATUS_SUCCESSFUL)
            ->sum('amount');

        $lastMonthRevenue = (int) Payment::query()
            ->where('status', Payment::STATUS_SUCCESSFUL)
            ->where('paid_at', '>=', now()->subMonth()->startOfMonth())
            ->where('paid_at', '<=', now()->subMonth()->endOfMonth())
            ->sum('amount');

        $thisMonthRevenue = (int) Payment::query()
            ->where('status', Payment::STATUS_SUCCESSFUL)
            ->where('paid_at', '>=', now()->startOfMonth())
            ->where('paid_at', '<=', now()->endOfMonth())
            ->sum('amount');

        $revenueTrend = $lastMonthRevenue > 0
            ? round((($thisMonthRevenue - $lastMonthRevenue) / $lastMonthRevenue) * 100, 1)
            : null;

        $activeSubscriptions = Subscription::query()
            ->where('status', Subscription::STATUS_ACTIVE)
            ->count();

        $lastMonthActive = Subscription::query()
            ->where('status', Subscription::STATUS_ACTIVE)
            ->where('activated_at', '<=', now()->subMonth()->endOfMonth())
            ->count();

        $subTrend = $lastMonthActive > 0
            ? round((($activeSubscriptions - $lastMonthActive) / $lastMonthActive) * 100, 1)
            : null;

        $suspendedSubscriptions = Subscription::query()
            ->where('status', Subscription::STATUS_SUSPENDED)
            ->count();

        $activeCustomers = Customer::query()
            ->where('status', 'active')
            ->count();

        $lastMonthCustomers = Customer::query()
            ->where('status', 'active')
            ->where('created_at', '<=', now()->subMonth()->endOfMonth())
            ->count();

        $customerTrend = $lastMonthCustomers > 0
            ? round((($activeCustomers - $lastMonthCustomers) / $lastMonthCustomers) * 100, 1)
            : null;

        $overdueInvoices = Invoice::query()
            ->where('status', Invoice::STATUS_OVERDUE)
            ->count();

        $pendingPayments = Payment::query()
            ->where('status', Payment::STATUS_PENDING)
            ->count();

        return [
            Stat::make('Chiffre d\'affaires encaissé', number_format($revenue, 0, ',', ' ').' XOF')
                ->icon(Heroicon::OutlinedBanknotes)
                ->description($revenueTrend !== null
                    ? ($revenueTrend >= 0 ? '+'.$revenueTrend.'%' : $revenueTrend.'%').' ce mois-ci'
                    : 'Paiements réussis cumulés')
                ->descriptionColor($revenueTrend !== null
                    ? ($revenueTrend >= 0 ? 'success' : 'danger')
                    : 'success'),

            Stat::make('Abonnements actifs', number_format($activeSubscriptions))
                ->icon(Heroicon::OutlinedPlayCircle)
                ->description($subTrend !== null
                    ? ($subTrend >= 0 ? '+'.$subTrend.'%' : $subTrend.'%').' ce mois-ci'
                    : 'En cours de service')
                ->descriptionColor($subTrend !== null
                    ? ($subTrend >= 0 ? 'success' : 'danger')
                    : 'success'),

            Stat::make('Abonnements suspendus', number_format($suspendedSubscriptions))
                ->icon(Heroicon::OutlinedPauseCircle)
                ->description('En attente de règlement')
                ->descriptionColor('warning'),

            Stat::make('Clients actifs', number_format($activeCustomers))
                ->icon(Heroicon::OutlinedUsers)
                ->description($customerTrend !== null
                    ? ($customerTrend >= 0 ? '+'.$customerTrend.'%' : $customerTrend.'%').' ce mois-ci'
                    : 'Comptes opérationnels')
                ->descriptionColor($customerTrend !== null
                    ? ($customerTrend >= 0 ? 'success' : 'danger')
                    : 'primary'),

            Stat::make('Factures en retard', number_format($overdueInvoices))
                ->icon(Heroicon::OutlinedExclamationTriangle)
                ->description('À relancer')
                ->descriptionColor('danger'),

            Stat::make('Paiements en attente', number_format($pendingPayments))
                ->icon(Heroicon::OutlinedClock)
                ->description('À acquitter')
                ->descriptionColor('warning'),
        ];
    }
}
