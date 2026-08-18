<?php

namespace App\Filament\Resources\NetworkAccounts;

use App\Filament\Resources\Concerns\AccessControlledResource;
use App\Filament\Resources\NetworkAccounts\Pages\ManageNetworkAccounts;
use App\Models\NetworkAccount;
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
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class NetworkAccountResource extends Resource
{
    use AccessControlledResource;

    protected static ?string $model = NetworkAccount::class;

    protected static function permissionPrefix(): string
    {
        return 'network';
    }

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedComputerDesktop;

    protected static ?string $navigationLabel = 'Comptes réseau';

    protected static ?string $recordTitleAttribute = 'username';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('username')
                    ->label('Nom d\'utilisateur')
                    ->required()
                    ->maxLength(64),
                Select::make('customer_id')
                    ->label('Client')
                    ->relationship('customer', 'customer_number')
                    ->searchable()
                    ->required(),
                Select::make('authentication_type')
                    ->label('Authentification')
                    ->options(['pap' => 'PAP', 'chap' => 'CHAP', 'mac' => 'MAC', 'peap' => 'PEAP'])
                    ->default('pap')
                    ->required(),
                Select::make('status')
                    ->label('Statut')
                    ->options(['pending' => 'En attente', 'active' => 'Actif', 'suspended' => 'Suspendu', 'disabled' => 'Désactivé'])
                    ->default('pending')
                    ->required(),
                Select::make('mac_auth_enabled')
                    ->label('Authentification MAC')
                    ->options([0 => 'Non', 1 => 'Oui'])
                    ->default(0),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('username')
            ->columns([
                TextColumn::make('username')
                    ->label('Utilisateur')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('customer.customer_number')
                    ->label('Client')
                    ->searchable(),
                TextColumn::make('authentication_type')
                    ->label('Auth')
                    ->badge(),
                TextColumn::make('status')
                    ->label('Statut')
                    ->badge(),
                IconColumn::make('mac_auth_enabled')
                    ->label('MAC')
                    ->boolean(),
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
            'index' => ManageNetworkAccounts::route('/'),
        ];
    }
}
