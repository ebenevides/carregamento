<?php

namespace App\Domain\Carregamento\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Equipamento extends Model
{
    use SoftDeletes;

    protected $table = 'equipamentos';

    protected $fillable = [
        'codigo',
        'descricao',
        'tipo',
        'ativo',
    ];

    protected $casts = [
        'ativo' => 'boolean',
    ];

    public function scopeAtivos($query)
    {
        return $query->where('ativo', true);
    }
}
