# CLAUDE.md — Roadmap de Execução do Projeto de Carregamento

## Projeto
Integração dos pontos de carregamento, pilhas de produto, operador de pá carregadeira, Protheus, Prix Guardian e painel operacional.

## Objetivo Geral
Construir uma solução operacional para controlar o carregamento de caminhões em pilhas de produto, garantindo que:

- O pedido/contrato venha do Protheus.
- O ticket, tara e pesagem venham do Prix Guardian.
- O backend consolide as informações.
- O operador de pá receba apenas a fila e os dados relevantes para carregar corretamente.
- A operação registre eventos, divergências e evidências.
- O painel web acompanhe o processo em tempo real ou quase real.

---

# 1. Instruções Gerais para o Claude Code

## 1.1 Forma de trabalho obrigatória

Execute o projeto de forma incremental, por etapas pequenas, simples e encadeadas.

A cada etapa concluída, atualize obrigatoriamente:

1. O arquivo `docs/ROADMAP.md`.
2. O arquivo `docs/STATUS.md`.
3. A documentação técnica relacionada à etapa.
4. O checklist de critérios de aceite.
5. O histórico de decisões técnicas, quando houver alteração relevante.

Não avance para uma nova etapa sem deixar a etapa anterior documentada.

---

## 1.2 Padrão de entrega por etapa

Cada etapa deve conter:

```md
## Etapa X — Nome da etapa

### Objetivo
Descrição curta do objetivo.

### Escopo
O que será implementado.

### Fora do escopo
O que não será feito nesta etapa.

### Arquivos criados/alterados
Lista dos arquivos.

### Critérios de aceite
- [ ] Critério 1
- [ ] Critério 2

### Status
Pendente | Em andamento | Concluído | Bloqueado

### Observações
Decisões, pendências, riscos ou dúvidas.
```

---

## 1.3 Regras de desenvolvimento

- Não criar funcionalidades grandes em uma única etapa.
- Não misturar backend, frontend, integração e app na mesma etapa sem necessidade.
- Criar primeiro estrutura, depois regras, depois telas, depois integrações.
- Priorizar código simples, testável e documentado.
- Evitar acoplamento direto com Protheus e Guardian nas telas.
- Toda comunicação externa deve passar por services/adapters.
- Toda mudança de status da ordem de carregamento deve gerar evento.
- Toda divergência deve ficar registrada.
- Nenhuma regra crítica deve ficar apenas no frontend.

---

# 2. Estrutura Recomendada do Repositório

Caso o projeto seja Laravel + Vue/Inertia + PostgreSQL, usar estrutura semelhante:

```text
app/
  Actions/
  DTOs/
  Enums/
  Events/
  Http/
    Controllers/
    Requests/
    Resources/
  Integrations/
    Protheus/
    Guardian/
  Models/
  Policies/
  Services/
    Carregamento/
    Fila/
    Divergencia/
    Pesagem/
  Support/

database/
  migrations/
  seeders/

resources/
  js/
    Pages/
      Dashboard/
      Operador/
      OrdensCarregamento/
      PontosCarregamento/
      PilhasProduto/
      Divergencias/
    Components/
    Layouts/

routes/
  web.php
  api.php

docs/
  arquitetura.md
  regras-negocio.md
  integracao-protheus.md
  integracao-guardian.md
  api.md
  app-operador.md
  painel-operacional.md
  testes.md

docs/ROADMAP.md
docs/STATUS.md
docs/DECISOES_TECNICAS.md
docs/CHANGELOG.md
```

---

# 3. Documentos Obrigatórios

## 3.1 docs/ROADMAP.md

Deve conter a visão por fases, status e percentual aproximado.

Modelo:

```md
# ROADMAP — Projeto Carregamento

| Fase | Descrição | Status | Progresso |
|---|---|---|---:|
| 0 | Preparação do projeto | Pendente | 0% |
| 1 | Modelo operacional | Pendente | 0% |
| 2 | Cadastros básicos | Pendente | 0% |
| 3 | Ordem de carregamento | Pendente | 0% |
| 4 | Fila de carregamento | Pendente | 0% |
| 5 | App operador | Pendente | 0% |
| 6 | Painel operacional | Pendente | 0% |
| 7 | Integração Protheus | Pendente | 0% |
| 8 | Integração Guardian | Pendente | 0% |
| 9 | Testes e homologação | Pendente | 0% |
```

---

## 3.2 docs/STATUS.md

Deve registrar o andamento atual.

Modelo:

```md
# STATUS — Projeto Carregamento

## Status atual
Em planejamento.

## Última etapa concluída
Nenhuma.

## Etapa atual
Etapa 0 — Preparação do projeto.

## Próxima etapa
Etapa 1 — Modelo operacional.

## Pendências
- Definir dados reais do Protheus.
- Definir forma de integração com Guardian.
- Definir produtos, pilhas e pontos de carregamento.

## Riscos
- Instabilidade ou ausência de API do Guardian.
- Regras de negócio não formalizadas.
- Conectividade ruim no pátio.

## Última atualização
YYYY-MM-DD HH:mm
```

---

## 3.3 docs/DECISOES_TECNICAS.md

Registrar decisões importantes.

Modelo:

```md
# DECISÕES TÉCNICAS

## DT-001 — Backend centralizado

### Decisão
O backend será responsável por orquestrar Protheus, Guardian, painel e app operador.

### Motivo
Evitar que frontend ou app consultem diretamente sistemas externos.

### Impacto
Maior rastreabilidade e menor acoplamento.

### Data
YYYY-MM-DD
```

---

# 4. Domínio do Projeto

## 4.1 Conceitos principais

### Ordem de carregamento
Registro central do processo. Une pedido Protheus, ticket Guardian, veículo, motorista, produto, pilha, ponto de carregamento, status e eventos.

### Ponto de carregamento
Local físico onde o caminhão será carregado, geralmente associado a uma pá carregadeira e operador.

### Pilha de produto
Local físico onde está armazenado o produto a ser carregado.

### Fila de carregamento
Lista ordenada de caminhões aguardando carregamento por ponto, pilha ou produto.

### Divergência
Qualquer inconsistência ou problema operacional que impeça ou questione a continuidade normal do processo.

---

## 4.2 Status da ordem de carregamento

Usar enum ou equivalente com os seguintes status iniciais:

```text
CRIADO
TARA_REALIZADA
AGUARDANDO_CARREGAMENTO
EM_CARREGAMENTO
CARREGAMENTO_CONCLUIDO
AGUARDANDO_PESAGEM_FINAL
PESAGEM_FINAL_REALIZADA
VALIDADO
DIVERGENCIA
CANCELADO
FINALIZADO
```

Toda alteração de status deve criar evento em `eventos_ordem_carregamento`.

---

# 5. Roadmap de Execução Encadeado

## Fase 0 — Preparação do Projeto

### Etapa 0.1 — Criar documentação base

#### Objetivo
Criar arquivos iniciais de acompanhamento do projeto.

#### Escopo
Criar:

- `docs/ROADMAP.md`
- `docs/STATUS.md`
- `docs/DECISOES_TECNICAS.md`
- `docs/CHANGELOG.md`
- Pasta `docs/`

#### Critérios de aceite
- [ ] Arquivos criados.
- [ ] Roadmap inicial preenchido.
- [ ] Status inicial preenchido.
- [ ] Decisão técnica inicial registrada.

#### Atualização obrigatória
Atualizar `docs/STATUS.md` com conclusão da etapa.

---

### Etapa 0.2 — Criar estrutura base do projeto

#### Objetivo
Preparar a estrutura técnica inicial.

