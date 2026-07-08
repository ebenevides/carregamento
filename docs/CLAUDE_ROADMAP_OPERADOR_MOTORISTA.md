# CLAUDE.md — Roadmap de Execução: App Operador (fila) + App Motorista + Chat

## Contexto

Este documento complementa `docs/CLAUDE_ROADMAP_CARREGAMENTO.md` (Fases 0–10, já concluídas). O domínio `Carregamento`, a máquina de estados `StatusOrdem`, a fila (`FilaCarregamentoService`) e a API `v1` já existem e funcionam. Este roadmap cobre apenas o que falta para os dois perfis mobile do app Flutter (`mobile/`, projeto único, perfil resolvido no login):

- **Operador de pá carregadeira**: já tem fila e detalhe de ordem no app; falta o botão **Rejeitar** (Carregado e Próximo já existem via `concluir` e `fila-carregamento/{ordem}/liberar`).
- **Motorista**: não existe hoje como usuário do sistema — precisa app, login e vínculo com a ordem.
- **Chat operador↔motorista**: não existe em nenhuma camada (backend ou mobile) — feature 100% nova.
- **Realtime**: `OrdemStatusAlterado` já é `ShouldBroadcast` em canais **públicos** (`ordens`, `ponto.{id}`), mas não há `Broadcast::routes()` registrado em lugar nenhum — canais privados (necessários para chat e para notificar o motorista certo) ainda não funcionam.

## Numeração continuada

- Fases: continuam a partir da **Fase 11** (Fases 0–10 já concluídas em `docs/ROADMAP.md`).
- Regras de negócio: continuam a partir de **RN-009** (`docs/regras-negocio.md` vai até RN-008).
- Decisões técnicas: continuam a partir de **DT-009** (`docs/DECISOES_TECNICAS.md` vai até DT-008).

## Regras de desenvolvimento (herdadas de `CLAUDE_ROADMAP_CARREGAMENTO.md`)

- Executar de forma incremental, uma etapa por vez.
- Ao concluir cada etapa, atualizar `docs/ROADMAP.md`, `docs/STATUS.md`, `docs/regras-negocio.md`, `docs/DECISOES_TECNICAS.md` e `docs/api.md` conforme aplicável.
- Nenhuma regra crítica só no frontend/app — toda validação de transição de status, permissão de perfil e regra de fila vive no backend.
- Reaproveitar o que já existe: `AlterarStatusOrdemAction`, `RegistrarDivergenciaAction`, `FilaCarregamentoService`, `OrdemStatusAlterado`, `PerfilUsuario`, `StatusOrdem`. Não recriar máquina de estados nem enums já existentes.

---

## Fase 11 — Perfil e vínculo do Motorista

### Etapa 11.1 — Migration: `documento` em `users` + `motorista_user_id` em `ordens_carregamento`

#### Objetivo
Permitir que um motorista seja um `User` do sistema (`perfil = MOTORISTA`) e que a ordem referencie esse usuário.

#### Escopo
- Migration `add_documento_to_users_table`: `users.documento` (string, nullable, `unique` quando preenchido) — CPF do motorista, usado para casar com `ordens_carregamento.motorista_documento`.
- Migration `add_motorista_user_id_to_ordens_carregamento_table`: `ordens_carregamento.motorista_user_id` (nullable, `foreignId` → `users`, `nullOnDelete`).
- Atualizar `App\Models\User`: adicionar `documento` ao `$fillable`.
- Atualizar `OrdemCarregamento`: adicionar `motorista_user_id` ao `$fillable` e relação `motorista(): BelongsTo` (para `User::class`).

#### Arquivos criados/alterados
- `database/migrations/2026_XX_XX_add_documento_to_users_table.php`
- `database/migrations/2026_XX_XX_add_motorista_user_id_to_ordens_carregamento_table.php`
- `app/Models/User.php`
- `app/Domain/Carregamento/Models/OrdemCarregamento.php`

#### Critérios de aceite
- [ ] `php artisan migrate` executa sem erro.
- [ ] `users.documento` aceita nulo e é único quando preenchido.
- [ ] `OrdemCarregamento::motorista()` retorna o `User` vinculado.
- [ ] Documentação de schema atualizada.

---

### Etapa 11.2 — Resolução automática do vínculo motorista↔ordem

#### Objetivo
Ao criar/sincronizar uma ordem (Protheus/Guardian continuam mandando `motorista_documento` como texto), o backend resolve e preenche `motorista_user_id` automaticamente, sem bloquear a ordem se não achar.

