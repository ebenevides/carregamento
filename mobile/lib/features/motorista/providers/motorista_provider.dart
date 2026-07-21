import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../models/ordem_motorista_model.dart';
import '../../../core/api/api_client.dart';
import '../../../core/realtime/realtime_client.dart';
import '../../../core/storage/secure_storage.dart';

final motoristaProvider =
    StateNotifierProvider<MotoristaNotifier, AsyncValue<OrdemMotoristaModel?>>(
      (ref) => MotoristaNotifier(
        ref.watch(apiClientProvider),
        ref.watch(realtimeClientProvider),
        ref.watch(secureStorageProvider),
      ),
    );

class MotoristaNotifier
    extends StateNotifier<AsyncValue<OrdemMotoristaModel?>> {
  final ApiClient _api;
  final RealtimeClient _realtime;
  final SecureStorage _storage;
  String? _channel;

  MotoristaNotifier(this._api, this._realtime, this._storage)
    : super(const AsyncValue.loading()) {
    _inscreverCanalPrivado();
  }

  Future<void> _inscreverCanalPrivado() async {
    final userId = await _storage.getUserId();
    if (userId == null) return;
    _channel = 'private-App.Models.User.$userId';
    await _realtime.subscribe(_channel!, _onRealtimeEvent);
  }

  void _onRealtimeEvent(String event, Map<String, dynamic> data) {
    // "Chegou sua vez" e qualquer outra mudança de status da ordem do
    // motorista chegam aqui — recarrega pra refletir o estado novo.
    if (event == 'ordem.status.alterado') carregar();
  }

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

  @override
  void dispose() {
    if (_channel != null) _realtime.unsubscribe(_channel!);
    super.dispose();
  }
}
