class MensagemModel {
  final int id;
  final int? remetenteId;
  final String? perfilRemetente;
  final String mensagem;
  final String? createdAt;

  const MensagemModel({
    required this.id,
    this.remetenteId,
    this.perfilRemetente,
    required this.mensagem,
    this.createdAt,
  });

  factory MensagemModel.fromJson(Map<String, dynamic> j) => MensagemModel(
        id: j['id'],
        remetenteId: j['remetente_id'],
        perfilRemetente: j['perfil_remetente'],
        mensagem: j['mensagem'],
        createdAt: j['created_at'],
      );

  bool get isDoMotorista => perfilRemetente == 'MOTORISTA';
  bool get isDoOperador =>
      perfilRemetente == 'OPERADOR' ||
      perfilRemetente == 'ADMIN' ||
      perfilRemetente == 'EXPEDICAO';
}
