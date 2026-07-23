import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../models/mensagem_model.dart';
import '../../../core/api/api_client.dart';
import '../../../core/realtime/realtime_client.dart';

final mensagensProvider = StateNotifierProvider.autoDispose
    .family<MensagensNotifier, ChatState, String>(
      (ref, ordemId) => MensagensNotifier(
        ref.watch(apiClientProvider),
        ref.watch(realtimeClientProvider),
        ordemId,
      ),
    );

class ChatState {
  const ChatState({
    this.mensagens = const AsyncValue.loading(),
    this.enviando = false,
    this.erroEnvio,
  });

  final AsyncValue<List<MensagemModel>> mensagens;
  final bool enviando;
  final String? erroEnvio;

  ChatState copyWith({
    AsyncValue<List<MensagemModel>>? mensagens,
    bool? enviando,
    String? erroEnvio,
    bool limparErroEnvio = false,
  }) => ChatState(
    mensagens: mensagens ?? this.mensagens,
    enviando: enviando ?? this.enviando,
    erroEnvio: limparErroEnvio ? null : erroEnvio ?? this.erroEnvio,
  );
}

class MensagensNotifier extends StateNotifier<ChatState> {
  final ApiClient _api;
  final RealtimeClient _realtime;
  final String _ordemId;
  late final String _channel = 'private-ordem.$_ordemId.chat';

  MensagensNotifier(this._api, this._realtime, this._ordemId)
    : super(const ChatState()) {
    _realtime.subscribe(_channel, _onRealtimeEvent);
    carregar();
  }

  void _onRealtimeEvent(String event, Map<String, dynamic> data) {
    if (event == 'mensagem.enviada') carregar();
  }

  Future<void> carregar() async {
    try {
      final res = await _api.get('/ordens-carregamento/$_ordemId/mensagens');
      final lista = ((res.data['data'] ?? []) as List)
          .map((j) => MensagemModel.fromJson(j))
          .toList();
      state = state.copyWith(mensagens: AsyncValue.data(lista));
    } catch (e, st) {
      state = state.copyWith(mensagens: AsyncValue.error(e, st));
    }
  }

  Future<bool> enviar(String texto) async {
    if (state.enviando || texto.trim().isEmpty) return false;
    state = state.copyWith(enviando: true, limparErroEnvio: true);
    try {
      await _api.post(
        '/ordens-carregamento/$_ordemId/mensagens',
        data: {'mensagem': texto.trim()},
      );
      await carregar();
      state = state.copyWith(enviando: false, limparErroEnvio: true);
      return true;
    } catch (_) {
      state = state.copyWith(
        enviando: false,
        erroEnvio: 'Não foi possível enviar. Tente novamente.',
      );
      return false;
    }
  }

  @override
  void dispose() {
    _realtime.unsubscribe(_channel);
    super.dispose();
  }
}
