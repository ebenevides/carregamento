# API — Carregamento

## Autenticação

### `POST /api/v1/auth/login`

Retorna token Sanctum para o app Flutter.

```json
{
    "email": "user@example.com",
    "password": "secret"
}
```

Resposta `200`:
```json
{
    "token": "1|abc123...",
    "user": {
        "id": 1,
        "name": "João",
        "email": "user@example.com",
        "perfil": "OPERADOR",
        "ponto_carregamento_id": 3
    }
}
```

Todas as demais rotas exigem header `Authorization: Bearer {token}`.

---

## Ordens de carregamento

### `GET /api/v1/ordens-carregamento`

Lista paginada (30/pp). Filtros: `status`, `ticket`, `pedido`, `placa`, `produto_codigo`, `ponto_id`, `data_inicio`, `data_fim`.

### `POST /api/v1/ordens-carregamento`

Cria nova ordem via `CriarOrdemAction`. Body: `produto_codigo`, `quantidade_prevista`, `placa_veiculo`, + campos opcionais.

### `GET /api/v1/ordens-carregamento/{ordemCarregamento}`

Detalhe da ordem com `pilhaProduto`, `pontoCarregamento`, `operador`, `eventos`, `divergencias`.

---

## Ações de fila (expedição/admin)

### `POST /api/v1/fila-carregamento/{ordemCarregamento}/liberar`

**Fluxo expedição.** Libera ordem para a fila de carregamento.
- Valida RN-001 (ticket Guardian), RN-002 (tara), RN-003 (pilha+ponto), RN-005 (divergências abertas)
- Transição: `TARA_REALIZADA` → `AGUARDANDO_CARREGAMENTO`
- Chama `EntrarNaFilaAction`

### `GET /api/v1/fila-carregamento/{ordemCarregamento}/validar`

Valida se ordem está apta a entrar na fila. Retorna:

```json
{ "apto": true, "erros": [] }
```

### `GET /api/v1/fila-carregamento?ponto_carregamento_id=1`

Retorna a fila atual de um ponto.

### `GET /api/v1/operador/minha-fila`

Retorna a fila do ponto do operador autenticado.

---

## Ações do operador (3 botões)

### 1. "Próximo" — `POST /api/v1/ordens-carregamento/{ordemCarregamento}/iniciar`

Coloca ordem em carregamento.
- Transição: `AGUARDANDO_CARREGAMENTO` → `EM_CARREGAMENTO`
- Autorização: usuário com `perfil->podeIniciarCarregamento()` (OPERADOR/EXPEDICAO/ADMIN) **e** mesmo `ponto_carregamento_id`
- Valida RN-001 (ticket), RN-002 (tara), RN-005 (sem divergência aberta)
- Body: `operador_id` (int, obrigatório), `ponto_carregamento_id` (int, obrigatório), `equipamento_codigo` (string, opcional), `observacao` (string, opcional)

### 2. "Carregado" — `POST /api/v1/ordens-carregamento/{ordemCarregamento}/concluir`

Finaliza carregamento da ordem atual.
- Transição: `EM_CARREGAMENTO` → `CARREGAMENTO_CONCLUIDO`
- Autorização: usuário com `perfil->podeIniciarCarregamento()` **e** mesmo `ponto_carregamento_id`
- Body: `operador_id` (int, opcional), `observacao` (string, opcional)

### 3. "Rejeitar" — `POST /api/v1/ordens-carregamento/{ordemCarregamento}/rejeitar`

Rejeita caminhão — gera **divergência**, nunca cancelamento (RN-009).
- Transição: qualquer status que permita → `DIVERGENCIA`
- Autorização: usuário com `perfil->podeIniciarCarregamento()` **e** mesmo `ponto_carregamento_id` (RN-010)
- Body: `descricao` (string, obrigatório, min:5, max:1000)
- Resposta `201` com os dados da divergência criada

---

## Motorista

### `GET /api/v1/motorista/minha-ordem`

Retorna a ordem ativa do motorista autenticado (se houver).
- Autorização: apenas `perfil = MOTORISTA`
- Retorna `204` se nenhuma ordem ativa
- Ordernamentos ativos: todos exceto `CANCELADO` e `FINALIZADO`
- Resposta `200` inclui `ticket_guardian`, `produto_codigo`, `produto_descricao`, `quantidade_prevista`, `placa_veiculo`, `placa_carreta`, `status`, `status_label`, `pilha_produto` (id/codigo/descricao), `ponto_carregamento` (id/codigo/descricao), `peso_liquido`, `tara`, `peso_bruto`, `divergencias_abertas`.

---

## Divergências

### `GET /api/v1/divergencias`

Lista divergências. Filtros: `status`, `tipo`, `ordem_id`.

### `POST /api/v1/divergencias/{divergencia}/resolver`

Resolve divergência e libera ordem automaticamente.
- Autorização: `perfil->podeResolverDivergencia()` (EXPEDICAO/ADMIN)
- Body: `observacao` (string, opcional)
- Se ordem estava em `DIVERGENCIA`, retorna ao status anterior

---

## Pesagem final

### `POST /api/v1/ordens-carregamento/{ordemCarregamento}/pesagem-final`

Registra pesagem final.
- Body: `peso_bruto` (float, obrigatório)
- Transição: `AGUARDANDO_PESAGEM_FINAL` → `PESAGEM_FINAL_REALIZADA`
- Valida tolerância (RN-007): gera divergência `PESO_FORA_TOLERANCIA` se aplicar

