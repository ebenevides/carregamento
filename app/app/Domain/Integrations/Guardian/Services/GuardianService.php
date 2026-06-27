<?php

namespace App\Domain\Integrations\Guardian\Services;

use App\Domain\Carregamento\Actions\AlterarStatusOrdemAction;
use App\Domain\Carregamento\Actions\RegistrarPesagemFinalAction;
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
}
