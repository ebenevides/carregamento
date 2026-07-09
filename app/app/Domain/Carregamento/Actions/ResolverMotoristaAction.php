<?php

declare(strict_types=1);

namespace App\Domain\Carregamento\Actions;

use App\Domain\Carregamento\Enums\PerfilUsuario;
use App\Domain\Carregamento\Models\OrdemCarregamento;
use App\Models\User;
use Illuminate\Support\Facades\Log;

/**
 * Resolve o vínculo motorista↔ordem: busca User com perfil MOTORISTA
 * cujo documento (CPF) bata com motorista_documento da ordem.
 * Não bloqueia se não encontrar — apenas loga.
 */
class ResolverMotoristaAction
{
    public function execute(OrdemCarregamento $ordem): void
    {
        if ($ordem->motorista_documento === null || $ordem->motorista_documento === '') {
            return;
        }

        // Evita re-resolver se já estiver vinculado ao documento certo
        if ($ordem->motorista_user_id !== null) {
            $vinculado = User::find($ordem->motorista_user_id);
            if ($vinculado && $vinculado->documento === $ordem->motorista_documento) {
                return; // já vinculado ao motorista correto
            }
        }

        $motorista = User::where('documento', $ordem->motorista_documento)
            ->where('perfil', PerfilUsuario::MOTORISTA)
            ->first();

        if ($motorista) {
            $ordem->update(['motorista_user_id' => $motorista->id]);

            Log::info('Motorista vinculado à ordem', [
                'ordem_id'  => $ordem->id,
                'user_id'   => $motorista->id,
                'user_name' => $motorista->name,
                'documento' => $ordem->motorista_documento,
            ]);
        } else {
            Log::info('Motorista não encontrado para vínculo automático', [
                'ordem_id'          => $ordem->id,
                'motorista_documento' => $ordem->motorista_documento,
                'motorista_nome'    => $ordem->motorista_nome,
            ]);
        }
    }
}
