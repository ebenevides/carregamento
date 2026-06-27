<?php

namespace App\Domain\Integrations\Guardian\Adapters;

use App\Domain\Integrations\Guardian\DTOs\TicketGuardianDTO;
use Illuminate\Validation\ValidationException;

class GuardianMockAdapter implements GuardianAdapterInterface
{
    private array $tickets = [
        '0000001' => [
            'ticket'      => '0000001',
            'status'      => 'ATIVO',
            'placa'       => 'ABC1D23',
            'motorista'   => 'João da Silva',
            'tara'        => 15.2,
            'peso_bruto'  => 47.2,
            'peso_liquido' => 32.0,
            'data_entrada' => '2026-06-26T08:00:00Z',
            'data_saida'   => null,
        ],
        '0000002' => [
            'ticket'      => '0000002',
            'status'      => 'ATIVO',
            'placa'       => 'XYZ9Z99',
            'motorista'   => 'Maria Souza',
            'tara'        => 14.8,
            'peso_bruto'  => null,
            'peso_liquido' => null,
            'data_entrada' => '2026-06-26T09:30:00Z',
            'data_saida'   => null,
        ],
    ];

    public function consultarTicket(string $ticket): TicketGuardianDTO
    {
        $data = $this->tickets[$ticket] ?? null;

        if ($data === null) {
            throw ValidationException::withMessages([
                'ticket' => "Ticket {$ticket} não encontrado no Guardian (mock).",
            ]);
        }

        return new TicketGuardianDTO(
            ticket: $data['ticket'],
            status: $data['status'],
            placa: $data['placa'],
            motorista: $data['motorista'],
            tara: $data['tara'],
            pesoBruto: $data['peso_bruto'],
            pesoLiquido: $data['peso_liquido'],
            dataEntrada: $data['data_entrada'],
            dataSaida: $data['data_saida'],
        );
    }

    public function consultarTara(string $ticket): float
    {
        return $this->consultarTicket($ticket)->taraKg() ?? 0.0;
    }

    public function consultarPesoFinal(string $ticket): float
    {
        $dto = $this->consultarTicket($ticket);

        if ($dto->pesoBrutoKg() === null) {
            throw ValidationException::withMessages([
                'ticket' => "Peso final ainda não disponível para ticket {$ticket}.",
            ]);
        }

        return $dto->pesoBrutoKg();
    }

    public function ticketExiste(string $ticket): bool
    {
        return isset($this->tickets[$ticket]);
    }
}
