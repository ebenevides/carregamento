<?php

namespace App\Domain\Carregamento\Actions;

use App\Domain\Carregamento\DTOs\AlterarStatusDTO;
use App\Domain\Carregamento\Enums\OrigemEvento;
use App\Domain\Carregamento\Enums\StatusDivergencia;
use App\Domain\Carregamento\Enums\StatusOrdem;
use App\Domain\Carregamento\Enums\TipoDivergencia;
use App\Domain\Carregamento\Enums\TipoEvento;
use App\Domain\Carregamento\Models\DivergenciaCarregamento;
use App\Domain\Carregamento\Models\OrdemCarregamento;

class RegistrarDivergenciaAction
{
    public function __construct(
        private readonly AlterarStatusOrdemAction $alterarStatus,
    ) {}

    public function execute(
        OrdemCarregamento $ordem,
        TipoDivergencia $tipo,
        OrigemEvento $origem,
        string $descricao,
        ?int $usuarioId = null,
        ?string $usuarioNome = null,
    ): DivergenciaCarregamento {
        $divergencia = DivergenciaCarregamento::create([
            'ordem_carregamento_id' => $ordem->id,
            'tipo'                  => $tipo,
            'status'                => StatusDivergencia::ABERTA,
            'origem'                => $origem,
            'descricao'             => $descricao,
            'registrado_por'        => $usuarioId,
            'registrado_por_nome'   => $usuarioNome,
        ]);

        if ($ordem->status !== StatusOrdem::DIVERGENCIA) {
            $this->alterarStatus->execute($ordem, new AlterarStatusDTO(
                novoStatus: StatusOrdem::DIVERGENCIA,
                tipoEvento: TipoEvento::DIVERGENCIA_REGISTRADA,
                origem: $origem,
                usuarioId: $usuarioId,
                usuarioNome: $usuarioNome,
                observacao: $descricao,
                payload: ['tipo' => $tipo->value, 'divergencia_id' => $divergencia->id],
            ));
        }

        return $divergencia;
    }
}
