import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../../core/theme/app_theme_tokens.dart';
import '../../fila/models/ordem_model.dart';
import '../../fila/providers/fila_provider.dart';
import '../providers/divergencia_provider.dart';

class RegistrarDivergenciaScreen extends ConsumerStatefulWidget {
  const RegistrarDivergenciaScreen({super.key, required this.ordemId});

  final String ordemId;

  @override
  ConsumerState<RegistrarDivergenciaScreen> createState() =>
      _RegistrarDivergenciaState();
}

class _RegistrarDivergenciaState
    extends ConsumerState<RegistrarDivergenciaScreen> {
  final _formKey = GlobalKey<FormState>();
  final _descricaoController = TextEditingController();
  String? _tipo;

  @override
  void dispose() {
    _descricaoController.dispose();
    super.dispose();
  }

  Future<void> _registrar() async {
    if (!_formKey.currentState!.validate()) return;

    final sucesso = await ref
        .read(divergenciaControllerProvider.notifier)
        .registrar(
          ordemId: widget.ordemId,
          tipo: _tipo!,
          descricao: _descricaoController.text,
        );
    if (!mounted) return;

    if (sucesso) {
      ScaffoldMessenger.of(
        context,
      ).showSnackBar(const SnackBar(content: Text('Divergência registrada.')));
      Navigator.of(context).pop();
    }
  }

  @override
  Widget build(BuildContext context) {
    final ordemAsync = ref.watch(ordemDetalheProvider(widget.ordemId));
    final envio = ref.watch(divergenciaControllerProvider);
    final tokens = context.appTokens;
    final colors = Theme.of(context).colorScheme;

    return Scaffold(
      appBar: AppBar(title: const Text('Registrar divergência')),
      body: SafeArea(
        child: Form(
          key: _formKey,
          child: ListView(
            padding: EdgeInsets.all(tokens.spaceMd),
            children: [
              ordemAsync.when(
                loading: () => const _ContextoCarregando(),
                error: (_, _) => const _ContextoIndisponivel(),
                data: (ordem) => _ContextoOrdem(ordem: ordem),
              ),
              SizedBox(height: tokens.spaceLg),
              Text(
                'O que aconteceu?',
                style: Theme.of(context).textTheme.titleLarge,
              ),
              SizedBox(height: tokens.spaceXs),
              Text(
                'Selecione uma categoria para facilitar o tratamento.',
                style: TextStyle(color: colors.onSurfaceVariant),
              ),
              SizedBox(height: tokens.spaceMd),
              ...tiposDivergencia.map(
                (tipo) => Padding(
                  padding: EdgeInsets.only(bottom: tokens.spaceSm),
                  child: _TipoOption(
                    title: tipo.$2,
                    subtitle: tipo.$3,
                    selected: _tipo == tipo.$1,
                    onTap: envio.isLoading
                        ? null
                        : () => setState(() => _tipo = tipo.$1),
                  ),
                ),
              ),
              if (_tipo == null)
                FormField<String>(
                  validator: (_) =>
                      _tipo == null ? 'Selecione o tipo de divergência' : null,
                  builder: (field) => field.hasError
                      ? Padding(
                          padding: EdgeInsets.only(bottom: tokens.spaceSm),
                          child: Text(
                            field.errorText!,
                            style: TextStyle(color: colors.error),
                          ),
                        )
                      : const SizedBox.shrink(),
                ),
              SizedBox(height: tokens.spaceSm),
              Text('Descrição', style: Theme.of(context).textTheme.titleMedium),
              SizedBox(height: tokens.spaceSm),
              TextFormField(
                controller: _descricaoController,
                minLines: 4,
                maxLines: 6,
                maxLength: 1000,
                enabled: !envio.isLoading,
                decoration: const InputDecoration(
                  hintText: 'Descreva o problema encontrado…',
                  alignLabelWithHint: true,
                ),
                validator: (value) => value == null || value.trim().isEmpty
                    ? 'Informe a descrição'
                    : null,
              ),
              if (envio.hasError) ...[
                SizedBox(height: tokens.spaceSm),
                Container(
                  padding: EdgeInsets.all(tokens.spaceMd),
                  decoration: BoxDecoration(
                    color: colors.errorContainer,
                    borderRadius: BorderRadius.circular(tokens.radiusMd),
                  ),
                  child: Row(
                    children: [
                      Icon(Icons.error_outline, color: colors.error),
                      SizedBox(width: tokens.spaceSm),
                      const Expanded(
                        child: Text(
                          'Não foi possível registrar. Verifique os dados e tente novamente.',
                        ),
                      ),
                    ],
                  ),
                ),
              ],
              SizedBox(height: tokens.spaceLg),
              FilledButton.icon(
                style: FilledButton.styleFrom(
                  backgroundColor: tokens.warning,
                  foregroundColor: tokens.onWarning,
                ),
                onPressed: envio.isLoading ? null : _registrar,
                icon: envio.isLoading
                    ? SizedBox.square(
                        dimension: 20,
                        child: CircularProgressIndicator(
                          strokeWidth: 2,
                          color: tokens.onWarning,
                        ),
                      )
                    : const Icon(Icons.report_problem_outlined),
                label: Text(
                  envio.isLoading ? 'Registrando…' : 'Registrar divergência',
                ),
              ),
              SizedBox(height: tokens.spaceSm),
              OutlinedButton(
                onPressed: envio.isLoading
                    ? null
                    : () => Navigator.of(context).pop(),
                child: const Text('Cancelar'),
              ),
            ],
          ),
        ),
      ),
    );
  }
}

