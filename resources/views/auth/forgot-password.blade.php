@extends('layouts.app')

@section('title', 'Mot de passe oublié')

@section('content')
    <div class="auth-box card">
        <h2>Mot de passe oublié</h2>
        <form method="POST" action="{{ route('password.email') }}">
            @csrf
            <label for="email">Email</label>
            <input id="email" type="email" name="email" value="{{ old('email') }}" required autofocus>

            <div style="margin-top:1rem">
                <button class="btn" type="submit">Envoyer le lien de réinitialisation</button>
            </div>
        </form>
        <p class="muted" style="margin-top:1rem">
            <a href="{{ route('login') }}">Retour à la connexion</a>
        </p>
    </div>
@endsection