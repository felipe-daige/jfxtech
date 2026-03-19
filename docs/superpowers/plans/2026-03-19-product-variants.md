# Product Variants Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Add a product options/variants system so admins can configure selectable options (e.g. Cor, Tamanho) per product, each combination becoming a distinct cart item with its own optional price.

**Architecture:** Three new DB tables (`produto_opcao_grupos`, `produto_opcao_valores`, `produto_variantes`) + two column additions. Admin gets a new "Variantes" tab in the product modal (tabs built from scratch). Frontend shows option selectors on the product detail page; JS resolves the selected combination and passes `produto_variante_id` to the cart. `CarrinhoController` is updated to use a composite `(produto_id, produto_variante_id)` key throughout.

**Tech Stack:** Laravel 12, PostgreSQL, Blade, Tailwind CSS 4, vanilla JS (no Vite for `public/js/`). Tests run via `docker exec laravel-app php artisan test`. Frontend rebuilt via `npm run build` on host.

**Spec:** `docs/superpowers/specs/2026-03-19-product-variants-design.md`

---

## File Map

| File | Action | Responsibility |
|------|--------|----------------|
| `database/migrations/2026_03_19_000001_create_produto_opcao_grupos_table.php` | Create | Groups migration |
| `database/migrations/2026_03_19_000002_create_produto_opcao_valores_table.php` | Create | Values migration |
| `database/migrations/2026_03_19_000003_create_produto_variantes_table.php` | Create | Variants migration |
| `database/migrations/2026_03_19_000004_add_estoque_compartilhado_to_produtos_table.php` | Create | Add estoque_compartilhado |
| `database/migrations/2026_03_19_000005_add_variante_fields_to_itens_pedido_table.php` | Create | Add produto_variante_id + opcoes_snapshot |
| `app/Models/ProdutoOpcaoGrupo.php` | Create | OpcaoGrupo model |
| `app/Models/ProdutoOpcaoValor.php` | Create | OpcaoValor model |
| `app/Models/ProdutoVariante.php` | Create | Variante model + accessors |
| `app/Models/Produto.php` | Modify | Add relationships + accessor |
| `app/Models/ItemPedido.php` | Modify | Add relationship + fillable |
| `app/Http/Controllers/AdminController.php` | Modify | Add 4 variant endpoints |
| `app/Http/Controllers/CarrinhoController.php` | Modify | Composite key + variant-aware logic |
| `routes/web.php` | Modify | Register 4 new admin routes before {id} wildcard |
| `resources/views/site/produto-detalhes.blade.php` | Modify | Add variant selector block |
| `public/js/produto-detalhes.js` | Modify | Add variant selection JS |
| `public/js/cart.js` | Modify | Display opcoes_snapshot in cart sidebar |
| `public/js/admin.js` | Modify | Add tabs system + variant management UI |
| `resources/views/admin/produtos.blade.php` | Modify | Add variant tab HTML to modal |
| `tests/Feature/ProdutoVarianteTest.php` | Create | Feature tests for admin variant endpoints |
| `tests/Feature/CarrinhoVarianteTest.php` | Create | Feature tests for cart with variants |

---

## Task 1: Database Migrations

**Files:**
- Create: `database/migrations/2026_03_19_000001_create_produto_opcao_grupos_table.php`
- Create: `database/migrations/2026_03_19_000002_create_produto_opcao_valores_table.php`
- Create: `database/migrations/2026_03_19_000003_create_produto_variantes_table.php`
- Create: `database/migrations/2026_03_19_000004_add_estoque_compartilhado_to_produtos_table.php`
- Create: `database/migrations/2026_03_19_000005_add_variante_fields_to_itens_pedido_table.php`

- [ ] **Step 1: Create groups migration**

```php
// database/migrations/2026_03_19_000001_create_produto_opcao_grupos_table.php
<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('produto_opcao_grupos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('produto_id')->constrained('produtos')->onDelete('cascade');
            $table->string('nome');
            $table->integer('ordem')->default(0);
            $table->timestamps();
            $table->unique(['produto_id', 'nome']);
        });
    }
    public function down(): void {
        Schema::dropIfExists('produto_opcao_grupos');
    }
};
```

- [ ] **Step 2: Create values migration**

```php
// database/migrations/2026_03_19_000002_create_produto_opcao_valores_table.php
<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('produto_opcao_valores', function (Blueprint $table) {
            $table->id();
            $table->foreignId('grupo_id')->constrained('produto_opcao_grupos')->onDelete('cascade');
            $table->string('valor');
            $table->integer('ordem')->default(0);
            $table->timestamps();
        });
    }
    public function down(): void {
        Schema::dropIfExists('produto_opcao_valores');
    }
};
```

- [ ] **Step 3: Create variants migration**

```php
// database/migrations/2026_03_19_000003_create_produto_variantes_table.php
<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('produto_variantes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('produto_id')->constrained('produtos')->onDelete('cascade');
            $table->json('valores'); // sorted array of produto_opcao_valor_id
            $table->decimal('preco', 10, 2)->nullable();
            $table->integer('estoque')->nullable();
            $table->boolean('ativo')->default(true);
            $table->timestamps();
        });
    }
    public function down(): void {
        Schema::dropIfExists('produto_variantes');
    }
};
```

- [ ] **Step 4: Add estoque_compartilhado to produtos**

```php
// database/migrations/2026_03_19_000004_add_estoque_compartilhado_to_produtos_table.php
<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::table('produtos', function (Blueprint $table) {
            $table->boolean('estoque_compartilhado')->default(true)->after('estoque');
        });
    }
    public function down(): void {
        Schema::table('produtos', function (Blueprint $table) {
            $table->dropColumn('estoque_compartilhado');
        });
    }
};
```

- [ ] **Step 5: Add variante fields to itens_pedido**

```php
// database/migrations/2026_03_19_000005_add_variante_fields_to_itens_pedido_table.php
<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::table('itens_pedido', function (Blueprint $table) {
            $table->foreignId('produto_variante_id')
                  ->nullable()
                  ->after('produto_id')
                  ->constrained('produto_variantes')
                  ->nullOnDelete();
            $table->json('opcoes_snapshot')->nullable()->after('produto_variante_id');
        });
    }
    public function down(): void {
        Schema::table('itens_pedido', function (Blueprint $table) {
            $table->dropForeign(['produto_variante_id']);
            $table->dropColumn(['produto_variante_id', 'opcoes_snapshot']);
        });
    }
};
```

- [ ] **Step 6: Run migrations**

```bash
docker exec laravel-app php artisan migrate --force
```
Expected: 5 new migrations applied, no errors.

- [ ] **Step 7: Commit**

```bash
git add database/migrations/2026_03_19_*
git commit -m "feat: add product variants database migrations"
```

---

## Task 2: Models

**Files:**
- Create: `app/Models/ProdutoOpcaoGrupo.php`
- Create: `app/Models/ProdutoOpcaoValor.php`
- Create: `app/Models/ProdutoVariante.php`
- Modify: `app/Models/Produto.php`
- Modify: `app/Models/ItemPedido.php`

- [ ] **Step 1: Write failing model unit tests**

```php
// tests/Unit/ProdutoVarianteTest.php
<?php
namespace Tests\Unit;

use Tests\TestCase;
use App\Models\ProdutoVariante;
use App\Models\Produto;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ProdutoVarianteTest extends TestCase
{
    use RefreshDatabase;

    public function test_preco_efetivo_returns_own_preco_when_set(): void
    {
        $produto = Produto::factory()->create(['preco' => 100.00, 'em_promocao' => false]);
        $variante = ProdutoVariante::factory()->create([
            'produto_id' => $produto->id,
            'preco' => 149.90,
            'valores' => [1],
        ]);

        $this->assertEquals(149.90, $variante->preco_efetivo);
    }

    public function test_preco_efetivo_inherits_produto_preco_com_desconto_when_null(): void
    {
        $produto = Produto::factory()->create([
            'preco' => 80.00,
            'preco_original' => 100.00,
            'em_promocao' => true,
            'desconto_percentual' => 20.00,
        ]);
        $variante = ProdutoVariante::factory()->create([
            'produto_id' => $produto->id,
            'preco' => null,
            'valores' => [1],
        ]);
        $variante->setRelation('produto', $produto);

        $this->assertEquals(round($produto->preco_com_desconto, 2), $variante->preco_efetivo);
    }

    public function test_estoque_efetivo_returns_produto_estoque_when_compartilhado(): void
    {
        $produto = Produto::factory()->create(['estoque' => 50, 'estoque_compartilhado' => true]);
        $variante = ProdutoVariante::factory()->create([
            'produto_id' => $produto->id,
            'estoque' => null,
            'valores' => [1],
        ]);
        $variante->setRelation('produto', $produto);

        $this->assertEquals(50, $variante->estoque_efetivo);
    }

    public function test_estoque_efetivo_returns_zero_when_not_compartilhado_and_estoque_null(): void
    {
        $produto = Produto::factory()->create(['estoque' => 50, 'estoque_compartilhado' => false]);
        $variante = ProdutoVariante::factory()->create([
            'produto_id' => $produto->id,
            'estoque' => null,
            'valores' => [1],
        ]);
        $variante->setRelation('produto', $produto);

        $this->assertEquals(0, $variante->estoque_efetivo);
    }

    public function test_estoque_efetivo_returns_own_estoque_when_not_compartilhado(): void
    {
        $produto = Produto::factory()->create(['estoque' => 50, 'estoque_compartilhado' => false]);
        $variante = ProdutoVariante::factory()->create([
            'produto_id' => $produto->id,
            'estoque' => 5,
            'valores' => [1],
        ]);
        $variante->setRelation('produto', $produto);

        $this->assertEquals(5, $variante->estoque_efetivo);
    }
}
```

