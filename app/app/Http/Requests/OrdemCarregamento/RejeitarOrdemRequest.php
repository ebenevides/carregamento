<?php

declare(strict_types=1);

namespace App\Http\Requests\OrdemCarregamento;

use Illuminate\Foundation\Http\FormRequest;

class RejeitarOrdemRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'descricao' => ['required', 'string', 'min:5', 'max:1000'],
        ];
    }

    public function messages(): array
    {
        return [
            'descricao.required' => 'A descrição do motivo é obrigatória.',
            'descricao.min'      => 'A descrição deve ter pelo menos :min caracteres.',
        ];
    }
}
