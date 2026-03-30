<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\CampaignController;
use App\Http\Controllers\MaterialController;
use App\Http\Controllers\MapController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\EventoController;
use App\Http\Controllers\AuthController;

Route::get('/', function () {
    return view('welcome');
})->name('login');

Route::redirect('/login', '/');
Route::redirect('/admin', '/');
Route::post('/admin/login', [AuthController::class, 'login'])->name('admin.login');
Route::get('/admin/logout', [AuthController::class, 'logout'])->name('admin.logout');

// Rutas del panel de administración
Route::prefix('admin')->middleware(['auth', 'admin'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    
    Route::get('/usuarios', [UserController::class, 'index'])->name('usuarios.index');
    Route::get('/usuarios/create', [UserController::class, 'create'])->name('usuarios.create');
    Route::post('/usuarios', [UserController::class, 'store'])->name('usuarios.store');

    Route::get('/campanas', [CampaignController::class, 'index'])->name('campanas.index');
    Route::get('/campanas/create', [CampaignController::class, 'create'])->name('campanas.create');
    Route::post('/campanas', [CampaignController::class, 'store'])->name('campanas.store');

    Route::get('/materiales', [MaterialController::class, 'index'])->name('materiales.index');
    Route::post('/materiales', [MaterialController::class, 'store'])->name('materiales.store');

    Route::get('/mapa', [MapController::class, 'index'])->name('mapa.index');
    Route::get('/mapa/create', [MapController::class, 'create'])->name('mapa.create');
    Route::post('/mapa', [MapController::class, 'store'])->name('mapa.store');

    Route::get('/eventos', [EventoController::class, 'index'])->name('eventos.index');
    Route::get('/eventos/create', [EventoController::class, 'create'])->name('eventos.create');
    Route::post('/eventos', [EventoController::class, 'store'])->name('eventos.store');
    Route::get('/eventos/{evento}/edit', [EventoController::class, 'edit'])->name('eventos.edit');
    Route::put('/eventos/{evento}', [EventoController::class, 'update'])->name('eventos.update');
    Route::delete('/eventos/{evento}', [EventoController::class, 'destroy'])->name('eventos.destroy');
});