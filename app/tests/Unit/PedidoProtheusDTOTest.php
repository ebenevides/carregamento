<?php

use App\Domain\Integrations\Protheus\DTOs\PedidoProtheusDTO;

/**
 * Testa PedidoProtheusDTO::fromArray() contra uma resposta real de produção
 * do Protheus (GET /rest/api/v1/faturamento/pedidos/{filial}/{numero}),
 * fixture anonimizada. Contrato real confirmado em 2026-07-19 — veiculo/
 * motorista vêm aninhados dentro de cada item, não no cabeçalho do pedido.
 */
function carregarPedidoProtheusFixture(): array
{
    return json_decode(
        file_get_contents(__DIR__.'/../Fixtures/protheus-pedido-exemplo.json'),
        true
    );
}

describe('PedidoProtheusDTO::fromArray (dado real anonimizado)', function () {

    it('mapeia o cabeçalho do pedido e o cliente', function () {
        $dto = PedidoProtheusDTO::fromArray(carregarPedidoProtheusFixture());

        expect($dto->filial)->toBe('00')
            ->and($dto->numero)->toBe('778975')
            ->and($dto->condicaoPagamento)->toBe('I70')
            ->and($dto->transportadoraCodigo)->toBe('012115')
            ->and($dto->transportadoraNome)->toBe('TRANSPORTADORA TESTE LTDA')
            ->and($dto->cliente->codigo)->toBe('017687')
            ->and($dto->cliente->nome)->toBe('CLIENTE TESTE CONSORCIO LTDA')
            ->and($dto->cliente->cidade)->toBe('CUIABA');
    });

    it('mapeia veículo e motorista aninhados no item, não no cabeçalho', function () {
        $dto = PedidoProtheusDTO::fromArray(carregarPedidoProtheusFixture());
        $item = $dto->primeiroItem();

        expect($item)->not->toBeNull()
            ->and($item->item)->toBe('01')
            ->and($item->produto)->toBe('BRITA (PEDRA DE MAO)')
            ->and($item->quantidade)->toBe(29.1)
            ->and($item->veiculo?->placa)->toBe('TST3G18')
            ->and($item->motorista?->nome)->toBe('MOTORISTA TESTE SILVA')
            ->and($item->motorista?->cpf)->toBe('00000000000');
    });

    it('busca item pelo número via item()', function () {
        $dto = PedidoProtheusDTO::fromArray(carregarPedidoProtheusFixture());

        expect($dto->item('01'))->not->toBeNull()
            ->and($dto->item('99'))->toBeNull();
    });

    it('normaliza campos string vazios do Protheus para null (chassi, renavan, RG)', function () {
        $dto = PedidoProtheusDTO::fromArray(carregarPedidoProtheusFixture());
        $veiculo = $dto->primeiroItem()->veiculo;
        $motorista = $dto->primeiroItem()->motorista;

        expect($veiculo->chassi)->toBeNull()
            ->and($veiculo->renavan)->toBeNull()
            ->and($motorista->rg)->toBeNull()
            ->and($motorista->email)->toBeNull();
    });

});
