import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';
import '../../fila/providers/fila_provider.dart';
import '../../divergencia/screens/registrar_divergencia_screen.dart';

class OrdemDetalheScreen extends ConsumerWidget {
  final String ordemId;
  const OrdemDetalheScreen({super.key, required this.ordemId});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final ordemAsync = ref.watch(ordemDetalheProvider(ordemId));

    return Scaffold(
      appBar: AppBar(
        title: const Text('Detalhe da Ordem'),
        actions: [
          IconButton(
            icon: const Icon(Icons.warning_amber, color: Colors.orange),
            tooltip: 'Registrar divergência',
            onPressed: () => Navigator.push(
              context,
              MaterialPageRoute(
                builder: (_) => RegistrarDivergenciaScreen(ordemId: ordemId),
              ),
            ),
          ),
        ],
      ),
      body: ordemAsync.when(
        loading: () => const Center(child: CircularProgressIndicator()),
        error: (e, _) => Center(child: Text('Erro: $e')),
        data: (ordem) => SingleChildScrollView(
          padding: const EdgeInsets.all(16),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              if (ordem.temDivergencia)
                Container(
                  width: double.infinity,
                  padding: const EdgeInsets.all(12),
                  margin: const EdgeInsets.only(bottom: 16),
                  decoration: BoxDecoration(
                    color: Colors.red.shade50,
                    border: Border.all(color: Colors.red),
                    borderRadius: BorderRadius.circular(8),
                  ),
                  child: Row(
                    children: [
                      const Icon(Icons.warning_amber, color: Colors.red),
                      const SizedBox(width: 8),
                      Text(
                        '${ordem.divergenciasAbertas} divergência(s) aberta(s)',
                        style: const TextStyle(color: Colors.red, fontWeight: FontWeight.bold),
                      ),
                    ],
                  ),
                ),
              _SecaoCard(
                titulo: 'Identificação',
                itens: [
                  _Item('Ticket Guardian', ordem.ticketGuardian ?? '—', destaque: true),
                  _Item('Pedido', ordem.pedidoNumero ?? '—'),
                  _Item('Status', ordem.statusLabel, destaque: true),
                ],
              ),
              const SizedBox(height: 12),
              _SecaoCard(
                titulo: 'Veículo e Motorista',
                itens: [
                  _Item('Placa', ordem.placaVeiculo, destaque: true),
                  if (ordem.placaCarreta != null) _Item('Carreta', ordem.placaCarreta!),
                  _Item('Motorista', ordem.motoristaNome ?? '—'),
                ],
              ),
              const SizedBox(height: 12),
              _SecaoCard(
                titulo: 'Produto e Carga',
                itens: [
                  _Item('Produto', '${ordem.produtoCodigo} — ${ordem.produtoDescricao ?? ""}', destaque: true),
                  _Item('Qtd. prevista', '${ordem.quantidadePrevista} ${ordem.unidade}', destaque: true),
                  _Item('Tara', ordem.tara != null ? '${ordem.tara} kg' : '—'),
                  if (ordem.pilhaProduto != null)
                    _Item('Pilha', ordem.pilhaProduto!['codigo'] ?? ''),
                  if (ordem.pontoCarregamento != null)
                    _Item('Ponto', '${ordem.pontoCarregamento!['codigo']} — ${ordem.pontoCarregamento!['descricao']}'),
                ],
              ),
              if (ordem.clienteNome != null) ...[
                const SizedBox(height: 12),
                _SecaoCard(
                  titulo: 'Cliente',
                  itens: [_Item('Nome', ordem.clienteNome!)],
                ),
              ],
              const SizedBox(height: 24),
              SizedBox(
                width: double.infinity,
                child: OutlinedButton.icon(
                  icon: const Icon(Icons.arrow_back),
                  label: const Text('Voltar para a fila'),
                  onPressed: () => context.pop(),
                ),
              ),
            ],
          ),
        ),
      ),
    );
  }
}

class _Item {
  final String label;
  final String value;
  final bool destaque;
  _Item(this.label, this.value, {this.destaque = false});
}

class _SecaoCard extends StatelessWidget {
  final String titulo;
  final List<_Item> itens;
  const _SecaoCard({required this.titulo, required this.itens});

  @override
  Widget build(BuildContext context) {
    return Card(
      child: Padding(
        padding: const EdgeInsets.all(16),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Text(titulo, style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 15, color: Colors.grey)),
            const Divider(),
            ...itens.map((item) => Padding(
                  padding: const EdgeInsets.symmetric(vertical: 4),
                  child: Row(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      SizedBox(
                        width: 120,
                        child: Text('${item.label}:', style: const TextStyle(color: Colors.grey, fontSize: 13)),
                      ),
                      Expanded(
                        child: Text(
                          item.value,
                          style: TextStyle(
                            fontWeight: item.destaque ? FontWeight.bold : FontWeight.normal,
                            fontSize: item.destaque ? 15 : 13,
                          ),
                        ),
                      ),
                    ],
                  ),
                )),
          ],
        ),
      ),
    );
  }
}
