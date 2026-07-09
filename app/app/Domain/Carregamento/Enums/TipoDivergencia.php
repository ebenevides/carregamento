<?php

namespace App\Domain\Carregamento\Enums;

enum TipoDivergencia: string
{
    case PRODUTO_DIVERGENTE        = 'PRODUTO_DIVERGENTE';
    case QUANTIDADE_DIVERGENTE     = 'QUANTIDADE_DIVERGENTE';
    case VEICULO_DIVERGENTE        = 'VEICULO_DIVERGENTE';
    case MOTORISTA_DIVERGENTE      = 'MOTORISTA_DIVERGENTE';
    case TICKET_INVALIDO           = 'TICKET_INVALIDO';
    case TARA_INVALIDA             = 'TARA_INVALIDA';
    case PESO_FORA_TOLERANCIA      = 'PESO_FORA_TOLERANCIA';
    case PILHA_SEM_PRODUTO         = 'PILHA_SEM_PRODUTO';
    case PONTO_INDISPONIVEL        = 'PONTO_INDISPONIVEL';
    case REJEITADO_PELO_OPERADOR  = 'REJEITADO_PELO_OPERADOR';
    case PEDIDO_INVALIDO           = 'PEDIDO_INVALIDO';
    case OUTRO                     = 'OUTRO';

    public function label(): string
    {
        return match($this) {
            self::PRODUTO_DIVERGENTE    => 'Produto divergente',
            self::QUANTIDADE_DIVERGENTE => 'Quantidade divergente',
            self::VEICULO_DIVERGENTE    => 'Veículo divergente',
            self::MOTORISTA_DIVERGENTE  => 'Motorista divergente',
            self::TICKET_INVALIDO       => 'Ticket inválido',
            self::TARA_INVALIDA         => 'Tara inválida',
            self::PESO_FORA_TOLERANCIA  => 'Peso fora da tolerância',
            self::PILHA_SEM_PRODUTO     => 'Pilha sem produto configurado',
            self::PONTO_INDISPONIVEL    => 'Ponto de carregamento indisponível',
            self::REJEITADO_PELO_OPERADOR => 'Rejeitado pelo operador',
            self::PEDIDO_INVALIDO       => 'Pedido inválido',
            self::OUTRO                 => 'Outro',
        };
    }
}
