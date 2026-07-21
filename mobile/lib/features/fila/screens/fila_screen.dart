import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';

import '../../../core/theme/app_theme_tokens.dart';
import '../../auth/providers/auth_provider.dart';
import '../models/ordem_model.dart';
import '../providers/fila_provider.dart';

class FilaScreen extends ConsumerWidget {
  const FilaScreen({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final fila = ref.watch(filaProvider);
    final usuario = ref.watch(authProvider).valueOrNull;

    return Scaffold(
      appBar: AppBar(
        title: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            const Text('Minha fila'),
            if (usuario != null)
              Text(
                usuario.name,
                style: Theme.of(context).textTheme.labelMedium?.copyWith(
                  color: Theme.of(context).colorScheme.onPrimary,
                ),
              ),
          ],
        ),
        actions: [
          IconButton(
            tooltip: 'Atualizar fila',
            icon: const Icon(Icons.refresh),
            onPressed: () => ref.read(filaProvider.notifier).carregar(),
          ),
          IconButton(
            tooltip: 'Sair',
            icon: const Icon(Icons.logout),
            onPressed: () => ref.read(authProvider.notifier).logout(),
          ),
        ],
      ),
      body: fila.when(
        loading: () => const Center(child: CircularProgressIndicator()),
        error: (_, _) => _FeedbackState(
          icon: Icons.cloud_off_outlined,
          title: 'Não foi possível carregar a fila',
          actionLabel: 'Tentar novamente',
          onAction: () => ref.read(filaProvider.notifier).carregar(),
        ),
        data: (ordens) => ordens.isEmpty
            ? const _FeedbackState(
                icon: Icons.task_alt,
                title: 'Fila concluída',
                message: 'Nenhuma ordem aguardando carregamento.',
              )
            : RefreshIndicator(
                onRefresh: () => ref.read(filaProvider.notifier).carregar(),
                child: ListView.separated(
                  padding: EdgeInsets.all(context.appTokens.spaceMd),
                  itemCount: ordens.length,
                  separatorBuilder: (_, _) =>
                      SizedBox(height: context.appTokens.spaceSm),
                  itemBuilder: (_, index) =>
                      _OrdemCard(ordem: ordens[index], posicao: index + 1),
                ),
              ),
      ),
    );
  }
}

class _OrdemCard extends ConsumerWidget {
  const _OrdemCard({required this.ordem, required this.posicao});

  final OrdemModel ordem;
  final int posicao;

  Color _statusColor(BuildContext context) => switch (ordem.status) {
    'EM_CARREGAMENTO' => context.appTokens.success,
    'AGUARDANDO_CARREGAMENTO' => context.appTokens.warning,
    'DIVERGENCIA' => Theme.of(context).colorScheme.error,
    _ => Theme.of(context).colorScheme.outline,
  };

  IconData get _statusIcon => switch (ordem.status) {
    'EM_CARREGAMENTO' => Icons.local_shipping,
    'AGUARDANDO_CARREGAMENTO' => Icons.schedule,
    'DIVERGENCIA' => Icons.warning_amber,
    _ => Icons.info_outline,
  };

  Color _statusForeground(BuildContext context) => switch (ordem.status) {
    'EM_CARREGAMENTO' => context.appTokens.onSuccess,
    'AGUARDANDO_CARREGAMENTO' => context.appTokens.onWarning,
    'DIVERGENCIA' => Theme.of(context).colorScheme.onError,
    _ => Theme.of(context).colorScheme.surface,
  };

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final colors = Theme.of(context).colorScheme;
    final statusColor = _statusColor(context);

