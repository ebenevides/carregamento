# Plano Flutter — App único: Operador + Motorista + Chat

## Contexto

`carregamento_operador` já é um app Flutter funcional (Riverpod + go_router + dio + flutter_secure_storage) com login, fila do operador (`features/fila`), detalhe de ordem (`features/ordem`) e registro de divergência (`features/divergencia`). Este documento **não cria um app novo** — estende o mesmo projeto para também atender o perfil **motorista**, e adiciona **chat** para os dois perfis. O contrato de API espelha `docs/CLAUDE_ROADMAP_OPERADOR_MOTORISTA.md` no backend (`app/`) — implemente lá primeiro ou em paralelo, os endpoints abaixo só existem depois das Fases 11–14 daquele documento.

O login (`POST /api/v1/auth/login`, já implementado em `features/auth`) já retorna `perfil` no payload do usuário — é esse campo que decide a navegação: `OPERADOR`/`EXPEDICAO`/`ADMIN` vão para a home de operador; `MOTORISTA` vai para a home de motorista.

## O que já existe (reaproveitar, não recriar)

| Camada | Arquivo | Reaproveitar para |
|---|---|---|
| Cliente HTTP | `lib/core/api/api_client.dart` | Todos os novos endpoints (dio já configurado com base URL + interceptor de token) |
| Token storage | `lib/core/storage/secure_storage.dart` | Sem mudança |
| Router | `lib/core/providers/router_provider.dart` | Adicionar `redirect` por perfil e novas rotas `/motorista/*`, `/chat/:ordemId` |
| Auth | `lib/features/auth/*` | `login()` já retorna perfil; só ramificar navegação pós-login |
| Fila operador | `lib/features/fila/*` | Adicionar botões Rejeitar/Próximo na tela existente |
| Ordem | `lib/features/ordem/*` | Reaproveitar `OrdemModel` como base do model de "minha ordem" do motorista (mesmos campos: produto, pilha, ponto, status) |

## Novo pacote

```yaml
# pubspec.yaml — adicionar
dependencies:
  pusher_channels_flutter: ^2.4.0   # substitui web_socket_channel para canais privados Reverb (protocolo Pusher)
```

`web_socket_channel` pode ser removido se nada mais o usa após a migração (checar `lib/features/fila` — se hoje escuta canal público via WS cru, migrar também para o novo cliente por consistência, mesmo que canal público não exija autenticação).

## Contrato de API (definido em `docs/CLAUDE_ROADMAP_OPERADOR_MOTORISTA.md` no backend)

| Ação | Método/rota | Payload | Quem usa |
|---|---|---|---|
| Login | `POST /api/v1/auth/login` | `{email, password}` → `{token, user: {..., perfil}}` | Ambos (já existe) |
| Minha fila | `GET /api/v1/operador/minha-fila` | — | Operador (já existe) |
| Rejeitar | `POST /api/v1/ordens-carregamento/{id}/rejeitar` | `{descricao}` (min 5 chars) | Operador (**novo**) |
| Carregado | `POST /api/v1/ordens-carregamento/{id}/concluir` | `{}` | Operador (já existe) |
| Próximo | `POST /api/v1/fila-carregamento/{id}/liberar` | `{}` | Operador (já existe) |
| Minha ordem | `GET /api/v1/motorista/minha-ordem` | — | Motorista (**novo**, 204 se não houver ordem ativa) |
| Listar mensagens | `GET /api/v1/ordens-carregamento/{id}/mensagens` | paginado | Ambos (**novo**) |
| Enviar mensagem | `POST /api/v1/ordens-carregamento/{id}/mensagens` | `{mensagem}` (max 1000) | Ambos (**novo**, 422 se ordem não ativa) |

### Canais realtime (Reverb, protocolo Pusher, auth via header `Authorization: Bearer <token sanctum>` no handshake do `pusher_channels_flutter`)

| Canal | Tipo | Evento | Quem escuta |
|---|---|---|---|
| `ponto.{pontoId}` | público | `ordem.status.alterado` | Operador (fila do próprio ponto) |
| `App.Models.User.{motoristaId}` | privado | `ordem.status.alterado` | Motorista (recebe quando sua ordem vira `EM_CARREGAMENTO` → "pode se posicionar") |
| `ordem.{ordemId}.chat` | privado | `mensagem.enviada` | Ambos, só enquanto a ordem está aberta na tela de chat |

