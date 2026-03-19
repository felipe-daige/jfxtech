<?php
namespace Database\Factories;

use App\Models\ProdutoOpcaoGrupo;
use App\Models\Produto;
use Illuminate\Database\Eloquent\Factories\Factory;

class ProdutoOpcaoGrupoFactory extends Factory
{
    protected $model = ProdutoOpcaoGrupo::class;

    public function definition(): array
    {
        return [
            'produto_id' => Produto::factory(),
            'nome'       => $this->faker->word(),
            'ordem'      => 0,
        ];
    }
}
