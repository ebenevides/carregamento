<?php

namespace App\Http\Controllers;

use App\Domain\Carregamento\Enums\StatusOrdem;
use App\Domain\Carregamento\Models\DivergenciaCarregamento;
use App\Domain\Carregamento\Models\OrdemCarregamento;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function index(Request $request): Response
    {
        $data  = $request->input('data', today()->toDateString());
        $ponto = $request->input('ponto_id');

        $base = OrdemCarregamento::whereDate('created_at', $data)
            ->when($ponto, fn ($q) => $q->where('ponto_carregamento_id', $ponto));

        $contadores = [
            'aguardando_carregamento'  => (clone $base)->where('status', StatusOrdem::AGUARDANDO_CARREGAMENTO)->count(),
            'em_carregamento'          => (clone $base)->where('status', StatusOrdem::EM_CARREGAMENTO)->count(),
            'carregamento_concluido'   => (clone $base)->where('status', StatusOrdem::CARREGAMENTO_CONCLUIDO)->count(),
            'aguardando_pesagem_final' => (clone $base)->where('status', StatusOrdem::AGUARDANDO_PESAGEM_FINAL)->count(),
            'pesagem_final_realizada'  => (clone $base)->where('status', StatusOrdem::PESAGEM_FINAL_REALIZADA)->count(),
            'validado'                 => (clone $base)->where('status', StatusOrdem::VALIDADO)->count(),
            'divergencias'             => (clone $base)->where('status', StatusOrdem::DIVERGENCIA)->count(),
            'finalizado'               => (clone $base)->where('status', StatusOrdem::FINALIZADO)->count(),
            'cancelado'                => (clone $base)->where('status', StatusOrdem::CANCELADO)->count(),
            'total'                    => (clone $base)->count(),
        ];

        $divergenciasAbertas = DivergenciaCarregamento::with('ordemCarregamento')
            ->where('status', 'ABERTA')
            ->when($ponto, fn ($q) => $q->whereHas('ordemCarregamento', fn ($q2) => $q2->where('ponto_carregamento_id', $ponto)))
            ->orderByDesc('created_at')
            ->limit(20)
            ->get()
            ->map(fn ($d) => [
                'id'          => $d->id,
                'tipo'        => $d->tipo->value,
                'tipo_label'  => $d->tipo->label(),
                'descricao'   => $d->descricao,
                'origem'      => $d->origem->value,
                'created_at'  => $d->created_at?->toISOString(),
                'ordem'       => [
                    'id'           => $d->ordemCarregamento?->id,
                    'placa'        => $d->ordemCarregamento?->placa_veiculo,
                    'produto'      => $d->ordemCarregamento?->produto_codigo,
                    'status'       => $d->ordemCarregamento?->status?->value,
                    'status_label' => $d->ordemCarregamento?->status?->label(),
                ],
            ]);

        return Inertia::render('Dashboard/Index', [
            'contadores'          => $contadores,
            'divergenciasAbertas' => $divergenciasAbertas,
            'filtros'             => ['data' => $data, 'ponto_id' => $ponto],
        ]);
    }
}
