<?php

namespace App\Domain\Carregamento\Models;

use App\Domain\Carregamento\Enums\StatusPonto;
use Database\Factories\PontoCarregamentoFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class PontoCarregamento extends Model
{
    /** @use HasFactory<PontoCarregamentoFactory> */
    use HasFactory;
    use SoftDeletes;

    protected static function newFactory(): PontoCarregamentoFactory
    {
        return PontoCarregamentoFactory::new();
    }

    protected $table = 'pontos_carregamento';

    protected $fillable = [
        'codigo',
        'descricao',
        'status',
        'observacao',
    ];

    protected $casts = [
        'status' => StatusPonto::class,
    ];

    public function ordensCarregamento(): HasMany
    {
        return $this->hasMany(OrdemCarregamento::class, 'ponto_carregamento_id');
    }

    public function produtoPilhaPontos(): HasMany
    {
        return $this->hasMany(ProdutoPilhaPonto::class, 'ponto_carregamento_id');
    }

    public function scopeAtivos($query)
    {
        return $query->where('status', StatusPonto::ATIVO->value);
    }
}
