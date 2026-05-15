<?php

namespace Tests\Feature;

use App\Models\Configuracao;
use App\Models\Produto;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class AdminDescontoPixTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->create(['admin' => true]);
    }

    public function test_configuracao_get_returns_default_when_key_missing(): void
    {
        Cache::flush();
        $result = Configuracao::get('chave_inexistente', 99.0);
        $this->assertEquals(99.0, $result);
    }

    public function test_configuracao_set_persists_and_invalidates_cache(): void
    {
        Cache::flush();
        Configuracao::set('desconto_pix_global', '7.50');

        $value = Configuracao::get('desconto_pix_global', 5.0);
        $this->assertEquals('7.50', $value);
        $this->assertDatabaseHas('configuracoes', ['chave' => 'desconto_pix_global', 'valor' => '7.50']);
    }

    public function test_produto_get_desconto_pix_returns_product_override(): void
    {
        Cache::flush();
        Configuracao::set('desconto_pix_global', '5.00');

        $produto = Produto::factory()->create(['desconto_pix' => 10.00]);

        $this->assertEquals(10.0, $produto->getDescontoPix());
    }

    public function test_produto_get_desconto_pix_falls_back_to_global(): void
    {
        Cache::flush();
        Configuracao::set('desconto_pix_global', '8.00');

        $produto = Produto::factory()->create(['desconto_pix' => null]);

        $this->assertEquals(8.0, $produto->getDescontoPix());
    }

    public function test_produto_get_desconto_pix_falls_back_to_hardcoded_default(): void
    {
        Cache::flush();
        // Sem nenhuma configuração no banco
        $produto = Produto::factory()->create(['desconto_pix' => null]);

        $this->assertEquals(5.0, $produto->getDescontoPix());
    }

    public function test_admin_can_update_global_pix_discount(): void
    {
        $this->actingAs($this->admin)
            ->postJson(route('admin.configuracoes.update'), [
                'desconto_pix_global' => 7,
            ])
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('desconto_pix_global', 7.0);

        $this->assertDatabaseHas('configuracoes', ['chave' => 'desconto_pix_global', 'valor' => '7.00']);
    }

    public function test_admin_configuracao_update_validates_range(): void
    {
        $this->actingAs($this->admin)
            ->postJson(route('admin.configuracoes.update'), [
                'desconto_pix_global' => 150,
            ])
            ->assertUnprocessable();
    }

    public function test_guest_cannot_update_global_pix_discount(): void
    {
        $this->postJson(route('admin.configuracoes.update'), [
            'desconto_pix_global' => 7,
        ])
        ->assertStatus(401);
    }

    public function test_editar_produto_saves_desconto_pix(): void
    {
        $categoria = \App\Models\Categoria::factory()->create();
        $produto = Produto::factory()->create(['categoria_id' => $categoria->id]);

        $this->actingAs($this->admin)
            ->post(route('admin.produtos.editar', $produto->id), [
                'nome' => $produto->nome,
                'descricao' => '<p>Descrição válida do produto</p>',
                'preco' => 'R$ 100,00',
                'estoque' => 10,
                'categoria_id' => $categoria->id,
                'desconto_pix' => 8,
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('produtos', [
            'id' => $produto->id,
            'desconto_pix' => 8.00,
        ]);
    }

    public function test_editar_produto_clears_desconto_pix_when_empty(): void
    {
        $categoria = \App\Models\Categoria::factory()->create();
        $produto = Produto::factory()->create([
            'categoria_id' => $categoria->id,
            'desconto_pix' => 10.00,
        ]);

        $this->actingAs($this->admin)
            ->post(route('admin.produtos.editar', $produto->id), [
                'nome' => $produto->nome,
                'descricao' => '<p>Descrição válida do produto</p>',
                'preco' => 'R$ 100,00',
                'estoque' => 10,
                'categoria_id' => $categoria->id,
                'desconto_pix' => '',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('produtos', [
            'id' => $produto->id,
            'desconto_pix' => null,
        ]);
    }
}
