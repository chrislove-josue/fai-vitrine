@extends('layouts.app')

@section('title', 'Connexion')

@section('content')
    <div class="auth-card">
        <div class="auth-card-left">
            <div class="auth-card-left-content">
                <img src="{{ asset('img/logo-jeny.png') }}" alt="JENY SAS" class="auth-logo">
                <h2>Bienvenue sur<br>votre espace client</h2>
                <p>Gérez vos abonnements, factures et suivez votre consommation en temps réel.</p>
                <div class="auth-features">
                    <div class="auth-feature">
                        <i class="bi bi-receipt"></i>
                        <span>Factures en ligne</span>
                    </div>
                    <div class="auth-feature">
                        <i class="bi bi-graph-up"></i>
                        <span>Consommation réseau</span>
                    </div>
                    <div class="auth-feature">
                        <i class="bi bi-credit-card"></i>
                        <span>Paiement Mobile Money</span>
                    </div>
                </div>
            </div>
            <div class="auth-card-left-footer">
                <span>&copy; {{ now()->year }} JENY SAS &middot; Internet fibre, sans compromis</span>
            </div>
        </div>

        <div class="auth-card-right">
            <div class="auth-form-wrap">
                <div class="auth-form-header">
                    <i class="bi bi-person-circle auth-form-icon"></i>
                    <h1>Connexion</h1>
                    <p>Connectez-vous pour accéder à votre espace</p>
                </div>

                <form method="POST" action="{{ route('login') }}" class="auth-form">
                    @csrf
                    <div class="auth-field">
                        <label for="email">
                            <i class="bi bi-envelope"></i>
                            Adresse email
                        </label>
                        <input id="email" type="email" name="email" value="{{ old('email') }}" required autofocus autocomplete="email" placeholder="vous@exemple.com">
                        @error('email')
                            <span class="auth-field-error">{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="auth-field">
                        <label for="password">
                            <i class="bi bi-lock"></i>
                            Mot de passe
                        </label>
                        <input id="password" type="password" name="password" required autocomplete="current-password" placeholder="••••••••">
                        @error('password')
                            <span class="auth-field-error">{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="auth-field-row">
                        <label class="auth-checkbox">
                            <input type="checkbox" name="remember" value="1">
                            <span>Se souvenir de moi</span>
                        </label>
                        <a href="{{ route('password.request') }}" class="auth-link">Mot de passe oublié ?</a>
                    </div>

                    <button class="auth-btn" type="submit">
                        <i class="bi bi-box-arrow-in-right"></i>
                        Se connecter
                    </button>
                </form>
            </div>
        </div>
    </div>
@endsection
