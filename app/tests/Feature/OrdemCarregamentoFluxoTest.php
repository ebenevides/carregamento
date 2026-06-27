<?php

use App\Domain\Carregamento\Actions\AlterarStatusOrdemAction;
use App\Domain\Carregamento\Actions\ConcluirCarregamentoAction;
use App\Domain\Carregamento\Actions\CriarOrdemAction;
use App\Domain\Carregamento\Actions\IniciarCarregamentoAction;
use App\Domain\Carregamento\Actions\LiberarParaFaturamentoAction;
use App\Domain\Carregamento\Actions\RegistrarDivergenciaAction;
use App\Domain\Carregamento\Actions\RegistrarPesagemFinalAction;
use App\Domain\Carregamento\DTOs\CriarOrdemDTO;
use App\Domain\Carregamento\Enums\OrigemEvento;
use App\Domain\Carregamento\Enums\StatusOrdem;
use App\Domain\Carregamento\Enums\TipoDivergencia;
use App\Domain\Carregamento\Models\OrdemCarregamento;
use App\Domain\Carregamento\Models\PilhaProduto;
use App\Domain\Carregamento\Models\PontoCarregamento;
use App\Domain\Carregamento\Models\ProdutoPilhaPonto;
use App\Models\User;
use Illuminate\Validation\ValidationException;

// Helpers
function criarPontoComPilha(string $produto = 'BRITA1'): array
{
    $ponto = PontoCarregamento::factory()->create();
    $pilha = PilhaProduto::factory()->create(['produto_codigo' => $produto]);
    ProdutoPilhaPonto::create([
        'produto_codigo'       => $produto,
        'pilha_produto_id'     => $pilha->id,
        'ponto_carregamento_id' => $ponto->id,
        'padrao'               => true,
        'ativo'                => true,
    ]);

    return [$ponto, $pilha];
}

function ordemBase(PontoCarregamento $ponto, array $override = []): OrdemCarregamento
{
    return OrdemCarregamento::factory()->create(array_merge([
        'ponto_carregamento_id' => $ponto->id,
        'ticket_guardian'       => 'TK0001',
        'tara'                  => 15.0,
        'quantidade_prevista'   => 32.0,
        'status'                => StatusOrdem::AGUARDANDO_CARREGAMENTO,
    ], $override));
}

// ─── Fluxo completo CRIADO → FINALIZADO ───────────────────────────────────

describe('Fluxo completo de carregamento', function () {

    it('percorre todos os status de CRIADO até FINALIZADO', function () {
        [$ponto, $pilha] = criarPontoComPilha();

        $dto = new CriarOrdemDTO(
            empresa: '01', filial: '01',
            pedidoNumero: '999001', pedidoItem: '01',
            contratoCodigo: null,
            ticketGuardian: 'TK9001',
            clienteCodigo: 'CLI001', clienteLoja: '01', clienteNome: 'Cliente Teste',
            produtoCodigo: 'BRITA1', produtoDescricao: 'Brita 1',
            quantidadePrevista: 32.0, unidade: 'TN',
            placaVeiculo: 'TST0001', placaCarreta: null,
            motoristaNome: 'João', motoristaDocumento: '12345678901',
            transportadoraCodigo: null, transportadoraNome: null,
            tara: 15.0,
            toleranciaPercentual: 5.0,
        );

        $ordem = app(CriarOrdemAction::class)->execute($dto);
        expect($ordem->status)->toBe(StatusOrdem::TARA_REALIZADA);

        // Entrar na fila
        app(AlterarStatusOrdemAction::class)->execute($ordem->fresh(), new \App\Domain\Carregamento\DTOs\AlterarStatusDTO(
            novoStatus: StatusOrdem::AGUARDANDO_CARREGAMENTO,
            tipoEvento: \App\Domain\Carregamento\Enums\TipoEvento::ORDEM_ENTRADA_FILA,
            origem: OrigemEvento::SISTEMA,
        ));

        $ordem->refresh();
        expect($ordem->status)->toBe(StatusOrdem::AGUARDANDO_CARREGAMENTO);

        $operador = User::factory()->create();

        // Iniciar
        $ordem = app(IniciarCarregamentoAction::class)->execute(
            ordem: $ordem,
            operadorId: $operador->id,
            pontoCarregamentoId: $ponto->id,
            equipamentoCodigo: null,
            observacao: null,
        );
        expect($ordem->status)->toBe(StatusOrdem::EM_CARREGAMENTO);

        // Concluir
        $ordem = app(ConcluirCarregamentoAction::class)->execute(ordem: $ordem, operadorId: $operador->id, observacao: null);
        expect($ordem->status)->toBe(StatusOrdem::AGUARDANDO_PESAGEM_FINAL);

        // Pesagem final dentro da tolerância → auto VALIDADO
        $ordem = app(RegistrarPesagemFinalAction::class)->execute($ordem, pesoBruto: 47.0);
        expect($ordem->status)->toBe(StatusOrdem::VALIDADO);
        expect($ordem->peso_liquido)->toBe('32.000');

        // Liberar para faturamento
        $ordem = app(LiberarParaFaturamentoAction::class)->execute($ordem);
        expect($ordem->status)->toBe(StatusOrdem::FINALIZADO);

        // 10+ eventos registrados
        expect($ordem->eventos()->count())->toBeGreaterThanOrEqual(7);
    });
});