#### Escopo
- Novo método/serviço em `App\Domain\Carregamento\Actions\CriarOrdemAction` (ou service dedicado `ResolverMotoristaAction`): busca `User::where('documento', $ordem->motorista_documento)->where('perfil', PerfilUsuario::MOTORISTA)->first()`; se achar, seta `motorista_user_id`; se não achar, loga (`Log::info`) e segue sem erro — ordem continua funcionando normalmente pelo texto solto (compatibilidade com fluxo atual do operador).
- Rodar essa resolução também quando a ordem for atualizada via sync Guardian (`GuardianService::sincronizarTara`), pois é nesse ponto que `motorista_documento` costuma chegar/mudar.

#### Arquivos criados/alterados
- `app/Domain/Carregamento/Actions/CriarOrdemAction.php`
- `app/Domain/Integrations/Guardian/Services/GuardianService.php`

#### Critérios de aceite
- [ ] Ordem criada com `motorista_documento` de um motorista cadastrado resolve `motorista_user_id` automaticamente.
- [ ] Ordem com `motorista_documento` sem cadastro correspondente não gera erro nem divergência.
- [ ] Resolução também roda em sincronização de tara vinda do Guardian.

---

### Etapa 11.3 — Endpoint "minha ordem" do motorista

#### Objetivo
Permitir que o app do motorista descubra qual é a ordem ativa dele.

#### Escopo
- `GET /api/v1/motorista/minha-ordem`: autenticado, exige `auth()->user()->perfil === PerfilUsuario::MOTORISTA`; retorna a `OrdemCarregamento` com `motorista_user_id = auth()->id()` e `status->estaAtivo() === true` (mais recente se houver mais de uma, o que não deveria ocorrer em operação normal); retorna `204`/corpo vazio se não houver ordem ativa.
- Resource de resposta inclui: `ticket_guardian`, `produto_codigo`, `produto_descricao`, `quantidade_prevista`, `pilha_produto` (nome/código — é a "bica"/pilha de destino), `ponto_carregamento` (nome — a "unidade de britagem"/ponto físico), `status`, `status_label`, `placa_veiculo`, `placa_carreta`.

#### Arquivos criados/alterados
- `app/Http/Controllers/Api/V1/MotoristaController.php` (novo)
- `routes/api.php` (nova rota)
- `app/Http/Resources/OrdemMotoristaResource.php` (novo, opcional — pode ser array simples se o projeto não usa API Resources em outros endpoints; verificar padrão em `OrdemCarregamentoController` antes de decidir)

#### Critérios de aceite
- [ ] Motorista autenticado recebe apenas a própria ordem ativa.
- [ ] Usuário com outro perfil recebe `403`.
- [ ] Resposta traz produto, pilha e ponto com nomes legíveis (não só IDs).

---

## Fase 12 — Ações de fila do Operador

### Etapa 12.1 — Ação "Rejeitar"

#### Objetivo
Dar ao operador um botão que barra o caminhão atual sem cancelar a ordem definitivamente — decide-se **DIVERGÊNCIA**, não `CANCELADO` (cancelamento continua exclusivo de EXPEDICAO/ADMIN via `PerfilUsuario::podeCancelarOrdem()`, que não muda).

#### Escopo
- Novo case `TipoDivergencia::REJEITADO_PELO_OPERADOR` (label "Rejeitado pelo operador").
- `POST /api/v1/ordens-carregamento/{ordemCarregamento}/rejeitar`: body `{ "descricao": string (obrigatório, min:5) }`; controller chama `RegistrarDivergenciaAction::execute($ordem, TipoDivergencia::REJEITADO_PELO_OPERADOR, OrigemEvento::APP_OPERADOR, $descricao, usuarioId: auth()->id(), usuarioNome: auth()->user()->name)`.
- Autorização: usuário deve ter `perfil->podeIniciarCarregamento()` (mesmo grupo que já inicia/conclui carregamento: OPERADOR/EXPEDICAO/ADMIN) **e** `$ordem->ponto_carregamento_id === auth()->user()->ponto_carregamento_id` (reforça RN-004/RN-010 abaixo).
- Ordem só pode ser rejeitada se estiver em `AGUARDANDO_CARREGAMENTO` ou `EM_CARREGAMENTO` (mesmas transições já permitidas para `DIVERGENCIA` no `StatusOrdem`).

