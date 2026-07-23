import 'package:flutter/material.dart';

import 'app_palette.dart';
import 'app_theme_tokens.dart';

abstract final class AppTheme {
  static ThemeData light({
    AppPalette palette = AppPalette.industrialBlue,
    AppThemeTokens? tokens,
  }) {
    final appTokens = tokens ?? AppThemeTokens.fromPalette(palette);
    final colorScheme =
        ColorScheme.fromSeed(
          seedColor: palette.primary,
          brightness: Brightness.light,
        ).copyWith(
          primary: palette.primary,
          onPrimary: palette.onPrimary,
          primaryContainer: palette.primaryContainer,
          onPrimaryContainer: palette.onPrimaryContainer,
          secondary: palette.secondary,
          onSecondary: palette.onSecondary,
          surface: palette.surface,
          surfaceContainer: palette.surfaceContainer,
          surfaceContainerHighest: palette.surfaceContainerHighest,
          onSurface: palette.onSurface,
          onSurfaceVariant: palette.onSurfaceVariant,
          outline: palette.outline,
          outlineVariant: palette.outlineVariant,
          shadow: palette.shadow,
          error: palette.error,
          onError: palette.onError,
          errorContainer: palette.errorContainer,
          onErrorContainer: palette.onErrorContainer,
        );

    final cardShape = RoundedRectangleBorder(
      borderRadius: BorderRadius.circular(appTokens.radiusMd),
      side: BorderSide(color: colorScheme.outlineVariant),
    );
    final controlBorderRadius = BorderRadius.circular(appTokens.radiusSm);
    final controlShape = RoundedRectangleBorder(
      borderRadius: controlBorderRadius,
    );

    return ThemeData(
      useMaterial3: true,
      colorScheme: colorScheme,
      scaffoldBackgroundColor: colorScheme.surface,
      extensions: <ThemeExtension<dynamic>>[appTokens],
      appBarTheme: AppBarTheme(
        backgroundColor: colorScheme.primary,
        foregroundColor: colorScheme.onPrimary,
        elevation: 0,
        centerTitle: false,
        titleTextStyle: TextStyle(
          color: colorScheme.onPrimary,
          fontSize: 20,
          fontWeight: FontWeight.w700,
        ),
      ),
      cardTheme: CardThemeData(
        color: colorScheme.surface,
        elevation: 0,
        margin: EdgeInsets.zero,
        shape: cardShape,
      ),
      inputDecorationTheme: InputDecorationTheme(
        filled: true,
        fillColor: colorScheme.surface,
        contentPadding: EdgeInsets.symmetric(
          horizontal: appTokens.spaceMd,
          vertical: appTokens.spaceMd,
        ),
        border: OutlineInputBorder(borderRadius: controlBorderRadius),
        enabledBorder: OutlineInputBorder(
          borderRadius: controlBorderRadius,
          borderSide: BorderSide(color: colorScheme.outline),
        ),
        focusedBorder: OutlineInputBorder(
          borderRadius: controlBorderRadius,
          borderSide: BorderSide(color: colorScheme.primary, width: 2),
        ),
      ),
      filledButtonTheme: FilledButtonThemeData(
        style: FilledButton.styleFrom(
          minimumSize: const Size(64, 48),
          padding: EdgeInsets.symmetric(horizontal: appTokens.spaceLg),
          shape: controlShape,
          textStyle: const TextStyle(fontSize: 16, fontWeight: FontWeight.w700),
        ),
      ),
      outlinedButtonTheme: OutlinedButtonThemeData(
        style: OutlinedButton.styleFrom(
          minimumSize: const Size(64, 48),
          padding: EdgeInsets.symmetric(horizontal: appTokens.spaceLg),
          shape: controlShape,
          textStyle: const TextStyle(fontSize: 16, fontWeight: FontWeight.w700),
        ),
      ),
      textButtonTheme: TextButtonThemeData(
        style: TextButton.styleFrom(
          minimumSize: const Size(48, 48),
          shape: controlShape,
          textStyle: const TextStyle(fontSize: 15, fontWeight: FontWeight.w600),
        ),
      ),
      chipTheme: ChipThemeData(
        shape: RoundedRectangleBorder(
          borderRadius: BorderRadius.circular(appTokens.radiusLg),
        ),
        side: BorderSide.none,
      ),
      dividerTheme: DividerThemeData(
        color: colorScheme.outlineVariant,
        thickness: 1,
      ),
      progressIndicatorTheme: ProgressIndicatorThemeData(
        color: colorScheme.primary,
      ),
      snackBarTheme: SnackBarThemeData(
        behavior: SnackBarBehavior.floating,
        shape: controlShape,
      ),
      visualDensity: VisualDensity.standard,
    );
  }
}
