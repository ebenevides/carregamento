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

---

## DT-009 — Motorista como User do sistema

### Decisão
Motorista é `User` cadastrado (`perfil = MOTORISTA`), vinculado à ordem via `motorista_user_id`, resolvido automaticamente por `documento` (CPF).

### Motivo
Permitir autenticação Sanctum própria, canal privado individual e histórico por motorista, mantendo compatibilidade com o texto solto vindo de Protheus/Guardian.

### Impacto
- `users.documento` (nullable, unique quando preenchido)
- `ordens_carregamento.motorista_user_id` (FK → users, nullable)
- Resolução automática não bloqueia ordem se motorista não tiver cadastro

### Data
2026-07-09

---

## DT-010 — Canais privados Reverb via Sanctum

### Decisão
Canais privados Reverb (`PrivateChannel`) autenticados via Sanctum (`auth:sanctum`) para chat (`ordem.{id}.chat`) e notificação individual ao motorista (`App.Models.User.{id}`).

### Motivo
Canais públicos não suportam autorização por usuário, necessária para chat 1:1 e notificação individual do motorista.

### Impacto
- `Broadcast::routes(['middleware' => ['api', 'auth:sanctum']])` substitui o default web
- Canais públicos existentes (`ordens`, `ponto.{id}`) continuam como estão
- App Flutter precisará de cliente Pusher/Echo compatível para assinar canais privados

### Data
2026-07-09

---

## DT-011 — Laravel Telescope para debug local

### Decisão
`laravel/telescope` instalado como dependência `require-dev`, com o gate `viewTelescope` (definido em `App\Providers\TelescopeServiceProvider`) restrito a lista de e-mails vazia por padrão — ou seja, acesso liberado apenas em `APP_ENV=local`.

### Motivo
Ferramenta de inspeção de requests/queries/jobs/eventos para acelerar debug local dos fluxos de integração (Protheus/Guardian) e do state machine de `OrdemCarregamento`, sem expor dados em produção.

### Impacto
- Migration `telescope_entries` adicionada
- Provider registrado em `bootstrap/providers.php`, **condicionado a `class_exists(\Laravel\Telescope\TelescopeServiceProvider::class)`** — como o pacote é `require-dev`, um `composer install --no-dev` (imagem Docker de produção, `docker/php/Dockerfile`) não o instala; sem essa guarda o boot da aplicação quebrava inteiro nesses builds (`Class "Laravel\Telescope\TelescopeApplicationServiceProvider" not found`)
- Painel acessível em `/telescope` somente com `APP_ENV=local` (ou usuário autorizado no gate, em outros ambientes)

### Data
2026-07-14 (guarda de boot adicionada em correção posterior)

---

## DT-012 — Isolamento de ambiente de teste via `tests/bootstrap.php`

### Decisão
`phpunit.xml` não força mais `APP_ENV`/`DB_DATABASE`/etc via `<env force="true">`. Em vez disso,
`tests/bootstrap.php` (novo `bootstrap` do PHPUnit, substitui `vendor/autoload.php` direto) escreve os valores
de teste em `$_SERVER` antes do autoload. `DB_HOST`/`DB_PORT` continuam só em `phpunit.xml`, sem force.

### Motivo
`<env>` do PHPUnit só escreve em `$_ENV`/`putenv()`. O `Illuminate\Support\Env` do Laravel dá prioridade a
`$_SERVER`, que dentro do container já vem populado com os valores reais de `app/.env` (via `env_file` do
docker-compose) antes do PHPUnit sequer rodar — `force="true"` nunca vencia essa prioridade. Resultado:
`APP_ENV` ficava `local` durante os testes (quebrando `runningUnitTests()` e gerando 419 em toda rota com CSRF)
e `DB_DATABASE` apontava pro banco de **dev** (`carregamento`) em vez de um banco de teste isolado — que nem
existia. `DB_HOST`/`DB_PORT` ficam de fora do force porque são os únicos valores realmente dependentes de
contexto: dentro do container devem continuar herdando `postgres:5432` do `.env` real; rodando a partir do
host (sem esse env já setado) caem no fallback `127.0.0.1:5433` do `docker-compose.override.yml`.

### Impacto
- Banco `carregamento_test` criado no Postgres (não existia)
- `tests/bootstrap.php` novo, referenciado por `phpunit.xml`'s `bootstrap=`
- 83/83 testes passando de forma isolada, sem tocar o banco de dev
- Ver nota em `STATUS.md` sobre o banco de dev ter sido zerado e restaurado durante a investigação deste bug

### Data
2026-07-17

---

## DT-013 — Realtime foreground via Reverb + client Pusher-protocol manual no Flutter

