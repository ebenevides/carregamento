# ROADMAP — Projeto Carregamento

| Fase | Descrição | Status | Progresso |
|---|---|---|---:|
| 0 | Preparação do projeto | Concluído | 100% |
| 1 | Modelo operacional | Concluído | 100% |
| 2 | Cadastros básicos | Concluído | 100% |
| 3 | Ordem de carregamento | Concluído | 100% |
| 4 | Fila de carregamento | Concluído | 100% |
| 5 | App operador (Flutter) | Concluído | 100% |
| 6 | Painel operacional (web) | Concluído | 100% |
| 7 | Integração Protheus | Concluído | 100% |
| 8 | Integração Guardian | Concluído | 100% |
| 9 | Pesagem final e validação | Concluído | 100% |
| 10 | Testes e homologação | Concluído | 100% |
| 11 | App Operador + Motorista + Chat | Planejado | 0% |

Plano detalhado da Fase 11 em `docs/CLAUDE_ROADMAP_OPERADOR_MOTORISTA.md` (backend) e
`docs/PLANO_APP_OPERADOR_MOTORISTA.md` (Flutter). Etapa 11.1 (migration `documento`/
`motorista_user_id`) ainda não iniciada.

---

## Fase 0 — Preparação do Projeto

### Etapa 0.1 — Criar documentação base

#### Objetivo
Criar arquivos iniciais de acompanhamento do projeto.

#### Escopo
- `ROADMAP.md`
- `STATUS.md`
- `DECISOES_TECNICAS.md`
- `CHANGELOG.md`
- Pasta `docs/`

#### Arquivos criados
- `ROADMAP.md`
- `STATUS.md`
- `DECISOES_TECNICAS.md`
- `CHANGELOG.md`
- `docs/integracao-guardian.md`

#### Critérios de aceite
- [x] Arquivos criados.
- [x] Roadmap inicial preenchido.
- [x] Status inicial preenchido.
- [x] Decisão técnica inicial registrada.

#### Status
Concluído

---

### Etapa 0.2 — Criar estrutura base do projeto Laravel 12

#### Objetivo
Preparar estrutura técnica inicial.

#### Escopo
- Criar projeto Laravel 12
- Configurar PostgreSQL
- Configurar Redis
- Configurar `.env.example`
- Instalar Inertia.js + Vue 3
- Instalar Laravel Sanctum
- Instalar Laravel Horizon
- Instalar Laravel Reverb
- Configurar estrutura de diretórios por domínio

#### Critérios de aceite
- [ ] Projeto executa localmente.
- [ ] Banco PostgreSQL conecta.
- [ ] Redis conecta.
- [ ] `.env.example` documentado.
- [ ] `php artisan migrate` executa sem erro.
- [ ] Horizon acessível em `/horizon`.
- [ ] Reverb configurado.

#### Status
Pendente
