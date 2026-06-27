<?php

namespace App\Domain\Carregamento\Models;

use App\Domain\Carregamento\Enums\OrigemEvento;
use App\Domain\Carregamento\Enums\StatusOrdem;
use App\Domain\Carregamento\Enums\TipoEvento;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Auth\User;

class EventoOrdemCarregamento extends Model
{
    protected $table = 'eventos_ordem_carregamento';

    protected $fillable = [
        'ordem_carregamento_id',
        'tipo',
        'status_anterior',
        'status_novo',
        'origem',
        'usuario_id',
        'usuario_nome',
        'observacao',
        'payload',
        'ocorrido_em',
    ];

    protected $casts = [
        'tipo'            => TipoEvento::class,
        'status_anterior' => StatusOrdem::class,
        'status_novo'     => StatusOrdem::class,
        'origem'          => OrigemEvento::class,
        'payload'         => 'array',
        'ocorrido_em'     => 'datetime',
    ];

    public function ordemCarregamento(): BelongsTo
    {
        return $this->belongsTo(OrdemCarregamento::class, 'ordem_carregamento_id');
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'usuario_id');
    }
}
