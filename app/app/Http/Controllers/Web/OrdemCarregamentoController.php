<?php

namespace App\Http\Controllers\Web;

use App\Domain\Carregamento\Models\OrdemCarregamento;
use App\Domain\Carregamento\Models\PontoCarregamento;
use App\Http\Controllers\Controller;
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
                'id'                 => $o->id,
                'pedido_numero'      => $o->pedido_numero,
                'cliente_nome'       => $o->cliente_nome,
                'produto_descricao'  => $o->produto_descricao,
                'quantidade_prevista' => $o->quantidade_prevista,
                'unidade'            => $o->unidade,
                'placa_veiculo'      => $o->placa_veiculo,
                'motorista_nome'     => $o->motorista_nome,
                'status'             => $o->status->value,
                'status_label'       => $o->status->label(),
                'ponto'              => $o->pontoCarregamento?->descricao,
                'criado_em'          => $o->created_at?->toISOString(),
                'iniciado_em'        => $o->iniciado_em?->toISOString(),
                'concluido_em'       => $o->concluido_em?->toISOString(),
            ]);

        $pontos = PontoCarregamento::orderBy('descricao')->get(['id', 'descricao']);

        return Inertia::render('Ordens/Index', [
            'ordens'  => $ordens,
            'pontos'  => $pontos,
            'filtros' => ['data' => $data, 'status' => $status, 'ponto_id' => $ponto],
        ]);
    }
}
