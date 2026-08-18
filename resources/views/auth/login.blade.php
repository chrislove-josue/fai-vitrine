@extends('layouts.app')

@section('title', 'Connexion')

@section('content')
    <div class="auth-box card">
        <p class="eyebrow">Espace client &middot; JENY SAS</p>
        <h2>Connexion</h2>
        <form method="POST" action="{{ route('login') }}">
            @csrf
            <label for="email">Email</label>
            <input id="email" type="email" name="email" value="{{ old('email') }}" required autofocus autocomplete="email" placeholder="vous@exemple.com">

            <label for="password">Mot de passe</label>
            <input id="password" type="password" name="password" required autocomplete="current-password" placeholder="••••••••">

            <div class="check-row">
                <input type="checkbox" name="remember" value="1"> Se souvenir de moi
            </div>

            <div style="margin-top:1rem">
                <button class="btn primary" type="submit" style="width:100%">Se connecter</button>
            </div>
        </form>
        <p class="muted auth-foot">
            <a href="{{ route('password.request') }}">Mot de passe oublié ?</a>
        </p>
    </div>
@endsection