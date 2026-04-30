<?php

namespace Tests\Feature;

use App\Models\Categoria;
use App\Models\Fornecedor;
use App\Models\Produto;
use App\Models\ProdutoImagem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AdminProductCrudTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_create_product_with_brand_and_short_description(): void
    {
        $admin = User::factory()->create(['admin' => true]);
        $categoria = Categoria::factory()->create(['nome' => 'Mouses']);

        $response = $this->actingAs($admin)
            ->from(route('admin.produtos'))
            ->post(route('admin.produtos.criar'), [
                'nome' => 'Mouse Gamer Logitech Pro X2 Superstrike Lightspeed - Branco',
                'marca' => 'LOGITECH',
                'descricao_curta' => 'Mouse wireless competitivo com sensor HERO 2, 8K via Lightspeed e 61 gramas.',
                'descricao' => '<p>Texto principal do produto.</p>',
                'preco' => 'R$ 2.000,00',
                'custo_compra' => 'R$ 1.121,49',
                'frete_compra' => 'R$ 78,51',
                'peso' => '61',
                'estoque' => 5,
                'categoria_id' => $categoria->id,
                'specs' => [
                    'sensor' => 'HERO 2',
                    'peso' => '61 gramas',
                ],
            ]);

        $response->assertRedirect(route('admin.produtos'));

        $this->assertDatabaseHas('produtos', [
            'nome' => 'Mouse Gamer Logitech Pro X2 Superstrike Lightspeed - Branco',
            'marca' => 'LOGITECH',
            'descricao_curta' => 'Mouse wireless competitivo com sensor HERO 2, 8K via Lightspeed e 61 gramas.',
            'custo_compra' => 1121.49,
            'frete_compra' => 78.51,
            'peso' => 61,
            'estoque' => 5,
            'categoria_id' => $categoria->id,
        ]);
    }

    public function test_admin_can_fetch_and_update_brand_and_short_description(): void
    {
        $admin = User::factory()->create(['admin' => true]);
        $categoria = Categoria::factory()->create(['nome' => 'Mouses']);
        $produto = Produto::factory()->create([
            'categoria_id' => $categoria->id,
            'marca' => 'Logitech',
            'descricao_curta' => 'Resumo inicial',
            'descricao' => '<p>Descrição inicial.</p>',
        ]);

        $this->actingAs($admin)
            ->getJson(route('admin.produtos.buscar', $produto))
            ->assertOk()
            ->assertJsonFragment([
                'marca' => 'Logitech',
                'descricao_curta' => 'Resumo inicial',
                'frete_compra' => '',
            ]);

        $response = $this->actingAs($admin)
            ->from(route('admin.produtos'))
            ->post(route('admin.produtos.editar', $produto), [
                'nome' => $produto->nome,
                'marca' => 'LOGITECH',
                'descricao_curta' => 'Resumo atualizado',
                'descricao' => '<p>Descrição atualizada.</p>',
                'preco' => 'R$ 199,90',
                'custo_compra' => 'R$ 99,90',
                'frete_compra' => 'R$ 12,10',
                'peso' => '61',
                'estoque' => 8,
                'categoria_id' => $categoria->id,
            ]);

        $response->assertRedirect(route('admin.produtos'));

        $this->assertDatabaseHas('produtos', [
            'id' => $produto->id,
            'marca' => 'LOGITECH',
            'descricao_curta' => 'Resumo atualizado',
            'custo_compra' => 99.90,
            'frete_compra' => 12.10,
            'peso' => 61,
            'estoque' => 8,
        ]);
    }

    public function test_product_effective_cost_includes_purchase_freight_only_when_purchase_cost_exists(): void
    {
        $produto = Produto::factory()->create([
            'preco' => 200.00,
            'custo_compra' => 120.00,
            'frete_compra' => 15.50,
        ]);

        $this->assertSame(135.50, $produto->custo_efetivo);
        $this->assertSame(64.50, $produto->lucro_bruto_unitario);

        $produtoSemCusto = Produto::factory()->create([
            'preco' => 200.00,
            'custo_compra' => null,
            'frete_compra' => 15.50,
        ]);

        $this->assertNull($produtoSemCusto->custo_efetivo);
        $this->assertNull($produtoSemCusto->lucro_bruto_unitario);
    }

    public function test_admin_can_save_supplier_offers_for_a_product(): void
    {
        $admin = User::factory()->create(['admin' => true]);
        $produto = Produto::factory()->create();
        $fornecedorExistente = Fornecedor::create([
            'nome' => 'Fornecedor Antigo',
            'email' => 'old@example.com',
        ]);

        $response = $this->actingAs($admin)
            ->putJson(route('admin.produtos.fornecedores.salvar', $produto), [
                'ofertas' => [
                    [
                        'fornecedor_id' => $fornecedorExistente->id,
                        'fornecedor' => [
                            'nome' => 'Fornecedor Atualizado',
                            'email' => 'compras@example.com',
                            'perfil_url' => 'https://perfil.example.com/jfx',
                        ],
                        'preco_compra' => 120.50,
                        'frete_compra' => 12.30,
                        'moeda' => 'BRL',
                        'quantidade_minima' => 2,
                        'prazo_dias' => 15,
                        'url_produto' => 'https://produto.example.com/mouse',
                        'sku_fornecedor' => 'MOUSE-01',
                        'observacoes' => 'Oferta principal',
                        'cotado_em' => '2026-04-24',
                        'ativo' => true,
                    ],
                    [
                        'fornecedor' => [
                            'nome' => 'Novo Fornecedor',
                            'telefone' => '+55 11 99999-0000',
                        ],
                        'preco_compra' => 130.00,
                        'frete_compra' => null,
                        'moeda' => 'USD',
                    ],
                ],
            ]);

        $response->assertOk()->assertJson(['success' => true]);

        $this->assertDatabaseHas('fornecedores', [
            'id' => $fornecedorExistente->id,
            'nome' => 'Fornecedor Atualizado',
            'email' => 'compras@example.com',
        ]);
        $this->assertDatabaseHas('fornecedores', [
            'nome' => 'Novo Fornecedor',
            'telefone' => '+55 11 99999-0000',
        ]);
        $this->assertDatabaseHas('produto_fornecedor_ofertas', [
            'produto_id' => $produto->id,
            'fornecedor_id' => $fornecedorExistente->id,
            'preco_compra' => 120.50,
            'frete_compra' => 12.30,
            'moeda' => 'BRL',
            'sku_fornecedor' => 'MOUSE-01',
        ]);

        $this->actingAs($admin)
            ->getJson(route('admin.produtos.fornecedores', $produto))
            ->assertOk()
            ->assertJsonPath('ofertas.0.fornecedor.nome', 'Fornecedor Atualizado')
            ->assertJsonCount(2, 'ofertas');
    }

    public function test_admin_can_create_product_with_webp_image(): void
    {
        Storage::fake('public');

        $admin = User::factory()->create(['admin' => true]);
        $categoria = Categoria::factory()->create(['nome' => 'Mouses']);

        $response = $this->actingAs($admin)
            ->from(route('admin.produtos'))
            ->post(route('admin.produtos.criar'), [
                'nome' => 'Mouse Gamer com Imagem WebP',
                'descricao' => '<p>Texto principal do produto.</p>',
                'preco' => 'R$ 499,90',
                'estoque' => 3,
                'categoria_id' => $categoria->id,
                'imagens' => [$this->fakeWebpImage('produto.webp')],
            ]);

        $response->assertRedirect(route('admin.produtos'));

        $produto = Produto::where('nome', 'Mouse Gamer com Imagem WebP')->firstOrFail();
        $imagem = $produto->imagens()->first();

        $this->assertNotNull($imagem);
        $this->assertStringEndsWith('.webp', $imagem->caminho);
        Storage::disk('public')->assertExists($imagem->caminho);
    }

    public function test_admin_can_replace_product_image_with_webp(): void
    {
        Storage::fake('public');

        $admin = User::factory()->create(['admin' => true]);
        $categoria = Categoria::factory()->create(['nome' => 'Mouses']);
        $produto = Produto::factory()->create([
            'categoria_id' => $categoria->id,
            'descricao' => '<p>Descrição inicial.</p>',
        ]);

        Storage::disk('public')->put('produtos/original.jpg', 'original');

        $imagem = ProdutoImagem::create([
            'produto_id' => $produto->id,
            'caminho' => 'produtos/original.jpg',
            'capa' => true,
        ]);

        $response = $this->actingAs($admin)
            ->post(route('admin.produtos.imagens.substituir', $imagem->id), [
                'imagem' => $this->fakeWebpImage('substituida.webp'),
            ]);

        $response
            ->assertOk()
            ->assertJson(['success' => true, 'capa' => true]);

        $imagem->refresh();

        $this->assertStringEndsWith('.webp', $imagem->caminho);
        Storage::disk('public')->assertMissing('produtos/original.jpg');
        Storage::disk('public')->assertExists($imagem->caminho);
    }

    private function fakeWebpImage(string $name): UploadedFile
    {
        return UploadedFile::fake()->createWithContent(
            $name,
            base64_decode('UklGRiIAAABXRUJQVlA4IBYAAAAwAQCdASoBAAEAAUAmJaACdLoB+AADsAD+8ut//NgVzXPv9//S4P0uD9LgAAA=')
        );
    }
}
