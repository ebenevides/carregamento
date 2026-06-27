<?php

namespace App\Domain\Carregamento\Enums;

enum OrigemEvento: string
{
    case SISTEMA      = 'SISTEMA';
    case APP_OPERADOR = 'APP_OPERADOR';
    case PAINEL_WEB   = 'PAINEL_WEB';
    case API          = 'API';
    case GUARDIAN     = 'GUARDIAN';
    case PROTHEUS     = 'PROTHEUS';

    public function label(): string
    {
        return match($this) {
            self::SISTEMA      => 'Sistema',
            self::APP_OPERADOR => 'App Operador',
            self::PAINEL_WEB   => 'Painel Web',
            self::API          => 'API',
            self::GUARDIAN     => 'Prix Guardian',
            self::PROTHEUS     => 'Protheus',
        };
    }
}
