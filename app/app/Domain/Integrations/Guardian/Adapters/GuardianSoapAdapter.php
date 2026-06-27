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

    // Parâmetros de autenticação/validação exigidos pelos métodos [INTERFACE]
    private const AUTH_PRODUTO = 'WS G';
    private const AUTH_CODIGO  = '01';

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
            // ExportaTicketParametro é o método [INTERFACE] documentado para consulta por código.
            // Prioridade: 1º ticketCodigo → 2º ticketPlaca → 3º ticketTAG
            $resposta = $this->client->ExportaTicketParametro([
                'ticketCodigo' => $ticket,
                'ticketPlaca'  => '',
                'ticketTAG'    => '',
                'produto'      => self::AUTH_PRODUTO,
                'codigo'       => self::AUTH_CODIGO,
            ]);

            if (($resposta->Erro ?? 0) !== 0) {
                throw new \RuntimeException("Guardian erro {$resposta->Erro}: " . ($resposta->ErroMSG ?? ''));
            }

            $lista = $resposta->ExportaTicketParametroResult ?? null;

            // Lista vazia = ticket não encontrado
            $primeiro = $this->primeiroTicket($lista);
            if ($primeiro === null) {
                throw new \RuntimeException("Ticket {$ticket} não encontrado no Guardian.");
            }

            Log::info('Guardian consultarTicket', ['ticket' => $ticket]);

            return $this->mapearTicket($ticket, $primeiro);
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

    private function primeiroTicket(mixed $lista): mixed
    {
        if ($lista === null) {
            return null;
        }

        // ArrayOfTicket pode chegar como objeto com ->Ticket ou como array direto
        if (is_object($lista) && isset($lista->Ticket)) {
            $t = $lista->Ticket;
            return is_array($t) ? ($t[0] ?? null) : $t;
        }

        if (is_array($lista)) {
            return $lista[0] ?? null;
        }

        // objeto único (apenas 1 ticket)
        return $lista;
    }

    private function mapearTicket(string $ticket, mixed $t): TicketGuardianDTO
    {
        // Ticket struct retorna campos decimal — cast direto, sem parse de string BR
        $pesoBruto  = isset($t->PesoBruto) && (float) $t->PesoBruto > 0 ? (float) $t->PesoBruto : null;
        $tara       = isset($t->Tara) && (float) $t->Tara > 0 ? (float) $t->Tara : null;
        $pesoLiq    = ($pesoBruto !== null && $tara !== null) ? $pesoBruto - $tara : null;

        $placa  = isset($t->PlacaCarreta) && (string) $t->PlacaCarreta !== '' ? (string) $t->PlacaCarreta : null;
        $estado = isset($t->Estado) ? (string) $t->Estado : 'DESCONHECIDO';

        $dataEntrada = null;
        if (isset($t->DataCriacao) && $t->DataCriacao instanceof \DateTime) {
            $dataEntrada = $t->DataCriacao->format('Y-m-d H:i:s');
        } elseif (isset($t->DataCriacao) && (string) $t->DataCriacao !== '') {
            $dataEntrada = (string) $t->DataCriacao;
        }

        return new TicketGuardianDTO(
            ticket:      $ticket,
            status:      $estado,
            placa:       $placa,
            motorista:   null, // struct Ticket não expõe motorista diretamente
            tara:        $tara,
            pesoBruto:   $pesoBruto,
            pesoLiquido: $pesoLiq,
            dataEntrada: $dataEntrada,
            dataSaida:   null,
        );
    }

    private function tratarFalha(SoapFault $e): never
    {
        Log::error('Guardian SOAP falhou', ['codigo' => $e->faultcode, 'mensagem' => $e->faultstring]);
        throw new \RuntimeException("Guardian: {$e->faultstring}");
    }
}
