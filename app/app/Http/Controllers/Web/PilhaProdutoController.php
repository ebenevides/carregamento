<?php

namespace App\Http\Controllers\Web;

use App\Domain\Carregamento\Models\PilhaProduto;
use App\Http\Controllers\Controller;
use Inertia\Inertia;
use Inertia\Response;

class PilhaProdutoController extends Controller
{
    public function index(): Response
    {
        $pilhas = PilhaProduto::with('pontosCarregamento')
            ->orderBy('descricao')
            ->get()
            ->map(fn ($p) => [
                'id'                 => $p->id,
                'codigo'             => $p->codigo,
                'descricao'          => $p->descricao,
                'produto_codigo'     => $p->produto_codigo,
                'produto_descricao'  => $p->produto_descricao,
                'ativa'              => $p->ativa,
                'observacao'         => $p->observacao,
                'pontos'             => $p->pontosCarregamento->map(fn ($pt) => [
                    'id'        => $pt->id,
                    'descricao' => $pt->descricao,
                ]),
            ]);

        return Inertia::render('Pilhas/Index', [
            'pilhas' => $pilhas,
        ]);
    }
}
