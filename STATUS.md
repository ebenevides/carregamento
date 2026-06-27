# STATUS — Projeto Carregamento

## Status atual
Fases 0–8 concluídas. Restam: Fase 9 (pesagem/validação já implementada nas actions) e Fase 10 (testes).

## Última etapa concluída
Fase 8 — Integração Guardian SOAP + Protheus HTTP (mocks + adapters reais).

## Próximas etapas
- Fase 9: documentar e expor validação de peso (já implementada em `RegistrarPesagemFinalAction`)
- Fase 10: testes Pest (unitários + integração)
- Configurar autenticação web completa (Breeze ou customizada)
- Configurar PostgreSQL + Redis em produção
- Validar WSDL Guardian real: `$client->__getFunctions()`
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

## Pendências críticas para produção
- [ ] Validar métodos SOAP reais: `new SoapClient($wsdl)->__getFunctions()`
- [ ] Ajustar nomes dos campos XML em `GuardianSoapAdapter::mapearTicket()`
- [ ] Configurar `PROTHEUS_BASE_URL` + credenciais
- [ ] Configurar auth web completa (Breeze ou manual)
- [ ] Escrever testes Pest (Fase 10)

## Última atualização
2026-06-26
