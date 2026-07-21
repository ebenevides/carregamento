# Regras de Negócio — Projeto Carregamento

## Enums do domínio

### StatusOrdem
| Valor | Label | Transições permitidas |
|---|---|---|
| CRIADO | Criado | TARA_REALIZADA, DIVERGENCIA, CANCELADO |
| TARA_REALIZADA | Tara Realizada | AGUARDANDO_CARREGAMENTO, DIVERGENCIA, CANCELADO |
| AGUARDANDO_CARREGAMENTO | Aguardando Carregamento | EM_CARREGAMENTO, DIVERGENCIA, CANCELADO |
| EM_CARREGAMENTO | Em Carregamento | CARREGAMENTO_CONCLUIDO, DIVERGENCIA |
| CARREGAMENTO_CONCLUIDO | Carregamento Concluído | AGUARDANDO_PESAGEM_FINAL |
| AGUARDANDO_PESAGEM_FINAL | Aguardando Pesagem Final | PESAGEM_FINAL_REALIZADA, DIVERGENCIA |
| PESAGEM_FINAL_REALIZADA | Pesagem Final Realizada | VALIDADO, DIVERGENCIA |
| VALIDADO | Validado | FINALIZADO |
| DIVERGENCIA | Divergência | AGUARDANDO_CARREGAMENTO, CANCELADO |
| CANCELADO | Cancelado | *(terminal)* |
| FINALIZADO | Finalizado | *(terminal)* |

### PerfilUsuario e permissões
| Perfil | Iniciar carg. | Concluir carg. | Resolver diverg. | Cancelar ordem |
|---|:---:|:---:|:---:|:---:|
| ADMIN | ✓ | ✓ | ✓ | ✓ |
| EXPEDICAO | ✓ | ✓ | ✓ | ✓ |
| OPERADOR | ✓ | ✓ | ✗ | ✗ |
| MOTORISTA | ✗ | ✗ | ✗ | ✗ |

Fonte: `app/app/Domain/Carregamento/Enums/PerfilUsuario.php`. A capacidade declarada pelo enum não
garante que todo controller API já aplique a verificação; veja "Gaps de autorização" abaixo.

### Schema de dados relevante
- `users.documento` (string, nullable, unique): CPF do usuário, usado para casar motorista com ordem.
- `ordens_carregamento.motorista_user_id` (bigint, nullable, FK → users): vínculo opcional com o User motorista.

## Regras de negócio obrigatórias

### RN-001 — Ordem sem ticket não carrega
Nenhuma ordem pode ir para carregamento sem `ticket_guardian` preenchido.

### RN-002 — Ordem sem tara não carrega
Nenhuma ordem pode ser carregada sem `tara` registrada.

### RN-003 — Produto deve ter pilha e ponto
Todo produto deve ter `produto_pilha_ponto` configurado com `ativo=true`. Ausência gera `PILHA_SEM_PRODUTO`.

### RN-004 — Operador só vê sua fila
Operador (`PerfilUsuario::OPERADOR`) visualiza apenas ordens do seu `ponto_carregamento_id`.

### RN-005 — Divergência bloqueia carregamento
Ordem com `divergencias_carregamento.status = ABERTA` não pode transicionar para `EM_CARREGAMENTO`.

### RN-006 — Mudança de status gera evento
Toda alteração de status cria registro em `eventos_ordem_carregamento` com `usuario_id`, `origem`, `ocorrido_em`, `status_anterior`, `status_novo`.

### RN-007 — Peso final validado com tolerância
`|peso_liquido - quantidade_prevista| <= (quantidade_prevista * tolerancia_percentual / 100)`. Padrão: 5%. Fora gera `PESO_FORA_TOLERANCIA`.

### RN-008 — Integrações externas só via backend
Protheus e Guardian não são acessados diretamente por frontend ou Flutter. Toda chamada passa pelos adapters em `app/app/Domain/Integrations/`.

### RN-009 — Rejeição operacional gera divergência
Rejeitar uma ordem cria divergência `REJEITADO_PELO_OPERADOR` e transiciona para `DIVERGENCIA`;
rejeição não equivale a cancelamento. Fonte:
`app/app/Http/Controllers/Api/V1/OrdemCarregamentoController.php` (`rejeitar`).

### RN-010 — Operador atua no próprio ponto
Ao rejeitar e concluir, o backend exige perfil capaz de iniciar carregamento e o mesmo
`ponto_carregamento_id` da ordem. Iniciar ainda tem gap descrito abaixo.

### RN-011 — Chat restrito aos participantes da ordem
Motorista vinculado ou `ADMIN`/`EXPEDICAO`/`OPERADOR` do mesmo ponto podem acessar o chat. Ordem terminal
(`FINALIZADO` ou `CANCELADO`) não aceita nova mensagem. Fontes:
`app/app/Domain/Carregamento/Models/OrdemCarregamento.php` (`usuarioPodeAcessar`) e
`app/app/Http/Controllers/Api/V1/ChatController.php`.

*(Nota: RN-009, RN-010 e RN-011 já existem — Rejeitar sempre DIVERGENCIA, ações de fila restritas ao ponto do
operador, chat só ativo com ordem ativa — mas ainda não tinham sido retroportadas pra este arquivo; ver
`docs/CLAUDE_ROADMAP_OPERADOR_MOTORISTA.md` e `docs/api.md`.)*

### RN-012 — Resolução de pilha/ponto por UB (unidade de britagem)
Produtos cadastrados em mais de uma UB (ex.: BRITA 01 FINA em UB1 e UB2) resolvem para a pilha/ponto da UB
correta usando o campo UB do ticket Guardian (`CamposAdicionais.Numero=2`/`1002`), não o `produto_codigo`
sozinho. Sem UB identificável (ticket ainda não vinculado, ou produto exclusivo de uma UB), cai no
comportamento padrão: primeira pilha ativa/`padrao=true` encontrada pro código. Ver DT-017.

## Tolerância de peso
- Padrão: **5%** (configurável por ordem via `tolerancia_percentual`)
- Cálculo: `abs(peso_liquido - quantidade_prevista) <= (quantidade_prevista * tolerancia_percentual / 100)`
- Unidade Guardian: **kg**

## Gaps identificados em testes (2026-07-15, cenário de testes mobile operador/motorista)

### Motorista com múltiplas ordens ativas simultâneas — regra indefinida
`GET /motorista/minha-ordem` assume implicitamente que um motorista tem no máximo uma ordem ativa
por vez. Em teste manual (dados de `TestDataSeeder`, cenário artificial), um motorista com duas
ordens ativas recebe a mais nova por `created_at` (`latest()->first()`). Falta regra explícita que
proíba simultaneidade ou defina prioridade operacional além desse critério técnico. Fonte:
`app/app/Http/Controllers/Api/V1/MotoristaController.php`.

### Gaps de autorização da API

O grupo `auth:sanctum` autentica as rotas, mas vários endpoints ainda não verificam a capacidade de
`PerfilUsuario`. Em especial, criação de ordem/cadastros, pesagem, faturamento e resolução de
divergência precisam de hardening antes de exposição não confiável. `iniciar` aceita `operador_id`
e `ponto_carregamento_id` do body; a Action valida o ponto informado contra a ordem, mas o controller
não força o usuário autenticado nem seu ponto. Trate isso como débito conhecido, não como autorização
já implementada. Fontes: `app/routes/api.php`, controllers `Api/V1` e Form Requests.