- [ ] **Step 2: Run tests to verify they fail**

```bash
docker exec laravel-app php artisan test tests/Unit/ProdutoVarianteTest.php
```
Expected: FAIL — class `ProdutoVariante` not found (or factories missing).

- [ ] **Step 3: Create ProdutoOpcaoGrupo model**

```php
// app/Models/ProdutoOpcaoGrupo.php
<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProdutoOpcaoGrupo extends Model
{
    protected $table = 'produto_opcao_grupos';

    protected $fillable = ['produto_id', 'nome', 'ordem'];

    public function produto(): BelongsTo
    {
        return $this->belongsTo(Produto::class);
    }

    public function valores(): HasMany
    {
        return $this->hasMany(ProdutoOpcaoValor::class, 'grupo_id')->orderBy('ordem')->orderBy('id');
    }
}
```

- [ ] **Step 4: Create ProdutoOpcaoValor model**

```php
// app/Models/ProdutoOpcaoValor.php
<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProdutoOpcaoValor extends Model
{
    protected $table = 'produto_opcao_valores';

    protected $fillable = ['grupo_id', 'valor', 'ordem'];

    public function grupo(): BelongsTo
    {
        return $this->belongsTo(ProdutoOpcaoGrupo::class, 'grupo_id');
    }
}
```

- [ ] **Step 5: Create ProdutoVariante model**

```php
// app/Models/ProdutoVariante.php
<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProdutoVariante extends Model
{
    protected $table = 'produto_variantes';

    protected $fillable = ['produto_id', 'valores', 'preco', 'estoque', 'ativo'];

    protected $casts = [
        'valores' => 'array',
        'preco'   => 'decimal:2',
        'ativo'   => 'boolean',
    ];

    public function produto(): BelongsTo
    {
        return $this->belongsTo(Produto::class);
    }

    /**
     * Preço efetivo: próprio ou preco_com_desconto do produto pai (arredondado).
     */
    public function getPrecEfetivoAttribute(): float
    {
        if ($this->preco !== null) {
            return (float) $this->preco;
        }
        return round($this->produto->preco_com_desconto, 2);
    }

    /**
     * Estoque efetivo: próprio se não compartilhado; produto pai se compartilhado.
     * Retorna 0 se estoque_compartilhado=false e estoque da variante é null.
     */
    public function getEstoqueEfetivoAttribute(): int
    {
        if ($this->produto->estoque_compartilhado) {
            return $this->produto->estoque;
        }
        return $this->estoque ?? 0;
    }
}
```

Note the accessor is `getPrecEfetivoAttribute` (not `getPrecoEfetivoAttribute`) — fix below.

- [ ] **Step 6: Fix accessor name typo — use `getPrecoEfetivoAttribute`**

Edit `app/Models/ProdutoVariante.php`: rename `getPrecEfetivoAttribute` to `getPrecoEfetivoAttribute`.

- [ ] **Step 7: Create model factories**

```php
// database/factories/ProdutoVarianteFactory.php
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
        ];
    }
}
```

Also add `HasFactory` to `ProdutoVariante` model and make sure `Produto` factory exists (check `database/factories/ProdutoFactory.php`). If it doesn't exist, create a minimal one:

```php
// database/factories/ProdutoFactory.php
<?php
namespace Database\Factories;

use App\Models\Produto;
use App\Models\Categoria;
use Illuminate\Database\Eloquent\Factories\Factory;

class ProdutoFactory extends Factory
{
    protected $model = Produto::class;

    public function definition(): array
    {
        return [
            'nome'                  => $this->faker->words(3, true),
            'descricao'             => $this->faker->paragraph(),
            'preco'                 => $this->faker->randomFloat(2, 10, 500),
            'preco_original'        => null,
            'desconto_percentual'   => null,
            'em_promocao'           => false,
            'destaque'              => false,
            'estoque'               => 10,
            'estoque_compartilhado' => true,
            'ativo'                 => true,
            'categoria_id'          => Categoria::factory(),
            'peso'                  => 0.5,
        ];
    }
}
```

```php
// database/factories/CategoriaFactory.php
<?php
namespace Database\Factories;

use App\Models\Categoria;
use Illuminate\Database\Eloquent\Factories\Factory;

class CategoriaFactory extends Factory
{
    protected $model = Categoria::class;

    public function definition(): array
    {
        return [
            'nome' => $this->faker->unique()->word(),
        ];
    }
}
```

Add `use HasFactory;` to `Categoria` and `Produto` models if not already present.

- [ ] **Step 8: Update Produto model — add relationships, fillable, accessor**

In `app/Models/Produto.php`:

1. Add to `$fillable`: `'estoque_compartilhado'`
2. Add to `$casts`: `'estoque_compartilhado' => 'boolean'`
3. Add relationships:

```php
public function opcaoGrupos(): HasMany
{
    return $this->hasMany(ProdutoOpcaoGrupo::class)->orderBy('ordem')->orderBy('id');
}

public function variantes(): HasMany
{
    return $this->hasMany(ProdutoVariante::class);
}

public function variantesAtivas(): HasMany
{
    return $this->hasMany(ProdutoVariante::class)->where('ativo', true);
}
```

4. Add accessor:

```php
public function getTem_variantesAttribute(): bool
{
    return $this->opcaoGrupos()->exists();
}
```

Note: Laravel accessor for `tem_variantes` should be `getTemVariantesAttribute`. Use this name.

- [ ] **Step 9: Update ItemPedido model**

In `app/Models/ItemPedido.php`:

1. Add to `$fillable`: `'produto_variante_id'`, `'opcoes_snapshot'`
2. Add to `$casts`: `'opcoes_snapshot' => 'array'`
3. Add relationship:

```php
public function produtoVariante(): BelongsTo
{
    return $this->belongsTo(ProdutoVariante::class, 'produto_variante_id');
}
```

- [ ] **Step 10: Run unit tests**

```bash
docker exec laravel-app php artisan test tests/Unit/ProdutoVarianteTest.php
```
Expected: 5 tests PASS.

- [ ] **Step 11: Commit**

```bash
git add app/Models/ database/factories/ tests/Unit/ProdutoVarianteTest.php
git commit -m "feat: add ProdutoOpcaoGrupo, ProdutoOpcaoValor, ProdutoVariante models and update Produto/ItemPedido"
```

---

## Task 3: Admin Endpoints

**Files:**
- Modify: `app/Http/Controllers/AdminController.php`
- Modify: `routes/web.php`
- Create: `tests/Feature/ProdutoVarianteTest.php`

- [ ] **Step 1: Write failing feature tests**

```php
// tests/Feature/ProdutoVarianteTest.php
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
        $user = User::factory()->create(['is_admin' => true]);
        return $this->actingAs($user);
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

    public function test_post_opcao_grupos_rejects_duplicate_group_name(): void
    {
        $produto = Produto::factory()->create();
        ProdutoOpcaoGrupo::factory()->create(['produto_id' => $produto->id, 'nome' => 'Cor']);

        $response = $this->actingAsAdmin()->postJson("/admin/produtos/{$produto->id}/opcao-grupos", [
            'grupos' => [
                ['nome' => 'Cor', 'ordem' => 0, 'valores' => [['valor' => 'Azul', 'ordem' => 0]]],
            ],
        ]);

        $response->assertStatus(422);
    }

    public function test_post_variantes_gerar_creates_cartesian_combinations(): void
    {
        $produto = Produto::factory()->create();
        $grupoCor = ProdutoOpcaoGrupo::factory()->create(['produto_id' => $produto->id, 'nome' => 'Cor']);
        $preto = ProdutoOpcaoValor::factory()->create(['grupo_id' => $grupoCor->id, 'valor' => 'Preto']);
        $branco = ProdutoOpcaoValor::factory()->create(['grupo_id' => $grupoCor->id, 'valor' => 'Branco']);
        $grupoTam = ProdutoOpcaoGrupo::factory()->create(['produto_id' => $produto->id, 'nome' => 'Tamanho']);
        $p = ProdutoOpcaoValor::factory()->create(['grupo_id' => $grupoTam->id, 'valor' => 'P']);
        $g = ProdutoOpcaoValor::factory()->create(['grupo_id' => $grupoTam->id, 'valor' => 'G']);

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
}
```

