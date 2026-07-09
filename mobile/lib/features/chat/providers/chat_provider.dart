import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../models/mensagem_model.dart';
import '../../../core/api/api_client.dart';

final mensagensProvider =
    StateNotifierProvider.family<MensagensNotifier, AsyncValue<List<MensagemModel>>, String>(
  (ref, ordemId) => MensagensNotifier(ref.watch(apiClientProvider), ordemId),
);

class MensagensNotifier extends StateNotifier<AsyncValue<List<MensagemModel>>> {
  final ApiClient _api;
  final String _ordemId;

  MensagensNotifier(this._api, this._ordemId) : super(const AsyncValue.loading());

  Future<void> carregar() async {
    try {
      final res = await _api.get('/ordens-carregamento/$_ordemId/mensagens');
      final lista = ((res.data['data'] ?? []) as List)
          .map((j) => MensagemModel.fromJson(j))
          .toList();
      state = AsyncValue.data(lista);
    } catch (e, st) {
      state = AsyncValue.error(e, st);
    }
  }

  Future<void> enviar(String texto) async {
    await _api.post('/ordens-carregamento/$_ordemId/mensagens', data: {
      'mensagem': texto,
    });
    await carregar();
  }
}
