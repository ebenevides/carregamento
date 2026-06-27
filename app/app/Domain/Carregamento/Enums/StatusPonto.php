<?php

namespace App\Domain\Carregamento\Enums;

enum StatusPonto: string
{
    case ATIVO    = 'ATIVO';
    case INATIVO  = 'INATIVO';
    case BLOQUEADO = 'BLOQUEADO';

    public function label(): string
    {
        return match($this) {
            self::ATIVO     => 'Ativo',
            self::INATIVO   => 'Inativo',
            self::BLOQUEADO => 'Bloqueado',
        };
    }

    public function disponivelParaFila(): bool
    {
        return $this === self::ATIVO;
    }
}