- [ ] **Step 2: Add factories for ProdutoOpcaoGrupo and ProdutoOpcaoValor**

```php
// database/factories/ProdutoOpcaoGrupoFactory.php
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
            'nome' => $this->faker->word(),
            'ordem' => 0,
        ];
    }
}
```

```php
// database/factories/ProdutoOpcaoValorFactory.php
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
            'valor' => $this->faker->word(),
            'ordem' => 0,
        ];
    }
}
```

Add `use HasFactory;` + `use Illuminate\Database\Eloquent\Factories\HasFactory;` to `ProdutoOpcaoGrupo` and `ProdutoOpcaoValor` models.

- [ ] **Step 3: Run tests to confirm they fail**

```bash
docker exec laravel-app php artisan test tests/Feature/ProdutoVarianteTest.php
```
Expected: FAIL — routes not found (404s).

- [ ] **Step 4: Add routes to web.php**

In `routes/web.php`, inside the `admin` prefix group, add these four routes **immediately before** the line:
```php
Route::get('/produtos/{id}', [AdminController::class, 'buscarProduto'])->name('produtos.buscar');
```

Insert:
```php
Route::get('/produtos/{id}/opcoes', [AdminController::class, 'buscarOpcoesProduto'])->name('produtos.opcoes');
Route::post('/produtos/{id}/opcao-grupos', [AdminController::class, 'salvarOpcaoGrupos'])->name('produtos.opcao-grupos');
Route::post('/produtos/{id}/variantes/gerar', [AdminController::class, 'gerarVariantes'])->name('produtos.variantes.gerar');
Route::put('/produtos/{id}/variantes', [AdminController::class, 'salvarVariantes'])->name('produtos.variantes.salvar');
```

- [ ] **Step 5: Implement `buscarOpcoesProduto` in AdminController**

```php
public function buscarOpcoesProduto($id)
{
    if (!Auth::check()) {
        return response()->json(['error' => 'Não autorizado'], 401);
    }

    $produto = Produto::with(['opcaoGrupos.valores', 'variantes'])->findOrFail($id);

    // Build a valor map for label resolution
    $valorMap = [];
    foreach ($produto->opcaoGrupos as $grupo) {
        foreach ($grupo->valores as $valor) {
            $valorMap[$valor->id] = ['grupo' => $grupo->nome, 'valor' => $valor->valor];
        }
    }

    $variantes = $produto->variantes->map(function ($v) use ($valorMap) {
        $label = collect($v->valores)->map(fn($vid) => $valorMap[$vid]['valor'] ?? '?')->join(' / ');
        return [
            'id'      => $v->id,
            'valores' => $v->valores,
            'preco'   => $v->preco,
            'estoque' => $v->estoque,
            'ativo'   => $v->ativo,
            'label'   => $label,
        ];
    });

    return response()->json([
        'grupos'   => $produto->opcaoGrupos->map(fn($g) => [
            'id'     => $g->id,
            'nome'   => $g->nome,
            'ordem'  => $g->ordem,
            'valores' => $g->valores->map(fn($v) => [
                'id'    => $v->id,
                'valor' => $v->valor,
                'ordem' => $v->ordem,
            ]),
        ]),
        'variantes'             => $variantes,
        'estoque_compartilhado' => $produto->estoque_compartilhado,
    ]);
}
```

- [ ] **Step 6: Implement `salvarOpcaoGrupos` in AdminController**

```php
public function salvarOpcaoGrupos(Request $request, $id)
{
    if (!Auth::check()) {
        return response()->json(['error' => 'Não autorizado'], 401);
    }

    $produto = Produto::findOrFail($id);

    $request->validate([
        'grupos'              => 'required|array',
        'grupos.*.nome'       => 'required|string|max:50',
        'grupos.*.ordem'      => 'nullable|integer',
        'grupos.*.valores'    => 'nullable|array',
        'grupos.*.valores.*.valor' => 'required|string|max:100',
        'grupos.*.valores.*.ordem' => 'nullable|integer',
    ]);

    // Validate unique names per produto
    $nomes = collect($request->grupos)->pluck('nome');
    if ($nomes->unique()->count() !== $nomes->count()) {
        return response()->json(['error' => 'Nomes de grupo devem ser únicos por produto.'], 422);
    }

    \DB::transaction(function () use ($request, $produto) {
        // Delete groups not in this request (by name)
        $nomesNovos = collect($request->grupos)->pluck('nome')->all();
        $produto->opcaoGrupos()->whereNotIn('nome', $nomesNovos)->delete();

        foreach ($request->grupos as $idx => $grupoData) {
            $grupo = $produto->opcaoGrupos()->updateOrCreate(
                ['nome' => $grupoData['nome']],
                ['ordem' => $grupoData['ordem'] ?? $idx]
            );

            if (!empty($grupoData['valores'])) {
                $valoresNovos = collect($grupoData['valores'])->pluck('valor')->all();
                $grupo->valores()->whereNotIn('valor', $valoresNovos)->delete();

                foreach ($grupoData['valores'] as $vIdx => $valorData) {
                    $grupo->valores()->updateOrCreate(
                        ['valor' => $valorData['valor']],
                        ['ordem' => $valorData['ordem'] ?? $vIdx]
                    );
                }
            }
        }
    });

    return response()->json(['success' => true]);
}
```

- [ ] **Step 7: Implement `gerarVariantes` in AdminController**

```php
public function gerarVariantes($id)
{
    if (!Auth::check()) {
        return response()->json(['error' => 'Não autorizado'], 401);
    }

    $produto = Produto::with('opcaoGrupos.valores')->findOrFail($id);
    $grupos = $produto->opcaoGrupos;

    if ($grupos->isEmpty()) {
        return response()->json(['success' => true, 'geradas' => 0]);
    }

    // Build cartesian product of valor_id arrays
    $sets = $grupos->map(fn($g) => $g->valores->pluck('id')->all())->all();
    $combinacoes = $this->cartesianProduct($sets);

    $criadas = 0;
    \DB::transaction(function () use ($produto, $combinacoes, &$criadas) {
        foreach ($combinacoes as $combo) {
            sort($combo); // always sorted for consistent comparison
            $valoresJson = json_encode($combo);

            // Check if variant already exists (compare sorted JSON)
            $existe = $produto->variantes()
                ->whereRaw("valores::text = ?", [$valoresJson])
                ->exists();

            if (!$existe) {
                ProdutoVariante::create([
                    'produto_id' => $produto->id,
                    'valores'    => $combo,
                    'preco'      => null,
                    'estoque'    => null,
                    'ativo'      => true,
                ]);
                $criadas++;
            }
        }
    });

    return response()->json(['success' => true, 'geradas' => $criadas]);
}

private function cartesianProduct(array $sets): array
{
    $result = [[]];
    foreach ($sets as $set) {
        $newResult = [];
        foreach ($result as $existing) {
            foreach ($set as $item) {
                $newResult[] = array_merge($existing, [$item]);
            }
        }
        $result = $newResult;
    }
    return $result;
}
```

Also add `use App\Models\ProdutoVariante;` and `use App\Models\ProdutoOpcaoGrupo;` at the top of `AdminController`.

- [ ] **Step 8: Implement `salvarVariantes` in AdminController**

```php
public function salvarVariantes(Request $request, $id)
{
    if (!Auth::check()) {
        return response()->json(['error' => 'Não autorizado'], 401);
    }

    $produto = Produto::findOrFail($id);

    $request->validate([
        'estoque_compartilhado'    => 'nullable|boolean',
        'variantes'                => 'nullable|array',
        'variantes.*.id'           => 'required|exists:produto_variantes,id',
        'variantes.*.preco'        => 'nullable|numeric|min:0',
        'variantes.*.estoque'      => 'nullable|integer|min:0',
        'variantes.*.ativo'        => 'nullable|boolean',
    ]);

    \DB::transaction(function () use ($request, $produto) {
        if ($request->has('estoque_compartilhado')) {
            $produto->update(['estoque_compartilhado' => $request->boolean('estoque_compartilhado')]);
        }

        foreach ($request->input('variantes', []) as $varData) {
            $variante = ProdutoVariante::where('id', $varData['id'])
                ->where('produto_id', $produto->id)
                ->firstOrFail();

            $update = [];
            if (array_key_exists('preco', $varData))   $update['preco']   = $varData['preco'];
            if (array_key_exists('estoque', $varData)) $update['estoque'] = $varData['estoque'];
            if (array_key_exists('ativo', $varData))   $update['ativo']   = $varData['ativo'];

            if (!empty($update)) $variante->update($update);
        }
    });

    return response()->json(['success' => true]);
}
```

- [ ] **Step 9: Run feature tests**

```bash
docker exec laravel-app php artisan test tests/Feature/ProdutoVarianteTest.php
```
Expected: all tests PASS.

- [ ] **Step 10: Commit**

