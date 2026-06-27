<?php

namespace App\Domain\Carregamento\Actions;

use App\Domain\Carregamento\DTOs\AlterarStatusDTO;
use App\Domain\Carregamento\Events\OrdemStatusAlterado;
use App\Domain\Carregamento\Models\EventoOrdemCarregamento;
use App\Domain\Carregamento\Models\OrdemCarregamento;
use Illuminate\Validation\ValidationException;

class AlterarStatusOrdemAction
{
    public function execute(OrdemCarregamento $ordem, AlterarStatusDTO $dto): EventoOrdemCarregamento
    {
        $statusAtual = $ordem->status;

        if (!$statusAtual->podeTransicionarPara($dto->novoStatus)) {
            throw ValidationException::withMessages([
                'status' => "Transição inválida: {$statusAtual->value} → {$dto->novoStatus->value}",
            ]);
        }

        $evento = EventoOrdemCarregamento::create([
            'ordem_carregamento_id' => $ordem->id,
            'tipo'                  => $dto->tipoEvento,
            'status_anterior'       => $statusAtual,
            'status_novo'           => $dto->novoStatus,
            'origem'                => $dto->origem,
            'usuario_id'            => $dto->usuarioId,
            'usuario_nome'          => $dto->usuarioNome,
            'observacao'            => $dto->observacao,
            'payload'               => $dto->payload,
            'ocorrido_em'           => now(),
        ]);

        $ordem->update(['status' => $dto->novoStatus]);
        $ordem->refresh();

        OrdemStatusAlterado::dispatch($ordem, $evento);

        return $evento;
    }
}
