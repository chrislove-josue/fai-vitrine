@extends('layouts.app')

@section('title', 'Réinitialiser le mot de passe')

@section('content')
    <div class="auth-card">
        <div class="auth-card-right">
            <div class="auth-form-wrap">
                <div class="auth-form-header">
                    <i class="bi bi-shield-lock auth-form-icon"></i>
                    <h1>Nouveau mot de passe</h1>
                    <p>Entrez et confirmez votre nouveau mot de passe</p>
                </div>

                <form method="POST" action="{{ route('password.update') }}" class="auth-form">
                    @csrf
                    <input type="hidden" name="token" value="{{ $token }}">

                    <div class="auth-field">
                        <label for="email">
                            <i class="bi bi-envelope"></i>
                            Adresse email
                        </label>
                        <input id="email" type="email" name="email" value="{{ $email ?? old('email') }}" required autofocus autocomplete="email">
                        @error('email')
                            <span class="auth-field-error">{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="auth-field">
                        <label for="password">
                            <i class="bi bi-lock"></i>
                            Nouveau mot de passe
                        </label>
                        <input id="password" type="password" name="password" required autocomplete="new-password" placeholder="••••••••">
                        @error('password')
                            <span class="auth-field-error">{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="auth-field">
                        <label for="password_confirmation">
                            <i class="bi bi-lock-fill"></i>
                            Confirmer le mot de passe
                        </label>
                        <input id="password_confirmation" type="password" name="password_confirmation" required autocomplete="new-password" placeholder="••••••••">
                    </div>

                    <button class="auth-btn" type="submit">
                        <i class="bi bi-check-lg"></i>
                        Réinitialiser
                    </button>
                </form>
            </div>
        </div>
    </div>
@endsection
