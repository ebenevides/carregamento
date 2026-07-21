<?php

use App\Domain\Integrations\Guardian\Adapters\GuardianSoapAdapter;
use App\Domain\Integrations\Guardian\DTOs\TicketGuardianDTO;

/**
 * Testa o parsing de tickets Guardian reais (fixture anonimizada em
 * tests/Fixtures/guardian-tickets-exemplo.xml, ver Etapa 2.5 do roadmap) contra
 * GuardianSoapAdapter::mapearTicket(), sem depender de uma conexão SOAP real —
 * o construtor do adapter abre um SoapClient de verdade, então a instância é
 * criada via newInstanceWithoutConstructor() e só os métodos privados de
 * parsing (que não usam $this->client) são exercitados via Reflection.
 */
function carregarTicketsGuardianFixture(): array
{
    $xml = simplexml_load_file(__DIR__.'/../Fixtures/guardian-tickets-exemplo.xml');
    $tickets = [];

    foreach ($xml->Ticket as $ticket) {
        // SoapClient devolve stdClass aninhado; json round-trip é a forma mais
        // simples de reproduzir essa forma a partir do SimpleXMLElement.
        $tickets[(string) $ticket->Codigo] = json_decode(json_encode($ticket));
    }

    return $tickets;
}

function mapearTicketGuardian(string $codigo, mixed $ticketObj): TicketGuardianDTO
{
    $adapter = (new ReflectionClass(GuardianSoapAdapter::class))->newInstanceWithoutConstructor();
    $metodo  = new ReflectionMethod(GuardianSoapAdapter::class, 'mapearTicket');

    return $metodo->invoke($adapter, $codigo, $ticketObj);
}

describe('GuardianSoapAdapter::mapearTicket (dados reais anonimizados)', function () {

    beforeEach(function () {
        $this->tickets = carregarTicketsGuardianFixture();
    });

    it('deriva a tara da Pesagem Inicial (TipoOperacaoCodigo=2) quando Tara raiz vem xsi:nil', function () {
        $dto = mapearTicketGuardian('0201020', $this->tickets['0201020']);

        expect($dto->taraKg())->toBe(18890.0)
            ->and($dto->pesoBrutoKg())->toBe(50940.0)
            ->and($dto->pesoLiquidoKg())->toBe(32050.0);
    });

    it('extrai motorista do pré-cadastro (TipoOperacaoCodigo=1)', function () {
        $dto = mapearTicketGuardian('0201020', $this->tickets['0201020']);

        expect($dto->motorista)->toBe('JOAO DA SILVA TESTE')
            ->and($dto->placa)->toBe('TST1A11');
    });

    it('extrai CamposAdicionais (1-4 ou 1001-1004, blocos duplicados no dado real)', function () {
        $dto = mapearTicketGuardian('0201020', $this->tickets['0201020']);

        expect($dto->quantidadeACarregar)->toBe(32.0)
            ->and($dto->ub)->toBe('UB-2')
            ->and($dto->usuarioProtheus)->toBe('atendente-teste')
            ->and($dto->observacao)->toBe('097488');
    });

    it('mapeia o segundo ticket (ainda aguardando, Estado=10) igualmente', function () {
        $dto = mapearTicketGuardian('0201021', $this->tickets['0201021']);

        expect($dto->status)->toBe('10')
            ->and($dto->taraKg())->toBe(9900.0)
            ->and($dto->pesoBrutoKg())->toBe(22950.0)
            ->and($dto->pesoLiquidoKg())->toBe(13050.0)
            ->and($dto->ub)->toBe('UB-2')
            ->and($dto->motorista)->toBe('MARIA TESTE SANTOS');
    });

});
