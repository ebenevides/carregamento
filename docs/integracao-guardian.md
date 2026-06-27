# Integração Guardian — Referência SOAP

**Versão WS**: 6.23.56 (Toledo do Brasil)  
**Endpoint**: `http://177.221.101.197:9148/ws_guardian/ws_guardian_plus.asmx`  
**WSDL**: `http://177.221.101.197:9148/ws_guardian/ws_guardian_plus.asmx?wsdl`  
**Namespace**: `http://toledobrasil.com.br/WS_Guardian`

> **ATENÇÃO — porta**: WSDL interno aponta `<soap:address location>` para porta 80. Forçar location correto ao instanciar SoapClient:
> ```php
> new SoapClient($wsdl, ['location' => preg_replace('/\?.*$/', '', $wsdl)])
> ```

---

## Auth/Validação

Vários métodos exigem parâmetros de validação obrigatórios:
- `produto` = `'WS G'`
- `codigo` = `'01'`

---

## Formato de dados

- **Datas DATA E HORA**: `yyyy-MM-ddThh:mm:ss:FFF` (ex: `2012-01-14T15:58:00.237`)
- **Datas STRING**: `dd/MM/yyyy hh:mm:ss:FFF`
- **Pesos em VOTicketIntegracao** (TicketCompletoObtem): string BR — vírgula decimal, ponto milhar (`"47.230,500"` = 47230.5 kg)
- **Pesos em Ticket** (ExportaTicketParametro): `decimal` (sem conversão)
- **Elemento nulo**: suprimir tag do XML ou usar `xsi:nil="true"` — NUNCA enviar tag vazia `<campo></campo>`

---

## Métodos [INTERFACE] — uso em integração

### TicketCompletoObtem *(não documentado explicitamente, mas funciona)*

Consulta ticket completo como `VOTicketIntegracao` (campos como strings BR).

```php
$resposta = $client->TicketCompletoObtem([
    'voDadoTicket' => [
        'TicketCodigo' => '000123',
        'PlacaCarreta' => '',
        'Identificador' => '',
    ],
]);
$vo = $resposta->TicketCompletoObtemResult;
// Ticket inexistente → todos campos vazios (verificar $vo->PlacaCarreta === '' && $vo->DataCriacao === '')
```

Campos de `VOTicketIntegracao`:
| Campo | Tipo | Descrição |
|-------|------|-----------|
| `PlacaCarreta` | string | Placa da carreta |
| `Estado` | string | Estado do ticket |
| `EstadoAguardando` | string | Ex: "Encerrado" |
| `PesoBruto` | string BR | Peso bruto kg |
| `Tara` | string BR | Tara kg |
| `PesoLiquido` | string BR | Peso líquido kg |
| `DataCriacao` | string | Data/hora criação |
| `Transportadora.RazaoSocial` | string | Razão social transportadora |
| `CDCColeta.MotoristaEmail` | string | Email motorista (geralmente vazio) |

---

### ExportaTicketParametro *(preferencial para consulta por código — [INTERFACE])*

Consulta por código, placa ou TAG. Retorna `ArrayOfTicket` (struct `Ticket` com campos `decimal`).

Prioridade: 1º código → 2º última placa → 3º último TAG

```php
$resposta = $client->ExportaTicketParametro([
    'ticketCodigo' => '000123',
    'ticketPlaca'  => '',
    'ticketTAG'    => '',
    'produto'      => 'WS G',
    'codigo'       => '01',
]);
// $resposta->ExportaTicketParametroResult = ArrayOfTicket (pegar [0])
// $resposta->Erro: 0 = ok
// $resposta->ErroMSG
```

Campos relevantes de `Ticket`:
| Campo | Tipo | Descrição |
|-------|------|-----------|
| `Sequencial` | int64 | PK interna Guardian |
| `Codigo` | string | Código do ticket |
| `PlacaCarreta` | string(20) | Placa carreta |
| `PesoBruto` | decimal | Peso bruto kg |
| `Tara` | decimal | Tara kg |
| `Estado` | int | Estado (código inteiro) |
| `TipoOperacao` | int | 1=Recebimento, 2=Expedição |
| `DataPesagem` | dateTime | Data da pesagem |
| `DataCriacao` | dateTime | Data criação |
| `OperacaoTicket` | lista | Operações realizadas |

---

### ExportaTicketUnico + ConfirmaTicketUnico *(exportação diferencial — [INTERFACE])*

