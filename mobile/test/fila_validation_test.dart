import 'package:carregamento_operador/features/fila/providers/fila_provider.dart';
import 'package:flutter_test/flutter_test.dart';

void main() {
  group('validarMotivoRejeicao', () {
    test('rejeita motivo curto após trim', () {
      expect(validarMotivoRejeicao('  não  '), isNotNull);
    });

    test('aceita cinco caracteres', () {
      expect(validarMotivoRejeicao('falha'), isNull);
    });
  });
}
