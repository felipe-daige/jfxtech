<?php

namespace Tests\Feature;

use App\Models\Categoria;
use App\Models\ItemPedido;
use App\Models\Pedido;
use App\Models\Produto;
use App\Models\User;
use App\Services\FreteRateioService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FreteRateioTest extends TestCase
{
    use RefreshDatabase;

    private function criarItemFake(Pedido $pedido, Produto $produto, int $quantidade = 1): ItemPedido
    {
        return ItemPedido::create([
            'pedido_id'          => $pedido->id,
            'produto_id'         => $produto->id,
            'quantidade'         => $quantidade,
            'preco'              => $produto->preco,
        ]);
    }

    /**
     * Pedido com 1 item: o rateio deve retornar o mesmo custo de frete que
     * o PAC pior caso para o peso individual do produto.
     *
     * Mouse: 0.5kg × 1 unidade → peso total 0.5kg → faixa '0-0.5' → R$23.00
     * Frete rateado por unidade: 1.0 × 23.00 / 1 = R$23.00
     */
    public function test_pedido_com_um_item_frete_igual_ao_individual(): void
    {
        $categoria = Categoria::factory()->create();
        $produto = Produto::factory()->create([
            'peso'        => 0.5,
            'categoria_id' => $categoria->id,
        ]);

        $pedido = Pedido::factory()->create();

        $item = $this->criarItemFake($pedido, $produto, 1);

        // Carrega a relação produto para simular eager load
        $itens = ItemPedido::with('produto')->where('pedido_id', $pedido->id)->get();

        $service = app(FreteRateioService::class);
        $rateio = $service->calcularRateio($itens);

        $this->assertArrayHasKey($item->id, $rateio);

        // Frete PAC outras_regioes para 0.5kg (faixa '0-0.5') = R$23.00
        // Com 1 único item, 100% do frete vai para ele, por unidade = R$23.00
        $this->assertEquals(23.00, $rateio[$item->id]);
    }

    /**
     * Pedido com 2 itens (Mouse 0.5kg e Teclado 1.5kg), 1 unidade cada.
     * Peso total: 2.0kg → faixa '1-2' → frete PAC R$33.00
     * - Mouse:   0.5/2.0 × 33 = R$8.25 por unidade
     * - Teclado: 1.5/2.0 × 33 = R$24.75 por unidade
     */
    public function test_pedido_com_dois_itens_frete_rateado_proporcionalmente(): void
    {
        $categoria = Categoria::factory()->create();

        $mouse = Produto::factory()->create([
            'peso'         => 0.5,
            'categoria_id' => $categoria->id,
        ]);

        $teclado = Produto::factory()->create([
            'peso'         => 1.5,
            'categoria_id' => $categoria->id,
        ]);

        $pedido = Pedido::factory()->create();

        $itemMouse   = $this->criarItemFake($pedido, $mouse, 1);
        $itemTeclado = $this->criarItemFake($pedido, $teclado, 1);

        $itens = ItemPedido::with('produto')->where('pedido_id', $pedido->id)->get();

        $service = app(FreteRateioService::class);
        $rateio  = $service->calcularRateio($itens);

        $this->assertArrayHasKey($itemMouse->id, $rateio);
        $this->assertArrayHasKey($itemTeclado->id, $rateio);

        // Frete total R$33 (PAC outras_regioes, 2.0kg)
        $this->assertEquals(8.25,  $rateio[$itemMouse->id]);
        $this->assertEquals(24.75, $rateio[$itemTeclado->id]);

        // A soma dos fretes rateados deve ser igual ao frete total
        $freteTotal = $rateio[$itemMouse->id] + $rateio[$itemTeclado->id];
        $this->assertEquals(33.00, $freteTotal);
    }

    /**
     * Quando um item não tem peso cadastrado (peso = 0 ou null),
     * ele deve receber frete 0 no rateio.
     */
    public function test_item_sem_peso_recebe_frete_zero(): void
    {
        $categoria = Categoria::factory()->create();

        $produtoComPeso = Produto::factory()->create([
            'peso'         => 1.0,
            'categoria_id' => $categoria->id,
        ]);

        $produtoSemPeso = Produto::factory()->create([
            'peso'         => 0,
            'categoria_id' => $categoria->id,
        ]);

        $pedido = Pedido::factory()->create();

        $itemComPeso   = $this->criarItemFake($pedido, $produtoComPeso, 1);
        $itemSemPeso   = $this->criarItemFake($pedido, $produtoSemPeso, 1);

        $itens = ItemPedido::with('produto')->where('pedido_id', $pedido->id)->get();

        $service = app(FreteRateioService::class);
        $rateio  = $service->calcularRateio($itens);

        // O item sem peso deve receber exatamente R$0.00
        $this->assertArrayHasKey($itemSemPeso->id, $rateio);
        $this->assertEquals(0.0, $rateio[$itemSemPeso->id]);

        // O item com peso deve receber 100% do frete total (1.0kg → faixa '0.5-1' → R$28.00)
        $this->assertArrayHasKey($itemComPeso->id, $rateio);
        $this->assertEquals(28.00, $rateio[$itemComPeso->id]);
    }

    /**
     * Múltiplas unidades do mesmo item devem distribuir o frete
     * corretamente: o peso total do item é peso × quantidade,
     * e o frete por unidade é freteItem / quantidade.
     *
     * Mouse (0.5kg × 2 unid = 1kg) + Teclado (1.5kg × 1 unid = 1.5kg) = 2.5kg
     * Frete PAC outras_regioes 2.5kg → faixa '2-3' → R$44.00
     * - Mouse: 1.0/2.5 × 44 = 17.6 / 2 unid = R$8.80 por unidade
     * - Teclado: 1.5/2.5 × 44 = 26.4 / 1 unid = R$26.40 por unidade
     */
    public function test_multiplas_unidades_frete_rateado_corretamente(): void
    {
        $categoria = Categoria::factory()->create();

        $mouse = Produto::factory()->create([
            'peso'         => 0.5,
            'categoria_id' => $categoria->id,
        ]);

        $teclado = Produto::factory()->create([
            'peso'         => 1.5,
            'categoria_id' => $categoria->id,
        ]);

        $pedido = Pedido::factory()->create();

        $itemMouse   = $this->criarItemFake($pedido, $mouse, 2);
        $itemTeclado = $this->criarItemFake($pedido, $teclado, 1);

        $itens = ItemPedido::with('produto')->where('pedido_id', $pedido->id)->get();

        $service = app(FreteRateioService::class);
        $rateio  = $service->calcularRateio($itens);

        $this->assertArrayHasKey($itemMouse->id, $rateio);
        $this->assertArrayHasKey($itemTeclado->id, $rateio);

        // Mouse: 1.0/2.5 × 44 = 17.6 → 17.6/2 = R$8.80 por unidade
        $this->assertEquals(8.80, $rateio[$itemMouse->id]);

        // Teclado: 1.5/2.5 × 44 = 26.4 → 26.4/1 = R$26.40 por unidade
        $this->assertEquals(26.40, $rateio[$itemTeclado->id]);
    }

    /**
     * Se todos os itens têm peso zero, calcularRateio retorna array vazio.
     */
    public function test_todos_sem_peso_retorna_array_vazio(): void
    {
        $categoria = Categoria::factory()->create();

        $produto = Produto::factory()->create([
            'peso'         => 0,
            'categoria_id' => $categoria->id,
        ]);

        $pedido = Pedido::factory()->create();
        $this->criarItemFake($pedido, $produto, 1);

        $itens = ItemPedido::with('produto')->where('pedido_id', $pedido->id)->get();

        $service = app(FreteRateioService::class);
        $rateio  = $service->calcularRateio($itens);

        $this->assertEmpty($rateio);
    }
}
