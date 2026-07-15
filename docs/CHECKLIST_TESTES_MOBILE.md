# Checklist de Testes Manuais — App Mobile (Operador e Motorista)

Cenários de teste manual pro app Flutter (`mobile/carregamento_operador`), cobrindo os dois perfis
atendidos pelo app único: **OPERADOR** (também `EXPEDICAO`/`ADMIN`) e **MOTORISTA**. Complementa
`docs/homologacao-ponta-a-ponta.md` (que cobre o fluxo via `curl` direto na API) com os cenários
específicos de UI/UX e realtime do app.

## Pré-requisitos

- Stack rodando (`docker compose up -d`)
- Mock Guardian e Mock Protheus ativados (`GUARDIAN_MOCK=true`, `PROTHEUS_MOCK=true`)
- Usuários de teste: um `perfil=OPERADOR` vinculado a um ponto, um `perfil=MOTORISTA` com
  `documento` preenchido, uma ordem ativa vinculada a esse motorista (ver
  `docs/homologacao-ponta-a-ponta.md` passos 1–2)
- App rodando em emulador/dispositivo apontando pro backend local

**Legenda:** `[api]` = comportamento de backend já validado via `curl` direto na API em
2026-07-15 (ver sessão de testes), falta só confirmar a camada de UI/Flutter num emulador/
dispositivo real (sem Flutter SDK no ambiente onde os testes de API rodaram). Item sem tag ainda
não foi testado de forma alguma.

## Login e roteamento por perfil

- [ ] `[api]` Login com `perfil=OPERADOR` (ou `EXPEDICAO`/`ADMIN`) → abre fila do operador
- [ ] `[api]` Login com `perfil=MOTORISTA` → abre "Minha Carga", não fila
- [ ] Login com credencial inválida → erro claro, sem token salvo
- [ ] Token expirado/inválido em request → força logout, volta pra tela de login
- [ ] App fechado e reaberto com token válido no `flutter_secure_storage` → pula login, vai direto
      pra home do perfil certo

## Operador — fila (`features/fila`)

- [ ] Fila carrega lista de ordens do ponto, ordem em destaque é a `AGUARDANDO_CARREGAMENTO`/
      `EM_CARREGAMENTO`
- [ ] `[api]` Botão **Rejeitar** sem motivo → validação bloqueia (mínimo 5 caracteres)
- [ ] `[api]` Botão **Rejeitar** com motivo válido → `POST rejeitar`, ordem sai da fila ativa,
      aparece em divergência (`DIVERGENCIA`) — RN-009 confirmada (nunca `CANCELADO`)
- [ ] `[api]` Operador de outro ponto tenta Rejeitar → 403 (RN-010 confirmada)
- [ ] `[api]` Botão **Carregado** → confirmação → `POST concluir`, ordem sai da tela do operador
- [ ] `[api]` Botão **Próximo**/Iniciar → `AGUARDANDO_CARREGAMENTO` vira `EM_CARREGAMENTO`
- [ ] Ícone de chat mostra badge de não lidas correto e abre `chat_screen` com `ordemId` certo
- [ ] Fila vazia (sem ordens no ponto) → estado vazio tratado, sem crash
- [ ] Evento realtime `ordem.status.alterado` no canal `ponto.{pontoId}` atualiza fila sem precisar
      refresh manual
- [ ] App perde conexão e reconecta → refaz fetch REST da fila (não confia só em WS pra estado
      perdido)

## Operador — divergência (`features/divergencia`)

- [ ] Registrar divergência a partir de ordem ativa, campos obrigatórios validados
- [ ] Ordem em `DIVERGENCIA` volta pra `AGUARDANDO_CARREGAMENTO` (única transição permitida) —
      testar reentrada na fila
- [ ] Tentativa de ação inválida pro status atual (ex.: Rejeitar ordem já `CANCELADO`/`FINALIZADO`)
      → erro tratado, sem crash

## Motorista — Minha Carga (`features/motorista`)

- [ ] `[api]` Sem ordem ativa (API retorna 204) → tela "Nenhuma carga no momento"
- [ ] `[api]` Dados batem com API (produto, ponto, pilha, quantidade, placa, status) — só falta
      confirmar rótulos "Unidade"/"Bica-Pilha" na tela
- [ ] Ordem em `AGUARDANDO_CARREGAMENTO` → mensagem "Aguardando liberação pra posicionar", assina
      canal privado `App.Models.User.{id}`
- [ ] Operador libera ordem (`EM_CARREGAMENTO`) → motorista recebe evento realtime, tela muda pra
      "Pode se posicionar" com destaque visual, sem refresh manual
- [ ] `[api]` Operador aperta Carregado → tela do motorista reflete mudança de estado (ou some, conforme
      UX definida) — checar comportamento real implementado
- [ ] Botão de chat visível só quando há ordem ativa
- [ ] Troca de motorista/ordem enquanto app aberto (nova ordem atribuída) → tela atualiza sem
      precisar logout/login
- [ ] Gap conhecido (ver `docs/regras-negocio.md` § Gaps identificados em testes): motorista com
      duas ordens ativas simultâneas — confirmar qual delas a tela exibe e se é aceitável

## Chat (`features/chat`) — ambos perfis

- [ ] `[api]` Mensagem enviada pelo operador chega pro motorista e vice-versa (via REST — falta
      confirmar entrega em tempo real pelo canal `ordem.{id}.chat`)
- [ ] `[api]` Histórico de mensagens carrega ordenado ao abrir tela
- [ ] `[api]` Envio de mensagem vazia → validação bloqueia (422)
- [ ] Envio de mensagem acima de 1000 caracteres → validação bloqueia
- [ ] `[api]` Chat de ordem `FINALIZADO`/`CANCELADO` → erro 422 claro, sem permitir envio (RN-011
      confirmada) — falta confirmar que o botão fica desabilitado na UI
- [ ] Sair da tela de chat → unsubscribe do canal (checar não fica ouvindo em background
      indevidamente)
- [ ] Duas ordens diferentes abertas em sequência → não vaza mensagem de uma ordem pro chat da
      outra (canal errado)

## Realtime e resiliência (transversal)

- [ ] Wi-Fi cai e volta durante ordem ativa → app reconecta canal Pusher/Reverb automaticamente
- [ ] App em background e volta ao foreground → estado atualizado (não mostra dado stale)
- [ ] Erro de rede (API fora do ar) em qualquer ação (rejeitar/concluir/liberar/enviar mensagem) →
      mensagem de erro clara pro usuário, sem travar tela

## Critérios de aceite

- [ ] Todos os cenários acima executados sem crash e sem estado inconsistente na UI
- [ ] Roteamento por perfil (`OPERADOR` × `MOTORISTA`) correto em 100% dos logins testados
- [ ] Realtime (fila, minha-carga, chat) reflete mudanças sem exigir refresh manual em nenhum
      cenário
