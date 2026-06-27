<?php

namespace App\Http\Requests\OrdemCarregamento;

use Illuminate\Foundation\Http\FormRequest;

class IniciarCarregamentoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'operador_id'           => ['required', 'integer', 'exists:users,id'],
            'equipamento_codigo'    => ['nullable', 'string', 'max:20', 'exists:equipamentos,codigo'],
            'ponto_carregamento_id' => ['required', 'integer', 'exists:pontos_carregamento,id'],
            'observacao'            => ['nullable', 'string'],
        ];
    }
}
