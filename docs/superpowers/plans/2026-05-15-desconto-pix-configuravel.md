# Desconto PIX Configurável — Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Tornar o percentual de desconto PIX configurável — um valor global editável pelo admin no dashboard, com override opcional por produto.

**Architecture:** Nova tabela `configuracoes` (key-value) armazena o desconto PIX global. O model `Configuracao` expõe `get/set` estáticos com cache de 60s. `Produto` ganha coluna `desconto_pix` nullable e um accessor `getDescontoPix()` que implementa a cadeia de fallback: produto → global → 5.0.

**Tech Stack:** Laravel 12 · PHP 8.3 · PostgreSQL · Blade · Vanilla JS · PHPUnit (RefreshDatabase + SQLite in-memory)

---

## Mapa de Arquivos

| Ação | Arquivo |
|------|---------|
| Criar | `database/migrations/2026_05_15_100001_create_configuracoes_table.php` |
| Criar | `database/migrations/2026_05_15_100002_add_desconto_pix_to_produtos_table.php` |
| Criar | `app/Models/Configuracao.php` |
| Criar | `app/Http/Controllers/AdminConfiguracaoController.php` |
| Criar | `tests/Feature/AdminDescontoPixTest.php` |
| Modificar | `app/Models/Produto.php` (fillable, casts, accessor) |
| Modificar | `app/Http/Controllers/AdminController.php` (dashboard, buscarProduto, editarProduto, criarProduto) |
| Modificar | `routes/web.php` (nova rota POST /admin/configuracoes) |
| Modificar | `resources/views/admin/dashboard.blade.php` (card config PIX) |
| Modificar | `resources/views/admin/includes/modal-produto.blade.php` (campo desconto_pix) |
| Modificar | `public/js/admin.js` (preencher desconto_pix no modal) |
| Modificar | `resources/views/components/product-card.blade.php` (usar accessor) |
| Modificar | `resources/views/site/produto-detalhes.blade.php` (usar accessor + data-attribute) |
| Modificar | `public/js/produto-detalhes.js` (ler data-desconto-pix) |

---

## Task 1: Migrations

**Files:**
- Create: `database/migrations/2026_05_15_100001_create_configuracoes_table.php`
- Create: `database/migrations/2026_05_15_100002_add_desconto_pix_to_produtos_table.php`

- [ ] **Step 1: Criar migration da tabela configuracoes**

```bash
docker exec laravel-app php artisan make:migration create_configuracoes_table
```

Edite o arquivo gerado em `database/migrations/` com o nome `*_create_configuracoes_table.php`:

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('configuracoes', function (Blueprint $table) {
            $table->id();
            $table->string('chave')->unique();
            $table->string('valor');
            $table->timestamps();
        });

        DB::table('configuracoes')->insert([
            'chave' => 'desconto_pix_global',
            'valor' => '5.00',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('configuracoes');
    }
};
```

- [ ] **Step 2: Criar migration da coluna desconto_pix nos produtos**

```bash
docker exec laravel-app php artisan make:migration add_desconto_pix_to_produtos_table
```

Edite o arquivo gerado com o nome `*_add_desconto_pix_to_produtos_table.php`:

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('produtos', function (Blueprint $table) {
            $table->decimal('desconto_pix', 5, 2)->nullable()->after('desconto_percentual');
        });
    }

    public function down(): void
    {
        Schema::table('produtos', function (Blueprint $table) {
            $table->dropColumn('desconto_pix');
        });
    }
};
```

- [ ] **Step 3: Executar as migrations**

```bash
docker exec laravel-app php artisan migrate --force
```

Saída esperada: duas linhas de migração bem-sucedidas, sem erros.

- [ ] **Step 4: Commit**

```bash
git add database/migrations/
git commit -m "feat: migrations para configuracoes e desconto_pix em produtos"
```

---

## Task 2: Model Configuracao

**Files:**
- Create: `app/Models/Configuracao.php`

- [ ] **Step 1: Criar o model**

