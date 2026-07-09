import 'package:flutter_test/flutter_test.dart';
import 'package:carregamento_operador/features/fila/models/ordem_model.dart';
import 'package:carregamento_operador/features/motorista/models/ordem_motorista_model.dart';
import 'package:carregamento_operador/features/chat/models/mensagem_model.dart';
import 'package:carregamento_operador/features/auth/models/usuario_model.dart';

void main() {
  group('UsuarioModel', () {
    test('fromJson cria modelo corretamente', () {
      final json = {
        'id': 1,
        'name': 'João',
        'email': 'joao@teste.com',
        'perfil': 'MOTORISTA',
        'ponto_carregamento_id': null,
      };
      final u = UsuarioModel.fromJson(json);
      expect(u.id, 1);
      expect(u.name, 'João');
      expect(u.isMotorista, isTrue);
      expect(u.isOperador, isFalse);
    });

    test('isOperador retorna true para OPERADOR', () {
      final u = UsuarioModel.fromJson({
        'id': 2, 'name': 'Op', 'email': 'op@t.com', 'perfil': 'OPERADOR', 'ponto_carregamento_id': 5,
      });
      expect(u.isOperador, isTrue);
      expect(u.isMotorista, isFalse);
    });
  });

  group('OrdemModel', () {
    final json = {
      'id': 'uuid-123',
      'ticket_guardian': 'TK0001',
      'pedido_numero': 'PED123',
      'cliente_nome': 'Cliente A',
      'produto_codigo': 'BRITA1',
      'produto_descricao': 'Brita 1',
      'quantidade_prevista': 32.0,
      'unidade': 'TN',
      'placa_veiculo': 'ABC1D23',
      'placa_carreta': null,
      'motorista_nome': 'João',
      'tara': 15.0,
      'peso_bruto': null,
      'peso_liquido': null,
      'status': 'AGUARDANDO_CARREGAMENTO',
      'status_label': 'Aguardando Carregamento',
      'iniciado_em': null,
      'concluido_em': null,
      'pilha_produto': {'id': 1, 'codigo': 'PILHA001', 'descricao': 'Pilha Brita'},
      'ponto_carregamento': {'id': 1, 'codigo': 'PONTO001', 'descricao': 'Ponto 1'},
      'divergencias_abertas': 0,
      'created_at': '2026-07-09T12:00:00.000Z',
    };

    test('fromJson cria modelo corretamente', () {
      final o = OrdemModel.fromJson(json);
      expect(o.id, 'uuid-123');
      expect(o.placaVeiculo, 'ABC1D23');
      expect(o.produtoCodigo, 'BRITA1');
      expect(o.quantidadePrevista, 32.0);
      expect(o.status, 'AGUARDANDO_CARREGAMENTO');
      expect(o.statusLabel, 'Aguardando Carregamento');
    });

    test('aguardando retorna true para AGUARDANDO_CARREGAMENTO', () {
      final o = OrdemModel.fromJson({...json, 'status': 'AGUARDANDO_CARREGAMENTO'});
      expect(o.aguardando, isTrue);
      expect(o.emCarregamento, isFalse);
    });

    test('emCarregamento retorna true para EM_CARREGAMENTO', () {
      final o = OrdemModel.fromJson({...json, 'status': 'EM_CARREGAMENTO'});
      expect(o.emCarregamento, isTrue);
      expect(o.aguardando, isFalse);
    });

    test('temDivergencia retorna true se > 0', () {
      final o = OrdemModel.fromJson({...json, 'divergencias_abertas': 2});
      expect(o.temDivergencia, isTrue);
    });

    test('temDivergencia retorna false se 0', () {
      final o = OrdemModel.fromJson({...json, 'divergencias_abertas': 0});
      expect(o.temDivergencia, isFalse);
    });

    test('estaAtivo retorna false para FINALIZADO', () {
      final o = OrdemModel.fromJson({...json, 'status': 'FINALIZADO'});
      expect(o.estaAtivo, isFalse);
    });

    test('estaAtivo retorna true para EM_CARREGAMENTO', () {
      final o = OrdemModel.fromJson({...json, 'status': 'EM_CARREGAMENTO'});
      expect(o.estaAtivo, isTrue);
    });
  });

  group('OrdemMotoristaModel', () {
    final json = {
      'id': 'uuid-456',
      'ticket_guardian': 'TK0002',
      'produto_codigo': 'AREIA',
      'produto_descricao': 'Areia Lavada',
      'quantidade_prevista': 25.0,
      'placa_veiculo': 'XYZ9X99',
      'placa_carreta': null,
      'status': 'AGUARDANDO_CARREGAMENTO',
      'status_label': 'Aguardando Carregamento',
      'motorista_nome': 'João Motorista',
      'tara': 12.0,
      'peso_bruto': null,
      'peso_liquido': null,
      'pilha_produto': {'id': 1, 'codigo': 'PILHA002', 'descricao': 'Pilha Areia'},
      'ponto_carregamento': {'id': 2, 'codigo': 'PONTO002', 'descricao': 'Unidade 2'},
      'divergencias_abertas': 0,
    };

    test('fromJson cria modelo corretamente', () {
      final o = OrdemMotoristaModel.fromJson(json);
      expect(o.id, 'uuid-456');
      expect(o.produtoCodigo, 'AREIA');
      expect(o.quantidadePrevista, 25.0);
      expect(o.placaVeiculo, 'XYZ9X99');
    });

    test('podeSePosicionar retorna true para EM_CARREGAMENTO', () {
      final o = OrdemMotoristaModel.fromJson({...json, 'status': 'EM_CARREGAMENTO'});
      expect(o.podeSePosicionar, isTrue);
      expect(o.aguardando, isFalse);
    });

    test('aguardando retorna true para AGUARDANDO_CARREGAMENTO', () {
      final o = OrdemMotoristaModel.fromJson({...json, 'status': 'AGUARDANDO_CARREGAMENTO'});
      expect(o.aguardando, isTrue);
      expect(o.podeSePosicionar, isFalse);
    });

    test('estaAtivo retorna false para FINALIZADO', () {
      final o = OrdemMotoristaModel.fromJson({...json, 'status': 'FINALIZADO'});
      expect(o.estaAtivo, isFalse);
    });

    test('estaAtivo retorna false para CANCELADO', () {
      final o = OrdemMotoristaModel.fromJson({...json, 'status': 'CANCELADO'});
      expect(o.estaAtivo, isFalse);
    });

    test('pilhaProduto e pontoCarregamento sao acessiveis', () {
      final o = OrdemMotoristaModel.fromJson(json);
      expect(o.pilhaProduto?['codigo'], 'PILHA002');
      expect(o.pontoCarregamento?['codigo'], 'PONTO002');
    });
  });

  group('MensagemModel', () {
    test('fromJson cria modelo corretamente', () {
      final json = {
        'id': 1,
        'remetente_id': 1,
        'perfil_remetente': 'OPERADOR',
        'mensagem': 'Pode posicionar',
        'created_at': '2026-07-09T12:00:00.000Z',
      };
      final m = MensagemModel.fromJson(json);
      expect(m.id, 1);
      expect(m.mensagem, 'Pode posicionar');
      expect(m.isDoOperador, isTrue);
      expect(m.isDoMotorista, isFalse);
    });

    test('isDoMotorista retorna true para MOTORISTA', () {
      final m = MensagemModel.fromJson({
        'id': 2, 'remetente_id': 2, 'perfil_remetente': 'MOTORISTA',
        'mensagem': 'Chegando', 'created_at': '2026-07-09T12:01:00.000Z',
      });
      expect(m.isDoMotorista, isTrue);
      expect(m.isDoOperador, isFalse);
    });

    test('isDoOperador retorna true para ADMIN e EXPEDICAO', () {
      for (final perfil in ['ADMIN', 'EXPEDICAO']) {
        final m = MensagemModel.fromJson({
          'id': 3, 'remetente_id': 3, 'perfil_remetente': perfil,
          'mensagem': 'teste', 'created_at': '2026-07-09T12:00:00.000Z',
        });
        expect(m.isDoOperador, isTrue, reason: 'perfil=$perfil');
      }
    });
  });
}
