class OrdemMotoristaModel {
  final String id;
  final String? ticketGuardian;
  final String produtoCodigo;
  final String? produtoDescricao;
  final double quantidadePrevista;
  final String placaVeiculo;
  final String? placaCarreta;
  final String status;
  final String statusLabel;
  final String? motoristaNome;
  final double? tara;
  final double? pesoBruto;
  final double? pesoLiquido;
  final Map<String, dynamic>? pilhaProduto;
  final Map<String, dynamic>? pontoCarregamento;
  final int? divergenciasAbertas;

  const OrdemMotoristaModel({
    required this.id,
    this.ticketGuardian,
    required this.produtoCodigo,
    this.produtoDescricao,
    required this.quantidadePrevista,
    required this.placaVeiculo,
    this.placaCarreta,
    required this.status,
    required this.statusLabel,
    this.motoristaNome,
    this.tara,
    this.pesoBruto,
    this.pesoLiquido,
    this.pilhaProduto,
    this.pontoCarregamento,
    this.divergenciasAbertas,
  });

  factory OrdemMotoristaModel.fromJson(Map<String, dynamic> j) => OrdemMotoristaModel(
        id: j['id'],
        ticketGuardian: j['ticket_guardian'],
        produtoCodigo: j['produto_codigo'],
        produtoDescricao: j['produto_descricao'],
        quantidadePrevista: (j['quantidade_prevista'] as num).toDouble(),
        placaVeiculo: j['placa_veiculo'],
        placaCarreta: j['placa_carreta'],
        status: j['status'],
        statusLabel: j['status_label'],
        motoristaNome: j['motorista_nome'],
        tara: j['tara'] != null ? (j['tara'] as num).toDouble() : null,
        pesoBruto: j['peso_bruto'] != null ? (j['peso_bruto'] as num).toDouble() : null,
        pesoLiquido: j['peso_liquido'] != null ? (j['peso_liquido'] as num).toDouble() : null,
        pilhaProduto: j['pilha_produto'],
        pontoCarregamento: j['ponto_carregamento'],
        divergenciasAbertas: j['divergencias_abertas'],
      );

  bool get emCarregamento => status == 'EM_CARREGAMENTO';
  bool get podeSePosicionar => status == 'EM_CARREGAMENTO';
  bool get aguardando => status == 'AGUARDANDO_CARREGAMENTO';
  bool get estaAtivo =>
      !['CANCELADO', 'FINALIZADO'].contains(status);
}
