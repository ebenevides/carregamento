<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\Web\DivergenciaController;
use App\Http\Controllers\Web\FilaController;
use App\Http\Controllers\Web\OrdemCarregamentoController;
use App\Http\Controllers\Web\PilhaProdutoController;
use App\Http\Controllers\Web\PontoCarregamentoController;
use App\Http\Controllers\Web\UsuarioController;
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
    Route::post('/pontos', [PontoCarregamentoController::class, 'store'])->name('pontos.store');
    Route::put('/pontos/{pontoCarregamento}', [PontoCarregamentoController::class, 'update'])->name('pontos.update');
    Route::delete('/pontos/{pontoCarregamento}', [PontoCarregamentoController::class, 'destroy'])->name('pontos.destroy');
    Route::post('/pontos/{pontoCarregamento}/ativar', [PontoCarregamentoController::class, 'ativar'])->name('pontos.ativar');
    Route::post('/pontos/{pontoCarregamento}/inativar', [PontoCarregamentoController::class, 'inativar'])->name('pontos.inativar');

    Route::get('/pilhas', [PilhaProdutoController::class, 'index'])->name('pilhas');
    Route::post('/pilhas', [PilhaProdutoController::class, 'store'])->name('pilhas.store');
    Route::put('/pilhas/{pilhaProduto}', [PilhaProdutoController::class, 'update'])->name('pilhas.update');
    Route::delete('/pilhas/{pilhaProduto}', [PilhaProdutoController::class, 'destroy'])->name('pilhas.destroy');

    Route::get('/usuarios', [UsuarioController::class, 'index'])->name('usuarios');
    Route::post('/usuarios', [UsuarioController::class, 'store'])->name('usuarios.store');
    Route::put('/usuarios/{usuario}', [UsuarioController::class, 'update'])->name('usuarios.update');
    Route::delete('/usuarios/{usuario}', [UsuarioController::class, 'destroy'])->name('usuarios.destroy');
    Route::post('/usuarios/{usuario}/toggle-ativo', [UsuarioController::class, 'toggleAtivo'])->name('usuarios.toggle-ativo');
});

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
