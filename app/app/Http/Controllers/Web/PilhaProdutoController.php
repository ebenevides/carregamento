<?php

namespace App\Http\Controllers\Web;

use App\Domain\Carregamento\Models\PilhaProduto;
use App\Domain\Carregamento\Models\PontoCarregamento;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
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
                'id'                => $p->id,
                'codigo'            => $p->codigo,
                'descricao'         => $p->descricao,
                'produto_codigo'    => $p->produto_codigo,
                'produto_descricao' => $p->produto_descricao,
                'ativa'             => $p->ativa,
                'observacao'        => $p->observacao,
                'pontos'            => $p->pontosCarregamento->map(fn ($pt) => [
                    'id'        => $pt->id,
                    'descricao' => $pt->descricao,
                ]),
            ]);

        $pontos = PontoCarregamento::orderBy('descricao')->get(['id', 'descricao']);

        return Inertia::render('Pilhas/Index', [
            'pilhas' => $pilhas,
            'pontos' => $pontos,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'codigo'            => ['required', 'string', 'max:20', 'unique:pilhas_produto,codigo'],
            'descricao'         => ['required', 'string', 'max:100'],
            'produto_codigo'    => ['nullable', 'string', 'max:30'],
            'produto_descricao' => ['nullable', 'string', 'max:100'],
            'ativa'             => ['sometimes', 'boolean'],
            'observacao'        => ['nullable', 'string'],
            'ponto_ids'         => ['sometimes', 'array'],
            'ponto_ids.*'       => ['integer', 'exists:pontos_carregamento,id'],
        ]);

        $pilha = PilhaProduto::create($data);

        if (isset($data['ponto_ids'])) {
            $pilha->pontosCarregamento()->sync($data['ponto_ids']);
        }

        return back()->with('success', 'Pilha criada.');
    }

    public function update(Request $request, PilhaProduto $pilhaProduto): RedirectResponse
    {
        $data = $request->validate([
            'codigo'            => ['sometimes', 'string', 'max:20', "unique:pilhas_produto,codigo,{$pilhaProduto->id}"],
            'descricao'         => ['sometimes', 'string', 'max:100'],
            'produto_codigo'    => ['nullable', 'string', 'max:30'],
            'produto_descricao' => ['nullable', 'string', 'max:100'],
            'ativa'             => ['sometimes', 'boolean'],
            'observacao'        => ['nullable', 'string'],
            'ponto_ids'         => ['sometimes', 'array'],
            'ponto_ids.*'       => ['integer', 'exists:pontos_carregamento,id'],
        ]);

        $pilhaProduto->update($data);

        if (array_key_exists('ponto_ids', $data)) {
            $pilhaProduto->pontosCarregamento()->sync($data['ponto_ids'] ?? []);
        }

        return back()->with('success', 'Pilha atualizada.');
    }

    public function destroy(PilhaProduto $pilhaProduto): RedirectResponse
    {
        $pilhaProduto->delete();

        return back()->with('success', 'Pilha removida.');
    }
}
