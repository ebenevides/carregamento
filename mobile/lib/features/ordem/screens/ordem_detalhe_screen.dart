import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';

import '../../../core/theme/app_theme_tokens.dart';
import '../../divergencia/screens/registrar_divergencia_screen.dart';
import '../../fila/models/ordem_model.dart';
import '../../fila/providers/fila_provider.dart';
import '../../fila/widgets/confirmar_acao_dialog.dart';
import '../../fila/widgets/rejeitar_bottom_sheet.dart';

class OrdemDetalheScreen extends ConsumerWidget {
  const OrdemDetalheScreen({super.key, required this.ordemId});

  final String ordemId;

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final ordemAsync = ref.watch(ordemDetalheProvider(ordemId));
    final ordemValue = ordemAsync.valueOrNull;

    return Scaffold(
      appBar: AppBar(
        title: const Text('Detalhe da ordem'),
        actions: [
          if (ordemValue != null)
            IconButton(
              tooltip: 'Registrar divergência',
              icon: const Icon(Icons.warning_amber_outlined),
              onPressed: () => Navigator.of(context).push(
                MaterialPageRoute<void>(
                  builder: (_) =>
                      RegistrarDivergenciaScreen(ordemId: ordemValue.id),
                ),
              ),
            ),
        ],
      ),
      body: ordemAsync.when(
        loading: () => const Center(child: CircularProgressIndicator()),
        error: (_, _) => _ErroDetalhe(
          onRetry: () => ref.invalidate(ordemDetalheProvider(ordemId)),
        ),
        data: (ordem) => _ConteudoDetalhe(ordem: ordem, ref: ref),
      ),
      bottomNavigationBar: _buildBottomBar(context, ref, ordemValue),
    );
  }
}

class _ConteudoDetalhe extends StatelessWidget {
  const _ConteudoDetalhe({required this.ordem, required this.ref});

  final OrdemModel ordem;
  final WidgetRef ref;

  @override
  Widget build(BuildContext context) {
    final tokens = context.appTokens;
    final pilha = ordem.pilhaProduto?['codigo']?.toString();
    final pontoCodigo = ordem.pontoCarregamento?['codigo']?.toString();
    final pontoDescricao = ordem.pontoCarregamento?['descricao']?.toString();

    return SafeArea(
      child: ListView(
        padding: EdgeInsets.all(tokens.spaceMd),
        children: [
          _HeroOrdem(ordem: ordem),
          if (ordem.temDivergencia) ...[
            SizedBox(height: tokens.spaceSm),
            _AvisoDivergencia(total: ordem.divergenciasAbertas ?? 0),
          ],
          SizedBox(height: tokens.spaceMd),
          Text('Operação', style: Theme.of(context).textTheme.titleMedium),
          SizedBox(height: tokens.spaceSm),
          Row(
            children: [
              Expanded(
                child: _DestaqueCard(
                  icon: Icons.inventory_2_outlined,
                  label: 'Produto',
                  value: ordem.produtoDescricao ?? ordem.produtoCodigo,
                  supporting: ordem.produtoDescricao == null
                      ? null
                      : ordem.produtoCodigo,
                ),
              ),
              SizedBox(width: tokens.spaceSm),
              Expanded(
                child: _DestaqueCard(
                  icon: Icons.scale_outlined,
                  label: 'Quantidade',
                  value:
                      '${_formatarQuantidade(ordem.quantidadePrevista)} ${ordem.unidade}',
                ),
              ),
            ],
          ),
          SizedBox(height: tokens.spaceSm),
          Row(
            children: [
              Expanded(
                child: _DestaqueCard(
                  icon: Icons.layers_outlined,
                  label: 'Pilha',
                  value: _valorOuTraco(pilha),
                ),
              ),
              SizedBox(width: tokens.spaceSm),
              Expanded(
                child: _DestaqueCard(
                  icon: Icons.location_on_outlined,
                  label: 'Ponto',
                  value: _valorOuTraco(pontoCodigo),
                  supporting: pontoDescricao,
                ),
              ),
            ],
          ),
          SizedBox(height: tokens.spaceMd),
          _InfoCard(
            title: 'Dados da carga',
            items: [
              _InfoItem('Ticket Guardian', ordem.ticketGuardian ?? '—'),
              _InfoItem('Pedido', ordem.pedidoNumero ?? '—'),
              if (ordem.placaCarreta != null)
                _InfoItem('Carreta', ordem.placaCarreta!),
              if (ordem.clienteNome != null)
                _InfoItem('Cliente', ordem.clienteNome!),
              _InfoItem(
                'Tara',
                ordem.tara == null
                    ? '—'
                    : '${_formatarQuantidade(ordem.tara!)} kg',
              ),
            ],
          ),
          SizedBox(height: tokens.spaceSm),
          _CardMotorista(ordem: ordem),
        ],
      ),
    );
  }
}

