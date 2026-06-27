<?php

namespace Database\Factories;

use App\Domain\Carregamento\Enums\StatusPonto;
use App\Domain\Carregamento\Models\PontoCarregamento;
use Illuminate\Database\Eloquent\Factories\Factory;

class PontoCarregamentoFactory extends Factory
{
    protected $model = PontoCarregamento::class;

    public function definition(): array
    {
        return [
            'codigo'    => $this->faker->unique()->numerify('PONTO###'),
            'descricao' => 'Ponto ' . $this->faker->word(),
            'status'    => StatusPonto::ATIVO,
            'observacao' => null,
        ];
    }

    public function inativo(): static
    {
        return $this->state(['status' => StatusPonto::INATIVO]);
    }
}
