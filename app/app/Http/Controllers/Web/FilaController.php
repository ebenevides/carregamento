<?php

namespace App\Http\Controllers\Web;

use App\Domain\Carregamento\Enums\StatusOrdem;
use App\Domain\Carregamento\Models\OrdemCarregamento;
use App\Domain\Carregamento\Models\PontoCarregamento;
use App\Http\Controllers\Controller;
use Inertia\Inertia;
use Inertia\Response;

class FilaController extends Controller
{
    public function index(): Response
    {
        $pontos = PontoCarregamento::with([
            'ordensCarregamento' => fn ($q) => $q
                ->whereIn('status', [
                    StatusOrdem::AGUARDANDO_CARREGAMENTO->value,
                    StatusOrdem::EM_CARREGAMENTO->value,
                ])
                ->orderBy('created_at'),
        ])->orderBy('descricao')->get()->map(fn ($p) => [
            'id'        => $p->id,
            'codigo'    => $p->codigo,
            'descricao' => $p->descricao,
            'status'    => $p->status->value,
            'ordens'    => $p->ordensCarregamento->map(fn ($o) => [
                'id'           => $o->id,
                'placa'        => $o->placa_veiculo,
                'motorista'    => $o->motorista_nome,
                'produto'      => $o->produto_descricao,
                'quantidade'   => $o->quantidade_prevista,
                'unidade'      => $o->unidade,
                'status'       => $o->status->value,
                'status_label' => $o->status->label(),
                'iniciado_em'  => $o->iniciado_em?->toISOString(),
            ]),
        ]);

        $totais = [
            'aguardando' => OrdemCarregamento::where('status', StatusOrdem::AGUARDANDO_CARREGAMENTO)->count(),
            'em_carga'   => OrdemCarregamento::where('status', StatusOrdem::EM_CARREGAMENTO)->count(),
        ];

        return Inertia::render('Fila/Index', [
            'pontos' => $pontos,
            'totais' => $totais,
        ]);
    }
}
