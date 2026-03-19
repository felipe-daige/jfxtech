<?php
namespace Database\Factories;

use App\Models\Produto;
use App\Models\Categoria;
use Illuminate\Database\Eloquent\Factories\Factory;

class ProdutoFactory extends Factory
{
    protected $model = Produto::class;

    public function definition(): array
    {
        return [
            'nome'                  => $this->faker->words(3, true),
            'descricao'             => $this->faker->paragraph(),
            'preco'                 => $this->faker->randomFloat(2, 10, 500),
            'preco_original'        => null,
            'desconto_percentual'   => null,
            'em_promocao'           => false,
            'destaque'              => false,
            'estoque'               => 10,
            'estoque_compartilhado' => true,
            'ativo'                 => true,
            'categoria_id'          => Categoria::factory(),
            'peso'                  => 0.5,
        ];
    }
}