class _HeroOrdem extends StatelessWidget {
  const _HeroOrdem({required this.ordem});

  final OrdemModel ordem;

  @override
  Widget build(BuildContext context) {
    final tokens = context.appTokens;
    final colors = Theme.of(context).colorScheme;
    final statusColor = switch (ordem.status) {
      'EM_CARREGAMENTO' => tokens.success,
      'AGUARDANDO_CARREGAMENTO' => tokens.warning,
      _ => tokens.info,
    };
    final onStatusColor = switch (ordem.status) {
      'EM_CARREGAMENTO' => tokens.onSuccess,
      'AGUARDANDO_CARREGAMENTO' => tokens.onWarning,
      _ => tokens.onInfo,
    };

    return Container(
      padding: EdgeInsets.all(tokens.spaceLg),
      decoration: BoxDecoration(
        color: colors.primaryContainer,
        borderRadius: BorderRadius.circular(tokens.radiusLg),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Text(
            'VEÍCULO',
            style: Theme.of(context).textTheme.labelMedium?.copyWith(
              color: colors.onPrimaryContainer.withValues(alpha: .7),
            ),
          ),
          SizedBox(height: tokens.spaceXs),
          Text(
            ordem.placaVeiculo,
            style: Theme.of(context).textTheme.headlineMedium?.copyWith(
              fontWeight: FontWeight.w800,
              letterSpacing: 1.5,
            ),
          ),
          SizedBox(height: tokens.spaceMd),
          Container(
            padding: EdgeInsets.symmetric(
              horizontal: tokens.spaceSm,
              vertical: tokens.spaceXs,
            ),
            decoration: BoxDecoration(
              color: statusColor,
              borderRadius: BorderRadius.circular(tokens.radiusLg),
            ),
            child: Row(
              mainAxisSize: MainAxisSize.min,
              children: [
                Icon(Icons.circle, size: 10, color: onStatusColor),
                SizedBox(width: tokens.spaceSm),
                Text(
                  ordem.statusLabel,
                  style: TextStyle(
                    color: onStatusColor,
                    fontWeight: FontWeight.w700,
                  ),
                ),
              ],
            ),
          ),
        ],
      ),
    );
  }
}

class _DestaqueCard extends StatelessWidget {
  const _DestaqueCard({
    required this.icon,
    required this.label,
    required this.value,
    this.supporting,
  });

  final IconData icon;
  final String label;
  final String value;
  final String? supporting;

  @override
  Widget build(BuildContext context) {
    final tokens = context.appTokens;
    final colors = Theme.of(context).colorScheme;
    return Card(
      child: Padding(
        padding: EdgeInsets.all(tokens.spaceMd),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Icon(icon, color: colors.primary),
            SizedBox(height: tokens.spaceSm),
            Text(
              label,
              style: Theme.of(
                context,
              ).textTheme.labelMedium?.copyWith(color: colors.onSurfaceVariant),
            ),
            SizedBox(height: tokens.spaceXs),
            Text(
              value,
              maxLines: 2,
              overflow: TextOverflow.ellipsis,
              style: const TextStyle(fontWeight: FontWeight.w700),
            ),
            if (supporting != null && supporting!.isNotEmpty)
              Text(
                supporting!,
                maxLines: 1,
                overflow: TextOverflow.ellipsis,
                style: Theme.of(context).textTheme.bodySmall,
              ),
          ],
        ),
      ),
    );
  }
}

