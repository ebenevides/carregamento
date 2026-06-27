<?php

namespace App\Http\Controllers\Web;

use App\Domain\Carregamento\Models\DivergenciaCarregamento;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DivergenciaController extends Controller
{
    public function index(Request $request): Response
    {
        $status = $request->input('status', 'ABERTA');

        $divergencias = DivergenciaCarregamento::with('ordemCarregamento')
            ->when($status !== 'TODAS', fn ($q) => $q->where('status', $status))
            ->orderByDesc('created_at')
            ->paginate(30)
            ->through(fn ($d) => [
                'id'          => $d->id,
                'tipo'        => $d->tipo->value,
                'tipo_label'  => $d->tipo->label(),
                'descricao'   => $d->descricao,
                'status'      => $d->status->value,
                'status_label' => $d->status->label(),
                'origem'      => $d->origem->value,
                'resolucao'   => $d->resolucao,
                'resolvido_em' => $d->resolvido_em?->toISOString(),
                'created_at'  => $d->created_at?->toISOString(),
                'ordem'       => $d->ordemCarregamento ? [
                    'id'           => $d->ordemCarregamento->id,
                    'placa'        => $d->ordemCarregamento->placa_veiculo,
                    'motorista'    => $d->ordemCarregamento->motorista_nome,
                    'produto'      => $d->ordemCarregamento->produto_descricao,
                    'status'       => $d->ordemCarregamento->status?->value,
                    'status_label' => $d->ordemCarregamento->status?->label(),
                ] : null,
            ]);

        return Inertia::render('Divergencias/Index', [
            'divergencias' => $divergencias,
            'filtros'      => ['status' => $status],
        ]);
    }
}
