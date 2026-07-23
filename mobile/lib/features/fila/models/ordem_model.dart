class OrdemModel {
  final String id;
  final String? ticketGuardian;
  final String? pedidoNumero;
  final String? clienteNome;
  final String produtoCodigo;
  final String? produtoDescricao;
  final double quantidadePrevista;
  final String unidade;
  final String placaVeiculo;
  final String? placaCarreta;
  final String? motoristaNome;
  final double? tara;
  final double? pesoBruto;
  final double? pesoLiquido;
  final String status;
  final String statusLabel;
  final String? iniciadoEm;
  final String? concluidoEm;
  final Map<String, dynamic>? pilhaProduto;
  final Map<String, dynamic>? pontoCarregamento;
  final int? divergenciasAbertas;
  final int? mensagensNaoLidas;
  final String? createdAt;

  const OrdemModel({
    required this.id,
    this.ticketGuardian,
    this.pedidoNumero,
    this.clienteNome,
    required this.produtoCodigo,
    this.produtoDescricao,
    required this.quantidadePrevista,
    required this.unidade,
    required this.placaVeiculo,
    this.placaCarreta,
    this.motoristaNome,
    this.tara,
    this.pesoBruto,
    this.pesoLiquido,
    required this.status,
    required this.statusLabel,
    this.iniciadoEm,
    this.concluidoEm,
    this.pilhaProduto,
    this.pontoCarregamento,
    this.divergenciasAbertas,
    this.mensagensNaoLidas,
    this.createdAt,
  });

  factory OrdemModel.fromJson(Map<String, dynamic> j) => OrdemModel(
    id: j['id'],
    ticketGuardian: j['ticket_guardian'],
    pedidoNumero: j['pedido_numero'],
    clienteNome: j['cliente_nome'],
    produtoCodigo: j['produto_codigo'],
    produtoDescricao: j['produto_descricao'],
    quantidadePrevista: (j['quantidade_prevista'] as num).toDouble(),
    unidade: j['unidade'] ?? 'TN',
    placaVeiculo: j['placa_veiculo'],
    placaCarreta: j['placa_carreta'],
    motoristaNome: j['motorista_nome'],
    tara: j['tara'] != null ? (j['tara'] as num).toDouble() : null,
    pesoBruto: j['peso_bruto'] != null
        ? (j['peso_bruto'] as num).toDouble()
        : null,
    pesoLiquido: j['peso_liquido'] != null
        ? (j['peso_liquido'] as num).toDouble()
        : null,
    status: j['status'],
    statusLabel: j['status_label'],
    iniciadoEm: j['iniciado_em'],
    concluidoEm: j['concluido_em'],
    pilhaProduto: j['pilha_produto'],
    pontoCarregamento: j['ponto_carregamento'],
    divergenciasAbertas: j['divergencias_abertas'],
    mensagensNaoLidas: j['mensagens_nao_lidas'],
    createdAt: j['created_at'],
  );

  bool get emCarregamento => status == 'EM_CARREGAMENTO';
  bool get aguardando => status == 'AGUARDANDO_CARREGAMENTO';
  bool get temDivergencia => (divergenciasAbertas ?? 0) > 0;
  bool get temMensagensNaoLidas => (mensagensNaoLidas ?? 0) > 0;

  bool get estaAtivo => !['CANCELADO', 'FINALIZADO'].contains(status);
}
