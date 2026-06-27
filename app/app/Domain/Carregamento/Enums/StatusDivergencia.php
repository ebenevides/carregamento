<?php

namespace App\Domain\Carregamento\Enums;

enum StatusDivergencia: string
{
    case ABERTA   = 'ABERTA';
    case RESOLVIDA = 'RESOLVIDA';
    case CANCELADA = 'CANCELADA';

    public function label(): string
    {
        return match($this) {
            self::ABERTA    => 'Aberta',
            self::RESOLVIDA => 'Resolvida',
            self::CANCELADA => 'Cancelada',
        };
    }
}
