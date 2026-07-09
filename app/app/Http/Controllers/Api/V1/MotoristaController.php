<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Domain\Carregamento\Enums\PerfilUsuario;
use App\Domain\Carregamento\Enums\StatusOrdem;
use App\Domain\Carregamento\Models\OrdemCarregamento;
use App\Http\Controllers\Controller;
use App\Http\Resources\OrdemMotoristaResource;

class MotoristaController extends Controller
{
    /**
     * Retorna a ordem ativa do motorista autenticado.
     * 204 se nenhuma ordem ativa encontrada.
     */
    public function minhaOrdem(): OrdemMotoristaResource|\Illuminate\Http\JsonResponse
    {
        /** @var \App\Models\User $user */
        $user = auth()->user();

        if ($user->perfil !== PerfilUsuario::MOTORISTA) {
            return response()->json(['message' => 'Acesso restrito a motoristas.'], 403);
        }

        $ordem = OrdemCarregamento::where('motorista_user_id', $user->id)
            ->whereNotIn('status', [StatusOrdem::CANCELADO, StatusOrdem::FINALIZADO])
            ->with(['pilhaProduto', 'pontoCarregamento', 'divergencias'])
            ->latest()
            ->first();

        if (!$ordem) {
            return response()->json(null, 204);
        }

        return new OrdemMotoristaResource($ordem);
    }
}
