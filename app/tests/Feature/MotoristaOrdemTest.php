<?php

use App\Domain\Carregamento\Models\OrdemCarregamento;
use App\Domain\Carregamento\Enums\PerfilUsuario;
use App\Domain\Carregamento\Enums\StatusOrdem;
use App\Models\User;
use Symfony\Component\HttpFoundation\Response;

describe('Motorista - minha ordem', function () {

    it('retorna 403 se usuario nao e motorista', function () {
        $user = User::factory()->create(['perfil' => PerfilUsuario::OPERADOR]);

        $this->actingAs($user)
            ->getJson('/api/v1/motorista/minha-ordem')
            ->assertStatus(Response::HTTP_FORBIDDEN);
    });

    it('retorna 204 se motorista nao tem ordem ativa', function () {
        $motorista = User::factory()->create([
            'perfil'   => PerfilUsuario::MOTORISTA,
            'documento' => '12345678901',
        ]);

        $this->actingAs($motorista)
            ->getJson('/api/v1/motorista/minha-ordem')
            ->assertStatus(Response::HTTP_NO_CONTENT);
    });

    it('retorna ordem ativa do motorista com dados legiveis', function () {
        [$ponto, $pilha] = criarPontoComPilha();

        $motorista = User::factory()->create([
            'perfil'   => PerfilUsuario::MOTORISTA,
            'documento' => '12345678901',
        ]);

        $ordem = OrdemCarregamento::factory()->create([
            'motorista_user_id'     => $motorista->id,
            'motorista_nome'        => 'João Motorista',
            'motorista_documento'   => '12345678901',
            'ponto_carregamento_id' => $ponto->id,
            'pilha_produto_id'      => $pilha->id,
            'status'                => StatusOrdem::EM_CARREGAMENTO,
            'ticket_guardian'       => 'TK0001',
            'produto_codigo'        => 'BRITA1',
            'produto_descricao'     => 'Brita 1',
            'quantidade_prevista'   => 32.0,
            'placa_veiculo'         => 'ABC1D23',
        ]);

        $response = $this->actingAs($motorista)
            ->getJson('/api/v1/motorista/minha-ordem');

        $response->assertStatus(Response::HTTP_OK);
        $response->assertJsonFragment([
            'id'                  => $ordem->id,
            'ticket_guardian'     => 'TK0001',
            'produto_codigo'      => 'BRITA1',
            'produto_descricao'   => 'Brita 1',
            'quantidade_prevista' => $ordem->quantidade_prevista,
            'placa_veiculo'       => 'ABC1D23',
            'status'              => 'EM_CARREGAMENTO',
        ]);
        $response->assertJsonStructure([
            'data' => [
                'pilha_produto'       => ['id', 'codigo', 'descricao'],
                'ponto_carregamento'  => ['id', 'codigo', 'descricao'],
            ],
        ]);
    });

    it('nao retorna ordem finalizada ou cancelada', function () {
        [$ponto, $pilha] = criarPontoComPilha();

        $motorista = User::factory()->create([
            'perfil'   => PerfilUsuario::MOTORISTA,
            'documento' => '12345678901',
        ]);

        OrdemCarregamento::factory()->create([
            'motorista_user_id'     => $motorista->id,
            'ponto_carregamento_id' => $ponto->id,
            'pilha_produto_id'      => $pilha->id,
            'status'                => StatusOrdem::FINALIZADO,
        ]);

        $this->actingAs($motorista)
            ->getJson('/api/v1/motorista/minha-ordem')
            ->assertStatus(Response::HTTP_NO_CONTENT);
    });

    it('motorista so ve propria ordem, nao de outro motorista', function () {
        [$ponto, $pilha] = criarPontoComPilha();

        $motoristaA = User::factory()->create([
            'perfil'   => PerfilUsuario::MOTORISTA,
        ]);
        $motoristaB = User::factory()->create([
            'perfil'   => PerfilUsuario::MOTORISTA,
        ]);

        OrdemCarregamento::factory()->create([
            'motorista_user_id'     => $motoristaA->id,
            'ponto_carregamento_id' => $ponto->id,
            'pilha_produto_id'      => $pilha->id,
            'status'                => StatusOrdem::AGUARDANDO_CARREGAMENTO,
        ]);

        $this->actingAs($motoristaB)
            ->getJson('/api/v1/motorista/minha-ordem')
            ->assertStatus(Response::HTTP_NO_CONTENT);
    });
});
