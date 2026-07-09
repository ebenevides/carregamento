<?php

use App\Domain\Carregamento\Models\OrdemCarregamento;
use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

Broadcast::channel('ordem.{ordemId}.chat', function ($user, $ordemId) {
    $ordem = OrdemCarregamento::find($ordemId);

    return $ordem?->usuarioPodeAcessar($user) ? $ordem : false;
});
