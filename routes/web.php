<?php

use App\Http\Controllers\AuthController;
use App\Livewire\MarketplaceFeed;
use App\Livewire\BusinessRegistry;
use App\Livewire\InteractiveMap;
use App\Livewire\Admin\MachineDashboard;
use App\Livewire\Admin\UserManagement;
use App\Livewire\Admin\PostManagement;
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

// Admin Protected Routes (Role: admin)
Route::middleware(['auth', 'role:admin'])->prefix('admin')->as('admin.')->group(function () {
    Route::get('/maquina', MachineDashboard::class)->name('machine');
    Route::get('/usuarios', UserManagement::class)->name('users');
    Route::get('/publicaciones', PostManagement::class)->name('posts');
});