Crie `app/Models/Configuracao.php`:

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class Configuracao extends Model
{
    protected $table = 'configuracoes';

    protected $fillable = ['chave', 'valor'];

    public static function get(string $chave, mixed $default = null): mixed
    {
        return Cache::remember("configuracao_{$chave}", 60, function () use ($chave, $default) {
            $config = static::where('chave', $chave)->first();
            return $config ? $config->valor : $default;
        });
    }

    public static function set(string $chave, mixed $valor): void
    {
        static::updateOrCreate(['chave' => $chave], ['valor' => (string) $valor]);
        Cache::forget("configuracao_{$chave}");
    }
}
```

- [ ] **Step 2: Commit**

```bash
git add app/Models/Configuracao.php
git commit -m "feat: model Configuracao com get/set e cache"
```

---

## Task 3: Atualizar Model Produto

**Files:**
- Modify: `app/Models/Produto.php`

- [ ] **Step 1: Adicionar desconto_pix ao fillable**

Em `app/Models/Produto.php`, no array `$fillable` (linha ~17), adicione `'desconto_pix'` após `'desconto_percentual'`:

```php
protected $fillable = [
    'nome',
    'slug',
    'marca',
    'descricao',
    'descricao_curta',
    'specs',
    'preco',
    'custo_compra',
    'frete_compra',
    'peso',
    'comprimento',
    'largura',
    'altura',
    'preco_original',
    'desconto_percentual',
    'desconto_pix',       // <-- novo
    'em_promocao',
    'destaque',
    'estoque',
    'estoque_compartilhado',
    'categoria_id',
    'ativo',
    'tags'
];
```

- [ ] **Step 2: Adicionar desconto_pix aos casts**

No array `$casts` (linha ~42), adicione:

```php
'desconto_pix' => 'decimal:2',
```

- [ ] **Step 3: Adicionar o import e o accessor getDescontoPix**

No topo do arquivo, verifique se já existe `use App\Models\Configuracao;` — se não, adicione junto aos outros imports do model (após `namespace App\Models;`):

```php
use App\Models\Configuracao;
```

Adicione o accessor após o accessor `getPrecoComDescontoAttribute` (ou em qualquer ponto dos accessors existentes):

```php
public function getDescontoPix(): float
{
    return (float) ($this->desconto_pix ?? Configuracao::get('desconto_pix_global', 5.0));
}
```

- [ ] **Step 4: Commit**

```bash
git add app/Models/Produto.php
git commit -m "feat: adiciona desconto_pix ao Produto com accessor getDescontoPix"
```

---

## Task 4: Testes

**Files:**
- Create: `tests/Feature/AdminDescontoPixTest.php`

- [ ] **Step 1: Escrever os testes**

Crie `tests/Feature/AdminDescontoPixTest.php`:

```php
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
        $this->admin = User::factory()->create(['is_admin' => true]);
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

        // Cache foi invalidado — próxima leitura vai ao banco
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
```

- [ ] **Step 2: Rodar os testes — devem falhar**

```bash
docker exec laravel-app php artisan test --filter=AdminDescontoPixTest
```

Saída esperada: todos os testes falhando (routes, factories, etc. ainda não implementados).

- [ ] **Step 3: Commit dos testes**

```bash
git add tests/Feature/AdminDescontoPixTest.php
git commit -m "test: testes para desconto PIX configurável (TDD)"
```

---

## Task 5: AdminConfiguracaoController + Rota

**Files:**
- Create: `app/Http/Controllers/AdminConfiguracaoController.php`
- Modify: `routes/web.php`

- [ ] **Step 1: Criar o controller**

Crie `app/Http/Controllers/AdminConfiguracaoController.php`:

```php
<?php

namespace App\Http\Controllers;

