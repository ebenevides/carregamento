<?php

namespace App\Domain\Carregamento\Services;

use App\Domain\Carregamento\DTOs\DestinoProdutoDTO;
use App\Domain\Carregamento\Enums\TipoDivergencia;
use App\Domain\Carregamento\Models\ProdutoPilhaPonto;

class ResolverDestinoProdutoService
{
    public function resolver(string $produtoCodigo): DestinoProdutoDTO
    {
        $vinculo = ProdutoPilhaPonto::with(['pilhaProduto', 'pontoCarregamento'])
            ->where('produto_codigo', $produtoCodigo)
            ->ativos()
            ->padrao()
            ->first();

        if ($vinculo === null) {
            $vinculo = ProdutoPilhaPonto::with(['pilhaProduto', 'pontoCarregamento'])
                ->where('produto_codigo', $produtoCodigo)
                ->ativos()
                ->first();
        }

        if ($vinculo === null) {
            return new DestinoProdutoDTO(
                resolvido: false,
                pilhaProdutoId: null,
                pontoCarregamentoId: null,
                tipoDivergencia: TipoDivergencia::PILHA_SEM_PRODUTO,
            );
        }

        $pilha = $vinculo->pilhaProduto;
        $ponto = $vinculo->pontoCarregamento;

        if (!$pilha?->ativa || !$ponto?->status?->disponivelParaFila()) {
            return new DestinoProdutoDTO(
                resolvido: false,
                pilhaProdutoId: $pilha?->id,
                pontoCarregamentoId: $ponto?->id,
                tipoDivergencia: $ponto && !$ponto->status->disponivelParaFila()
                    ? TipoDivergencia::PONTO_INDISPONIVEL
                    : TipoDivergencia::PILHA_SEM_PRODUTO,
            );
        }

        return new DestinoProdutoDTO(
            resolvido: true,
            pilhaProdutoId: $pilha->id,
            pontoCarregamentoId: $ponto->id,
            tipoDivergencia: null,
        );
    }
}
