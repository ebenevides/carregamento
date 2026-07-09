import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';
import '../providers/motorista_provider.dart';
import '../../auth/providers/auth_provider.dart';

class MinhaCargaScreen extends ConsumerWidget {
  const MinhaCargaScreen({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final state = ref.watch(motoristaProvider);

    return Scaffold(
      appBar: AppBar(
        title: const Text('Minha Carga'),
        actions: [
          IconButton(
            icon: const Icon(Icons.refresh),
            onPressed: () => ref.read(motoristaProvider.notifier).carregar(),
          ),
          IconButton(
            icon: const Icon(Icons.logout),
            onPressed: () => ref.read(authProvider.notifier).logout(),
          ),
        ],
      ),
      body: state.when(
        loading: () => const Center(child: CircularProgressIndicator()),
        error: (e, _) => Center(
          child: Column(
            mainAxisSize: MainAxisSize.min,
            children: [
              const Icon(Icons.error_outline, size: 48, color: Colors.red),
              const SizedBox(height: 8),
              const Text('Erro ao carregar'),
              const SizedBox(height: 8),
              FilledButton(
                onPressed: () => ref.read(motoristaProvider.notifier).carregar(),
                child: const Text('Tentar novamente'),
              ),
            ],
          ),
        ),
        data: (ordem) {
          if (ordem == null) {
            return _buildSemOrdem(context);
          }
          return _ComOrdem(ordem: ordem);
        },
      ),
    );
  }

  Widget _buildSemOrdem(BuildContext context) {
    return Center(
      child: Column(
        mainAxisSize: MainAxisSize.min,
        children: [
          Icon(Icons.inbox_outlined, size: 80, color: Colors.grey.shade400),
          const SizedBox(height: 16),
          Text(
            'Nenhuma carga no momento',
            style: Theme.of(context).textTheme.titleLarge,
          ),
          const SizedBox(height: 8),
          const Text('Assim que uma ordem for atribuída a você,\nela aparecerá aqui.',
              textAlign: TextAlign.center, style: TextStyle(color: Colors.grey)),
        ],
      ),
    );
  }
}

class _ComOrdem extends ConsumerWidget {
  final ordem;

  const _ComOrdem({required this.ordem});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final podePosicionar = ordem.podeSePosicionar;
    final aguardando = ordem.aguardando;

