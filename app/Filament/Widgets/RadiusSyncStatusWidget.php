<?php

namespace App\Filament\Widgets;

use App\Models\IspRadiusSyncState;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Facades\Artisan;

class RadiusSyncStatusWidget extends TableWidget
{
    protected int | string | array $columnSpan = 1;

    protected static ?int $sort = 7;

    protected function getTableQuery(): Builder|Relation|null
    {
        return IspRadiusSyncState::query()
            ->orderByDesc('last_sync_at')
            ->limit(5);
    }

    public function table(Table $table): Table
    {
        return $table
            ->query($this->getTableQuery())
            ->heading('Synchronisation FreeRADIUS')
            ->columns([
                TextColumn::make('network_account_uuid')
                    ->label('Compte')
                    ->limit(10),
                TextColumn::make('sync_status')
                    ->label('Sync')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'synced' => 'success',
                        'failed' => 'danger',
                        default => 'warning',
                    }),
                TextColumn::make('last_error')
                    ->label('Erreur')
                    ->limit(25)
                    ->placeholder('—'),
                TextColumn::make('last_sync_at')
                    ->label('Essai')
                    ->dateTime('d/m H:i'),
            ])
            ->headerActions([
                Action::make('sync_now')
                    ->label('Synchroniser')
                    ->icon(Heroicon::OutlinedArrowPathRoundedSquare)
                    ->color('primary')
                    ->action(function () {
                        $result = Artisan::call('radius:sync');

                        Notification::make()
                            ->title('Synchronisation lancée (code '.$result.').')
                            ->success()
                            ->send();
                    }),
            ]);
    }

    public static function canView(): bool
    {
        return auth()->user()?->can('network.view') ?? false;
    }
}