## Estrutura de pastas nova

```text
lib/features/
  motorista/
    models/ordem_motorista_model.dart      # produto, pilha (bica), ponto (unidade), status, quantidade_prevista
    providers/motorista_provider.dart       # busca "minha ordem", escuta canal privado do usuário
    screens/minha_carga_screen.dart         # tela principal do motorista
    screens/aguardando_liberacao_screen.dart # estado sem ordem ativa / aguardando EM_CARREGAMENTO
  chat/
    models/mensagem_model.dart
    providers/chat_provider.dart            # lista mensagens + subscribe no canal ordem.{id}.chat
    screens/chat_screen.dart                # usada por operador (a partir da fila) e motorista (a partir de minha carga)
```

## Telas — Operador (alterações sobre o que já existe)

- **Tela de fila / detalhe de ordem** (`features/fila`, `features/ordem`): adicionar 3 botões de ação na ordem em destaque (a que está `EM_CARREGAMENTO` ou próxima `AGUARDANDO_CARREGAMENTO`):
  - **Rejeitar** → abre modal pedindo motivo (campo obrigatório, min 5 chars) → `POST .../rejeitar` → volta pra fila mostrando a ordem em `DIVERGENCIA`.
  - **Carregado** → confirmação simples → `POST .../concluir`.
  - **Próximo** → libera a próxima da fila → `POST /fila-carregamento/{id}/liberar`.
  - Ícone de chat com badge de não lidas, abre `chat_screen.dart` passando `ordemId`.

## Telas — Motorista (novas)

- **Login**: reaproveita tela existente (`features/auth`); após sucesso, se `perfil == 'MOTORISTA'`, `router` redireciona para `/motorista/minha-carga` em vez da home do operador.
- **Minha Carga** (`minha_carga_screen.dart`): chama `GET /motorista/minha-ordem`; exibe produto, ponto de carregamento (rotulado como "Unidade"), pilha (rotulada como "Bica/Pilha"), quantidade prevista, status atual, placa.
  - Se sem ordem ativa (204): tela de espera ("Nenhuma carga no momento").
  - Se ordem em `AGUARDANDO_CARREGAMENTO`: exibe "Aguardando liberação para posicionar" e assina o canal privado `App.Models.User.{id}`.
  - Ao receber `ordem.status.alterado` com `status == 'EM_CARREGAMENTO'`: atualiza tela para "Pode se posicionar" (destaque visual forte — é a ação que o motorista está esperando).
  - Botão de chat com o operador, sempre visível quando há ordem ativa.

## Fluxo de realtime no app

1. Ao entrar em uma tela com ordem ativa (fila do operador, minha-carga do motorista, chat), `initState`/provider assina o(s) canal(is) relevante(s) via `pusher_channels_flutter`.
2. Ao sair da tela ou perder a ordem ativa, `unsubscribe`.
3. Reconexão: `pusher_channels_flutter` já reconecta automaticamente; ao reconectar, refazer o fetch REST correspondente (fila / minha-ordem / mensagens) para não perder eventos ocorridos durante a desconexão — não confiar só no WS para estado inicial.

## Checklist de aceite manual

- [ ] Login como usuário `perfil=OPERADOR` abre a fila; login como `perfil=MOTORISTA` abre "Minha Carga".
- [ ] Operador aperta Rejeitar sem motivo → erro de validação; com motivo → ordem some da fila ativa e aparece em divergências.
- [ ] Operador aperta Próximo → motorista da ordem liberada vê "Pode se posicionar" em tempo real, sem dar refresh manual.
- [ ] Operador aperta Carregado → ordem sai da tela do motorista (ou muda de estado, conforme UX definida).
- [ ] Chat: mensagem enviada pelo operador aparece no app do motorista sem reload, e vice-versa.
- [ ] Chat bloqueado (botão desabilitado ou erro claro) quando a ordem já está `FINALIZADO`/`CANCELADO`.
