<?php

namespace App\Filament\Pages\Auth;

use Filament\Auth\Pages\Login;
use Illuminate\Support\Facades\Auth;

class UnifiedLogin extends Login
{
    public function mount(): void
    {
        if (Auth::check()) {
            $this->redirect(Auth::user()->homeRoute());
            return;
        }

        $this->redirect(route('login'));
    }
}
