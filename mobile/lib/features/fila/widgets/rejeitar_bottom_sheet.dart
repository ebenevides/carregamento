import 'package:flutter/material.dart';

import '../../../core/theme/app_theme_tokens.dart';
import '../providers/fila_provider.dart';

Future<String?> showRejeitarBottomSheet(
  BuildContext context, {
  required String placa,
}) {
  return showModalBottomSheet<String>(
    context: context,
    isScrollControlled: true,
    useSafeArea: true,
    shape: const RoundedRectangleBorder(
      borderRadius: BorderRadius.vertical(top: Radius.circular(16)),
    ),
    builder: (sheetContext) => Padding(
      padding: EdgeInsets.only(
        bottom: MediaQuery.of(sheetContext).viewInsets.bottom,
      ),
      child: _RejeitarBottomSheetContent(placa: placa),
    ),
  );
}

class _RejeitarBottomSheetContent extends StatefulWidget {
  const _RejeitarBottomSheetContent({required this.placa});
  final String placa;

  @override
  State<_RejeitarBottomSheetContent> createState() =>
      _RejeitarBottomSheetContentState();
}

class _RejeitarBottomSheetContentState
    extends State<_RejeitarBottomSheetContent> {
  final _controller = TextEditingController();
  String? _erro;

  @override
  void dispose() {
    _controller.dispose();
    super.dispose();
  }

  void _confirmar() {
    final erro = validarMotivoRejeicao(_controller.text);
    if (erro != null) return setState(() => _erro = erro);
    Navigator.pop(context, _controller.text.trim());
  }

  @override
  Widget build(BuildContext context) {
    final tokens = context.appTokens;
    final colors = Theme.of(context).colorScheme;

    return Padding(
      padding: EdgeInsets.all(tokens.spaceMd),
      child: Column(
        mainAxisSize: MainAxisSize.min,
        crossAxisAlignment: CrossAxisAlignment.stretch,
        children: [
          Center(
            child: Container(
              width: 32,
              height: 4,
              decoration: BoxDecoration(
                color: colors.onSurfaceVariant.withValues(alpha: .3),
                borderRadius: BorderRadius.circular(2),
              ),
            ),
          ),
          SizedBox(height: tokens.spaceMd),
          Row(
            children: [
              Icon(Icons.cancel_outlined, color: colors.error, size: 36),
              SizedBox(width: tokens.spaceSm),
              Expanded(
                child: Text(
                  'Rejeitar caminhão',
                  style: Theme.of(context).textTheme.titleLarge?.copyWith(
                    fontWeight: FontWeight.w700,
                  ),
                ),
              ),
            ],
          ),
          SizedBox(height: tokens.spaceMd),
          Text('Placa', style: Theme.of(context).textTheme.labelMedium),
          Text(
            widget.placa,
            style: Theme.of(context).textTheme.titleLarge?.copyWith(
              fontWeight: FontWeight.w800,
            ),
          ),
          SizedBox(height: tokens.spaceMd),
          TextField(
            controller: _controller,
            minLines: 2,
            maxLines: 3,
            maxLength: 1000,
            autofocus: true,
            decoration: InputDecoration(
              labelText: 'Motivo da rejeição *',
              hintText: 'Mínimo de 5 caracteres',
              errorText: _erro,
            ),
          ),
          SizedBox(height: tokens.spaceMd),
          Row(
            children: [
              Expanded(
                child: TextButton(
                  onPressed: () => Navigator.pop(context),
                  child: const Text('Cancelar'),
                ),
              ),
              SizedBox(width: tokens.spaceSm),
              Expanded(
                child: FilledButton.icon(
                  style: FilledButton.styleFrom(
                    backgroundColor: colors.error,
                    foregroundColor: colors.onError,
                  ),
                  onPressed: _confirmar,
                  icon: const Icon(Icons.close),
                  label: const Text('Rejeitar'),
                ),
              ),
            ],
          ),
        ],
      ),
    );
  }
}
