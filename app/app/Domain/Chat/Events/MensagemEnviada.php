<?php

declare(strict_types=1);

namespace App\Domain\Chat\Events;

use App\Domain\Chat\Models\MensagemChat;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class MensagemEnviada implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly MensagemChat $mensagem,
    ) {}

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel("ordem.{$this->mensagem->ordem_carregamento_id}.chat"),
        ];
    }

    public function broadcastAs(): string
    {
        return 'mensagem.enviada';
    }

    public function broadcastWith(): array
    {
        return [
            'id'                => $this->mensagem->id,
            'ordem_id'          => $this->mensagem->ordem_carregamento_id,
            'remetente_id'      => $this->mensagem->remetente_id,
            'perfil_remetente'  => $this->mensagem->perfil_remetente,
            'mensagem'          => $this->mensagem->mensagem,
            'created_at'        => $this->mensagem->created_at->toISOString(),
        ];
    }
}
