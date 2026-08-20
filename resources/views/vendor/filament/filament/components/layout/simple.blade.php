@php
    use Filament\Support\Enums\Width;

    $livewire ??= null;

    $renderHookScopes = $livewire?->getRenderHookScopes();
    $maxContentWidth ??= (filament()->getSimplePageMaxContentWidth() ?? Width::Large);

    if (is_string($maxContentWidth)) {
        $maxContentWidth = Width::tryFrom($maxContentWidth) ?? $maxContentWidth;
    }
@endphp

<x-filament-panels::layout.base :livewire="$livewire">
    @props([
        'after' => null,
        'heading' => null,
        'subheading' => null,
    ])

    <div class="fi-simple-layout">
        @if (($hasTopbar ?? true) && filament()->auth()->check())
            <a href="#fi-main-content" class="fi-skip-link fi-sr-only">
                {{ __('filament-panels::layout.skip_to_content.label') }}
            </a>
        @endif

        {{ \Filament\Support\Facades\FilamentView::renderHook(\Filament\View\PanelsRenderHook::SIMPLE_LAYOUT_START, scopes: $renderHookScopes) }}

        @if (($hasTopbar ?? true) && filament()->auth()->check())
            <div class="fi-simple-layout-header">
                @if (filament()->hasDatabaseNotifications())
                    @livewire(filament()->getDatabaseNotificationsLivewireComponent(), [
                        'lazy' => filament()->hasLazyLoadedDatabaseNotifications(),
                        'position' => \Filament\Enums\DatabaseNotificationsPosition::Topbar,
                    ])
                @endif

                @if (filament()->hasUserMenu())
                    @livewire(Filament\Livewire\SimpleUserMenu::class)
                @endif
            </div>
        @endif

        @if (! filament()->auth()->check())
            {{-- Split-screen aside for login/forgot/reset pages --}}
            <div class="fi-login-page-aside">
                <div class="fi-login-page-aside-content">
                    <h2>Bienvenue sur<br>votre espace admin</h2>
                    <p>Gérez les abonnements, factures et comptes réseau de vos clients depuis une seule interface.</p>
                    <div class="fi-login-page-aside-features">
                        <div class="fi-login-page-aside-feature">
                            <i class="bi bi-people"></i>
                            <span>Gestion des clients</span>
                        </div>
                        <div class="fi-login-page-aside-feature">
                            <i class="bi bi-receipt"></i>
                            <span>Facturation automatisée</span>
                        </div>
                        <div class="fi-login-page-aside-feature">
                            <i class="bi bi-hdd-network"></i>
                            <span>Synchronisation Radius</span>
                        </div>
                    </div>
                </div>
                <div class="fi-login-page-aside-footer">
                    <span>&copy; {{ now()->year }} JENY SAS &middot; Tableau de bord administrateur</span>
                </div>
            </div>
        @endif

        <div class="fi-simple-main-ctn">
            <main
                id="fi-main-content"
                tabindex="-1"
                @class([
                    'fi-simple-main',
                    ($maxContentWidth instanceof Width) ? "fi-width-{$maxContentWidth->value}" : $maxContentWidth,
                ])
            >
                {{ $slot }}
            </main>
        </div>

        {{ \Filament\Support\Facades\FilamentView::renderHook(\Filament\View\PanelsRenderHook::FOOTER, scopes: $renderHookScopes) }}

        {{ \Filament\Support\Facades\FilamentView::renderHook(\Filament\View\PanelsRenderHook::SIMPLE_LAYOUT_END, scopes: $renderHookScopes) }}
    </div>
</x-filament-panels::layout.base>
