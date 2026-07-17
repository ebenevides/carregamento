<?php

namespace App\Domain\Integrations\Guardian\DTOs;

final readonly class FilaGuardianDTO
{
    public function __construct(
        public string $ticket,
        public int $erro,
        public ?string $descricao,
        public ?string $placa,
        public ?int $posicao,
        public ?string $estado,
        public ?string $estadoDescricao,
        public ?int $filaId,
        public ?string $filaCodigo,
        public ?string $filaNome,
        public ?string $filaMensagem,
        public ?string $mensagemUsuario,
        public ?string $dataAtualizacao,
    ) {}

    public function sucesso(): bool
    {
        return $this->erro === 0;
    }

    /**
     * Guardian não documenta um enum oficial de códigos de estado da fila —
     * checagem por descrição (ex.: "Liberado") até confirmarmos os demais
     * valores possíveis em dados reais.
     */
    public function liberado(): bool
    {
        return $this->estadoDescricao !== null
            && str_contains(mb_strtolower($this->estadoDescricao), 'liberado');
    }
}