#### Escopo
- Configurar projeto backend.
- Configurar banco PostgreSQL.
- Configurar `.env.example`.
- Configurar migrations iniciais vazias ou base.
- Configurar autenticação se aplicável.

#### Critérios de aceite
- [ ] Projeto executa localmente.
- [ ] Banco conecta corretamente.
- [ ] `.env.example` documentado.
- [ ] Comando de migração executa sem erro.

---

## Fase 1 — Modelo Operacional

### Etapa 1.1 — Criar enums e constantes do domínio

#### Objetivo
Formalizar os status, tipos de evento, tipos de divergência e perfis.

#### Escopo
Criar enums ou constantes para:

- Status da ordem.
- Tipo de evento.
- Tipo de divergência.
- Origem do evento.
- Perfil do usuário operacional.

#### Critérios de aceite
- [ ] Status padronizados.
- [ ] Eventos padronizados.
- [ ] Divergências padronizadas.
- [ ] Documentação atualizada em `docs/regras-negocio.md`.

---

### Etapa 1.2 — Criar migrations principais

#### Objetivo
Criar a estrutura inicial do banco de dados.

#### Escopo
Criar tabelas:

- `pontos_carregamento`
- `pilhas_produto`
- `produto_pilha_ponto`
- `ordens_carregamento`
- `eventos_ordem_carregamento`
- `divergencias_carregamento`
- `equipamentos`
- `operadores_pontos`

#### Critérios de aceite
- [ ] Migrations criadas.
- [ ] Relacionamentos definidos.
- [ ] Índices principais criados.
- [ ] Soft delete avaliado onde necessário.
- [ ] Documentação do modelo atualizada.

---

### Etapa 1.3 — Criar models e relacionamentos

#### Objetivo
Criar models do domínio operacional.

#### Escopo
Criar models para cada tabela principal e configurar relacionamentos.

#### Critérios de aceite
- [ ] Models criados.
- [ ] Fillables/casts definidos.
- [ ] Relacionamentos funcionando.
- [ ] Factories criadas quando aplicável.

---

## Fase 2 — Cadastros Operacionais

### Etapa 2.1 — CRUD de pontos de carregamento

#### Objetivo
Permitir cadastrar e manter pontos físicos de carregamento.

#### Escopo
- Listar pontos.
- Criar ponto.
- Editar ponto.
- Ativar/inativar ponto.
- API ou tela web simples.

#### Critérios de aceite
- [ ] Ponto pode ser criado.
- [ ] Ponto pode ser editado.
- [ ] Ponto pode ser inativado.
- [ ] Validação impede código duplicado.

---

### Etapa 2.2 — CRUD de pilhas de produto

#### Objetivo
Permitir cadastrar pilhas associadas a produtos.

#### Escopo
- Listar pilhas.
- Criar pilha.
- Editar pilha.
- Ativar/inativar pilha.
- Vincular produto Protheus.

#### Critérios de aceite
- [ ] Pilha pode ser criada.
- [ ] Pilha pode ser vinculada a produto.
- [ ] Pilha pode ser vinculada a ponto de carregamento.
- [ ] Pilha inativa não entra na fila operacional.

---

### Etapa 2.3 — Vínculo produto x pilha x ponto

#### Objetivo
Definir para qual pilha e ponto cada produto deve ser direcionado.

#### Escopo
- Cadastrar produto Protheus.
- Cadastrar pilha padrão.
- Cadastrar ponto padrão.
- Permitir múltiplas pilhas por produto, se necessário.

#### Critérios de aceite
- [ ] Produto possui ponto padrão.
- [ ] Produto possui pilha padrão.
- [ ] Sistema consegue resolver automaticamente o ponto pela ordem.
- [ ] Documentação da regra atualizada.

---

## Fase 3 — Ordem de Carregamento

### Etapa 3.1 — Criar serviço de criação da ordem

#### Objetivo
Criar ordem de carregamento a partir dos dados mínimos do pedido e ticket.

