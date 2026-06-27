<?php

namespace App\Http\Requests\ProdutoPilhaPonto;

use Illuminate\Foundation\Http\FormRequest;

class StoreProdutoPilhaPontoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'produto_codigo'       => ['required', 'string', 'max:30'],
            'produto_descricao'    => ['nullable', 'string', 'max:100'],
            'pilha_produto_id'     => ['required', 'integer', 'exists:pilhas_produto,id'],
            'ponto_carregamento_id' => ['required', 'integer', 'exists:pontos_carregamento,id'],
            'padrao'               => ['sometimes', 'boolean'],
            'ativo'                => ['sometimes', 'boolean'],
        ];
    }
}
