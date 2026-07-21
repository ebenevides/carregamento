import 'dart:async';
import 'dart:convert';

import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:web_socket_channel/web_socket_channel.dart';

import '../api/api_client.dart';

/// Chave do app Reverb (REVERB_APP_KEY no backend) — assim como [apiRootUrl],
/// é fixa por ambiente e trocada manualmente se o backend mudar.
const String _reverbAppKey = 'carregamento-key';

/// Cliente mínimo do protocolo Pusher (o que o Laravel Reverb fala) via
/// WebSocket puro. Não existe pacote pronto que suporte host self-hosted
/// (pusher_channels_flutter só fala com Pusher Cloud via "cluster") — ver
/// Etapa 3 do roadmap.
final realtimeClientProvider = Provider<RealtimeClient>((ref) {
  final client = RealtimeClient(ref.watch(apiClientProvider));
  ref.onDispose(client.dispose);
  return client;
});

class RealtimeClient {
  final ApiClient _api;
  WebSocketChannel? _channel;
  StreamSubscription? _sub;
  String? _socketId;
  bool _connecting = false;

  final Map<String, void Function(String event, Map<String, dynamic> data)>
  _handlers = {};
  final Set<String> _pendingSubscriptions = {};
  final Set<String> _confirmedSubscriptions = {};

  RealtimeClient(this._api);

  Future<void> _ensureConnected() async {
    if (_channel != null || _connecting) return;
    _connecting = true;

    final uri = Uri.parse(apiRootUrl);
    final wsScheme = uri.scheme == 'https' ? 'wss' : 'ws';
    final port = uri.hasPort ? ':${uri.port}' : '';
    final wsUrl =
        '$wsScheme://${uri.host}$port/app/$_reverbAppKey'
        '?protocol=7&client=flutter&version=1.0';

    _channel = WebSocketChannel.connect(Uri.parse(wsUrl));
    _sub = _channel!.stream.listen(
      _onMessage,
      onDone: _onDisconnected,
      onError: (_) => _onDisconnected(),
    );
  }

  void _onDisconnected() {
    _channel = null;
    _sub?.cancel();
    _sub = null;
    _socketId = null;
    _connecting = false;
    _confirmedSubscriptions.clear();
    // Reconexão simples: tenta de novo em 3s se ainda houver interesse
    // (alguém inscrito). Sem backoff — foreground apenas, ver Etapa 3.
    if (_pendingSubscriptions.isNotEmpty ||
        _confirmedSubscriptions.isNotEmpty) {
      Future.delayed(const Duration(seconds: 3), () {
        _ensureConnected();
      });
    }
  }

  Future<void> _onMessage(dynamic raw) async {
    final msg = jsonDecode(raw as String) as Map<String, dynamic>;
    final event = msg['event'] as String?;
    final channelName = msg['channel'] as String?;

    switch (event) {
      case 'pusher:connection_established':
        final data = jsonDecode(msg['data'] as String) as Map<String, dynamic>;
        _socketId = data['socket_id'] as String?;
        _connecting = false;
        for (final ch in _pendingSubscriptions) {
          await _subscribeChannel(ch);
        }
        break;
      case 'pusher_internal:subscription_succeeded':
      case 'pusher:subscription_succeeded':
        if (channelName != null) _confirmedSubscriptions.add(channelName);
        break;
      case 'pusher:error':
        break;
      default:
        if (channelName != null &&
            event != null &&
            _handlers.containsKey(channelName)) {
          final data = msg['data'];
          final decoded = data is String
              ? jsonDecode(data) as Map<String, dynamic>
              : (data as Map<String, dynamic>? ?? {});
          _handlers[channelName]?.call(event, decoded);
        }
    }
  }

  Future<void> _subscribeChannel(String channelName) async {
    if (channelName.startsWith('private-')) {
      final auth = await _authorize(channelName);
      if (auth == null) return; // sem acesso — não insiste
      _channel?.sink.add(
        jsonEncode({
          'event': 'pusher:subscribe',
          'data': {'channel': channelName, 'auth': auth},
        }),
      );
    } else {
      _channel?.sink.add(
        jsonEncode({
          'event': 'pusher:subscribe',
          'data': {'channel': channelName},
        }),
      );
    }
  }

  Future<String?> _authorize(String channelName) async {
    try {
      final res = await _api.post(
        '$apiRootUrl/broadcasting/auth',
        data: {'socket_id': _socketId, 'channel_name': channelName},
      );
      return res.data['auth'] as String?;
    } catch (_) {
      return null;
    }
  }

  /// Assina [channelName] e chama [onEvent] a cada evento recebido nele.
  /// Idempotente: chamar de novo com o mesmo canal só troca o callback.
  Future<void> subscribe(
    String channelName,
    void Function(String event, Map<String, dynamic> data) onEvent,
  ) async {
    _handlers[channelName] = onEvent;
    _pendingSubscriptions.add(channelName);
    await _ensureConnected();
    if (_socketId != null && !_confirmedSubscriptions.contains(channelName)) {
      await _subscribeChannel(channelName);
    }
  }

  void unsubscribe(String channelName) {
    _handlers.remove(channelName);
    _pendingSubscriptions.remove(channelName);
    _confirmedSubscriptions.remove(channelName);
    _channel?.sink.add(
      jsonEncode({
        'event': 'pusher:unsubscribe',
        'data': {'channel': channelName},
      }),
    );
  }

  void dispose() {
    _sub?.cancel();
    _channel?.sink.close();
  }
}
