<?php

declare(strict_types=1);

namespace App\Http\Requests\Chat;

use Illuminate\Foundation\Http\FormRequest;

class EnviarMensagemRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'mensagem' => ['required', 'string', 'max:1000'],
        ];
    }

    public function messages(): array
    {
        return [
            'mensagem.required' => 'A mensagem é obrigatória.',
            'mensagem.max'      => 'A mensagem deve ter no máximo :max caracteres.',
        ];
    }
}
