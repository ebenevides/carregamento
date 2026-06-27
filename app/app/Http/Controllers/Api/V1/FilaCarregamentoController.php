<?php

namespace App\Http\Controllers\Api\V1;

use App\Domain\Carregamento\Actions\EntrarNaFilaAction;
use App\Domain\Carregamento\Models\OrdemCarregamento;
use App\Domain\Carregamento\Models\PontoCarregamento;
use App\Domain\Fila\Services\FilaCarregamentoService;
use App\Http\Controllers\Controller;
use App\Http\Resources\OrdemCarregamentoResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Validation\ValidationException;

class FilaCarregamentoController extends Controller
{
    public function __construct(
        private readonly FilaCarregamentoService $filaService,
    ) {}

    public function index(): AnonymousResourceCollection
    {
        $pontoId = request()->integer('ponto_carregamento_id');

        if (!$pontoId) {
            throw ValidationException::withMessages([
                'ponto_carregamento_id' => 'Informe o ponto_carregamento_id.',
            ]);
        }

        $fila = $this->filaService->filaParaPonto($pontoId);

        return OrdemCarregamentoResource::collection($fila);
    }

    public function minhaFila(): AnonymousResourceCollection
    {
        $user = request()->user();
        $pontoId = $user->ponto_carregamento_id;

        if (!$pontoId) {
            throw ValidationException::withMessages([
                'ponto' => 'Operador sem ponto de carregamento configurado.',
            ]);
        }

        $fila = $this->filaService->filaParaPonto($pontoId);

        return OrdemCarregamentoResource::collection($fila);
    }

    public function liberarParaFila(OrdemCarregamento $ordemCarregamento, EntrarNaFilaAction $action): OrdemCarregamentoResource
    {
        $ordem = $action->execute($ordemCarregamento);

        return new OrdemCarregamentoResource($ordem);
    }

    public function validar(OrdemCarregamento $ordemCarregamento): JsonResponse
    {
        $erros = $this->filaService->validarAptoParaFila($ordemCarregamento);

        return response()->json([
            'apto'  => empty($erros),
            'erros' => $erros,
        ]);
    }
}
