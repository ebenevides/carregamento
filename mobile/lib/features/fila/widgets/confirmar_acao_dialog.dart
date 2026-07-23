import 'package:flutter/material.dart';

Future<bool?> showConfirmarAcaoDialog(
  BuildContext context, {
  required String titulo,
  required String confirmar,
  required String placa,
}) {
  return showDialog<bool>(
    context: context,
    builder: (dialogContext) => AlertDialog(
      title: Text(titulo),
      content: Text('Placa $placa'),
      actions: [
        TextButton(
          onPressed: () => Navigator.pop(dialogContext, false),
          child: const Text('Cancelar'),
        ),
        FilledButton(
          onPressed: () => Navigator.pop(dialogContext, true),
          child: Text(confirmar),
        ),
      ],
    ),
  );
}

Future<void> confirmarAcao({
  required BuildContext context,
  required String titulo,
  required String confirmar,
  required String placa,
  required Future<void> Function() action,
}) async {
  final confirmou = await showConfirmarAcaoDialog(
    context,
    titulo: titulo,
    confirmar: confirmar,
    placa: placa,
  );
  if (confirmou != true || !context.mounted) return;
  try {
    await action();
  } catch (_) {
    if (context.mounted) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(
          content: Text('Não foi possível concluir a ação. Tente novamente.'),
        ),
      );
    }
  }
}
