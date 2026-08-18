<?php

namespace App\Filament\Widgets;

use App\Models\Payment;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Carbon;

class RevenueChartWidget extends ChartWidget
{
    protected ?string $heading = 'Revenus mensuels';

    protected ?string $description = '6 derniers mois';

    protected int | string | array $columnSpan = 1;

    protected static ?int $sort = 3;

    protected function getType(): string
    {
        return 'line';
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

        $data = $months->map(function ($month) {
            return (int) Payment::query()
                ->where('status', Payment::STATUS_SUCCESSFUL)
                ->where('paid_at', '>=', $month['start'])
                ->where('paid_at', '<=', $month['end'])
                ->sum('amount');
        });

        return [
            'datasets' => [
                [
                    'label' => 'Revenus (XOF)',
                    'data' => $data->toArray(),
                    'borderColor' => '#0057B8',
                    'backgroundColor' => 'rgba(0, 87, 184, 0.08)',
                    'fill' => true,
                    'tension' => 0.4,
                    'pointBackgroundColor' => '#0057B8',
                    'pointBorderColor' => '#ffffff',
                    'pointBorderWidth' => 2,
                    'pointRadius' => 4,
                    'pointHoverRadius' => 6,
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
                    'ticks' => [
                        'callback' => 'function(value) { return value.toLocaleString() + " XOF"; }',
                    ],
                    'grid' => [
                        'color' => 'rgba(228, 234, 242, 0.5)',
                    ],
                ],
                'x' => [
                    'grid' => [
                        'display' => false,
                    ],
                ],
            ],
            'plugins' => [
                'legend' => [
                    'display' => false,
                ],
            ],
        ];
    }
}
