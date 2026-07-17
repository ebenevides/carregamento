# CHANGELOG — Projeto Carregamento

Formato: [Semântico](https://semver.org/). Datas no formato YYYY-MM-DD.

---

## [Unreleased]

### Added
- Documentação base do projeto (ROADMAP, STATUS, DECISOES_TECNICAS, CHANGELOG).
- `docs/integracao-guardian.md` com WSDL, protocolo SOAP e detalhes confirmados.
- Decisões técnicas DT-001 a DT-008.
- `docs/api.md`: seção de endpoints de chat (Fase 15, doc-debt fechado).
- Guardian: `consultarFila()` (método SOAP `FilaConsultaVeiculo`) — posição/estado do veículo na fila,
  endpoint `GET /api/v1/integracoes/guardian/fila/{ticket}`.
- `SincronizarFilaGuardianJob` (2min): ordens `TARA_REALIZADA` liberadas na fila do Guardian entram
  automaticamente em `AGUARDANDO_CARREGAMENTO` via `EntrarNaFilaAction` (DT-014).
- Painel `Integrações Guardian`: seção de fila com sync manual, card de contagem, ticket search enriquecido.

### Fixed
- `config/inertia.php` usava schema de chaves incompatível com a versão instalada do pacote
  (`inertiajs/inertia-laravel` v2.0.24) — quebrava `assertInertia()` em qualquer teste (DT-015).
- `bootstrap/providers.php` quebrava o boot em qualquer build `composer install --no-dev` (Docker de produção)
  por registrar o provider do Telescope sem checar se o pacote `require-dev` estava instalado (DT-011).
- 14 testes falhando com `419`: `phpunit.xml` não conseguia forçar `APP_ENV`/`DB_DATABASE` de teste (o `Env`
  do Laravel prioriza `$_SERVER`, já populado pelo ambiente real do container) — testes chegaram a rodar
  contra o banco de dev em vez de um banco isolado. Fix via `tests/bootstrap.php` (DT-012).
- Build docker travava com `invalid file request app/node_modules/.bin/...` — não existia `.dockerignore`.

### Added (realtime)
- Chat e notificação do motorista agora chegam em tempo real (foreground) via Reverb — antes só existia o
  scaffold de backend (`BROADCAST_CONNECTION=log`, sem serviço Reverb rodando, app Flutter sem client
  nenhum ligado). Ver DT-013.
- `mobile/lib/core/realtime/realtime_client.dart`: client mínimo do protocolo Pusher sobre `web_socket_channel`
  (substitui `pusher_channels_flutter`, removido — não suporta host self-hosted).
- Fixture de ticket Guardian real anonimizado (`tests/Fixtures/guardian-tickets-exemplo.xml`) + 4 testes
  travando `GuardianSoapAdapter::mapearTicket()` contra casos de borda reais.

---

## [0.1.0] — 2026-06-26

### Added
- Etapa 0.1 concluída: documentação base criada.
- Roadmap completo com fases 0–10.
- Guardian: WSDL confirmado, sem auth, pesos em kg, mesma rede.
