<?php

namespace Database\Factories;

use App\Domain\Carregamento\Models\PilhaProduto;
use Illuminate\Database\Eloquent\Factories\Factory;

class PilhaProdutoFactory extends Factory
{
    protected $model = PilhaProduto::class;

    public function definition(): array
    {
        return [
            'codigo'          => $this->faker->unique()->numerify('PILHA###'),
            'descricao'       => 'Pilha ' . $this->faker->word(),
            'produto_codigo'  => 'BRITA1',
            'ativa'           => true,
        ];
    }

    public function inativa(): static
    {
        return $this->state(['ativa' => false]);
    }
}