### `POST /api/v1/ordens-carregamento/{ordemCarregamento}/liberar-faturamento`

Libera ordem para faturamento.
- Transição: `VALIDADO` → `FINALIZADO`

---

## Chat (por ordem)

Chat é escopado por ordem — cada `OrdemCarregamento` tem seu próprio canal, não há chat perfil-a-perfil livre. Acesso via `OrdemCarregamento::usuarioPodeAcessar($user)` (operador do ponto, motorista vinculado, expedição/admin).

### `GET /api/v1/ordens-carregamento/{ordemCarregamento}/mensagens`

Lista mensagens da ordem, paginado (50/pp), mais antiga → recente, com `remetente:id,name`.
- `403` se usuário não tem acesso à ordem.

### `POST /api/v1/ordens-carregamento/{ordemCarregamento}/mensagens`

Envia mensagem no chat da ordem.
- Body: `mensagem` (string, obrigatório, max:1000)
- `403` se usuário não tem acesso à ordem
- `422` se a ordem já está `FINALIZADO`/`CANCELADO` (`status->estaAtivo()` falso)
- Dispara evento `MensagemEnviada` (broadcast) ao criar
- Resposta `201` com a mensagem criada

### Broadcast — canal privado `ordem.{ordemId}.chat`

Autenticado via Sanctum (`routes/channels.php`), autorizado só se `OrdemCarregamento::usuarioPodeAcessar($user)`. Evento `mensagem.enviada` (Reverb), payload:
```json
{
    "id": 1,
    "ordem_id": "uuid-da-ordem",
    "remetente_id": 5,
    "perfil_remetente": "OPERADOR",
    "mensagem": "Chegando na bica 1",
    "created_at": "2026-07-17T10:42:52.973000Z"
}
```

Motorista também recebe notificação individual no canal privado `App.Models.User.{id}` (evento `ordem.status.alterado`). Realtime funciona em foreground via Reverb (ver DT-013); sem push nativo (FCM/APNs) — app em background/fechado não notifica.

---

## Integrações

### `GET /api/v1/integracoes/protheus/pedidos/{numero}`

Consulta pedido no Protheus (`GET {PROTHEUS_BASE_URL}/api/v1/faturamento/pedidos/{filial}/{numero}` — ver
`docs/protheus-api-pedidos.postman_collection.json`, contrato confirmado contra produção em 2026-07-19, DT-016).
- Query opcional: `filial` (default `01`)
- Resposta: cabeçalho do pedido + `cliente` + `itens[]`. **Placa/motorista vêm dentro de cada item**
  (`itens[].placa_veiculo`/`motorista_nome`/`motorista_documento`), não no cabeçalho — um pedido Protheus pode
  ter itens com veículo/motorista diferentes cada.
- `404` se pedido não existe na filial informada

### `GET /api/v1/integracoes/guardian/tickets/{ticket}`

Consulta ticket no Guardian.

### `GET /api/v1/integracoes/guardian/fila/{ticket}`

Consulta posição/estado do veículo na fila do Guardian (método SOAP `FilaConsultaVeiculo`).
- Query opcional: `placa` (desempate quando o Guardian tem mais de um ticket ativo pra mesma placa)
- Resposta: `sucesso` (bool, `Erro === 0`), `posicao`, `estado`/`estado_descricao` (códigos do Guardian, não
  documentados oficialmente — ver `docs/integracao-guardian.md`), `liberado` (bool, heurística por
  `estado_descricao` conter "liberado"), `fila_id`/`fila_codigo`/`fila_nome`, `fila_mensagem`,
  `mensagem_usuario`, `data_atualizacao`
- Uso manual/dashboard. A liberação automática de status roda separada, via
  `GuardianService::sincronizarFila()` (job `SincronizarFilaGuardianJob`, a cada 2min): ordens
  `TARA_REALIZADA` liberadas na fila do Guardian entram em `AGUARDANDO_CARREGAMENTO` sozinhas — ver DT-014.

---

## Cadastros

### Pontos de carregamento
`GET|POST /api/v1/pontos-carregamento` · `GET|PUT|DELETE /api/v1/pontos-carregamento/{pontoCarregamento}`
`POST .../{pontoCarregamento}/ativar` · `POST .../{pontoCarregamento}/inativar`

### Pilhas de produto
`GET|POST /api/v1/pilhas-produto` · `GET|PUT|DELETE /api/v1/pilhas-produto/{pilhaProduto}`
`POST .../{pilhaProduto}/ativar` · `POST .../{pilhaProduto}/inativar`

### Produto × Pilha × Ponto
`GET|POST /api/v1/produto-pilha-ponto` · `DELETE /api/v1/produto-pilha-ponto/{id}`

---

## Resumo das ações do operador no app

| Botão | Método | Rota | Transição |
|---|---|---|---|
| Próximo | POST | `/ordens-carregamento/{id}/iniciar` | `AGUARDANDO_CARREGAMENTO` → `EM_CARREGAMENTO` |
| Carregado | POST | `/ordens-carregamento/{id}/concluir` | `EM_CARREGAMENTO` → `CARREGAMENTO_CONCLUIDO` |
| Rejeitar | POST | `/ordens-carregamento/{id}/rejeitar` | → `DIVERGENCIA` |
