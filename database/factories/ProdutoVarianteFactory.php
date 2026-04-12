<?php
namespace Database\Factories;

use App\Models\ProdutoVariante;
use App\Models\Produto;
use Illuminate\Database\Eloquent\Factories\Factory;

class ProdutoVarianteFactory extends Factory
{
    protected $model = ProdutoVariante::class;

    public function definition(): array
    {
        return [
            'produto_id' => Produto::factory(),
            'valores'    => [1],
            'preco'      => null,
            'estoque'    => null,
            'ativo'      => true,
            'descricao'  => null,
            'specs'      => null,
        ];
    }
}
