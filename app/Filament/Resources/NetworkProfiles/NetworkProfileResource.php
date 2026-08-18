<?php

namespace App\Filament\Resources\NetworkProfiles;

use App\Filament\Resources\Concerns\AccessControlledResource;
use App\Filament\Resources\NetworkProfiles\Pages\ManageNetworkProfiles;
use App\Models\NetworkProfile;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class NetworkProfileResource extends Resource
{
    use AccessControlledResource;

    protected static ?string $model = NetworkProfile::class;

    protected static function permissionPrefix(): string
    {
        return 'network';
    }

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedSignal;

    protected static ?string $navigationLabel = 'Profils réseau';

    protected static ?string $recordTitleAttribute = 'name';

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
                TextInput::make('download_speed')
                    ->label('Débit descendant (bps)')
                    ->numeric()
                    ->default(0),
                TextInput::make('upload_speed')
                    ->label('Débit montant (bps)')
                    ->numeric()
                    ->default(0),
                TextInput::make('rate_limit')
                    ->label('Limite de débit (bps)')
                    ->numeric(),
                TextInput::make('session_timeout')
                    ->label('Time-out session (s)')
                    ->numeric(),
                TextInput::make('idle_timeout')
                    ->label('Time-out inactif (s)')
                    ->numeric(),
                TextInput::make('data_limit')
                    ->label('Limite de données (octets)')
                    ->numeric(),
                Select::make('status')
                    ->label('Statut')
                    ->options(['active' => 'Actif', 'inactive' => 'Inactif'])
                    ->default('active')
                    ->required(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->columns([
                TextColumn::make('code')
                    ->label('Code')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('name')
                    ->label('Nom')
                    ->searchable(),
                TextColumn::make('download_speed')
                    ->label('Descendant')
                    ->formatStateUsing(fn ($state) => number_format((int) $state / 1_000_000, 0).' Mbps'),
                TextColumn::make('upload_speed')
                    ->label('Montant')
                    ->formatStateUsing(fn ($state) => number_format((int) $state / 1_000_000, 0).' Mbps'),
                TextColumn::make('status')
                    ->label('Statut')
                    ->badge(),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageNetworkProfiles::route('/'),
        ];
    }
}
