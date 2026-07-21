# AGENTS.md

Instrucoes para agentes de codigo trabalhando neste repositorio. Aplicam-se a toda a arvore.

## Modo de comunicacao

- Se a skill `caveman` existir no ambiente local, carregue-a no inicio da sessao e mantenha o modo
  `ultra` ativo. Preserve nomes de codigo, comandos, erros e precisao tecnica sem abreviacao.
- Saia temporariamente do modo comprimido quando seguranca, operacao destrutiva ou ordem de passos
  puder ficar ambigua. Retome `ultra` depois.
- Responda em portugues, salvo pedido contrario do usuario ou convencao do artefato.

## Orquestracao obrigatoria

- Sempre que houver duas ou mais frentes independentes, execute-as em fan-out com subagentes.
- Agrupe leituras, buscas, testes e comandos independentes em fan-out/paralelo; distribua-os entre
  subagentes quando houver frentes independentes.
- Antes do fan-out, divida por fronteira clara: descoberta, backend/API, mobile, web, testes ou review.
- Cada subagente recebe objetivo, escopo, arquivos permitidos, criterio de aceite e instrucao explicita
  sobre editar ou apenas investigar.
- Evite dois agentes editando o mesmo arquivo. Para trabalho sobreposto, use investigadores em
  paralelo e deixe um unico agente consolidar.
- Enquanto subagentes trabalham, avance na parte local independente. Reuna todos os resultados,
  resolva contradicoes e valide o conjunto; delegacao nao transfere responsabilidade final.
- Nao crie subagente para tarefa atomica, sequencial, destrutiva ou sem trabalho paralelo util.
- Antes de encerrar mudanca relevante, use subagente reviewer quando houver slot e review puder rodar
  em paralelo com validacoes finais.

## Contexto do produto

Sistema de Carregamento reconcilia ordens/contratos do Protheus, tickets e pesos do Prix Guardian e
acoes operacionais no patio.

- `app/`: Laravel 12, API, jobs, dominio e painel Inertia/Vue 3.
- `mobile/`: app Flutter unico para perfis `OPERADOR` e `MOTORISTA`.
- `docker/` e `docker-compose*.yml`: stack local com Nginx, PHP-FPM, PostgreSQL, Redis, Horizon,
  Reverb e Vite.
- `docs/`: arquitetura, contratos, regras, decisoes, operacao e roadmaps.

Leia primeiro [docs/README.md](docs/README.md). Use [docs/ARQUITETURA.md](docs/ARQUITETURA.md) para
limites do monorepo e padroes, [docs/MOBILE.md](docs/MOBILE.md) para Flutter e
[docs/ROTAS.md](docs/ROTAS.md) para superficies HTTP.

## Fontes de verdade

Em conflito, siga esta ordem:

1. Codigo executavel, migrations e testes.
2. Enums, Actions, Services, Form Requests e arquivos de rotas.
3. `docs/regras-negocio.md`, `docs/api.md`, `docs/DECISOES_TECNICAS.md`.
4. `docs/STATUS.md` e `docs/ROADMAP.md`.
5. Roadmaps `CLAUDE_*`, planos e checklists historicos.

Nao deduza comportamento atual apenas de roadmap. Confirme no codigo.

## Invariantes de arquitetura e negocio

- Organize backend por dominio em `app/app/Domain/`; controllers coordenam HTTP, nao concentram regra.
- Reuse Actions, Services, DTOs e Enums existentes. Nao replique regra no Vue ou Flutter.
- Toda transicao de `StatusOrdem` passa por `AlterarStatusOrdemAction`; nunca grave `status`
  diretamente. Cada mudanca gera `EventoOrdemCarregamento` (RN-006).
- Autorize por `PerfilUsuario` e policies/regras existentes. Nao compare strings dispersas.
- Protheus e Guardian so podem ser acessados por adapters em `app/app/Domain/Integrations/` (RN-008).
- Destino de produto pode depender da UB do ticket Guardian. Reuse
  `ResolverDestinoProdutoService`; preserve normalizacao e fallback definidos na RN-012/DT-017.
- Preserve a grafia existente `ProthousMockAdapter`; mudanca exige refatoracao deliberada.
- API mobile usa Sanctum em `/api/v1`; web usa sessao, `auth` e `verified`.
- Mudanca de contrato exige atualizar backend, cliente mobile, testes e documentacao no mesmo passo.

## Fluxo de trabalho

1. Leia `docs/STATUS.md`, docs do dominio tocado e codigo fonte correspondente.
2. Inspecione `git status`; preserve alteracoes do usuario e evite refactors fora do escopo.
3. Planeje menor passo completo. Faça fan-out de investigacoes/implementacoes independentes.
4. Implemente seguindo padroes locais; mantenha migrations reversiveis e contratos explicitos.
5. Rode testes focados, formatadores e depois validacao mais ampla proporcional ao risco.
6. Atualize `docs/ROADMAP.md`, `docs/STATUS.md`, `docs/regras-negocio.md`, `docs/api.md` e/ou
   `docs/DECISOES_TECNICAS.md` quando o comportamento correspondente mudar.
7. Revise diff final, confirme refs da documentacao e relate testes nao executados.

## Comandos

Suba ambiente na raiz:

```bash
docker compose up -d
```

Laravel roda no container `carregamento-app-1` porque os hosts `postgres` e `redis` pertencem a rede
Docker:

```bash
docker exec carregamento-app-1 php artisan test
docker exec carregamento-app-1 php artisan test --filter=nome_do_teste
docker exec carregamento-app-1 vendor/bin/pint
docker exec carregamento-app-1 php artisan migrate
docker exec carregamento-app-1 php artisan route:list
```

Web, em `app/` ou no container Node:

```bash
npm run dev
npm run build
```

Mobile, em `mobile/`:

```bash
flutter pub get
flutter analyze
flutter test
flutter run
```

Prefira comandos nao interativos. Nao execute reset, limpeza de banco, migration destrutiva ou
remocao de arquivos sem autorizacao explicita.

## Criterio de conclusao

- Comportamento solicitado implementado de ponta a ponta.
- Testes relevantes adicionados/ajustados e executados, ou limitacao registrada.
- Formatacao e analise estatica aplicaveis sem erro novo.
- Contratos e docs sincronizados com codigo, com links para fontes reais.
- Diff sem segredos, artefatos locais ou alteracoes alheias ao escopo.
