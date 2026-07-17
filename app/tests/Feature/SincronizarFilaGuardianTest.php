<?php

use App\Domain\Carregamento\Enums\StatusOrdem;
use App\Domain\Carregamento\Models\OrdemCarregamento;
use App\Domain\Carregamento\Models\PilhaProduto;
use App\Domain\Carregamento\Models\PontoCarregamento;
use App\Domain\Carregamento\Models\ProdutoPilhaPonto;
use App\Domain\Integrations\Guardian\Services\GuardianService;

// GuardianMockAdapter::consultarFila() só devolve liberado=true pro ticket 0000001
// (ver GuardianMockAdapter) — usado como "ticket liberado" nos testes abaixo.

function pontoComPilhaFila(string $produto = 'BRITA1'): array
{
    $ponto = PontoCarregamento::factory()->create();
    $pilha = PilhaProduto::factory()->create(['produto_codigo' => $produto]);
    ProdutoPilhaPonto::create([
        'produto_codigo'        => $produto,
        'pilha_produto_id'      => $pilha->id,
        'ponto_carregamento_id' => $ponto->id,
        'padrao'                => true,
        'ativo'                 => true,
    ]);

    return [$ponto, $pilha];
}

describe('GuardianService::sincronizarFila', function () {

    it('entra em AGUARDANDO_CARREGAMENTO quando Guardian libera o veículo na fila', function () {
        [$ponto, $pilha] = pontoComPilhaFila();

        $ordem = OrdemCarregamento::factory()->create([
            'ponto_carregamento_id' => $ponto->id,
            'pilha_produto_id'      => $pilha->id,
            'produto_codigo'        => 'BRITA1',
            'ticket_guardian'       => '0000001',
            'tara'                  => 15.2,
            'status'                => StatusOrdem::TARA_REALIZADA,
        ]);

        $resultado = app(GuardianService::class)->sincronizarFila($ordem);

        expect($resultado)->toBeTrue()
            ->and($ordem->fresh()->status)->toBe(StatusOrdem::AGUARDANDO_CARREGAMENTO);
    });

    it('não muda status quando Guardian ainda não liberou', function () {
        [$ponto, $pilha] = pontoComPilhaFila();

        $ordem = OrdemCarregamento::factory()->create([
            'ponto_carregamento_id' => $ponto->id,
            'pilha_produto_id'      => $pilha->id,
            'produto_codigo'        => 'BRITA1',
            'ticket_guardian'       => '0000002', // mock: não liberado
            'tara'                  => 14.8,
            'status'                => StatusOrdem::TARA_REALIZADA,
        ]);

        $resultado = app(GuardianService::class)->sincronizarFila($ordem);

        expect($resultado)->toBeFalse()
            ->and($ordem->fresh()->status)->toBe(StatusOrdem::TARA_REALIZADA);
    });

    it('ignora ordens sem ticket_guardian', function () {
        [$ponto, $pilha] = pontoComPilhaFila();

        $ordem = OrdemCarregamento::factory()->create([
            'ponto_carregamento_id' => $ponto->id,
            'pilha_produto_id'      => $pilha->id,
            'produto_codigo'        => 'BRITA1',
            'ticket_guardian'       => null,
            'tara'                  => 15.0,
            'status'                => StatusOrdem::TARA_REALIZADA,
        ]);

        expect(app(GuardianService::class)->sincronizarFila($ordem))->toBeFalse();
    });

    it('ignora ordens que não estão em TARA_REALIZADA', function () {
        [$ponto, $pilha] = pontoComPilhaFila();

        $ordem = OrdemCarregamento::factory()->create([
            'ponto_carregamento_id' => $ponto->id,
            'pilha_produto_id'      => $pilha->id,
            'produto_codigo'        => 'BRITA1',
            'ticket_guardian'       => '0000001',
            'tara'                  => 15.2,
            'status'                => StatusOrdem::CRIADO,
        ]);

        expect(app(GuardianService::class)->sincronizarFila($ordem))->toBeFalse();
    });

    it('sincronizarTodasFilas() processa só ordens TARA_REALIZADA com ticket', function () {
        [$ponto, $pilha] = pontoComPilhaFila();

        OrdemCarregamento::factory()->create([
            'ponto_carregamento_id' => $ponto->id,
            'pilha_produto_id'      => $pilha->id,
            'produto_codigo'        => 'BRITA1',
            'ticket_guardian'       => '0000001',
            'tara'                  => 15.2,
            'status'                => StatusOrdem::TARA_REALIZADA,
        ]);
        OrdemCarregamento::factory()->create([
            'ponto_carregamento_id' => $ponto->id,
            'pilha_produto_id'      => $pilha->id,
            'produto_codigo'        => 'BRITA1',
            'ticket_guardian'       => '0000002',
            'tara'                  => 14.8,
            'status'                => StatusOrdem::TARA_REALIZADA,
        ]);

        $liberadas = app(GuardianService::class)->sincronizarTodasFilas();

        expect($liberadas)->toBe(1);
    });

});
