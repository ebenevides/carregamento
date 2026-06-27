<?php

namespace App\Http\Requests\PilhaProduto;

use Illuminate\Foundation\Http\FormRequest;

class StorePilhaProdutoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'codigo'             => ['required', 'string', 'max:20', 'unique:pilhas_produto,codigo'],
            'descricao'          => ['required', 'string', 'max:100'],
            'produto_codigo'     => ['nullable', 'string', 'max:30'],
            'produto_descricao'  => ['nullable', 'string', 'max:100'],
            'ativa'              => ['sometimes', 'boolean'],
            'observacao'         => ['nullable', 'string'],
        ];
    }
}
