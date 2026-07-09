# Roteiro de Homologação — Ponta a Ponta

## Pré-requisitos

- Stack rodando (`docker compose up -d`)
- Migration executada (`php artisan migrate`)
- Vite build (`npm run build`)
- Mock Guardian e Mock Protheus ativados (`GUARDIAN_MOCK=true`, `PROTHEUS_MOCK=true`)

## 1. Cadastrar motorista

```bash
# Via tinker ou seed — criar User com perfil MOTORISTA e CPF
php artisan tinker
> $u = new App\Models\User; $u->name = 'João Motorista'; $u->email = 'joao@teste.com'; $u->password = bcrypt('password'); $u->perfil = App\Domain\Carregamento\Enums\PerfilUsuario::MOTORISTA; $u->documento = '12345678901'; $u->save();
```

## 2. Criar ordem com motorista_documento

```bash
curl -s -X POST http://localhost/api/v1/auth/login \
  -H 'Content-Type: application/json' \
  -d '{"email":"admin@teste.com","password":"password"}' | jq -r '.token'
# → salvar como TOKEN
```

```bash
# Criar ordem com o mesmo CPF do motorista
curl -s -X POST http://localhost/api/v1/ordens-carregamento \
  -H "Authorization: Bearer $TOKEN" \
  -H 'Content-Type: application/json' \
  -d '{
    "produto_codigo": "BRITA1",
    "quantidade_prevista": 32.0,
    "placa_veiculo": "ABC1D23",
    "motorista_documento": "12345678901",
    "ticket_guardian": "TK0001",
    "tara": 15.0
  }' | jq .
```

**Verificar:** `motorista_user_id` foi preenchido automaticamente.

## 3. Motorista vê a ordem ativa

```bash
# Login como motorista
TOKEN_MOT=$(curl -s -X POST http://localhost/api/v1/auth/login \
  -H 'Content-Type: application/json' \
  -d '{"email":"joao@teste.com","password":"password"}' | jq -r '.token')

curl -s http://localhost/api/v1/motorista/minha-ordem \
  -H "Authorization: Bearer $TOKEN_MOT" | jq .
```

**Verificar:** Retorna ordem com produto, pilha, ponto, status, placa.

## 4. Operador vê fila e puxa "Próximo"

```bash
# Login como operador do mesmo ponto
TOKEN_OP=$(curl -s -X POST http://localhost/api/v1/auth/login \
  -H 'Content-Type: application/json' \
  -d '{"email":"operador@teste.com","password":"password"}' | jq -r '.token')

# Ver fila
curl -s http://localhost/api/v1/operador/minha-fila \
  -H "Authorization: Bearer $TOKEN_OP" | jq '.data | length'

# "Próximo" — iniciar carregamento
curl -s -X POST "http://localhost/api/v1/ordens-carregamento/{ORDEM_ID}/iniciar" \
  -H "Authorization: Bearer $TOKEN_OP" \
  -H 'Content-Type: application/json' \
  -d '{"operador_id": 1, "ponto_carregamento_id": 1}' | jq .
```

**Verificar:** Status virou `EM_CARREGAMENTO`.

## 5. Chat operador ↔ motorista

```bash
# Operador envia mensagem
curl -s -X POST "http://localhost/api/v1/ordens-carregamento/{ORDEM_ID}/mensagens" \
  -H "Authorization: Bearer $TOKEN_OP" \
  -H 'Content-Type: application/json' \
  -d '{"mensagem": "Pode se posicionar na bica 1"}' | jq .

# Motorista responde
curl -s -X POST "http://localhost/api/v1/ordens-carregamento/{ORDEM_ID}/mensagens" \
  -H "Authorization: Bearer $TOKEN_MOT" \
  -H 'Content-Type: application/json' \
  -d '{"mensagem": "Chegando"}' | jq .

# Motorista lê histórico
curl -s "http://localhost/api/v1/ordens-carregamento/{ORDEM_ID}/mensagens" \
  -H "Authorization: Bearer $TOKEN_MOT" | jq '.data[].mensagem'
```

**Verificar:** Mensagens visíveis nos dois lados, ordenadas por data.

## 6. Operador "Carregado"

```bash
curl -s -X POST "http://localhost/api/v1/ordens-carregamento/{ORDEM_ID}/concluir" \
  -H "Authorization: Bearer $TOKEN_OP" \
  -H 'Content-Type: application/json' \
  -d '{"operador_id": 1}' | jq '.status'
```

**Verificar:** Status `CARREGAMENTO_CONCLUIDO` → `AGUARDANDO_PESAGEM_FINAL`.

## 7. Alternativo: operador "Rejeitar"

```bash
curl -s -X POST "http://localhost/api/v1/ordens-carregamento/{ORDEM_ID}/rejeitar" \
  -H "Authorization: Bearer $TOKEN_OP" \
  -H 'Content-Type: application/json' \
  -d '{"descricao": "Caminhão com problema na carroceria"}' | jq .
```

**Verificar:**
- Status virou `DIVERGENCIA` (nunca `CANCELADO`)
- Divergência criada com `tipo = REJEITADO_PELO_OPERADOR`
- Evento registrado com `origem = APP_OPERADOR`

**Resolver divergência (expedição):**
```bash
curl -s -X POST "http://localhost/api/v1/divergencias/{DIVERGENCIA_ID}/resolver" \
  -H "Authorization: Bearer $TOKEN_ADMIN" \
  -H 'Content-Type: application/json' \
  -d '{"observacao": "Autorizado após vistoria"}' | jq .
```

## Verificações de segurança

### RN-010 — Operador de outro ponto não acessa

```bash
# Login como operador de ponto diferente
TOKEN_OUTRO=$(curl -s -X POST http://localhost/api/v1/auth/login \
  -H 'Content-Type: application/json' \
  -d '{"email":"operador2@teste.com","password":"password"}' | jq -r '.token')

# Tentar rejeitar → 403
curl -s -o /dev/null -w "%{http_code}" -X POST "http://localhost/api/v1/ordens-carregamento/{ORDEM_ID}/rejeitar" \
  -H "Authorization: Bearer $TOKEN_OUTRO" \
  -H 'Content-Type: application/json' \
  -d '{"descricao": "teste"}'

# Tentar concluir → 403
curl -s -o /dev/null -w "%{http_code}" -X POST "http://localhost/api/v1/ordens-carregamento/{ORDEM_ID}/concluir" \
  -H "Authorization: Bearer $TOKEN_OUTRO"
```

### RN-011 — Chat bloqueado após finalizado

Após ordem finalizada, tentar enviar mensagem → `422`.

### RN-009 — Rejeitar nunca cancela

Divergência resolvida retorna ordem ao status anterior, não para CANCELADO.

---

## Critérios de aceite

- [ ] Fluxo completo (cadastro motorista → ordem → fila → carregamento → chat → conclusão) executa sem erros
- [ ] Eventos registrados em `eventos_ordem_carregamento` em cada transição
- [ ] `motorista_user_id` resolvido automaticamente por documento
- [ ] Chat entrega visibilidade nos dois lados (operador e motorista)
- [ ] RN-009: rejeitar gera DIVERGENCIA, nunca CANCELADO
- [ ] RN-010: outro ponto recebe 403 nas três ações (rejeitar, concluir, liberar)
- [ ] RN-011: ordem finalizada/cancelada bloqueia envio de mensagem (422)
