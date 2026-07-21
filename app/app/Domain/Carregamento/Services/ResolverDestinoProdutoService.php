<?php

namespace App\Domain\Carregamento\Services;

use App\Domain\Carregamento\DTOs\DestinoProdutoDTO;
use App\Domain\Carregamento\Enums\TipoDivergencia;
use App\Domain\Carregamento\Models\ProdutoPilhaPonto;

class ResolverDestinoProdutoService
{
    /**
     * Resolve pilha/ponto pro produto. Quando $ub é informado (ex.: "UB1", vindo do ticket
     * Guardian), tenta primeiro achar vínculo cujo ponto pertença àquela unidade de britagem —
     * necessário pra desambiguar produtos que existem em mais de uma UB (ex.: BRITA 01 FINA em
     * UB1 e UB2, cada uma com sua pilha). Se não achar nada pra UB específica, cai pro
     * comportamento antigo (ignora UB) pra não gerar divergência em produtos exclusivos de uma UB.
     */
    public function resolver(string $produtoCodigo, ?string $ub = null): DestinoProdutoDTO
    {
        $vinculo = ($ub !== null ? $this->buscar($produtoCodigo, $ub, apenasPadrao: true) : null)
            ?? ($ub !== null ? $this->buscar($produtoCodigo, $ub, apenasPadrao: false) : null)
            ?? $this->buscar($produtoCodigo, null, apenasPadrao: true)
            ?? $this->buscar($produtoCodigo, null, apenasPadrao: false);

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

    private function buscar(string $produtoCodigo, ?string $ub, bool $apenasPadrao): ?ProdutoPilhaPonto
    {
        $query = ProdutoPilhaPonto::with(['pilhaProduto', 'pontoCarregamento'])
            ->where('produto_codigo', $produtoCodigo)
            ->ativos();

        if ($ub !== null) {
            $query->whereHas('pontoCarregamento', fn ($q) => $q->where('unidade_britagem', $ub));
        }

        if ($apenasPadrao) {
            $query->padrao();
        }

        return $query->first();
    }
}
