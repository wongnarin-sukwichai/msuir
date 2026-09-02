<?php
use App\Http\Controllers\Admin\ImpersonateController;
use App\Http\Controllers\Admin\MemberController;
use App\Http\Controllers\Admin\RepositoryController;
use App\Http\Controllers\Admin\RepositoryImportController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\GoogleAuthController;
use App\Http\Controllers\CollectionController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\ItemController;

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', [HomeController::class, 'index'])->name('home');

Route::get('/collection/{id}', [CollectionController::class, 'show'])->name('collection.show');
Route::get('/item/{id}', [ItemController::class, 'show'])->name('item.show');

// สำหรับ Email/Password ปกติ
Route::post('/login', [AuthenticatedSessionController::class, 'store']);
Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])->name('logout');

// สำหรับ Google OAuth
Route::get('/auth/google/redirect', [GoogleAuthController::class, 'redirectToGoogle'])->name('google.redirect');
Route::get('/auth/google/callback', [GoogleAuthController::class, 'handleGoogleCallback'])->name('google.callback');

Route::get('dashboard', [DashboardController::class, 'index'])->middleware(['auth', 'verified', 'role:2'])->name('dashboard');

Route::post('/impersonate/leave', [ImpersonateController::class, 'leave'])
    ->middleware('auth')->name('impersonate.leave');

// Repository — add / edit one item. staff + admin (edit is further gated to the owner while pending).
Route::middleware(['auth', 'verified', 'role:2'])->prefix('admin')->name('admin.')->group(function () {
    Route::post('/repository/items', [RepositoryController::class, 'store'])->name('repository.items.store');
    Route::get('/repository/items/{item}/edit', [RepositoryController::class, 'edit'])->name('repository.items.edit');
    Route::put('/repository/items/{item}', [RepositoryController::class, 'update'])->name('repository.items.update');
});

Route::middleware(['auth', 'verified', 'role:3'])->prefix('admin')->name('admin.')->group(function () {
    Route::post('/members', [MemberController::class, 'store'])->name('members.store');
    Route::put('/members/{member}', [MemberController::class, 'update'])->name('members.update');
    Route::patch('/members/{member}/status', [MemberController::class, 'toggleStatus'])->name('members.status');
    Route::delete('/members/{member}', [MemberController::class, 'destroy'])->name('members.destroy');
    Route::post('/members/{member}/impersonate', [MemberController::class, 'impersonate'])->name('members.impersonate');

    // Repository management — admin only
    Route::delete('/repository/items/{item}', [RepositoryController::class, 'destroy'])->name('repository.items.destroy');
    Route::patch('/repository/items/{item}/approve', [RepositoryController::class, 'approve'])->name('repository.items.approve');
    Route::patch('/repository/items/{item}/return', [RepositoryController::class, 'returnForEdit'])->name('repository.items.return');

    // CSV bulk import (Flow A) — admin only
    Route::get('/repository/import/template', [RepositoryImportController::class, 'template'])->name('repository.import.template');
    Route::post('/repository/import/validate', [RepositoryImportController::class, 'validateUpload'])->name('repository.import.validate');
    Route::post('/repository/import/commit', [RepositoryImportController::class, 'commit'])->name('repository.import.commit');
});

// หน้าทดสอบ SPA
Route::get('/test-spa', function () {
    return Inertia::render('TestSPA');
});

require __DIR__.'/settings.php';
require __DIR__.'/auth.php';