```bash
git add app/Http/Controllers/AdminController.php routes/web.php \
        tests/Feature/ProdutoVarianteTest.php database/factories/
git commit -m "feat: add admin variant endpoints (GET opcoes, POST opcao-grupos, POST gerar, PUT variantes)"
```

---

## Task 4: Cart Updates

**Files:**
- Modify: `app/Http/Controllers/CarrinhoController.php`
- Create: `tests/Feature/CarrinhoVarianteTest.php`

- [ ] **Step 1: Write failing cart variant tests**

```php
// tests/Feature/CarrinhoVarianteTest.php
<?php
namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Produto;
use App\Models\ProdutoOpcaoGrupo;
use App\Models\ProdutoOpcaoValor;
use App\Models\ProdutoVariante;
use App\Models\Pedido;
use App\Models\ItemPedido;
use Illuminate\Foundation\Testing\RefreshDatabase;

class CarrinhoVarianteTest extends TestCase
{
    use RefreshDatabase;

    private function loginUser(): User
    {
        $user = User::factory()->create();
        $this->actingAs($user);
        return $user;
    }

    private function makeVariante(array $produtoAttrs = [], array $varianteAttrs = []): ProdutoVariante
    {
        $produto = Produto::factory()->create(array_merge(['estoque' => 10, 'estoque_compartilhado' => true], $produtoAttrs));
        $grupo = ProdutoOpcaoGrupo::factory()->create(['produto_id' => $produto->id, 'nome' => 'Cor']);
        $valor = ProdutoOpcaoValor::factory()->create(['grupo_id' => $grupo->id, 'valor' => 'Preto']);
        return ProdutoVariante::factory()->create(array_merge([
            'produto_id' => $produto->id,
            'valores'    => [$valor->id],
        ], $varianteAttrs));
    }

    public function test_add_to_cart_with_variante_creates_item_with_variante_id(): void
    {
        $this->loginUser();
        $variante = $this->makeVariante([], ['preco' => 149.90]);

        $response = $this->postJson('/carrinho/adicionar', [
            'produto_id'         => $variante->produto_id,
            'produto_variante_id'=> $variante->id,
            'quantidade'         => 1,
        ]);

        $response->assertOk()->assertJson(['success' => true]);
        $this->assertDatabaseHas('itens_pedido', [
            'produto_id'          => $variante->produto_id,
            'produto_variante_id' => $variante->id,
            'preco'               => 149.90,
        ]);
    }

    public function test_same_produto_different_variante_creates_separate_cart_items(): void
    {
        $this->loginUser();
        $produto = Produto::factory()->create(['estoque' => 10, 'estoque_compartilhado' => true]);
        $grupo   = ProdutoOpcaoGrupo::factory()->create(['produto_id' => $produto->id]);
        $v1Val   = ProdutoOpcaoValor::factory()->create(['grupo_id' => $grupo->id, 'valor' => 'Preto']);
        $v2Val   = ProdutoOpcaoValor::factory()->create(['grupo_id' => $grupo->id, 'valor' => 'Branco']);
        $var1    = ProdutoVariante::factory()->create(['produto_id' => $produto->id, 'valores' => [$v1Val->id]]);
        $var2    = ProdutoVariante::factory()->create(['produto_id' => $produto->id, 'valores' => [$v2Val->id]]);

        $this->postJson('/carrinho/adicionar', ['produto_id' => $produto->id, 'produto_variante_id' => $var1->id, 'quantidade' => 1]);
        $this->postJson('/carrinho/adicionar', ['produto_id' => $produto->id, 'produto_variante_id' => $var2->id, 'quantidade' => 1]);

        $carrinho = Pedido::where('user_id', auth()->id())->where('status', 'carrinho')->first();
        $this->assertCount(2, $carrinho->itens);
    }

    public function test_adding_same_variante_twice_increments_quantity_not_new_row(): void
    {
        $this->loginUser();
        $variante = $this->makeVariante([], ['preco' => 99.00]);

        $this->postJson('/carrinho/adicionar', ['produto_id' => $variante->produto_id, 'produto_variante_id' => $variante->id, 'quantidade' => 1]);
        $this->postJson('/carrinho/adicionar', ['produto_id' => $variante->produto_id, 'produto_variante_id' => $variante->id, 'quantidade' => 1]);

        $carrinho = Pedido::where('user_id', auth()->id())->where('status', 'carrinho')->first();
        $this->assertCount(1, $carrinho->itens);
        $this->assertEquals(2, $carrinho->itens->first()->quantidade);
        // preco must NOT be refreshed on increment
        $this->assertEquals(99.00, $carrinho->itens->first()->preco);
    }

    public function test_rejects_variante_belonging_to_different_produto(): void
    {
        $this->loginUser();
        $produto1  = Produto::factory()->create(['estoque' => 10]);
        $produto2  = Produto::factory()->create(['estoque' => 10]);
        $grupo     = ProdutoOpcaoGrupo::factory()->create(['produto_id' => $produto2->id]);
        $valor     = ProdutoOpcaoValor::factory()->create(['grupo_id' => $grupo->id]);
        $variantep2 = ProdutoVariante::factory()->create(['produto_id' => $produto2->id, 'valores' => [$valor->id]]);

        $response = $this->postJson('/carrinho/adicionar', [
            'produto_id'          => $produto1->id, // wrong produto
            'produto_variante_id' => $variantep2->id,
            'quantidade'          => 1,
        ]);

        $response->assertStatus(422);
    }

    public function test_opcoes_snapshot_is_built_server_side(): void
    {
        $this->loginUser();
        $variante = $this->makeVariante();

        $this->postJson('/carrinho/adicionar', [
            'produto_id'          => $variante->produto_id,
            'produto_variante_id' => $variante->id,
            'quantidade'          => 1,
        ]);

        $item = ItemPedido::where('produto_variante_id', $variante->id)->first();
        $this->assertNotNull($item->opcoes_snapshot);
        $this->assertIsArray($item->opcoes_snapshot);
        $this->assertArrayHasKey('Cor', $item->opcoes_snapshot);
    }

    public function test_itens_response_includes_opcoes_snapshot(): void
    {
        $this->loginUser();
        $variante = $this->makeVariante();

        $this->postJson('/carrinho/adicionar', [
            'produto_id'          => $variante->produto_id,
            'produto_variante_id' => $variante->id,
            'quantidade'          => 1,
        ]);

        $response = $this->getJson('/carrinho/itens');
        $response->assertOk();
        $itens = $response->json('carrinho.itens');
        $this->assertArrayHasKey('opcoes_snapshot', $itens[0]);
    }

    public function test_remover_uses_composite_key(): void
    {
        $this->loginUser();
        $produto = Produto::factory()->create(['estoque' => 10, 'estoque_compartilhado' => true]);
        $grupo   = ProdutoOpcaoGrupo::factory()->create(['produto_id' => $produto->id]);
        $v1      = ProdutoOpcaoValor::factory()->create(['grupo_id' => $grupo->id]);
        $v2      = ProdutoOpcaoValor::factory()->create(['grupo_id' => $grupo->id]);
        $var1    = ProdutoVariante::factory()->create(['produto_id' => $produto->id, 'valores' => [$v1->id]]);
        $var2    = ProdutoVariante::factory()->create(['produto_id' => $produto->id, 'valores' => [$v2->id]]);

        $this->postJson('/carrinho/adicionar', ['produto_id' => $produto->id, 'produto_variante_id' => $var1->id, 'quantidade' => 1]);
        $this->postJson('/carrinho/adicionar', ['produto_id' => $produto->id, 'produto_variante_id' => $var2->id, 'quantidade' => 1]);

        $this->postJson('/carrinho/remover', ['produto_id' => $produto->id, 'produto_variante_id' => $var1->id]);

        $carrinho = Pedido::where('user_id', auth()->id())->where('status', 'carrinho')->with('itens')->first();
        $this->assertCount(1, $carrinho->itens);
        $this->assertEquals($var2->id, $carrinho->itens->first()->produto_variante_id);
    }
}
```

- [ ] **Step 2: Run tests to confirm they fail**

```bash
docker exec laravel-app php artisan test tests/Feature/CarrinhoVarianteTest.php
```
Expected: FAIL.

- [ ] **Step 3: Update `adicionar()` in CarrinhoController**

Replace the entire `adicionar()` method. Key changes vs existing code:
1. Accept `produto_variante_id` in validation (nullable)
2. Validate variant ownership cross-FK
3. Use composite key for item lookup `(produto_id, produto_variante_id)`
4. Use `$estoqueEfetivo` for stock checks
5. Store `preco_efetivo`, **do not refresh preco on increment**
6. Build `opcoes_snapshot` server-side

