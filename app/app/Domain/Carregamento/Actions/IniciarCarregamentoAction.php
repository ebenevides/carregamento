<?php

namespace App\Domain\Carregamento\Actions;

use App\Domain\Carregamento\DTOs\AlterarStatusDTO;
use App\Domain\Carregamento\Enums\OrigemEvento;
use App\Domain\Carregamento\Enums\StatusOrdem;
use App\Domain\Carregamento\Enums\TipoEvento;
use App\Domain\Carregamento\Models\Equipamento;
use App\Domain\Carregamento\Models\OrdemCarregamento;
use Illuminate\Validation\ValidationException;

class IniciarCarregamentoAction
{
    public function __construct(
        private readonly AlterarStatusOrdemAction $alterarStatus,
    ) {}

    public function execute(
        OrdemCarregamento $ordem,
        int $operadorId,
        int $pontoCarregamentoId,
        ?string $equipamentoCodigo,
        ?string $observacao,
        OrigemEvento $origem = OrigemEvento::APP_OPERADOR,
    ): OrdemCarregamento {
        if ($ordem->ponto_carregamento_id !== $pontoCarregamentoId) {
            throw ValidationException::withMessages([
                'ponto_carregamento_id' => 'Operador não está no ponto de carregamento desta ordem.',
            ]);
        }

        if ($ordem->ticket_guardian === null) {
            throw ValidationException::withMessages([
                'ticket_guardian' => 'Ordem sem ticket Guardian (RN-001).',
            ]);
        }

        if ($ordem->tara === null) {
            throw ValidationException::withMessages([
                'tara' => 'Ordem sem tara registrada (RN-002).',
            ]);
        }

        if ($ordem->temDivergenciaAberta()) {
            throw ValidationException::withMessages([
                'divergencia' => 'Ordem possui divergência aberta (RN-005).',
            ]);
        }

        $equipamentoId = null;
        if ($equipamentoCodigo) {
            $equipamentoId = Equipamento::where('codigo', $equipamentoCodigo)->value('id');
        }

        $ordem->update([
            'operador_id'    => $operadorId,
            'equipamento_id' => $equipamentoId,
            'iniciado_em'    => now(),
        ]);

        $this->alterarStatus->execute($ordem, new AlterarStatusDTO(
            novoStatus: StatusOrdem::EM_CARREGAMENTO,
            tipoEvento: TipoEvento::CARREGAMENTO_INICIADO,
            origem: $origem,
            usuarioId: $operadorId,
            observacao: $observacao,
            payload: ['equipamento_codigo' => $equipamentoCodigo],
        ));

        return $ordem->fresh(['pilhaProduto', 'pontoCarregamento', 'operador']);
    }
}