    return Card(
      child: InkWell(
        borderRadius: BorderRadius.circular(context.appTokens.radiusMd),
        onTap: () => context.push('/ordem/${ordem.id}'),
        child: Padding(
          padding: EdgeInsets.all(context.appTokens.spaceMd),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Row(
                children: [
                  Container(
                    width: 42,
                    height: 42,
                    alignment: Alignment.center,
                    decoration: BoxDecoration(
                      color: statusColor,
                      borderRadius: BorderRadius.circular(
                        context.appTokens.radiusSm,
                      ),
                    ),
                    child: Text(
                      '$posicao',
                      style: TextStyle(
                        color: _statusForeground(context),
                        fontSize: 20,
                        fontWeight: FontWeight.w800,
                      ),
                    ),
                  ),
                  SizedBox(width: context.appTokens.spaceMd),
                  Expanded(
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Text(
                          ordem.placaVeiculo,
                          style: Theme.of(context).textTheme.titleLarge
                              ?.copyWith(fontWeight: FontWeight.w800),
                        ),
                        Text(
                          ordem.motoristaNome ?? 'Motorista não informado',
                          style: Theme.of(context).textTheme.bodyMedium
                              ?.copyWith(color: colors.onSurfaceVariant),
                        ),
                      ],
                    ),
                  ),
                  if (ordem.temDivergencia)
                    Icon(
                      Icons.warning_amber,
                      color: colors.error,
                      semanticLabel: 'Ordem com divergência',
                    ),
                ],
              ),
              SizedBox(height: context.appTokens.spaceSm),
              Align(
                alignment: Alignment.centerRight,
                child: _StatusChip(
                  label: ordem.statusLabel,
                  icon: _statusIcon,
                  color: statusColor,
                ),
              ),
              const Divider(height: 24),
              Row(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Expanded(
                    child: _DataItem(
                      icon: Icons.inventory_2_outlined,
                      label: 'Produto',
                      value: ordem.produtoDescricao ?? ordem.produtoCodigo,
                      detail: ordem.produtoDescricao == null
                          ? null
                          : ordem.produtoCodigo,
                    ),
                  ),
                  SizedBox(width: context.appTokens.spaceMd),
                  Expanded(
                    child: _DataItem(
                      icon: Icons.scale_outlined,
                      label: 'Qtd. prevista',
                      value:
                          '${_quantidade(ordem.quantidadePrevista)} ${ordem.unidade}',
                    ),
                  ),
                ],
              ),
              SizedBox(height: context.appTokens.spaceMd),
              Row(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Expanded(
                    child: _DataItem(
                      icon: Icons.layers_outlined,
                      label: 'Pilha',
                      value:
                          ordem.pilhaProduto?['descricao'] ??
                          ordem.pilhaProduto?['codigo'] ??
                          '—',
                      detail: ordem.pilhaProduto?['descricao'] == null
                          ? null
                          : ordem.pilhaProduto?['codigo'],
                    ),
                  ),
                  SizedBox(width: context.appTokens.spaceMd),
                  Expanded(
                    child: _DataItem(
                      icon: Icons.receipt_long_outlined,
                      label: 'Ticket',
                      value: ordem.ticketGuardian ?? '—',
                    ),
                  ),
                ],
              ),
              SizedBox(height: context.appTokens.spaceMd),
              if (ordem.aguardando)
                SizedBox(
                  width: double.infinity,
                  child: FilledButton.icon(
                    style: FilledButton.styleFrom(
                      backgroundColor: context.appTokens.success,
                      foregroundColor: context.appTokens.onSuccess,
                    ),
                    icon: const Icon(Icons.play_arrow),
                    label: const Text('Iniciar carregamento'),
                    onPressed: () => _confirmarAcao(
                      context,
                      'Iniciar carregamento?',
                      'Iniciar',
                      () => ref
                          .read(filaProvider.notifier)
                          .iniciarCarregamento(ordem.id),
                    ),
                  ),
                ),
              if (ordem.emCarregamento) ...[
                Row(
                  children: [
                    Expanded(
                      child: OutlinedButton.icon(
                        style: OutlinedButton.styleFrom(
                          foregroundColor: colors.error,
                          side: BorderSide(color: colors.error),
                        ),
                        icon: const Icon(Icons.close),
                        label: const Text('Rejeitar'),
                        onPressed: () => _mostrarModalRejeitar(context, ref),
                      ),
                    ),
                    SizedBox(width: context.appTokens.spaceSm),
                    Expanded(
                      child: FilledButton.icon(
                        icon: const Icon(Icons.check),
                        label: const Text('Concluir'),
                        onPressed: () => _confirmarAcao(
                          context,
                          'Concluir carregamento?',
                          'Concluir',
                          () => ref
                              .read(filaProvider.notifier)
                              .concluirCarregamento(ordem.id),
                        ),
                      ),
                    ),
                  ],
                ),
                SizedBox(height: context.appTokens.spaceSm),
                SizedBox(
                  width: double.infinity,
                  child: OutlinedButton.icon(
                    icon: const Icon(Icons.chat_bubble_outline),
                    label: const Text('Abrir chat da ordem'),
                    onPressed: () => context.push('/chat/${ordem.id}'),
                  ),
                ),
              ],
            ],
          ),
        ),
      ),
    );
  }

  Future<void> _confirmarAcao(
    BuildContext context,
    String titulo,
    String confirmar,
    Future<void> Function() action,
  ) async {
    final confirmou = await showDialog<bool>(
      context: context,
      builder: (dialogContext) => AlertDialog(
        title: Text(titulo),
        content: Text('Placa ${ordem.placaVeiculo}'),
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

  Future<void> _mostrarModalRejeitar(
    BuildContext context,
    WidgetRef ref,
  ) async {
    final motivo = await showDialog<String>(
      context: context,
      builder: (_) => _RejeitarDialog(placa: ordem.placaVeiculo),
    );
    if (motivo == null || !context.mounted) return;
    try {
      await ref.read(filaProvider.notifier).rejeitar(ordem.id, motivo);
    } catch (_) {
      if (context.mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          const SnackBar(
            content: Text('Não foi possível rejeitar o caminhão.'),
          ),
        );
      }
    }
  }
}

class _RejeitarDialog extends StatefulWidget {
  const _RejeitarDialog({required this.placa});
  final String placa;

  @override
  State<_RejeitarDialog> createState() => _RejeitarDialogState();
}

class _RejeitarDialogState extends State<_RejeitarDialog> {
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
    final colors = Theme.of(context).colorScheme;
    return AlertDialog(
      icon: Icon(Icons.cancel_outlined, color: colors.error, size: 36),
      title: const Text('Rejeitar caminhão'),
      content: Column(
        mainAxisSize: MainAxisSize.min,
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Text('Placa', style: Theme.of(context).textTheme.labelMedium),
          Text(
            widget.placa,
            style: Theme.of(
              context,
            ).textTheme.titleLarge?.copyWith(fontWeight: FontWeight.w800),
          ),
          SizedBox(height: context.appTokens.spaceMd),
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
        ],
      ),
      actions: [
        TextButton(
          onPressed: () => Navigator.pop(context),
          child: const Text('Cancelar'),
        ),
        FilledButton.icon(
          style: FilledButton.styleFrom(
            backgroundColor: colors.error,
            foregroundColor: colors.onError,
          ),
          onPressed: _confirmar,
          icon: const Icon(Icons.close),
          label: const Text('Rejeitar'),
        ),
      ],
    );
  }
}

