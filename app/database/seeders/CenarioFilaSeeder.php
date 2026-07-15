<?php

namespace Database\Seeders;

use App\Domain\Carregamento\Actions\ResolverMotoristaAction;
use App\Domain\Carregamento\Enums\PerfilUsuario;
use App\Domain\Carregamento\Enums\StatusOrdem;
use App\Domain\Carregamento\Enums\StatusPonto;
use App\Domain\Carregamento\Models\Equipamento;
use App\Domain\Carregamento\Models\OrdemCarregamento;
use App\Domain\Carregamento\Models\PilhaProduto;
use App\Domain\Carregamento\Models\PontoCarregamento;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class CenarioFilaSeeder extends Seeder
{
    /**
     * Cria cenário completo para testar a fila do operador:
     * - 1 operador com ponto vinculado
     * - 3 motoristas com documento
     * - 7 ordens em status diversos, sendo 2 na fila (AGUARDANDO_CARREGAMENTO)
     *   e 1 sendo carregada agora (EM_CARREGAMENTO)
     */
    public function run(): void
    {
        // ── 1. Ponto de Carregamento ──
        $ponto = PontoCarregamento::updateOrCreate(
            ['codigo' => 'PONTO_FILA'],
            ['descricao' => 'Bica Fila Teste', 'status' => StatusPonto::ATIVO]
        );

        // ── 2. Pilhas de Produto ──
        $pilhaBrita = PilhaProduto::updateOrCreate(
            ['codigo' => 'PILHA_FILA_BRITA'],
            ['descricao' => 'Pilha Brita Fila', 'produto_codigo' => 'BRITA1', 'produto_descricao' => 'Brita 1', 'ativa' => true]
        );
        $pilhaAreia = PilhaProduto::updateOrCreate(
            ['codigo' => 'PILHA_FILA_AREIA'],
            ['descricao' => 'Pilha Areia Fila', 'produto_codigo' => 'AREIA1', 'produto_descricao' => 'Areia Média', 'ativa' => true]
        );

        // Vincula produtos ao ponto
        $pilhaBrita->pontosCarregamento()->syncWithoutDetaching([
            $ponto->id => ['produto_codigo' => 'BRITA1', 'produto_descricao' => 'Brita 1'],
        ]);
        $pilhaAreia->pontosCarregamento()->syncWithoutDetaching([
            $ponto->id => ['produto_codigo' => 'AREIA1', 'produto_descricao' => 'Areia Média'],
        ]);

        // ── 3. Equipamento ──
        Equipamento::updateOrCreate(
            ['codigo' => 'EQUIP_FILA'],
            ['descricao' => 'Pá Carregadeira Fila', 'tipo' => 'PA_CARREGADEIRA', 'ativo' => true]
        );

        // ── 4. Usuários ──
        $operador = User::updateOrCreate(
            ['email' => 'op.fila@teste.com'],
            [
                'name' => 'Operador Fila',
                'password' => Hash::make('password'),
                'perfil' => PerfilUsuario::OPERADOR,
                'ponto_carregamento_id' => $ponto->id,
                'email_verified_at' => now(),
            ]
        );

        $motoristas = [];
        foreach ([
            ['nome' => 'Carlos Caminhoneiro', 'email' => 'carlos.fila@teste.com', 'doc' => '33333333333'],
            ['nome' => 'Ana Transportadora',  'email' => 'ana.fila@teste.com',   'doc' => '44444444444'],
            ['nome' => 'Pedro Carga Seca',    'email' => 'pedro.fila@teste.com',  'doc' => '55555555555'],
        ] as $m) {
            $motoristas[] = User::updateOrCreate(
                ['email' => $m['email']],
                [
                    'name' => $m['nome'],
                    'password' => Hash::make('password'),
                    'perfil' => PerfilUsuario::MOTORISTA,
                    'documento' => $m['doc'],
                    'email_verified_at' => now(),
                ]
            );
        }

        [$carlos, $ana, $pedro] = $motoristas;

        $resolverMotorista = app(ResolverMotoristaAction::class);

        // ── 5. Ordens de Carregamento ──
        $base = [
            'empresa' => '01',
            'filial' => '01',
            'pedido_item' => '01',
            'unidade' => 'TN',
            'quantidade_prevista' => 32.0,
            'tolerancia_percentual' => 5.0,
            'pilha_produto_id' => $pilhaBrita->id,
            'ponto_carregamento_id' => $ponto->id,
        ];

        $ordens = [];

        // ── ORDEM 1: CRIADO — acabou de chegar, sem tara ──
        $ordens['FILA001'] = array_merge($base, [
            'pedido_numero' => 'FILA001',
            'ticket_guardian' => 'TK_FILA_001',
            'cliente_codigo' => 'CLI_FILA_100',
            'cliente_nome' => 'Construtora Fila',
            'produto_codigo' => 'BRITA1',
            'produto_descricao' => 'Brita 1',
            'placa_veiculo' => 'FIL1A01',
            'motorista_nome' => $carlos->name,
            'motorista_documento' => $carlos->documento,
            'status' => StatusOrdem::CRIADO,
        ]);

        // ── ORDEM 2: TARA_REALIZADA — tara ok, esperando liberação p/ fila ──
        $ordens['FILA002'] = array_merge($base, [
            'pedido_numero' => 'FILA002',
            'ticket_guardian' => 'TK_FILA_002',
            'cliente_codigo' => 'CLI_FILA_100',
            'cliente_nome' => 'Construtora Fila',
            'produto_codigo' => 'BRITA1',
            'produto_descricao' => 'Brita 1',
            'placa_veiculo' => 'FIL2A02',
            'motorista_nome' => $ana->name,
            'motorista_documento' => $ana->documento,
            'tara' => 14.8,
            'status' => StatusOrdem::TARA_REALIZADA,
        ]);

        // ── ORDEM 3: AGUARDANDO_CARREGAMENTO — na fila, pronto p/ carregar ──
        $ordens['FILA003'] = array_merge($base, [
            'pedido_numero' => 'FILA003',
            'ticket_guardian' => 'TK_FILA_003',
            'cliente_codigo' => 'CLI_FILA_200',
            'cliente_nome' => 'Marmoraria Fila',
            'produto_codigo' => 'BRITA1',
            'produto_descricao' => 'Brita 1',
            'placa_veiculo' => 'FIL3A03',
            'motorista_nome' => $pedro->name,
            'motorista_documento' => $pedro->documento,
            'tara' => 15.2,
            'status' => StatusOrdem::AGUARDANDO_CARREGAMENTO,
        ]);

        // ── ORDEM 4: AGUARDANDO_CARREGAMENTO — na fila, produto diferente ──
        $ordens['FILA004'] = array_merge($base, [
            'pedido_numero' => 'FILA004',
            'ticket_guardian' => 'TK_FILA_004',
            'cliente_codigo' => 'CLI_FILA_300',
            'cliente_nome' => 'Areal Fila',
            'produto_codigo' => 'AREIA1',
            'produto_descricao' => 'Areia Média',
            'placa_veiculo' => 'FIL4A04',
            'motorista_nome' => $carlos->name,
            'motorista_documento' => $carlos->documento,
            'tara' => 13.5,
            'quantidade_prevista' => 28.0,
            'pilha_produto_id' => $pilhaAreia->id,
            'status' => StatusOrdem::AGUARDANDO_CARREGAMENTO,
        ]);

        // ── ORDEM 5: EM_CARREGAMENTO — sendo carregada AGORA pelo operador ──
        $ordens['FILA005'] = array_merge($base, [
            'pedido_numero' => 'FILA005',
            'ticket_guardian' => 'TK_FILA_005',
            'cliente_codigo' => 'CLI_FILA_100',
            'cliente_nome' => 'Construtora Fila',
            'produto_codigo' => 'BRITA1',
            'produto_descricao' => 'Brita 1',
            'placa_veiculo' => 'FIL5A05',
            'motorista_nome' => $ana->name,
            'motorista_documento' => $ana->documento,
            'tara' => 15.0,
            'operador_id' => $operador->id,
            'iniciado_em' => now()->subMinutes(10),
            'status' => StatusOrdem::EM_CARREGAMENTO,
        ]);

        // ── ORDEM 6: CARREGAMENTO_CONCLUIDO — carga feita, aguardando pesagem ──
        $ordens['FILA006'] = array_merge($base, [
            'pedido_numero' => 'FILA006',
            'ticket_guardian' => 'TK_FILA_006',
            'cliente_codigo' => 'CLI_FILA_200',
            'cliente_nome' => 'Marmoraria Fila',
            'produto_codigo' => 'BRITA1',
            'produto_descricao' => 'Brita 1',
            'placa_veiculo' => 'FIL6A06',
            'motorista_nome' => $pedro->name,
            'motorista_documento' => $pedro->documento,
            'tara' => 14.9,
            'operador_id' => $operador->id,
            'iniciado_em' => now()->subMinutes(45),
            'concluido_em' => now()->subMinutes(15),
            'status' => StatusOrdem::CARREGAMENTO_CONCLUIDO,
        ]);

        // ── ORDEM 7: DIVERGENCIA — com problema, NÃO aparece na fila ──
        $ordens['FILA007'] = array_merge($base, [
            'pedido_numero' => 'FILA007',
            'ticket_guardian' => 'TK_FILA_007',
            'cliente_codigo' => 'CLI_FILA_300',
            'cliente_nome' => 'Areal Fila',
            'produto_codigo' => 'AREIA1',
            'produto_descricao' => 'Areia Média',
            'placa_veiculo' => 'FIL7A07',
            'motorista_nome' => $carlos->name,
            'motorista_documento' => $carlos->documento,
            'tara' => 14.2,
            'pilha_produto_id' => $pilhaAreia->id,
            'status' => StatusOrdem::DIVERGENCIA,
        ]);

        // ── Persiste ordens e resolve motorista ──
        foreach ($ordens as $pedidoNumero => $atributos) {
            $ordem = OrdemCarregamento::updateOrCreate(
                ['pedido_numero' => $pedidoNumero],
                $atributos,
            );
            $resolverMotorista->execute($ordem);
        }

        $this->command?->info('Cenário de fila criado com sucesso!');
        $this->command?->info("Operador: op.fila@teste.com / password");
        $this->command?->info("Ponto: {$ponto->codigo} ({$ponto->descricao})");
        $this->command?->info("Ordens na fila: FILA003, FILA004 (aguardando), FILA005 (carregando)");
    }
}
