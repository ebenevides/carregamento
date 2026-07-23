import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../../auth/models/usuario_model.dart';
import '../../../core/api/api_client.dart';
import '../../../core/storage/secure_storage.dart';

final authProvider =
    StateNotifierProvider<AuthNotifier, AsyncValue<UsuarioModel?>>(
      (ref) => AuthNotifier(
        ref.watch(apiClientProvider),
        ref.watch(secureStorageProvider),
      ),
    );

class AuthNotifier extends StateNotifier<AsyncValue<UsuarioModel?>> {
  final ApiClient _api;
  final SecureStorage _storage;

  AuthNotifier(this._api, this._storage) : super(const AsyncValue.loading()) {
    _init();
  }

  Future<void> _init() async {
    if (await _storage.isLoggedIn()) {
      try {
        final res = await _api.get('/me');
        state = AsyncValue.data(UsuarioModel.fromJson(res.data));
      } catch (_) {
        await _storage.clear();
        state = const AsyncValue.data(null);
      }
    } else {
      state = const AsyncValue.data(null);
    }
  }

  Future<void> login(String email, String password) async {
    state = const AsyncValue.loading();
    try {
      final res = await _api.post(
        '/auth/login',
        data: {'email': email, 'password': password},
      );

      final data = res.data;
      final user = UsuarioModel.fromJson(data['user']);

      await _storage.saveAuth(
        token: data['token'],
        userId: user.id,
        userName: user.name,
        perfil: user.perfil,
        pontoId: user.pontoCarregamentoId,
      );

      state = AsyncValue.data(user);
    } catch (e, st) {
      state = AsyncValue.error(e, st);
    }
  }

  Future<void> logout() async {
    await _storage.clear();
    state = const AsyncValue.data(null);
  }
}
