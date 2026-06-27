<?php

use App\Domain\Carregamento\Enums\StatusOrdem;

describe('StatusOrdem transições', function () {

    it('permite transições válidas', function (StatusOrdem $de, StatusOrdem $para) {
        expect($de->podeTransicionarPara($para))->toBeTrue();
    })->with([
        'CRIADO → TARA_REALIZADA'                      => [StatusOrdem::CRIADO,                   StatusOrdem::TARA_REALIZADA],
        'CRIADO → DIVERGENCIA'                         => [StatusOrdem::CRIADO,                   StatusOrdem::DIVERGENCIA],
        'TARA_REALIZADA → AGUARDANDO_CARREGAMENTO'     => [StatusOrdem::TARA_REALIZADA,           StatusOrdem::AGUARDANDO_CARREGAMENTO],
        'AGUARDANDO_CARREGAMENTO → EM_CARREGAMENTO'   => [StatusOrdem::AGUARDANDO_CARREGAMENTO,  StatusOrdem::EM_CARREGAMENTO],
        'EM_CARREGAMENTO → CARREGAMENTO_CONCLUIDO'    => [StatusOrdem::EM_CARREGAMENTO,          StatusOrdem::CARREGAMENTO_CONCLUIDO],
        'CARREGAMENTO_CONCLUIDO → AGUARDANDO_PESAGEM' => [StatusOrdem::CARREGAMENTO_CONCLUIDO,   StatusOrdem::AGUARDANDO_PESAGEM_FINAL],
        'AGUARDANDO_PESAGEM → PESAGEM_FINAL'          => [StatusOrdem::AGUARDANDO_PESAGEM_FINAL, StatusOrdem::PESAGEM_FINAL_REALIZADA],
        'PESAGEM_FINAL → VALIDADO'                    => [StatusOrdem::PESAGEM_FINAL_REALIZADA,  StatusOrdem::VALIDADO],
        'VALIDADO → FINALIZADO'                       => [StatusOrdem::VALIDADO,                 StatusOrdem::FINALIZADO],
        'DIVERGENCIA → AGUARDANDO_CARREGAMENTO'       => [StatusOrdem::DIVERGENCIA,              StatusOrdem::AGUARDANDO_CARREGAMENTO],
        'DIVERGENCIA → CANCELADO'                     => [StatusOrdem::DIVERGENCIA,              StatusOrdem::CANCELADO],
    ]);

    it('bloqueia transições inválidas', function (StatusOrdem $de, StatusOrdem $para) {
        expect($de->podeTransicionarPara($para))->toBeFalse();
    })->with([
        'CRIADO → EM_CARREGAMENTO'         => [StatusOrdem::CRIADO,      StatusOrdem::EM_CARREGAMENTO],
        'CRIADO → VALIDADO'                => [StatusOrdem::CRIADO,      StatusOrdem::VALIDADO],
        'FINALIZADO → CRIADO'              => [StatusOrdem::FINALIZADO,  StatusOrdem::CRIADO],
        'CANCELADO → TARA_REALIZADA'       => [StatusOrdem::CANCELADO,   StatusOrdem::TARA_REALIZADA],
        'EM_CARREGAMENTO → VALIDADO'       => [StatusOrdem::EM_CARREGAMENTO, StatusOrdem::VALIDADO],
        'VALIDADO → AGUARDANDO_CARREGAMENTO' => [StatusOrdem::VALIDADO,  StatusOrdem::AGUARDANDO_CARREGAMENTO],
    ]);

    it('CANCELADO e FINALIZADO não estão ativos', function () {
        expect(StatusOrdem::CANCELADO->estaAtivo())->toBeFalse()
            ->and(StatusOrdem::FINALIZADO->estaAtivo())->toBeFalse();
    });

    it('outros status estão ativos', function (StatusOrdem $status) {
        expect($status->estaAtivo())->toBeTrue();
    })->with([
        [StatusOrdem::CRIADO],
        [StatusOrdem::TARA_REALIZADA],
        [StatusOrdem::EM_CARREGAMENTO],
        [StatusOrdem::VALIDADO],
        [StatusOrdem::DIVERGENCIA],
    ]);

    it('estaEmFila retorna true apenas para status de fila', function () {
        expect(StatusOrdem::AGUARDANDO_CARREGAMENTO->estaEmFila())->toBeTrue()
            ->and(StatusOrdem::EM_CARREGAMENTO->estaEmFila())->toBeTrue()
            ->and(StatusOrdem::CRIADO->estaEmFila())->toBeFalse()
            ->and(StatusOrdem::VALIDADO->estaEmFila())->toBeFalse();
    });
});
