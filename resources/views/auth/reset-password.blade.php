@extends('layouts.app')

@section('title', 'Réinitialiser le mot de passe')

@section('content')
    <div class="auth-box card">
        <h2>Réinitialiser le mot de passe</h2>
        <form method="POST" action="{{ route('password.update') }}">
            @csrf
            <input type="hidden" name="token" value="{{ $token }}">

            <label for="email">Email</label>
            <input id="email" type="email" name="email" value="{{ $email ?? old('email') }}" required autofocus>

            <label for="password">Nouveau mot de passe</label>
            <input id="password" type="password" name="password" required autocomplete="new-password">

            <label for="password_confirmation">Confirmation</label>
            <input id="password_confirmation" type="password" name="password_confirmation" required autocomplete="new-password">

            <div style="margin-top:1rem">
                <button class="btn" type="submit">Réinitialiser</button>
            </div>
        </form>
    </div>
@endsection