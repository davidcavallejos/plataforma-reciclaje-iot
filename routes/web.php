<?php

use App\Http\Controllers\AuthController;
use App\Livewire\MarketplaceFeed;
use App\Livewire\BusinessRegistry;
use App\Livewire\InteractiveMap;
use App\Livewire\Admin\MachineDashboard;
use Illuminate\Support\Facades\Route;

// Public Marketplace & Map Routes (Guest Mode Support)
Route::get('/', MarketplaceFeed::class)->name('marketplace');
Route::get('/mapa', InteractiveMap::class)->name('map');
Route::get('/negocios', BusinessRegistry::class)->name('businesses');

// Auth Routes
Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login']);
Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
Route::post('/register', [AuthController::class, 'register']);
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

// Admin Exclusive Route for IoT Machine Telemetry & Control Panel
Route::middleware(['auth', App\Http\Middleware\EnsureUserIsAdmin::class])->group(function () {
    Route::get('/admin/maquina', MachineDashboard::class)->name('admin.machine');
});
