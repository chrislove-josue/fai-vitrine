<?php

namespace App\Filament\Resources\Offers;

use App\Filament\Resources\Concerns\AccessControlledResource;
use App\Filament\Resources\Offers\Pages\ManageOffers;
use App\Models\Offer;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class OfferResource extends Resource
{
    use AccessControlledResource;

    protected static ?string $model = Offer::class;

    protected static function permissionPrefix(): string
    {
        return 'subscription';
    }

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedTag;

    protected static ?string $navigationLabel = 'Offres';

    protected static ?string $recordTitleAttribute = 'code';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('code')
                    ->label('Code')
                    ->required()
                    ->maxLength(40),
                TextInput::make('name')
                    ->label('Nom')
                    ->required(),
                Textarea::make('description')
                    ->label('Description'),
                Select::make('status')
                    ->label('Statut')
                    ->options(['draft' => 'Brouillon', 'active' => 'Active', 'archived' => 'Archivée'])
                    ->default('draft')
                    ->required(),
                TextInput::make('duration_days')
                    ->label('Durée (jours)')
                    ->numeric()
                    ->default(30)
                    ->required(),
                Select::make('network_profile_id')
                    ->label('Profil réseau')
                    ->relationship('networkProfile', 'name')
                    ->required(),
                TextInput::make('activation_fee')
                    ->label('Frais d\'activation')
                    ->numeric()
                    ->default(0),
                TextInput::make('currency')
                    ->label('Devise')
                    ->default('XOF')
                    ->maxLength(3),
                TextInput::make('max_simultaneous_sessions')
                    ->label('Sessions simultanées max')
                    ->numeric()
                    ->default(1),
                TextInput::make('data_limit')
                    ->label('Limite de données (octets)')
                    ->numeric(),
                TextInput::make('fair_use_limit')
                    ->label('Limite fair-use (octets)')
                    ->numeric(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('code')
            ->columns([
                TextColumn::make('code')
                    ->label('Code')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('name')
                    ->label('Nom')
                    ->searchable(),
                TextColumn::make('status')
                    ->label('Statut')
                    ->badge(),
                TextColumn::make('duration_days')
                    ->label('Durée')
                    ->suffix(' j'),
                TextColumn::make('current_price')
                    ->label('Prix courant')
                    ->state(fn (Offer $record): ?string => $record->currentPrice()?->amount !== null
                        ? number_format((int) $record->currentPrice()->amount, 0, ',', ' ').' XOF'
                        : null),
                TextColumn::make('networkProfile.name')
                    ->label('Profil réseau'),
            ])
            ->filters([
                TrashedFilter::make(),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
                ForceDeleteAction::make(),
                RestoreAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    ForceDeleteBulkAction::make(),
                    RestoreBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageOffers::route('/'),
        ];
    }

    public static function getRecordRouteBindingEloquentQuery(): Builder
    {
        return parent::getRecordRouteBindingEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }
}