#### Escopo
Criar service responsável por receber:

- Empresa/filial.
- Pedido.
- Item.
- Contrato, se houver.
- Ticket Guardian.
- Cliente.
- Produto.
- Quantidade prevista.
- Placa.
- Motorista.
- Tara.

#### Critérios de aceite
- [ ] Ordem criada com status correto.
- [ ] Produto resolve pilha e ponto automaticamente.
- [ ] Evento `ORDEM_CRIADA` registrado.
- [ ] Evento `TARA_REALIZADA` registrado quando tara existir.
- [ ] Dados obrigatórios validados.

---

### Etapa 3.2 — Consultar ordem de carregamento

#### Objetivo
Permitir buscar ordens por ID, ticket, pedido ou placa.

#### Escopo
Criar endpoints:

```http
GET /api/v1/ordens-carregamento
GET /api/v1/ordens-carregamento/{id}
```

Filtros:

- Status.
- Ticket.
- Pedido.
- Placa.
- Produto.
- Ponto de carregamento.
- Período.

#### Critérios de aceite
- [ ] Consulta por ID funciona.
- [ ] Consulta por ticket funciona.
- [ ] Consulta por placa funciona.
- [ ] Filtros retornam dados corretos.

---

### Etapa 3.3 — Alterar status da ordem com evento

#### Objetivo
Centralizar mudança de status.

#### Escopo
Criar service/método:

```text
alterarStatus(ordem, novoStatus, usuario, origem, observacao)
```

#### Critérios de aceite
- [ ] Status é alterado corretamente.
- [ ] Evento é criado automaticamente.
- [ ] Não permite transição inválida.
- [ ] Regras documentadas.

---

## Fase 4 — Fila de Carregamento

### Etapa 4.1 — Criar consulta de fila por ponto

#### Objetivo
Permitir que cada ponto de carregamento tenha sua fila.

#### Escopo
Criar endpoint:

```http
GET /api/v1/fila-carregamento?ponto_carregamento_id=PC001
```

Retornar ordens com status:

- `AGUARDANDO_CARREGAMENTO`
- `EM_CARREGAMENTO`

#### Critérios de aceite
- [ ] Fila retorna somente ordens do ponto.
- [ ] Fila ordena por prioridade e horário.
- [ ] Ordens divergentes não aparecem como liberadas.
- [ ] Resposta contém dados necessários ao operador.

---

### Etapa 4.2 — Criar regras de entrada na fila

#### Objetivo
Garantir que somente ordens aptas entrem na fila.

#### Regras
Uma ordem só entra na fila se:

- Tiver pedido ou contrato válido.
- Tiver ticket Guardian.
- Tiver produto.
- Tiver pilha e ponto definidos.
- Tiver tara registrada.
- Não estiver em divergência.

#### Critérios de aceite
- [ ] Ordem sem ticket não entra.
- [ ] Ordem sem tara não entra.
- [ ] Produto sem pilha gera divergência.
- [ ] Regra documentada.

---

## Fase 5 — App/PWA do Operador de Pá

### Etapa 5.1 — Tela de login operacional

#### Objetivo
Permitir acesso do operador.

#### Escopo
- Login com usuário/senha ou PIN.
- Identificar perfil operador.
- Associar operador ao ponto de carregamento.

#### Critérios de aceite
- [ ] Operador autentica.
- [ ] Perfil é reconhecido.
- [ ] Ponto do operador é carregado.

---

### Etapa 5.2 — Tela minha fila

#### Objetivo
Mostrar ao operador os caminhões que ele deve carregar.

#### Escopo
Exibir:

- Posição.
- Placa.
- Motorista.
- Produto.
- Quantidade prevista.
- Cliente.
- Status.
- Tempo aguardando.

#### Critérios de aceite
- [ ] Mostra somente fila do operador.
- [ ] Atualiza manualmente.
- [ ] Mostra status claro.
- [ ] Layout é simples para tablet/celular.

