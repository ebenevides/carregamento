<?php

namespace App\Http\Requests\OrdemCarregamento;

use Illuminate\Foundation\Http\FormRequest;

class StoreOrdemCarregamentoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'empresa'               => ['nullable', 'string', 'max:10'],
            'filial'                => ['nullable', 'string', 'max:10'],
            'pedido_numero'         => ['nullable', 'string', 'max:20'],
            'pedido_item'           => ['nullable', 'string', 'max:10'],
            'contrato_codigo'       => ['nullable', 'string', 'max:20'],
            'ticket_guardian'       => ['nullable', 'string', 'max:20', 'unique:ordens_carregamento,ticket_guardian'],
            'cliente_codigo'        => ['nullable', 'string', 'max:20'],
            'cliente_loja'          => ['nullable', 'string', 'max:10'],
            'cliente_nome'          => ['nullable', 'string', 'max:150'],
            'produto_codigo'        => ['required', 'string', 'max:30'],
            'produto_descricao'     => ['nullable', 'string', 'max:100'],
            'quantidade_prevista'   => ['required', 'numeric', 'min:0.001'],
            'unidade'               => ['nullable', 'string', 'max:10'],
            'placa_veiculo'         => ['required', 'string', 'max:10'],
            'placa_carreta'         => ['nullable', 'string', 'max:10'],
            'motorista_nome'        => ['nullable', 'string', 'max:100'],
            'motorista_documento'   => ['nullable', 'string', 'max:20'],
            'transportadora_codigo' => ['nullable', 'string', 'max:20'],
            'transportadora_nome'   => ['nullable', 'string', 'max:100'],
            'tara'                  => ['nullable', 'numeric', 'min:0'],
            'tolerancia_percentual' => ['nullable', 'numeric', 'min:0', 'max:100'],
        ];
    }
}
