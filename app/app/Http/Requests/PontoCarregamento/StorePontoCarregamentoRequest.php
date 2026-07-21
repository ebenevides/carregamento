<?php

namespace App\Http\Requests\PontoCarregamento;

use App\Domain\Carregamento\Enums\StatusPonto;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class StorePontoCarregamentoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'codigo'           => ['required', 'string', 'max:20', 'unique:pontos_carregamento,codigo'],
            'descricao'        => ['required', 'string', 'max:100'],
            'unidade_britagem' => ['nullable', 'string', 'max:10'],
            'status'           => ['sometimes', new Enum(StatusPonto::class)],
            'observacao'       => ['nullable', 'string'],
        ];
    }
}
