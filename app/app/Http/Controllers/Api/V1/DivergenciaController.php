<?php

namespace App\Http\Controllers\Api\V1;

use App\Domain\Carregamento\Actions\AlterarStatusOrdemAction;
use App\Domain\Carregamento\DTOs\AlterarStatusDTO;
use App\Domain\Carregamento\Enums\OrigemEvento;
use App\Domain\Carregamento\Enums\StatusDivergencia;
use App\Domain\Carregamento\Enums\StatusOrdem;
use App\Domain\Carregamento\Enums\TipoEvento;
use App\Domain\Carregamento\Models\DivergenciaCarregamento;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DivergenciaController extends Controller
{
    public function index(): JsonResponse
    {
        $divergencias = DivergenciaCarregamento::with('ordemCarregamento')
            ->when(request('status'), fn ($q, $s) => $q->where('status', $s))
            ->when(!request('status'), fn ($q) => $q->abertas())
            ->when(request('ponto_id'), fn ($q, $id) => $q->whereHas(
                'ordemCarregamento', fn ($q2) => $q2->where('ponto_carregamento_id', $id)
            ))
            ->orderByDesc('created_at')
            ->paginate(30);

        return response()->json($divergencias);
    }

    public function resolver(Request $request, DivergenciaCarregamento $divergencia, AlterarStatusOrdemAction $alterarStatus): JsonResponse
    {
        $request->validate([
            'resolucao'  => ['required', 'string', 'max:1000'],
            'usuario_id' => ['nullable', 'integer', 'exists:users,id'],
            'liberar'    => ['sometimes', 'boolean'],
        ]);

        DB::transaction(function () use ($request, $divergencia, $alterarStatus) {
            $divergencia->update([
                'status'             => StatusDivergencia::RESOLVIDA,
                'resolucao'          => $request->input('resolucao'),
                'resolvido_por'      => $request->input('usuario_id'),
                'resolvido_por_nome' => $request->input('usuario_nome'),
                'resolvido_em'       => now(),
            ]);

            $ordem = $divergencia->ordemCarregamento;

            if ($request->boolean('liberar') && $ordem && !$ordem->temDivergenciaAberta()) {
                $alterarStatus->execute($ordem, new AlterarStatusDTO(
                    novoStatus: StatusOrdem::AGUARDANDO_CARREGAMENTO,
                    tipoEvento: TipoEvento::DIVERGENCIA_RESOLVIDA,
                    origem: OrigemEvento::PAINEL_WEB,
                    usuarioId: $request->integer('usuario_id') ?: null,
                    observacao: "Divergência resolvida: {$request->input('resolucao')}",
                ));
            }
        });

        return response()->json($divergencia->fresh('ordemCarregamento'));
    }
}
