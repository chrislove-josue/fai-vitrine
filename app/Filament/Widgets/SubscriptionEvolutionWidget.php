<?php

namespace App\Filament\Widgets;

use App\Models\Subscription;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Carbon;

class SubscriptionEvolutionWidget extends ChartWidget
{
    protected ?string $heading = 'Évolution des abonnements';

    protected ?string $description = '6 derniers mois';

    protected int | string | array $columnSpan = 1;

    protected static ?int $sort = 4;

    protected function getType(): string
    {
        return 'bar';
    }

    protected function getData(): array
    {
        $months = collect();
        for ($i = 5; $i >= 0; $i--) {
            $date = Carbon::now()->subMonths($i);
            $months->push([
                'start' => $date->copy()->startOfMonth(),
                'end' => $date->copy()->endOfMonth(),
                'label' => $date->locale('fr')->isoFormat('MMM'),
            ]);
        }

        $active = $months->map(function ($month) {
            return (int) Subscription::query()
                ->where('status', Subscription::STATUS_ACTIVE)
                ->where('activated_at', '<=', $month['end'])
                ->where(function ($q) use ($month) {
                    $q->whereNull('expires_at')
                      ->orWhere('expires_at', '>', $month['start']);
                })
                ->count();
        });

        $suspended = $months->map(function ($month) {
            return (int) Subscription::query()
                ->where('status', Subscription::STATUS_SUSPENDED)
                ->where('suspended_at', '<=', $month['end'])
                ->count();
        });

        $expired = $months->map(function ($month) {
            return (int) Subscription::query()
                ->where('status', Subscription::STATUS_EXPIRED)
                ->where('expires_at', '>=', $month['start'])
                ->where('expires_at', '<=', $month['end'])
                ->count();
        });

        return [
            'datasets' => [
                [
                    'label' => 'Actifs',
                    'data' => $active->toArray(),
                    'backgroundColor' => '#0057B8',
                    'borderRadius' => 4,
                ],
                [
                    'label' => 'Suspendus',
                    'data' => $suspended->toArray(),
                    'backgroundColor' => '#F7941D',
                    'borderRadius' => 4,
                ],
                [
                    'label' => 'Expirés',
                    'data' => $expired->toArray(),
                    'backgroundColor' => '#E4EAF2',
                    'borderRadius' => 4,
                ],
            ],
            'labels' => $months->pluck('label')->toArray(),
        ];
    }

    protected function getOptions(): array
    {
        return [
            'scales' => [
                'y' => [
                    'beginAtZero' => true,
                    'stacked' => true,
                    'grid' => [
                        'color' => 'rgba(228, 234, 242, 0.5)',
                    ],
                ],
                'x' => [
                    'stacked' => true,
                    'grid' => [
                        'display' => false,
                    ],
                ],
            ],
            'plugins' => [
                'legend' => [
                    'position' => 'bottom',
                    'labels' => [
                        'usePointStyle' => true,
                        'pointStyle' => 'rectRounded',
                        'padding' => 16,
                    ],
                ],
            ],
        ];
    }
}
