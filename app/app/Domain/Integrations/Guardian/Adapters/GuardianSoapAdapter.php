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

    public function consultarTicketsPorPeriodo(\DateTimeInterface $inicio, \DateTimeInterface $fim): array
    {
        try {
            $resposta = $this->client->ConsultaTicketsPorPeriodo([
                'dataInicial' => $inicio->format('Y-m-d\TH:i:s'),
                'dataFinal'   => $fim->format('Y-m-d\TH:i:s'),
                'produto'     => self::AUTH_PRODUTO,
                'codigo'      => self::AUTH_CODIGO,
            ]);

            if (($resposta->Erro ?? 0) !== 0) {
                throw new \RuntimeException("Guardian erro {$resposta->Erro}: " . ($resposta->ErroMSG ?? ''));
            }

            $lista = $resposta->ConsultaTicketsPorPeriodoResult ?? null;

            Log::info('Guardian consultarTicketsPorPeriodo', [
                'inicio' => $inicio->format('Y-m-d H:i'),
                'fim'    => $fim->format('Y-m-d H:i'),
            ]);

            return array_map(
                fn ($t) => $this->mapearTicket((string) ($t->Codigo ?? ''), $t),
                $this->todosTickets($lista)
            );
        } catch (SoapFault $e) {
            $this->tratarFalha($e);
        }
    }

    private function primeiroTicket(mixed $lista): mixed
    {
        $todos = $this->todosTickets($lista);

        return $todos[0] ?? null;
    }

    /** Normaliza ArrayOfTicket (objeto com ->Ticket, array direto ou item único) em lista plana. */
    private function todosTickets(mixed $lista): array
    {
        if ($lista === null) {
            return [];
        }

        if (is_object($lista) && isset($lista->Ticket)) {
            $t = $lista->Ticket;
            return is_array($t) ? $t : [$t];
        }

        if (is_array($lista)) {
            return $lista;
        }

        // objeto único (apenas 1 ticket)
        return [$lista];
    }

    private function mapearTicket(string $ticket, mixed $t): TicketGuardianDTO
    {
        $placa  = isset($t->PlacaCarreta) && (string) $t->PlacaCarreta !== '' ? trim((string) $t->PlacaCarreta) : null;
        $estado = isset($t->Estado) ? (string) $t->Estado : 'DESCONHECIDO';

        // Extrair pesos das operações:
        //   TipoOperacaoCodigo=2 (Pesagem Inicial) → Peso = tara
        //   TipoOperacaoCodigo=3 (Pesagem Final)   → Peso = bruto, PesoLiqObtido = líquido
        $tara      = null;
        $pesoBruto = null;
        $pesoLiq   = null;
        $motorista = null;

        $ops = $t->OperacaoTicket ?? null;
        $opArr = [];
        if ($ops !== null) {
            if (isset($ops->OperacaoTicket)) {
                $opArr = is_array($ops->OperacaoTicket) ? $ops->OperacaoTicket : [$ops->OperacaoTicket];
            } elseif (is_array($ops)) {
                $opArr = $ops;
            }
        }

        foreach ($opArr as $op) {
            $tipo = (int) ($op->TipoOperacaoCodigo ?? 0);
            $peso = isset($op->Peso) && (float) $op->Peso > 0 ? (float) $op->Peso : null;

            if ($tipo === 2 && $peso !== null) {
                $tara = $peso; // Pesagem Inicial = tara
            }

            if ($tipo === 3) {
                if ($peso !== null) {
                    $pesoBruto = $peso; // Pesagem Final = bruto
                }
                if (isset($op->PesoLiqObtido) && (float) $op->PesoLiqObtido > 0) {
                    $pesoLiq = (float) $op->PesoLiqObtido;
                }
            }

            // Motorista vem do pré-cadastro (op tipo 1); Motorista é objeto MotoristaIntegracao (campo Nome)
            if ($tipo === 1 && $motorista === null) {
                $nome = isset($op->Motorista->Nome) && (string) $op->Motorista->Nome !== ''
                    ? trim((string) $op->Motorista->Nome)
                    : null;
                $motorista = $nome;
            }
        }

        // Fallback: Ticket.PesoBruto raiz (última pesagem registrada)
        if ($pesoBruto === null && isset($t->PesoBruto) && (float) $t->PesoBruto > 0) {
            $pesoBruto = (float) $t->PesoBruto;
        }

        $dataEntrada = null;
        if (isset($t->DataCriacao) && (string) $t->DataCriacao !== '') {
            $dataEntrada = (string) $t->DataCriacao;
        }

        // DataPesagem = horário da última pesagem registrada; usado como proxy de saída
        // do pátio (Guardian não expõe um campo explícito de "data de saída").
        $dataSaida = null;
        if (isset($t->DataPesagem) && (string) $t->DataPesagem !== '') {
            $dataSaida = (string) $t->DataPesagem;
        }

        [$pesoDoc, $unidade, $atendente, $pedido] = $this->extrairCamposAdicionais($t);

        $tempoPermanencia = isset($t->TempoPermanencia) && $t->TempoPermanencia !== null
            ? (int) $t->TempoPermanencia
            : null;

        return new TicketGuardianDTO(
            ticket:           $ticket,
            status:           $estado,
            placa:            $placa,
            motorista:        $motorista,
            tara:             $tara,
            pesoBruto:        $pesoBruto,
            pesoLiquido:      $pesoLiq,
            dataEntrada:      $dataEntrada,
            dataSaida:        $dataSaida,
            pesoDoc:          $pesoDoc,
            unidade:          $unidade,
            atendente:        $atendente,
            pedido:           $pedido,
            tempoPermanencia: $tempoPermanencia,
        );
    }

    /**
     * Campos adicionais configuráveis no Guardian (Ticket.CamposAdicionais, Numero 1-4).
     * Confirmado em dados reais de produção: 1=peso doc, 2=unidade/praça, 3=atendente, 4=pedido/nota.
     * Tickets de portaria (placa ENT0000/SAI0000) só têm 2 slots (flags) — ficam null aqui.
     *
     * @return array{0: ?float, 1: ?string, 2: ?string, 3: ?string}
     */
    private function extrairCamposAdicionais(mixed $t): array
    {
        $pesoDoc = $unidade = $atendente = $pedido = null;

        $campos = $t->CamposAdicionais->CampoAdicionalTicket ?? null;
        if ($campos !== null) {
            foreach (is_array($campos) ? $campos : [$campos] as $campo) {
                $numero = (int) ($campo->Numero ?? 0);
                $valor  = isset($campo->Valor) ? trim((string) $campo->Valor) : null;

                match ($numero) {
                    1       => $pesoDoc = ($valor !== null && is_numeric($valor)) ? (float) $valor : null,
                    2       => $unidade = $valor ?: null,
                    3       => $atendente = $valor ?: null,
                    4       => $pedido = $valor ?: null,
                    default => null,
                };
            }
        }

        return [$pesoDoc, $unidade, $atendente, $pedido];
    }

    private function tratarFalha(SoapFault $e): never
    {
        Log::error('Guardian SOAP falhou', ['codigo' => $e->faultcode, 'mensagem' => $e->faultstring]);
        throw new \RuntimeException("Guardian: {$e->faultstring}");
    }
}
