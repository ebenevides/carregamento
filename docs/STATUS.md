# STATUS — Projeto Carregamento

## Status atual
Fases 0–15 concluídas (Operador + Motorista + Chat completos). 83 testes passando.

**Resumo do entregue (Fases 11–15):**
- Motorista como User do sistema (documento, motorista_user_id, resolução automática)
- Endpoint minha-ordem para motorista
- Ação Rejeitar do operador (DIVERGENCIA, nunca CANCELADO)
- RN-010: ações de fila restritas ao ponto do operador
- Chat por ordem (model, endpoints, evento broadcast)
- Broadcast privado via Reverb/Sanctum (Broadcast::routes + channels)
- Notificação individual ao motorista via PrivateChannel
- 12 novos testes de feature (83 total) + roteiro de homologação

## Última etapa concluída
**Etapa 15.1 e 15.2** — 7 novos testes (ResolverMotoristaAction, RN-010 concluir/liberar), RN-010 implementado em `concluir`/`liberarParaFila`, roteiro de homologação em `docs/homologacao-ponta-a-ponta.md`. Total: **83 testes, 158 assertions.**

## Próximas etapas
- Fase 11 / Etapa 11.1: migration `documento` em `users` + `motorista_user_id` em
  `ordens_carregamento` (ver `docs/CLAUDE_ROADMAP_OPERADOR_MOTORISTA.md`)
- Investigar 14 testes falhando por `419` (CSRF/sessão) em `php artisan test` — ver Pendências abaixo
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

## Pendências críticas para produção
- [x] Validar métodos SOAP reais: `new SoapClient($wsdl)->__getFunctions()`
- [x] Ajustar nomes dos campos XML em `GuardianSoapAdapter::mapearTicket()`
- [x] ~~14 testes falhando com `419` (CSRF/sessão)~~ — causa real: Vite manifest ausente; `npm run build` nunca rodado. Fix: Dockerfile reordenado (composer-builder antes de node-builder) + `npm run build` executado. **60/60 passando.**
- [ ] Configurar `PROTHEUS_BASE_URL` + credenciais
- [ ] Iniciar Fase 11 (App Operador + Motorista + Chat)
- [ ] Testar em emulador/dispositivo real (não só via API): badge de não lidas do chat, indicador
      visual "Pode se posicionar" chegando por realtime (Reverb/Pusher) sem refresh manual,
      reconexão automática após queda de rede. Ver `docs/CHECKLIST_TESTES_MOBILE.md` — cenários de
      backend/API já validados via `curl` em 2026-07-15, os de UI/realtime seguem pendentes por
      falta de Flutter SDK no ambiente de teste atual.
- [ ] Definir regra de negócio pra motorista com múltiplas ordens ativas simultâneas (gap
      encontrado em teste — ver `docs/regras-negocio.md`, seção "Gaps identificados em testes").

## Última atualização
2026-07-15