#### Arquivos criados/alterados
- `app/Domain/Carregamento/Enums/TipoDivergencia.php`
- `app/Http/Controllers/Api/V1/OrdemCarregamentoController.php` (novo método `rejeitar`)
- `routes/api.php`

#### Critérios de aceite
- [ ] `POST .../rejeitar` sem `descricao` retorna `422`.
- [ ] Ordem rejeitada vai para `DIVERGENCIA`, nunca `CANCELADO`.
- [ ] Evento registrado em `eventos_ordem_carregamento` com `origem = APP_OPERADOR`.
- [ ] Operador de outro ponto recebe `403`.
- [ ] Divergência criada aparece em `GET /api/v1/divergencias` com `tipo = REJEITADO_PELO_OPERADOR`.

---

### Etapa 12.2 — Confirmar contrato de "Carregado" e "Próximo" (sem código novo)

#### Objetivo
Documentar, para o app Flutter, que estas duas ações **já existem** — evitar retrabalho.

#### Escopo
- "Carregado" = `POST /api/v1/ordens-carregamento/{ordemCarregamento}/concluir` (transiciona `EM_CARREGAMENTO` → `CARREGAMENTO_CONCLUIDO`).
- "Próximo da fila" = `POST /api/v1/fila-carregamento/{ordemCarregamento}/liberar` (transiciona a próxima ordem `AGUARDANDO_CARREGAMENTO` → `EM_CARREGAMENTO`; é neste momento que o motorista deve ser notificado via realtime — ver Fase 14.2).

#### Arquivos criados/alterados
Nenhum (documentação apenas).

#### Critérios de aceite
- [ ] `docs/api.md` documenta os três botões do operador (rejeitar/carregado/próximo) com método, rota e payload.

---

### RN-009 e RN-010 (novas)

**RN-009 — Rejeitar nunca cancela direto.** O botão "Rejeitar" do operador sempre transiciona a ordem para `DIVERGENCIA` com `descricao` obrigatória. Cancelamento definitivo (`CANCELADO`) continua restrito a `EXPEDICAO`/`ADMIN`.

**RN-010 — Ações de fila restritas ao ponto do operador.** `rejeitar`, `concluir` e `fila-carregamento/{ordem}/liberar` só podem ser executadas por usuário cujo `ponto_carregamento_id` seja igual ao `ponto_carregamento_id` da ordem (reforça RN-004).

---

## Fase 13 — Chat operador ↔ motorista (por ordem)

### Etapa 13.1 — Domínio `Chat`: model e migration

#### Objetivo
Criar a estrutura de dados de uma conversa 1:1 atrelada a uma ordem de carregamento.

#### Escopo
- Migration `create_mensagens_chat_table`: `id` (bigint), `ordem_carregamento_id` (uuid, FK `ordens_carregamento`, `cascadeOnDelete`), `remetente_id` (FK `users`), `perfil_remetente` (string — snapshot do perfil no momento do envio, evita depender de join para saber se foi operador ou motorista), `mensagem` (text), `lida_em` (timestamp nullable), `created_at`/`updated_at`.
- Model `App\Domain\Chat\Models\MensagemChat` (`$fillable`, `belongsTo(OrdemCarregamento::class)`, `belongsTo(User::class, 'remetente_id')`).
- Relação `OrdemCarregamento::mensagensChat(): HasMany`.

#### Arquivos criados/alterados
- `database/migrations/2026_XX_XX_create_mensagens_chat_table.php`
- `app/Domain/Chat/Models/MensagemChat.php`
- `app/Domain/Carregamento/Models/OrdemCarregamento.php`

#### Critérios de aceite
- [ ] Migration roda sem erro.
- [ ] `MensagemChat::create(...)` funciona com FK válida.
- [ ] `$ordem->mensagensChat` retorna coleção ordenada por `created_at`.

---

### Etapa 13.2 — Endpoints de mensagens

#### Objetivo
Permitir listar e enviar mensagens de uma ordem.

#### Escopo
- `GET /api/v1/ordens-carregamento/{ordemCarregamento}/mensagens`: lista paginada (mais antiga → mais recente), autorizado apenas para (a) operador cujo `ponto_carregamento_id` bate com o da ordem, ou (b) `auth()->id() === $ordem->motorista_user_id`.
- `POST /api/v1/ordens-carregamento/{ordemCarregamento}/mensagens`: body `{ "mensagem": string (obrigatório, max:1000) }`; mesma autorização; bloqueado (`422`) se `!$ordem->status->estaAtivo()` (RN-011); grava `perfil_remetente = auth()->user()->perfil->value`; dispara evento `MensagemEnviada` (Fase 13.3).
- Extrair a checagem de autorização acima (operador do ponto OU motorista da ordem) para um método reutilizável, já que Fase 12.1 e Fase 14.1 usam a mesma regra.