class _InfoItem {
  const _InfoItem(this.label, this.value);
  final String label;
  final String value;
}

class _InfoCard extends StatelessWidget {
  const _InfoCard({required this.title, required this.items});
  final String title;
  final List<_InfoItem> items;

  @override
  Widget build(BuildContext context) {
    final tokens = context.appTokens;
    return Card(
      child: Padding(
        padding: EdgeInsets.all(tokens.spaceMd),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Text(title, style: Theme.of(context).textTheme.titleMedium),
            SizedBox(height: tokens.spaceSm),
            ...items.map(
              (item) => Padding(
                padding: EdgeInsets.symmetric(vertical: tokens.spaceXs),
                child: Row(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Expanded(
                      child: Text(
                        item.label,
                        style: TextStyle(
                          color: Theme.of(context).colorScheme.onSurfaceVariant,
                        ),
                      ),
                    ),
                    Expanded(
                      child: Text(
                        item.value,
                        textAlign: TextAlign.end,
                        style: const TextStyle(fontWeight: FontWeight.w600),
                      ),
                    ),
                  ],
                ),
              ),
            ),
          ],
        ),
      ),
    );
  }
}

class _AvisoDivergencia extends StatelessWidget {
  const _AvisoDivergencia({required this.total});
  final int total;

  @override
  Widget build(BuildContext context) {
    final tokens = context.appTokens;
    return Container(
      padding: EdgeInsets.all(tokens.spaceMd),
      decoration: BoxDecoration(
        color: Theme.of(context).colorScheme.errorContainer,
        borderRadius: BorderRadius.circular(tokens.radiusMd),
      ),
      child: Row(
        children: [
          Icon(
            Icons.report_problem_outlined,
            color: Theme.of(context).colorScheme.error,
          ),
          SizedBox(width: tokens.spaceSm),
          Expanded(
            child: Text(
              '$total divergência(s) aberta(s)',
              style: const TextStyle(fontWeight: FontWeight.w700),
            ),
          ),
        ],
      ),
    );
  }
}

class _CardMotorista extends StatelessWidget {
  const _CardMotorista({required this.ordem});
  final OrdemModel ordem;

  @override
  Widget build(BuildContext context) {
    final tokens = context.appTokens;
    final colors = Theme.of(context).colorScheme;
    final unread = ordem.mensagensNaoLidas ?? 0;

    return Card(
      child: InkWell(
        borderRadius: BorderRadius.circular(tokens.radiusMd),
        onTap: () => context.push('/chat/${ordem.id}'),
        child: Padding(
          padding: EdgeInsets.all(tokens.spaceMd),
          child: Row(
            children: [
              CircleAvatar(
                backgroundColor: colors.primaryContainer,
                child: Icon(Icons.person, color: colors.primary),
              ),
              SizedBox(width: tokens.spaceSm),
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(
                      ordem.motoristaNome ?? 'Motorista não informado',
                      style: const TextStyle(fontWeight: FontWeight.w700),
                    ),
                    Text(
                      ordem.placaVeiculo,
                      style: TextStyle(color: colors.onSurfaceVariant),
                    ),
                  ],
                ),
              ),
              if (unread > 0)
                Container(
                  margin: EdgeInsets.only(right: tokens.spaceXs),
                  padding: EdgeInsets.symmetric(horizontal: 8, vertical: 2),
                  decoration: BoxDecoration(
                    color: colors.error,
                    borderRadius: BorderRadius.circular(12),
                  ),
                  child: Text(
                    '$unread',
                    style: TextStyle(
                      color: colors.onError,
                      fontSize: 12,
                      fontWeight: FontWeight.bold,
                    ),
                  ),
                ),
              Icon(Icons.chat_bubble_outline, size: 20),
              SizedBox(width: tokens.spaceXs),
              Text(
                'Abrir chat',
                style: TextStyle(
                  color: colors.primary,
                  fontWeight: FontWeight.w600,
                ),
              ),
              Icon(Icons.chevron_right, color: colors.onSurfaceVariant),
            ],
          ),
        ),
      ),
    );
  }
}

