import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import 'core/providers/router_provider.dart';
import 'core/theme/app_palette.dart';
import 'core/theme/app_theme.dart';

void main() {
  runApp(const ProviderScope(child: CarregamentoApp()));
}

class CarregamentoApp extends ConsumerWidget {
  const CarregamentoApp({super.key, this.palette = AppPalette.industrialBlue});

  /// Permite trocar identidade visual sem alterar telas ou componentes.
  final AppPalette palette;

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final router = ref.watch(routerProvider);

    return MaterialApp.router(
      title: 'Carregamento',
      debugShowCheckedModeBanner: false,
      theme: AppTheme.light(palette: palette),
      routerConfig: router,
    );
  }
}
