@if (! filament()->auth()->check())
    <div class="fi-login-page-aside">
        <div class="fi-login-page-aside-content">
            <img src="{{ asset('img/logo-jeny.png') }}" alt="JENY SAS" class="fi-login-page-aside-logo">
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
