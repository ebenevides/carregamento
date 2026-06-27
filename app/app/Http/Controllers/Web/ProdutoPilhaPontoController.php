<?php

namespace App\Http\Controllers\Web;

use App\Domain\Carregamento\Models\PilhaProduto;
use App\Domain\Carregamento\Models\PontoCarregamento;
use App\Domain\Carregamento\Models\ProdutoPilhaPonto;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ProdutoPilhaPontoController extends Controller
{
    public function index(Request $request): Response
    {
        $busca = $request->input('busca');

        $mapeamentos = ProdutoPilhaPonto::with(['pilhaProduto', 'pontoCarregamento'])
            ->when($busca, fn ($q) => $q->where(fn ($q2) =>
                $q2->where('produto_codigo', 'ilike', "%{$busca}%")
                   ->orWhere('produto_descricao', 'ilike', "%{$busca}%")
            ))
            ->orderBy('produto_codigo')
            ->paginate(40)
            ->through(fn ($m) => [
                'id'               => $m->id,
                'produto_codigo'   => $m->produto_codigo,
                'produto_descricao' => $m->produto_descricao,
                'pilha_produto_id' => $m->pilha_produto_id,
                'pilha_descricao'  => $m->pilhaProduto?->descricao,
                'ponto_id'         => $m->ponto_carregamento_id,
                'ponto_descricao'  => $m->pontoCarregamento?->descricao,
                'padrao'           => $m->padrao,
                'ativo'            => $m->ativo,
            ]);

        return Inertia::render('Mapeamento/Index', [
            'mapeamentos' => $mapeamentos,
            'pilhas'      => PilhaProduto::where('ativa', true)->orderBy('descricao')->get(['id', 'descricao']),
            'pontos'      => PontoCarregamento::orderBy('descricao')->get(['id', 'descricao']),
            'filtros'     => ['busca' => $busca],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'produto_codigo'      => ['required', 'string', 'max:30'],
            'produto_descricao'   => ['nullable', 'string', 'max:100'],
            'pilha_produto_id'    => ['required', 'integer', 'exists:pilhas_produto,id'],
            'ponto_carregamento_id' => ['required', 'integer', 'exists:pontos_carregamento,id'],
            'padrao'              => ['sometimes', 'boolean'],
            'ativo'               => ['sometimes', 'boolean'],
        ]);

        ProdutoPilhaPonto::create(['ativo' => true, 'padrao' => false, ...$data]);

        return back()->with('success', 'Mapeamento criado.');
    }

    public function update(Request $request, ProdutoPilhaPonto $mapeamento): RedirectResponse
    {
        $data = $request->validate([
            'produto_codigo'        => ['sometimes', 'string', 'max:30'],
            'produto_descricao'     => ['nullable', 'string', 'max:100'],
            'pilha_produto_id'      => ['sometimes', 'integer', 'exists:pilhas_produto,id'],
            'ponto_carregamento_id' => ['sometimes', 'integer', 'exists:pontos_carregamento,id'],
            'padrao'                => ['sometimes', 'boolean'],
            'ativo'                 => ['sometimes', 'boolean'],
        ]);

        $mapeamento->update($data);

        return back()->with('success', 'Mapeamento atualizado.');
    }

    public function destroy(ProdutoPilhaPonto $mapeamento): RedirectResponse
    {
        $mapeamento->delete();

        return back()->with('success', 'Mapeamento removido.');
    }
}
