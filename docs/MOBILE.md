# Mobile Flutter

## Escopo

`mobile/` contem app unico `carregamento_operador`. Perfil devolvido no login escolhe experiencia:

- `OPERADOR`: fila do ponto, detalhe da ordem, iniciar, concluir, rejeitar, divergencia e chat.
- `MOTORISTA`: carga ativa vinculada pelo documento e chat da ordem.

Backend continua autoritativo para permissao e transicao; redirect no cliente melhora navegacao,
mas nao substitui autorizacao da API.

## Stack e estrutura

| Area | Tecnologia | Fonte |
|---|---|---|
| UI | Flutter/Material 3 | `lib/main.dart` |
| Estado/DI | Riverpod | `lib/features/*/providers/` |
| Navegacao | `go_router` | `lib/core/providers/router_provider.dart` |
| HTTP | Dio | `lib/core/api/api_client.dart` |
| Token | `flutter_secure_storage` | `lib/core/storage/secure_storage.dart` |
| Realtime | `web_socket_channel`, protocolo Pusher/Reverb | `lib/core/realtime/realtime_client.dart` |

Features:

```text
lib/
  core/{api,providers,realtime,storage}/
  features/auth/{models,providers,screens}/
  features/fila/{models,providers,screens}/
  features/ordem/screens/
  features/divergencia/screens/
  features/motorista/{models,providers,screens}/
  features/chat/{models,providers,screens}/
```

## Setup e execucao

Requisitos: Flutter compativel com Dart SDK `^3.11.5`, backend acessivel pelo dispositivo e Android/
iOS tooling instalado.

```bash
cd mobile
flutter pub get
flutter analyze
flutter test
flutter run
```

Em emulador/dispositivo, `localhost` aponta para o proprio dispositivo. O host atual da API fica em
`lib/core/api/api_client.dart` (`apiRootUrl`) e deve ser ajustado por ambiente. Hoje e constante de
compilacao manual; mudanca para `--dart-define` requer decisao/implementacao explicita.

## Auth, API e rotas de tela

Login chama `POST /api/v1/auth/login`, persiste token Sanctum e injeta
`Authorization: Bearer <token>` no interceptor Dio. `AuthListenable` atualiza `GoRouter`.

| Rota Flutter | Tela | Perfil esperado |
|---|---|---|
| `/login` | `LoginScreen` | publico |
| `/fila` | `FilaScreen` | operador |
| `/ordem/:id` | `OrdemDetalheScreen` | operador autorizado |
| `/motorista/minha-carga` | `MinhaCargaScreen` | motorista |
| `/chat/:ordemId` | `ChatScreen` | participante autorizado |

Contrato HTTP completo: [api.md](api.md). Inventario e ownership: [ROTAS.md](ROTAS.md).

## Realtime

`RealtimeClient` abre WebSocket no mesmo host de `apiRootUrl`, caminho `/app/{REVERB_APP_KEY}`. Para
canais `private-*`, solicita assinatura em `/broadcasting/auth` com token Sanctum. Canais atuais:

- `private-ordem.{ordemId}.chat`: mensagens por ordem.
- `private-App.Models.User.{id}`: mudanças de status destinadas ao motorista.

A fila do operador ainda não assina canal por ponto. Referências antigas a realtime
`ponto.{pontoId}` em planos/checklists descrevem intenção, não implementação atual. Canais adicionais
devem coincidir com `app/routes/channels.php` e eventos broadcast do backend.

Cliente implementa reconexao simples em 3 segundos enquanto ha subscriptions, sem backoff e voltado
ao foreground. Chave Reverb tambem e constante no arquivo; trate configuracao por ambiente como
trabalho futuro, nao como capacidade existente.

## Convencoes de implementacao

- Separe JSON/model, estado/provider e UI/screen dentro da feature.
- Centralize HTTP em `ApiClient`; nao espalhe host, token ou Dio nas telas.
- Provider coordena loading/erro/dados; tela renderiza e dispara intencoes.
- Espelhe nomes/status do backend; nao implemente maquina de estados paralela no Dart.
- Ao alterar payload, atualize model Dart, provider, Request/Resource PHP e testes juntos.
- Exiba falhas de rede/autorizacao sem perder estado util; invalide providers apos mutacoes.
- Subscription realtime deve ser idempotente e liberada no dispose.

## Validacao

Execute `flutter analyze` e `flutter test`. Para fluxo real, use
[CHECKLIST_TESTES_MOBILE.md](CHECKLIST_TESTES_MOBILE.md) e
[homologacao-ponta-a-ponta.md](homologacao-ponta-a-ponta.md), cobrindo ambos perfis, perda de rede,
reconexao e autorizacao cruzada por ponto/ordem.
