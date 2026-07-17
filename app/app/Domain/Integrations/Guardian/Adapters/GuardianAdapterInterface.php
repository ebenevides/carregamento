<?php

namespace App\Domain\Integrations\Guardian\Adapters;

use App\Domain\Integrations\Guardian\DTOs\FilaGuardianDTO;
use App\Domain\Integrations\Guardian\DTOs\TicketGuardianDTO;

interface GuardianAdapterInterface
{
    /** Retorna dados completos do ticket */
    public function consultarTicket(string $ticket): TicketGuardianDTO;

    /** Consulta posição/estado do veículo na fila do Guardian (FilaConsultaVeiculo) */
    public function consultarFila(string $ticket, ?string $placa = null): FilaGuardianDTO;

    /** Retorna tara em kg */
    public function consultarTara(string $ticket): float;

    /** Retorna peso bruto final em kg */
    public function consultarPesoFinal(string $ticket): float;

    /** Verifica se ticket existe */
    public function ticketExiste(string $ticket): bool;

    /**
     * Lista tickets criados/pesados no período (relatório).
     *
     * @return TicketGuardianDTO[]
     */
    public function consultarTicketsPorPeriodo(\DateTimeInterface $inicio, \DateTimeInterface $fim): array;
}
