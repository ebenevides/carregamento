<?php

namespace App\Domain\Carregamento\DTOs;

use App\Domain\Carregamento\Enums\OrigemEvento;
use App\Domain\Carregamento\Enums\StatusOrdem;
use App\Domain\Carregamento\Enums\TipoEvento;

final readonly class AlterarStatusDTO
{
    public function __construct(
        public StatusOrdem $novoStatus,
        public TipoEvento $tipoEvento,
        public OrigemEvento $origem,
        public ?int $usuarioId = null,
        public ?string $usuarioNome = null,
        public ?string $observacao = null,
        public ?array $payload = null,
    ) {}
}
