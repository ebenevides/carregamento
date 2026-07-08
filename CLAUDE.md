# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project overview

Sistema de Carregamento: controls truck loading at product piles, reconciling data from three
sources — **Protheus** (orders/contracts), **Prix Guardian** (weighbridge tickets/tara/final weight),
and operators on the floor. Three parts in this repo:

- `app/` — Laravel 12 backend + Inertia/Vue3 back-office web panel.
- `mobile/` — Flutter app (`carregamento_operador`) for the pá-carregadeira operator and, as of the
  current roadmap phase, the motorista profile too.
- `docker/`, `docker-compose.yml`, `docker-compose.override.yml` — local dev stack.

## Running the stack

```bash
docker compose up -d          # postgres, redis, app (php-fpm), horizon, nginx, node (vite dev)
```

The app container is `carregamento-app-1`. Run all `php artisan`/`composer` commands inside it, not
on the host — the host cannot resolve the `postgres`/`redis` hostnames used in `app/.env`:

```bash
docker exec carregamento-app-1 php artisan migrate
docker exec carregamento-app-1 php artisan route:list
docker exec -it carregamento-app-1 php artisan tinker
```

Nginx serves the app on `http://localhost` (port from `APP_PORT`, default 80). Vite dev server runs
in the `node` container on port 5173 (see `docker-compose.override.yml`); `vite.config.js` binds
`0.0.0.0` with HMR host `localhost` for this to work from the host browser.

## Common commands

Run inside `carregamento-app-1` (prefix with `docker exec carregamento-app-1` from the host):

```bash
php artisan test                              # full Pest/PHPUnit suite
php artisan test --filter=nome_do_teste       # single test
php artisan test tests/Feature/OrdemCarregamentoFluxoTest.php
vendor/bin/pint                               # code style (Laravel preset, no custom pint.json)
php artisan migrate
php artisan migrate:status
```

Frontend (from `app/`, or via the `node` container which already runs `npm run dev`):

```bash
npm run dev      # vite dev
npm run build    # production build
```

Test DB config (`phpunit.xml`) points at `carregamento_test` on `127.0.0.1:5432` — that port is
exposed to the host via `docker-compose.override.yml`, so tests can also run from the host if PHP/
Composer deps are installed there; otherwise run them inside the container as above.

## Architecture

### Domain layout (`app/app/Domain/`)

Code is organized by domain, not by technical layer. Each domain groups its own `Actions/`
(single-purpose use cases), `Services/`, `DTOs/`, `Enums/`, `Models/`:

- `Carregamento/` — the core domain: `OrdemCarregamento`, `PontoCarregamento`, `PilhaProduto`,
  `ProdutoPilhaPonto`, `DivergenciaCarregamento`, `Equipamento`, `EventoOrdemCarregamento`, plus the
  `StatusOrdem`/`PerfilUsuario`/`StatusDivergencia`/`StatusPonto`/`TipoDivergencia`/`TipoEvento`/
  `OrigemEvento` enums.
- `Fila/` — loading queue logic (`FilaCarregamentoService`).
- `Divergencia/` — divergence handling actions/services.
- `Pesagem/` — final weighing actions/services.
- `Integrations/Guardian/` and `Integrations/Protheus/` — external system adapters (see below).

Business logic lives in Actions/Services, never in controllers or the frontend — **RN-008**: Protheus
and Guardian are only ever reached through `app/Domain/Integrations/*` adapters, never directly from
the web frontend or the Flutter app.

### State machine

`StatusOrdem` (enum) encodes the full order lifecycle and its legal transitions via
`transicoesPermitidas()` / `podeTransicionarPara()`:

```
CRIADO → TARA_REALIZADA → AGUARDANDO_CARREGAMENTO → EM_CARREGAMENTO → CARREGAMENTO_CONCLUIDO
       → AGUARDANDO_PESAGEM_FINAL → PESAGEM_FINAL_REALIZADA → VALIDADO → FINALIZADO
```
`DIVERGENCIA` and `CANCELADO` are reachable from most non-terminal states. Every status change must
go through `AlterarStatusOrdemAction` and records an `EventoOrdemCarregamento` (RN-006) — don't
mutate `status` directly on the model. Reuse this action and the existing enums instead of
reimplementing transition logic.

`PerfilUsuario` (`ADMIN`, `EXPEDICAO`, `OPERADOR`, `MOTORISTA`) gates permissions via methods like
`podeIniciarCarregamento()`, `podeResolverDivergencia()`, `podeCancelarOrdem()` — check the enum, not
`docs/regras-negocio.md`, for the current authoritative profile list (the doc's table predates the
`MOTORISTA` profile and may lag).

### Two API surfaces

- `routes/web.php` — session-authenticated (`auth`, `verified` middleware), Inertia-rendered, used by
  the Vue back-office panel (admin/expedição screens: ordens, fila, pontos, pilhas, equipamentos,
  divergências, usuários, integração Guardian).
- `routes/api.php` — `Sanctum` token auth, prefixed `v1`, consumed by the Flutter app. Login issues a
  `plainTextToken` (`POST /api/v1/auth/login`). Operator queue endpoints live under
  `fila-carregamento/*` and `operador/minha-fila`.

### External integrations

Both integrations are behind an interface + two implementations, toggled by env flag — controllers
and services depend only on the interface:

- `Guardian` (weighbridge, SOAP): `GuardianAdapterInterface` → `GuardianSoapAdapter` (real, uses
  PHP `SoapClient` against the WSDL — see `docs/integracao-guardian.md` for the full SOAP protocol
  reference: method list, date/weight formats, auth params, known WSDL quirks) or
  `GuardianMockAdapter` (`GUARDIAN_MOCK=true`, the local/test default). `GuardianService` wraps the
  adapter with app-level logic (tara/pesagem sync, período reports with metrics, filtering out
  Guardian's fake employee-clock-in/out placas).
- `Protheus` (orders, HTTP): `ProtheusAdapterInterface` → `ProtheusHttpAdapter` or
  `ProthousMockAdapter` (`PROTHEUS_MOCK=true`) — note the adapter class name typo is intentional
  existing code, not a bug to fix incidentally.

### Mobile app (`mobile/`)

Flutter, Riverpod (`flutter_riverpod` + `riverpod_generator`) for state, `go_router` for navigation,
`dio` for the API v1 client, `flutter_secure_storage` for the auth token. Structure:
`lib/core/{api,providers,storage}` and `lib/features/{auth,fila,ordem,divergencia}`. Profile
(operador vs motorista) is resolved at login, single app/codebase for both.

## Project documentation workflow

`docs/CLAUDE_ROADMAP_CARREGAMENTO.md` (phases 0–10, done) and its continuation
`docs/CLAUDE_ROADMAP_OPERADOR_MOTORISTA.md` (phase 11+, in progress) are the authoritative execution
plans and set the working convention for this repo: **implement one small step at a time, and on
finishing a step update `docs/ROADMAP.md`, `docs/STATUS.md`, the relevant piece of
`docs/regras-negocio.md`/`docs/api.md`, and `docs/DECISOES_TECNICAS.md` when a technical decision
changed.** Don't start a new step before the previous one is documented. Prefer reusing existing
actions/services/enums (`AlterarStatusOrdemAction`, `RegistrarDivergenciaAction`,
`FilaCarregamentoService`, `OrdemStatusAlterado`, `PerfilUsuario`, `StatusOrdem`) over recreating
them.

`docs/` also holds `docs/CHANGELOG.md` and `docs/PLANO_APP_OPERADOR_MOTORISTA.md` (the Flutter-side
counterpart to the operador/motorista roadmap, with its own API contract). All project docs other
than the per-package `README.md` files live in `docs/`.
