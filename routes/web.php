<?php

use App\Http\Controllers\Auth\ForgotPasswordController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\LogoutController;
use App\Http\Controllers\Auth\ResetPasswordController;
use App\Http\Controllers\Client\InvoiceController;
use App\Http\Controllers\Client\PaymentController;
use App\Http\Controllers\Client\ProfileController;
use App\Http\Controllers\Client\SessionController;
use App\Http\Controllers\DashboardController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [LoginController::class, 'login'])->middleware('throttle:5,1');

    Route::get('/password/reset', [ForgotPasswordController::class, 'showLinkRequestForm'])->name('password.request');
    Route::post('/password/email', [ForgotPasswordController::class, 'sendResetLink'])->middleware('throttle:5,1')->name('password.email');
    Route::get('/password/reset/{token}', [ResetPasswordController::class, 'showResetForm'])->name('password.reset');
    Route::post('/password/reset', [ResetPasswordController::class, 'reset'])->name('password.update');
});

Route::post('/logout', [LogoutController::class, 'logout'])->name('logout');

Route::middleware(['auth', 'is_active'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'client'])->middleware('role:client')->name('dashboard.index');

    Route::get('/admin-home', function () {
        return redirect('/admin');
    })->middleware('role:staff')->name('admin.index');

    Route::middleware('role:client')->prefix('espace')->name('client.')->group(function () {
        Route::get('factures', [InvoiceController::class, 'index'])->name('invoices.index');
        Route::get('factures/{invoice:uuid}', [InvoiceController::class, 'show'])->name('invoices.show');
        Route::get('factures/{invoice:uuid}/pdf', [InvoiceController::class, 'pdf'])->name('invoices.pdf');
        Route::post('factures/{invoice:uuid}/payer', [PaymentController::class, 'store'])->name('payments.store');
        Route::get('paiements', [PaymentController::class, 'index'])->name('payments.index');
        Route::get('consommation', [SessionController::class, 'index'])->name('sessions.index');
        Route::get('profil', [ProfileController::class, 'show'])->name('profile.show');
    });
});
