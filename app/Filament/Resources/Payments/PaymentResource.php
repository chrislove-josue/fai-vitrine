<?php

namespace App\Filament\Resources\Payments;

use App\Filament\Resources\Concerns\AccessControlledResource;
use App\Filament\Resources\Payments\Pages\ManagePayments;
use App\Models\Payment;
use App\Services\BillingService;
use App\Support\AdminAudit;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class PaymentResource extends Resource
{
    use AccessControlledResource;

    protected static ?string $model = Payment::class;

    protected static function permissionPrefix(): string
    {
        return 'payment';
    }

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBanknotes;

    protected static ?string $navigationLabel = 'Paiements';

    protected static ?string $recordTitleAttribute = 'payment_reference';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('payment_reference')
                    ->label('Référence')
                    ->disabled()
                    ->dehydrated()
                    ->maxLength(64),
                Select::make('customer_id')
                    ->label('Client')
                    ->relationship('customer', 'customer_number')
                    ->searchable()
                    ->required(),
                Select::make('invoice_id')
                    ->label('Facture')
                    ->relationship('invoice', 'invoice_number')
                    ->searchable(),
                Select::make('subscription_id')
                    ->label('Abonnement')
                    ->relationship('subscription', 'subscription_number')
                    ->searchable(),
                TextInput::make('amount')
                    ->label('Montant')
                    ->numeric()
                    ->default(0)
                    ->required(),
                TextInput::make('currency')
                    ->label('Devise')
                    ->default('XOF')
                    ->maxLength(3)
                    ->required(),
                Select::make('method')
                    ->label('Méthode')
                    ->options([
                        'mobile_money' => 'Mobile money',
                        'card' => 'Carte',
                        'bank_transfer' => 'Virement',
                        'cash' => 'Espèces',
                        'manual' => 'Manuel',
                    ])
                    ->default('manual')
                    ->required(),
                TextInput::make('provider')
                    ->label('Prestataire'),
                Select::make('status')
                    ->label('Statut')
                    ->options([
                        'pending' => 'En attente',
                        'processing' => 'En cours',
                        'successful' => 'Réussi',
                        'failed' => 'Échoué',
                        'cancelled' => 'Annulé',
                        'refunded' => 'Remboursé',
                        'partially_refunded' => 'Partiellement remboursé',
                    ])
                    ->default('pending')
                    ->required(),
                TextInput::make('transaction_id')
                    ->label('Transaction'),
                DateTimePicker::make('paid_at')
                    ->label('Payé le'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('payment_reference')
            ->columns([
                TextColumn::make('payment_reference')
                    ->label('Référence')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('customer.customer_number')
                    ->label('Client')
                    ->searchable(),
                TextColumn::make('invoice.invoice_number')
                    ->label('Facture')
                    ->searchable(),
                TextColumn::make('amount')
                    ->label('Montant')
                    ->money('XOF')
                    ->sortable(),
                TextColumn::make('method')
                    ->label('Méthode')
                    ->badge(),
                TextColumn::make('status')
                    ->label('Statut')
                    ->badge(),
                TextColumn::make('provider_reference')
                    ->label('Réf. prestataire')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('paid_at')
                    ->label('Payé le')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('created_at')
                    ->label('Créé le')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->recordActions([
                Action::make('acquire')
                    ->label('Acquitter (manuel)')
                    ->icon(Heroicon::OutlinedCheckCircle)
                    ->color('success')
                    ->requiresConfirmation()
                    ->visible(fn (Model $record) => auth()->user()?->can('payment.create') && $record->status === Payment::STATUS_PENDING && $record->invoice_id !== null)
                    ->action(function (Model $record) {
                        try {
                            $payment = app(BillingService::class)->applyPayment($record);

                            AdminAudit::log(
                                'payment.apply',
                                $record::class,
                                $record->uuid,
                                [
                                    'payment_reference' => $payment->payment_reference,
                                    'invoice_number' => $payment->invoice->invoice_number,
                                    'amount' => $payment->amount,
                                ],
                            );

                            Notification::make()->title('Paiement acquitté.')->success()->send();
                        } catch (\InvalidArgumentException $e) {
                            Notification::make()->title($e->getMessage())->danger()->send();
                        }
                    }),
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
            'index' => ManagePayments::route('/'),
        ];
    }
}
