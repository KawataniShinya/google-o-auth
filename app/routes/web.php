<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\TwoFactorAuthController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

// 二要素認証画面を表示するルート
Route::get('/two-factor-authentication', [TwoFactorAuthController::class, 'show'])
    ->name('two-factor-authentication');

// 二要素認証を有効にするルート
Route::get('/generate-qr-code', [TwoFactorAuthController::class, 'enableTwoFactor']);

// 二要素認証を検証するルート
Route::post('/two-factor-authentication', [TwoFactorAuthController::class, 'verify'])
    ->name('two-factor-authentication.verify');

// シークレットをリセットするルート
Route::post('/reset-secret', [TwoFactorAuthController::class, 'resetSecret'])
    ->middleware(['auth'])->name('reset.secret');

Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
        'laravelVersion' => Application::VERSION,
        'phpVersion' => PHP_VERSION,
    ]);
});

Route::get('/dashboard', function () {
    return Inertia::render('Dashboard');
})->middleware(['auth', 'verified', '2fa'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
