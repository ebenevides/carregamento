import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../models/ordem_motorista_model.dart';
import '../../../core/api/api_client.dart';

final motoristaProvider = StateNotifierProvider<MotoristaNotifier, AsyncValue<OrdemMotoristaModel?>>(
  (ref) => MotoristaNotifier(ref.watch(apiClientProvider)),
);

class MotoristaNotifier extends StateNotifier<AsyncValue<OrdemMotoristaModel?>> {
  final ApiClient _api;

  MotoristaNotifier(this._api) : super(const AsyncValue.loading());

  Future<void> carregar() async {
    state = const AsyncValue.loading();
    try {
      final res = await _api.get('/motorista/minha-ordem');
      if (res.statusCode == 204 || res.data == null) {
        state = const AsyncValue.data(null);
        return;
      }
      // Resposta vem dentro de "data" (API Resource)
      final data = res.data['data'] ?? res.data;
      state = AsyncValue.data(OrdemMotoristaModel.fromJson(data));
    } catch (e, st) {
      // 204 sem corpo gera erro no dio — trata como "sem ordem"
      if (e.toString().contains('204') || e.toString().contains('No content')) {
        state = const AsyncValue.data(null);
        return;
      }
      state = AsyncValue.error(e, st);
    }
  }
}