```php
public function adicionar(Request $request)
{
    $request->validate([
        'produto_id'          => 'required|exists:produtos,id',
        'quantidade'          => 'required|integer|min:1|max:10',
        'produto_variante_id' => 'nullable|exists:produto_variantes,id',
    ]);

    $produto = Produto::where('id', $request->produto_id)->where('ativo', true)->first();
    if (!$produto) {
        return response()->json(['success' => false, 'message' => 'Produto não encontrado ou inativo.'], 404);
    }

    // Validate and load variant
    $variante = null;
    if ($request->produto_variante_id) {
        $variante = \App\Models\ProdutoVariante::with('valores.grupo')
            ->where('id', $request->produto_variante_id)
            ->where('produto_id', $produto->id) // cross-FK check
            ->where('ativo', true)
            ->first();

        if (!$variante) {
            return response()->json(['success' => false, 'message' => 'Variante inválida ou inativa.'], 422);
        }
    }

    $estoqueEfetivo = $variante ? $variante->estoque_efetivo : $produto->estoque;

    if ($estoqueEfetivo < $request->quantidade) {
        return response()->json(['success' => false, 'message' => 'Quantidade solicitada não disponível em estoque.'], 400);
    }

    if (!Auth::check()) {
        return response()->json([
            'success' => false,
            'message' => 'Você precisa estar logado para adicionar produtos ao carrinho.',
            'redirect' => route('site.login')
        ], 401);
    }

    $carrinho = Pedido::where('user_id', Auth::id())->where('status', 'carrinho')->first();
    if (!$carrinho) {
        $carrinho = Pedido::create(['user_id' => Auth::id(), 'status' => 'carrinho', 'valor_total' => 0]);
    }

    // Composite key lookup
    $itemExistente = ItemPedido::where('pedido_id', $carrinho->id)
        ->where('produto_id', $produto->id)
        ->where('produto_variante_id', $request->produto_variante_id) // null is a valid key
        ->first();

    if ($itemExistente) {
        $novaQuantidade = $itemExistente->quantidade + $request->quantidade;
        if ($novaQuantidade > $estoqueEfetivo) {
            return response()->json(['success' => false, 'message' => 'Quantidade total excede o estoque disponível.'], 400);
        }
        // Only increment — never refresh preco
        $itemExistente->quantidade = $novaQuantidade;
        $itemExistente->save();
    } else {
        $precoGravado = $variante ? $variante->preco_efetivo : $produto->preco;

        // Build opcoes_snapshot server-side
        $snapshot = null;
        if ($variante) {
            $snapshot = [];
            foreach ($variante->valores as $valorId) {
                $valorModel = $variante->valores()->getRelated()->with('grupo')->find($valorId);
                if ($valorModel) {
                    $snapshot[$valorModel->grupo->nome] = $valorModel->valor;
                }
            }
        }

        // Note: $variante->valores is an array of IDs. We need to load models.
        // Better approach using the eager-loaded relation:
        if ($variante && $snapshot === null) {
            $snapshot = [];
        }

        ItemPedido::create([
            'pedido_id'           => $carrinho->id,
            'produto_id'          => $produto->id,
            'produto_variante_id' => $variante?->id,
            'quantidade'          => $request->quantidade,
            'preco'               => $precoGravado,
            'opcoes_snapshot'     => $snapshot,
        ]);
    }

    $this->recalcularValorTotal($carrinho);

    return response()->json([
        'success' => true,
        'message' => 'Produto adicionado ao carrinho com sucesso!',
        'carrinho' => [
            'total_itens' => $carrinho->itens()->sum('quantidade'),
            'valor_total' => $carrinho->valor_total,
        ],
    ]);
}
```

The snapshot building above needs cleanup. Replace the snapshot block with:

```php
// Build opcoes_snapshot server-side
$snapshot = null;
if ($variante) {
    $snapshot = [];
    $varianteComValores = \App\Models\ProdutoVariante::with('valores.grupo')->find($variante->id);
    foreach ($varianteComValores->valores as $valorId) {
        // valores is an array of IDs; load each
        $valorModel = \App\Models\ProdutoOpcaoValor::with('grupo')->find($valorId);
        if ($valorModel) {
            $snapshot[$valorModel->grupo->nome] = $valorModel->valor;
        }
    }
}
```

Note: `$variante->valores` is the array cast — it's an array of IDs, not a relation. The line `$variante->valores()` won't work. Use the explicit load shown above.

- [ ] **Step 4: Update `remover()` in CarrinhoController**

Add `produto_variante_id` to validation and WHERE clause:

```php
public function remover(Request $request)
{
    $request->validate([
        'produto_id'          => 'required|exists:produtos,id',
        'produto_variante_id' => 'nullable|exists:produto_variantes,id',
    ]);

    if (!Auth::check()) {
        return response()->json(['success' => false, 'message' => 'Usuário não autenticado.'], 401);
    }

    $carrinho = Pedido::where('user_id', Auth::id())->where('status', 'carrinho')->first();
    if (!$carrinho) {
        return response()->json(['success' => false, 'message' => 'Carrinho não encontrado.'], 404);
    }

    $item = ItemPedido::where('pedido_id', $carrinho->id)
        ->where('produto_id', $request->produto_id)
        ->where('produto_variante_id', $request->produto_variante_id)
        ->first();

    if ($item) {
        $item->delete();
        $this->recalcularValorTotal($carrinho);
    }

    return response()->json(['success' => true, 'message' => 'Produto removido do carrinho.']);
}
```

- [ ] **Step 5: Update `atualizar_quantidade()` in CarrinhoController**

```php
public function atualizar_quantidade(Request $request)
{
    $request->validate([
        'produto_id'          => 'required|exists:produtos,id',
        'quantidade'          => 'required|integer|min:1|max:10',
        'produto_variante_id' => 'nullable|exists:produto_variantes,id',
    ]);

    if (!Auth::check()) {
        return response()->json(['success' => false, 'message' => 'Usuário não autenticado.'], 401);
    }

    $produto = Produto::find($request->produto_id);

    // Load variant and use estoque_efetivo
    $variante = null;
    if ($request->produto_variante_id) {
        $variante = \App\Models\ProdutoVariante::find($request->produto_variante_id);
    }
    $estoqueEfetivo = $variante ? $variante->estoque_efetivo : $produto->estoque;

    if ($estoqueEfetivo < $request->quantidade) {
        return response()->json(['success' => false, 'message' => 'Quantidade solicitada não disponível em estoque.'], 400);
    }

    $carrinho = Pedido::where('user_id', Auth::id())->where('status', 'carrinho')->first();
    if (!$carrinho) {
        return response()->json(['success' => false, 'message' => 'Carrinho não encontrado.'], 404);
    }

    $item = ItemPedido::where('pedido_id', $carrinho->id)
        ->where('produto_id', $request->produto_id)
        ->where('produto_variante_id', $request->produto_variante_id)
        ->first();

    if ($item) {
        $item->quantidade = $request->quantidade;
        $item->save();
        $this->recalcularValorTotal($carrinho);
    }

    return response()->json(['success' => true, 'message' => 'Quantidade atualizada com sucesso.']);
}
```

- [ ] **Step 6: Update `verificar_produto()` in CarrinhoController**

```php
public function verificar_produto(Request $request)
{
    if (!Auth::check()) {
        return response()->json(['success' => false, 'no_carrinho' => false]);
    }

    $request->validate([
        'produto_id'          => 'required|exists:produtos,id',
        'produto_variante_id' => 'nullable|exists:produto_variantes,id',
    ]);

    $carrinho = Pedido::where('user_id', Auth::id())->where('status', 'carrinho')->first();
    if (!$carrinho) {
        return response()->json(['success' => true, 'no_carrinho' => false]);
    }

    $item = ItemPedido::where('pedido_id', $carrinho->id)
        ->where('produto_id', $request->produto_id)
        ->where('produto_variante_id', $request->produto_variante_id)
        ->first();

    return response()->json([
        'success'             => true,
        'no_carrinho'         => (bool) $item,
        'quantidade'          => $item ? $item->quantidade : 0,
        'produto_variante_id' => $item ? $item->produto_variante_id : null,
    ]);
}
```

- [ ] **Step 7: Update `itens()` in CarrinhoController**

Add eager loading and include `opcoes_snapshot` in the response:

```php
public function itens()
{
    if (!Auth::check()) {
        return response()->json(['success' => false, 'message' => 'Usuário não autenticado.'], 401);
    }

    $carrinho = Pedido::where('user_id', Auth::id())
        ->where('status', 'carrinho')
        ->with(['itens.produto.imagens', 'itens.produtoVariante'])
        ->first();

    if (!$carrinho) {
        return response()->json(['success' => true, 'carrinho' => null]);
    }

    return response()->json([
        'success' => true,
        'carrinho' => [
            'id'          => $carrinho->id,
            'valor_total' => $carrinho->valor_total,
            'itens'       => $carrinho->itens->map(function ($item) {
                return [
                    'id'                  => $item->id,
                    'quantidade'          => $item->quantidade,
                    'preco'               => $item->preco,
                    'produto_variante_id' => $item->produto_variante_id,
                    'opcoes_snapshot'     => $item->opcoes_snapshot,
                    'produto' => [
                        'id'             => $item->produto->id,
                        'nome'           => $item->produto->nome,
                        'primeira_imagem'=> $item->produto->primeira_imagem,
                    ],
                ];
            }),
        ],
    ]);
}
```

