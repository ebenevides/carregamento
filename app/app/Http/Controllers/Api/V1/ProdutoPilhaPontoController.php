<?php

namespace App\Http\Controllers\Api\V1;

use App\Domain\Carregamento\Models\ProdutoPilhaPonto;
use App\Http\Controllers\Controller;
use App\Http\Requests\ProdutoPilhaPonto\StoreProdutoPilhaPontoRequest;
use Illuminate\Http\JsonResponse;

class ProdutoPilhaPontoController extends Controller
{
    public function index(): JsonResponse
    {
        $vinculos = ProdutoPilhaPonto::with(['pilhaProduto', 'pontoCarregamento'])
            ->when(request('produto_codigo'), fn ($q, $c) => $q->where('produto_codigo', $c))
            ->when(request('ativo') === 'true', fn ($q) => $q->ativos())
            ->orderBy('produto_codigo')
            ->paginate(50);

        return response()->json($vinculos);
    }

    public function store(StoreProdutoPilhaPontoRequest $request): JsonResponse
    {
        $dados = $request->validated();

        if ($dados['padrao'] ?? false) {
            ProdutoPilhaPonto::where('produto_codigo', $dados['produto_codigo'])
                ->update(['padrao' => false]);
        }

        $vinculo = ProdutoPilhaPonto::create($dados);

        return response()->json($vinculo->load(['pilhaProduto', 'pontoCarregamento']), 201);
    }

    public function destroy(ProdutoPilhaPonto $produtoPilhaPonto): JsonResponse
    {
        $produtoPilhaPonto->delete();

        return response()->json(null, 204);
    }
}
