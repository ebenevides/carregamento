<?php

namespace App\Http\Requests\OrdemCarregamento;

use App\Domain\Carregamento\Enums\TipoDivergencia;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class RegistrarDivergenciaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'tipo'       => ['required', new Enum(TipoDivergencia::class)],
            'descricao'  => ['required', 'string', 'max:1000'],
            'usuario_id' => ['nullable', 'integer', 'exists:users,id'],
            'origem'     => ['nullable', 'string'],
        ];
    }
}
