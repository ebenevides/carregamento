# STATUS — Projeto Carregamento

## Status atual
Fases 0–15 concluídas (Operador + Motorista + Chat completos). 83 testes passando.

**Resumo do entregue (Fases 11–15):**
- Motorista como User do sistema (documento, motorista_user_id, resolução automática)
- Endpoint minha-ordem para motorista
- Ação Rejeitar do operador (DIVERGENCIA, nunca CANCELADO)
- RN-010: ações de fila restritas ao ponto do operador
- Chat por ordem (model, endpoints, evento broadcast)
- Broadcast privado via Reverb/Sanctum, ligado ponta a ponta (foreground) — ver seção "Realtime foreground
  ligado ponta a ponta" abaixo
- Notificação individual ao motorista via PrivateChannel — idem, consumida pelo app (`motorista_provider.dart`)
- 12 novos testes de feature (83 total) + roteiro de homologação

## Última etapa concluída
**Guardian — fila libera ordem automaticamente** — `consultarFila()`/`FilaGuardianDTO`, endpoint de consulta,
job `SincronizarFilaGuardianJob` (2min) liberando `TARA_REALIZADA` → `AGUARDANDO_CARREGAMENTO` via
`EntrarNaFilaAction` quando o Guardian sinaliza veículo liberado na fila dele. Ver DT-014. 8 testes novos.
Total: **95 testes, 194 assertions.**

Anteriormente: realtime foreground (Reverb) ligado ponta a ponta — ver seção própria abaixo. Antes disso,
**Etapa 2.5** — fixture anonimizada de ticket real Guardian com 4 testes travando `GuardianSoapAdapter::mapearTicket()`.

## Próximas etapas
- Fase 11 / Etapa 11.1: migration `documento` em `users` + `motorista_user_id` em
  `ordens_carregamento` (ver `docs/CLAUDE_ROADMAP_OPERADOR_MOTORISTA.md`)
- Configurar PostgreSQL + Redis em produção
- Definir URL/credenciais Protheus real

## O que foi feito (Fases 5–8)

### Fase 5 — App Flutter (mobile/operador)
- Projeto Flutter 3.41.9 em `mobile/`
- Stack: Riverpod (estado), GoRouter (navegação), Dio + Sanctum token, flutter_secure_storage
- `ApiClient` com interceptor de token automático
- `SecureStorage` para token, userId, perfil, pontoId
- `AuthProvider` (StateNotifier) com login/logout/init
- `FilaNotifier` com carregar, iniciarCarregamento, concluirCarregamento
- Telas: LoginScreen, FilaScreen (com cards de ação), OrdemDetalheScreen, RegistrarDivergenciaScreen
- Navegação com redirect auth automático
- Build sem erros (`flutter analyze` limpo)

### Fase 6 — Painel Web (Inertia + Vue)
- `DashboardController` com contadores por status e divergências abertas
- `DashboardIndex.vue` com filtro por data, grid de contadores coloridos, lista de divergências
- `ContadorCard.vue` componente reutilizável
- `DivergenciaController` com listagem e resolução (libera ordem automaticamente)
- Rotas web `GET /` e `POST /divergencias/{id}/resolver`

### Fase 7 — Protheus
- `ProtheusAdapterInterface` com `consultarPedido` e `pedidoExiste`
- `ProthousMockAdapter` com pedido 781456 de teste
- `ProtheusHttpAdapter` com HTTP Basic Auth, timeout, logs, tratamento 404
- Bind automático via config `PROTHEUS_MOCK`

### Fase 8 — Guardian SOAP
- `GuardianAdapterInterface` com `consultarTicket`, `consultarTara`, `consultarPesoFinal`, `ticketExiste`
- `GuardianMockAdapter` com tickets 0000001 e 0000002
- `GuardianSoapAdapter` com `SoapClient` nativo, circuit breaker Redis, WSDL cache, logs
- Pesos em kg conforme confirmado
- Sem autenticação conforme confirmado
- Bind automático via config `GUARDIAN_MOCK`
- `IntegracaoController` com endpoints `GET /api/v1/integracoes/protheus/pedidos/{numero}` e `GET /api/v1/integracoes/guardian/tickets/{ticket}`

