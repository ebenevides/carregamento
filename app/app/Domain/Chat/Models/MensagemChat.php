<?php

declare(strict_types=1);

namespace App\Domain\Chat\Models;

use App\Domain\Carregamento\Models\OrdemCarregamento;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MensagemChat extends Model
{
    protected $table = 'mensagens_chat';

    protected $fillable = [
        'ordem_carregamento_id',
        'remetente_id',
        'perfil_remetente',
        'mensagem',
        'lida_em',
    ];

    protected $casts = [
        'lida_em' => 'datetime',
    ];

    public function ordemCarregamento(): BelongsTo
    {
        return $this->belongsTo(OrdemCarregamento::class, 'ordem_carregamento_id');
    }

    public function remetente(): BelongsTo
    {
        return $this->belongsTo(User::class, 'remetente_id');
    }

    public function foiLida(): bool
    {
        return $this->lida_em !== null;
    }

    public function marcarComoLida(): void
    {
        if (!$this->foiLida()) {
            $this->update(['lida_em' => now()]);
        }
    }
}
