<?php

use App\Http\Controllers\Api\V1\DivergenciaController;
use App\Http\Controllers\Api\V1\FilaCarregamentoController;
use App\Http\Controllers\Api\V1\ChatController;
use App\Http\Controllers\Api\V1\IntegracaoController;
use App\Http\Controllers\Api\V1\MotoristaController;
use App\Http\Controllers\Api\V1\OrdemCarregamentoController;
use App\Http\Controllers\Api\V1\PilhaProdutoController;
use App\Http\Controllers\Api\V1\PontoCarregamentoController;
use App\Http\Controllers\Api\V1\ProdutoPilhaPontoController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {

    // Auth
    Route::post('auth/login', function (Request $request) {
        $request->validate([
            'email'    => 'required|email',
            'password' => 'required',
        ]);

        if (!\Illuminate\Support\Facades\Auth::attempt($request->only('email', 'password'))) {
            return response()->json(['message' => 'Credenciais inválidas.'], 401);
        }

        $user  = \App\Models\User::where('email', $request->email)->first();
        $token = $user->createToken('flutter-app')->plainTextToken;

        return response()->json([
            'token' => $token,
            'user'  => [
                'id'     => $user->id,
                'name'   => $user->name,
                'email'  => $user->email,
                'perfil' => $user->perfil->value,
                'ponto_carregamento_id' => $user->ponto_carregamento_id,
            ],
        ]);
    });

    // Rotas autenticadas
    Route::middleware('auth:sanctum')->group(function () {

        Route::get('me', fn (Request $r) => $r->user()->load('pontoCarregamento'));

        // Pontos de carregamento
        Route::apiResource('pontos-carregamento', PontoCarregamentoController::class)
            ->parameters(['pontos-carregamento' => 'pontoCarregamento']);
        Route::post('pontos-carregamento/{pontoCarregamento}/ativar', [PontoCarregamentoController::class, 'ativar']);
        Route::post('pontos-carregamento/{pontoCarregamento}/inativar', [PontoCarregamentoController::class, 'inativar']);

        // Pilhas de produto
        Route::apiResource('pilhas-produto', PilhaProdutoController::class)
            ->parameters(['pilhas-produto' => 'pilhaProduto']);
        Route::post('pilhas-produto/{pilhaProduto}/ativar', [PilhaProdutoController::class, 'ativar']);
        Route::post('pilhas-produto/{pilhaProduto}/inativar', [PilhaProdutoController::class, 'inativar']);

        // Produto x Pilha x Ponto
        Route::get('produto-pilha-ponto', [ProdutoPilhaPontoController::class, 'index']);
        Route::post('produto-pilha-ponto', [ProdutoPilhaPontoController::class, 'store']);
        Route::delete('produto-pilha-ponto/{produtoPilhaPonto}', [ProdutoPilhaPontoController::class, 'destroy']);

        // Ordens de carregamento
        Route::apiResource('ordens-carregamento', OrdemCarregamentoController::class)
            ->only(['index', 'show', 'store'])
            ->parameters(['ordens-carregamento' => 'ordemCarregamento']);
        Route::post('ordens-carregamento/{ordemCarregamento}/iniciar', [OrdemCarregamentoController::class, 'iniciar']);
        Route::post('ordens-carregamento/{ordemCarregamento}/concluir', [OrdemCarregamentoController::class, 'concluir']);
        Route::post('ordens-carregamento/{ordemCarregamento}/rejeitar', [OrdemCarregamentoController::class, 'rejeitar']);
        Route::post('ordens-carregamento/{ordemCarregamento}/divergencias', [OrdemCarregamentoController::class, 'registrarDivergencia']);
        Route::post('ordens-carregamento/{ordemCarregamento}/pesagem-final', [OrdemCarregamentoController::class, 'pesagemFinal']);
        Route::post('ordens-carregamento/{ordemCarregamento}/liberar-faturamento', [OrdemCarregamentoController::class, 'liberarFaturamento']);

        // Divergências
        Route::get('divergencias', [DivergenciaController::class, 'index']);
        Route::post('divergencias/{divergencia}/resolver', [DivergenciaController::class, 'resolver']);

        // Integrações
        Route::get('integracoes/protheus/pedidos/{numero}', [IntegracaoController::class, 'pedidoProtheus']);
        Route::get('integracoes/guardian/tickets/{ticket}', [IntegracaoController::class, 'ticketGuardian']);
        Route::get('integracoes/guardian/fila/{ticket}', [IntegracaoController::class, 'filaGuardian']);

        // Fila de carregamento
        Route::get('fila-carregamento', [FilaCarregamentoController::class, 'index']);
        Route::get('operador/minha-fila', [FilaCarregamentoController::class, 'minhaFila']);
        Route::post('fila-carregamento/{ordemCarregamento}/liberar', [FilaCarregamentoController::class, 'liberarParaFila']);
        Route::get('fila-carregamento/{ordemCarregamento}/validar', [FilaCarregamentoController::class, 'validar']);

        // Chat da ordem
        Route::get('ordens-carregamento/{ordemCarregamento}/mensagens', [ChatController::class, 'index']);
        Route::post('ordens-carregamento/{ordemCarregamento}/mensagens', [ChatController::class, 'store']);

        // Motorista
        Route::get('motorista/minha-ordem', [MotoristaController::class, 'minhaOrdem']);
    });
});
