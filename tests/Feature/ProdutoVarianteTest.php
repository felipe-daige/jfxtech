<?php
namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Produto;
use App\Models\ProdutoOpcaoGrupo;
use App\Models\ProdutoOpcaoValor;
use App\Models\ProdutoVariante;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ProdutoVarianteTest extends TestCase
{
    use RefreshDatabase;

    private function actingAsAdmin()
    {
        $user = User::factory()->create(['admin' => true]);
        $this->actingAs($user);
        return $this;
    }

    public function test_get_opcoes_returns_grupos_and_variantes(): void
    {
        $produto = Produto::factory()->create();
        $grupo = ProdutoOpcaoGrupo::factory()->create(['produto_id' => $produto->id, 'nome' => 'Cor']);
        ProdutoOpcaoValor::factory()->create(['grupo_id' => $grupo->id, 'valor' => 'Preto']);

        $response = $this->actingAsAdmin()->getJson("/admin/produtos/{$produto->id}/opcoes");

        $response->assertOk()
            ->assertJsonStructure(['grupos', 'variantes']);
    }

    public function test_post_opcao_grupos_creates_groups_and_values(): void
    {
        $produto = Produto::factory()->create();

        $response = $this->actingAsAdmin()->postJson("/admin/produtos/{$produto->id}/opcao-grupos", [
            'grupos' => [
                ['nome' => 'Cor', 'ordem' => 0, 'valores' => [
                    ['valor' => 'Preto', 'ordem' => 0],
                    ['valor' => 'Branco', 'ordem' => 1],
                ]],
            ],
        ]);

        $response->assertOk();
        $this->assertDatabaseHas('produto_opcao_grupos', ['produto_id' => $produto->id, 'nome' => 'Cor']);
        $this->assertDatabaseHas('produto_opcao_valores', ['valor' => 'Preto']);
        $this->assertDatabaseHas('produto_opcao_valores', ['valor' => 'Branco']);
    }

    public function test_post_variantes_gerar_creates_cartesian_combinations(): void
    {
        $produto = Produto::factory()->create();
        $grupoCor = ProdutoOpcaoGrupo::factory()->create(['produto_id' => $produto->id, 'nome' => 'Cor']);
        ProdutoOpcaoValor::factory()->create(['grupo_id' => $grupoCor->id, 'valor' => 'Preto']);
        ProdutoOpcaoValor::factory()->create(['grupo_id' => $grupoCor->id, 'valor' => 'Branco']);
        $grupoTam = ProdutoOpcaoGrupo::factory()->create(['produto_id' => $produto->id, 'nome' => 'Tamanho']);
        ProdutoOpcaoValor::factory()->create(['grupo_id' => $grupoTam->id, 'valor' => 'P']);
        ProdutoOpcaoValor::factory()->create(['grupo_id' => $grupoTam->id, 'valor' => 'G']);

        $response = $this->actingAsAdmin()->postJson("/admin/produtos/{$produto->id}/variantes/gerar");

        $response->assertOk();
        $this->assertDatabaseCount('produto_variantes', 4); // 2x2
    }

    public function test_post_variantes_gerar_does_not_duplicate_existing(): void
    {
        $produto = Produto::factory()->create();
        $grupo = ProdutoOpcaoGrupo::factory()->create(['produto_id' => $produto->id]);
        $valor = ProdutoOpcaoValor::factory()->create(['grupo_id' => $grupo->id]);
        ProdutoVariante::factory()->create([
            'produto_id' => $produto->id,
            'valores' => [$valor->id],
            'preco' => 199.00,
        ]);

        $this->actingAsAdmin()->postJson("/admin/produtos/{$produto->id}/variantes/gerar");

        // Still only 1 variant, price preserved
        $this->assertDatabaseCount('produto_variantes', 1);
        $this->assertDatabaseHas('produto_variantes', ['preco' => 199.00]);
    }

    public function test_post_opcao_grupos_rejects_duplicate_group_name_in_payload(): void
    {
        $produto = Produto::factory()->create();

        $response = $this->actingAsAdmin()->postJson("/admin/produtos/{$produto->id}/opcao-grupos", [
            'grupos' => [
                ['nome' => 'Cor', 'ordem' => 0, 'valores' => [['valor' => 'Preto', 'ordem' => 0]]],
                ['nome' => 'Cor', 'ordem' => 1, 'valores' => [['valor' => 'Azul', 'ordem' => 0]]],
            ],
        ]);

        $response->assertStatus(422);
    }

    public function test_put_variantes_updates_price_and_stock(): void
    {
        $produto = Produto::factory()->create();
        $grupo = ProdutoOpcaoGrupo::factory()->create(['produto_id' => $produto->id]);
        $valor = ProdutoOpcaoValor::factory()->create(['grupo_id' => $grupo->id]);
        $variante = ProdutoVariante::factory()->create([
            'produto_id' => $produto->id,
            'valores' => [$valor->id],
        ]);

        $response = $this->actingAsAdmin()->putJson("/admin/produtos/{$produto->id}/variantes", [
            'estoque_compartilhado' => false,
            'variantes' => [
                ['id' => $variante->id, 'preco' => 149.90, 'estoque' => 5, 'ativo' => true],
            ],
        ]);

        $response->assertOk();
        $this->assertDatabaseHas('produto_variantes', ['id' => $variante->id, 'preco' => 149.90, 'estoque' => 5]);
        $this->assertDatabaseHas('produtos', ['id' => $produto->id, 'estoque_compartilhado' => false]);
    }

    public function test_get_opcoes_returns_descricao_and_specs_raw_values(): void
    {
        $produto = Produto::factory()->create();
        $grupo = ProdutoOpcaoGrupo::factory()->create(['produto_id' => $produto->id, 'nome' => 'Cor']);
        $valor = ProdutoOpcaoValor::factory()->create(['grupo_id' => $grupo->id, 'valor' => 'Azul']);
        ProdutoVariante::factory()->create([
            'produto_id' => $produto->id,
            'valores'    => [$valor->id],
            'descricao'  => '<p>Descrição da variante</p>',
            'specs'      => ['dpi_maximo' => '16000'],
        ]);

        $response = $this->actingAsAdmin()->getJson("/admin/produtos/{$produto->id}/opcoes");

        $response->assertOk();
        $variantes = $response->json('variantes');
        $this->assertNotEmpty($variantes);
        $this->assertArrayHasKey('descricao', $variantes[0]);
        $this->assertArrayHasKey('specs', $variantes[0]);
        $this->assertEquals('<p>Descrição da variante</p>', $variantes[0]['descricao']);
        $this->assertEquals(['dpi_maximo' => '16000'], $variantes[0]['specs']);
    }

    public function test_put_variantes_saves_descricao_and_specs_per_variant(): void
    {
        $produto = Produto::factory()->create();
        $grupo = ProdutoOpcaoGrupo::factory()->create(['produto_id' => $produto->id]);
        $valor = ProdutoOpcaoValor::factory()->create(['grupo_id' => $grupo->id]);
        $variante = ProdutoVariante::factory()->create([
            'produto_id' => $produto->id,
            'valores'    => [$valor->id],
        ]);

        $response = $this->actingAsAdmin()->putJson("/admin/produtos/{$produto->id}/variantes", [
            'variantes' => [
                [
                    'id'        => $variante->id,
                    'descricao' => '<p>Texto da variante</p>',
                    'specs'     => ['peso' => '95g'],
                ],
            ],
        ]);

        $response->assertOk();
        $variante->refresh();
        $this->assertEquals('<p>Texto da variante</p>', $variante->descricao);
        $this->assertEquals(['peso' => '95g'], $variante->specs);
    }

    public function test_put_variantes_preserves_existing_values_when_keys_omitted(): void
    {
        $produto = Produto::factory()->create();
        $grupo = ProdutoOpcaoGrupo::factory()->create(['produto_id' => $produto->id]);
        $valor = ProdutoOpcaoValor::factory()->create(['grupo_id' => $grupo->id]);
        $variante = ProdutoVariante::factory()->create([
            'produto_id' => $produto->id,
            'valores'    => [$valor->id],
            'descricao'  => '<p>Valor original</p>',
            'specs'      => ['peso' => '80g'],
        ]);

        $response = $this->actingAsAdmin()->putJson("/admin/produtos/{$produto->id}/variantes", [
            'variantes' => [
                ['id' => $variante->id, 'preco' => 99.90],
            ],
        ]);

        $response->assertOk();
        $variante->refresh();
        // descricao and specs keys were omitted, so existing values remain unchanged
        $this->assertEquals('<p>Valor original</p>', $variante->descricao);
        $this->assertEquals(['peso' => '80g'], $variante->specs);
    }

    public function test_put_variantes_sanitizes_blank_descricao_to_null(): void
    {
        $produto = Produto::factory()->create();
        $grupo = ProdutoOpcaoGrupo::factory()->create(['produto_id' => $produto->id]);
        $valor = ProdutoOpcaoValor::factory()->create(['grupo_id' => $grupo->id]);
        $variante = ProdutoVariante::factory()->create([
            'produto_id' => $produto->id,
            'valores'    => [$valor->id],
            'descricao'  => '<p>Valor anterior</p>',
        ]);

        // Empty string should be sanitized to null
        $response = $this->actingAsAdmin()->putJson("/admin/produtos/{$produto->id}/variantes", [
            'variantes' => [
                ['id' => $variante->id, 'descricao' => ''],
            ],
        ]);

        $response->assertOk();
        $variante->refresh();
        $this->assertNull($variante->descricao);

        // Whitespace-only HTML (e.g. <p>   </p>) should also be sanitized to null
        $response = $this->actingAsAdmin()->putJson("/admin/produtos/{$produto->id}/variantes", [
            'variantes' => [
                ['id' => $variante->id, 'descricao' => '<p>   </p>'],
            ],
        ]);

        $response->assertOk();
        $variante->refresh();
        $this->assertNull($variante->descricao);
    }
}
