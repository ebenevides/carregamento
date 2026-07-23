import 'package:flutter/material.dart';

/// Cores de marca usadas para gerar o tema do aplicativo.
///
/// Outra identidade visual pode ser aplicada sem alterar widgets:
/// `AppTheme.light(palette: minhaPaleta)`.
@immutable
class AppPalette {
  const AppPalette({
    required this.primary,
    required this.onPrimary,
    required this.primaryContainer,
    required this.onPrimaryContainer,
    required this.secondary,
    required this.onSecondary,
    required this.surface,
    required this.surfaceContainer,
    required this.surfaceContainerHighest,
    required this.onSurface,
    required this.onSurfaceVariant,
    required this.outline,
    required this.outlineVariant,
    required this.shadow,
    required this.success,
    required this.onSuccess,
    required this.successContainer,
    required this.onSuccessContainer,
    required this.warning,
    required this.onWarning,
    required this.warningContainer,
    required this.onWarningContainer,
    required this.error,
    required this.onError,
    required this.errorContainer,
    required this.onErrorContainer,
    required this.info,
    required this.onInfo,
    required this.infoContainer,
    required this.onInfoContainer,
  });

  /// Paleta padrão: azul industrial, superfícies frias e status de alto contraste.
  static const industrialBlue = AppPalette(
    primary: Color(0xFF173B67),
    onPrimary: Color(0xFFFFFFFF),
    primaryContainer: Color(0xFFD5E4F5),
    onPrimaryContainer: Color(0xFF0A2848),
    secondary: Color(0xFF35658F),
    onSecondary: Color(0xFFFFFFFF),
    surface: Color(0xFFF8FAFC),
    surfaceContainer: Color(0xFFEAF0F5),
    surfaceContainerHighest: Color(0xFFE0E7ED),
    onSurface: Color(0xFF17212B),
    onSurfaceVariant: Color(0xFF465461),
    outline: Color(0xFF6D7A86),
    outlineVariant: Color(0xFFC3CDD6),
    shadow: Color(0xFF000000),
    success: Color(0xFF247A45),
    onSuccess: Color(0xFFFFFFFF),
    successContainer: Color(0xFFD8F3DF),
    onSuccessContainer: Color(0xFF0A4623),
    warning: Color(0xFF9A5A00),
    onWarning: Color(0xFFFFFFFF),
    warningContainer: Color(0xFFFFE3B3),
    onWarningContainer: Color(0xFF4A2800),
    error: Color(0xFFB3261E),
    onError: Color(0xFFFFFFFF),
    errorContainer: Color(0xFFF9DEDC),
    onErrorContainer: Color(0xFF410E0B),
    info: Color(0xFF315DA8),
    onInfo: Color(0xFFFFFFFF),
    infoContainer: Color(0xFFD9E5FF),
    onInfoContainer: Color(0xFF001A41),
  );

  final Color primary;
  final Color onPrimary;
  final Color primaryContainer;
  final Color onPrimaryContainer;
  final Color secondary;
  final Color onSecondary;
  final Color surface;
  final Color surfaceContainer;
  final Color surfaceContainerHighest;
  final Color onSurface;
  final Color onSurfaceVariant;
  final Color outline;
  final Color outlineVariant;
  final Color shadow;
  final Color success;
  final Color onSuccess;
  final Color successContainer;
  final Color onSuccessContainer;
  final Color warning;
  final Color onWarning;
  final Color warningContainer;
  final Color onWarningContainer;
  final Color error;
  final Color onError;
  final Color errorContainer;
  final Color onErrorContainer;
  final Color info;
  final Color onInfo;
  final Color infoContainer;
  final Color onInfoContainer;

  AppPalette copyWith({
    Color? primary,
    Color? onPrimary,
    Color? primaryContainer,
    Color? onPrimaryContainer,
    Color? secondary,
    Color? onSecondary,
    Color? surface,
    Color? surfaceContainer,
    Color? surfaceContainerHighest,
    Color? onSurface,
    Color? onSurfaceVariant,
    Color? outline,
    Color? outlineVariant,
    Color? shadow,
    Color? success,
    Color? onSuccess,
    Color? successContainer,
    Color? onSuccessContainer,
    Color? warning,
    Color? onWarning,
    Color? warningContainer,
    Color? onWarningContainer,
    Color? error,
    Color? onError,
    Color? errorContainer,
    Color? onErrorContainer,
    Color? info,
    Color? onInfo,
    Color? infoContainer,
    Color? onInfoContainer,
  }) => AppPalette(
    primary: primary ?? this.primary,
    onPrimary: onPrimary ?? this.onPrimary,
    primaryContainer: primaryContainer ?? this.primaryContainer,
    onPrimaryContainer: onPrimaryContainer ?? this.onPrimaryContainer,
    secondary: secondary ?? this.secondary,
    onSecondary: onSecondary ?? this.onSecondary,
    surface: surface ?? this.surface,
    surfaceContainer: surfaceContainer ?? this.surfaceContainer,
    surfaceContainerHighest:
        surfaceContainerHighest ?? this.surfaceContainerHighest,
    onSurface: onSurface ?? this.onSurface,
    onSurfaceVariant: onSurfaceVariant ?? this.onSurfaceVariant,
    outline: outline ?? this.outline,
    outlineVariant: outlineVariant ?? this.outlineVariant,
    shadow: shadow ?? this.shadow,
    success: success ?? this.success,
    onSuccess: onSuccess ?? this.onSuccess,
    successContainer: successContainer ?? this.successContainer,
    onSuccessContainer: onSuccessContainer ?? this.onSuccessContainer,
    warning: warning ?? this.warning,
    onWarning: onWarning ?? this.onWarning,
    warningContainer: warningContainer ?? this.warningContainer,
    onWarningContainer: onWarningContainer ?? this.onWarningContainer,
    error: error ?? this.error,
    onError: onError ?? this.onError,
    errorContainer: errorContainer ?? this.errorContainer,
    onErrorContainer: onErrorContainer ?? this.onErrorContainer,
    info: info ?? this.info,
    onInfo: onInfo ?? this.onInfo,
    infoContainer: infoContainer ?? this.infoContainer,
    onInfoContainer: onInfoContainer ?? this.onInfoContainer,
  );
}