// ─── RN-001: Ticket obrigatório para iniciar ──────────────────────────────

describe('RN-001 ticket obrigatório', function () {

    it('bloqueia iniciar sem ticket_guardian', function () {
        [$ponto] = criarPontoComPilha();
        $ordem = ordemBase($ponto, ['ticket_guardian' => null]);

        expect(fn () => app(IniciarCarregamentoAction::class)->execute(
            ordem: $ordem,
            operadorId: 1,
            pontoCarregamentoId: $ponto->id,
            equipamentoCodigo: null,
            observacao: null,
        ))->toThrow(ValidationException::class);
    });
});

// ─── RN-002: Tara obrigatória para iniciar ────────────────────────────────

describe('RN-002 tara obrigatória', function () {

    it('bloqueia iniciar sem tara', function () {
        [$ponto] = criarPontoComPilha();
        $ordem = ordemBase($ponto, ['tara' => null]);

        expect(fn () => app(IniciarCarregamentoAction::class)->execute(
            ordem: $ordem,
            operadorId: 1,
            pontoCarregamentoId: $ponto->id,
            equipamentoCodigo: null,
            observacao: null,
        ))->toThrow(ValidationException::class);
    });
});

// ─── RN-005: Divergência aberta bloqueia início ───────────────────────────

describe('RN-005 divergência aberta bloqueia início', function () {

    it('bloqueia iniciar quando há divergência aberta', function () {
        [$ponto] = criarPontoComPilha();
        $ordem = ordemBase($ponto);

        // Registrar divergência sem alterar status (insere direto)
        app(RegistrarDivergenciaAction::class)->execute(
            $ordem,
            TipoDivergencia::PILHA_SEM_PRODUTO,
            OrigemEvento::SISTEMA,
            'Sem pilha configurada',
        );

        expect(fn () => app(IniciarCarregamentoAction::class)->execute(
            ordem: $ordem->fresh(),
            operadorId: 1,
            pontoCarregamentoId: $ponto->id,
            equipamentoCodigo: null,
            observacao: null,
        ))->toThrow(ValidationException::class);
    });
});

// ─── RN-007: Tolerância de peso ──────────────────────────────────────────

