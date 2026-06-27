<?php

namespace App\Http\Controllers\Api\V1;

use App\Domain\Carregamento\Enums\StatusPonto;
use App\Domain\Carregamento\Models\PontoCarregamento;
use App\Http\Controllers\Controller;
use App\Http\Requests\PontoCarregamento\StorePontoCarregamentoRequest;
use App\Http\Requests\PontoCarregamento\UpdatePontoCarregamentoRequest;
use App\Http\Resources\PontoCarregamentoResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class PontoCarregamentoController extends Controller
{
    public function index(): AnonymousResourceCollection
    {
        $pontos = PontoCarregamento::query()
            ->when(request('status'), fn ($q, $s) => $q->where('status', $s))
            ->when(request('ativo') === 'true', fn ($q) => $q->ativos())
            ->orderBy('codigo')
            ->paginate(50);

        return PontoCarregamentoResource::collection($pontos);
    }

    public function show(PontoCarregamento $pontoCarregamento): PontoCarregamentoResource
    {
        return new PontoCarregamentoResource($pontoCarregamento);
    }

    public function store(StorePontoCarregamentoRequest $request): PontoCarregamentoResource
    {
        $ponto = PontoCarregamento::create($request->validated());

        return new PontoCarregamentoResource($ponto);
    }

    public function update(UpdatePontoCarregamentoRequest $request, PontoCarregamento $pontoCarregamento): PontoCarregamentoResource
    {
        $pontoCarregamento->update($request->validated());

        return new PontoCarregamentoResource($pontoCarregamento->fresh());
    }

    public function ativar(PontoCarregamento $pontoCarregamento): PontoCarregamentoResource
    {
        $pontoCarregamento->update(['status' => StatusPonto::ATIVO]);

        return new PontoCarregamentoResource($pontoCarregamento->fresh());
    }

    public function inativar(PontoCarregamento $pontoCarregamento): PontoCarregamentoResource
    {
        $pontoCarregamento->update(['status' => StatusPonto::INATIVO]);

        return new PontoCarregamentoResource($pontoCarregamento->fresh());
    }

    public function destroy(PontoCarregamento $pontoCarregamento): JsonResponse
    {
        $pontoCarregamento->delete();

        return response()->json(null, 204);
    }
}
