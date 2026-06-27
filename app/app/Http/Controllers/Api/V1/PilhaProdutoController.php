<?php

namespace App\Http\Controllers\Api\V1;

use App\Domain\Carregamento\Models\PilhaProduto;
use App\Http\Controllers\Controller;
use App\Http\Requests\PilhaProduto\StorePilhaProdutoRequest;
use App\Http\Requests\PilhaProduto\UpdatePilhaProdutoRequest;
use App\Http\Resources\PilhaProdutoResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class PilhaProdutoController extends Controller
{
    public function index(): AnonymousResourceCollection
    {
        $pilhas = PilhaProduto::query()
            ->when(request('produto_codigo'), fn ($q, $c) => $q->where('produto_codigo', $c))
            ->when(request('ativa') === 'true', fn ($q) => $q->ativas())
            ->when(request('ativa') === 'false', fn ($q) => $q->where('ativa', false))
            ->orderBy('codigo')
            ->paginate(50);

        return PilhaProdutoResource::collection($pilhas);
    }

    public function show(PilhaProduto $pilhaProduto): PilhaProdutoResource
    {
        return new PilhaProdutoResource($pilhaProduto);
    }

    public function store(StorePilhaProdutoRequest $request): PilhaProdutoResource
    {
        $pilha = PilhaProduto::create($request->validated());

        return new PilhaProdutoResource($pilha);
    }

    public function update(UpdatePilhaProdutoRequest $request, PilhaProduto $pilhaProduto): PilhaProdutoResource
    {
        $pilhaProduto->update($request->validated());

        return new PilhaProdutoResource($pilhaProduto->fresh());
    }

    public function ativar(PilhaProduto $pilhaProduto): PilhaProdutoResource
    {
        $pilhaProduto->update(['ativa' => true]);

        return new PilhaProdutoResource($pilhaProduto->fresh());
    }

    public function inativar(PilhaProduto $pilhaProduto): PilhaProdutoResource
    {
        $pilhaProduto->update(['ativa' => false]);

        return new PilhaProdutoResource($pilhaProduto->fresh());
    }

    public function destroy(PilhaProduto $pilhaProduto): JsonResponse
    {
        $pilhaProduto->delete();

        return response()->json(null, 204);
    }
}
