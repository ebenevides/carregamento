<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Domain\Carregamento\Models\OrdemCarregamento;
use App\Domain\Chat\Events\MensagemEnviada;
use App\Domain\Chat\Models\MensagemChat;
use App\Http\Controllers\Controller;
use App\Http\Requests\Chat\EnviarMensagemRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ChatController extends Controller
{
    /**
     * Lista mensagens de uma ordem (paginada, mais antiga → recente).
     */
    public function index(Request $request, OrdemCarregamento $ordemCarregamento): JsonResponse
    {
        if (!$ordemCarregamento->usuarioPodeAcessar($request->user())) {
            return response()->json(['message' => 'Acesso não autorizado a esta ordem.'], 403);
        }

        $mensagens = $ordemCarregamento->mensagensChat()
            ->with('remetente:id,name')
            ->paginate(50);

        return response()->json($mensagens);
    }

    /**
     * Envia mensagem no chat da ordem.
     */
    public function store(EnviarMensagemRequest $request, OrdemCarregamento $ordemCarregamento): JsonResponse
    {
        $user = $request->user();

        if (!$ordemCarregamento->usuarioPodeAcessar($user)) {
            return response()->json(['message' => 'Acesso não autorizado a esta ordem.'], 403);
        }

        if (!$ordemCarregamento->status->estaAtivo()) {
            return response()->json([
                'message' => 'Não é possível enviar mensagens em ordens finalizadas ou canceladas.',
            ], 422);
        }

        $mensagem = MensagemChat::create([
            'ordem_carregamento_id' => $ordemCarregamento->id,
            'remetente_id'          => $user->id,
            'perfil_remetente'      => $user->perfil->value,
            'mensagem'              => $request->input('mensagem'),
        ]);

        MensagemEnviada::dispatch($mensagem);

        return response()->json($mensagem, 201);
    }
}
