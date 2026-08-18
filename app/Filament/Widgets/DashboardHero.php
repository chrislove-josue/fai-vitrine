<?php

namespace App\Filament\Widgets;

use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Subscription;
use Filament\Widgets\Widget;

class DashboardHero extends Widget
{
    protected string $view = 'filament.widgets.dashboard-hero';

    protected int | string | array $columnSpan = 'full';

    protected static ?int $sort = -2;

    protected static ?int $columns = 2;

    public static function canView(): bool
    {
        return auth()->user() !== null;
    }

    public function getStats(): array
    {
        $totalRevenue = (int) Payment::query()
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
            : 0;

        $activeSubscriptions = Subscription::query()
            ->where('status', Subscription::STATUS_ACTIVE)
            ->count();

        $totalCustomers = Customer::query()
            ->where('status', 'active')
            ->count();

        $pendingInvoices = Invoice::query()
            ->whereIn('status', [Invoice::STATUS_ISSUED, Invoice::STATUS_OVERDUE])
            ->where('amount_due', '>', 0)
            ->count();

        return [
            'total_revenue' => $totalRevenue,
            'revenue_trend' => $revenueTrend,
            'active_subscriptions' => $activeSubscriptions,
            'total_customers' => $totalCustomers,
            'pending_invoices' => $pendingInvoices,
        ];
    }
}
