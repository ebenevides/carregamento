<?php

namespace App\Domain\Integrations\Guardian\Adapters;

use App\Domain\Integrations\Guardian\DTOs\TicketGuardianDTO;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use SoapClient;
use SoapFault;

class GuardianSoapAdapter implements GuardianAdapterInterface
{
    private SoapClient $client;
    private const CIRCUIT_KEY = 'guardian_circuit_open';
    private const CIRCUIT_TTL = 300; // 5 min offline antes de tentar novamente

    public function __construct()
    {
        if (Cache::get(self::CIRCUIT_KEY)) {
            throw new \RuntimeException('Guardian indisponível (circuit breaker ativo). Tente novamente em instantes.');
        }

        try {
            $this->client = new SoapClient(
                config('integrations.guardian.wsdl'),
                [
                    'trace'              => false,
                    'exceptions'         => true,
                    'connection_timeout' => config('integrations.guardian.timeout', 10),
                    'cache_wsdl'         => WSDL_CACHE_DISK,
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
        // Nomes dos métodos SOAP a confirmar via $this->client->__getFunctions()
        // Substituir 'ConsultarTicket' pelo método real retornado pelo WSDL
        try {
            $resultado = $this->client->__soapCall('ConsultarTicket', [['Ticket' => $ticket]]);

            Log::info('Guardian consultarTicket', ['ticket' => $ticket]);

            return $this->mapearTicket($ticket, $resultado);
        } catch (SoapFault $e) {
            $this->tratarFalha($e);
        }
    }

    public function consultarTara(string $ticket): float
    {
        try {
            $resultado = $this->client->__soapCall('ConsultarTara', [['Ticket' => $ticket]]);

            // Peso em kg — Guardian confirmado retornar kg
            return (float) ($resultado->Tara ?? $resultado->tara ?? 0);
        } catch (SoapFault $e) {
            $this->tratarFalha($e);
        }
    }

    public function consultarPesoFinal(string $ticket): float
    {
        try {
            $resultado = $this->client->__soapCall('ConsultarPesoFinal', [['Ticket' => $ticket]]);

            // Peso em kg — Guardian confirmado retornar kg
            return (float) ($resultado->PesoBruto ?? $resultado->peso_bruto ?? 0);
        } catch (SoapFault $e) {
            $this->tratarFalha($e);
        }
    }

    public function ticketExiste(string $ticket): bool
    {
        try {
            $this->consultarTicket($ticket);
            return true;
        } catch (\Exception) {
            return false;
        }
    }

    private function mapearTicket(string $ticket, mixed $resultado): TicketGuardianDTO
    {
        // Ajustar nomes dos campos conforme XML real do Guardian
        $r = (array) $resultado;

        return new TicketGuardianDTO(
            ticket: $ticket,
            status: (string) ($r['Status'] ?? $r['status'] ?? 'DESCONHECIDO'),
            placa: (string) ($r['Placa'] ?? $r['placa'] ?? null) ?: null,
            motorista: (string) ($r['Motorista'] ?? $r['motorista'] ?? null) ?: null,
            tara: isset($r['Tara']) ? (float) $r['Tara'] : null,
            pesoBruto: isset($r['PesoBruto']) ? (float) $r['PesoBruto'] : null,
            pesoLiquido: isset($r['PesoLiquido']) ? (float) $r['PesoLiquido'] : null,
            dataEntrada: (string) ($r['DataEntrada'] ?? '') ?: null,
            dataSaida: (string) ($r['DataSaida'] ?? '') ?: null,
        );
    }

    private function tratarFalha(SoapFault $e): never
    {
        Log::error('Guardian SOAP falhou', ['codigo' => $e->faultcode, 'mensagem' => $e->faultstring]);
        throw new \RuntimeException("Guardian: {$e->faultstring}");
    }
}
