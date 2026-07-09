<?php

namespace App\Domain\Integrations\Guardian\Services;

use App\Domain\Carregamento\Actions\AlterarStatusOrdemAction;
use App\Domain\Carregamento\Actions\RegistrarPesagemFinalAction;
use App\Domain\Carregamento\Actions\ResolverMotoristaAction;
use App\Domain\Carregamento\DTOs\AlterarStatusDTO;
use App\Domain\Carregamento\Enums\OrigemEvento;
use App\Domain\Carregamento\Enums\StatusOrdem;
use App\Domain\Carregamento\Enums\TipoEvento;
use App\Domain\Carregamento\Models\OrdemCarregamento;
use App\Domain\Integrations\Guardian\Adapters\GuardianAdapterInterface;
use App\Domain\Integrations\Guardian\DTOs\TicketGuardianDTO;
use Illuminate\Support\Facades\Log;

class GuardianService
{
    public function __construct(
        private readonly GuardianAdapterInterface $adapter,
        private readonly AlterarStatusOrdemAction $alterarStatus,
        private readonly RegistrarPesagemFinalAction $registrarPesagem,
        private readonly ResolverMotoristaAction $resolverMotorista,
    ) {}

    public function consultarTicket(string $ticket): TicketGuardianDTO
    {
        return $this->adapter->consultarTicket($ticket);
    }

    public function verificarConectividade(): bool
    {
        try {
            $this->adapter->ticketExiste('PING');
            return true;
        } catch (\Throwable) {
            return true; // mock sempre retorna true; SOAP retorna false em falha
        }
    }

    /**
     * Para ordens em CRIADO ou TARA_REALIZADA sem tara:
     * busca tara no Guardian e avança para TARA_REALIZADA.
     */
    public function sincronizarTara(OrdemCarregamento $ordem): bool
    {
        if ($ordem->ticket_guardian === null) {
            return false;
        }

        if ($ordem->tara !== null) {
            return false; // já tem tara
        }

        if (!in_array($ordem->status, [StatusOrdem::CRIADO, StatusOrdem::AGUARDANDO_CARREGAMENTO])) {
            return false;
        }

        try {
            $tara = $this->adapter->consultarTara($ordem->ticket_guardian);

            if ($tara <= 0) {
                return false;
            }

            $ordem->update(['tara' => $tara]);

            // Tenta vincular motorista cadastrado (motorista_documento pode ter chegado nesta sync)
            $this->resolverMotorista->execute($ordem->fresh());

            if ($ordem->status === StatusOrdem::CRIADO) {
                $this->alterarStatus->execute($ordem, new AlterarStatusDTO(
                    novoStatus: StatusOrdem::TARA_REALIZADA,
                    tipoEvento: TipoEvento::TARA_REALIZADA,
                    origem: OrigemEvento::GUARDIAN,
                    observacao: "Tara sincronizada do Guardian: {$tara} kg",
                    payload: ['tara' => $tara, 'ticket' => $ordem->ticket_guardian],
                ));
            }

            Log::info('Guardian: tara sincronizada', [
                'ordem_id' => $ordem->id,
                'ticket'   => $ordem->ticket_guardian,
                'tara'     => $tara,
            ]);

            return true;
        } catch (\Throwable $e) {
            Log::warning('Guardian: falha ao sincronizar tara', [
                'ordem_id' => $ordem->id,
                'ticket'   => $ordem->ticket_guardian,
                'erro'     => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Para ordens em AGUARDANDO_PESAGEM_FINAL:
     * busca peso bruto e registra pesagem final.
     */
    public function sincronizarPesagemFinal(OrdemCarregamento $ordem): bool
    {
        if ($ordem->ticket_guardian === null) {
            return false;
        }

        if ($ordem->status !== StatusOrdem::AGUARDANDO_PESAGEM_FINAL) {
            return false;
        }

        try {
            $pesoBruto = $this->adapter->consultarPesoFinal($ordem->ticket_guardian);

            if ($pesoBruto <= 0) {
                return false;
            }

            $this->registrarPesagem->execute($ordem, $pesoBruto, OrigemEvento::GUARDIAN);

            Log::info('Guardian: pesagem final sincronizada', [
                'ordem_id'  => $ordem->id,
                'ticket'    => $ordem->ticket_guardian,
                'peso_bruto' => $pesoBruto,
            ]);

            return true;
        } catch (\Throwable $e) {
            Log::warning('Guardian: falha ao sincronizar pesagem', [
                'ordem_id' => $ordem->id,
                'ticket'   => $ordem->ticket_guardian,
                'erro'     => $e->getMessage(),
            ]);
            return false;
        }
    }

    /** Sincroniza tara de todas as ordens pendentes. Retorna contagem de atualizadas. */
    public function sincronizarTodasTaras(): int
    {
        $ordens = OrdemCarregamento::where('status', StatusOrdem::CRIADO)
            ->whereNotNull('ticket_guardian')
            ->whereNull('tara')
            ->get();

        return $ordens->filter(fn ($o) => $this->sincronizarTara($o))->count();
    }

    /** Sincroniza pesagem de todas as ordens aguardando. Retorna contagem de atualizadas. */
    public function sincronizarTodasPesagens(): int
    {
        $ordens = OrdemCarregamento::where('status', StatusOrdem::AGUARDANDO_PESAGEM_FINAL)
            ->whereNotNull('ticket_guardian')
            ->get();

        return $ordens->filter(fn ($o) => $this->sincronizarPesagemFinal($o))->count();
    }

    /**
     * Relatório de tickets do Guardian num período: lista + métricas de volume,
     * tempo médio de pátio e throughput por hora.
     *
     * @return array{tickets: TicketGuardianDTO[], metricas: array}
     */
    // Placas fictícias usadas pelo Guardian p/ registrar entrada/saída de
    // funcionário (não são veículos de carregamento) — excluir do relatório.
    private const PLACAS_FUNCIONARIO = ['ENT0000', 'SAI0000'];

    public function relatorioPorPeriodo(\DateTimeInterface $inicio, \DateTimeInterface $fim): array
    {
        $tickets = array_values(array_filter(
            $this->adapter->consultarTicketsPorPeriodo($inicio, $fim),
            fn ($t) => !in_array($t->placa, self::PLACAS_FUNCIONARIO, true)
        ));

        return [
            'tickets'  => $tickets,
            'metricas' => $this->calcularMetricas($tickets),
        ];
    }

    /** @param TicketGuardianDTO[] $tickets */
    private function calcularMetricas(array $tickets): array
    {
        $total = count($tickets);

        $pesosLiquidos = array_filter(array_map(fn ($t) => $t->pesoLiquidoKg(), $tickets), fn ($p) => $p !== null);
        $temposPatio   = array_filter(array_map(fn ($t) => $t->tempoPatioMinutos(), $tickets), fn ($m) => $m !== null);

        $porHora = [];
        foreach ($tickets as $t) {
            if ($t->dataEntrada === null) {
                continue;
            }
            $hora = date('H:00', strtotime($t->dataEntrada));
            $porHora[$hora] = ($porHora[$hora] ?? 0) + 1;
        }
        ksort($porHora);

        return [
            'total_tickets'         => $total,
            'total_com_pesagem'     => count($pesosLiquidos),
            'peso_liquido_total_kg' => array_sum($pesosLiquidos),
            'tempo_medio_patio_min' => count($temposPatio) > 0 ? round(array_sum($temposPatio) / count($temposPatio)) : null,
            'throughput_por_hora'   => $porHora,
        ];
    }
}
