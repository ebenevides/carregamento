<?php

namespace App\Http\Requests\OrdemCarregamento;

use Illuminate\Foundation\Http\FormRequest;

class PesagemFinalRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'peso_bruto'      => ['required', 'numeric', 'min:0.001'],
            'ticket_guardian' => ['nullable', 'string', 'max:20'],
            'origem'          => ['nullable', 'string'],
        ];
    }
}
