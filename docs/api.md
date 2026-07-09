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

## Integrações

### `GET /api/v1/integracoes/protheus/pedidos/{numero}`

Consulta pedido no Protheus.

### `GET /api/v1/integracoes/guardian/tickets/{ticket}`

Consulta ticket no Guardian.

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