## Total: 38 rotas API registradas

### Fase 8+ — Hardening Guardian e relatório por período
- `GuardianSoapAdapter` migrado para `ExportaTicketParametro` (métodos reais do WSDL, confirmados)
- Extração correta de tara e peso bruto a partir das operações do ticket
- `GuardianService::relatorioPorPeriodo()` — lista tickets do período + métricas (total, peso
  líquido total, tempo médio de pátio, throughput por hora), excluindo placas fictícias de
  entrada/saída de funcionário (`ENT0000`/`SAI0000`)
- `IntegracaoGuardianController::relatorioPeriodo`/`relatorioPeriodoPdf` + `Relatorio.vue` +
  `resources/views/guardian/relatorio-pdf.blade.php` (dompdf, paisagem A4)
- Pivot `produto_pilha_ponto` passa a herdar `produto_codigo`/`produto_descricao` da pilha ao
  sincronizar pontos (`PilhaProduto::pontosCarregamento()->withPivot(...)`); coluna `produto_codigo`
  virou nullable
- Documentação reorganizada: todos os `.md` soltos (exceto READMEs) movidos para `docs/`; `CLAUDE.md`
  criado na raiz com guia de arquitetura/comandos para instâncias futuras do Claude Code

### Fase 15+ — Laravel Telescope
- `laravel/telescope` instalado (`require-dev`), migration `telescope_entries` rodada
- Painel em `/telescope`, liberado por padrão só em `APP_ENV=local` (gate `viewTelescope` vazio fora disso)
- Ver DT-011

### Guardian — consulta de fila + liberação automática (FilaConsultaVeiculo)
- `GuardianAdapterInterface::consultarFila()`, implementado em `GuardianSoapAdapter` (método SOAP real, sem
  os params de auth `produto`/`codigo` que os outros métodos exigem) e `GuardianMockAdapter`
- `FilaGuardianDTO`: posição, estado/descrição, dados da fila (`CadastroFilaEntidade`), `sucesso()`,
  `liberado()` (heurística por descrição — Guardian não documenta enum de códigos de estado)
- Endpoint `GET /api/v1/integracoes/guardian/fila/{ticket}`
- `GuardianService::sincronizarFila()`/`sincronizarTodasFilas()` + `SincronizarFilaGuardianJob`
  (`everyTwoMinutes()`, mesmo padrão das outras sincronizações Guardian): ordens `TARA_REALIZADA` liberadas
  na fila do Guardian entram automaticamente em `AGUARDANDO_CARREGAMENTO` via `EntrarNaFilaAction`. Decisão
  confirmada com o usuário — ver DT-014. 5 testes novos.
- Painel `Integrações Guardian` (`/integracoes/guardian`): seção "Aguardando fila" com botão de sync manual
  por ordem, card de contagem, e busca manual de ticket enriquecida com posição/estado da fila.
  `sincronizarTodas()` agora inclui fila. 1 teste web novo (96 total).
- **Achado de bugfix junto**: `config/inertia.php` usava schema errado (`pages.paths` em vez de
  `page_paths`/`testing.page_paths`, que é o que a versão instalada — `inertiajs/inertia-laravel` v2.0.24 —
  realmente lê) — quebrava `assertInertia()` em qualquer teste, nunca detectado porque nenhum teste tinha
  usado essa assertion antes.

## Pendências críticas para produção
- [x] Validar métodos SOAP reais: `new SoapClient($wsdl)->__getFunctions()`
- [x] Ajustar nomes dos campos XML em `GuardianSoapAdapter::mapearTicket()`
- [x] ~~14 testes falhando com `419` (CSRF/sessão)~~ — causa real (reaberta e investigada de novo em 2026-07-17,
  a entrada anterior sobre Vite manifest era outra causa já corrigida): `phpunit.xml` usava `<env force="true">`,
  que só escreve em `$_ENV`/`putenv()` — o `Env` do Laravel prioriza `$_SERVER`, já populado com `APP_ENV=local`
  (e `DB_DATABASE=carregamento`, o banco de **dev**) pelo `env_file: app/.env` do container antes do PHPUnit
  rodar. Fix: `tests/bootstrap.php` força via `$_SERVER` antes do autoload; ver DT-012. **83/83 passando.**
