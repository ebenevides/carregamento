<?php

namespace App\Domain\Carregamento\Enums;

enum TipoEvento: string
{
    case ORDEM_CRIADA                = 'ORDEM_CRIADA';
    case TARA_REALIZADA              = 'TARA_REALIZADA';
    case ORDEM_ENTRADA_FILA          = 'ORDEM_ENTRADA_FILA';
    case CARREGAMENTO_INICIADO       = 'CARREGAMENTO_INICIADO';
    case CARREGAMENTO_CONCLUIDO      = 'CARREGAMENTO_CONCLUIDO';
    case PESAGEM_FINAL_REGISTRADA    = 'PESAGEM_FINAL_REGISTRADA';
    case ORDEM_VALIDADA              = 'ORDEM_VALIDADA';
    case DIVERGENCIA_REGISTRADA      = 'DIVERGENCIA_REGISTRADA';
    case DIVERGENCIA_RESOLVIDA       = 'DIVERGENCIA_RESOLVIDA';
    case ORDEM_CANCELADA             = 'ORDEM_CANCELADA';
    case ORDEM_FINALIZADA            = 'ORDEM_FINALIZADA';
    case STATUS_ALTERADO             = 'STATUS_ALTERADO';

    public function label(): string
    {
        return match($this) {
            self::ORDEM_CRIADA             => 'Ordem criada',
            self::TARA_REALIZADA           => 'Tara realizada',
            self::ORDEM_ENTRADA_FILA       => 'Entrada na fila',
            self::CARREGAMENTO_INICIADO    => 'Carregamento iniciado',
            self::CARREGAMENTO_CONCLUIDO   => 'Carregamento concluído',
            self::PESAGEM_FINAL_REGISTRADA => 'Pesagem final registrada',
            self::ORDEM_VALIDADA           => 'Ordem validada',
            self::DIVERGENCIA_REGISTRADA   => 'Divergência registrada',
            self::DIVERGENCIA_RESOLVIDA    => 'Divergência resolvida',
            self::ORDEM_CANCELADA          => 'Ordem cancelada',
            self::ORDEM_FINALIZADA         => 'Ordem finalizada',
            self::STATUS_ALTERADO          => 'Status alterado',
        };
    }
}
