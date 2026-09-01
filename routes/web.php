<?php
use App\Http\Controllers\Admin\ImpersonateController;
use App\Http\Controllers\Admin\MemberController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\GoogleAuthController;
use App\Http\Controllers\CollectionController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ItemController;

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('Welcome');
})->name('home');

Route::get('/collection/{id}', [CollectionController::class, 'show'])->name('collection.show');
Route::get('/item/{id}', [ItemController::class, 'show'])->name('item.show');

// สำหรับ Email/Password ปกติ
Route::post('/login', [AuthenticatedSessionController::class, 'store']);
Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])->name('logout');

// สำหรับ Google OAuth
Route::get('/auth/google/redirect', [GoogleAuthController::class, 'redirectToGoogle'])->name('google.redirect');
Route::get('/auth/google/callback', [GoogleAuthController::class, 'handleGoogleCallback'])->name('google.callback');

Route::get('dashboard', [DashboardController::class, 'index'])->middleware(['auth', 'verified'])->name('dashboard');

Route::post('/impersonate/leave', [ImpersonateController::class, 'leave'])
    ->middleware('auth')->name('impersonate.leave');

Route::middleware(['auth', 'verified', 'role:3'])->prefix('admin')->name('admin.')->group(function () {
    Route::post('/members', [MemberController::class, 'store'])->name('members.store');
    Route::put('/members/{member}', [MemberController::class, 'update'])->name('members.update');
    Route::patch('/members/{member}/status', [MemberController::class, 'toggleStatus'])->name('members.status');
    Route::delete('/members/{member}', [MemberController::class, 'destroy'])->name('members.destroy');
    Route::post('/members/{member}/impersonate', [MemberController::class, 'impersonate'])->name('members.impersonate');
});

// หน้าทดสอบ SPA
Route::get('/test-spa', function () {
    return Inertia::render('TestSPA');
});

require __DIR__.'/settings.php';
require __DIR__.'/auth.php';
