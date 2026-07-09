import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../models/ordem_model.dart';
import '../../../core/api/api_client.dart';
import '../../auth/providers/auth_provider.dart';

final filaProvider = StateNotifierProvider<FilaNotifier, AsyncValue<List<OrdemModel>>>(
  (ref) => FilaNotifier(ref.watch(apiClientProvider), ref),
);

class FilaNotifier extends StateNotifier<AsyncValue<List<OrdemModel>>> {
  final ApiClient _api;
  final Ref _ref;

  FilaNotifier(this._api, this._ref) : super(const AsyncValue.loading()) {
    carregar();
  }

  Future<void> carregar() async {
    state = const AsyncValue.loading();
    try {
      final res = await _api.get('/operador/minha-fila');
      final lista = (res.data['data'] as List)
          .map((j) => OrdemModel.fromJson(j))
          .toList();
      state = AsyncValue.data(lista);
    } catch (e, st) {
      state = AsyncValue.error(e, st);
    }
  }

  Future<void> iniciarCarregamento(String ordemId) async {
    final user = _ref.read(authProvider).valueOrNull;
    if (user == null) return;

    await _api.post('/ordens-carregamento/$ordemId/iniciar', data: {
      'operador_id': user.id,
      'ponto_carregamento_id': user.pontoCarregamentoId,
    });
    await carregar();
  }

  Future<void> concluirCarregamento(String ordemId) async {
    await _api.post('/ordens-carregamento/$ordemId/concluir');
    await carregar();
  }

  Future<void> rejeitar(String ordemId, String descricao) async {
    await _api.post('/ordens-carregamento/$ordemId/rejeitar', data: {
      'descricao': descricao,
    });
    await carregar();
  }
}

final ordemDetalheProvider = FutureProvider.family<OrdemModel, String>((ref, id) async {
  final api = ref.watch(apiClientProvider);
  final res = await api.get('/ordens-carregamento/$id');
  return OrdemModel.fromJson(res.data['data'] ?? res.data);
});
