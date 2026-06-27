# Integração Prix Guardian

## Endpoint WSDL

```
http://177.221.101.197:9148/ws_guardian/ws_guardian_plus.asmx?wsdl
```

- Protocolo: **SOAP** (ASP.NET `.asmx`)
- IP: `177.221.101.197`
- Porta: `9148`
- Serviço: `ws_guardian_plus`

## Protocolo

Guardian expõe SOAP, não REST. O adapter Laravel deve usar `SoapClient` PHP nativo (`ext-soap`) ou pacote `artisaninc/soap`.

### Verificar métodos disponíveis

```php
$client = new SoapClient('http://177.221.101.197:9148/ws_guardian/ws_guardian_plus.asmx?wsdl');
dd($client->__getFunctions());
```

## Contrato esperado (a validar contra WSDL)

| Operação | Método SOAP (a confirmar) | Retorno esperado |
|---|---|---|
| Consultar ticket | `consultarTicket(ticket)` | dados do ticket |
| Consultar tara | `consultarTara(ticket)` | peso tara em **kg** |
| Consultar peso final | `consultarPesoFinal(ticket)` | peso bruto final em **kg** |
| Status do ticket | `consultarStatus(ticket)` | status atual |

> **Atenção:** nomes dos métodos SOAP devem ser validados consultando o WSDL real.

## Implementação prevista

### Fase 8.1 — Adapter mockado

```php
interface GuardianAdapterInterface
{
    public function consultarTicket(string $ticket): TicketGuardianDTO;
    public function consultarTara(string $ticket): float;
    public function consultarPesoFinal(string $ticket): float;
}

class GuardianMockAdapter implements GuardianAdapterInterface
{
    // retorna dados fixos para desenvolvimento sem conexão real
}
```

### Fase 8.2 — Adapter real SOAP

```php
class GuardianSoapAdapter implements GuardianAdapterInterface
{
    public function __construct(
        private readonly SoapClient $client
    ) {}

    public function consultarTicket(string $ticket): TicketGuardianDTO
    {
        $response = $this->client->__soapCall('NomeDoMetodo', [['ticket' => $ticket]]);
        return TicketGuardianDTO::fromSoap($response);
    }
}
```

Bind no `AppServiceProvider`:

```php
$this->app->bind(GuardianAdapterInterface::class, function () {
    return config('integrations.guardian.mock')
        ? new GuardianMockAdapter()
        : new GuardianSoapAdapter(new SoapClient(config('integrations.guardian.wsdl')));
});
```

`config/integrations.php`:

```php
return [
    'guardian' => [
        'wsdl' => env('GUARDIAN_WSDL', 'http://177.221.101.197:9148/ws_guardian/ws_guardian_plus.asmx?wsdl'),
        'mock' => env('GUARDIAN_MOCK', true),
        'timeout' => env('GUARDIAN_TIMEOUT', 10),
    ],
];
```

`.env`:

```env
GUARDIAN_WSDL=http://177.221.101.197:9148/ws_guardian/ws_guardian_plus.asmx?wsdl
GUARDIAN_MOCK=false
GUARDIAN_TIMEOUT=10
```

## Considerações

- **Rede**: backend na mesma rede do Guardian — acesso direto ao IP `177.221.101.197:9148`, sem VPN/proxy.
- **Autenticação**: Guardian **não requer autenticação** SOAP. `SoapClient` sem WS-Security.
- **Unidade de peso**: Guardian retorna pesos em **kg**. DTOs armazenam e operam em kg.
- **Timeout**: SOAP pode ser lento. Configurar timeout explícito no `SoapClient`.
- **Circuit breaker**: marcar Guardian como offline no Redis se falhar N vezes consecutivas. Evitar fila travada.
- **Logs**: registrar request/response SOAP para diagnóstico.

## Pendências

- [ ] Confirmar nomes dos métodos SOAP consultando o WSDL.
- [ ] Validar formato/estrutura do XML de resposta para cada operação.