#### Arquivos criados/alterados
- `app/Http/Controllers/Api/V1/ChatController.php` (novo)
- `routes/api.php`
- `app/Domain/Carregamento/Models/OrdemCarregamento.php` (método `usuarioPodeAcessar(User $user): bool`, reutilizável)

#### Critérios de aceite
- [ ] Operador do ponto certo lê e envia mensagens.
- [ ] Motorista vinculado lê e envia mensagens.
- [ ] Terceiro usuário (perfil errado ou ponto errado) recebe `403`.
- [ ] Envio em ordem `FINALIZADO`/`CANCELADO` retorna `422`.

---

### Etapa 13.3 — Evento broadcast `MensagemEnviada`

#### Objetivo
Entregar mensagens em tempo real para o outro lado da conversa.

#### Escopo
- `App\Domain\Chat\Events\MensagemEnviada implements ShouldBroadcast`: `broadcastOn()` retorna `new PrivateChannel("ordem.{$ordem->id}.chat")`; `broadcastAs()` = `mensagem.enviada`; `broadcastWith()` inclui `id`, `ordem_id`, `remetente_id`, `perfil_remetente`, `mensagem`, `created_at`.
- Disparar o evento dentro do controller/service de envio (Etapa 13.2).

#### Arquivos criados/alterados
- `app/Domain/Chat/Events/MensagemEnviada.php`

#### Critérios de aceite
- [ ] Evento dispara ao enviar mensagem.
- [ ] Canal é privado (`PrivateChannel`, não `Channel`) — depende da Fase 14.1 para autenticação funcionar de ponta a ponta.

---

### RN-011 (nova)

**RN-011 — Chat só ativo com ordem ativa.** Só é possível enviar mensagem em `mensagens_chat` quando `ordem.status->estaAtivo() === true`. Mensagens de ordens `FINALIZADO`/`CANCELADO` continuam legíveis (histórico), mas o endpoint de envio (`POST`) retorna `422`.

---

## Fase 14 — Realtime privado (Reverb)

### Etapa 14.1 — Registrar autenticação de canais privados

#### Objetivo
Hoje `routes/channels.php` define canal privado (`App.Models.User.{id}`), mas **não existe `Broadcast::routes()` registrado** — o endpoint `/broadcasting/auth` não existe, então nenhum canal privado autentica de fato. Sem isso, chat e notificação de liberação ao motorista não funcionam.

#### Escopo
- Registrar `Broadcast::routes(['middleware' => ['auth:sanctum']])` — em Laravel 12 (sem Jetstream), isso normalmente vai em `routes/api.php` dentro do grupo `auth:sanctum`, ou em um `AppServiceProvider::boot()`. Verificar se o projeto tem `BroadcastServiceProvider` registrado em `bootstrap/providers.php`; se não tiver, criar e registrar.
- Adicionar em `routes/channels.php`:
  ```php
  Broadcast::channel('ordem.{ordemId}.chat', function ($user, $ordemId) {
      $ordem = OrdemCarregamento::find($ordemId);
      return $ordem && $ordem->usuarioPodeAcessar($user); // método da Etapa 13.2
  });
  ```

#### Arquivos criados/alterados
- `bootstrap/providers.php` (se precisar registrar `BroadcastServiceProvider`)
- `app/Providers/BroadcastServiceProvider.php` (se não existir)
- `routes/channels.php`

#### Critérios de aceite
- [ ] `POST /broadcasting/auth` (com token Sanctum válido) responde `200` para canal autorizado.
- [ ] Responde `403` para usuário sem acesso à ordem.
- [ ] Canal `App.Models.User.{id}` (já existente) continua funcionando.

---

### Etapa 14.2 — Notificar motorista na liberação ("próximo" do operador)

#### Objetivo
Quando o operador libera o próximo caminhão da fila (`fila-carregamento/{ordem}/liberar`, ordem vai para `EM_CARREGAMENTO`), o motorista daquela ordem deve saber em tempo real que pode se posicionar.

#### Escopo
- Alterar `OrdemStatusAlterado::broadcastOn()`: se `$this->ordem->motorista_user_id` não for nulo, adicionar `new PrivateChannel("App.Models.User.{$this->ordem->motorista_user_id}")` à lista de canais (mantendo os públicos `ordens`/`ponto.{id}` já existentes).
- `broadcastWith()` já inclui `status`/`status_label`; o app do motorista escuta esse canal e, ao ver `status === 'EM_CARREGAMENTO'`, exibe "Pode se posicionar".