    return RefreshIndicator(
      onRefresh: () => ref.read(motoristaProvider.notifier).carregar(),
      child: ListView(
        padding: const EdgeInsets.all(16),
        children: [
          // Status banner
          if (aguardando)
            Card(
              color: Colors.orange.shade50,
              child: const Padding(
                padding: EdgeInsets.all(16),
                child: Row(
                  children: [
                    Icon(Icons.hourglass_top, color: Colors.orange, size: 32),
                    SizedBox(width: 12),
                    Expanded(
                      child: Text(
                        'Aguardando liberação para posicionar',
                        style: TextStyle(fontSize: 16, fontWeight: FontWeight.bold, color: Colors.orange),
                      ),
                    ),
                  ],
                ),
              ),
            ),
          if (podePosicionar)
            Card(
              color: Colors.green.shade50,
              child: const Padding(
                padding: EdgeInsets.all(16),
                child: Row(
                  children: [
                    Icon(Icons.check_circle, color: Colors.green, size: 32),
                    SizedBox(width: 12),
                    Expanded(
                      child: Text(
                        'Pode se posicionar!',
                        style: TextStyle(fontSize: 16, fontWeight: FontWeight.bold, color: Colors.green),
                      ),
                    ),
                  ],
                ),
              ),
            ),

          const SizedBox(height: 16),

          // Produto
          _InfoCard(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                const Text('Produto', style: TextStyle(color: Colors.grey, fontSize: 13)),
                const SizedBox(height: 4),
                Text(
                  '${ordem.produtoCodigo}${ordem.produtoDescricao != null ? ' — ${ordem.produtoDescricao}' : ''}',
                  style: const TextStyle(fontSize: 20, fontWeight: FontWeight.bold),
                ),
              ],
            ),
          ),

          const SizedBox(height: 8),

          // Pilha e Ponto
          Row(
            children: [
              Expanded(
                child: _InfoCard(
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      const Text('Bica / Pilha', style: TextStyle(color: Colors.grey, fontSize: 13)),
                      const SizedBox(height: 4),
                      Text(
                        ordem.pilhaProduto?['codigo'] ?? '—',
                        style: const TextStyle(fontSize: 16, fontWeight: FontWeight.bold),
                      ),
                    ],
                  ),
                ),
              ),
              const SizedBox(width: 8),
              Expanded(
                child: _InfoCard(
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      const Text('Unidade', style: TextStyle(color: Colors.grey, fontSize: 13)),
                      const SizedBox(height: 4),
                      Text(
                        ordem.pontoCarregamento?['codigo'] ?? '—',
                        style: const TextStyle(fontSize: 16, fontWeight: FontWeight.bold),
                      ),
                    ],
                  ),
                ),
              ),
            ],
          ),

          const SizedBox(height: 8),

          // Placa
          _InfoCard(
            child: Row(
              children: [
                const Icon(Icons.local_shipping, color: Colors.grey),
                const SizedBox(width: 8),
                Expanded(
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      const Text('Placa', style: TextStyle(color: Colors.grey, fontSize: 13)),
                      Text(ordem.placaVeiculo, style: const TextStyle(fontSize: 18, fontWeight: FontWeight.bold)),
                    ],
                  ),
                ),
              ],
            ),
          ),

          if (ordem.placaCarreta != null && ordem.placaCarreta!.isNotEmpty) ...[
            const SizedBox(height: 8),
            _InfoCard(
              child: Row(
                children: [
                  const Icon(Icons.link, color: Colors.grey),
                  const SizedBox(width: 8),
                  Expanded(
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        const Text('Carreta', style: TextStyle(color: Colors.grey, fontSize: 13)),
                        Text(ordem.placaCarreta!, style: const TextStyle(fontSize: 18, fontWeight: FontWeight.bold)),
                      ],
                    ),
                  ),
                ],
              ),
            ),
          ],

          const SizedBox(height: 8),

          // Quantidade
          _InfoCard(
            child: Row(
              children: [
                const Icon(Icons.scale, color: Colors.grey),
                const SizedBox(width: 8),
                Expanded(
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      const Text('Quantidade prevista', style: TextStyle(color: Colors.grey, fontSize: 13)),
                      Text('${ordem.quantidadePrevista} TN', style: const TextStyle(fontSize: 18, fontWeight: FontWeight.bold)),
                    ],
                  ),
                ),
              ],
            ),
          ),

          const SizedBox(height: 8),

          // Status
          Chip(
            label: Text(ordem.statusLabel, style: const TextStyle(fontWeight: FontWeight.bold)),
            backgroundColor: podePosicionar ? Colors.green.shade100 : Colors.orange.shade100,
          ),

          const SizedBox(height: 24),

          // Chat button
          if (ordem.estaAtivo)
            SizedBox(
              width: double.infinity,
              child: OutlinedButton.icon(
                icon: const Icon(Icons.chat),
                label: const Text('Chat com o operador'),
                style: OutlinedButton.styleFrom(
                  padding: const EdgeInsets.symmetric(vertical: 16),
                  side: const BorderSide(color: Colors.indigo),
                ),
                onPressed: () => context.push('/chat/${ordem.id}'),
              ),
            ),
        ],
      ),
    );
  }
}

class _InfoCard extends StatelessWidget {
  final Widget child;
  const _InfoCard({required this.child});

  @override
  Widget build(BuildContext context) {
    return Card(
      margin: EdgeInsets.zero,
      child: Padding(
        padding: const EdgeInsets.all(16),
        child: child,
      ),
    );
  }
}
