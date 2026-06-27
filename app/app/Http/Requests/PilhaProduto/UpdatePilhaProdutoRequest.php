<?php

namespace App\Http\Requests\PilhaProduto;

use Illuminate\Foundation\Http\FormRequest;

class UpdatePilhaProdutoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $id = $this->route('pilha_produto');

        return [
            'codigo'             => ['sometimes', 'string', 'max:20', "unique:pilhas_produto,codigo,{$id}"],
            'descricao'          => ['sometimes', 'string', 'max:100'],
            'produto_codigo'     => ['nullable', 'string', 'max:30'],
            'produto_descricao'  => ['nullable', 'string', 'max:100'],
            'ativa'              => ['sometimes', 'boolean'],
            'observacao'         => ['nullable', 'string'],
        ];
    }
}
