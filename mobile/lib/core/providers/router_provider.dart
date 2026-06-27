import 'package:flutter/foundation.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';
import '../../features/auth/providers/auth_provider.dart';
import '../../features/auth/screens/login_screen.dart';
import '../../features/fila/screens/fila_screen.dart';
import '../../features/ordem/screens/ordem_detalhe_screen.dart';

final routerProvider = Provider<GoRouter>((ref) {
  final listenable = AuthListenable(ref);

  return GoRouter(
    initialLocation: '/fila',
    refreshListenable: listenable,
    redirect: (context, state) {
      final authState = ref.read(authProvider);

      if (authState.isLoading) return null;

      final loggedIn = authState.valueOrNull != null;
      final onLogin = state.matchedLocation == '/login';

      if (!loggedIn && !onLogin) return '/login';
      if (loggedIn && onLogin) return '/fila';
      return null;
    },
    routes: [
      GoRoute(path: '/login', builder: (_, __) => const LoginScreen()),
      GoRoute(path: '/fila', builder: (_, __) => const FilaScreen()),
      GoRoute(
        path: '/ordem/:id',
        builder: (_, state) => OrdemDetalheScreen(ordemId: state.pathParameters['id']!),
      ),
    ],
  );
});

class AuthListenable extends ChangeNotifier {
  AuthListenable(Ref ref) {
    ref.listen(authProvider, (prev, next) => notifyListeners());
  }
}
