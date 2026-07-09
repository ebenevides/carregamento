import 'package:flutter/foundation.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';
import '../../features/auth/providers/auth_provider.dart';
import '../../features/auth/screens/login_screen.dart';
import '../../features/fila/screens/fila_screen.dart';
import '../../features/ordem/screens/ordem_detalhe_screen.dart';
import '../../features/motorista/screens/minha_carga_screen.dart';
import '../../features/chat/screens/chat_screen.dart';

final routerProvider = Provider<GoRouter>((ref) {
  final listenable = AuthListenable(ref);

  return GoRouter(
    initialLocation: '/login',
    refreshListenable: listenable,
    redirect: (context, state) {
      final authState = ref.read(authProvider);

      if (authState.isLoading) return null;

      final user = authState.valueOrNull;
      final loggedIn = user != null;
      final onLogin = state.matchedLocation == '/login';

      if (!loggedIn && !onLogin) return '/login';
      if (loggedIn && onLogin) {
        // Redirect based on profile
        if (user.isMotorista) return '/motorista/minha-carga';
        return '/fila';
      }

      // If logged in and trying to access wrong area for profile
      if (loggedIn && user.isMotorista) {
        final motoristaPath = state.matchedLocation.startsWith('/motorista') ||
            state.matchedLocation.startsWith('/chat');
        if (!motoristaPath && state.matchedLocation != '/login') {
          return '/motorista/minha-carga';
        }
      }

      return null;
    },
    routes: [
      GoRoute(path: '/login', builder: (_, __) => const LoginScreen()),
      GoRoute(path: '/fila', builder: (_, __) => const FilaScreen()),
      GoRoute(
        path: '/ordem/:id',
        builder: (_, state) => OrdemDetalheScreen(ordemId: state.pathParameters['id']!),
      ),
      GoRoute(
        path: '/motorista/minha-carga',
        builder: (_, __) => const MinhaCargaScreen(),
      ),
      GoRoute(
        path: '/chat/:ordemId',
        builder: (_, state) => ChatScreen(ordemId: state.pathParameters['ordemId']!),
      ),
    ],
  );
});

class AuthListenable extends ChangeNotifier {
  AuthListenable(Ref ref) {
    ref.listen(authProvider, (prev, next) => notifyListeners());
  }
}
