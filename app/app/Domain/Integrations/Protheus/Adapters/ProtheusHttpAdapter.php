<?php

namespace App\Domain\Integrations\Protheus\Adapters;

use App\Domain\Integrations\Protheus\DTOs\PedidoProtheusDTO;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class ProtheusHttpAdapter implements ProtheusAdapterInterface
{
    private string $baseUrl;
    private string $username;
    private string $password;
    private int $timeout;

    public function __construct()
    {
        $this->baseUrl  = config('integrations.protheus.base_url');
        $this->username = config('integrations.protheus.username');
        $this->password = config('integrations.protheus.password');
        $this->timeout  = config('integrations.protheus.timeout', 15);
    }

    public function consultarPedido(string $numero, string $filial): PedidoProtheusDTO
    {
        try {
            // GET /api/v1/faturamento/pedidos/{filial}/{numero} — ver docs/protheus-api-pedidos.postman_collection.json
            $response = Http::withBasicAuth($this->username, $this->password)
                ->timeout($this->timeout)
                ->get("{$this->baseUrl}/api/v1/faturamento/pedidos/{$filial}/{$numero}");

            Log::info('Protheus consultarPedido', [
                'numero' => $numero,
                'filial' => $filial,
                'status' => $response->status(),
            ]);

            if ($response->status() === 404) {
                throw ValidationException::withMessages([
                    'pedido' => "Pedido {$numero} não encontrado no Protheus (filial {$filial}).",
                ]);
            }

            $response->throw();

            return PedidoProtheusDTO::fromArray($response->json());
        } catch (ValidationException $e) {
            throw $e;
        } catch (\Exception $e) {
            Log::error('Protheus consultarPedido erro', ['numero' => $numero, 'erro' => $e->getMessage()]);
            throw new \RuntimeException("Falha na comunicação com Protheus: {$e->getMessage()}");
        }
    }

    public function pedidoExiste(string $numero, string $filial): bool
    {
        try {
            $response = Http::withBasicAuth($this->username, $this->password)
                ->timeout($this->timeout)
                ->get("{$this->baseUrl}/api/v1/faturamento/pedidos/{$filial}/{$numero}");

            return $response->ok();
        } catch (\Exception) {
            return false;
        }
    }
}