class _ContextoOrdem extends StatelessWidget {
  const _ContextoOrdem({required this.ordem});
  final OrdemModel ordem;

  @override
  Widget build(BuildContext context) {
    final tokens = context.appTokens;
    final colors = Theme.of(context).colorScheme;
    return Container(
      padding: EdgeInsets.all(tokens.spaceMd),
      decoration: BoxDecoration(
        color: colors.primaryContainer,
        borderRadius: BorderRadius.circular(tokens.radiusLg),
      ),
      child: Row(
        children: [
          CircleAvatar(
            backgroundColor: colors.primary,
            foregroundColor: colors.onPrimary,
            child: const Icon(Icons.local_shipping_outlined),
          ),
          SizedBox(width: tokens.spaceMd),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(
                  ordem.placaVeiculo,
                  style: Theme.of(
                    context,
                  ).textTheme.titleLarge?.copyWith(fontWeight: FontWeight.w800),
                ),
                Text(
                  '${ordem.produtoDescricao ?? ordem.produtoCodigo} • ${ordem.statusLabel}',
                  maxLines: 2,
                  overflow: TextOverflow.ellipsis,
                  style: TextStyle(
                    color: colors.onPrimaryContainer.withValues(alpha: .75),
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

class _ContextoCarregando extends StatelessWidget {
  const _ContextoCarregando();
  @override
  Widget build(BuildContext context) => const Card(
    child: Padding(
      padding: EdgeInsets.all(24),
      child: LinearProgressIndicator(),
    ),
  );
}

class _ContextoIndisponivel extends StatelessWidget {
  const _ContextoIndisponivel();
  @override
  Widget build(BuildContext context) => const Card(
    child: Padding(
      padding: EdgeInsets.all(16),
      child: Row(
        children: [
          Icon(Icons.info_outline),
          SizedBox(width: 12),
          Expanded(child: Text('Contexto da ordem indisponível.')),
        ],
      ),
    ),
  );
}

class _TipoOption extends StatelessWidget {
  const _TipoOption({
    required this.title,
    required this.subtitle,
    required this.selected,
    required this.onTap,
  });

  final String title;
  final String subtitle;
  final bool selected;
  final VoidCallback? onTap;

  @override
  Widget build(BuildContext context) {
    final tokens = context.appTokens;
    final colors = Theme.of(context).colorScheme;
    return Material(
      color: selected ? colors.primaryContainer : colors.surface,
      shape: RoundedRectangleBorder(
        borderRadius: BorderRadius.circular(tokens.radiusMd),
        side: BorderSide(
          color: selected ? colors.primary : colors.outlineVariant,
          width: selected ? 2 : 1,
        ),
      ),
      child: InkWell(
        onTap: onTap,
        borderRadius: BorderRadius.circular(tokens.radiusMd),
        child: Padding(
          padding: EdgeInsets.all(tokens.spaceMd),
          child: Row(
            children: [
              Icon(
                selected ? Icons.radio_button_checked : Icons.radio_button_off,
                color: selected ? colors.primary : colors.onSurfaceVariant,
              ),
              SizedBox(width: tokens.spaceMd),
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(
                      title,
                      style: const TextStyle(fontWeight: FontWeight.w700),
                    ),
                    SizedBox(height: tokens.spaceXs),
                    Text(
                      subtitle,
                      style: Theme.of(context).textTheme.bodySmall?.copyWith(
                        color: colors.onSurfaceVariant,
                      ),
                    ),
                  ],
                ),
              ),
            ],
          ),
        ),
      ),
    );
  }
}
