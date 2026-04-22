<?php

use App\Models\Produto;
use App\Support\ProdutoDescricaoFormatter;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    private const ARTISAN_PRODUCT_IDS = [154, 155, 159, 160, 161];

    private const ARTISAN_DIMENSIONS = 'L: 420 × 330 × 4 mm / XL: 490 × 420 × 4 mm / XXL: 500 × 490 × 4 mm';

    private const PREVIOUS_DIMENSIONS = '490 × 420 × 4 mm';

    public function up(): void
    {
        $this->updateProdutos(self::ARTISAN_DIMENSIONS);
    }

    public function down(): void
    {
        $this->updateProdutos(self::PREVIOUS_DIMENSIONS);
    }

    private function updateProdutos(string $dimensions): void
    {
        Produto::with('categoria')
            ->whereIn('id', self::ARTISAN_PRODUCT_IDS)
            ->get()
            ->each(function (Produto $produto) use ($dimensions): void {
                $specs = is_array($produto->specs) ? $produto->specs : [];
                $specs['dimensoes'] = $dimensions;

                $produto->specs = $specs;
                $produto->descricao = ProdutoDescricaoFormatter::formatProduto($produto);

                $produto->saveQuietly();
            });
    }
};
