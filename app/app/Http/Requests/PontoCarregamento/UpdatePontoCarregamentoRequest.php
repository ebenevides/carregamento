<?php

namespace App\Http\Requests\PontoCarregamento;

use App\Domain\Carregamento\Enums\StatusPonto;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class UpdatePontoCarregamentoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $id = $this->route('ponto_carregamento');

        return [
            'codigo'     => ['sometimes', 'string', 'max:20', "unique:pontos_carregamento,codigo,{$id}"],
            'descricao'  => ['sometimes', 'string', 'max:100'],
            'status'     => ['sometimes', new Enum(StatusPonto::class)],
            'observacao' => ['nullable', 'string'],
        ];
    }
}
