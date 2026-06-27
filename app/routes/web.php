<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\Web\DivergenciaController;
use App\Http\Controllers\Web\IntegracaoGuardianController;
use App\Http\Controllers\Web\EquipamentoController;
use App\Http\Controllers\Web\FilaController;
use App\Http\Controllers\Web\OrdemCarregamentoController;
use App\Http\Controllers\Web\PilhaProdutoController;
use App\Http\Controllers\Web\PontoCarregamentoController;
use App\Http\Controllers\Web\ProdutoPilhaPontoController;
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
    Route::post('/ordens', [OrdemCarregamentoController::class, 'store'])->name('ordens.store');
    Route::get('/ordens/{ordem}', [OrdemCarregamentoController::class, 'show'])->name('ordens.show');
    Route::post('/ordens/{ordem}/cancelar', [OrdemCarregamentoController::class, 'cancelar'])->name('ordens.cancelar');
    Route::post('/ordens/{ordem}/iniciar', [OrdemCarregamentoController::class, 'iniciar'])->name('ordens.iniciar');
    Route::post('/ordens/{ordem}/concluir', [OrdemCarregamentoController::class, 'concluir'])->name('ordens.concluir');
    Route::post('/ordens/{ordem}/liberar-faturamento', [OrdemCarregamentoController::class, 'liberarFaturamento'])->name('ordens.liberar-faturamento');
    Route::post('/ordens/{ordem}/divergencias', [OrdemCarregamentoController::class, 'registrarDivergencia'])->name('ordens.divergencias.store');
    Route::get('/fila', [FilaController::class, 'index'])->name('fila');
    Route::post('/fila/{ordem}/entrar', [FilaController::class, 'entrar'])->name('fila.entrar');
    Route::post('/fila/{ordem}/iniciar', [FilaController::class, 'iniciar'])->name('fila.iniciar');
    Route::post('/fila/{ordem}/concluir', [FilaController::class, 'concluir'])->name('fila.concluir');
    Route::post('/fila/{ordem}/cancelar', [FilaController::class, 'cancelar'])->name('fila.cancelar');
    Route::get('/divergencias', [DivergenciaController::class, 'index'])->name('divergencias');
    Route::post('/divergencias/{divergencia}/resolver', [\App\Http\Controllers\Api\V1\DivergenciaController::class, 'resolver'])->name('divergencias.resolver');
    Route::post('/divergencias/{divergencia}/cancelar', [DivergenciaController::class, 'cancelar'])->name('divergencias.cancelar');
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

    Route::get('/equipamentos', [EquipamentoController::class, 'index'])->name('equipamentos');
    Route::post('/equipamentos', [EquipamentoController::class, 'store'])->name('equipamentos.store');
    Route::put('/equipamentos/{equipamento}', [EquipamentoController::class, 'update'])->name('equipamentos.update');
    Route::delete('/equipamentos/{equipamento}', [EquipamentoController::class, 'destroy'])->name('equipamentos.destroy');

    Route::get('/mapeamento', [ProdutoPilhaPontoController::class, 'index'])->name('mapeamento');
    Route::post('/mapeamento', [ProdutoPilhaPontoController::class, 'store'])->name('mapeamento.store');
    Route::put('/mapeamento/{mapeamento}', [ProdutoPilhaPontoController::class, 'update'])->name('mapeamento.update');
    Route::delete('/mapeamento/{mapeamento}', [ProdutoPilhaPontoController::class, 'destroy'])->name('mapeamento.destroy');

    Route::get('/integracoes/guardian', [IntegracaoGuardianController::class, 'index'])->name('integracoes.guardian');
    Route::post('/integracoes/guardian/consultar-ticket', [IntegracaoGuardianController::class, 'consultarTicket'])->name('integracoes.guardian.consultar-ticket');
    Route::post('/integracoes/guardian/sync-todas', [IntegracaoGuardianController::class, 'sincronizarTodas'])->name('integracoes.guardian.sync-todas');
    Route::post('/integracoes/guardian/{ordem}/sync-tara', [IntegracaoGuardianController::class, 'sincronizarTaraOrdem'])->name('integracoes.guardian.sync-tara');
    Route::post('/integracoes/guardian/{ordem}/sync-pesagem', [IntegracaoGuardianController::class, 'sincronizarPesagemOrdem'])->name('integracoes.guardian.sync-pesagem');

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
