<?php

namespace App\Domain\Carregamento\Models;

use App\Domain\Carregamento\Enums\OrigemEvento;
use App\Domain\Carregamento\Enums\StatusDivergencia;
use App\Domain\Carregamento\Enums\TipoDivergencia;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Auth\User;

class DivergenciaCarregamento extends Model
{
    protected $table = 'divergencias_carregamento';

    protected $fillable = [
        'ordem_carregamento_id',
        'tipo',
        'status',
        'origem',
        'descricao',
        'registrado_por',
        'registrado_por_nome',
        'resolvido_por',
        'resolvido_por_nome',
        'resolucao',
        'resolvido_em',
    ];

    protected $casts = [
        'tipo'         => TipoDivergencia::class,
        'status'       => StatusDivergencia::class,
        'origem'       => OrigemEvento::class,
        'resolvido_em' => 'datetime',
    ];

    public function ordemCarregamento(): BelongsTo
    {
        return $this->belongsTo(OrdemCarregamento::class, 'ordem_carregamento_id');
    }

    public function registradoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'registrado_por');
    }

    public function resolvidoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'resolvido_por');
    }

    public function scopeAbertas($query)
    {
        return $query->where('status', StatusDivergencia::ABERTA->value);
    }
}
