<?php

namespace Database\Seeders;

use App\Domain\Carregamento\Actions\ResolverMotoristaAction;
use App\Domain\Carregamento\Enums\PerfilUsuario;
use App\Domain\Carregamento\Enums\StatusOrdem;
use App\Domain\Carregamento\Enums\StatusPonto;
use App\Domain\Carregamento\Models\OrdemCarregamento;
use App\Domain\Carregamento\Models\PilhaProduto;
use App\Domain\Carregamento\Models\PontoCarregamento;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        // ── 1. Usuários mockados ──
        $users = [
            ['name' => 'Admin',     'email' => 'admin@carregamento.local',     'perfil' => PerfilUsuario::ADMIN],
            ['name' => 'Expedição', 'email' => 'expedicao@carregamento.local', 'perfil' => PerfilUsuario::EXPEDICAO],
            ['name' => 'Operador',  'email' => 'operador@carregamento.local',  'perfil' => PerfilUsuario::OPERADOR],
        ];

        foreach ($users as $data) {
            User::updateOrCreate(
                ['email' => $data['email']],
                [...$data, 'password' => Hash::make('password'), 'email_verified_at' => now()]
            );
        }

        // Motorista com documento (CPF) — obrigatório para vínculo com ordens via ResolverMotoristaAction
        $motorista = User::updateOrCreate(
            ['email' => 'motorista@carregamento.local'],
            [
                'name'               => 'Motorista Teste',
                'password'           => Hash::make('password'),
                'perfil'             => PerfilUsuario::MOTORISTA,
                'documento'          => '99999999999',
                'email_verified_at'  => now(),
            ]
        );

        // ── 2. Infraestrutura mínima para ordens ──
        $ponto = PontoCarregamento::updateOrCreate(
            ['codigo' => 'PONTO_MTR'],
            ['descricao' => 'Bica Motorista', 'status' => StatusPonto::ATIVO]
        );

        $pilha = PilhaProduto::updateOrCreate(
            ['codigo' => 'PILHA_MTR'],
            [
                'descricao'        => 'Pilha Brita 1 Motorista',
                'produto_codigo'   => 'BRITA1',
                'produto_descricao' => 'Brita 1',
                'ativa'            => true,
            ]
        );

        $pilha->pontosCarregamento()->syncWithoutDetaching([
            $ponto->id => ['produto_codigo' => 'BRITA1', 'produto_descricao' => 'Brita 1'],
        ]);

        // ── 3. Ordens vinculadas ao motorista mockado ──
        $base = [
            'empresa'              => '01',
            'filial'               => '01',
            'pedido_item'          => '01',
            'unidade'              => 'TN',
            'quantidade_prevista'  => 32.0,
            'tolerancia_percentual'=> 5.0,
            'cliente_codigo'       => 'CLI_MTR',
            'cliente_nome'         => 'Construtora Motorista',
            'produto_codigo'       => 'BRITA1',
            'produto_descricao'    => 'Brita 1',
            'pilha_produto_id'     => $pilha->id,
            'ponto_carregamento_id'=> $ponto->id,
            'motorista_nome'       => $motorista->name,
            'motorista_documento'  => $motorista->documento,
        ];

        $ordens = [
            [
                'pedido_numero'  => 'MTR001',
                'ticket_guardian'=> 'TK_MTR_001',
                'placa_veiculo'  => 'MTR1A01',
                'tara'           => 15.0,
                'status'         => StatusOrdem::AGUARDANDO_CARREGAMENTO,
            ],
            [
                'pedido_numero'  => 'MTR002',
                'ticket_guardian'=> 'TK_MTR_002',
                'placa_veiculo'  => 'MTR2B02',
                'placa_carreta'  => 'MTR2B02C',
                'tara'           => 14.8,
                'iniciado_em'    => now()->subMinutes(10),
                'status'         => StatusOrdem::EM_CARREGAMENTO,
            ],
            [
                'pedido_numero'  => 'MTR003',
                'ticket_guardian'=> 'TK_MTR_003',
                'placa_veiculo'  => 'MTR3C03',
                'tara'           => 15.2,
                'iniciado_em'    => now()->subMinutes(45),
                'concluido_em'   => now()->subMinutes(15),
                'status'         => StatusOrdem::CARREGAMENTO_CONCLUIDO,
            ],
        ];

        $resolverMotorista = app(ResolverMotoristaAction::class);

        foreach ($ordens as $attrs) {
            $ordem = OrdemCarregamento::updateOrCreate(
                ['pedido_numero' => $attrs['pedido_numero']],
                array_merge($base, $attrs),
            );
            $resolverMotorista->execute($ordem);
        }

        // ── 4. Cenário completo da fila (operador + múltiplos motoristas) ──
        $this->call(CenarioFilaSeeder::class);

        $this->command?->info('Seed concluído.');
        $this->command?->info('Motorista: motorista@carregamento.local / password  (ordens: MTR001, MTR002, MTR003)');
        $this->command?->info('Operador:  op.fila@teste.com / password');
    }
}
