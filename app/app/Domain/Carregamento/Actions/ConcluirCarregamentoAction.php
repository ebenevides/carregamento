<?php

namespace App\Domain\Carregamento\Actions;

use App\Domain\Carregamento\DTOs\AlterarStatusDTO;
use App\Domain\Carregamento\Enums\OrigemEvento;
use App\Domain\Carregamento\Enums\StatusOrdem;
use App\Domain\Carregamento\Enums\TipoEvento;
use App\Domain\Carregamento\Models\OrdemCarregamento;

class ConcluirCarregamentoAction
{
    public function __construct(
        private readonly AlterarStatusOrdemAction $alterarStatus,
    ) {}

    public function execute(
        OrdemCarregamento $ordem,
        ?int $operadorId,
        ?string $observacao,
        OrigemEvento $origem = OrigemEvento::APP_OPERADOR,
    ): OrdemCarregamento {
        $ordem->update(['concluido_em' => now()]);

        $this->alterarStatus->execute($ordem, new AlterarStatusDTO(
            novoStatus: StatusOrdem::CARREGAMENTO_CONCLUIDO,
            tipoEvento: TipoEvento::CARREGAMENTO_CONCLUIDO,
            origem: $origem,
            usuarioId: $operadorId,
            observacao: $observacao,
        ));

        $this->alterarStatus->execute($ordem, new AlterarStatusDTO(
            novoStatus: StatusOrdem::AGUARDANDO_PESAGEM_FINAL,
            tipoEvento: TipoEvento::STATUS_ALTERADO,
            origem: OrigemEvento::SISTEMA,
            observacao: 'Aguardando pesagem final no Guardian',
        ));

        return $ordem->fresh(['pilhaProduto', 'pontoCarregamento']);
    }
}
