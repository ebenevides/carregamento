# DECISÕES TÉCNICAS — Projeto Carregamento

---

## DT-001 — Backend centralizado (Laravel 12)

### Decisão
Backend Laravel 12 orquestra Protheus, Guardian, painel web e app Flutter. Nenhuma integração externa é acessada diretamente pelo frontend ou mobile.

### Motivo
Rastreabilidade, segurança e controle único de regras de negócio. Evita que mudança em sistema externo quebre múltiplos clientes.

### Impacto
Toda comunicação externa passa por `app/Domain/Integrations/`. Frontend e Flutter consomem apenas API interna.

### Data
2026-06-26

---

## DT-002 — Guardian via SOAP (ext-soap PHP)

### Decisão
Integração com Prix Guardian usa `SoapClient` PHP nativo (`ext-soap`). Sem biblioteca extra.

### Motivo
Guardian expõe SOAP ASP.NET (`.asmx?wsdl`). Confirmado: sem autenticação, pesos em kg, backend na mesma rede.

### Detalhes confirmados
- WSDL: `http://177.221.101.197:9148/ws_guardian/ws_guardian_plus.asmx?wsdl`
- Autenticação: nenhuma
- Unidade de peso: kg
- Rede: acesso direto (mesma rede)

### Impacto
`GuardianSoapAdapter` usa `new SoapClient($wsdl)` sem headers de autenticação. Pendente: validar nomes dos métodos via `__getFunctions()`.

### Data
2026-06-26

---

## DT-003 — Mobile Flutter com Sanctum token

### Decisão
App Flutter (operador de pá) consome API Laravel via token Sanctum. Web usa Sanctum session (Inertia).

### Motivo
Um único sistema de auth cobre dois clientes (web e mobile) com mecanismos diferentes mas mesmo guard.

### Impacto
`POST /api/v1/auth/login` retorna token. Flutter armazena em `flutter_secure_storage`. Web usa cookie de sessão via Inertia.

### Data
2026-06-26

---

## DT-004 — Real-time via Laravel Reverb

### Decisão
Painel operacional web usa WebSocket via Laravel Reverb (nativo Laravel). Sem Pusher externo.

### Motivo
Reverb é self-hosted, sem custo por mensagem, integrado nativamente ao broadcasting do Laravel.

### Impacto
Redis como driver de broadcast. Frontend Vue usa Laravel Echo + Reverb driver. Flutter usa `web_socket_channel` para atualizações da fila.

### Data
2026-06-26

---

## DT-005 — Filas e jobs via Laravel Horizon + Redis

### Decisão
Jobs assíncronos (sync Protheus, consultas Guardian, notificações) processados via Laravel Horizon com Redis.

### Motivo
Operações com sistemas externos são lentas/instáveis. Horizon dá visibilidade de filas, retries e falhas.

### Impacto
`QUEUE_CONNECTION=redis`. Horizon dashboard em `/horizon` (protegido por gate). Circuit breaker de Guardian implementado via Redis.

### Data
2026-06-26

---

## DT-006 — Estrutura por domínio (DDD-lite)

### Decisão
Código organizado por domínio (`Carregamento`, `Fila`, `Divergencia`, `Pesagem`, `Integrations`) dentro de `app/Domain/`, não por tipo técnico.

### Motivo
Projeto cresce por domínio, não por tipo. Evita `Services/` com 40 arquivos sem relação.

### Impacto
Cada domínio tem `Actions/`, `DTOs/`, `Enums/`, `Events/`, `Models/`, `Services/` próprios.

### Data
2026-06-26

---

## DT-007 — UUID como PK nas ordens de carregamento

### Decisão
Tabela `ordens_carregamento` usa UUID como chave primária.

### Motivo
Flutter e integrações externas não dependem de sequência do banco. IDs seguros em URLs de API.

### Impacto
`$table->uuid('id')->primary()`. Demais tabelas operacionais usam BIGINT autoincrement.

### Data
2026-06-26

---

## DT-008 — JSONB para payload de eventos

### Decisão
`eventos_ordem_carregamento.payload` é coluna `JSONB` (PostgreSQL).

### Motivo
Cada tipo de evento tem dados variáveis. JSONB evita schema explosion sem perder queryability.

### Impacto
PostgreSQL nativo. Eventos consultáveis por conteúdo do payload via `->` e `@>` operators.

### Data
2026-06-26