- [x] ~~Telescope quebrava boot em build `--no-dev`~~ — `bootstrap/providers.php` registrava o provider sem
  checar se o pacote (`require-dev`) estava instalado; qualquer imagem Docker (`--no-dev`) não subia. Fix:
  guarda por `class_exists`. Ver DT-011 (atualizado).
- [ ] Configurar `PROTHEUS_BASE_URL` + credenciais
- [ ] Iniciar Fase 11 (App Operador + Motorista + Chat)
- [ ] Testar em emulador/dispositivo real (não só via API): badge de não lidas do chat, indicador
      visual "Pode se posicionar" chegando por realtime (Reverb/Pusher) sem refresh manual,
      reconexão automática após queda de rede. Ver `docs/CHECKLIST_TESTES_MOBILE.md` — cenários de
      backend/API já validados via `curl` em 2026-07-15, os de UI/realtime seguem pendentes por
      falta de Flutter SDK no ambiente de teste atual.
- [ ] Definir regra de negócio pra motorista com múltiplas ordens ativas simultâneas (gap
      encontrado em teste — ver `docs/regras-negocio.md`, seção "Gaps identificados em testes").

## Realtime foreground ligado ponta a ponta (2026-07-17)
Chat e "chegou sua vez" do motorista agora funcionam em tempo real com o app em foreground. O que foi feito,
depois de auditar e achar que nada disso existia de fato (só scaffold de backend + pacote client incompatível):

- `BROADCAST_CONNECTION=reverb` (era `log`) em `app/.env`.
- Serviço `reverb` novo em `docker-compose.yml`/`.override.yml` (`php artisan reverb:start`, mesma imagem do
  `app`/`horizon`).
- `docker/nginx/default.conf` faz proxy de `/app/*` (handshake WebSocket) e `/apps/*` (API HTTP do Reverb,
  usada pelo próprio backend pra publicar) pra `reverb:8080` — sem porta extra exposta a clientes.
- `REVERB_HOST=reverb` (hostname interno docker, usado pelo backend pra publicar) desacoplado de
  `VITE_REVERB_HOST`/`PORT` (usado pelo painel Vue, aponta pro host:porta externos) — antes eram a mesma
  variável interpolada, o que não podia funcionar pros dois lados ao mesmo tempo.
- `pusher_channels_flutter` **removido** do app Flutter: não suporta host self-hosted (só fala com Pusher
  Cloud via `cluster`, confirmado até a branch `master` do pacote). `web_socket_channel` (já declarado, não
  usado) virou a base de um client mínimo do protocolo Pusher em
  `mobile/lib/core/realtime/realtime_client.dart` — conecta, autentica canal privado via Sanctum
  (`/broadcasting/auth`), assina, reconecta 1x em caso de queda. Ligado em `chat_provider.dart`
  (`private-ordem.{id}.chat`) e `motorista_provider.dart` (`private-App.Models.User.{id}`).
- `.dockerignore` criado (não existia) — `app/node_modules` sem isso quebrava rebuild do BuildKit com um
  symlink.
- Testado ponta a ponta com script Node (WS real + REST real): mensagem enviada por HTTP chegou via
  WebSocket no mesmo segundo.

**Sem push nativo (FCM/APNs)** — app fechado/background não notifica. Ver DT-013.

## Atenção — banco de dev foi zerado e restaurado em 2026-07-17
Durante a investigação acima, testes com `RefreshDatabase` rodaram por engano contra o banco `carregamento`
(dev) antes do bug ser identificado — o banco `carregamento_test` isolado não existia. Dados perdidos;
restaurados via `php artisan db:seed --class=TestDataSeeder` (só continha massa de teste, sem cadastro manual
relevante, confirmado com o time). Banco `carregamento_test` criado no Postgres para isolar testes daqui pra
frente.

## Última atualização
2026-07-17
