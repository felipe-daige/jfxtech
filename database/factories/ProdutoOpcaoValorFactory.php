<?php
namespace Database\Factories;

use App\Models\ProdutoOpcaoValor;
use App\Models\ProdutoOpcaoGrupo;
use Illuminate\Database\Eloquent\Factories\Factory;

class ProdutoOpcaoValorFactory extends Factory
{
    protected $model = ProdutoOpcaoValor::class;

    public function definition(): array
    {
        return [
            'grupo_id' => ProdutoOpcaoGrupo::factory(),
            'valor'    => $this->faker->word(),
            'ordem'    => 0,
        ];
    }
}
