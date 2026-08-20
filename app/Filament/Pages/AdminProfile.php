<?php

namespace App\Filament\Pages;

use Filament\Auth\Pages\EditProfile;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\HtmlString;
use Illuminate\Validation\Rules\Password;

class AdminProfile extends EditProfile
{
    protected static string | \BackedEnum | null $navigationIcon = Heroicon::UserCircle;

    protected static ?string $navigationLabel = 'Mon profil';

    protected static ?string $slug = 'profile';

    protected static ?string $title = 'Mon profil';

    protected static bool $isDiscovered = false;

    public static function isSimple(): bool
    {
        return false;
    }

    public static function getLabel(): string
    {
        return 'Mon profil';
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $user = Auth::user();
        $data['role'] = $user->getRoleNames()->first() ?? '—';
        $data['status'] = ucfirst($user->status ?? '—');
        $data['last_login_at'] = $user->last_login_at?->format('d/m/Y H:i') ?? '—';
        $data['phone'] = $user->phone ?? '';

        return $data;
    }

    public function form(Schema $schema): Schema
    {
        $user = Auth::user();

        return $schema->components([
            Group::make([
                Section::make('Informations du compte')
                    ->icon('heroicon-o-information-circle')
                    ->schema([
                        Placeholder::make('role_label')
                            ->label('Rôle')
                            ->content(fn () => $user->getRoleNames()->first() ?? '—'),
                        Placeholder::make('status_label')
                            ->label('Statut')
                            ->content(fn () => ucfirst($user->status ?? '—')),
                        Placeholder::make('last_login_label')
                            ->label('Dernière connexion')
                            ->content(fn () => $user->last_login_at?->format('d/m/Y H:i') ?? '—'),
                    ])->columns(3),

                Section::make('Informations personnelles')
                    ->icon('heroicon-o-user')
                    ->schema([
                        TextInput::make('name')
                            ->label('Nom complet')
                            ->required()
                            ->maxLength(255)
                            ->autofocus(),
                        TextInput::make('email')
                            ->label('Adresse email')
                            ->email()
                            ->required()
                            ->maxLength(255)
                            ->unique(ignoreRecord: true),
                        TextInput::make('phone')
                            ->label('Téléphone')
                            ->tel()
                            ->maxLength(50),
                    ])->columns(3),

                Section::make('Changer le mot de passe')
                    ->icon('heroicon-o-key')
                    ->description('Laissez vide pour conserver le mot de passe actuel.')
                    ->schema([
                        TextInput::make('password')
                            ->label('Nouveau mot de passe')
                            ->password()
                            ->revealable(filament()->arePasswordsRevealable())
                            ->rule(Password::default())
                            ->showAllValidationMessages()
                            ->autocomplete('new-password')
                            ->dehydrated(fn ($state): bool => filled($state))
                            ->dehydrateStateUsing(fn ($state): string => \Hash::make($state)),
                        TextInput::make('passwordConfirmation')
                            ->label('Confirmer le mot de passe')
                            ->password()
                            ->revealable(filament()->arePasswordsRevealable())
                            ->autocomplete('new-password')
                            ->required()
                            ->visible(fn ($get): bool => filled($get('password')))
                            ->dehydrated(false),
                        TextInput::make('currentPassword')
                            ->label('Mot de passe actuel')
                            ->password()
                            ->revealable(filament()->arePasswordsRevealable())
                            ->autocomplete('current-password')
                            ->currentPassword(guard: \Filament\Facades\Filament::getAuthGuard())
                            ->required()
                            ->visible(fn ($get): bool => filled($get('password')) || ($get('email') !== $user->email))
                            ->dehydrated(false),
                    ])->columns(3),
            ])->columnSpan(['lg' => 2]),

            Group::make([
                Section::make('Photo de profil')
                    ->icon('heroicon-o-camera')
                    ->schema([
                        Placeholder::make('avatar_placeholder')
                            ->label('')
                            ->content(fn () => new HtmlString('<div style="width:120px;height:120px;border-radius:50%;background:#0B2545;color:#C9A24B;display:flex;align-items:center;justify-content:center;font-size:2.5rem;font-weight:700;">' . strtoupper(substr($user->name ?? 'A', 0, 1)) . '</div>')),
                    ]),
            ])->columnSpan(['lg' => 1]),
        ]);
    }
}
