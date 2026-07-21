import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';

import '../../../core/theme/app_theme_tokens.dart';
import '../../divergencia/screens/registrar_divergencia_screen.dart';
import '../../fila/models/ordem_model.dart';
import '../../fila/providers/fila_provider.dart';

class OrdemDetalheScreen extends ConsumerWidget {
  const OrdemDetalheScreen({super.key, required this.ordemId});

  final String ordemId;

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final ordemAsync = ref.watch(ordemDetalheProvider(ordemId));

    return Scaffold(
      appBar: AppBar(title: const Text('Detalhe da ordem')),
      body: ordemAsync.when(
        loading: () => const Center(child: CircularProgressIndicator()),
        error: (_, _) => _ErroDetalhe(
          onRetry: () => ref.invalidate(ordemDetalheProvider(ordemId)),
        ),
        data: (ordem) => _ConteudoDetalhe(ordem: ordem),
      ),
    );
  }
}

class _ConteudoDetalhe extends StatelessWidget {
  const _ConteudoDetalhe({required this.ordem});

  final OrdemModel ordem;

  @override
  Widget build(BuildContext context) {
    final tokens = context.appTokens;
    final colors = Theme.of(context).colorScheme;
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
              _InfoItem('Motorista', ordem.motoristaNome ?? '—'),
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
          SizedBox(height: tokens.spaceLg),
          SizedBox(
            width: double.infinity,
            child: OutlinedButton.icon(
              icon: const Icon(Icons.chat_bubble_outline),
              label: const Text('Abrir chat da ordem'),
              onPressed: () => context.push('/chat/${ordem.id}'),
            ),
          ),
          SizedBox(height: tokens.spaceSm),
          SizedBox(
            width: double.infinity,
            child: FilledButton.icon(
              style: FilledButton.styleFrom(
                backgroundColor: tokens.warning,
                foregroundColor: tokens.onWarning,
              ),
              icon: const Icon(Icons.report_problem_outlined),
              label: const Text('Registrar divergência'),
              onPressed: () => Navigator.of(context).push(
                MaterialPageRoute<void>(
                  builder: (_) => RegistrarDivergenciaScreen(ordemId: ordem.id),
                ),
              ),
            ),
          ),
          SizedBox(height: tokens.spaceSm),
          TextButton.icon(
            onPressed: () => context.pop(),
            icon: Icon(Icons.arrow_back, color: colors.primary),
            label: const Text('Voltar para a fila'),
          ),
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

String _formatarQuantidade(double value) => value == value.truncateToDouble()
    ? value.toStringAsFixed(0)
    : value.toStringAsFixed(2);
String _valorOuTraco(String? value) =>
    value == null || value.trim().isEmpty ? '—' : value;
