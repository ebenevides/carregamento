<?php

namespace App\Domain\Carregamento\Enums;

enum PerfilUsuario: string
{
    case ADMIN     = 'ADMIN';
    case EXPEDICAO = 'EXPEDICAO';
    case OPERADOR  = 'OPERADOR';
    case MOTORISTA = 'MOTORISTA';

    public function label(): string
    {
        return match($this) {
            self::ADMIN     => 'Administrador',
            self::EXPEDICAO => 'Expedição',
            self::OPERADOR  => 'Operador',
            self::MOTORISTA => 'Motorista',
        };
    }

    public function podeIniciarCarregamento(): bool
    {
        return in_array($this, [self::OPERADOR, self::EXPEDICAO, self::ADMIN], strict: true);
    }

    public function podeConcluirCarregamento(): bool
    {
        return in_array($this, [self::OPERADOR, self::EXPEDICAO, self::ADMIN], strict: true);
    }

    public function podeResolverDivergencia(): bool
    {
        return in_array($this, [self::EXPEDICAO, self::ADMIN], strict: true);
    }

    public function podeCancelarOrdem(): bool
    {
        return in_array($this, [self::EXPEDICAO, self::ADMIN], strict: true);
    }
}
