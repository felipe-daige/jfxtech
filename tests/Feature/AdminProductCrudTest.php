<?php

namespace Tests\Feature;

use App\Models\Categoria;
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
                'peso' => '61',
                'estoque' => 8,
                'categoria_id' => $categoria->id,
            ]);

        $response->assertRedirect(route('admin.produtos'));

        $this->assertDatabaseHas('produtos', [
            'id' => $produto->id,
            'marca' => 'LOGITECH',
            'descricao_curta' => 'Resumo atualizado',
            'peso' => 61,
            'estoque' => 8,
        ]);
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