- [ ] **Step 8: Run cart tests**

```bash
docker exec laravel-app php artisan test tests/Feature/CarrinhoVarianteTest.php
```
Expected: all tests PASS.

- [ ] **Step 9: Run full test suite**

```bash
docker exec laravel-app php artisan test
```
Expected: all existing + new tests PASS.

- [ ] **Step 10: Commit**

```bash
git add app/Http/Controllers/CarrinhoController.php tests/Feature/CarrinhoVarianteTest.php
git commit -m "feat: update CarrinhoController for variant-aware cart (composite key, preco_efetivo, opcoes_snapshot)"
```

---

## Task 5: Admin UI — Tabs + Variant Management

**Files:**
- Modify: `resources/views/admin/produtos.blade.php`
- Modify: `public/js/admin.js`

> Note: This task involves the product edit modal. Read the existing modal HTML in `resources/views/admin/produtos.blade.php` to understand current structure before editing. The existing modal has no tab system — build it from scratch.

- [ ] **Step 1: Read current modal HTML**

Open `resources/views/admin/produtos.blade.php` and locate the product edit modal. Identify:
- The modal's ID
- The form fields sections
- The image management section

- [ ] **Step 2: Add tab navigation HTML to the modal**

Inside the product edit modal, above the form fields, add a tab bar:

```html
{{-- Tab navigation --}}
<div class="flex border-b border-gray-200 mb-6" id="produto-modal-tabs">
    <button type="button"
            class="produto-tab-btn px-4 py-2 text-sm font-mono uppercase tracking-widest border-b-2 -mb-px transition-colors border-black text-black"
            data-tab="dados">
        DADOS
    </button>
    <button type="button"
            class="produto-tab-btn px-4 py-2 text-sm font-mono uppercase tracking-widest border-b-2 -mb-px transition-colors border-transparent text-gray-400 hover:text-black"
            data-tab="imagens">
        IMAGENS
    </button>
    <button type="button"
            class="produto-tab-btn px-4 py-2 text-sm font-mono uppercase tracking-widest border-b-2 -mb-px transition-colors border-transparent text-gray-400 hover:text-black"
            data-tab="variantes">
        VARIANTES
    </button>
</div>

{{-- Tab panels --}}
<div id="tab-dados" class="produto-tab-panel">
    {{-- existing form fields go here --}}
</div>
<div id="tab-imagens" class="produto-tab-panel hidden">
    {{-- existing image management goes here --}}
</div>
<div id="tab-variantes" class="produto-tab-panel hidden">
    {{-- variant management UI --}}
    <div id="variantes-loading" class="text-center py-8 text-gray-400 font-mono text-sm">CARREGANDO...</div>
    <div id="variantes-content" class="hidden">

        {{-- Estoque compartilhado --}}
        <div class="flex items-center gap-3 mb-6 p-4 border border-gray-200">
            <input type="checkbox" id="estoque-compartilhado" class="w-4 h-4">
            <label for="estoque-compartilhado" class="text-sm font-mono uppercase tracking-widest">
                Compartilhar estoque entre variantes
            </label>
        </div>

        {{-- Grupos e valores --}}
        <div id="grupos-container" class="space-y-4 mb-6"></div>
        <button type="button" id="add-grupo-btn"
                class="text-sm font-mono uppercase tracking-widest border border-dashed border-gray-400 px-4 py-2 hover:border-black transition-colors w-full mb-6">
            + ADICIONAR GRUPO DE OPÇÕES
        </button>

        {{-- Botão gerar combinações --}}
        <button type="button" id="gerar-variantes-btn"
                class="bg-black text-white font-mono uppercase tracking-widest text-sm px-6 py-3 hover:bg-gray-800 transition-colors mb-6 w-full">
            GERAR COMBINAÇÕES
        </button>

        {{-- Tabela de variantes --}}
        <div id="variantes-tabela-container" class="hidden">
            <h4 class="font-mono text-xs uppercase tracking-widest text-gray-500 mb-3">VARIANTES GERADAS</h4>
            <div id="variantes-tabela" class="space-y-2"></div>
            <button type="button" id="salvar-variantes-btn"
                    class="mt-4 bg-black text-white font-mono uppercase tracking-widest text-sm px-6 py-3 hover:bg-gray-800 transition-colors w-full">
                SALVAR VARIANTES
            </button>
        </div>
    </div>
</div>
```

- [ ] **Step 3: Add tab switching logic to admin.js**

Add to `admin.js` (inside DOMContentLoaded or as a standalone function called when the modal opens):

```javascript
// Tab system for product modal
function initProdutoModalTabs() {
    var tabBtns = document.querySelectorAll('.produto-tab-btn');
    var tabPanels = document.querySelectorAll('.produto-tab-panel');

    tabBtns.forEach(function(btn) {
        btn.addEventListener('click', function() {
            var targetTab = this.getAttribute('data-tab');

            tabBtns.forEach(function(b) {
                b.classList.remove('border-black', 'text-black');
                b.classList.add('border-transparent', 'text-gray-400');
            });
            this.classList.remove('border-transparent', 'text-gray-400');
            this.classList.add('border-black', 'text-black');

            tabPanels.forEach(function(panel) {
                panel.classList.add('hidden');
            });
            var panel = document.getElementById('tab-' + targetTab);
            if (panel) panel.classList.remove('hidden');

            if (targetTab === 'variantes') {
                loadVariantes(window.currentProdutoId);
            }
        });
    });
}
```

- [ ] **Step 4: Add variant load and management functions to admin.js**

Add these functions to `admin.js`:

```javascript
function loadVariantes(produtoId) {
    if (!produtoId) return;
    var loading = document.getElementById('variantes-loading');
    var content = document.getElementById('variantes-content');
    if (loading) loading.classList.remove('hidden');
    if (content) content.classList.add('hidden');

    fetch('/admin/produtos/' + produtoId + '/opcoes', {
        headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': getCsrfToken() }
    })
    .then(function(r) { return r.json(); })
    .then(function(data) {
        renderGrupos(data.grupos || []);
        renderVariantesTabela(data.variantes || []);
        var cb = document.getElementById('estoque-compartilhado');
        if (cb) cb.checked = data.estoque_compartilhado;
        if (loading) loading.classList.add('hidden');
        if (content) content.classList.remove('hidden');
    });
}

function renderGrupos(grupos) {
    var container = document.getElementById('grupos-container');
    if (!container) return;
    container.innerHTML = '';
    grupos.forEach(function(grupo, idx) {
        container.insertAdjacentHTML('beforeend', buildGrupoHTML(grupo, idx));
    });
}

function buildGrupoHTML(grupo, idx) {
    var valoresHTML = (grupo.valores || []).map(function(v) {
        return '<span class="inline-flex items-center gap-1 bg-gray-100 px-2 py-1 text-xs font-mono">' +
               escapeHtml(v.valor) +
               '<button type="button" class="remove-valor-btn text-gray-400 hover:text-black" data-valor-id="' + v.id + '">×</button>' +
               '</span>';
    }).join('');

    return '<div class="grupo-item border border-gray-200 p-4" data-grupo-id="' + (grupo.id || '') + '" data-grupo-idx="' + idx + '">' +
        '<div class="flex items-center gap-2 mb-3">' +
        '<input type="text" class="grupo-nome-input flex-1 border border-gray-300 px-3 py-2 text-sm font-mono uppercase" value="' + escapeHtml(grupo.nome || '') + '" placeholder="EX: COR">' +
        '<button type="button" class="remove-grupo-btn text-gray-400 hover:text-black font-mono text-sm px-2">REMOVER</button>' +
        '</div>' +
        '<div class="flex flex-wrap gap-2 mb-2 valores-container">' + valoresHTML + '</div>' +
        '<div class="flex gap-2">' +
        '<input type="text" class="novo-valor-input flex-1 border border-gray-300 px-3 py-2 text-sm font-mono" placeholder="Novo valor...">' +
        '<button type="button" class="add-valor-btn bg-gray-100 hover:bg-gray-200 px-3 py-2 text-sm font-mono uppercase transition-colors">+ ADD</button>' +
        '</div>' +
        '</div>';
}

function renderVariantesTabela(variantes) {
    var container = document.getElementById('variantes-tabela');
    var wrapper = document.getElementById('variantes-tabela-container');
    if (!container) return;
    if (variantes.length === 0) {
        if (wrapper) wrapper.classList.add('hidden');
        return;
    }
    if (wrapper) wrapper.classList.remove('hidden');
    container.innerHTML = variantes.map(function(v) {
        return '<div class="variante-row flex items-center gap-3 border border-gray-100 p-3" data-variante-id="' + v.id + '">' +
            '<span class="flex-1 text-sm font-mono">' + escapeHtml(v.label) + '</span>' +
            '<input type="number" class="variante-preco w-28 border border-gray-300 px-2 py-1 text-sm font-mono" placeholder="Preço" value="' + (v.preco || '') + '" step="0.01" min="0">' +
            '<input type="number" class="variante-estoque w-20 border border-gray-300 px-2 py-1 text-sm font-mono estoque-field" placeholder="Estq" value="' + (v.estoque !== null ? v.estoque : '') + '" min="0">' +
            '<label class="flex items-center gap-1 text-xs font-mono"><input type="checkbox" class="variante-ativo" ' + (v.ativo ? 'checked' : '') + '> ATIVO</label>' +
            '</div>';
    }).join('');
}

function getCsrfToken() {
    var meta = document.querySelector('meta[name="csrf-token"]');
    return meta ? meta.getAttribute('content') : '';
}

function escapeHtml(str) {
    var d = document.createElement('div');
    d.appendChild(document.createTextNode(str));
    return d.innerHTML;
}
```

