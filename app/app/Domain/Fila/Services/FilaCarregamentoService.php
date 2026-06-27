<?php

namespace App\Domain\Fila\Services;

use App\Domain\Carregamento\Enums\StatusOrdem;
use App\Domain\Carregamento\Models\OrdemCarregamento;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

class FilaCarregamentoService
{
    private const CACHE_TTL = 30;

    public function filaParaPonto(int $pontoCarregamentoId): Collection
    {
        $cacheKey = "fila_ponto_{$pontoCarregamentoId}";

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($pontoCarregamentoId) {
            return OrdemCarregamento::with(['pilhaProduto', 'operador'])
                ->where('ponto_carregamento_id', $pontoCarregamentoId)
                ->whereIn('status', [
                    StatusOrdem::AGUARDANDO_CARREGAMENTO->value,
                    StatusOrdem::EM_CARREGAMENTO->value,
                ])
                ->whereDoesntHave('divergencias', fn ($q) => $q->where('status', 'ABERTA'))
                ->orderByRaw("CASE status WHEN 'EM_CARREGAMENTO' THEN 0 ELSE 1 END")
                ->orderBy('created_at')
                ->get();
        });
    }

    public function invalidarCachePonto(int $pontoCarregamentoId): void
    {
        Cache::forget("fila_ponto_{$pontoCarregamentoId}");
    }

    public function validarAptoParaFila(OrdemCarregamento $ordem): array
    {
        $erros = [];

        if ($ordem->ticket_guardian === null) {
            $erros[] = 'Ordem sem ticket Guardian (RN-001).';
        }

        if ($ordem->tara === null) {
            $erros[] = 'Ordem sem tara registrada (RN-002).';
        }

        if ($ordem->produto_codigo === null) {
            $erros[] = 'Ordem sem produto definido.';
        }

        if ($ordem->pilha_produto_id === null || $ordem->ponto_carregamento_id === null) {
            $erros[] = 'Produto sem pilha ou ponto de carregamento configurado (RN-003).';
        }

        if ($ordem->temDivergenciaAberta()) {
            $erros[] = 'Ordem possui divergência aberta (RN-005).';
        }

        return $erros;
    }

    public function entrarNaFila(OrdemCarregamento $ordem): bool
    {
        $erros = $this->validarAptoParaFila($ordem);

        return empty($erros);
    }
}
