<?php

namespace App\Http\Controllers\Api\V1;

use App\Domain\Carregamento\Actions\ConcluirCarregamentoAction;
use App\Domain\Carregamento\Actions\CriarOrdemAction;
use App\Domain\Carregamento\Actions\IniciarCarregamentoAction;
use App\Domain\Carregamento\Actions\LiberarParaFaturamentoAction;
use App\Domain\Carregamento\Actions\RegistrarDivergenciaAction;
use App\Domain\Carregamento\Actions\RegistrarPesagemFinalAction;
use App\Domain\Carregamento\DTOs\CriarOrdemDTO;
use App\Domain\Carregamento\Enums\OrigemEvento;
use App\Domain\Carregamento\Enums\TipoDivergencia;
use App\Domain\Carregamento\Models\OrdemCarregamento;
use App\Http\Controllers\Controller;
use App\Http\Requests\OrdemCarregamento\IniciarCarregamentoRequest;
use App\Http\Requests\OrdemCarregamento\PesagemFinalRequest;
use App\Http\Requests\OrdemCarregamento\RegistrarDivergenciaRequest;
use App\Http\Requests\OrdemCarregamento\RejeitarOrdemRequest;
use App\Http\Requests\OrdemCarregamento\StoreOrdemCarregamentoRequest;
use App\Http\Resources\OrdemCarregamentoResource;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class OrdemCarregamentoController extends Controller
{
    public function index(): AnonymousResourceCollection
    {
        $ordens = OrdemCarregamento::with(['pilhaProduto', 'pontoCarregamento', 'operador'])
            ->when(request('status'), fn ($q, $s) => $q->where('status', $s))
            ->when(request('ticket'), fn ($q, $t) => $q->where('ticket_guardian', $t))
            ->when(request('pedido'), fn ($q, $p) => $q->where('pedido_numero', $p))
            ->when(request('placa'), fn ($q, $p) => $q->where('placa_veiculo', $p))
            ->when(request('produto_codigo'), fn ($q, $c) => $q->where('produto_codigo', $c))
            ->when(request('ponto_id'), fn ($q, $id) => $q->where('ponto_carregamento_id', $id))
            ->when(request('data_inicio'), fn ($q, $d) => $q->whereDate('created_at', '>=', $d))
            ->when(request('data_fim'), fn ($q, $d) => $q->whereDate('created_at', '<=', $d))
            ->orderByDesc('created_at')
            ->paginate(30);

        return OrdemCarregamentoResource::collection($ordens);
    }

    public function show(OrdemCarregamento $ordemCarregamento): OrdemCarregamentoResource
    {
        $ordemCarregamento->load(['pilhaProduto', 'pontoCarregamento', 'operador', 'eventos', 'divergencias']);

        return new OrdemCarregamentoResource($ordemCarregamento);
    }

    public function store(StoreOrdemCarregamentoRequest $request, CriarOrdemAction $action): OrdemCarregamentoResource
    {
        $ordem = $action->execute(CriarOrdemDTO::fromArray($request->validated()));

        return new OrdemCarregamentoResource($ordem);
    }

    public function iniciar(IniciarCarregamentoRequest $request, OrdemCarregamento $ordemCarregamento, IniciarCarregamentoAction $action): OrdemCarregamentoResource
    {
        $ordem = $action->execute(
            ordem: $ordemCarregamento,
            operadorId: $request->integer('operador_id'),
            pontoCarregamentoId: $request->integer('ponto_carregamento_id'),
            equipamentoCodigo: $request->string('equipamento_codigo')->toString() ?: null,
            observacao: $request->string('observacao')->toString() ?: null,
            origem: OrigemEvento::APP_OPERADOR,
        );

        return new OrdemCarregamentoResource($ordem);
    }

    public function concluir(OrdemCarregamento $ordemCarregamento, ConcluirCarregamentoAction $action): OrdemCarregamentoResource|\Illuminate\Http\JsonResponse
    {
        $user = request()->user();

        if (!$user->perfil->podeIniciarCarregamento()) {
            return response()->json(['message' => 'Ação não permitida para este perfil.'], 403);
        }

        if ((int) $user->ponto_carregamento_id !== (int) $ordemCarregamento->ponto_carregamento_id) {
            return response()->json(['message' => 'Ordem não pertence ao seu ponto de carregamento.'], 403);
        }

        $ordem = $action->execute(
            ordem: $ordemCarregamento,
            operadorId: request()->integer('operador_id') ?: null,
            observacao: request()->string('observacao')->toString() ?: null,
            origem: OrigemEvento::APP_OPERADOR,
        );

        return new OrdemCarregamentoResource($ordem);
    }

    /**
     * Rejeitar caminhão — gera DIVERGENCIA (nunca CANCELADO).
     * Apenas usuários do mesmo ponto com permissão de iniciar carregamento.
     */
    public function rejeitar(
        RejeitarOrdemRequest $request,
        OrdemCarregamento $ordemCarregamento,
        RegistrarDivergenciaAction $action,
    ): \Illuminate\Http\JsonResponse {
        $user = $request->user();

        // Autorização: perfil e mesmo ponto
        if (!$user->perfil->podeIniciarCarregamento()) {
            return response()->json(['message' => 'Ação não permitida para este perfil.'], 403);
        }

        if ($user->ponto_carregamento_id !== $ordemCarregamento->ponto_carregamento_id) {
            return response()->json(['message' => 'Ordem não pertence ao seu ponto de carregamento.'], 403);
        }

        // Ordem precisa poder transicionar para DIVERGENCIA
        if (!$ordemCarregamento->status->podeTransicionarPara(\App\Domain\Carregamento\Enums\StatusOrdem::DIVERGENCIA)) {
            return response()->json([
                'message' => 'Ordem não pode ser rejeitada no status atual.',
            ], 422);
        }

        $divergencia = $action->execute(
            ordem: $ordemCarregamento,
            tipo: TipoDivergencia::REJEITADO_PELO_OPERADOR,
            origem: OrigemEvento::APP_OPERADOR,
            descricao: $request->input('descricao'),
            usuarioId: $user->id,
            usuarioNome: $user->name,
        );

        return response()->json($divergencia, 201);
    }

    public function registrarDivergencia(RegistrarDivergenciaRequest $request, OrdemCarregamento $ordemCarregamento, RegistrarDivergenciaAction $action)
    {
        $divergencia = $action->execute(
            ordem: $ordemCarregamento,
            tipo: TipoDivergencia::from($request->string('tipo')->toString()),
            origem: OrigemEvento::APP_OPERADOR,
            descricao: $request->string('descricao')->toString(),
            usuarioId: $request->integer('usuario_id') ?: null,
        );

        return response()->json($divergencia, 201);
    }

    public function pesagemFinal(PesagemFinalRequest $request, OrdemCarregamento $ordemCarregamento, RegistrarPesagemFinalAction $action): OrdemCarregamentoResource
    {
        $ordem = $action->execute(
            ordem: $ordemCarregamento,
            pesoBruto: $request->float('peso_bruto'),
            origem: OrigemEvento::GUARDIAN,
        );

        return new OrdemCarregamentoResource($ordem);
    }

    public function liberarFaturamento(OrdemCarregamento $ordemCarregamento, LiberarParaFaturamentoAction $action): OrdemCarregamentoResource
    {
        $ordem = $action->execute(
            ordem: $ordemCarregamento,
            usuarioId: request()->user()?->id,
            observacao: request()->string('observacao')->toString() ?: null,
            origem: OrigemEvento::PAINEL_WEB,
        );

        return new OrdemCarregamentoResource($ordem);
    }
}
