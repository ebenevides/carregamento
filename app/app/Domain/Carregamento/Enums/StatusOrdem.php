<?php

namespace App\Domain\Carregamento\Enums;

enum StatusOrdem: string
{
    case CRIADO                   = 'CRIADO';
    case TARA_REALIZADA           = 'TARA_REALIZADA';
    case AGUARDANDO_CARREGAMENTO  = 'AGUARDANDO_CARREGAMENTO';
    case EM_CARREGAMENTO          = 'EM_CARREGAMENTO';
    case CARREGAMENTO_CONCLUIDO   = 'CARREGAMENTO_CONCLUIDO';
    case AGUARDANDO_PESAGEM_FINAL = 'AGUARDANDO_PESAGEM_FINAL';
    case PESAGEM_FINAL_REALIZADA  = 'PESAGEM_FINAL_REALIZADA';
    case VALIDADO                 = 'VALIDADO';
    case DIVERGENCIA              = 'DIVERGENCIA';
    case CANCELADO                = 'CANCELADO';
    case FINALIZADO               = 'FINALIZADO';

    public function label(): string
    {
        return match($this) {
            self::CRIADO                   => 'Criado',
            self::TARA_REALIZADA           => 'Tara Realizada',
            self::AGUARDANDO_CARREGAMENTO  => 'Aguardando Carregamento',
            self::EM_CARREGAMENTO          => 'Em Carregamento',
            self::CARREGAMENTO_CONCLUIDO   => 'Carregamento Concluído',
            self::AGUARDANDO_PESAGEM_FINAL => 'Aguardando Pesagem Final',
            self::PESAGEM_FINAL_REALIZADA  => 'Pesagem Final Realizada',
            self::VALIDADO                 => 'Validado',
            self::DIVERGENCIA              => 'Divergência',
            self::CANCELADO                => 'Cancelado',
            self::FINALIZADO               => 'Finalizado',
        };
    }

    /** Retorna transições válidas a partir deste status */
    public function transicoesPermitidas(): array
    {
        return match($this) {
            self::CRIADO                   => [self::TARA_REALIZADA, self::DIVERGENCIA, self::CANCELADO],
            self::TARA_REALIZADA           => [self::AGUARDANDO_CARREGAMENTO, self::DIVERGENCIA, self::CANCELADO],
            self::AGUARDANDO_CARREGAMENTO  => [self::EM_CARREGAMENTO, self::DIVERGENCIA, self::CANCELADO],
            self::EM_CARREGAMENTO          => [self::CARREGAMENTO_CONCLUIDO, self::DIVERGENCIA],
            self::CARREGAMENTO_CONCLUIDO   => [self::AGUARDANDO_PESAGEM_FINAL],
            self::AGUARDANDO_PESAGEM_FINAL => [self::PESAGEM_FINAL_REALIZADA, self::DIVERGENCIA],
            self::PESAGEM_FINAL_REALIZADA  => [self::VALIDADO, self::DIVERGENCIA],
            self::VALIDADO                 => [self::FINALIZADO],
            self::DIVERGENCIA              => [self::AGUARDANDO_CARREGAMENTO, self::CANCELADO],
            self::CANCELADO                => [],
            self::FINALIZADO               => [],
        };
    }

    public function podeTransicionarPara(self $novo): bool
    {
        return in_array($novo, $this->transicoesPermitidas(), strict: true);
    }

    public function estaAtivo(): bool
    {
        return !in_array($this, [self::CANCELADO, self::FINALIZADO], strict: true);
    }

    public function estaEmFila(): bool
    {
        return in_array($this, [self::AGUARDANDO_CARREGAMENTO, self::EM_CARREGAMENTO], strict: true);
    }
}