### Decisão
Backend usa `laravel/reverb` (já estava no `composer.json`) com `BROADCAST_CONNECTION=reverb`, servido atrás
do nginx via proxy em `/app/*` e `/apps/*` (sem porta extra exposta). No Flutter, `pusher_channels_flutter`
foi removido e substituído por um client mínimo do protocolo Pusher escrito à mão sobre `web_socket_channel`
(`mobile/lib/core/realtime/realtime_client.dart`).

### Motivo
`pusher_channels_flutter` (2.4.0, e confirmado até a `master` do repo oficial) só conecta em Pusher Cloud —
seu `init()` aceita `cluster`, não um host/porta customizado, e o plugin nativo Android não expõe outro jeito
de apontar pra um servidor self-hosted como o Reverb. Como o Reverb fala exatamente o protocolo Pusher sobre
WebSocket puro (`pusher:connection_established`, `pusher:subscribe` com `auth` de canal privado, etc.), um
client manual sobre `web_socket_channel` (dependência já declarada, também não usada até então) resolve sem
trocar de pacote por outro de fornecedor desconhecido.

`REVERB_HOST` (usado pelo backend pra publicar eventos, via rede interna do Docker — `reverb:8080`) foi
desacoplado de `VITE_REVERB_HOST`/`VITE_REVERB_PORT` (usado pelo painel Vue, aponta pro host:porta externos,
ex.: `localhost:5405` local). Antes eram a mesma variável interpolada (`VITE_REVERB_HOST="${REVERB_HOST}"`),
o que não podia satisfazer os dois lados ao mesmo tempo — um serve tráfego container-a-container, o outro
precisa ser alcançável de fora.

### Impacto
- `docker-compose.yml`/`.override.yml`: serviço `reverb` novo
- `docker/nginx/default.conf`: proxy `/app/*`,`/apps/*` → `reverb:8080` com upgrade de WebSocket
- `.dockerignore` criado (não existia — bloqueava rebuild por causa de `app/node_modules`)
- `mobile/pubspec.yaml`: `pusher_channels_flutter` removido
- `mobile/lib/core/realtime/realtime_client.dart` novo — reconexão simples (1 retry, sem backoff), sem
  suporte a push nativo (FCM/APNs) — app em background/fechado não recebe notificação, só foreground
- `chat_provider.dart` e `motorista_provider.dart` assinam canais privados e recarregam dados on-event, em
  vez de só substituir o estado localmente (mais simples, evita lógica de merge/dedupe)

### Data
2026-07-17

---

## DT-014 — Fila do Guardian libera ordem automaticamente (TARA_REALIZADA → AGUARDANDO_CARREGAMENTO)

### Decisão
`GuardianService::sincronizarFila()` consulta `FilaConsultaVeiculo` pra ordens em `TARA_REALIZADA` com
`ticket_guardian`; quando o Guardian reporta o veículo liberado na fila dele (`FilaGuardianDTO::liberado()`),
a ordem entra na nossa fila de carregamento via `EntrarNaFilaAction` (mesma ação/validação do fluxo manual de
expedição — RN-001/002/003/005), origem `OrigemEvento::GUARDIAN`. Job `SincronizarFilaGuardianJob`,
`everyTwoMinutes()`, mesmo padrão de `SincronizarTarasGuardianJob`/`SincronizarPesagensGuardianJob`.

### Motivo
Decisão de produto confirmada com o usuário: a fila do Guardian é o gate físico antes do caminhão poder
entrar na fila de carregamento do sistema — antes disso a transição era só manual (ação da expedição).
Automatizar reduz etapa manual redundante quando o Guardian já sinaliza que o veículo está liberado.

### Impacto
- `GuardianAdapterInterface::consultarFila()` + `FilaGuardianDTO` novos (SOAP real via `FilaConsultaVeiculo`
  — método sem os parâmetros de auth `produto`/`codigo` que os demais métodos [INTERFACE] exigem — e mock)
- `GuardianService::sincronizarFila()`/`sincronizarTodasFilas()`, `SincronizarFilaGuardianJob`
- Endpoint `GET /api/v1/integracoes/guardian/fila/{ticket}` pra consulta manual/dashboard
- `liberado()` usa heurística por `EstadoDescricao` (string, case-insensitive) — Guardian não documenta enum
  oficial de códigos de estado da fila; só `305060`/`"Liberado"` confirmado até agora
- Reaproveita `EntrarNaFilaAction` sem alterações — se a ordem não estiver apta (RN-003 pilha sem produto,
  RN-005 divergência aberta), a exceção é capturada e logada, ordem fica em `TARA_REALIZADA` pro próximo ciclo

### Data
2026-07-17

---

## DT-015 — Fix `config/inertia.php` (schema incompatível com a versão instalada)

