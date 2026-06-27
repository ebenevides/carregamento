<?php

namespace App\Domain\Integrations\Protheus\Adapters;

use App\Domain\Integrations\Protheus\DTOs\PedidoProtheusDTO;

interface ProtheusAdapterInterface
{
    public function consultarPedido(string $numero, string $filial): PedidoProtheusDTO;

    public function pedidoExiste(string $numero, string $filial): bool;
}
