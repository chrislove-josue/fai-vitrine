<?php

namespace App\Filament\Widgets;

use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Subscription;
use Filament\Widgets\Widget;

class RecentActivityWidget extends Widget
{
    protected string $view = 'filament.widgets.recent-activity-widget';

    protected int | string | array $columnSpan = 1;

    protected static ?int $sort = 5;

    public function getActivities(): array
    {
        $activities = [];

        Subscription::query()
            ->with('customer')
            ->latest('created_at')
            ->limit(5)
            ->get()
            ->each(function ($sub) use (&$activities) {
                $activities[] = [
                    'type' => 'Abonnement',
                    'description' => $sub->subscription_number.' — '.$sub->customer->display_name,
                    'amount' => $sub->price,
                    'date' => $sub->created_at,
                    'color' => '#0057B8',
                ];
            });

        Payment::query()
            ->where('status', Payment::STATUS_SUCCESSFUL)
            ->latest('paid_at')
            ->limit(5)
            ->get()
            ->each(function ($pay) use (&$activities) {
                $activities[] = [
                    'type' => 'Paiement',
                    'description' => $pay->payment_reference,
                    'amount' => $pay->amount,
                    'date' => $pay->paid_at ?? $pay->created_at,
                    'color' => '#12B76A',
                ];
            });

        Invoice::query()
            ->whereIn('status', [Invoice::STATUS_ISSUED, Invoice::STATUS_OVERDUE])
            ->latest('issue_date')
            ->limit(5)
            ->get()
            ->each(function ($inv) use (&$activities) {
                $activities[] = [
                    'type' => 'Facture',
                    'description' => $inv->invoice_number,
                    'amount' => $inv->total,
                    'date' => $inv->issue_date ?? $inv->created_at,
                    'color' => '#F7941D',
                ];
            });

        usort($activities, fn ($a, $b) => $b['date'] <=> $a['date']);

        return array_slice($activities, 0, 10);
    }
}
