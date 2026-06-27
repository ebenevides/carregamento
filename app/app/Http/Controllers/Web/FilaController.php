<?php

namespace App\Http\Controllers\Web;

use App\Domain\Carregamento\Actions\AlterarStatusOrdemAction;
use App\Domain\Carregamento\Actions\ConcluirCarregamentoAction;
use App\Domain\Carregamento\Actions\EntrarNaFilaAction;
use App\Domain\Carregamento\Actions\IniciarCarregamentoAction;
use App\Domain\Carregamento\DTOs\AlterarStatusDTO;
use App\Domain\Carregamento\Enums\OrigemEvento;
use App\Domain\Carregamento\Enums\StatusOrdem;
use App\Domain\Carregamento\Enums\TipoEvento;
use App\Domain\Carregamento\Models\OrdemCarregamento;
use App\Domain\Carregamento\Models\PontoCarregamento;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class FilaController extends Controller
{
    public function index(): Response
    {
        $pontos = PontoCarregamento::with([
            'ordensCarregamento' => fn ($q) => $q
                ->with('divergencias')
                ->whereIn('status', [
                    StatusOrdem::AGUARDANDO_CARREGAMENTO->value,
                    StatusOrdem::EM_CARREGAMENTO->value,
                ])
                ->orderByRaw("CASE status WHEN 'EM_CARREGAMENTO' THEN 0 ELSE 1 END")
                ->orderBy('created_at'),
        ])->orderBy('descricao')->get()->map(fn ($p) => [
            'id'        => $p->id,
            'codigo'    => $p->codigo,
            'descricao' => $p->descricao,
            'status'    => $p->status->value,
            'ordens'    => $p->ordensCarregamento->map(fn ($o) => [
                'id'             => $o->id,
                'placa'          => $o->placa_veiculo,
                'motorista'      => $o->motorista_nome,
                'produto'        => $o->produto_descricao,
                'quantidade'     => $o->quantidade_prevista,
                'unidade'        => $o->unidade,
                'status'         => $o->status->value,
                'status_label'   => $o->status->label(),
                'iniciado_em'    => $o->iniciado_em?->toISOString(),
                'tem_divergencia' => $o->divergencias->where('status', 'ABERTA')->isNotEmpty(),
            ]),
        ]);

        $pendentes = OrdemCarregamento::where('status', StatusOrdem::TARA_REALIZADA)
            ->whereNotNull('ponto_carregamento_id')
            ->whereNotNull('ticket_guardian')
            ->whereNotNull('tara')
            ->with('pontoCarregamento')
            ->orderBy('created_at')
            ->get()
            ->map(fn ($o) => [
                'id'       => $o->id,
                'placa'    => $o->placa_veiculo,
                'motorista' => $o->motorista_nome,
                'produto'  => $o->produto_descricao,
                'ponto'    => $o->pontoCarregamento?->descricao,
            ]);

        $totais = [
            'aguardando' => OrdemCarregamento::where('status', StatusOrdem::AGUARDANDO_CARREGAMENTO)->count(),
            'em_carga'   => OrdemCarregamento::where('status', StatusOrdem::EM_CARREGAMENTO)->count(),
            'pendentes'  => $pendentes->count(),
        ];

        return Inertia::render('Fila/Index', [
            'pontos'    => $pontos,
            'pendentes' => $pendentes,
            'totais'    => $totais,
        ]);
    }

    public function entrar(OrdemCarregamento $ordem, EntrarNaFilaAction $action): RedirectResponse
    {
        $action->execute($ordem, OrigemEvento::PAINEL_WEB);

        return back()->with('success', "Ordem {$ordem->placa_veiculo} adicionada à fila.");
    }

    public function iniciar(Request $request, OrdemCarregamento $ordem, IniciarCarregamentoAction $action): RedirectResponse
    {
        abort_if($ordem->ponto_carregamento_id === null, 422, 'Ordem sem ponto de carregamento.');

        $action->execute(
            ordem: $ordem,
            operadorId: auth()->id(),
            pontoCarregamentoId: $ordem->ponto_carregamento_id,
            equipamentoCodigo: null,
            observacao: $request->input('observacao'),
            origem: OrigemEvento::PAINEL_WEB,
        );

        return back()->with('success', "Carregamento iniciado — {$ordem->placa_veiculo}.");
    }

    public function concluir(Request $request, OrdemCarregamento $ordem, ConcluirCarregamentoAction $action): RedirectResponse
    {
        $action->execute(
            ordem: $ordem,
            operadorId: auth()->id(),
            observacao: $request->input('observacao'),
            origem: OrigemEvento::PAINEL_WEB,
        );

        return back()->with('success', "Carregamento concluído — {$ordem->placa_veiculo}.");
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
            observacao: $request->input('observacao', 'Cancelado via fila.'),
        ));

        return back()->with('success', "Ordem {$ordem->placa_veiculo} cancelada.");
    }
}
