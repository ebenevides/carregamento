<?php

namespace App\Domain\Carregamento\DTOs;

use App\Domain\Carregamento\Enums\TipoDivergencia;

final readonly class DestinoProdutoDTO
{
    public function __construct(
        public bool $resolvido,
        public ?int $pilhaProdutoId,
        public ?int $pontoCarregamentoId,
        public ?TipoDivergencia $tipoDivergencia,
    ) {}
}