class _DataItem extends StatelessWidget {
  const _DataItem({
    required this.icon,
    required this.label,
    required this.value,
    this.detail,
  });
  final IconData icon;
  final String label;
  final String value;
  final String? detail;

  @override
  Widget build(BuildContext context) {
    final colors = Theme.of(context).colorScheme;
    return Row(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Container(
          padding: const EdgeInsets.all(8),
          decoration: BoxDecoration(
            color: colors.primaryContainer,
            borderRadius: BorderRadius.circular(context.appTokens.radiusSm),
          ),
          child: Icon(icon, color: colors.primary, size: 22),
        ),
        const SizedBox(width: 10),
        Expanded(
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Text(
                label,
                style: Theme.of(context).textTheme.labelMedium?.copyWith(
                  color: colors.onSurfaceVariant,
                ),
              ),
              Text(
                value,
                maxLines: 2,
                overflow: TextOverflow.ellipsis,
                style: Theme.of(
                  context,
                ).textTheme.bodyLarge?.copyWith(fontWeight: FontWeight.w700),
              ),
              if (detail != null)
                Text(
                  detail!,
                  maxLines: 1,
                  overflow: TextOverflow.ellipsis,
                  style: Theme.of(context).textTheme.labelSmall?.copyWith(
                    color: colors.onSurfaceVariant,
                  ),
                ),
            ],
          ),
        ),
      ],
    );
  }
}

class _StatusChip extends StatelessWidget {
  const _StatusChip({
    required this.label,
    required this.icon,
    required this.color,
  });
  final String label;
  final IconData icon;
  final Color color;

  @override
  Widget build(BuildContext context) => Container(
    padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 6),
    decoration: BoxDecoration(
      color: color.withValues(alpha: .1),
      border: Border.all(color: color),
      borderRadius: BorderRadius.circular(context.appTokens.radiusLg),
    ),
    child: Row(
      mainAxisSize: MainAxisSize.min,
      children: [
        Icon(icon, size: 16, color: color),
        const SizedBox(width: 6),
        ConstrainedBox(
          constraints: const BoxConstraints(maxWidth: 180),
          child: Text(
            label,
            overflow: TextOverflow.ellipsis,
            style: TextStyle(color: color, fontWeight: FontWeight.w700),
          ),
        ),
      ],
    ),
  );
}

class _FeedbackState extends StatelessWidget {
  const _FeedbackState({
    required this.icon,
    required this.title,
    this.message,
    this.actionLabel,
    this.onAction,
  });
  final IconData icon;
  final String title;
  final String? message;
  final String? actionLabel;
  final VoidCallback? onAction;

  @override
  Widget build(BuildContext context) => Center(
    child: Padding(
      padding: const EdgeInsets.all(32),
      child: Column(
        mainAxisSize: MainAxisSize.min,
        children: [
          Icon(icon, size: 56, color: Theme.of(context).colorScheme.primary),
          const SizedBox(height: 16),
          Text(
            title,
            textAlign: TextAlign.center,
            style: Theme.of(
              context,
            ).textTheme.titleLarge?.copyWith(fontWeight: FontWeight.w700),
          ),
          if (message != null) ...[
            const SizedBox(height: 8),
            Text(message!, textAlign: TextAlign.center),
          ],
          if (onAction != null) ...[
            const SizedBox(height: 16),
            FilledButton(onPressed: onAction, child: Text(actionLabel!)),
          ],
        ],
      ),
    ),
  );
}

String _quantidade(double value) => value == value.roundToDouble()
    ? value.toStringAsFixed(0)
    : value.toStringAsFixed(1).replaceAll('.', ',');
