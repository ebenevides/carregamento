<?php

namespace App\Http\Controllers\Web;

use App\Domain\Carregamento\Models\PontoCarregamento;
use App\Http\Controllers\Controller;
use Inertia\Inertia;
use Inertia\Response;

class PontoCarregamentoController extends Controller
{
    public function index(): Response
    {
        $pontos = PontoCarregamento::withCount('ordensCarregamento')
            ->orderBy('descricao')
            ->get()
            ->map(fn ($p) => [
                'id'                     => $p->id,
                'codigo'                 => $p->codigo,
                'descricao'              => $p->descricao,
                'status'                 => $p->status->value,
                'status_label'           => $p->status->label(),
                'observacao'             => $p->observacao,
                'ordens_carregamento_count' => $p->ordens_carregamento_count,
            ]);

        return Inertia::render('Pontos/Index', [
            'pontos' => $pontos,
        ]);
    }
}