- [ ] **Step 5: Wire up variant tab buttons in admin.js**

Add event delegation for the dynamic elements in the variantes tab:

```javascript
// Delegate events for variant tab (add inside DOMContentLoaded)
document.addEventListener('click', function(e) {
    // Add grupo
    if (e.target && e.target.id === 'add-grupo-btn') {
        var container = document.getElementById('grupos-container');
        var idx = container ? container.children.length : 0;
        container.insertAdjacentHTML('beforeend', buildGrupoHTML({nome: '', valores: []}, idx));
    }

    // Remove grupo
    if (e.target && e.target.classList.contains('remove-grupo-btn')) {
        e.target.closest('.grupo-item').remove();
    }

    // Add valor
    if (e.target && e.target.classList.contains('add-valor-btn')) {
        var grupoItem = e.target.closest('.grupo-item');
        var input = grupoItem.querySelector('.novo-valor-input');
        var valor = input.value.trim();
        if (!valor) return;
        var valoresContainer = grupoItem.querySelector('.valores-container');
        valoresContainer.insertAdjacentHTML('beforeend',
            '<span class="inline-flex items-center gap-1 bg-gray-100 px-2 py-1 text-xs font-mono">' +
            escapeHtml(valor) +
            '<button type="button" class="remove-valor-btn text-gray-400 hover:text-black" data-valor-id="">×</button>' +
            '</span>');
        input.value = '';
    }

    // Remove valor
    if (e.target && e.target.classList.contains('remove-valor-btn')) {
        e.target.closest('span').remove();
    }

    // Salvar grupos
    if (e.target && e.target.id === 'salvar-grupos-btn') {
        salvarGrupos(window.currentProdutoId);
    }

    // Gerar variantes
    if (e.target && e.target.id === 'gerar-variantes-btn') {
        salvarGruposEGerar(window.currentProdutoId);
    }

    // Salvar variantes
    if (e.target && e.target.id === 'salvar-variantes-btn') {
        salvarVariantes(window.currentProdutoId);
    }
});

function salvarGruposEGerar(produtoId) {
    var grupos = collectGruposFromUI();
    var csrfToken = getCsrfToken();

    fetch('/admin/produtos/' + produtoId + '/opcao-grupos', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' },
        body: JSON.stringify({ grupos: grupos })
    })
    .then(function(r) { return r.json(); })
    .then(function(data) {
        if (!data.success) { alert('Erro ao salvar grupos.'); return; }
        return fetch('/admin/produtos/' + produtoId + '/variantes/gerar', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' }
        });
    })
    .then(function(r) { return r ? r.json() : null; })
    .then(function(data) {
        if (data) loadVariantes(produtoId);
    });
}

function collectGruposFromUI() {
    var grupos = [];
    document.querySelectorAll('.grupo-item').forEach(function(item, idx) {
        var nome = item.querySelector('.grupo-nome-input').value.trim();
        if (!nome) return;
        var valores = [];
        item.querySelectorAll('.valores-container span').forEach(function(span, vIdx) {
            var texto = span.childNodes[0].textContent.trim();
            if (texto) valores.push({ valor: texto, ordem: vIdx });
        });
        grupos.push({ nome: nome, ordem: idx, valores: valores });
    });
    return grupos;
}

function salvarVariantes(produtoId) {
    var variantes = [];
    document.querySelectorAll('.variante-row').forEach(function(row) {
        var id = parseInt(row.getAttribute('data-variante-id'));
        var preco = row.querySelector('.variante-preco').value;
        var estoque = row.querySelector('.variante-estoque').value;
        var ativo = row.querySelector('.variante-ativo').checked;
        variantes.push({
            id: id,
            preco: preco !== '' ? parseFloat(preco) : null,
            estoque: estoque !== '' ? parseInt(estoque) : null,
            ativo: ativo,
        });
    });

    var estoqueCompartilhado = document.getElementById('estoque-compartilhado').checked;

    fetch('/admin/produtos/' + produtoId + '/variantes', {
        method: 'PUT',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': getCsrfToken(), 'Accept': 'application/json' },
        body: JSON.stringify({ estoque_compartilhado: estoqueCompartilhado, variantes: variantes })
    })
    .then(function(r) { return r.json(); })
    .then(function(data) {
        if (data.success && window.showNotification) window.showNotification('Variantes salvas!', 'success');
    });
}
```

- [ ] **Step 6: Ensure `window.currentProdutoId` is set when opening the edit modal**

In `admin.js`, find where the edit modal is opened (look for the function that calls the product fetch endpoint and opens the modal). Add:

```javascript
window.currentProdutoId = produtoId;
// Also reset tab to first tab when opening modal
document.querySelectorAll('.produto-tab-btn').forEach(function(b, i) {
    if (i === 0) { b.classList.add('border-black', 'text-black'); b.classList.remove('border-transparent', 'text-gray-400'); }
    else { b.classList.remove('border-black', 'text-black'); b.classList.add('border-transparent', 'text-gray-400'); }
});
document.querySelectorAll('.produto-tab-panel').forEach(function(p, i) {
    if (i === 0) p.classList.remove('hidden'); else p.classList.add('hidden');
});
```

Call `initProdutoModalTabs()` once after DOM is ready.

- [ ] **Step 7: Clear views cache and rebuild assets**

```bash
docker exec laravel-app php artisan view:clear
docker exec laravel-app php artisan cache:clear
cd /var/www/html && npm run build
```

- [ ] **Step 8: Manually test admin variant UI**
1. Log in as admin
2. Open product edit modal
3. Verify three tabs appear (DADOS / IMAGENS / VARIANTES)
4. Click VARIANTES tab
5. Add a group "Cor" with values "Preto" and "Branco"
6. Add a group "Tamanho" with values "P" and "G"
7. Click GERAR COMBINAÇÕES → verify 4 rows appear
8. Set prices for each row, click SALVAR VARIANTES
9. Reopen modal → verify values are persisted

- [ ] **Step 9: Commit**

```bash
git add resources/views/admin/produtos.blade.php public/js/admin.js
git commit -m "feat: add admin variant tab UI with group/value editor and variant price table"
```

---

## Task 6: Frontend — Product Detail Variant Selector

**Files:**
- Modify: `resources/views/site/produto-detalhes.blade.php`
- Modify: `public/js/produto-detalhes.js`

- [ ] **Step 1: Update SiteController to pass variant data**

In `SiteController::produto_detalhes()` (or wherever `produto-detalhes.blade.php` is rendered), eager-load variants and groups:

```php
$produto = Produto::with(['imagens', 'categoria', 'imagemCapa', 'opcaoGrupos.valores', 'variantesAtivas'])
    ->where('slug', $slug)
    ->firstOrFail();
```

- [ ] **Step 2: Add variant selector to Blade view**

In `resources/views/site/produto-detalhes.blade.php`, insert this block **between the description paragraph and the `{{-- Stock Status --}}` section**:

```blade
{{-- Variant Selector --}}
@if($produto->tem_variantes && $produto->variantesAtivas->count() > 0)
<div class="mb-8" id="variante-selector">
    @foreach($produto->opcaoGrupos as $grupo)
    <div class="mb-4">
        <span class="text-xs font-mono font-bold uppercase tracking-widest text-gray-500">{{ $grupo->nome }}</span>
        <div class="flex flex-wrap gap-2 mt-2">
            @foreach($grupo->valores as $valor)
            <button type="button"
                    class="opcao-btn border border-[var(--color-lab-border)] px-4 py-2 text-sm font-mono hover:border-black transition-colors"
                    data-grupo="{{ $grupo->id }}"
                    data-valor="{{ $valor->id }}">
                {{ $valor->valor }}
            </button>
            @endforeach
        </div>
    </div>
    @endforeach
</div>

{{-- Pass variant data as JSON for JS --}}
<script id="variantes-data" type="application/json">
@json($produto->variantesAtivas->map(fn($v) => [
    'id'             => $v->id,
    'valores'        => $v->valores,
    'preco_efetivo'  => $v->preco_efetivo,
    'estoque_efetivo'=> $v->estoque_efetivo,
    'ativo'          => $v->ativo,
]))
</script>
@endif
```

