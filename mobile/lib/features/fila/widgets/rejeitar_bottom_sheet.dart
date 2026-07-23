import 'package:flutter/material.dart';

import '../../../core/theme/app_theme_tokens.dart';
import '../providers/fila_provider.dart';

const _motivosRejeicao = [
  ('Veículo divergente', 'Placa ou veículo incorreto'),
  ('Motorista divergente', 'Motorista diferente da ordem'),
  ('Produto divergente', 'Produto diferente da ordem'),
  ('Quantidade divergente', 'Volume diferente do previsto'),
  ('Ticket inválido', 'Ticket ausente ou incorreto'),
  ('Tara inválida', 'Peso de tara inconsistente'),
  ('Pilha sem produto configurado', 'Pilha não possui produto'),
  ('Ponto de carregamento indisponível', 'Ponto sem condição de operar'),
  ('Outro', 'Outro problema operacional'),
];

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
  String? _motivoSelecionado;
  final _obsController = TextEditingController();
  String? _erro;

  @override
  void dispose() {
    _obsController.dispose();
    super.dispose();
  }

  void _confirmar() {
    if (_motivoSelecionado == null) {
      return setState(() => _erro = 'Selecione o motivo da rejeição');
    }

    final obs = _obsController.text.trim();
    final descricao = obs.isEmpty
        ? _motivoSelecionado!
        : '$_motivoSelecionado\nOBS: $obs';

    final erro = validarMotivoRejeicao(descricao);
    if (erro != null) return setState(() => _erro = erro);
    Navigator.pop(context, descricao);
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
          DropdownButtonFormField<String>(
            initialValue: _motivoSelecionado,
            decoration: InputDecoration(
              labelText: 'Motivo da rejeição *',
              errorText: _erro,
              helperText: _motivoSelecionado != null
                  ? _motivosRejeicao.firstWhere((m) => m.$1 == _motivoSelecionado).$2
                  : null,
              helperMaxLines: 2,
            ),
            isExpanded: true,
            hint: const Text('Selecione o motivo'),
            items: _motivosRejeicao.map((m) {
              return DropdownMenuItem<String>(
                value: m.$1,
                child: Text(m.$1),
              );
            }).toList(),
            onChanged: (value) {
              setState(() {
                _motivoSelecionado = value;
                _erro = null;
              });
            },
          ),
          SizedBox(height: tokens.spaceMd),
          TextField(
            controller: _obsController,
            minLines: 1,
            maxLines: 3,
            maxLength: 500,
            decoration: const InputDecoration(
              labelText: 'Observação (opcional)',
              hintText: 'Detalhes adicionais sobre o problema…',
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
