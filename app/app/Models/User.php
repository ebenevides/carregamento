<?php

namespace App\Models;

use App\Domain\Carregamento\Enums\PerfilUsuario;
use App\Domain\Carregamento\Models\PontoCarregamento;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'perfil',
        'ponto_carregamento_id',
        'ativo',
        'documento',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password'          => 'hashed',
            'perfil'            => PerfilUsuario::class,
            'ativo'             => 'boolean',
        ];
    }

    public function pontoCarregamento(): BelongsTo
    {
        return $this->belongsTo(PontoCarregamento::class, 'ponto_carregamento_id');
    }

    public function isAdmin(): bool
    {
        return $this->perfil === PerfilUsuario::ADMIN;
    }

    public function isExpedicao(): bool
    {
        return $this->perfil === PerfilUsuario::EXPEDICAO;
    }

    public function isOperador(): bool
    {
        return $this->perfil === PerfilUsuario::OPERADOR;
    }

    public function isMotorista(): bool
    {
        return $this->perfil === PerfilUsuario::MOTORISTA;
    }

    public function podeGerenciar(): bool
    {
        return in_array($this->perfil, [PerfilUsuario::EXPEDICAO, PerfilUsuario::ADMIN], strict: true);
    }
}