Also update the add-to-cart button to include `data-variante-id` and the initial disabled state when variants exist:

```blade
<button type="button"
    class="add-to-cart-btn flex-1 bg-black text-white py-4 px-8 font-bold tracking-widest hover:bg-gray-800 transition-colors uppercase text-sm
        {{ !$produto->em_estoque || ($produto->tem_variantes && $produto->variantesAtivas->count() > 0) ? 'opacity-50 cursor-not-allowed' : '' }}"
    data-produto-id="{{ $produto->id }}"
    data-variante-id=""
    {{ !$produto->em_estoque || ($produto->tem_variantes && $produto->variantesAtivas->count() > 0) ? 'disabled' : '' }}>
    {{ ($produto->tem_variantes && $produto->variantesAtivas->count() > 0) ? 'SELECIONE AS OPÇÕES' : 'ADICIONAR AO CARRINHO' }}
</button>
```

- [ ] **Step 3: Add variant selection logic to produto-detalhes.js**

At the end of the `DOMContentLoaded` block in `public/js/produto-detalhes.js`, add:

```javascript
// Variant selector
var variantesDataEl = document.getElementById('variantes-data');
if (variantesDataEl) {
    var variantes = JSON.parse(variantesDataEl.textContent);
    var selecao = {}; // { grupoId: valorId }
    var opcaoBtns = document.querySelectorAll('.opcao-btn');
    var addToCartBtn = document.querySelector('.add-to-cart-btn');
    var gruposIds = [];

    opcaoBtns.forEach(function(btn) {
        var grupoId = btn.getAttribute('data-grupo');
        if (gruposIds.indexOf(grupoId) === -1) gruposIds.push(grupoId);
    });

    opcaoBtns.forEach(function(btn) {
        btn.addEventListener('click', function() {
            var grupoId = this.getAttribute('data-grupo');
            var valorId = parseInt(this.getAttribute('data-valor'));

            // Deselect others in same group
            opcaoBtns.forEach(function(b) {
                if (b.getAttribute('data-grupo') === grupoId) {
                    b.classList.remove('border-black', 'bg-black', 'text-white');
                    b.classList.add('border-[var(--color-lab-border)]');
                }
            });
            this.classList.add('border-black', 'bg-black', 'text-white');
            this.classList.remove('border-[var(--color-lab-border)]');

            selecao[grupoId] = valorId;

            // Check if all groups are selected
            var todosGrupos = Object.keys(selecao).length === gruposIds.length;
            if (!todosGrupos) {
                if (addToCartBtn) {
                    addToCartBtn.disabled = true;
                    addToCartBtn.classList.add('opacity-50', 'cursor-not-allowed');
                    addToCartBtn.textContent = 'SELECIONE AS OPÇÕES';
                }
                return;
            }

            // Find matching variant (sorted comparison)
            var selectedIds = gruposIds.map(function(g) { return selecao[g]; }).sort(function(a, b) { return a - b; });
            var varianteEncontrada = null;
            for (var i = 0; i < variantes.length; i++) {
                var vIds = variantes[i].valores.slice().sort(function(a, b) { return a - b; });
                if (JSON.stringify(vIds) === JSON.stringify(selectedIds)) {
                    varianteEncontrada = variantes[i];
                    break;
                }
            }

            if (!varianteEncontrada || !varianteEncontrada.ativo || varianteEncontrada.estoque_efetivo <= 0) {
                if (addToCartBtn) {
                    addToCartBtn.disabled = true;
                    addToCartBtn.classList.add('opacity-50', 'cursor-not-allowed');
                    addToCartBtn.textContent = 'INDISPONÍVEL';
                }
                return;
            }

            // Update price display
            var priceEl = document.querySelector('.text-3xl.font-mono.font-bold');
            if (priceEl) {
                priceEl.textContent = 'R$ ' + varianteEncontrada.preco_efetivo.toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
            }

            // Update stock display
            var stockEl = document.querySelector('.text-green-700, .text-red-600');
            if (stockEl && varianteEncontrada.estoque_efetivo > 0) {
                // Update the units display if present
                var unitsEl = document.querySelector('.text-gray-400');
                if (unitsEl && unitsEl.textContent.includes('un.')) {
                    unitsEl.textContent = '(' + varianteEncontrada.estoque_efetivo + ' un.)';
                }
            }

            // Update quantity max
            if (quantityInput) quantityInput.setAttribute('max', varianteEncontrada.estoque_efetivo);

            // Enable button with variante-id
            if (addToCartBtn) {
                addToCartBtn.setAttribute('data-variante-id', varianteEncontrada.id);
                addToCartBtn.disabled = false;
                addToCartBtn.classList.remove('opacity-50', 'cursor-not-allowed');
                addToCartBtn.textContent = 'ADICIONAR AO CARRINHO';
            }
        });
    });
}
```

- [ ] **Step 4: Update cart fetch in produto-detalhes.js to send variante_id**

In the add-to-cart fetch call (around line 72), update the body:

```javascript
var varianteId = this.getAttribute('data-variante-id');
body: JSON.stringify({
    produto_id: produtoId,
    quantidade: qty,
    produto_variante_id: varianteId ? parseInt(varianteId) : null
})
```

- [ ] **Step 5: Clear cache and rebuild**

```bash
docker exec laravel-app php artisan view:clear
docker exec laravel-app php artisan cache:clear
cd /var/www/html && npm run build
```

- [ ] **Step 6: Manually test product page variant selector**
1. Open a product that has variants configured (from Task 5)
2. Verify option buttons appear for each group
3. Select one option from each group → verify price updates, button enables
4. Add to cart → verify cart sidebar shows the selected options
5. Open a product without variants → verify unchanged behavior

- [ ] **Step 7: Commit**

```bash
git add resources/views/site/produto-detalhes.blade.php public/js/produto-detalhes.js
git commit -m "feat: add variant selector on product detail page with JS price/stock update"
```

---

## Task 7: Cart Sidebar — Display opcoes_snapshot

**Files:**
- Modify: `public/js/cart.js`

- [ ] **Step 1: Locate where cart items are rendered in cart.js**

Open `public/js/cart.js` and find the function that renders cart items from the `/carrinho/itens` JSON response (look for `item.produto.nome`).

- [ ] **Step 2: Add opcoes_snapshot display below product name**

In the item rendering template string, after the product name, add:

```javascript
// After the product name span, add:
var opcoesHtml = '';
if (item.opcoes_snapshot && Object.keys(item.opcoes_snapshot).length > 0) {
    var partes = Object.entries(item.opcoes_snapshot).map(function(entry) {
        return entry[0] + ': ' + entry[1];
    });
    opcoesHtml = '<span class="block text-xs font-mono text-gray-400 mt-1">' +
                 partes.join(' · ') + '</span>';
}
```

Insert `opcoesHtml` into the item HTML after the product name.

- [ ] **Step 3: Update remover/atualizar calls in cart.js to pass produto_variante_id**

Find calls to `/carrinho/remover` and `/carrinho/atualizar` in `cart.js`. Add `produto_variante_id` from the item data:

```javascript
// When rendering items, store variante_id in data attribute:
// data-variante-id="{{ item.produto_variante_id }}"

// When calling remover:
body: JSON.stringify({
    produto_id: produtoId,
    produto_variante_id: varianteId || null
})

// When calling atualizar:
body: JSON.stringify({
    produto_id: produtoId,
    produto_variante_id: varianteId || null,
    quantidade: newQty
})
```

- [ ] **Step 4: Manually test cart sidebar**
1. Add a product with variants to cart
2. Open cart sidebar → verify "Cor: Preto · Tamanho: M" appears below product name
3. Remove item → verify correct item is removed (not wrong variant)

- [ ] **Step 5: Commit**

```bash
git add public/js/cart.js
git commit -m "feat: display opcoes_snapshot in cart sidebar and pass variante_id on remove/update"
```

---

## Task 8: Final Verification

- [ ] **Step 1: Run full test suite**

```bash
docker exec laravel-app php artisan test
```
Expected: ALL tests PASS.

- [ ] **Step 2: Clear all caches**

```bash
docker exec laravel-app php artisan view:clear
docker exec laravel-app php artisan cache:clear
docker exec laravel-app php artisan config:clear
```

- [ ] **Step 3: End-to-end smoke test**
1. Admin: create a mousepad product with groups "Cor" (Preto, Branco) + "Tamanho" (P, G)
2. Admin: generate combinations → 4 variants appear
3. Admin: set different prices per variant, uncheck shared stock, set stock per variant
4. Save
5. Customer: open product page → verify 2 groups with buttons appear
6. Customer: select Preto + G → price updates to correct variant price
7. Customer: add to cart → cart sidebar shows "Cor: Preto · Tamanho: G"
8. Customer: add same product with Branco + P → verify 2 separate cart items
9. Customer: remove one item → other item remains

- [ ] **Step 4: Final commit**

```bash
git add -A
git commit -m "feat: complete product variants system (options, variants, cart integration)"
```
