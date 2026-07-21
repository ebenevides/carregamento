import 'package:carregamento_operador/core/theme/app_palette.dart';
import 'package:carregamento_operador/core/theme/app_theme.dart';
import 'package:carregamento_operador/core/theme/app_theme_tokens.dart';
import 'package:flutter/material.dart';
import 'package:flutter_test/flutter_test.dart';

void main() {
  test('AppTheme aplica paleta customizada e tokens semânticos', () {
    const customPrimary = Color(0xFF4A148C);
    const customPrimaryContainer = Color(0xFFE1BEE7);
    const customSuccess = Color(0xFF00695C);
    final palette = AppPalette.industrialBlue.copyWith(
      primary: customPrimary,
      primaryContainer: customPrimaryContainer,
      success: customSuccess,
    );

    final theme = AppTheme.light(palette: palette);
    final tokens = theme.extension<AppThemeTokens>();

    expect(theme.colorScheme.primary, customPrimary);
    expect(theme.colorScheme.primaryContainer, customPrimaryContainer);
    expect(tokens?.success, customSuccess);
    expect(theme.useMaterial3, isTrue);
  });
}
