<?php

namespace App\Domain\Carregamento\Actions;

use App\Domain\Carregamento\DTOs\AlterarStatusDTO;
use App\Domain\Carregamento\Enums\OrigemEvento;
use App\Domain\Carregamento\Enums\StatusOrdem;
use App\Domain\Carregamento\Enums\TipoEvento;
use App\Domain\Carregamento\Models\OrdemCarregamento;
use App\Domain\Fila\Services\FilaCarregamentoService;
use Illuminate\Validation\ValidationException;

class EntrarNaFilaAction
{
    public function __construct(
        private readonly AlterarStatusOrdemAction $alterarStatus,
        private readonly FilaCarregamentoService $fila,
    ) {}

    public function execute(OrdemCarregamento $ordem, OrigemEvento $origem = OrigemEvento::SISTEMA): OrdemCarregamento
    {
        $erros = $this->fila->validarAptoParaFila($ordem);

        if (!empty($erros)) {
            throw ValidationException::withMessages(['fila' => $erros]);
        }

        $this->alterarStatus->execute($ordem, new AlterarStatusDTO(
            novoStatus: StatusOrdem::AGUARDANDO_CARREGAMENTO,
            tipoEvento: TipoEvento::ORDEM_ENTRADA_FILA,
            origem: $origem,
            observacao: 'Ordem liberada para a fila de carregamento.',
        ));

        $this->fila->invalidarCachePonto($ordem->ponto_carregamento_id);

        return $ordem->fresh();
    }
}
