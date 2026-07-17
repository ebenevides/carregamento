<?php

use App\Domain\Carregamento\Enums\PerfilUsuario;
use App\Domain\Carregamento\Enums\StatusOrdem;
use App\Domain\Carregamento\Models\OrdemCarregamento;
use App\Domain\Carregamento\Models\PilhaProduto;
use App\Domain\Carregamento\Models\PontoCarregamento;
use App\Domain\Carregamento\Models\ProdutoPilhaPonto;
use App\Models\User;

describe('Painel Guardian (web) — fila', function () {

    it('lista ordens aguardando fila e sincroniza via botão', function () {
        $admin = User::factory()->create(['perfil' => PerfilUsuario::ADMIN, 'email_verified_at' => now()]);

        $ponto = PontoCarregamento::factory()->create();
        $pilha = PilhaProduto::factory()->create(['produto_codigo' => 'BRITA1']);
        ProdutoPilhaPonto::create([
            'produto_codigo'        => 'BRITA1',
            'pilha_produto_id'      => $pilha->id,
            'ponto_carregamento_id' => $ponto->id,
            'padrao'                => true,
            'ativo'                 => true,
        ]);

        $ordem = OrdemCarregamento::factory()->create([
            'ponto_carregamento_id' => $ponto->id,
            'pilha_produto_id'      => $pilha->id,
            'produto_codigo'        => 'BRITA1',
            'ticket_guardian'       => '0000001', // mock: liberado
            'tara'                  => 15.2,
            'status'                => StatusOrdem::TARA_REALIZADA,
        ]);

        $this->actingAs($admin)
            ->get(route('integracoes.guardian'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Integracoes/Guardian/Index')
                ->has('pendente_fila', 1)
                ->where('pendente_fila.0.id', $ordem->id));

        $this->actingAs($admin)
            ->post(route('integracoes.guardian.sync-fila', ['ordem' => $ordem->id]))
            ->assertRedirect();

        expect($ordem->fresh()->status)->toBe(StatusOrdem::AGUARDANDO_CARREGAMENTO);
    });

});