---

### Etapa 5.3 — Tela detalhe da ordem

#### Objetivo
Permitir conferência antes do carregamento.

#### Escopo
Exibir:

- Ticket Guardian.
- Pedido Protheus.
- Cliente.
- Produto.
- Quantidade prevista.
- Placa.
- Motorista.
- Tara.
- Pilha.
- Ponto.
- Alertas.

#### Critérios de aceite
- [ ] Dados aparecem corretamente.
- [ ] Campos críticos têm destaque visual.
- [ ] Operador consegue voltar para fila.

---

### Etapa 5.4 — Ação iniciar carregamento

#### Objetivo
Registrar início do carregamento.

#### Escopo
Criar botão `Iniciar carregamento`.

Regras:

- Ordem deve estar em `AGUARDANDO_CARREGAMENTO`.
- Ordem deve pertencer ao ponto do operador.
- Não deve estar em divergência.

#### Critérios de aceite
- [ ] Status muda para `EM_CARREGAMENTO`.
- [ ] Evento é registrado.
- [ ] Horário de início é salvo.
- [ ] Bloqueia se ordem não estiver apta.

---

### Etapa 5.5 — Ação concluir carregamento

#### Objetivo
Registrar conclusão do carregamento.

#### Escopo
Criar botão `Concluir carregamento`.

Regras:

- Ordem deve estar em `EM_CARREGAMENTO`.
- Usuário deve ser operador ou supervisor.

#### Critérios de aceite
- [ ] Status muda para `CARREGAMENTO_CONCLUIDO`.
- [ ] Evento é registrado.
- [ ] Ordem sai da posição ativa da fila.
- [ ] Próxima etapa fica `AGUARDANDO_PESAGEM_FINAL` ou equivalente conforme regra definida.

---

### Etapa 5.6 — Registrar divergência pelo operador

#### Objetivo
Permitir que o operador bloqueie uma ordem com problema.

#### Escopo
Tela/modal para informar:

- Tipo de divergência.
- Descrição.
- Foto opcional em fase futura.

#### Critérios de aceite
- [ ] Divergência é criada.
- [ ] Ordem muda para `DIVERGENCIA`.
- [ ] Evento é registrado.
- [ ] Ordem deixa de aparecer como liberada para carregamento.

---

## Fase 6 — Painel Operacional Web

### Etapa 6.1 — Dashboard simples

#### Objetivo
Acompanhar operação do dia.

#### Escopo
Exibir contadores:

- Aguardando carregamento.
- Em carregamento.
- Carregamento concluído.
- Aguardando pesagem final.
- Divergências.
- Finalizados.

#### Critérios de aceite
- [ ] Indicadores carregam corretamente.
- [ ] Filtro por data funciona.
- [ ] Filtro por ponto funciona.

---

### Etapa 6.2 — Painel de divergências

#### Objetivo
Permitir supervisão e tratamento de divergências.

#### Escopo
- Listar divergências abertas.
- Abrir detalhe.
- Registrar solução.
- Liberar ou cancelar ordem.

#### Critérios de aceite
- [ ] Divergências abertas aparecem.
- [ ] Supervisor consegue resolver.
- [ ] Solução fica registrada.
- [ ] Evento de resolução é criado.

---

## Fase 7 — Integração Protheus

### Etapa 7.1 — Criar adapter Protheus mockado

#### Objetivo
Criar contrato técnico antes da integração real.

#### Escopo
Criar interface/service para consulta de pedido:

```text
consultarPedido(numero, filial)
```

Retorno esperado:

- Pedido.
- Item.
- Cliente.
- Produto.
- Quantidade.
- Transportadora.
- Veículo.
- Motorista.
- Status comercial.

#### Critérios de aceite
- [ ] Adapter mock retorna dados de teste.
- [ ] Backend cria ordem a partir do mock.
- [ ] Documentação da API Protheus esperada criada.