class _ErroDetalhe extends StatelessWidget {
  const _ErroDetalhe({required this.onRetry});
  final VoidCallback onRetry;

  @override
  Widget build(BuildContext context) => Center(
    child: Column(
      mainAxisSize: MainAxisSize.min,
      children: [
        const Icon(Icons.cloud_off_outlined, size: 40),
        const SizedBox(height: 12),
        const Text('Não foi possível carregar a ordem.'),
        const SizedBox(height: 12),
        FilledButton.icon(
          onPressed: onRetry,
          icon: const Icon(Icons.refresh),
          label: const Text('Tentar novamente'),
        ),
      ],
    ),
  );
}

Widget? _buildBottomBar(BuildContext context, WidgetRef ref, OrdemModel? ordem) {
  if (ordem == null) return null;
  final tokens = context.appTokens;

  if (ordem.aguardando) {
    return SafeArea(
      minimum: EdgeInsets.all(tokens.spaceMd),
      child: SizedBox(
        width: double.infinity,
        child: FilledButton.icon(
          style: FilledButton.styleFrom(
            backgroundColor: tokens.success,
            foregroundColor: tokens.onSuccess,
          ),
          icon: const Icon(Icons.play_arrow),
          label: const Text('Iniciar carregamento'),
          onPressed: () => confirmarAcao(
            context: context,
            titulo: 'Iniciar carregamento?',
            confirmar: 'Iniciar',
            placa: ordem.placaVeiculo,
            action: () =>
                ref.read(filaProvider.notifier).iniciarCarregamento(ordem.id),
          ),
        ),
      ),
    );
  }

  if (ordem.emCarregamento) {
    final colors = Theme.of(context).colorScheme;
    return SafeArea(
      minimum: EdgeInsets.symmetric(
        horizontal: tokens.spaceMd,
        vertical: tokens.spaceSm,
      ),
      child: Row(
        children: [
          Expanded(
            child: OutlinedButton.icon(
              style: OutlinedButton.styleFrom(
                foregroundColor: colors.error,
                side: BorderSide(color: colors.error),
              ),
              icon: const Icon(Icons.close),
              label: const Text('Rejeitar'),
              onPressed: () async {
                final motivo = await showRejeitarBottomSheet(
                  context,
                  placa: ordem.placaVeiculo,
                );
                if (motivo == null || !context.mounted) return;
                try {
                  await ref
                      .read(filaProvider.notifier)
                      .rejeitar(ordem.id, motivo);
                } catch (_) {
                  if (context.mounted) {
                    ScaffoldMessenger.of(context).showSnackBar(
                      const SnackBar(
                        content: Text(
                          'Não foi possível rejeitar o caminhão.',
                        ),
                      ),
                    );
                  }
                }
              },
            ),
          ),
          SizedBox(width: tokens.spaceSm),
          Expanded(
            child: FilledButton.icon(
              icon: const Icon(Icons.check),
              label: const Text('Concluir'),
              onPressed: () => confirmarAcao(
                context: context,
                titulo: 'Concluir carregamento?',
                confirmar: 'Concluir',
                placa: ordem.placaVeiculo,
                action: () =>
                    ref.read(filaProvider.notifier).concluirCarregamento(ordem.id),
              ),
            ),
          ),
        ],
      ),
    );
  }

  return null;
}

String _formatarQuantidade(double value) => value == value.truncateToDouble()
    ? value.toStringAsFixed(0)
    : value.toStringAsFixed(2);
String _valorOuTraco(String? value) =>
    value == null || value.trim().isEmpty ? '—' : value;
