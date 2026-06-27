<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\Web\DivergenciaController;
use App\Http\Controllers\Web\FilaController;
use App\Http\Controllers\Web\OrdemCarregamentoController;
use App\Http\Controllers\Web\PilhaProdutoController;
use App\Http\Controllers\Web\PontoCarregamentoController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canLogin'       => Route::has('login'),
        'canRegister'    => Route::has('register'),
        'laravelVersion' => Application::VERSION,
        'phpVersion'     => PHP_VERSION,
    ]);
});

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/ordens', [OrdemCarregamentoController::class, 'index'])->name('ordens');
    Route::get('/fila', [FilaController::class, 'index'])->name('fila');
    Route::get('/divergencias', [DivergenciaController::class, 'index'])->name('divergencias');
    Route::post('/divergencias/{divergencia}/resolver', [\App\Http\Controllers\Api\V1\DivergenciaController::class, 'resolver'])->name('divergencias.resolver');
    Route::get('/pontos', [PontoCarregamentoController::class, 'index'])->name('pontos');
    Route::get('/pilhas', [PilhaProdutoController::class, 'index'])->name('pilhas');
});

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
