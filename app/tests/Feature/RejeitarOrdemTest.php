<?php

use App\Domain\Carregamento\Enums\PerfilUsuario;
use App\Domain\Carregamento\Enums\StatusOrdem;
use App\Domain\Carregamento\Models\OrdemCarregamento;
use App\Models\User;
use Symfony\Component\HttpFoundation\Response;

describe('Rejeitar ordem', function () {

    it('rejeita ordem gera divergencia, nunca cancelado', function () {
        [$ponto, $pilha] = criarPontoComPilha();
        $operador = User::factory()->create([
            'perfil'                 => PerfilUsuario::OPERADOR,
            'ponto_carregamento_id'  => $ponto->id,
        ]);

        $ordem = ordemBase($ponto, [
            'status' => StatusOrdem::EM_CARREGAMENTO,
        ]);

        $response = $this->actingAs($operador)
            ->postJson("/api/v1/ordens-carregamento/{$ordem->id}/rejeitar", [
                'descricao' => 'Caminhão com problema na carroceria',
            ]);

        $response->assertStatus(Response::HTTP_CREATED);

        $ordem->refresh();
        expect($ordem->status)->toBe(StatusOrdem::DIVERGENCIA);
        expect($ordem->divergenciasAbertas()->count())->toBe(1);
        expect($ordem->divergenciasAbertas()->first()->tipo->value)->toBe('REJEITADO_PELO_OPERADOR');
    });

    it('rejeitar sem descricao retorna 422', function () {
        [$ponto, $pilha] = criarPontoComPilha();
        $operador = User::factory()->create([
            'perfil'                 => PerfilUsuario::OPERADOR,
            'ponto_carregamento_id'  => $ponto->id,
        ]);

        $ordem = ordemBase($ponto);

        $this->actingAs($operador)
            ->postJson("/api/v1/ordens-carregamento/{$ordem->id}/rejeitar", [])
            ->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY);
    });

    it('operador de outro ponto nao pode rejeitar', function () {
        [$pontoA,] = criarPontoComPilha();
        [$pontoB,] = criarPontoComPilha();

        $operador = User::factory()->create([
            'perfil'                 => PerfilUsuario::OPERADOR,
            'ponto_carregamento_id'  => $pontoB->id,
        ]);

        $ordem = ordemBase($pontoA, [
            'status' => StatusOrdem::EM_CARREGAMENTO,
        ]);

        $this->actingAs($operador)
            ->postJson("/api/v1/ordens-carregamento/{$ordem->id}/rejeitar", [
                'descricao' => 'Problema qualquer',
            ])
            ->assertStatus(Response::HTTP_FORBIDDEN);
    });

    it('perfil sem permissao nao pode rejeitar', function () {
        [$ponto,] = criarPontoComPilha();
        $user = User::factory()->create(['perfil' => PerfilUsuario::MOTORISTA]);

        $ordem = ordemBase($ponto);

        $this->actingAs($user)
            ->postJson("/api/v1/ordens-carregamento/{$ordem->id}/rejeitar", [
                'descricao' => 'Problema qualquer',
            ])
            ->assertStatus(Response::HTTP_FORBIDDEN);
    });

    it('ordem em status que nao pode transicionar rejeita 422', function () {
        [$ponto,] = criarPontoComPilha();
        $operador = User::factory()->create([
            'perfil'                 => PerfilUsuario::OPERADOR,
            'ponto_carregamento_id'  => $ponto->id,
        ]);

        $ordem = ordemBase($ponto, [
            'status' => StatusOrdem::FINALIZADO,
        ]);

        $this->actingAs($operador)
            ->postJson("/api/v1/ordens-carregamento/{$ordem->id}/rejeitar", [
                'descricao' => 'Problema qualquer',
            ])
            ->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY);
    });

    it('operador de outro ponto nao pode concluir (RN-010)', function () {
        [$pontoA,] = criarPontoComPilha();
        [$pontoB,] = criarPontoComPilha();

        $operador = User::factory()->create([
            'perfil'                 => PerfilUsuario::OPERADOR,
            'ponto_carregamento_id'  => $pontoB->id,
        ]);

        $ordem = ordemBase($pontoA, [
            'status' => StatusOrdem::EM_CARREGAMENTO,
        ]);

        $this->actingAs($operador)
            ->postJson("/api/v1/ordens-carregamento/{$ordem->id}/concluir")
            ->assertStatus(Response::HTTP_FORBIDDEN);
    });

    it('operador de outro ponto nao pode liberar para fila (RN-010)', function () {
        [$pontoA,] = criarPontoComPilha();
        [$pontoB,] = criarPontoComPilha();

        $operador = User::factory()->create([
            'perfil'                 => PerfilUsuario::OPERADOR,
            'ponto_carregamento_id'  => $pontoB->id,
        ]);

        $ordem = ordemBase($pontoA, [
            'status' => StatusOrdem::TARA_REALIZADA,
        ]);

        $this->actingAs($operador)
            ->postJson("/api/v1/fila-carregamento/{$ordem->id}/liberar")
            ->assertStatus(Response::HTTP_FORBIDDEN);
    });

    it('evento registrado com origem APP_OPERADOR', function () {
        [$ponto, $pilha] = criarPontoComPilha();
        $operador = User::factory()->create([
            'perfil'                 => PerfilUsuario::OPERADOR,
            'ponto_carregamento_id'  => $ponto->id,
        ]);

        $ordem = ordemBase($ponto, [
            'status' => StatusOrdem::EM_CARREGAMENTO,
        ]);

        $this->actingAs($operador)
            ->postJson("/api/v1/ordens-carregamento/{$ordem->id}/rejeitar", [
                'descricao' => 'Problema na carroceria detectado',
            ]);

        $eventos = $ordem->fresh()->eventos;
        expect($eventos->last()->origem->value)->toBe('APP_OPERADOR');
    });
});