#### Arquivos criados/alterados
- `app/Domain/Carregamento/Events/OrdemStatusAlterado.php`

#### Critérios de aceite
- [ ] Motorista vinculado recebe o evento no canal privado próprio ao ser liberado.
- [ ] Motorista de outra ordem não recebe o evento.
- [ ] Canais públicos existentes (painel web) continuam recebendo normalmente.

---

## Fase 15 — Testes e homologação

### Etapa 15.1 — Testes automatizados

#### Objetivo
Cobrir as regras novas com testes de domínio/feature.

#### Escopo
- Rejeitar sempre gera `DIVERGENCIA`, nunca `CANCELADO` (RN-009).
- Operador de outro ponto não rejeita/conclui/libera ordem alheia (RN-010).
- Motorista só enxerga a própria ordem em `GET /motorista/minha-ordem`.
- Chat: operador do ponto certo e motorista vinculado conseguem ler/enviar; terceiros recebem `403`; envio bloqueado em ordem finalizada/cancelada (RN-011).
- `Broadcast::routes()`/canal `ordem.{id}.chat` nega usuário não autorizado.

#### Critérios de aceite
- [ ] Testes cobrindo os pontos acima passam (`php artisan test`).

### Etapa 15.2 — Roteiro de homologação manual (ponta a ponta)

#### Fluxo
```text
Cadastrar User motorista (perfil MOTORISTA, documento = CPF)
→ Ordem criada com motorista_documento igual ao CPF cadastrado
→ Ordem resolve motorista_user_id automaticamente
→ Operador vê ordem na fila (app operador)
→ Operador aperta "Próximo" → ordem EM_CARREGAMENTO
→ Motorista recebe notificação em tempo real ("pode se posicionar")
→ Operador e motorista trocam mensagem no chat da ordem (nos dois sentidos)
→ Operador aperta "Carregado" → ordem CARREGAMENTO_CONCLUIDO
→ (alternativo) Operador aperta "Rejeitar" com motivo → ordem DIVERGENCIA, chat continua ativo até resolução
```

#### Critérios de aceite
- [ ] Fluxo completo executa sem erro.
- [ ] Eventos gravados em `eventos_ordem_carregamento` em cada transição.
- [ ] Chat entrega mensagem em tempo real nos dois sentidos.

---

## Documentação a atualizar por fase

| Fase | Atualizar |
|---|---|
| 11 | `docs/regras-negocio.md` (schema), `docs/DECISOES_TECNICAS.md` (DT-009), `docs/api.md` |
| 12 | `docs/regras-negocio.md` (RN-009, RN-010), `docs/api.md` |
| 13 | `docs/regras-negocio.md` (RN-011), `docs/api.md`, novo `docs/chat.md` (opcional) |
| 14 | `docs/DECISOES_TECNICAS.md` (DT-010), `docs/api.md` |
| 15 | `docs/STATUS.md`, `docs/CHANGELOG.md`, `docs/ROADMAP.md` (marcar Fases 11–15) |

### DT-009 (rascunho)
**Decisão:** Motorista é `User` cadastrado (`perfil = MOTORISTA`), vinculado à ordem via `motorista_user_id`, resolvido automaticamente por `documento` (CPF). **Motivo:** permitir autenticação Sanctum própria, canal privado individual e histórico por motorista, mantendo compatibilidade com o texto solto vindo de Protheus/Guardian. **Impacto:** `users.documento` novo, `ordens_carregamento.motorista_user_id` novo, resolução automática não bloqueia ordem se motorista não tiver cadastro.

### DT-010 (rascunho)
**Decisão:** Canais privados Reverb (`Broadcast::routes()` + `PrivateChannel`) autenticados via Sanctum passam a ser usados para chat (`ordem.{id}.chat`) e notificação individual ao motorista (`App.Models.User.{id}`). O app Flutter migra de `web_socket_channel` cru para um cliente que fale o protocolo Pusher/Echo (ex.: `pusher_channels_flutter`). **Motivo:** canais públicos (DT-004) não suportam autorização por usuário, necessária para chat 1:1 e notificação individual. **Impacto:** estende DT-004 (mantém Reverb/Redis como transporte), não a substitui — canais públicos existentes (`ordens`, `ponto.{id}`) continuam como estão.
