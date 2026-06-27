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
| ADMINISTRADOR | ✓ | ✓ | ✓ | ✓ |
| SUPERVISOR | ✓ | ✓ | ✓ | ✓ |
| OPERADOR | ✓ | ✓ | ✗ | ✗ |
| VISUALIZADOR | ✗ | ✗ | ✗ | ✗ |

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
Protheus e Guardian não são acessados diretamente por frontend ou Flutter. Toda chamada passa pelos adapters em `app/Domain/Integrations/`.

## Tolerância de peso
- Padrão: **5%** (configurável por ordem via `tolerancia_percentual`)
- Cálculo: `abs(peso_liquido - quantidade_prevista) <= (quantidade_prevista * tolerancia_percentual / 100)`
- Unidade Guardian: **kg**
