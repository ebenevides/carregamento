import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:flutter_secure_storage/flutter_secure_storage.dart';

final secureStorageProvider = Provider<SecureStorage>((ref) => SecureStorage());

class SecureStorage {
  static const _storage = FlutterSecureStorage();
  static const _keyToken = 'sanctum_token';
  static const _keyUserId = 'user_id';
  static const _keyUserName = 'user_name';
  static const _keyPerfil = 'user_perfil';
  static const _keyPontoId = 'ponto_carregamento_id';

  Future<void> saveAuth({
    required String token,
    required int userId,
    required String userName,
    required String perfil,
    required int? pontoId,
  }) async {
    await Future.wait([
      _storage.write(key: _keyToken, value: token),
      _storage.write(key: _keyUserId, value: userId.toString()),
      _storage.write(key: _keyUserName, value: userName),
      _storage.write(key: _keyPerfil, value: perfil),
      _storage.write(key: _keyPontoId, value: pontoId?.toString()),
    ]);
  }

  Future<String?> getToken() => _storage.read(key: _keyToken);
  Future<String?> getUserName() => _storage.read(key: _keyUserName);
  Future<String?> getPerfil() => _storage.read(key: _keyPerfil);

  Future<int?> getUserId() async {
    final v = await _storage.read(key: _keyUserId);
    return v != null ? int.tryParse(v) : null;
  }

  Future<int?> getPontoId() async {
    final v = await _storage.read(key: _keyPontoId);
    return v != null ? int.tryParse(v) : null;
  }

  Future<bool> isLoggedIn() async => (await getToken()) != null;

  Future<void> clear() => _storage.deleteAll();
}
