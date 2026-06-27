import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';
import '../providers/fila_provider.dart';
import '../models/ordem_model.dart';
import '../../auth/providers/auth_provider.dart';

class FilaScreen extends ConsumerWidget {
  const FilaScreen({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final fila = ref.watch(filaProvider);
    final user = ref.watch(authProvider).valueOrNull;

    return Scaffold(
      appBar: AppBar(
        title: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            const Text('Minha Fila', style: TextStyle(fontSize: 18)),
            if (user != null)
              Text(user.name, style: const TextStyle(fontSize: 12)),
          ],
        ),
        actions: [
          IconButton(
            icon: const Icon(Icons.refresh),
            onPressed: () => ref.read(filaProvider.notifier).carregar(),
          ),
          IconButton(
            icon: const Icon(Icons.logout),
            onPressed: () => ref.read(authProvider.notifier).logout(),
          ),
        ],
      ),
      body: fila.when(
        loading: () => const Center(child: CircularProgressIndicator()),
        error: (e, _) => Center(
          child: Column(
            mainAxisSize: MainAxisSize.min,
            children: [
              const Icon(Icons.error_outline, size: 48, color: Colors.red),
              const SizedBox(height: 8),
              Text('Erro ao carregar fila', style: Theme.of(context).textTheme.bodyLarge),
              const SizedBox(height: 8),
              FilledButton(
                onPressed: () => ref.read(filaProvider.notifier).carregar(),
                child: const Text('Tentar novamente'),
              ),
            ],
          ),
        ),
        data: (ordens) {
          if (ordens.isEmpty) {
            return const Center(
              child: Column(
                mainAxisSize: MainAxisSize.min,
                children: [
                  Icon(Icons.check_circle_outline, size: 64, color: Colors.green),
                  SizedBox(height: 16),
                  Text('Nenhuma ordem na fila', style: TextStyle(fontSize: 18)),
                ],
              ),
            );
          }
          return RefreshIndicator(
            onRefresh: () => ref.read(filaProvider.notifier).carregar(),
            child: ListView.builder(
              padding: const EdgeInsets.all(12),
              itemCount: ordens.length,
              itemBuilder: (ctx, i) => _OrdemCard(ordem: ordens[i], posicao: i + 1),
            ),
          );
        },
      ),
    );
  }
}

class _OrdemCard extends ConsumerWidget {
  final OrdemModel ordem;
  final int posicao;

  const _OrdemCard({required this.ordem, required this.posicao});

  Color get _statusColor {
    return switch (ordem.status) {
      'EM_CARREGAMENTO' => Colors.green,
      'AGUARDANDO_CARREGAMENTO' => Colors.orange,
      'DIVERGENCIA' => Colors.red,
      _ => Colors.grey,
    };
  }

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    return Card(
      margin: const EdgeInsets.only(bottom: 12),
      child: InkWell(
        borderRadius: BorderRadius.circular(12),
        onTap: () => context.push('/ordem/${ordem.id}'),
        child: Padding(
          padding: const EdgeInsets.all(16),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Row(
                children: [
                  CircleAvatar(
                    backgroundColor: _statusColor,
                    radius: 16,
                    child: Text('$posicao', style: const TextStyle(color: Colors.white, fontWeight: FontWeight.bold)),
                  ),
                  const SizedBox(width: 12),
                  Expanded(
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Text(
                          ordem.placaVeiculo,
                          style: const TextStyle(fontSize: 20, fontWeight: FontWeight.bold),
                        ),
                        Text(ordem.motoristaNome ?? '—', style: const TextStyle(color: Colors.grey)),
                      ],
                    ),
                  ),
                  if (ordem.temDivergencia)
                    const Icon(Icons.warning_amber, color: Colors.red, size: 28),
                ],
              ),
              const Divider(height: 20),
              _InfoRow(Icons.inventory_2, 'Produto', '${ordem.produtoCodigo} — ${ordem.produtoDescricao ?? ""}'),
              _InfoRow(Icons.scale, 'Qtd. prevista', '${ordem.quantidadePrevista} ${ordem.unidade}'),
              if (ordem.pilhaProduto != null)
                _InfoRow(Icons.layers, 'Pilha', ordem.pilhaProduto!['codigo'] ?? ''),
              if (ordem.ticketGuardian != null)
                _InfoRow(Icons.receipt_long, 'Ticket', ordem.ticketGuardian!),
              const SizedBox(height: 8),
              Container(
                padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 4),
                decoration: BoxDecoration(
                  color: _statusColor.withValues(alpha: 0.1),
                  borderRadius: BorderRadius.circular(20),
                  border: Border.all(color: _statusColor),
                ),
                child: Text(
                  ordem.statusLabel,
                  style: TextStyle(color: _statusColor, fontWeight: FontWeight.bold),
                ),
              ),
              if (ordem.aguardando) ...[
                const SizedBox(height: 12),
                SizedBox(
                  width: double.infinity,
                  child: FilledButton.icon(
                    icon: const Icon(Icons.play_arrow),
                    label: const Text('Iniciar Carregamento'),
                    style: FilledButton.styleFrom(backgroundColor: Colors.green),
                    onPressed: () async {
                      final confirm = await showDialog<bool>(
                        context: context,
                        builder: (ctx) => AlertDialog(
                          title: const Text('Iniciar carregamento?'),
                          content: Text('Placa: ${ordem.placaVeiculo}'),
                          actions: [
                            TextButton(onPressed: () => Navigator.pop(ctx, false), child: const Text('Cancelar')),
                            FilledButton(onPressed: () => Navigator.pop(ctx, true), child: const Text('Iniciar')),
                          ],
                        ),
                      );
                      if (confirm == true && context.mounted) {
                        await ref.read(filaProvider.notifier).iniciarCarregamento(ordem.id);
                      }
                    },
                  ),
                ),
              ],
              if (ordem.emCarregamento) ...[
                const SizedBox(height: 12),
                SizedBox(
                  width: double.infinity,
                  child: FilledButton.icon(
                    icon: const Icon(Icons.check_circle),
                    label: const Text('Concluir Carregamento'),
                    style: FilledButton.styleFrom(backgroundColor: Colors.blue),
                    onPressed: () async {
                      final confirm = await showDialog<bool>(
                        context: context,
                        builder: (ctx) => AlertDialog(
                          title: const Text('Concluir carregamento?'),
                          content: Text('Placa: ${ordem.placaVeiculo}'),
                          actions: [
                            TextButton(onPressed: () => Navigator.pop(ctx, false), child: const Text('Cancelar')),
                            FilledButton(onPressed: () => Navigator.pop(ctx, true), child: const Text('Concluir')),
                          ],
                        ),
                      );
                      if (confirm == true && context.mounted) {
                        await ref.read(filaProvider.notifier).concluirCarregamento(ordem.id);
                      }
                    },
                  ),
                ),
              ],
            ],
          ),
        ),
      ),
    );
  }
}

class _InfoRow extends StatelessWidget {
  final IconData icon;
  final String label;
  final String value;

  const _InfoRow(this.icon, this.label, this.value);

  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: const EdgeInsets.symmetric(vertical: 2),
      child: Row(
        children: [
          Icon(icon, size: 16, color: Colors.grey),
          const SizedBox(width: 6),
          Text('$label: ', style: const TextStyle(color: Colors.grey, fontSize: 13)),
          Expanded(child: Text(value, style: const TextStyle(fontWeight: FontWeight.w500, fontSize: 13))),
        ],
      ),
    );
  }
}
