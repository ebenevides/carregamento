# Rotas HTTP e API

Este arquivo mapeia superficies e ownership. Payloads, respostas e exemplos ficam em
[api.md](api.md). Fonte executavel: `app/routes/api.php`, `app/routes/web.php`, `app/routes/auth.php`
e `app/routes/channels.php`. Confirme com `php artisan route:list` apos qualquer mudanca.

## API v1 mobile

Prefixo `/api/v1`. Apenas login e publico; demais rotas usam `auth:sanctum`.

| Metodo | Caminho | Controller/acao |
|---|---|---|
| POST | `/auth/login` | closure em `routes/api.php` |
| GET | `/me` | usuario autenticado + ponto |
| GET, POST | `/pontos-carregamento` | `PontoCarregamentoController@index/store` |
| GET, PUT/PATCH, DELETE | `/pontos-carregamento/{pontoCarregamento}` | resource controller |
| POST | `/pontos-carregamento/{pontoCarregamento}/ativar` | `ativar` |
| POST | `/pontos-carregamento/{pontoCarregamento}/inativar` | `inativar` |
| GET, POST | `/pilhas-produto` | `PilhaProdutoController@index/store` |
| GET, PUT/PATCH, DELETE | `/pilhas-produto/{pilhaProduto}` | resource controller |
| POST | `/pilhas-produto/{pilhaProduto}/ativar` | `ativar` |
| POST | `/pilhas-produto/{pilhaProduto}/inativar` | `inativar` |
| GET, POST | `/produto-pilha-ponto` | `ProdutoPilhaPontoController@index/store` |
| DELETE | `/produto-pilha-ponto/{produtoPilhaPonto}` | `destroy` |
| GET, POST | `/ordens-carregamento` | `OrdemCarregamentoController@index/store` |
| GET | `/ordens-carregamento/{ordemCarregamento}` | `show` |
| POST | `/ordens-carregamento/{ordemCarregamento}/iniciar` | `iniciar` |
| POST | `/ordens-carregamento/{ordemCarregamento}/concluir` | `concluir` |
| POST | `/ordens-carregamento/{ordemCarregamento}/rejeitar` | `rejeitar` |
| POST | `/ordens-carregamento/{ordemCarregamento}/divergencias` | `registrarDivergencia` |
| POST | `/ordens-carregamento/{ordemCarregamento}/pesagem-final` | `pesagemFinal` |
| POST | `/ordens-carregamento/{ordemCarregamento}/liberar-faturamento` | `liberarFaturamento` |
| GET | `/divergencias` | `DivergenciaController@index` |
| POST | `/divergencias/{divergencia}/resolver` | `resolver` |
| GET | `/integracoes/protheus/pedidos/{numero}` | `IntegracaoController@pedidoProtheus` |
| GET | `/integracoes/guardian/tickets/{ticket}` | `ticketGuardian` |
| GET | `/integracoes/guardian/fila/{ticket}` | `filaGuardian` |
| GET | `/fila-carregamento` | `FilaCarregamentoController@index` |
| GET | `/operador/minha-fila` | `minhaFila` |
| POST | `/fila-carregamento/{ordemCarregamento}/liberar` | `liberarParaFila` |
| GET | `/fila-carregamento/{ordemCarregamento}/validar` | `validar` |
| GET, POST | `/ordens-carregamento/{ordemCarregamento}/mensagens` | `ChatController@index/store` |
| GET | `/motorista/minha-ordem` | `MotoristaController@minhaOrdem` |

Controllers ficam em `app/app/Http/Controllers/Api/V1/`. Validacao fica em
`app/app/Http/Requests/`; serializacao em `app/app/Http/Resources/`; regras em `app/app/Domain/`.
Pontos aceitam e retornam `unidade_britagem`, usada na resolucao de destino por UB (RN-012/DT-017).

## Web back-office

Rotas operacionais em `routes/web.php` usam sessao e grupo `auth`, `verified`:

| Area | Caminhos principais | Controller |
|---|---|---|
| Dashboard | `/dashboard` | `DashboardController` |
| Ordens | `/ordens`, `/ordens/{ordem}`, acoes cancelar/iniciar/concluir/faturar/divergencia | `Web/OrdemCarregamentoController` |
| Fila | `/fila`, acoes entrar/iniciar/concluir/cancelar | `Web/FilaController` |
| Divergencias | `/divergencias`, resolver/cancelar | `Web/DivergenciaController` e API resolver |
| Pontos | `/pontos`, CRUD + ativar/inativar | `Web/PontoCarregamentoController` |
| Pilhas | `/pilhas`, CRUD | `Web/PilhaProdutoController` |
| Equipamentos | `/equipamentos`, CRUD | `Web/EquipamentoController` |
| Mapeamento | `/mapeamento`, CRUD | `Web/ProdutoPilhaPontoController` |
| Guardian | `/integracoes/guardian`, relatorio/PDF, consulta e sync | `Web/IntegracaoGuardianController` |
| Usuarios | `/usuarios`, CRUD + toggle ativo | `Web/UsuarioController` |
| Perfil | `/profile` | `ProfileController` |

Login, registro, verificacao e recuperacao de senha estao em `app/routes/auth.php`. Paginas Inertia
ficam em `app/resources/js/Pages/`.

## Broadcasting

`POST /broadcasting/auth` autentica subscriptions privadas via configuracao Laravel. Regras dos
canais ficam em `app/routes/channels.php`; eventos em `app/app/Domain/*/Events/`. O mobile envia
token Sanctum durante autorizacao e usa nomes Pusher com prefixo `private-` no WebSocket.

## Regras para evolucao

- Adicione endpoint em `routes/api.php` somente sob `v1` e defina auth/autorizacao explicitamente.
- Use Form Request para entrada, Action/Service para regra e Resource para resposta estavel.
- Mantenha route model binding e nomes de parametro consistentes com models.
- Atualize [api.md](api.md), este inventario, colecao Postman e cliente Flutter quando contrato mudar.
- Teste sucesso, validacao `422`, ausencia de auth `401`, permissao `403` e recurso ausente `404`.
