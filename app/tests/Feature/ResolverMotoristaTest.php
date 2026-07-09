<?php

use App\Domain\Carregamento\Actions\ResolverMotoristaAction;
use App\Domain\Carregamento\Enums\PerfilUsuario;
use App\Domain\Carregamento\Enums\StatusOrdem;
use App\Domain\Carregamento\Models\OrdemCarregamento;
use App\Models\User;

describe('ResolverMotoristaAction', function () {

    it('vincula motorista quando documento bate', function () {
        $motorista = User::factory()->create([
            'perfil'    => PerfilUsuario::MOTORISTA,
            'documento' => '12345678901',
        ]);

        $ordem = OrdemCarregamento::factory()->create([
            'motorista_documento' => '12345678901',
            'status'              => StatusOrdem::CRIADO,
        ]);

        app(ResolverMotoristaAction::class)->execute($ordem);

        $ordem->refresh();
        expect((int) $ordem->motorista_user_id)->toBe((int) $motorista->id);
    });

    it('nao vincula quando documento nao existe', function () {
        $ordem = OrdemCarregamento::factory()->create([
            'motorista_documento' => '99999999999',
            'status'              => StatusOrdem::CRIADO,
        ]);

        app(ResolverMotoristaAction::class)->execute($ordem);

        $ordem->refresh();
        expect($ordem->motorista_user_id)->toBeNull();
    });

    it('nao vincula quando motorista_documento e nulo', function () {
        $ordem = OrdemCarregamento::factory()->create([
            'motorista_documento' => null,
            'status'              => StatusOrdem::CRIADO,
        ]);

        app(ResolverMotoristaAction::class)->execute($ordem);

        $ordem->refresh();
        expect($ordem->motorista_user_id)->toBeNull();
    });

    it('ignora se ja vinculado ao documento correto', function () {
        $motorista = User::factory()->create([
            'perfil'    => PerfilUsuario::MOTORISTA,
            'documento' => '12345678901',
        ]);

        $ordem = OrdemCarregamento::factory()->create([
            'motorista_user_id'   => $motorista->id,
            'motorista_documento' => '12345678901',
            'status'              => StatusOrdem::CRIADO,
        ]);

        app(ResolverMotoristaAction::class)->execute($ordem);

        // update() nao deve ser chamado se ja vinculado — refrescar confirma
        $ordem->refresh();
        expect((int) $ordem->motorista_user_id)->toBe((int) $motorista->id);
    });

    it('vincula quando ordem criada via CriarOrdemAction', function () {
        [$ponto, $pilha] = criarPontoComPilha();

        $motorista = User::factory()->create([
            'perfil'    => PerfilUsuario::MOTORISTA,
            'documento' => '12345678901',
        ]);

        $action = app(App\Domain\Carregamento\Actions\CriarOrdemAction::class);
        $dto = App\Domain\Carregamento\DTOs\CriarOrdemDTO::fromArray([
            'produto_codigo'      => 'BRITA1',
            'quantidade_prevista' => 32.0,
            'placa_veiculo'       => 'ABC1D23',
            'motorista_documento' => '12345678901',
        ]);

        $ordem = $action->execute($dto);

        expect((int) $ordem->motorista_user_id)->toBe((int) $motorista->id);
    });
});
