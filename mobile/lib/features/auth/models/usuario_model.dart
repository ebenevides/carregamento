class UsuarioModel {
  final int id;
  final String name;
  final String email;
  final String perfil;
  final int? pontoCarregamentoId;

  const UsuarioModel({
    required this.id,
    required this.name,
    required this.email,
    required this.perfil,
    this.pontoCarregamentoId,
  });

  factory UsuarioModel.fromJson(Map<String, dynamic> json) => UsuarioModel(
        id: json['id'],
        name: json['name'],
        email: json['email'],
        perfil: json['perfil'],
        pontoCarregamentoId: json['ponto_carregamento_id'],
      );

  bool get isOperador => perfil == 'OPERADOR';
  bool get isMotorista => perfil == 'MOTORISTA';
  bool get isSupervisor => perfil == 'SUPERVISOR' || perfil == 'ADMINISTRADOR';
}