---

### Etapa 7.2 — Integrar Protheus real

#### Objetivo
Substituir mock por integração real.

#### Escopo
- Configurar URL.
- Configurar autenticação.
- Criar client HTTP.
- Tratar erros.
- Registrar logs.

#### Critérios de aceite
- [ ] Consulta pedido real.
- [ ] Trata pedido inexistente.
- [ ] Trata erro de autenticação.
- [ ] Logs não expõem senha/token.

---

## Fase 8 — Integração Prix Guardian

### Etapa 8.1 — Criar adapter Guardian mockado

#### Objetivo
Criar contrato técnico antes da integração real.

#### Escopo
Criar interface/service para:

- Consultar ticket.
- Consultar tara.
- Consultar peso final.
- Consultar status do ticket.

#### Critérios de aceite
- [ ] Adapter mock retorna ticket de teste.
- [ ] Ordem vincula ticket mockado.
- [ ] Documentação do contrato Guardian criada.

---

### Etapa 8.2 — Integrar Guardian real

#### Objetivo
Conectar ao Prix Guardian.

#### Escopo
- Configurar endpoint SOAP/REST.
- Configurar credenciais.
- Consultar ticket.
- Consultar tara.
- Consultar peso final.
- Preparar método de fila específica, se aplicável.

#### Critérios de aceite
- [ ] Ticket real consultado.
- [ ] Tara real capturada.
- [ ] Peso final capturado.
- [ ] Erros de comunicação tratados.
- [ ] Logs técnicos registrados.

---

## Fase 9 — Pesagem Final e Validação

### Etapa 9.1 — Atualizar ordem com peso final

#### Objetivo
Receber ou consultar peso final e atualizar a ordem.

#### Escopo
- Salvar peso bruto.
- Calcular peso líquido.
- Comparar com quantidade prevista.
- Aplicar tolerância.

#### Critérios de aceite
- [ ] Peso final salvo.
- [ ] Peso líquido calculado.
- [ ] Ordem validada se dentro da tolerância.
- [ ] Divergência criada se fora da tolerância.

---

### Etapa 9.2 — Liberar para faturamento

#### Objetivo
Marcar ordem como apta para faturamento.

#### Escopo
Regras mínimas:

- Pedido válido.
- Ticket válido.
- Carregamento concluído.
- Pesagem final realizada.
- Peso coerente.
- Sem divergência aberta.

#### Critérios de aceite
- [ ] Ordem muda para `VALIDADO`.
- [ ] Ordem pode mudar para `FINALIZADO` após faturamento.
- [ ] Eventos são registrados.

---

## Fase 10 — Testes e Homologação

### Etapa 10.1 — Testes unitários de domínio

#### Objetivo
Garantir regras críticas.

#### Escopo
Testar:

- Transição de status.
- Entrada em fila.
- Produto sem pilha.
- Ordem sem tara.
- Ordem com divergência.
- Peso fora da tolerância.

#### Critérios de aceite
- [ ] Testes passam.
- [ ] Regras críticas cobertas.

---

### Etapa 10.2 — Testes integrados do fluxo completo

#### Objetivo
Simular processo real ponta a ponta.

#### Fluxo

```text
Pedido Protheus
→ Ticket Guardian
→ Tara
→ Ordem criada
→ Fila
→ Operador inicia
→ Operador conclui
→ Peso final
→ Validação
→ Liberação
```

#### Critérios de aceite
- [ ] Fluxo completo executa sem erro.
- [ ] Eventos são gravados.
- [ ] Divergências são tratadas.
- [ ] Painel mostra status correto.

---

# 6. Critérios Gerais de Aceite do MVP

O MVP será considerado entregue quando:

- [ ] For possível criar ordem de carregamento a partir de pedido e ticket.
- [ ] A ordem resolver produto, pilha e ponto de carregamento.
- [ ] O operador visualizar sua fila.
- [ ] O operador iniciar carregamento.
- [ ] O operador concluir carregamento.
- [ ] O operador registrar divergência.
- [ ] A supervisão visualizar divergências.
- [ ] A pesagem final atualizar a ordem.
- [ ] O sistema validar peso dentro/fora da tolerância.
- [ ] O painel web mostrar o status da operação.
- [ ] Todos os eventos relevantes ficarem registrados.
- [ ] A documentação estiver atualizada.

---

# 7. Regras de Negócio Obrigatórias

## RN-001 — Ordem sem ticket não carrega
Nenhuma ordem pode ir para carregamento sem ticket Guardian.

## RN-002 — Ordem sem tara não carrega
Nenhuma ordem pode ser carregada sem tara registrada.

## RN-003 — Produto deve ter pilha
Todo produto carregável deve possuir pilha e ponto de carregamento configurados.

## RN-004 — Operador só vê sua fila
Operador comum só deve visualizar ordens do seu ponto de carregamento.

## RN-005 — Divergência bloqueia carregamento
Ordem em divergência não pode ser carregada até liberação da supervisão.

## RN-006 — Mudança de status gera evento
Toda alteração de status deve registrar evento com usuário, origem, data/hora e observação.

## RN-007 — Peso final deve ser validado
Peso final deve ser comparado com quantidade prevista e tolerância configurada.

## RN-008 — Protheus e Guardian não devem ser acessados diretamente pelo frontend
Toda integração externa deve passar pelo backend.

---

# 8. APIs Mínimas Esperadas

## Ordens de carregamento

```http
GET    /api/v1/ordens-carregamento
GET    /api/v1/ordens-carregamento/{id}
POST   /api/v1/ordens-carregamento
POST   /api/v1/ordens-carregamento/{id}/iniciar
POST   /api/v1/ordens-carregamento/{id}/concluir
POST   /api/v1/ordens-carregamento/{id}/divergencias
POST   /api/v1/ordens-carregamento/{id}/resolver-divergencia
POST   /api/v1/ordens-carregamento/{id}/pesagem-final
```

## Fila

```http
GET /api/v1/fila-carregamento
GET /api/v1/operador/minha-fila
```

## Cadastros

```http
GET    /api/v1/pontos-carregamento
POST   /api/v1/pontos-carregamento
PUT    /api/v1/pontos-carregamento/{id}

GET    /api/v1/pilhas-produto
POST   /api/v1/pilhas-produto
PUT    /api/v1/pilhas-produto/{id}
```

## Integrações

```http
GET /api/v1/integracoes/protheus/pedidos/{numero}
GET /api/v1/integracoes/guardian/tickets/{ticket}
```

---

# 9. Payloads de Referência

## Criar ordem de carregamento

```json
{
  "empresa": "01",
  "filial": "01",
  "pedido_numero": "781456",
  "pedido_item": "01",
  "contrato_codigo": "000123",
  "ticket_guardian": "0000001",
  "cliente_codigo": "017687",
  "cliente_loja": "01",
  "cliente_nome": "Cliente Exemplo Ltda",
  "produto_codigo": "BRITA1",
  "produto_descricao": "Brita 1",
  "quantidade_prevista": 32.0,
  "unidade": "TN",
  "placa_veiculo": "ABC1D23",
  "placa_carreta": "XYZ4E56",
  "motorista_nome": "João da Silva",
  "motorista_documento": "00000000000",
  "transportadora_codigo": "000001",
  "transportadora_nome": "Transportadora Exemplo",
  "tara": 15200
}
```

## Iniciar carregamento

```json
{
  "operador_id": 15,
  "equipamento_codigo": "PA-01",
  "ponto_carregamento_id": 1,
  "observacao": "Início normal do carregamento"
}
```

## Concluir carregamento

```json
{
  "operador_id": 15,
  "observacao": "Carregamento concluído sem ocorrência"
}
```

