<?php

namespace Database\Seeders;

use App\Domain\Carregamento\Actions\RegistrarDivergenciaAction;
use App\Domain\Carregamento\Actions\ResolverMotoristaAction;
use App\Domain\Carregamento\Enums\OrigemEvento;
use App\Domain\Carregamento\Enums\PerfilUsuario;
use App\Domain\Carregamento\Enums\StatusPonto;
use App\Domain\Carregamento\Enums\TipoDivergencia;
use App\Domain\Carregamento\Models\Equipamento;
use App\Domain\Carregamento\Models\OrdemCarregamento;
use App\Domain\Carregamento\Models\PilhaProduto;
use App\Domain\Carregamento\Models\PontoCarregamento;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class TestDataSeeder extends Seeder
{
    public function run(): void
    {
        $ponto1 = PontoCarregamento::updateOrCreate(
            ['codigo' => 'PONTO001'],
            ['descricao' => 'Bica 1', 'status' => StatusPonto::ATIVO]
        );

        $ponto2 = PontoCarregamento::updateOrCreate(
            ['codigo' => 'PONTO002'],
            ['descricao' => 'Bica 2', 'status' => StatusPonto::ATIVO]
        );

        $pilha1 = PilhaProduto::updateOrCreate(
            ['codigo' => 'PILHA001'],
            ['descricao' => 'Pilha Brita 1', 'produto_codigo' => 'BRITA1', 'produto_descricao' => 'Brita 1', 'ativa' => true]
        );

        $pilha2 = PilhaProduto::updateOrCreate(
            ['codigo' => 'PILHA002'],
            ['descricao' => 'Pilha Brita 0', 'produto_codigo' => 'BRITA0', 'produto_descricao' => 'Brita 0', 'ativa' => true]
        );

        $pilha1->pontosCarregamento()->syncWithoutDetaching([
            $ponto1->id => ['produto_codigo' => $pilha1->produto_codigo, 'produto_descricao' => $pilha1->produto_descricao],
            $ponto2->id => ['produto_codigo' => $pilha1->produto_codigo, 'produto_descricao' => $pilha1->produto_descricao],
        ]);
        $pilha2->pontosCarregamento()->syncWithoutDetaching([
            $ponto1->id => ['produto_codigo' => $pilha2->produto_codigo, 'produto_descricao' => $pilha2->produto_descricao],
        ]);

        Equipamento::updateOrCreate(['codigo' => 'EQUIP001'], ['descricao' => 'Pá Carregadeira 1', 'tipo' => 'PA_CARREGADEIRA', 'ativo' => true]);
        Equipamento::updateOrCreate(['codigo' => 'EQUIP002'], ['descricao' => 'Pá Carregadeira 2', 'tipo' => 'PA_CARREGADEIRA', 'ativo' => true]);

        $operador1 = User::updateOrCreate(
            ['email' => 'operador1@teste.com'],
            ['name' => 'Operador Bica 1', 'password' => Hash::make('password'), 'perfil' => PerfilUsuario::OPERADOR, 'ponto_carregamento_id' => $ponto1->id, 'email_verified_at' => now()]
        );

        User::updateOrCreate(
            ['email' => 'operador2@teste.com'],
            ['name' => 'Operador Bica 2', 'password' => Hash::make('password'), 'perfil' => PerfilUsuario::OPERADOR, 'ponto_carregamento_id' => $ponto2->id, 'email_verified_at' => now()]
        );

        $motorista1 = User::updateOrCreate(
            ['email' => 'motorista1@teste.com'],
            ['name' => 'João Motorista', 'password' => Hash::make('password'), 'perfil' => PerfilUsuario::MOTORISTA, 'documento' => '11111111111', 'email_verified_at' => now()]
        );

        $motorista2 = User::updateOrCreate(
            ['email' => 'motorista2@teste.com'],
            ['name' => 'Maria Motorista', 'password' => Hash::make('password'), 'perfil' => PerfilUsuario::MOTORISTA, 'documento' => '22222222222', 'email_verified_at' => now()]
        );

        $resolverMotorista = app(ResolverMotoristaAction::class);

        $base = [
            'empresa' => '01',
            'filial' => '01',
            'pedido_item' => '01',
            'unidade' => 'TN',
            'quantidade_prevista' => 32.0,
            'tolerancia_percentual' => 5.0,
        ];

        $ordens = [
            'ORDTESTE01' => array_merge($base, [
                'pedido_numero' => 'ORDTESTE01', 'ticket_guardian' => 'TK00001', 'cliente_codigo' => 'CLI0001', 'cliente_nome' => 'Construtora Alfa',
                'produto_codigo' => 'BRITA1', 'produto_descricao' => 'Brita 1', 'placa_veiculo' => 'ABC1A11',
                'motorista_nome' => $motorista1->name, 'motorista_documento' => $motorista1->documento,
                'pilha_produto_id' => $pilha1->id, 'ponto_carregamento_id' => $ponto1->id,
                'status' => \App\Domain\Carregamento\Enums\StatusOrdem::CRIADO,
            ]),
            'ORDTESTE02' => array_merge($base, [
                'pedido_numero' => 'ORDTESTE02', 'ticket_guardian' => 'TK00002', 'cliente_codigo' => 'CLI0001', 'cliente_nome' => 'Construtora Alfa',
                'produto_codigo' => 'BRITA1', 'produto_descricao' => 'Brita 1', 'placa_veiculo' => 'ABC1A12',
                'motorista_nome' => $motorista1->name, 'motorista_documento' => $motorista1->documento,
                'pilha_produto_id' => $pilha1->id, 'ponto_carregamento_id' => $ponto1->id,
                'tara' => 15.0, 'status' => \App\Domain\Carregamento\Enums\StatusOrdem::TARA_REALIZADA,
            ]),
            'ORDTESTE03' => array_merge($base, [
                'pedido_numero' => 'ORDTESTE03', 'ticket_guardian' => 'TK00003', 'cliente_codigo' => 'CLI0002', 'cliente_nome' => 'Construtora Beta',
                'produto_codigo' => 'BRITA1', 'produto_descricao' => 'Brita 1', 'placa_veiculo' => 'ABC1A13',
                'motorista_nome' => $motorista2->name, 'motorista_documento' => $motorista2->documento,
                'pilha_produto_id' => $pilha1->id, 'ponto_carregamento_id' => $ponto1->id,
                'tara' => 15.0, 'status' => \App\Domain\Carregamento\Enums\StatusOrdem::AGUARDANDO_CARREGAMENTO,
            ]),
            'ORDTESTE04' => array_merge($base, [
                'pedido_numero' => 'ORDTESTE04', 'ticket_guardian' => 'TK00004', 'cliente_codigo' => 'CLI0002', 'cliente_nome' => 'Construtora Beta',
                'produto_codigo' => 'BRITA1', 'produto_descricao' => 'Brita 1', 'placa_veiculo' => 'ABC1A14',
                'motorista_nome' => $motorista2->name, 'motorista_documento' => $motorista2->documento,
                'pilha_produto_id' => $pilha1->id, 'ponto_carregamento_id' => $ponto1->id, 'operador_id' => $operador1->id,
                'tara' => 15.0, 'iniciado_em' => now()->subMinutes(20),
                'status' => \App\Domain\Carregamento\Enums\StatusOrdem::EM_CARREGAMENTO,
            ]),
            'ORDTESTE05' => array_merge($base, [
                'pedido_numero' => 'ORDTESTE05', 'ticket_guardian' => 'TK00005', 'cliente_codigo' => 'CLI0001', 'cliente_nome' => 'Construtora Alfa',
                'produto_codigo' => 'BRITA1', 'produto_descricao' => 'Brita 1', 'placa_veiculo' => 'ABC1A15',
                'motorista_nome' => $motorista1->name, 'motorista_documento' => $motorista1->documento,
                'pilha_produto_id' => $pilha1->id, 'ponto_carregamento_id' => $ponto2->id, 'operador_id' => $operador1->id,
                'tara' => 15.0, 'iniciado_em' => now()->subHour(), 'concluido_em' => now()->subMinutes(10),
                'status' => \App\Domain\Carregamento\Enums\StatusOrdem::AGUARDANDO_PESAGEM_FINAL,
            ]),
            'ORDTESTE06' => array_merge($base, [
                'pedido_numero' => 'ORDTESTE06', 'ticket_guardian' => 'TK00006', 'cliente_codigo' => 'CLI0003', 'cliente_nome' => 'Construtora Gama',
                'produto_codigo' => 'BRITA0', 'produto_descricao' => 'Brita 0', 'placa_veiculo' => 'ABC1A16',
                'motorista_nome' => $motorista2->name, 'motorista_documento' => $motorista2->documento,
                'pilha_produto_id' => $pilha2->id, 'ponto_carregamento_id' => $ponto1->id, 'operador_id' => $operador1->id,
                'tara' => 15.0, 'peso_bruto' => 47.0, 'peso_liquido' => 32.0,
                'iniciado_em' => now()->subHours(3), 'concluido_em' => now()->subHours(2), 'pesagem_final_em' => now()->subHour(),
                'status' => \App\Domain\Carregamento\Enums\StatusOrdem::VALIDADO,
            ]),
            'ORDTESTE07' => array_merge($base, [
                'pedido_numero' => 'ORDTESTE07', 'ticket_guardian' => 'TK00007', 'cliente_codigo' => 'CLI0001', 'cliente_nome' => 'Construtora Alfa',
                'produto_codigo' => 'BRITA1', 'produto_descricao' => 'Brita 1', 'placa_veiculo' => 'ABC1A17',
                'motorista_nome' => $motorista1->name, 'motorista_documento' => $motorista1->documento,
                'pilha_produto_id' => $pilha1->id, 'ponto_carregamento_id' => $ponto2->id,
                'tara' => 15.0, 'iniciado_em' => now()->subMinutes(30),
                'status' => \App\Domain\Carregamento\Enums\StatusOrdem::EM_CARREGAMENTO,
            ]),
        ];

        foreach ($ordens as $pedidoNumero => $atributos) {
            $ordem = OrdemCarregamento::updateOrCreate(['pedido_numero' => $pedidoNumero], $atributos);
            $resolverMotorista->execute($ordem);

            if ($pedidoNumero === 'ORDTESTE07' && ! $ordem->divergencias()->exists()) {
                app(RegistrarDivergenciaAction::class)->execute(
                    $ordem,
                    TipoDivergencia::REJEITADO_PELO_OPERADOR,
                    OrigemEvento::APP_OPERADOR,
                    'Caminhão com problema na carroceria (dado de teste)',
                );
            }
        }
    }
}