describe('RN-007 tolerância de peso', function () {

    it('valida automaticamente quando dentro da tolerância', function () {
        [$ponto] = criarPontoComPilha();
        $ordem = OrdemCarregamento::factory()->aguardandoPesagem()->create([
            'ponto_carregamento_id' => $ponto->id,
            'quantidade_prevista'   => 32.0,
            'tara'                  => 15.0,
            'tolerancia_percentual' => 5.0,
        ]);

        // 32.0 previsto, 5% tolerância = ±1.6 kg. Peso bruto 47.0 → líquido 32.0 = dentro
        $resultado = app(RegistrarPesagemFinalAction::class)->execute($ordem, pesoBruto: 47.0);
        expect($resultado->status)->toBe(StatusOrdem::VALIDADO);
    });

    it('cria divergência PESO_FORA_TOLERANCIA quando fora da tolerância', function () {
        [$ponto] = criarPontoComPilha();
        $ordem = OrdemCarregamento::factory()->aguardandoPesagem()->create([
            'ponto_carregamento_id' => $ponto->id,
            'quantidade_prevista'   => 32.0,
            'tara'                  => 15.0,
            'tolerancia_percentual' => 5.0,
        ]);

        // Líquido = 50.0 - 15.0 = 35.0 kg. Diff = 3.0 > 1.6 kg → fora
        $resultado = app(RegistrarPesagemFinalAction::class)->execute($ordem, pesoBruto: 50.0);
        expect($resultado->status)->toBe(StatusOrdem::DIVERGENCIA);
        expect($resultado->divergencias()->where('tipo', TipoDivergencia::PESO_FORA_TOLERANCIA->value)->exists())->toBeTrue();
    });

    it('dentroDaTolerancia calcula corretamente', function () {
        $ordem = OrdemCarregamento::factory()->make([
            'quantidade_prevista'   => 32.0,
            'tara'                  => 15.0,
            'peso_bruto'            => 47.0,
            'peso_liquido'          => 32.0,
            'tolerancia_percentual' => 5.0,
        ]);
        expect($ordem->dentroDaTolerancia())->toBeTrue();

        $ordemFora = OrdemCarregamento::factory()->make([
            'quantidade_prevista'   => 32.0,
            'tara'                  => 15.0,
            'peso_bruto'            => 50.0,
            'peso_liquido'          => 35.0,
            'tolerancia_percentual' => 5.0,
        ]);
        expect($ordemFora->dentroDaTolerancia())->toBeFalse();
    });
});

// ─── Transição inválida lança exceção ────────────────────────────────────

describe('AlterarStatusOrdemAction', function () {

    it('lança ValidationException em transição inválida', function () {
        [$ponto] = criarPontoComPilha();
        $ordem = ordemBase($ponto);

        expect(fn () => app(AlterarStatusOrdemAction::class)->execute($ordem, new \App\Domain\Carregamento\DTOs\AlterarStatusDTO(
            novoStatus: StatusOrdem::FINALIZADO,
            tipoEvento: \App\Domain\Carregamento\Enums\TipoEvento::ORDEM_FINALIZADA,
            origem: OrigemEvento::SISTEMA,
        )))->toThrow(ValidationException::class);
    });
});

// ─── Liberar para faturamento ─────────────────────────────────────────────

describe('LiberarParaFaturamentoAction', function () {

    it('falha se status não for VALIDADO', function () {
        [$ponto] = criarPontoComPilha();
        $ordem = ordemBase($ponto); // AGUARDANDO_CARREGAMENTO

        expect(fn () => app(LiberarParaFaturamentoAction::class)->execute($ordem))
            ->toThrow(ValidationException::class);
    });

    it('falha se peso_liquido nulo', function () {
        [$ponto] = criarPontoComPilha();
        $ordem = OrdemCarregamento::factory()->create([
            'ponto_carregamento_id' => $ponto->id,
            'status'                => StatusOrdem::VALIDADO,
            'ticket_guardian'       => 'TK9999',
            'tara'                  => 15.0,
            'peso_liquido'          => null,
        ]);

        expect(fn () => app(LiberarParaFaturamentoAction::class)->execute($ordem))
            ->toThrow(ValidationException::class);
    });

    it('libera quando tudo válido', function () {
        [$ponto] = criarPontoComPilha();
        $ordem = OrdemCarregamento::factory()->validado()->create([
            'ponto_carregamento_id' => $ponto->id,
            'ticket_guardian'       => 'TK8888',
        ]);

        $resultado = app(LiberarParaFaturamentoAction::class)->execute($ordem);
        expect($resultado->status)->toBe(StatusOrdem::FINALIZADO);
    });
});
