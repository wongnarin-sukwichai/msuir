<?php
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\GoogleAuthController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\ItemController;

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('Welcome');
})->name('home');

Route::get('/category/{id}', [CategoryController::class, 'show'])->name('category.show');
Route::get('/item/{id}', [ItemController::class, 'show'])->name('item.show');

// สำหรับ Email/Password ปกติ
Route::post('/login', [AuthenticatedSessionController::class, 'store']);
Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])->name('logout');

// สำหรับ Google OAuth
Route::get('/auth/google/redirect', [GoogleAuthController::class, 'redirectToGoogle'])->name('google.redirect');
Route::get('/auth/google/callback', [GoogleAuthController::class, 'handleGoogleCallback'])->name('google.callback');

Route::get('dashboard', function () {
    return Inertia::render('Dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

// หน้าทดสอบ SPA
Route::get('/test-spa', function () {
    return Inertia::render('TestSPA');
});

require __DIR__.'/settings.php';
require __DIR__.'/auth.php';