### Decisão
Reescrito pra bater com o schema real do `inertiajs/inertia-laravel` v2.0.24 instalado: chaves `page_paths`/
`page_extensions`/`ensure_pages_exist` na raiz (não aninhadas em `pages.*`), `testing.page_paths`/
`testing.page_extensions`, path corrigido pra `resource_path('js/Pages')` (`P` maiúsculo, batendo com a
estrutura real em `resources/js/Pages/`). Removida a chave `expose_shared_prop_keys` (não existe na v2.0.24,
não usada em nenhum lugar do código — provavelmente copiada de doc/versão diferente).

### Motivo
`config/inertia.php` do repo usava chaves de um schema que essa versão do pacote não lê
(`ServiceProvider::register()` acessa `config('inertia.page_paths')`/`config('inertia.testing.page_paths')`
diretamente). Como `pages.ensure_pages_exist` estava `false`, o app rodava normal — só quebrava
(`TypeError` em `FileViewFinder::__construct()`) ao chamar `assertInertia()` nos testes, o que nunca tinha
acontecido antes (nenhum teste do repo usava essa assertion até o teste do painel Guardian/fila).

### Impacto
- `config/inertia.php` reescrito
- `tests/Feature/IntegracaoGuardianFilaWebTest.php` é o primeiro teste do repo a usar `assertInertia()` —
  serve de guarda de regressão pra esse bug

### Data
2026-07-17

---

## DT-016 — Contrato real do Protheus confirmado, `PedidoProtheusDTO` redesenhado

### Decisão
`PROTHEUS_BASE_URL` real: `http://protheus.britaguia.com.br:8400/rest` (HTTPREST do AppServer TOTVS — o
`/rest` é obrigatório, sem ele cai no gateway genérico e dá 404 "HTTPREST- Error report"). Adapter chama
`GET {base}/api/v1/faturamento/pedidos/{filial}/{numero}`, conforme
`docs/protheus-api-pedidos.postman_collection.json`. `PedidoProtheusDTO` reescrito com `ClienteProtheusDTO`
(aninhado), `ItemPedidoProtheusDTO[]` (pedido pode ter múltiplos itens, cada um com seu próprio
`VeiculoProtheusDTO`/`MotoristaProtheusDTO` opcionais) — `PROTHEUS_MOCK=false` confirmado funcional contra
produção.

### Motivo
O DTO anterior (`clienteCodigo`, `produtoCodigo`, `placaVeiculo` no nível do pedido, etc.) foi escrito antes
de qualquer acesso real à API — campos como `placa_veiculo`/`motorista_nome` no cabeçalho do pedido **não
existem** no contrato real; vêm aninhados dentro de cada item (`itens[].veiculo`/`itens[].motorista`), porque
um mesmo pedido pode ser atendido por veículos/motoristas diferentes por item/entrega. `fromArray()` do DTO
antigo teria lançado `undefined array key` na primeira chamada real (esperava `cliente_codigo` como chave
plana; a API retorna `cliente` como objeto aninhado). Confirmado consultando produção diretamente
(`GET /rest/api/v1/faturamento/pedidos/{filial}/{numero}` com pedido real 778975/filial 00) antes de escrever
qualquer parsing — mesma disciplina usada pro Guardian (DT confirmar contrato > assumir).
Descoberta de investigação: probing contra `/pedidos/{numero}` (path antigo assumido) e porta `6790`
retornavam a página de erro genérica do TOTVS SmartClient/HTTPREST — não indicavam credencial errada, só path
errado. Porta certa é `8400`, prefixo `/rest` obrigatório.

### Impacto
- `PedidoProtheusDTO`, `ClienteProtheusDTO` (novo), `ItemPedidoProtheusDTO` (novo), `VeiculoProtheusDTO`
  (novo), `MotoristaProtheusDTO` (novo) em `Domain/Integrations/Protheus/DTOs/`
- `ProtheusHttpAdapter`/`ProthousMockAdapter` atualizados pro novo shape
- `IntegracaoController::pedidoProtheus()` (debug endpoint) atualizado
- **Nenhuma lógica de negócio consumia o DTO antigo** (confirmado antes do redesign — só o endpoint de debug
  ecoava os campos), então o redesign não teve efeito colateral em `CriarOrdemAction` ou fluxo de criação de
  ordem, que ainda não usa Protheus pra nada
- Fixture real anonimizada (`tests/Fixtures/protheus-pedido-exemplo.json`) + 4 testes novos
- Endpoints de dashboard financeiro, contratos de parceria e incluir/alterar pedido (também documentados nos
  Postman collections) **não implementados** — nada no código os usa ainda, fora do escopo desta etapa

### Data
2026-07-19
