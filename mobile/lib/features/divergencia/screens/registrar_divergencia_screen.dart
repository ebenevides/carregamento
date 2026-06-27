import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../../../core/api/api_client.dart';
import '../../auth/providers/auth_provider.dart';
import '../../fila/providers/fila_provider.dart';

const _tipos = [
  ('PRODUTO_DIVERGENTE', 'Produto divergente'),
  ('QUANTIDADE_DIVERGENTE', 'Quantidade divergente'),
  ('VEICULO_DIVERGENTE', 'Veículo divergente'),
  ('MOTORISTA_DIVERGENTE', 'Motorista divergente'),
  ('TICKET_INVALIDO', 'Ticket inválido'),
  ('TARA_INVALIDA', 'Tara inválida'),
  ('PONTO_INDISPONIVEL', 'Ponto indisponível'),
  ('OUTRO', 'Outro'),
];

class RegistrarDivergenciaScreen extends ConsumerStatefulWidget {
  final String ordemId;
  const RegistrarDivergenciaScreen({super.key, required this.ordemId});

  @override
  ConsumerState<RegistrarDivergenciaScreen> createState() => _State();
}

class _State extends ConsumerState<RegistrarDivergenciaScreen> {
  final _formKey = GlobalKey<FormState>();
  final _descCtrl = TextEditingController();
  String? _tipo;
  bool _loading = false;

  @override
  void dispose() {
    _descCtrl.dispose();
    super.dispose();
  }

  Future<void> _registrar() async {
    if (!_formKey.currentState!.validate() || _tipo == null) return;
    setState(() => _loading = true);

    try {
      final api = ref.read(apiClientProvider);
      final user = ref.read(authProvider).valueOrNull;

      await api.post('/ordens-carregamento/${widget.ordemId}/divergencias', data: {
        'tipo': _tipo,
        'descricao': _descCtrl.text.trim(),
        'usuario_id': user?.id,
        'origem': 'APP_OPERADOR',
      });

      await ref.read(filaProvider.notifier).carregar();

      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          const SnackBar(content: Text('Divergência registrada.'), backgroundColor: Colors.orange),
        );
        Navigator.pop(context);
      }
    } catch (e) {
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(content: Text('Erro: $e'), backgroundColor: Colors.red),
        );
      }
    } finally {
      if (mounted) setState(() => _loading = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: const Text('Registrar Divergência'),
        backgroundColor: Colors.orange,
        foregroundColor: Colors.white,
      ),
      body: Form(
        key: _formKey,
        child: ListView(
          padding: const EdgeInsets.all(20),
          children: [
            const Text(
              'Tipo de divergência',
              style: TextStyle(fontWeight: FontWeight.bold),
            ),
            const SizedBox(height: 8),
            DropdownButtonFormField<String>(
              value: _tipo,
              hint: const Text('Selecione o tipo'),
              decoration: const InputDecoration(border: OutlineInputBorder()),
              items: _tipos.map((t) => DropdownMenuItem(value: t.$1, child: Text(t.$2))).toList(),
              onChanged: (v) => setState(() => _tipo = v),
              validator: (v) => v == null ? 'Selecione o tipo' : null,
            ),
            const SizedBox(height: 20),
            const Text('Descrição', style: TextStyle(fontWeight: FontWeight.bold)),
            const SizedBox(height: 8),
            TextFormField(
              controller: _descCtrl,
              maxLines: 4,
              decoration: const InputDecoration(
                border: OutlineInputBorder(),
                hintText: 'Descreva o problema encontrado...',
              ),
              validator: (v) => (v == null || v.trim().isEmpty) ? 'Informe a descrição' : null,
            ),
            const SizedBox(height: 32),
            SizedBox(
              height: 48,
              child: FilledButton.icon(
                icon: const Icon(Icons.warning_amber),
                label: _loading
                    ? const CircularProgressIndicator(color: Colors.white)
                    : const Text('Registrar Divergência', style: TextStyle(fontSize: 16)),
                style: FilledButton.styleFrom(backgroundColor: Colors.orange),
                onPressed: _loading ? null : _registrar,
              ),
            ),
          ],
        ),
      ),
    );
  }
}
