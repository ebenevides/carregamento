<?php

namespace App\Domain\Integrations\Guardian\DTOs;

final readonly class TicketGuardianDTO
{
    public function __construct(
        public string $ticket,
        public string $status,
        public ?string $placa,
        public ?string $motorista,
        public ?float $tara,
        public ?float $pesoBruto,
        public ?float $pesoLiquido,
        public ?string $dataEntrada,
        public ?string $dataSaida,
        // Campos adicionais Guardian (CamposAdicionais, Numero 1-4/1001-1004 — ver docs/integracao-guardian.md)
        public ?float $quantidadeACarregar = null,
        public ?string $ub = null,
        public ?string $usuarioProtheus = null,
        public ?string $observacao = null,
        // Ticket.TempoPermanencia nativo do Guardian (minutos), quando disponível
        public ?int $tempoPermanencia = null,
    ) {}

    public function taraKg(): ?float
    {
        return $this->tara;
    }

    public function pesoBrutoKg(): ?float
    {
        return $this->pesoBruto;
    }

    public function pesoLiquidoKg(): ?float
    {
        return $this->pesoLiquido ?? (
            ($this->pesoBruto !== null && $this->tara !== null)
                ? $this->pesoBruto - $this->tara
                : null
        );
    }

    /** Tempo de pátio em minutos: usa TempoPermanencia nativo do Guardian se disponível, senão calcula entrada→saída. */
    public function tempoPatioMinutos(): ?int
    {
        if ($this->tempoPermanencia !== null) {
            return $this->tempoPermanencia;
        }

        if ($this->dataEntrada === null || $this->dataSaida === null) {
            return null;
        }

        return (int) round(
            (strtotime($this->dataSaida) - strtotime($this->dataEntrada)) / 60
        );
    }
}
