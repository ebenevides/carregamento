<?php

namespace App\Domain\Carregamento\Actions;

use App\Domain\Carregamento\DTOs\AlterarStatusDTO;
use App\Domain\Carregamento\Enums\OrigemEvento;
use App\Domain\Carregamento\Enums\StatusOrdem;
use App\Domain\Carregamento\Enums\TipoDivergencia;
use App\Domain\Carregamento\Enums\TipoEvento;
use App\Domain\Carregamento\Models\OrdemCarregamento;

class RegistrarPesagemFinalAction
{
    public function __construct(
        private readonly AlterarStatusOrdemAction $alterarStatus,
        private readonly RegistrarDivergenciaAction $registrarDivergencia,
    ) {}

    public function execute(
        OrdemCarregamento $ordem,
        float $pesoBruto,
        OrigemEvento $origem = OrigemEvento::GUARDIAN,
    ): OrdemCarregamento {
        $pesoLiquido = $pesoBruto - (float) $ordem->tara;

        $ordem->update([
            'peso_bruto'       => $pesoBruto,
            'peso_liquido'     => $pesoLiquido,
            'pesagem_final_em' => now(),
        ]);

        $this->alterarStatus->execute($ordem, new AlterarStatusDTO(
            novoStatus: StatusOrdem::PESAGEM_FINAL_REALIZADA,
            tipoEvento: TipoEvento::PESAGEM_FINAL_REGISTRADA,
            origem: $origem,
            observacao: "Peso bruto: {$pesoBruto} kg | Líquido: {$pesoLiquido} kg",
            payload: ['peso_bruto' => $pesoBruto, 'peso_liquido' => $pesoLiquido],
        ));

        $ordem->refresh();

        if ($ordem->dentroDaTolerancia()) {
            $this->alterarStatus->execute($ordem, new AlterarStatusDTO(
                novoStatus: StatusOrdem::VALIDADO,
                tipoEvento: TipoEvento::ORDEM_VALIDADA,
                origem: OrigemEvento::SISTEMA,
                observacao: 'Peso dentro da tolerância. Validado automaticamente.',
            ));
        } else {
            $diff = abs($pesoLiquido - (float) $ordem->quantidade_prevista);
            $this->registrarDivergencia->execute(
                $ordem,
                TipoDivergencia::PESO_FORA_TOLERANCIA,
                OrigemEvento::SISTEMA,
                "Diferença de {$diff} kg. Tolerância: {$ordem->tolerancia_percentual}%.",
            );
        }

        return $ordem->fresh();
    }
}
