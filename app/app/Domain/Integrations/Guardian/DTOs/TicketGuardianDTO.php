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
}
