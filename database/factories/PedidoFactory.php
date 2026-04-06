<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Pedido>
 */
class PedidoFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'status' => 'pendente',
            'valor_total' => fake()->randomFloat(2, 50, 500),
            'frete_tipo' => fake()->randomElement(['pac', 'sedex']),
            'frete_valor' => fake()->randomFloat(2, 10, 50),
            'codigo_rastreio' => null,
        ];
    }

    public function carrinho(): static
    {
        return $this->state(['status' => 'carrinho']);
    }

    public function enviado(): static
    {
        return $this->state(['status' => 'enviado']);
    }

    public function pago(): static
    {
        return $this->state(['status' => 'pago']);
    }
}
