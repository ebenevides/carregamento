<?php

use App\Domain\Carregamento\Actions\CriarOrdemAction;
use App\Domain\Carregamento\DTOs\CriarOrdemDTO;
use App\Domain\Carregamento\Models\PilhaProduto;
use App\Domain\Carregamento\Models\PontoCarregamento;
use App\Domain\Carregamento\Models\ProdutoPilhaPonto;
use App\Domain\Carregamento\Services\ResolverDestinoProdutoService;

/**
 * Cobre o caso de negócio: mesmo produto_codigo cadastrado em pilhas de UBs diferentes
 * (ex.: BRITA 01 FINA existe em UB1 e UB2, cada uma com sua pilha/ponto). A resolução
 * precisa usar a UB (vinda do CamposAdicionais Numero 2/1002 do ticket Guardian) pra não
 * escolher a pilha errada.
 */
function criarPontoPilhaComUb(string $produtoCodigo, string $ub, bool $padrao = true): array
{
    $ponto = PontoCarregamento::factory()->create(['unidade_britagem' => $ub]);
    $pilha = PilhaProduto::factory()->create(['produto_codigo' => $produtoCodigo]);
    ProdutoPilhaPonto::create([
        'produto_codigo'        => $produtoCodigo,
        'pilha_produto_id'      => $pilha->id,
        'ponto_carregamento_id' => $ponto->id,
        'padrao'                => $padrao,
        'ativo'                 => true,
    ]);

    return [$ponto, $pilha];
}

describe('ResolverDestinoProdutoService — desambiguação por UB', function () {

    it('resolve pra pilha da UB pedida quando produto existe em duas UBs', function () {
        [$pontoUb1, $pilhaUb1] = criarPontoPilhaComUb('000001', 'UB1');
        [$pontoUb2, $pilhaUb2] = criarPontoPilhaComUb('000001', 'UB2');

        $service = app(ResolverDestinoProdutoService::class);

        $destinoUb1 = $service->resolver('000001', 'UB1');
        expect($destinoUb1->resolvido)->toBeTrue()
            ->and($destinoUb1->pilhaProdutoId)->toBe($pilhaUb1->id)
            ->and($destinoUb1->pontoCarregamentoId)->toBe($pontoUb1->id);

        $destinoUb2 = $service->resolver('000001', 'UB2');
        expect($destinoUb2->resolvido)->toBeTrue()
            ->and($destinoUb2->pilhaProdutoId)->toBe($pilhaUb2->id)
            ->and($destinoUb2->pontoCarregamentoId)->toBe($pontoUb2->id);
    });

    it('cai pro comportamento antigo (ignora UB) quando produto só existe numa UB', function () {
        [$ponto, $pilha] = criarPontoPilhaComUb('000006', 'UB1');

        $destino = app(ResolverDestinoProdutoService::class)->resolver('000006', 'UB2');

        expect($destino->resolvido)->toBeTrue()
            ->and($destino->pilhaProdutoId)->toBe($pilha->id)
            ->and($destino->pontoCarregamentoId)->toBe($ponto->id);
    });

    it('CriarOrdemAction usa a UB do ticket Guardian (mock) pra escolher a pilha certa', function () {
        [$pontoUb1, $pilhaUb1] = criarPontoPilhaComUb('000001', 'UB1');
        criarPontoPilhaComUb('000001', 'UB2');

        // Ticket 0000001 do GuardianMockAdapter está marcado como UB-1
        $dto = new CriarOrdemDTO(
            produtoCodigo: '000001',
            quantidadePrevista: 30,
            placaVeiculo: 'ABC1D23',
            ticketGuardian: '0000001',
        );

        $ordem = app(CriarOrdemAction::class)->execute($dto);

        expect($ordem->pilha_produto_id)->toBe($pilhaUb1->id)
            ->and($ordem->ponto_carregamento_id)->toBe($pontoUb1->id);
    });

});
