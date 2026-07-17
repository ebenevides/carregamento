<?php

use App\Domain\Integrations\Guardian\Adapters\GuardianSoapAdapter;
use App\Domain\Integrations\Guardian\DTOs\FilaGuardianDTO;

/**
 * Testa GuardianSoapAdapter::mapearFila() contra uma resposta real de
 * FilaConsultaVeiculo (fixture anonimizada), sem depender de conexão SOAP —
 * mesma técnica de tests/Unit/GuardianSoapAdapterMapeamentoTest.php.
 */
function mapearFilaGuardian(string $ticket, mixed $retObj): FilaGuardianDTO
{
    $adapter = (new ReflectionClass(GuardianSoapAdapter::class))->newInstanceWithoutConstructor();
    $metodo  = new ReflectionMethod(GuardianSoapAdapter::class, 'mapearFila');

    return $metodo->invoke($adapter, $ticket, $retObj);
}

describe('GuardianSoapAdapter::mapearFila (dados reais anonimizados)', function () {

    beforeEach(function () {
        $xml = simplexml_load_file(__DIR__.'/../Fixtures/guardian-fila-consulta-exemplo.xml');
        $this->ret = json_decode(json_encode($xml));
    });

    it('mapeia posição, estado e fila do veículo', function () {
        $dto = mapearFilaGuardian('0201121', $this->ret);

        expect($dto->sucesso())->toBeTrue()
            ->and($dto->posicao)->toBe(5)
            ->and($dto->estado)->toBe('305060')
            ->and($dto->estadoDescricao)->toBe('Liberado')
            ->and($dto->liberado())->toBeTrue()
            ->and($dto->filaId)->toBe(3)
            ->and($dto->filaCodigo)->toBe('CARREGAMENTO')
            ->and($dto->filaNome)->toBe('PROCESSO DE CARREGAMENTO')
            ->and($dto->placa)->toBe('TST3C33');
    });

});

describe('FilaGuardianDTO', function () {

    it('sucesso() é true só quando erro=0', function () {
        $ok = new FilaGuardianDTO('T1', 0, null, null, null, null, null, null, null, null, null, null, null);
        $falha = new FilaGuardianDTO('T1', 1, null, null, null, null, null, null, null, null, null, null, null);

        expect($ok->sucesso())->toBeTrue()
            ->and($falha->sucesso())->toBeFalse();
    });

    it('liberado() checa a descrição do estado, não o código', function () {
        $liberado = new FilaGuardianDTO('T1', 0, null, null, 1, '305060', 'Liberado', null, null, null, null, null, null);
        $aguardando = new FilaGuardianDTO('T1', 0, null, null, 5, '305050', 'Aguardando', null, null, null, null, null, null);
        $semEstado = new FilaGuardianDTO('T1', 0, null, null, null, null, null, null, null, null, null, null, null);

        expect($liberado->liberado())->toBeTrue()
            ->and($aguardando->liberado())->toBeFalse()
            ->and($semEstado->liberado())->toBeFalse();
    });

});
