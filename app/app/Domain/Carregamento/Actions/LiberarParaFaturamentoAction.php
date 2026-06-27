<?php

namespace App\Domain\Carregamento\Actions;

use App\Domain\Carregamento\DTOs\AlterarStatusDTO;
use App\Domain\Carregamento\Enums\OrigemEvento;
use App\Domain\Carregamento\Enums\StatusOrdem;
use App\Domain\Carregamento\Enums\TipoEvento;
use App\Domain\Carregamento\Models\OrdemCarregamento;
use Illuminate\Validation\ValidationException;

class LiberarParaFaturamentoAction
{
    public function __construct(
        private readonly AlterarStatusOrdemAction $alterarStatus,
    ) {}

    public function execute(
        OrdemCarregamento $ordem,
        ?int $usuarioId = null,
        ?string $observacao = null,
        OrigemEvento $origem = OrigemEvento::PAINEL_WEB,
    ): OrdemCarregamento {
        $erros = $this->validar($ordem);

        if (!empty($erros)) {
            throw ValidationException::withMessages(['liberacao' => $erros]);
        }

        $this->alterarStatus->execute($ordem, new AlterarStatusDTO(
            novoStatus: StatusOrdem::FINALIZADO,
            tipoEvento: TipoEvento::ORDEM_FINALIZADA,
            origem: $origem,
            usuarioId: $usuarioId,
            observacao: $observacao ?? 'Liberado para faturamento.',
            payload: [
                'peso_liquido'    => $ordem->peso_liquido,
                'peso_bruto'      => $ordem->peso_bruto,
                'tara'            => $ordem->tara,
                'ticket_guardian' => $ordem->ticket_guardian,
                'pedido_numero'   => $ordem->pedido_numero,
            ],
        ));

        return $ordem->fresh();
    }

    private function validar(OrdemCarregamento $ordem): array
    {
        $erros = [];

        if ($ordem->status !== StatusOrdem::VALIDADO) {
            $erros[] = "Ordem deve estar em VALIDADO para liberar. Status atual: {$ordem->status->value}";
        }

        if ($ordem->ticket_guardian === null) {
            $erros[] = 'Ordem sem ticket Guardian (RN-001).';
        }

        if ($ordem->peso_liquido === null) {
            $erros[] = 'Pesagem final não registrada.';
        }

        if ($ordem->temDivergenciaAberta()) {
            $erros[] = 'Ordem possui divergência aberta (RN-005).';
        }

        return $erros;
    }
}
