<?php

namespace App\Filament\Resources\Invoices;

use App\Filament\Resources\Concerns\AccessControlledResource;
use App\Filament\Resources\Invoices\Pages\ManageInvoices;
use App\Models\Invoice;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class InvoiceResource extends Resource
{
    use AccessControlledResource;

    protected static ?string $model = Invoice::class;

    protected static function permissionPrefix(): string
    {
        return 'invoice';
    }

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentText;

    protected static ?string $navigationLabel = 'Factures';

    protected static ?string $recordTitleAttribute = 'invoice_number';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('invoice_number')
                    ->label('N° facture')
                    ->maxLength(40),
                Select::make('customer_id')
                    ->label('Client')
                    ->relationship('customer', 'customer_number')
                    ->searchable()
                    ->required(),
                Select::make('subscription_id')
                    ->label('Abonnement')
                    ->relationship('subscription', 'subscription_number')
                    ->searchable(),
                Select::make('status')
                    ->label('Statut')
                    ->options([
                        'draft' => 'Brouillon',
                        'issued' => 'Émise',
                        'partially_paid' => 'Partiellement payée',
                        'paid' => 'Payée',
                        'overdue' => 'En retard',
                        'cancelled' => 'Annulée',
                        'refunded' => 'Remboursée',
                    ])
                    ->default('draft')
                    ->required(),
                DatePicker::make('issue_date')
                    ->label('Date d\'émission')
                    ->default(now()),
                DatePicker::make('due_date')
                    ->label('Échéance'),
                TextInput::make('subtotal')
                    ->label('Sous-total')
                    ->numeric()
                    ->default(0),
                TextInput::make('discount')
                    ->label('Remise')
                    ->numeric()
                    ->default(0),
                TextInput::make('tax')
                    ->label('Taxe')
                    ->numeric()
                    ->default(0),
                TextInput::make('total')
                    ->label('Total')
                    ->numeric()
                    ->default(0)
                    ->required(),
                TextInput::make('amount_paid')
                    ->label('Montant payé')
                    ->numeric()
                    ->default(0),
                TextInput::make('currency')
                    ->label('Devise')
                    ->default('XOF')
                    ->maxLength(3),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('invoice_number')
            ->columns([
                TextColumn::make('invoice_number')
                    ->label('N° facture')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('customer.customer_number')
                    ->label('Client')
                    ->searchable(),
                TextColumn::make('subscription.subscription_number')
                    ->label('Abonnement')
                    ->searchable(),
                TextColumn::make('status')
                    ->label('Statut')
                    ->badge(),
                TextColumn::make('total')
                    ->label('Total')
                    ->money('XOF')
                    ->sortable(),
                TextColumn::make('amount_due')
                    ->label('Reste dû')
                    ->money('XOF'),
                TextColumn::make('due_date')
                    ->label('Échéance')
                    ->date()
                    ->sortable(),
                TextColumn::make('issue_date')
                    ->label('Émise le')
                    ->date()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('Statut')
                    ->options([
                        'draft' => 'Brouillon',
                        'issued' => 'Émise',
                        'partially_paid' => 'Partiellement payée',
                        'paid' => 'Payée',
                        'overdue' => 'En retard',
                        'cancelled' => 'Annulée',
                        'refunded' => 'Remboursée',
                    ]),
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
            'index' => ManageInvoices::route('/'),
        ];
    }
}
