<?php

namespace Database\Factories;

use App\Domain\Carregamento\Enums\StatusOrdem;
use App\Domain\Carregamento\Models\OrdemCarregamento;
use App\Domain\Carregamento\Models\PilhaProduto;
use App\Domain\Carregamento\Models\PontoCarregamento;
use Illuminate\Database\Eloquent\Factories\Factory;

class OrdemCarregamentoFactory extends Factory
{
    protected $model = OrdemCarregamento::class;

    public function definition(): array
    {
        return [
            'empresa'               => '01',
            'filial'                => '01',
            'pedido_numero'         => $this->faker->unique()->numerify('######'),
            'pedido_item'           => '01',
            'contrato_codigo'       => null,
            'ticket_guardian'       => $this->faker->unique()->numerify('TK#######'),
            'cliente_codigo'        => $this->faker->numerify('CLI####'),
            'cliente_loja'          => '01',
            'cliente_nome'          => $this->faker->company(),
            'produto_codigo'        => 'BRITA1',
            'produto_descricao'     => 'Brita 1',
            'quantidade_prevista'   => 32.0,
            'unidade'               => 'TN',
            'placa_veiculo'         => strtoupper($this->faker->unique()->bothify('???####')),
            'placa_carreta'         => null,
            'motorista_nome'        => $this->faker->name(),
            'motorista_documento'   => $this->faker->numerify('###########'),
            'transportadora_codigo' => null,
            'transportadora_nome'   => null,
            'tara'                  => null,
            'peso_bruto'            => null,
            'peso_liquido'          => null,
            'tolerancia_percentual' => 5.0,
            'pilha_produto_id'      => PilhaProduto::factory(),
            'ponto_carregamento_id' => PontoCarregamento::factory(),
            'equipamento_id'        => null,
            'operador_id'           => null,
            'status'                => StatusOrdem::CRIADO,
            'iniciado_em'           => null,
            'concluido_em'          => null,
            'pesagem_final_em'      => null,
        ];
    }

    public function comTara(float $tara = 15.0): static
    {
        return $this->state([
            'tara'   => $tara,
            'status' => StatusOrdem::TARA_REALIZADA,
        ]);
    }

    public function comTicket(string $ticket): static
    {
        return $this->state(['ticket_guardian' => $ticket]);
    }

    public function emFila(): static
    {
        return $this->state([
            'tara'   => 15.0,
            'status' => StatusOrdem::AGUARDANDO_CARREGAMENTO,
        ]);
    }

    public function emCarregamento(): static
    {
        return $this->state([
            'tara'        => 15.0,
            'status'      => StatusOrdem::EM_CARREGAMENTO,
            'iniciado_em' => now(),
        ]);
    }

    public function aguardandoPesagem(): static
    {
        return $this->state([
            'tara'          => 15.0,
            'status'        => StatusOrdem::AGUARDANDO_PESAGEM_FINAL,
            'iniciado_em'   => now()->subHour(),
            'concluido_em'  => now(),
        ]);
    }

    public function validado(float $pesoLiquido = 32.0, float $tara = 15.0): static
    {
        return $this->state([
            'tara'             => $tara,
            'peso_bruto'       => $pesoLiquido + $tara,
            'peso_liquido'     => $pesoLiquido,
            'status'           => StatusOrdem::VALIDADO,
            'iniciado_em'      => now()->subHours(2),
            'concluido_em'     => now()->subHour(),
            'pesagem_final_em' => now(),
        ]);
    }
}