use App\Models\Configuracao;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AdminConfiguracaoController extends Controller
{
    public function update(Request $request)
    {
        if (! Auth::check()) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $validated = $request->validate([
            'desconto_pix_global' => 'required|numeric|min:0|max:100',
        ]);

        Configuracao::set('desconto_pix_global', number_format((float) $validated['desconto_pix_global'], 2, '.', ''));

        return response()->json([
            'success' => true,
            'desconto_pix_global' => (float) Configuracao::get('desconto_pix_global'),
        ]);
    }
}
```

- [ ] **Step 2: Adicionar a rota**

Em `routes/web.php`, dentro do grupo `prefix('admin')->middleware(['auth', 'admin'])`, adicione após as rotas de cupons:

```php
Route::post('/configuracoes', [App\Http\Controllers\AdminConfiguracaoController::class, 'update'])->name('configuracoes.update');
```

- [ ] **Step 3: Rodar os testes de API**

```bash
docker exec laravel-app php artisan test --filter=AdminDescontoPixTest::test_admin_can_update_global_pix_discount
docker exec laravel-app php artisan test --filter=AdminDescontoPixTest::test_admin_configuracao_update_validates_range
docker exec laravel-app php artisan test --filter=AdminDescontoPixTest::test_guest_cannot_update_global_pix_discount
```

Saída esperada: 3 testes passando.

- [ ] **Step 4: Commit**

```bash
git add app/Http/Controllers/AdminConfiguracaoController.php routes/web.php
git commit -m "feat: AdminConfiguracaoController e rota POST /admin/configuracoes"
```

---

## Task 6: AdminController — dashboard + buscarProduto + editarProduto + criarProduto

**Files:**
- Modify: `app/Http/Controllers/AdminController.php`

- [ ] **Step 1: Adicionar import do Configuracao no topo do AdminController**

Em `app/Http/Controllers/AdminController.php`, no bloco de `use` imports (por volta da linha 5-18), adicione:

```php
use App\Models\Configuracao;
```

- [ ] **Step 2: Passar desconto_pix_global para o dashboard**

Altere o método `dashboard()` (linha ~36) para incluir o valor global:

```php
public function dashboard()
{
    if (! Auth::check()) {
        return redirect()->route('site.login');
    }

    $data = $this->getDashboardAnalyticsData();
    $data['desconto_pix_global'] = (float) Configuracao::get('desconto_pix_global', 5.0);

    return view('admin.dashboard', $data);
}
```

- [ ] **Step 3: Incluir desconto_pix no JSON do buscarProduto**

No método `buscarProduto()` (linha ~341), adicione `'desconto_pix'` no array de retorno, após `'desconto_percentual'`:

```php
'desconto_percentual' => $produto->desconto_percentual,
'desconto_pix' => $produto->desconto_pix,
```

- [ ] **Step 4: Adicionar desconto_pix na validação do editarProduto**

No método `editarProduto()` (linha ~491), no array de validação, adicione após `'desconto_percentual'`:

```php
'desconto_pix' => 'nullable|numeric|min:0|max:100',
```

- [ ] **Step 5: Salvar desconto_pix no update do editarProduto**

No array do `$produto->update([...])` dentro de `editarProduto()` (linha ~564), adicione após `'desconto_percentual'`:

```php
'desconto_pix' => $request->filled('desconto_pix') ? (float) $request->desconto_pix : null,
```

- [ ] **Step 6: Adicionar desconto_pix na validação do criarProduto**

No método `criarProduto()` (linha ~380), no array de validação, adicione após `'desconto_percentual'`:

```php
'desconto_pix' => 'nullable|numeric|min:0|max:100',
```

- [ ] **Step 7: Salvar desconto_pix no create do criarProduto**

No array do `Produto::create([...])` dentro de `criarProduto()` (linha ~444), adicione após `'desconto_percentual'`:

```php
'desconto_pix' => $request->filled('desconto_pix') ? (float) $request->desconto_pix : null,
```

- [ ] **Step 8: Rodar testes de produto**

```bash
docker exec laravel-app php artisan test --filter=AdminDescontoPixTest::test_editar_produto_saves_desconto_pix
docker exec laravel-app php artisan test --filter=AdminDescontoPixTest::test_editar_produto_clears_desconto_pix_when_empty
```

Saída esperada: 2 testes passando.

- [ ] **Step 9: Rodar todos os testes para verificar regressões**

```bash
docker exec laravel-app php artisan test
```

Saída esperada: todos os testes passando.

- [ ] **Step 10: Commit**

```bash
git add app/Http/Controllers/AdminController.php
git commit -m "feat: AdminController passa desconto_pix global e por produto"
```

---

## Task 7: Dashboard view — card de configuração PIX

**Files:**
- Modify: `resources/views/admin/dashboard.blade.php`

- [ ] **Step 1: Adicionar card de configuração PIX no dashboard**

Em `resources/views/admin/dashboard.blade.php`, antes de `@endsection` (linha ~687), adicione:

```blade
{{-- Configurações da Loja --}}
<div class="border border-[var(--color-lab-border)] bg-white">
    <div class="flex items-center justify-between gap-3 px-4 sm:px-6 py-4 border-b border-[var(--color-lab-border)]">
        <p class="font-mono text-[10px] uppercase tracking-widest text-[var(--color-lab-muted)]">Configurações da Loja</p>
    </div>
    <div class="p-4 sm:p-6">
        <form id="form-config-pix" class="flex items-end gap-4 max-w-xs">
            @csrf
            <div class="flex-1">
                <label class="block font-mono text-[10px] uppercase tracking-widest text-gray-400 mb-2">Desconto PIX Global (%)</label>
                <input type="number"
                       name="desconto_pix_global"
                       id="desconto_pix_global"
                       value="{{ number_format($desconto_pix_global, 2, '.', '') }}"
                       min="0" max="100" step="0.01"
                       class="w-full px-4 py-3 border border-[var(--color-lab-border)] text-sm font-mono focus:outline-none focus:border-black">
                <p class="font-mono text-[10px] text-gray-400 mt-1">Aplica a todos os produtos sem override individual</p>
            </div>
            <button type="submit"
                    class="px-5 py-3 bg-black text-white font-mono text-[10px] uppercase tracking-widest hover:bg-gray-800 transition-colors whitespace-nowrap">
                SALVAR
            </button>
        </form>
        <p id="config-pix-feedback" class="font-mono text-xs mt-3 hidden"></p>
    </div>
