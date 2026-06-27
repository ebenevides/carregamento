<?php

namespace App\Domain\Carregamento\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProdutoPilhaPonto extends Model
{
    protected $table = 'produto_pilha_ponto';

    protected $fillable = [
        'produto_codigo',
        'produto_descricao',
        'pilha_produto_id',
        'ponto_carregamento_id',
        'padrao',
        'ativo',
    ];

    protected $casts = [
        'padrao' => 'boolean',
        'ativo'  => 'boolean',
    ];

    public function pilhaProduto(): BelongsTo
    {
        return $this->belongsTo(PilhaProduto::class, 'pilha_produto_id');
    }

    public function pontoCarregamento(): BelongsTo
    {
        return $this->belongsTo(PontoCarregamento::class, 'ponto_carregamento_id');
    }

    public function scopeAtivos($query)
    {
        return $query->where('ativo', true);
    }

    public function scopePadrao($query)
    {
        return $query->where('padrao', true);
    }
}
