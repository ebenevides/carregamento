import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../../core/api/api_client.dart';
import '../../auth/providers/auth_provider.dart';
import '../../fila/providers/fila_provider.dart';

const tiposDivergencia = [
  ('PRODUTO_DIVERGENTE', 'Produto divergente', 'Produto diferente da ordem'),
  (
    'QUANTIDADE_DIVERGENTE',
    'Quantidade divergente',
    'Volume diferente do previsto',
  ),
  ('VEICULO_DIVERGENTE', 'Veículo divergente', 'Placa ou veículo incorreto'),
  (
    'MOTORISTA_DIVERGENTE',
    'Motorista divergente',
    'Motorista diferente da ordem',
  ),
  ('TICKET_INVALIDO', 'Ticket inválido', 'Ticket ausente ou incorreto'),
  ('TARA_INVALIDA', 'Tara inválida', 'Peso de tara inconsistente'),
  ('PONTO_INDISPONIVEL', 'Ponto indisponível', 'Ponto sem condição de operar'),
  ('OUTRO', 'Outro', 'Outro problema operacional'),
];

final divergenciaControllerProvider =
    AsyncNotifierProvider.autoDispose<DivergenciaController, void>(
      DivergenciaController.new,
    );

class DivergenciaController extends AutoDisposeAsyncNotifier<void> {
  @override
  Future<void> build() async {}

  Future<bool> registrar({
    required String ordemId,
    required String tipo,
    required String descricao,
  }) async {
    final usuario = ref.read(authProvider).valueOrNull;
    if (usuario == null) {
      state = AsyncError(
        StateError('Usuário não autenticado.'),
        StackTrace.current,
      );
      return false;
    }

    state = const AsyncLoading();
    state = await AsyncValue.guard(() async {
      await ref
          .read(apiClientProvider)
          .post(
            '/ordens-carregamento/$ordemId/divergencias',
            data: {
              'tipo': tipo,
              'descricao': descricao.trim(),
              'usuario_id': usuario.id,
              'origem': 'APP_OPERADOR',
            },
          );
      await ref.read(filaProvider.notifier).carregar();
      ref.invalidate(ordemDetalheProvider(ordemId));
    });

    return !state.hasError;
  }
}
