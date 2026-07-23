import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../../../core/theme/app_theme_tokens.dart';
import '../../auth/providers/auth_provider.dart';
import '../providers/chat_provider.dart';
import '../models/mensagem_model.dart';

class ChatScreen extends ConsumerStatefulWidget {
  final String ordemId;

  const ChatScreen({super.key, required this.ordemId});

  @override
  ConsumerState<ChatScreen> createState() => _ChatScreenState();
}

class _ChatScreenState extends ConsumerState<ChatScreen> {
  final _controller = TextEditingController();
  final _scrollController = ScrollController();

  @override
  void dispose() {
    _controller.dispose();
    _scrollController.dispose();
    super.dispose();
  }

  Future<void> _enviar() async {
    final texto = _controller.text.trim();
    if (texto.isEmpty) return;

    final enviado = await ref
        .read(mensagensProvider(widget.ordemId).notifier)
        .enviar(texto);
    if (!mounted || !enviado) return;
    _controller.clear();
    WidgetsBinding.instance.addPostFrameCallback((_) => _rolarParaBaixo());
  }

  void _rolarParaBaixo() {
    if (_scrollController.hasClients) {
      _scrollController.animateTo(
        _scrollController.position.maxScrollExtent,
        duration: const Duration(milliseconds: 200),
        curve: Curves.easeOut,
      );
    }
  }

  @override
  Widget build(BuildContext context) {
    final chat = ref.watch(mensagensProvider(widget.ordemId));
    final usuarioId = ref.watch(authProvider).valueOrNull?.id;
    final colors = Theme.of(context).colorScheme;
    final tokens = context.appTokens;

    return Scaffold(
      appBar: AppBar(
        title: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            const Text('Chat operacional'),
            Text(
              'Ordem #${widget.ordemId}',
              style: TextStyle(
                fontSize: 12,
                color: colors.onPrimary.withValues(alpha: .8),
              ),
            ),
          ],
        ),
      ),
      body: SafeArea(
        child: Column(
          children: [
            Expanded(
            child: chat.mensagens.when(
              loading: () => const Center(child: CircularProgressIndicator()),
              error: (e, _) => Center(
                child: Column(
                  mainAxisSize: MainAxisSize.min,
                  children: [
                    const Text('Erro ao carregar mensagens'),
                    const SizedBox(height: 8),
                    FilledButton(
                      onPressed: () => ref
                          .read(mensagensProvider(widget.ordemId).notifier)
                          .carregar(),
                      child: const Text('Tentar novamente'),
                    ),
                  ],
                ),
              ),
              data: (lista) {
                if (lista.isEmpty) {
                  return const Center(child: Text('Nenhuma mensagem ainda'));
                }
                WidgetsBinding.instance.addPostFrameCallback((_) {
                  if (_scrollController.hasClients) {
                    _scrollController.animateTo(
                      _scrollController.position.maxScrollExtent,
                      duration: const Duration(milliseconds: 100),
                      curve: Curves.easeOut,
                    );
                  }
                });
                return ListView.separated(
                  controller: _scrollController,
                  padding: EdgeInsets.all(tokens.spaceMd),
                  itemCount: lista.length,
                  itemBuilder: (_, i) =>
                      _MensagemBubble(msg: lista[i], usuarioId: usuarioId),
                  separatorBuilder: (_, _) => SizedBox(height: tokens.spaceSm),
                );
              },
            ),
          ),
          if (chat.erroEnvio != null)
            Container(
              width: double.infinity,
              color: colors.errorContainer,
              padding: EdgeInsets.symmetric(
                horizontal: tokens.spaceMd,
                vertical: tokens.spaceSm,
              ),
              child: Text(
                chat.erroEnvio!,
                style: TextStyle(color: colors.onErrorContainer),
              ),
            ),
          Container(
            decoration: BoxDecoration(
              color: Theme.of(context).scaffoldBackgroundColor,
              boxShadow: [
                BoxShadow(
                  color: colors.shadow.withValues(alpha: 0.08),
                  blurRadius: 4,
                  offset: const Offset(0, -2),
                ),
              ],
            ),
            padding: EdgeInsets.only(
              left: tokens.spaceMd,
              right: tokens.spaceMd,
              top: tokens.spaceSm,
              bottom: tokens.spaceSm,
            ),
            child: Row(
              children: [
                Expanded(
                  child: TextField(
                    controller: _controller,
                    decoration: const InputDecoration(
                      hintText: 'Digite sua mensagem...',
                      contentPadding: EdgeInsets.symmetric(
                        horizontal: 12,
                        vertical: 10,
                      ),
                    ),
                    maxLength: 1000,
                    maxLines: 3,
                    minLines: 1,
                    textInputAction: TextInputAction.send,
                    enabled: !chat.enviando,
                    onSubmitted: chat.enviando ? null : (_) => _enviar(),
                  ),
                ),
                SizedBox(width: tokens.spaceSm),
                IconButton.filled(
                  tooltip: 'Enviar mensagem',
                  icon: chat.enviando
                      ? SizedBox.square(
                          dimension: 20,
                          child: CircularProgressIndicator(
                            strokeWidth: 2,
                            color: colors.onPrimary,
                          ),
                        )
                      : const Icon(Icons.send_rounded),
                  onPressed: chat.enviando ? null : _enviar,
                ),
              ],
            ),
          ),
        ],
      ),
      ),
    );
  }
}

class _MensagemBubble extends StatelessWidget {
  final MensagemModel msg;
  final int? usuarioId;
  const _MensagemBubble({required this.msg, required this.usuarioId});

  @override
  Widget build(BuildContext context) {
    final isMinha = usuarioId != null && msg.remetenteId == usuarioId;
    final colors = Theme.of(context).colorScheme;
    final tokens = context.appTokens;
    return Align(
      alignment: isMinha ? Alignment.centerRight : Alignment.centerLeft,
      child: Container(
        padding: EdgeInsets.symmetric(
          horizontal: tokens.spaceMd,
          vertical: tokens.spaceSm + 2,
        ),
        constraints: BoxConstraints(
          maxWidth: MediaQuery.of(context).size.width * 0.75,
        ),
        decoration: BoxDecoration(
          color: isMinha
              ? colors.primaryContainer
              : colors.surfaceContainerHighest,
          borderRadius: BorderRadius.only(
            topLeft: const Radius.circular(16),
            topRight: const Radius.circular(16),
            bottomLeft: Radius.circular(isMinha ? 16 : 4),
            bottomRight: Radius.circular(isMinha ? 4 : 16),
          ),
        ),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Text(
              msg.isDoMotorista ? 'Motorista' : 'Operador',
              style: TextStyle(
                fontSize: 11,
                fontWeight: FontWeight.bold,
                color: colors.onSurfaceVariant,
              ),
            ),
            const SizedBox(height: 2),
            Text(msg.mensagem, style: const TextStyle(fontSize: 15)),
            if (msg.horarioLabel != null) ...[
              SizedBox(height: tokens.spaceXs),
              Align(
                alignment: Alignment.centerRight,
                child: Text(
                  msg.horarioLabel!,
                  style: Theme.of(context).textTheme.labelSmall?.copyWith(
                    color: colors.onSurfaceVariant,
                  ),
                ),
              ),
            ],
          ],
        ),
      ),
    );
  }
}
