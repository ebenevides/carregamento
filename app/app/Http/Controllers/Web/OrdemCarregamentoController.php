<?php

namespace App\Http\Controllers\Web;

use App\Domain\Carregamento\Actions\AlterarStatusOrdemAction;
use App\Domain\Carregamento\Actions\ConcluirCarregamentoAction;
use App\Domain\Carregamento\Actions\CriarOrdemAction;
use App\Domain\Carregamento\Actions\IniciarCarregamentoAction;
use App\Domain\Carregamento\Actions\LiberarParaFaturamentoAction;
use App\Domain\Carregamento\Actions\RegistrarDivergenciaAction;
use App\Domain\Carregamento\DTOs\AlterarStatusDTO;
use App\Domain\Carregamento\DTOs\CriarOrdemDTO;
use App\Domain\Carregamento\Enums\OrigemEvento;
use App\Domain\Carregamento\Enums\StatusOrdem;
use App\Domain\Carregamento\Enums\TipoDivergencia;
use App\Domain\Carregamento\Enums\TipoEvento;
use App\Domain\Carregamento\Models\OrdemCarregamento;
use App\Domain\Carregamento\Models\PontoCarregamento;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class OrdemCarregamentoController extends Controller
{
    public function index(Request $request): Response
    {
        $data   = $request->input('data', today()->toDateString());
        $status = $request->input('status');
        $ponto  = $request->input('ponto_id');

        $ordens = OrdemCarregamento::with('pontoCarregamento')
            ->whereDate('created_at', $data)
            ->when($status, fn ($q) => $q->where('status', $status))
            ->when($ponto, fn ($q) => $q->where('ponto_carregamento_id', $ponto))
            ->orderByDesc('created_at')
            ->paginate(40)
            ->through(fn ($o) => [
                'id'                  => $o->id,
                'pedido_numero'       => $o->pedido_numero,
                'cliente_nome'        => $o->cliente_nome,
                'produto_descricao'   => $o->produto_descricao,
                'quantidade_prevista' => $o->quantidade_prevista,
                'unidade'             => $o->unidade,
                'placa_veiculo'       => $o->placa_veiculo,
                'motorista_nome'      => $o->motorista_nome,
                'status'              => $o->status->value,
                'status_label'        => $o->status->label(),
                'ponto'               => $o->pontoCarregamento?->descricao,
                'criado_em'           => $o->created_at?->toISOString(),
                'iniciado_em'         => $o->iniciado_em?->toISOString(),
                'concluido_em'        => $o->concluido_em?->toISOString(),
            ]);

        $pontos = PontoCarregamento::orderBy('descricao')->get(['id', 'descricao']);

        return Inertia::render('Ordens/Index', [
            'ordens'  => $ordens,
            'pontos'  => $pontos,
            'filtros' => ['data' => $data, 'status' => $status, 'ponto_id' => $ponto],
        ]);
    }

    public function show(OrdemCarregamento $ordem): Response
    {
        $ordem->load(['pilhaProduto', 'pontoCarregamento', 'operador', 'eventos', 'divergencias']);

        return Inertia::render('Ordens/Show', [
            'ordem' => [
                'id'                    => $ordem->id,
                'empresa'               => $ordem->empresa,
                'filial'                => $ordem->filial,
                'pedido_numero'         => $ordem->pedido_numero,
                'pedido_item'           => $ordem->pedido_item,
                'contrato_codigo'       => $ordem->contrato_codigo,
                'ticket_guardian'       => $ordem->ticket_guardian,
                'cliente_codigo'        => $ordem->cliente_codigo,
                'cliente_nome'          => $ordem->cliente_nome,
                'produto_codigo'        => $ordem->produto_codigo,
                'produto_descricao'     => $ordem->produto_descricao,
                'quantidade_prevista'   => $ordem->quantidade_prevista,
                'unidade'               => $ordem->unidade,
                'placa_veiculo'         => $ordem->placa_veiculo,
                'placa_carreta'         => $ordem->placa_carreta,
                'motorista_nome'        => $ordem->motorista_nome,
                'motorista_documento'   => $ordem->motorista_documento,
                'transportadora_nome'   => $ordem->transportadora_nome,
                'tara'                  => $ordem->tara,
                'peso_bruto'            => $ordem->peso_bruto,
                'peso_liquido'          => $ordem->peso_liquido,
                'tolerancia_percentual' => $ordem->tolerancia_percentual,
                'status'                => $ordem->status->value,
                'status_label'          => $ordem->status->label(),
                'ponto'                 => $ordem->pontoCarregamento?->descricao,
                'ponto_id'              => $ordem->ponto_carregamento_id,
                'pilha'                 => $ordem->pilhaProduto?->descricao,
                'operador_nome'         => $ordem->operador?->name,
                'criado_em'             => $ordem->created_at?->toISOString(),
                'iniciado_em'           => $ordem->iniciado_em?->toISOString(),
                'concluido_em'          => $ordem->concluido_em?->toISOString(),
                'pesagem_final_em'      => $ordem->pesagem_final_em?->toISOString(),
                'dentro_tolerancia'     => $ordem->dentroDaTolerancia(),
                'eventos'               => $ordem->eventos->map(fn ($e) => [
                    'tipo'            => $e->tipo->value,
                    'tipo_label'      => $e->tipo->label(),
                    'status_anterior' => $e->status_anterior?->label(),
                    'status_novo'     => $e->status_novo?->label(),
                    'origem'          => $e->origem->value,
                    'usuario_nome'    => $e->usuario_nome ?? $e->usuario?->name,
                    'observacao'      => $e->observacao,
                    'ocorrido_em'     => $e->ocorrido_em?->toISOString(),
                ]),
                'divergencias' => $ordem->divergencias->map(fn ($d) => [
                    'tipo'        => $d->tipo->value,
                    'tipo_label'  => $d->tipo->label(),
                    'status'      => $d->status,
                    'descricao'   => $d->descricao,
                    'criado_em'   => $d->created_at?->toISOString(),
                    'resolvido_em' => $d->resolvido_em?->toISOString(),
                ]),
            ],
        ]);
    }

    public function store(Request $request, CriarOrdemAction $action): RedirectResponse
    {
        $data = $request->validate([
            'empresa'               => ['nullable', 'string', 'max:10'],
            'filial'                => ['nullable', 'string', 'max:10'],
            'pedido_numero'         => ['nullable', 'string', 'max:20'],
            'pedido_item'           => ['nullable', 'string', 'max:10'],
            'contrato_codigo'       => ['nullable', 'string', 'max:20'],
            'ticket_guardian'       => ['nullable', 'string', 'max:20', 'unique:ordens_carregamento,ticket_guardian'],
            'cliente_codigo'        => ['nullable', 'string', 'max:20'],
            'cliente_nome'          => ['nullable', 'string', 'max:150'],
            'produto_codigo'        => ['required', 'string', 'max:30'],
            'produto_descricao'     => ['nullable', 'string', 'max:100'],
            'quantidade_prevista'   => ['required', 'numeric', 'min:0.001'],
            'unidade'               => ['nullable', 'string', 'max:10'],
            'placa_veiculo'         => ['required', 'string', 'max:10'],
            'placa_carreta'         => ['nullable', 'string', 'max:10'],
            'motorista_nome'        => ['nullable', 'string', 'max:100'],
            'motorista_documento'   => ['nullable', 'string', 'max:20'],
            'transportadora_nome'   => ['nullable', 'string', 'max:100'],
            'tara'                  => ['nullable', 'numeric', 'min:0'],
            'tolerancia_percentual' => ['nullable', 'numeric', 'min:0', 'max:100'],
        ]);

        $ordem = $action->execute(CriarOrdemDTO::fromArray($data), OrigemEvento::PAINEL_WEB);

        return redirect()->route('ordens.show', $ordem->id)->with('success', 'Ordem criada.');
    }

    public function cancelar(Request $request, OrdemCarregamento $ordem, AlterarStatusOrdemAction $action): RedirectResponse
    {
        $request->validate([
            'observacao' => ['nullable', 'string', 'max:500'],
        ]);

        $action->execute($ordem, new AlterarStatusDTO(
            novoStatus: StatusOrdem::CANCELADO,
            tipoEvento: TipoEvento::ORDEM_CANCELADA,
            origem: OrigemEvento::PAINEL_WEB,
            usuarioId: auth()->id(),
            observacao: $request->input('observacao', 'Cancelado via painel web.'),
        ));

        return redirect()->route('ordens')->with('success', 'Ordem cancelada.');
    }

    public function iniciar(Request $request, OrdemCarregamento $ordem, IniciarCarregamentoAction $action): RedirectResponse
    {
        $request->validate([
            'operador_id' => ['nullable', 'integer', 'exists:users,id'],
            'observacao'  => ['nullable', 'string', 'max:500'],
        ]);

        abort_if($ordem->ponto_carregamento_id === null, 422, 'Ordem sem ponto de carregamento atribuído.');

        $action->execute(
            ordem: $ordem,
            operadorId: $request->integer('operador_id') ?: auth()->id(),
            pontoCarregamentoId: $ordem->ponto_carregamento_id,
            equipamentoCodigo: null,
            observacao: $request->input('observacao'),
            origem: OrigemEvento::PAINEL_WEB,
        );

        return back()->with('success', 'Carregamento iniciado.');
    }

    public function concluir(Request $request, OrdemCarregamento $ordem, ConcluirCarregamentoAction $action): RedirectResponse
    {
        $request->validate([
            'observacao' => ['nullable', 'string', 'max:500'],
        ]);

        $action->execute(
            ordem: $ordem,
            operadorId: auth()->id(),
            observacao: $request->input('observacao'),
            origem: OrigemEvento::PAINEL_WEB,
        );

        return back()->with('success', 'Carregamento concluído.');
    }

    public function liberarFaturamento(Request $request, OrdemCarregamento $ordem, LiberarParaFaturamentoAction $action): RedirectResponse
    {
        $request->validate([
            'observacao' => ['nullable', 'string', 'max:500'],
        ]);

        $action->execute(
            ordem: $ordem,
            usuarioId: auth()->id(),
            observacao: $request->input('observacao'),
            origem: OrigemEvento::PAINEL_WEB,
        );

        return back()->with('success', 'Ordem liberada para faturamento.');
    }

    public function registrarDivergencia(Request $request, OrdemCarregamento $ordem, RegistrarDivergenciaAction $action): RedirectResponse
    {
        $data = $request->validate([
            'tipo'      => ['required', 'string'],
            'descricao' => ['required', 'string', 'max:1000'],
        ]);

        $tipo = TipoDivergencia::from($data['tipo']);

        $action->execute(
            ordem: $ordem,
            tipo: $tipo,
            origem: OrigemEvento::PAINEL_WEB,
            descricao: $data['descricao'],
            usuarioId: auth()->id(),
            usuarioNome: auth()->user()->name,
        );

        return back()->with('success', 'Divergência registrada.');
    }
}
