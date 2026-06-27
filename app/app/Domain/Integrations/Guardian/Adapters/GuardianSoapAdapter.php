<?php

namespace App\Domain\Integrations\Guardian\Adapters;

use App\Domain\Integrations\Guardian\DTOs\TicketGuardianDTO;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use SoapClient;
use SoapFault;

class GuardianSoapAdapter implements GuardianAdapterInterface
{
    private SoapClient $client;
    private const CIRCUIT_KEY = 'guardian_circuit_open';
    private const CIRCUIT_TTL = 300;

    public function __construct()
    {
        if (Cache::get(self::CIRCUIT_KEY)) {
            throw new \RuntimeException('Guardian indisponível (circuit breaker ativo). Tente novamente em instantes.');
        }

        try {
            $wsdl = config('integrations.guardian.wsdl');

            // WSDL interno aponta <soap:address location> para porta 80; service está na porta do WSDL.
            $location = preg_replace('/\?.*$/', '', $wsdl);

            $this->client = new SoapClient(
                $wsdl,
                [
                    'location'           => $location,
                    'trace'              => false,
                    'exceptions'         => true,
                    'connection_timeout' => config('integrations.guardian.timeout', 10),
                    'cache_wsdl'         => WSDL_CACHE_NONE,
                ]
            );
        } catch (SoapFault $e) {
            Cache::put(self::CIRCUIT_KEY, true, self::CIRCUIT_TTL);
            Log::error('Guardian SOAP init falhou', ['erro' => $e->getMessage()]);
            throw new \RuntimeException("Falha ao conectar no Guardian: {$e->getMessage()}");
        }
    }

    public function consultarTicket(string $ticket): TicketGuardianDTO
    {
        try {
            $resposta = $this->client->TicketCompletoObtem([
                'voDadoTicket' => [
                    'TicketCodigo' => $ticket,
                    'PlacaCarreta' => '',
                    'Identificador' => '',
                ],
            ]);

            $vo = $resposta->TicketCompletoObtemResult ?? null;

            Log::info('Guardian consultarTicket', ['ticket' => $ticket]);

            return $this->mapearVO($ticket, $vo);
        } catch (SoapFault $e) {
            $this->tratarFalha($e);
        }
    }

    public function consultarTara(string $ticket): float
    {
        $dto = $this->consultarTicket($ticket);
        $tara = $dto->taraKg();

        if ($tara === null || $tara <= 0) {
            throw new \RuntimeException("Tara não disponível para ticket {$ticket}.");
        }

        return $tara;
    }

    public function consultarPesoFinal(string $ticket): float
    {
        $dto = $this->consultarTicket($ticket);
        $bruto = $dto->pesoBrutoKg();

        if ($bruto === null || $bruto <= 0) {
            throw new \RuntimeException("Peso bruto não disponível para ticket {$ticket}.");
        }

        return $bruto;
    }

    public function ticketExiste(string $ticket): bool
    {
        try {
            $this->consultarTicket($ticket);
            return true;
        } catch (\Throwable) {
            return false;
        }
    }

    private function mapearVO(string $ticket, mixed $vo): TicketGuardianDTO
    {
        $get = fn ($field) => isset($vo->$field) && (string) $vo->$field !== '' ? (string) $vo->$field : null;

        // Ticket não encontrado = todos os campos retornam vazios
        $codigo = $get('Codigo') ?? $get('PlacaCarreta') ?? $get('DataCriacao');
        if ($codigo === null) {
            throw new \RuntimeException("Ticket {$ticket} não encontrado no Guardian.");
        }

        $tara    = $this->parsePeso($get('Tara'));
        $bruto   = $this->parsePeso($get('PesoBruto'));
        $liquido = $this->parsePeso($get('PesoLiquido'));

        // Motorista em CDCColeta.MotoristaEmail, transportadora como fallback
        $motorista = null;
        if (isset($vo->CDCColeta->MotoristaEmail) && (string) $vo->CDCColeta->MotoristaEmail !== '') {
            $motorista = (string) $vo->CDCColeta->MotoristaEmail;
        } elseif (isset($vo->Transportadora->RazaoSocial) && (string) $vo->Transportadora->RazaoSocial !== '') {
            $motorista = (string) $vo->Transportadora->RazaoSocial;
        }

        return new TicketGuardianDTO(
            ticket:      $ticket,
            status:      $get('Estado') ?? $get('EstadoAguardando') ?? 'DESCONHECIDO',
            placa:       $get('PlacaCarreta'),
            motorista:   $motorista,
            tara:        $tara,
            pesoBruto:   $bruto,
            pesoLiquido: $liquido,
            dataEntrada: $get('DataCriacao'),
            dataSaida:   null,
        );
    }

    /** Guardian pode retornar pesos com vírgula (BR) ou ponto decimal. */
    private function parsePeso(?string $valor): ?float
    {
        if ($valor === null || $valor === '' || $valor === '0') {
            return null;
        }

        // "47.230,500" → "47230.500" ou "47230.5" → 47230.5
        $normalizado = str_replace(['.', ','], ['', '.'], $valor);

        $float = (float) $normalizado;

        return $float > 0 ? $float : null;
    }

    private function tratarFalha(SoapFault $e): never
    {
        Log::error('Guardian SOAP falhou', ['codigo' => $e->faultcode, 'mensagem' => $e->faultstring]);
        throw new \RuntimeException("Guardian: {$e->faultstring}");
    }
}
