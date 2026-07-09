import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';
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
  void initState() {
    super.initState();
    Future.microtask(() => ref.read(mensagensProvider(widget.ordemId).notifier).carregar());
  }

  @override
  void dispose() {
    _controller.dispose();
    _scrollController.dispose();
    super.dispose();
  }

  void _enviar() async {
    final texto = _controller.text.trim();
    if (texto.isEmpty) return;

    _controller.clear();
    await ref.read(mensagensProvider(widget.ordemId).notifier).enviar(texto);
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
    final mensagens = ref.watch(mensagensProvider(widget.ordemId));

    return Scaffold(
      appBar: AppBar(title: const Text('Chat')),
      body: Column(
        children: [
          Expanded(
            child: mensagens.when(
              loading: () => const Center(child: CircularProgressIndicator()),
              error: (e, _) => Center(
                child: Column(
                  mainAxisSize: MainAxisSize.min,
                  children: [
                    const Text('Erro ao carregar mensagens'),
                    const SizedBox(height: 8),
                    FilledButton(
                      onPressed: () => ref.read(mensagensProvider(widget.ordemId).notifier).carregar(),
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
                return ListView.builder(
                  controller: _scrollController,
                  padding: const EdgeInsets.all(12),
                  itemCount: lista.length,
                  itemBuilder: (_, i) => _MensagemBubble(msg: lista[i]),
                );
              },
            ),
          ),
          Container(
            decoration: BoxDecoration(
              color: Theme.of(context).scaffoldBackgroundColor,
              boxShadow: [BoxShadow(color: Colors.black.withValues(alpha: 0.05), blurRadius: 4, offset: const Offset(0, -2))],
            ),
            padding: EdgeInsets.only(
              left: 12,
              right: 12,
              top: 8,
              bottom: MediaQuery.of(context).viewInsets.bottom + 8,
            ),
            child: Row(
              children: [
                Expanded(
                  child: TextField(
                    controller: _controller,
                    decoration: const InputDecoration(
                      hintText: 'Digite sua mensagem...',
                      border: OutlineInputBorder(),
                      contentPadding: EdgeInsets.symmetric(horizontal: 12, vertical: 10),
                    ),
                    maxLength: 1000,
                    maxLines: 3,
                    minLines: 1,
                    textInputAction: TextInputAction.send,
                    onSubmitted: (_) => _enviar(),
                  ),
                ),
                const SizedBox(width: 8),
                IconButton.filled(
                  icon: const Icon(Icons.send),
                  onPressed: _enviar,
                  color: Colors.white,
                ),
              ],
            ),
          ),
        ],
      ),
    );
  }
}

class _MensagemBubble extends StatelessWidget {
  final MensagemModel msg;
  const _MensagemBubble({required this.msg});

  @override
  Widget build(BuildContext context) {
    final isMotorista = msg.isDoMotorista;
    return Align(
      alignment: isMotorista ? Alignment.centerRight : Alignment.centerLeft,
      child: Container(
        margin: const EdgeInsets.symmetric(vertical: 3),
        padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 10),
        constraints: BoxConstraints(maxWidth: MediaQuery.of(context).size.width * 0.75),
        decoration: BoxDecoration(
          color: isMotorista ? Colors.indigo.shade100 : Colors.grey.shade200,
          borderRadius: BorderRadius.only(
            topLeft: const Radius.circular(16),
            topRight: const Radius.circular(16),
            bottomLeft: Radius.circular(isMotorista ? 16 : 4),
            bottomRight: Radius.circular(isMotorista ? 4 : 16),
          ),
        ),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Text(
              isMotorista ? 'Motorista' : 'Operador',
              style: TextStyle(fontSize: 11, fontWeight: FontWeight.bold, color: Colors.grey.shade600),
            ),
            const SizedBox(height: 2),
            Text(msg.mensagem, style: const TextStyle(fontSize: 15)),
          ],
        ),
      ),
    );
  }
}
