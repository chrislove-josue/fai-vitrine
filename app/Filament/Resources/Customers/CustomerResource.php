<?php

namespace App\Filament\Resources\Customers;

use App\Filament\Resources\Concerns\AccessControlledResource;
use App\Filament\Resources\Customers\Pages\ManageCustomers;
use App\Models\Customer;
use App\Services\ReferenceGenerator;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class CustomerResource extends Resource
{
    use AccessControlledResource;

    protected static ?string $model = Customer::class;

    protected static function permissionPrefix(): string
    {
        return 'customer';
    }

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUsers;

    protected static ?string $navigationLabel = 'Clients';

    protected static ?string $recordTitleAttribute = 'customer_number';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('customer_number')
                    ->label('N° client')
                    ->default(fn () => ReferenceGenerator::customerNumber())
                    ->required()
                    ->maxLength(40),
                Select::make('type')
                    ->label('Type')
                    ->options(['individual' => 'Particulier', 'company' => 'Entreprise'])
                    ->default('individual')
                    ->required(),
                Select::make('status')
                    ->label('Statut')
                    ->options([
                        'prospect' => 'Prospect',
                        'pending' => 'En attente',
                        'active' => 'Actif',
                        'suspended' => 'Suspendu',
                        'blocked' => 'Bloqué',
                        'terminated' => 'Résilié',
                    ])
                    ->default('prospect')
                    ->required(),
                TextInput::make('first_name')
                    ->label('Prénom'),
                TextInput::make('last_name')
                    ->label('Nom'),
                TextInput::make('company_name')
                    ->label('Raison sociale'),
                TextInput::make('email')
                    ->label('Email')
                    ->email(),
                TextInput::make('phone')
                    ->label('Téléphone')
                    ->tel(),
                DatePicker::make('birth_date')
                    ->label('Date de naissance'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('customer_number')
            ->columns([
                TextColumn::make('customer_number')
                    ->label('N° client')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('display_name')
                    ->label('Nom')
                    ->searchable(query: function (Builder $query, string $search): Builder {
                        return $query->where(fn (Builder $sub) => $sub
                            ->where('first_name', 'like', "%{$search}%")
                            ->orWhere('last_name', 'like', "%{$search}%")
                            ->orWhere('company_name', 'like', "%{$search}%"));
                    }),
                TextColumn::make('type')
                    ->label('Type')
                    ->badge(),
                TextColumn::make('status')
                    ->label('Statut')
                    ->badge(),
                TextColumn::make('email')
                    ->label('Email')
                    ->searchable(),
                TextColumn::make('phone')
                    ->label('Téléphone')
                    ->searchable(),
                TextColumn::make('created_at')
                    ->label('Créé le')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
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
            'index' => ManageCustomers::route('/'),
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