Par obrigatório. Retorna 1 ticket por vez da fila marcada para exportação.

```php
// 1. Exportar
$resposta = $client->ExportaTicketUnico([
    'completa'      => false,        // sempre false
    'nomeSistema'   => 'WS GUARDIAN',
    'Sincronizacao' => false,        // sempre false
    'produto'       => 'WS G',
    'codigo'        => '01',
]);
$ticket = $resposta->ExportaTicketUnicoResult;

// 2. Confirmar (OBRIGATÓRIO — senão acumula)
$client->ConfirmaTicketUnico([
    'SequencialTicket' => $ticket->Sequencial,
    'nomeSistema'      => 'WS GUARDIAN',
    'Falhou'           => false,
    'produto'          => 'WS G',
    'codigo'           => '01',
]);
```

---

### ExportaTicketsMarcados + ConfirmaLeituraTicketsMarcados *(lote — [INTERFACE])*

Retorna TODOS os tickets marcados. Chamar `ConfirmaLeituraTicketsMarcados` sempre depois.

```php
$resposta = $client->ExportaTicketsMarcados([]);
// $resposta->ExportaTicketsMarcadosResult = ArrayOfTicket

$client->ConfirmaLeituraTicketsMarcados([]);
```

---

### ConsultaTicketsPorPeriodo *([INTERFACE])*

```php
$resposta = $client->ConsultaTicketsPorPeriodo([
    'dataInicial' => '2024-01-01T00:00:00',
    'dataFinal'   => '2024-01-31T23:59:59',
    'produto'     => 'WS G',
    'codigo'      => '01',
]);
// $resposta->ConsultaTicketsPorPeriodoResult = lista de Ticket
```

---

### CadastraTicketGuardian *(pré-cadastro — [INTERFACE])*

Importa ticket em pré-cadastro ou com pesagem inicial já executada.

```php
$resposta = $client->CadastraTicketGuardian([
    'DadosTicket' => [
        'PlacaCarreta'    => 'ABC1D23',               // OBRIGATÓRIO
        'PlacaVeiculo'    => 'ABC1D23',
        'DataPesagem'     => '2024-01-01T00:00:00',   // OBRIGATÓRIO (qualquer data)
        'Motorista'       => ['Nome' => 'João', 'CNH' => '12345678'],
        'Transportadora'  => ['CNPJ' => '12.345.678/0001-99', 'RazaoSocial' => 'Trans XYZ'],
        'PesoBrutoOrigem' => 30000.0,
        'PesoMinimoOrigem'=> 28000.0,
        'PesoMaximoOrigem'=> 32000.0,
    ],
]);
// $resposta->CadastraTicketGuardianResult = Ticket
// $resposta->Erro: 303221 = sucesso; diferente = erro
// $resposta->ErroMSG
```

---

### AlteraTicketGuardian *([INTERFACE])*

```php
$resposta = $client->AlteraTicketGuardian([
    'DadosTicket' => [
        'Codigo'      => '000123',  // OBRIGATÓRIO para alteração
        'PlacaCarreta'=> 'ABC1D23',
        'DataPesagem' => '2024-01-01T00:00:00',
    ],
    'CfgAlteracao' => null,
]);
// $resposta->Erro: 303221=ok, 303224=falha alterar
```

---

### ManutencaoTicketGuardian *(alterar estado — [INTERFACE])*

```php
$resposta = $client->ManutencaoTicketGuardian([
    'VOConfiguracaoManutencaoTicketGuardian' => null,
    'VODadosManutencaoTicketGuardian' => [
        'TicketCodigo' => '000123',
        'TicketPlaca'  => '',
        'TicketTAG'    => '',
        // campos de estado conforme VODadosManutencaoTicketGuardian
    ],
]);
// $resposta->VORetornoManutencaoTicketGuardian->Erro: 0 = sucesso
```

---

### CapturaPeso *(ler peso da balança — [INTERFACE])*

Comando na fila, aguarda peso estável. Chamar em polling até capturar (Erro=0).

```php
$resposta = $client->CapturaPeso([
    'PontoControlePonto'   => 'PC01',  // código do ponto de controle
    'TempoValidadePesoBom' => 5,       // segundos validade peso bom
    'TimeoutLeitura'       => 5,       // timeout em segundos
    'produto'              => 'WS G',
    'codigo'               => '01',
]);
// $resposta->Peso (float) = peso capturado em kg
// $resposta->Erro: 0=ok, 1=timeout sem peso bom, outro=erro
```