</div>

<script>
document.getElementById('form-config-pix').addEventListener('submit', function(e) {
    e.preventDefault();
    const feedback = document.getElementById('config-pix-feedback');
    fetch('{{ route("admin.configuracoes.update") }}', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}',
            'Accept': 'application/json',
        },
        body: JSON.stringify({
            desconto_pix_global: document.getElementById('desconto_pix_global').value
        })
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            feedback.textContent = 'Salvo! Desconto PIX global: ' + data.desconto_pix_global + '%';
            feedback.className = 'font-mono text-xs mt-3 text-green-600';
        } else {
            feedback.textContent = 'Erro ao salvar.';
            feedback.className = 'font-mono text-xs mt-3 text-red-600';
        }
    })
    .catch(() => {
        feedback.textContent = 'Erro de conexão.';
        feedback.className = 'font-mono text-xs mt-3 text-red-600';
    });
});
</script>
```

- [ ] **Step 2: Limpar cache de views**

```bash
docker exec laravel-app php artisan view:clear
```

- [ ] **Step 3: Commit**

```bash
git add resources/views/admin/dashboard.blade.php
git commit -m "feat: card de configuração desconto PIX global no dashboard admin"
```

---

## Task 8: Modal de produto — campo desconto_pix + admin.js

**Files:**
- Modify: `resources/views/admin/includes/modal-produto.blade.php`
- Modify: `public/js/admin.js`

- [ ] **Step 1: Adicionar campo no modal**

Em `resources/views/admin/includes/modal-produto.blade.php`, logo após o bloco do `desconto_percentual` (linha ~195), adicione:

```blade
<div id="campo-desconto-pix" class="mt-3">
    <label for="desconto_pix" class="block font-mono text-[10px] uppercase tracking-widest text-gray-400 mb-1">Desconto PIX deste produto (%)</label>
    <input type="number"
           name="desconto_pix"
           id="desconto_pix"
           min="0" max="100" step="0.01"
           class="w-full px-4 py-3 border border-[var(--color-lab-border)] text-sm font-mono focus:outline-none focus:border-black"
           placeholder="Vazio = usa global">
    <p class="font-mono text-[10px] text-gray-400 mt-1" id="desconto-pix-hint">Deixe vazio para herdar o global</p>
