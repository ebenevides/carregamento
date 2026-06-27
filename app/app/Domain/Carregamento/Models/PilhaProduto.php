<?php

namespace App\Domain\Carregamento\Models;

use Database\Factories\PilhaProdutoFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class PilhaProduto extends Model
{
    /** @use HasFactory<PilhaProdutoFactory> */
    use HasFactory;
    use SoftDeletes;

    protected static function newFactory(): PilhaProdutoFactory
    {
        return PilhaProdutoFactory::new();
    }

    protected $table = 'pilhas_produto';

    protected $fillable = [
        'codigo',
        'descricao',
        'produto_codigo',
        'produto_descricao',
        'ativa',
        'observacao',
    ];

    protected $casts = [
        'ativa' => 'boolean',
    ];

    public function produtoPilhaPontos(): HasMany
    {
        return $this->hasMany(ProdutoPilhaPonto::class, 'pilha_produto_id');
    }

    public function pontosCarregamento()
    {
        return $this->belongsToMany(
            PontoCarregamento::class,
            'produto_pilha_ponto',
            'pilha_produto_id',
            'ponto_carregamento_id'
        );
    }

    public function scopeAtivas($query)
    {
        return $query->where('ativa', true);
    }
}