---

### CancelaUltimaOperacaoAtivaGuardian *([INTERFACE])*

Cancela última operação. Se única → cancela ticket. Em encerrado → reabre (verifica duplicata de placa/TAG).

```php
$resposta = $client->CancelaUltimaOperacaoAtivaGuardian([
    'VOConfiguracaoCancelaUltimaOperacaoAtivaGuardian' => null,
    'VODadosCancelaUltimaOperacaoAtivaGuardian' => [
        'TicketCodigo' => '000123',
        'TicketPlaca'  => '',
        'TicketTAG'    => '',
    ],
]);
// $resposta->VORetornoCancelaUltimaOperacaoAtivaGuardian->Erro: 0 = sucesso
```

---

## PreCadastro — campos principais

| Campo | Tipo | Obrig. | Descrição |
|-------|------|--------|-----------|
| `Codigo` | String(30) | NÃO* | *Obrigatório em AlteraTicketGuardian |
| `PlacaCarreta` | String(20) | **SIM** | Placa carreta/vagão |
| `PlacaVeiculo` | String(20) | NÃO | Placa veículo (default=PlacaCarreta) |
| `DataPesagem` | DateTime | **SIM** | Não utilizado — preencher com qualquer data |
| `Motorista` | MotoristaIntegracao | NÃO | Nome, CNH, CPF |
| `Transportadora` | TransportadoraIntegracao | NÃO | CNPJ, razão social |
| `PesoBrutoOrigem` | Decimal | NÃO | Peso referência 1ª pesagem |
| `PesoMinimoOrigem` | Decimal | NÃO | Peso mínimo origem |
| `PesoMaximoOrigem` | Decimal | NÃO | Peso máximo origem |
| `PesoTotalOrigem` | Decimal | NÃO | Soma documentos |
| `TagAssociado` | String(250) | NÃO | TAG/transponder/código barras |
| `Observacao` | String(8000) | NÃO | Observação |
| `Item` | Lista ItemIntegracao | NÃO | Itens/produtos |
| `Tara` | Decimal | NÃO | **OBSOLETO** — usar HistoricoTara |
| `OperacaoDaPesagemInicial` | String | NÃO | Código operação pesagem inicial |

---

## Erros comuns

| Código | Contexto | Significado |
|--------|----------|-------------|
| `0` | ExportaTicket*, CapturaPeso | Sucesso |
| `1` | CapturaPeso | Timeout — peso não capturado |
| `303221` | Cadastra/AlteraTicket | Sucesso |
| `303224` | AlteraTicket | Falha ao alterar |

---

## Dicas de performance

1. Mínimo 1 segundo entre chamadas do **mesmo** método
2. `ExportaTicketUnico` sempre usar com `ConfirmaTicketUnico`
3. `ExportaTicketsMarcados` sempre usar com `ConfirmaLeituraTicketsMarcados`
4. Não acumular tickets sem confirmar leitura (degrada performance)

---

## Implementação no projeto

| Arquivo | Papel |
|---------|-------|
| `app/Domain/Integrations/Guardian/Adapters/GuardianAdapterInterface.php` | Interface |
| `app/Domain/Integrations/Guardian/Adapters/GuardianSoapAdapter.php` | Impl. SOAP real |
| `app/Domain/Integrations/Guardian/Adapters/GuardianMockAdapter.php` | Mock (tickets 0000001, 0000002) |
| `app/Domain/Integrations/Guardian/Services/GuardianService.php` | Orquestração com domínio |
| `app/Jobs/SincronizarTarasGuardianJob.php` | Job: sync taras (schedule 2min) |
| `app/Jobs/SincronizarPesagensGuardianJob.php` | Job: sync pesagens (schedule 2min) |
| `config/integrations.php` | `GUARDIAN_MOCK`, `GUARDIAN_WSDL`, `GUARDIAN_TIMEOUT` |

### Método atual para consulta de ticket

`TicketCompletoObtem` — retorna `VOTicketIntegracao` (pesos como strings BR, parse necessário)

**Alternativa [INTERFACE]**: `ExportaTicketParametro` com `produto='WS G'` + `codigo='01'` → retorna `ArrayOfTicket` (campos `decimal`, sem parse)