</div>
```

- [ ] **Step 2: Popular desconto_pix no modal via JS**

Em `public/js/admin.js`, dentro da função `editarProduto(id)` (linha ~135), após a linha que popula `desconto_percentual` (linha ~159):

```js
const descontoPix = document.getElementById('desconto_pix');
if (descontoPix) {
    descontoPix.value = data.desconto_pix !== null && data.desconto_pix !== undefined ? data.desconto_pix : '';
}
```

- [ ] **Step 3: Limpar o campo ao abrir modal de novo produto**

Na função que abre o modal para criação (procure por `abrirModalProduto` ou onde `desconto_percentual` é limpo, linha ~92-93 do admin.js), adicione:

```js
const descontoPix = document.getElementById('desconto_pix');
if (descontoPix) descontoPix.value = '';
```

- [ ] **Step 4: Limpar cache e testar visualmente no browser**

```bash
docker exec laravel-app php artisan view:clear
```

Abra `/admin/produtos`, clique em editar qualquer produto e verifique que o campo "Desconto PIX" aparece abaixo do campo de desconto percentual.

- [ ] **Step 5: Commit**

```bash
git add resources/views/admin/includes/modal-produto.blade.php public/js/admin.js
git commit -m "feat: campo desconto_pix individual no modal de produto admin"
```

---

## Task 9: Views Blade do site — product-card e produto-detalhes

**Files:**
- Modify: `resources/views/components/product-card.blade.php`
- Modify: `resources/views/site/produto-detalhes.blade.php`

- [ ] **Step 1: Atualizar product-card para usar o accessor**

Em `resources/views/components/product-card.blade.php`, localize o bloco `@php` que calcula `$precoPix` e `$valorParcela` (adicionado anteriormente com `* 0.95` hardcoded) e substitua:

```blade
@php
    $precoPix = $produto->preco_com_desconto * 0.95;
    $valorParcela = $produto->preco_com_desconto / 10;
@endphp
```

Por:

```blade
@php
    $fatorPix = 1 - ($produto->getDescontoPix() / 100);
    $precoPix = $produto->preco_com_desconto * $fatorPix;
    $valorParcela = $produto->preco_com_desconto / 10;
@endphp
```

- [ ] **Step 2: Atualizar produto-detalhes para usar o accessor e data-attribute**

Em `resources/views/site/produto-detalhes.blade.php`, localize o bloco `@php` com `* 0.95` e substitua:

```blade
@php
    $precoPix = $produto->preco_com_desconto * 0.95;
    $valorParcela = $produto->preco_com_desconto / 10;
@endphp
```

Por:

```blade
@php
    $fatorPix = 1 - ($produto->getDescontoPix() / 100);
    $precoPix = $produto->preco_com_desconto * $fatorPix;
    $valorParcela = $produto->preco_com_desconto / 10;
@endphp
```

Localize também a tag `<span id="price-pix"` e adicione o `data-attribute`:

```blade
<span class="text-3xl font-mono font-bold" id="price-pix" data-desconto-pix="{{ $produto->getDescontoPix() }}">
```

- [ ] **Step 3: Limpar cache**

```bash
docker exec laravel-app php artisan view:clear
```

- [ ] **Step 4: Commit**

```bash
git add resources/views/components/product-card.blade.php resources/views/site/produto-detalhes.blade.php
git commit -m "feat: views usam getDescontoPix() ao invés de 0.95 fixo"
```

---

## Task 10: JS de variantes — produto-detalhes.js

**Files:**
- Modify: `public/js/produto-detalhes.js`

- [ ] **Step 1: Substituir 0.95 fixo pelo data-attribute**

Em `public/js/produto-detalhes.js`, localize os dois blocos que calculam `pixPreco` com `* 0.95` (adicionados anteriormente).

**Bloco 1 — atualização de variante selecionada** (após o segundo `priceEl = document.getElementById('price-pix')`):

Substitua:
```js
var pixPreco = varianteEncontrada.preco_efetivo * 0.95;
```

Por:
```js
var descontoPix = parseFloat(priceEl.dataset.descontoPix) || 5;
var pixPreco = varianteEncontrada.preco_efetivo * (1 - descontoPix / 100);
```

- [ ] **Step 2: Rodar todos os testes finais**

```bash
docker exec laravel-app php artisan test --filter=AdminDescontoPixTest
```

Saída esperada: todos os 9 testes passando.

```bash
docker exec laravel-app php artisan test
```

Saída esperada: suite completa passando, sem regressões.

- [ ] **Step 3: Commit final**

```bash
git add public/js/produto-detalhes.js
git commit -m "feat: JS variantes lê desconto PIX do data-attribute em vez de 0.95 fixo"
```
