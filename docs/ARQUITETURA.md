# Arquitetura do Monorepo

## Visao geral

O repositorio entrega tres superficies sobre um unico dominio operacional:

```text
Flutter (operador/motorista) -> API /api/v1 + Sanctum -> Laravel Domain
Vue/Inertia (back-office)    -> web + sessao          -> Laravel Domain
Protheus HTTP / Guardian SOAP <-> adapters            -> Laravel Domain
                                                     -> PostgreSQL
Jobs/Horizon <-> Redis                         Reverb -> eventos privados
```

Laravel e a fronteira central: clientes nao acessam Protheus, Guardian ou banco diretamente.

## Pacotes e responsabilidades

| Caminho | Responsabilidade | Pontos de entrada |
|---|---|---|
| `app/` | Backend, painel web e processos assincronos | `routes/api.php`, `routes/web.php`, `routes/console.php` |
| `mobile/` | App Flutter por perfil | `lib/main.dart`, `lib/core/providers/router_provider.dart` |
| `docker/` | Imagens/config de PHP e Nginx | `docker/php/Dockerfile`, `docker/nginx/default.conf` |
| `docker-compose.yml` | Servicos persistentes/runtime | `app`, `nginx`, `horizon`, `reverb`, `postgres`, `redis` |
| `docker-compose.override.yml` | Desenvolvimento local | mount de fonte, Vite, portas PostgreSQL/Redis |
| `docs/` | Contratos, regras, operacao e historico | [README.md](README.md) |

Nao ha workspace manager no topo: Laravel/Vue e Flutter possuem toolchains independentes, unidos
pelo contrato HTTP/eventos e pelo Compose.

## Backend DDD-lite

`app/app/Domain/` agrupa comportamento por contexto:

- `Carregamento/`: ordem, pontos, pilhas, equipamentos, eventos, estados, perfis e casos de uso.
- `Fila/`: ordenacao e visibilidade operacional por ponto.
- `Chat/`: mensagens vinculadas a ordem e evento broadcast.
- `Integrations/Guardian/`: porta SOAP real/mock e sincronizacao de tara, peso e fila.
- `Integrations/Protheus/`: porta HTTP real/mock para pedidos e contratos.

Padrao de chamada:

```text
Route -> Controller -> Form Request -> Action/Service -> Model/Adapter
                                             |-> Event/Job
Controller <- Resource/JSON <-----------------|
```

Controllers traduzem HTTP. Actions executam casos de uso. Services concentram regra reutilizavel.
DTOs tornam entradas explicitas. Enums detem vocabulário e permissoes do dominio. Models persistem
estado e relacionamentos.

## Estado e regras centrais

Fluxo principal definido em `app/app/Domain/Carregamento/Enums/StatusOrdem.php`:

```text
CRIADO -> TARA_REALIZADA -> AGUARDANDO_CARREGAMENTO -> EM_CARREGAMENTO
       -> CARREGAMENTO_CONCLUIDO -> AGUARDANDO_PESAGEM_FINAL
       -> PESAGEM_FINAL_REALIZADA -> VALIDADO -> FINALIZADO
```

`DIVERGENCIA` e `CANCELADO` sao desvios permitidos conforme estado. Toda transicao usa
`app/app/Domain/Carregamento/Actions/AlterarStatusOrdemAction.php` e registra
`EventoOrdemCarregamento`. Consulte [regras-negocio.md](regras-negocio.md) para RN-001 a RN-011 e
confirme detalhes nos Actions, Services e Enums citados.

`PerfilUsuario.php` centraliza capacidades de `ADMIN`, `EXPEDICAO`, `OPERADOR` e `MOTORISTA`.
Autorizacao deve considerar perfil, ponto vinculado e ownership da ordem.

### Resolucao de destino por UB

Produtos existentes em mais de uma unidade de britagem nao podem ser resolvidos apenas por
`produto_codigo`. Quando a ordem nasce com `ticket_guardian`, `CriarOrdemAction` consulta o ticket,
extrai e normaliza a UB (`UB-1` -> `UB1`) e a entrega a `ResolverDestinoProdutoService`. O servico
prioriza vinculo `ProdutoPilhaPonto` cujo `PontoCarregamento.unidade_britagem` corresponde a UB;
sem correspondencia, preserva o fallback pelo primeiro vinculo ativo/padrao.

Fontes: `app/app/Domain/Carregamento/Actions/CriarOrdemAction.php`,
`app/app/Domain/Carregamento/Services/ResolverDestinoProdutoService.php`, migration
`app/database/migrations/2026_07_20_184746_add_unidade_britagem_to_pontos_carregamento_table.php`,
RN-012 em [regras-negocio.md](regras-negocio.md) e DT-017 em
[DECISOES_TECNICAS.md](DECISOES_TECNICAS.md). Se o ticket for vinculado depois da criacao, nao ha
re-resolucao automatica; trate como limitacao vigente.

## Integracoes e assincronia

Adapters sao selecionados por configuracao e injetados por interface:

- Guardian: `GuardianAdapterInterface`, `GuardianSoapAdapter`, `GuardianMockAdapter`.
- Protheus: `ProtheusAdapterInterface`, `ProtheusHttpAdapter`, `ProthousMockAdapter`.

Jobs `SincronizarTarasGuardianJob`, `SincronizarPesagensGuardianJob` e
`SincronizarFilaGuardianJob` rodam via Horizon/Redis. Protocolo Guardian: [integracao-guardian.md](integracao-guardian.md).

Reverb publica eventos publicos e privados. Autorizacao dos canais privados esta em
`app/routes/channels.php`; mobile fala protocolo Pusher via WebSocket em
`mobile/lib/core/realtime/realtime_client.dart`.

## Dados e ambientes

- PostgreSQL 16: persistencia transacional; UUID identifica ordens.
- Redis 7: filas/cache/broadcast conforme configuracao Laravel.
- JSONB: payload flexivel de eventos, conforme DT-008.
- Nginx: entrada HTTP; PHP-FPM executa Laravel; Node/Vite existe apenas no override de dev.

Variaveis ficam em `.env`/`app/.env`; nunca documente valores secretos. Flags `GUARDIAN_MOCK` e
`PROTHEUS_MOCK` controlam adapters. Confira nomes atuais em `app/.env.example` e configs Laravel.

## Padroes de mudanca

1. Contrato novo: rota + Request + Action/Service + Resource + testes + `api.md`/`ROTAS.md`.
2. Estado novo: enum + transicoes + Action + evento + permissoes + testes + regras de negocio.
3. Integracao nova: interface + adapter real + mock + binding + testes; nenhum acesso no cliente.
4. Funcao mobile: model/provider/screen por feature; chamadas somente via `ApiClient`.
5. Decisao estrutural: registre em `DECISOES_TECNICAS.md`; atualize `STATUS.md` e roadmap aplicavel.