## Registrar divergência

```json
{
  "tipo": "PRODUTO_DIVERGENTE",
  "descricao": "Produto informado pelo motorista não confere com o pedido.",
  "usuario_id": 15,
  "origem": "APP_OPERADOR"
}
```

## Pesagem final

```json
{
  "peso_bruto": 47200,
  "peso_liquido": 32000,
  "ticket_guardian": "0000001",
  "origem": "GUARDIAN"
}
```

---

# 10. Documentação que Deve Ser Atualizada por Fase

## Após Fase 1
Atualizar:

- `docs/regras-negocio.md`
- `docs/modelo-dados.md`
- `docs/DECISOES_TECNICAS.md`

## Após Fase 2
Atualizar:

- `docs/cadastros-operacionais.md`
- `docs/regras-negocio.md`

## Após Fase 3
Atualizar:

- `docs/ordem-carregamento.md`
- `docs/api.md`

## Após Fase 4
Atualizar:

- `docs/fila-carregamento.md`
- `docs/regras-negocio.md`

## Após Fase 5
Atualizar:

- `docs/app-operador.md`
- `docs/api.md`

## Após Fase 6
Atualizar:

- `docs/painel-operacional.md`

## Após Fase 7
Atualizar:

- `docs/integracao-protheus.md`
- `docs/api.md`

## Após Fase 8
Atualizar:

- `docs/integracao-guardian.md`
- `docs/api.md`

## Após Fase 9
Atualizar:

- `docs/pesagem-final.md`
- `docs/regras-negocio.md`

## Após Fase 10
Atualizar:

- `docs/testes.md`
- `docs/STATUS.md`
- `docs/CHANGELOG.md`

---

# 11. Roadmap Resumido para Execução

```text
Fase 0 — Preparação
  0.1 Documentação base
  0.2 Estrutura do projeto

Fase 1 — Modelo operacional
  1.1 Enums e constantes
  1.2 Migrations
  1.3 Models

Fase 2 — Cadastros
  2.1 Pontos de carregamento
  2.2 Pilhas de produto
  2.3 Produto x pilha x ponto

Fase 3 — Ordem de carregamento
  3.1 Criar ordem
  3.2 Consultar ordem
  3.3 Alterar status com evento

Fase 4 — Fila
  4.1 Fila por ponto
  4.2 Regras de entrada na fila

Fase 5 — App operador
  5.1 Login
  5.2 Minha fila
  5.3 Detalhe da ordem
  5.4 Iniciar carregamento
  5.5 Concluir carregamento
  5.6 Registrar divergência

Fase 6 — Painel web
  6.1 Dashboard simples
  6.2 Painel de divergências

Fase 7 — Protheus
  7.1 Adapter mockado
  7.2 Integração real

Fase 8 — Guardian
  8.1 Adapter mockado
  8.2 Integração real

Fase 9 — Pesagem final
  9.1 Atualizar peso final
  9.2 Liberar para faturamento

Fase 10 — Testes
  10.1 Testes unitários
  10.2 Testes integrados
```

---

# 12. Orientação Final para o Claude Code

Antes de começar qualquer implementação:

1. Leia este arquivo inteiro.
2. Crie os documentos obrigatórios.
3. Monte o `docs/ROADMAP.md` com todas as fases.
4. Monte o `docs/STATUS.md` com a etapa atual.
5. Comece pela Fase 0.
6. Ao concluir cada etapa, atualize documentação e status.
7. Faça commits pequenos e descritivos, se houver controle de versão.

Mensagem padrão de conclusão de etapa:

```md
## Etapa concluída
Etapa X.Y — Nome da etapa

## Entregas realizadas
- Item 1
- Item 2

## Arquivos alterados
- arquivo1
- arquivo2

## Critérios de aceite
- [x] Critério 1
- [x] Critério 2

## Próxima etapa sugerida
Etapa X.Y — Nome da próxima etapa
```

