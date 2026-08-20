@extends('layouts.app')

@section('title', 'Mot de passe oublié')

@section('content')
    <div class="auth-card">
        <div class="auth-card-right">
            <div class="auth-form-wrap">
                <div class="auth-form-header">
                    <i class="bi bi-key auth-form-icon"></i>
                    <h1>Mot de passe oublié</h1>
                    <p>Entrez votre email pour recevoir le lien de réinitialisation</p>
                </div>

                <form method="POST" action="{{ route('password.email') }}" class="auth-form">
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

                    <button class="auth-btn" type="submit">
                        <i class="bi bi-send"></i>
                        Envoyer le lien
                    </button>
                </form>
            </div>

            <div class="auth-form-footer">
                <a href="{{ route('login') }}">
                    <i class="bi bi-arrow-left"></i>
                    Retour à la connexion
                </a>
            </div>
        </div>
    </div>
@endsection
