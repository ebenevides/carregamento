<?php

namespace App\Domain\Carregamento\Events;

use App\Domain\Carregamento\Models\EventoOrdemCarregamento;
use App\Domain\Carregamento\Models\OrdemCarregamento;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class OrdemStatusAlterado implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly OrdemCarregamento $ordem,
        public readonly EventoOrdemCarregamento $evento,
    ) {}

    public function broadcastOn(): array
    {
        return [
            new Channel('ordens'),
            new Channel("ponto.{$this->ordem->ponto_carregamento_id}"),
        ];
    }

    public function broadcastAs(): string
    {
        return 'ordem.status.alterado';
    }

    public function broadcastWith(): array
    {
        return [
            'ordem_id'        => $this->ordem->id,
            'status'          => $this->ordem->status->value,
            'status_label'    => $this->ordem->status->label(),
            'ponto_id'        => $this->ordem->ponto_carregamento_id,
            'placa_veiculo'   => $this->ordem->placa_veiculo,
            'produto_codigo'  => $this->ordem->produto_codigo,
            'tipo_evento'     => $this->evento->tipo->value,
            'ocorrido_em'     => $this->evento->ocorrido_em->toISOString(),
        ];
    }
}
