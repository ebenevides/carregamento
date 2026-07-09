<?php

use App\Domain\Carregamento\Enums\PerfilUsuario;
use App\Domain\Carregamento\Enums\StatusOrdem;
use App\Domain\Carregamento\Models\OrdemCarregamento;
use App\Domain\Chat\Models\MensagemChat;
use App\Models\User;
use Symfony\Component\HttpFoundation\Response;

describe('Chat da ordem', function () {

    it('operador do ponto le e envia mensagens', function () {
        [$ponto, $pilha] = criarPontoComPilha();
        $operador = User::factory()->create([
            'perfil'                 => PerfilUsuario::OPERADOR,
            'ponto_carregamento_id'  => $ponto->id,
        ]);

        $ordem = ordemBase($ponto);

        // Enviar
        $this->actingAs($operador)
            ->postJson("/api/v1/ordens-carregamento/{$ordem->id}/mensagens", [
                'mensagem' => 'Chegou o caminhão',
            ])
            ->assertStatus(Response::HTTP_CREATED);

        // Ler
        $response = $this->actingAs($operador)
            ->getJson("/api/v1/ordens-carregamento/{$ordem->id}/mensagens");

        $response->assertStatus(Response::HTTP_OK);
        expect($response->json('data'))->toHaveCount(1);
        expect($response->json('data.0.mensagem'))->toBe('Chegou o caminhão');
    });

    it('motorista vinculado le e envia mensagens', function () {
        [$ponto, $pilha] = criarPontoComPilha();

        $motorista = User::factory()->create([
            'perfil'   => PerfilUsuario::MOTORISTA,
        ]);

        $ordem = OrdemCarregamento::factory()->create([
            'motorista_user_id'     => $motorista->id,
            'ponto_carregamento_id' => $ponto->id,
            'pilha_produto_id'      => $pilha->id,
            'status'                => StatusOrdem::AGUARDANDO_CARREGAMENTO,
            'ticket_guardian'       => 'TK0001',
            'tara'                  => 15.0,
            'quantidade_prevista'   => 32.0,
        ]);

        $this->actingAs($motorista)
            ->postJson("/api/v1/ordens-carregamento/{$ordem->id}/mensagens", [
                'mensagem' => 'Estou aqui',
            ])
            ->assertStatus(Response::HTTP_CREATED);

        $response = $this->actingAs($motorista)
            ->getJson("/api/v1/ordens-carregamento/{$ordem->id}/mensagens");

        expect($response->json('data.0.mensagem'))->toBe('Estou aqui');
    });

    it('terceiro sem acesso recebe 403', function () {
        [$ponto, $pilha] = criarPontoComPilha();
        [$outroPonto,] = criarPontoComPilha();
        $outro = User::factory()->create([
            'perfil'                 => PerfilUsuario::OPERADOR,
            'ponto_carregamento_id'  => $outroPonto->id,
        ]);

        $ordem = ordemBase($ponto);

        $this->actingAs($outro)
            ->postJson("/api/v1/ordens-carregamento/{$ordem->id}/mensagens", [
                'mensagem' => 'invadindo',
            ])
            ->assertStatus(Response::HTTP_FORBIDDEN);

        $this->actingAs($outro)
            ->getJson("/api/v1/ordens-carregamento/{$ordem->id}/mensagens")
            ->assertStatus(Response::HTTP_FORBIDDEN);
    });

    it('envio bloqueado em ordem finalizada retorna 422', function () {
        [$ponto, $pilha] = criarPontoComPilha();
        $operador = User::factory()->create([
            'perfil'                 => PerfilUsuario::OPERADOR,
            'ponto_carregamento_id'  => $ponto->id,
        ]);

        $ordem = ordemBase($ponto, [
            'status' => StatusOrdem::FINALIZADO,
        ]);

        $this->actingAs($operador)
            ->postJson("/api/v1/ordens-carregamento/{$ordem->id}/mensagens", [
                'mensagem' => 'mensagem pós finalizado',
            ])
            ->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY);
    });

    it('mensagens sao ordenadas por created_at crescente', function () {
        [$ponto, $pilha] = criarPontoComPilha();
        $operador = User::factory()->create([
            'perfil'                 => PerfilUsuario::OPERADOR,
            'ponto_carregamento_id'  => $ponto->id,
        ]);

        $ordem = ordemBase($ponto);

        MensagemChat::create([
            'ordem_carregamento_id' => $ordem->id,
            'remetente_id'          => $operador->id,
            'perfil_remetente'      => PerfilUsuario::OPERADOR->value,
            'mensagem'              => 'Primeira',
            'created_at'            => now()->subMinutes(2),
        ]);
        MensagemChat::create([
            'ordem_carregamento_id' => $ordem->id,
            'remetente_id'          => $operador->id,
            'perfil_remetente'      => PerfilUsuario::OPERADOR->value,
            'mensagem'              => 'Segunda',
            'created_at'            => now()->subMinute(),
        ]);

        $response = $this->actingAs($operador)
            ->getJson("/api/v1/ordens-carregamento/{$ordem->id}/mensagens");

        expect($response->json('data.0.mensagem'))->toBe('Primeira');
        expect($response->json('data.1.mensagem'))->toBe('Segunda');
    });
});
