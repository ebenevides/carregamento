# STATUS — Projeto Carregamento

## Status atual
Fases 0–10 concluídas. Fase 11 (App Operador + Motorista + Chat) planejada em
`docs/CLAUDE_ROADMAP_OPERADOR_MOTORISTA.md` / `docs/PLANO_APP_OPERADOR_MOTORISTA.md`, ainda não
iniciada (Etapa 11.1 — migration `documento`/`motorista_user_id` — é o próximo passo real).

## Última etapa concluída
Hardening da integração Guardian pós-Fase 8: `GuardianSoapAdapter` migrado para os métodos reais do
WSDL (`ExportaTicketParametro`), extração correta de tara/peso bruto, e nova tela de relatório de
tickets por período (web + export PDF via dompdf), com métricas de volume/tempo de pátio/throughput.

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

## Pendências críticas para produção
- [x] Validar métodos SOAP reais: `new SoapClient($wsdl)->__getFunctions()`
- [x] Ajustar nomes dos campos XML em `GuardianSoapAdapter::mapearTicket()`
- [ ] 14 testes falhando com `419` (CSRF/sessão) em `ProfileTest`/`Auth*Test` — investigar antes de
      confiar na suíte (`docker exec carregamento-app-1 php artisan test`)
- [ ] Configurar `PROTHEUS_BASE_URL` + credenciais
- [ ] Iniciar Fase 11 (App Operador + Motorista + Chat)

## Última atualização
2026-07-08
