<?php

namespace Tests\Unit;

use App\Models\Categoria;
use App\Models\Produto;
use App\Support\ProdutoDescricaoFormatter;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ProdutoDescricaoFormatterTest extends TestCase
{
    #[Test]
    public function it_sanitizes_allowed_tags_and_strips_attributes(): void
    {
        $html = '<p onclick="alert(1)">Texto <strong class="x">forte</strong> <script>alert(1)</script><a href="/">link</a></p>';

        $sanitized = ProdutoDescricaoFormatter::sanitize($html);

        $this->assertSame('<p>Texto <strong>forte</strong> alert(1)link</p>', $sanitized);
    }

    #[Test]
    public function it_formats_plain_product_description_into_structured_html(): void
    {
        $produto = new Produto([
            'nome' => 'Alienware AW2524HF 500Hz',
            'descricao' => 'O monitor IPS gaming mais rápido do mundo com AMD FreeSync Premium. O AW2524HF entrega 500 Hz de taxa de atualização em painel Fast IPS. O tempo de resposta de 0,5 ms minimiza motion blur e ghosting nas velocidades mais altas. A cobertura de 99% do sRGB garante cores vivas e precisas.',
            'specs' => [
                'garantia' => '3 anos',
                'dimensoes' => '555 x 244 mm',
                'conexao' => 'DisplayPort 1.4 e HDMI 2.1',
            ],
        ]);
        $produto->setRelation('categoria', new Categoria(['nome' => 'Monitores']));

        $formatted = ProdutoDescricaoFormatter::formatProduto($produto);

        $this->assertStringContainsString('<p>', $formatted);
        $this->assertStringContainsString('<ul>', $formatted);
        $this->assertStringContainsString('<strong>500 Hz</strong>', $formatted);
        $this->assertStringContainsString('<strong>Principais destaques</strong>', $formatted);
        $this->assertStringContainsString('<strong>Garantia:</strong> 3 anos', $formatted);
    }
}
