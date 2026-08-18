<?php

namespace App\Filament\Resources\Subscriptions;

use App\Filament\Resources\Concerns\AccessControlledResource;
use App\Filament\Resources\Subscriptions\Pages\ManageSubscriptions;
use App\Models\Subscription;
use App\Services\BillingService;
use App\Services\SubscriptionLifecycleService;
use App\Support\AdminAudit;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use InvalidArgumentException;

class SubscriptionResource extends Resource
{
    use AccessControlledResource;

    protected static ?string $model = Subscription::class;

    protected static function permissionPrefix(): string
    {
        return 'subscription';
    }

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedReceiptPercent;

    protected static ?string $navigationLabel = 'Abonnements';

    protected static ?string $recordTitleAttribute = 'subscription_number';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('subscription_number')
                    ->label('N° abonnement')
                    ->maxLength(40),
                Select::make('customer_id')
                    ->label('Client')
                    ->relationship('customer', 'customer_number')
                    ->searchable()
                    ->required(),
                Select::make('offer_id')
                    ->label('Offre')
                    ->relationship('offer', 'name')
                    ->required(),
                Select::make('status')
                    ->label('Statut')
                    ->options([
                        'pending' => 'En attente',
                        'active' => 'Actif',
                        'grace_period' => 'Période de grâce',
                        'suspended' => 'Suspendu',
                        'expired' => 'Expiré',
                        'cancelled' => 'Annulé',
                        'terminated' => 'Résilié',
                    ])
                    ->default('pending')
                    ->required(),
                DatePicker::make('starts_at')
                    ->label('Début'),
                DatePicker::make('expires_at')
                    ->label('Échéance'),
                DatePicker::make('next_renewal_at')
                    ->label('Prochain renouvellement'),
                TextInput::make('price')
                    ->label('Prix')
                    ->numeric()
                    ->required(),
                TextInput::make('currency')
                    ->label('Devise')
                    ->default('XOF')
                    ->maxLength(3),
                Select::make('auto_renew')
                    ->label('Renouvellement auto')
                    ->options([0 => 'Non', 1 => 'Oui'])
                    ->default(0),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('subscription_number')
            ->columns([
                TextColumn::make('subscription_number')
                    ->label('N° abonnement')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('customer.customer_number')
                    ->label('Client')
                    ->searchable(),
                TextColumn::make('offer.name')
                    ->label('Offre')
                    ->searchable(),
                TextColumn::make('status')
                    ->label('Statut')
                    ->badge(),
                TextColumn::make('expires_at')
                    ->label('Échéance')
                    ->date()
                    ->sortable(),
                TextColumn::make('price')
                    ->label('Prix')
                    ->money('XOF'),
                TextColumn::make('created_at')
                    ->label('Créé le')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('Statut')
                    ->options([
                        'pending' => 'En attente',
                        'active' => 'Actif',
                        'grace_period' => 'Période de grâce',
                        'suspended' => 'Suspendu',
                        'expired' => 'Expiré',
                        'cancelled' => 'Annulé',
                        'terminated' => 'Résilié',
                    ]),
            ])
            ->recordActions([
                Action::make('activate')
                    ->label('Activer')
                    ->icon(Heroicon::OutlinedPlayCircle)
                    ->color('success')
                    ->requiresConfirmation()
                    ->visible(fn (Model $record) => auth()->user()?->can('subscription.update') && in_array($record->status, [Subscription::STATUS_PENDING], true))
                    ->action(fn (Model $record) => self::runLifecycle($record, 'activate')),
                Action::make('reactivate')
                    ->label('Réactiver')
                    ->icon(Heroicon::OutlinedArrowPathRoundedSquare)
                    ->color('success')
                    ->requiresConfirmation()
                    ->visible(fn (Model $record) => auth()->user()?->can('subscription.reactivate') && in_array($record->status, [Subscription::STATUS_GRACE_PERIOD, Subscription::STATUS_SUSPENDED, Subscription::STATUS_EXPIRED], true))
                    ->action(fn (Model $record) => self::runLifecycle($record, 'reactivate')),
                Action::make('suspend')
                    ->label('Suspendre')
                    ->icon(Heroicon::OutlinedPauseCircle)
                    ->color('warning')
                    ->requiresConfirmation()
                    ->visible(fn (Model $record) => auth()->user()?->can('subscription.suspend') && in_array($record->status, [Subscription::STATUS_ACTIVE, Subscription::STATUS_GRACE_PERIOD], true))
                    ->action(fn (Model $record) => self::runLifecycle($record, 'suspend')),
                Action::make('terminate')
                    ->label('Résilier')
                    ->icon(Heroicon::OutlinedXCircle)
                    ->color('danger')
                    ->requiresConfirmation()
                    ->visible(fn (Model $record) => auth()->user()?->can('subscription.terminate') && ! in_array($record->status, [Subscription::STATUS_TERMINATED], true))
                    ->action(fn (Model $record) => self::runLifecycle($record, 'terminate')),
                Action::make('issue_invoice')
                    ->label('Émettre une facture')
                    ->icon(Heroicon::OutlinedDocumentCurrencyDollar)
                    ->requiresConfirmation()
                    ->visible(fn (Model $record) => auth()->user()?->can('invoice.create') && ! in_array($record->status, [Subscription::STATUS_PENDING, Subscription::STATUS_TERMINATED], true))
                    ->action(function (Model $record) {
                        try {
                            $invoice = app(BillingService::class)->issueInvoiceForSubscription($record);

                            AdminAudit::log(
                                'subscription.invoice_issued',
                                $record::class,
                                $record->uuid,
                                ['invoice_number' => $invoice->invoice_number],
                            );

                            Notification::make()->title('Facture émise.')->success()->send();
                        } catch (InvalidArgumentException $e) {
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

    private static function runLifecycle(Subscription $record, string $transition): void
    {
        try {
            $subscription = match ($transition) {
                'activate' => app(SubscriptionLifecycleService::class)->activate($record, source: 'admin'),
                'reactivate' => app(SubscriptionLifecycleService::class)->reactivate($record, source: 'admin'),
                'suspend' => app(SubscriptionLifecycleService::class)->suspend($record, reason: 'admin', source: 'admin'),
                'terminate' => app(SubscriptionLifecycleService::class)->terminate($record, reason: 'admin', source: 'admin'),
                default => throw new InvalidArgumentException('Transition inconnue.'),
            };

            AdminAudit::log(
                'subscription.'.$transition,
                $record::class,
                $record->uuid,
                ['from' => $record->status, 'to' => $subscription->status],
            );

            Notification::make()->title('Abonnement '.$transition.'.')->success()->send();
        } catch (InvalidArgumentException $e) {
            Notification::make()->title($e->getMessage())->danger()->send();
        }
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageSubscriptions::route('/'),
        ];
    }
}
