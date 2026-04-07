# Cupom de Desconto — Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Adicionar sistema completo de cupons de desconto (percentual e fixo) com campo AJAX no checkout, rastreamento de usos por usuário, e painel admin de gerenciamento.

**Architecture:** Tabela `cupons` + `cupom_usos` + 2 novos campos em `pedidos`. `CupomController` valida e aplica/remove o cupom no pedido ativo via AJAX. `AdminCupomController` faz CRUD. O desconto é subtraído em `MercadoPagoCheckoutController::prepare()` e os usos são registrados no webhook de pagamento confirmado.

**Tech Stack:** Laravel 12, PHP 8.3, PostgreSQL, Blade + jQuery (padrão do projeto), Tailwind CSS 4

---

## Mapa de Arquivos

| Arquivo | Ação |
|---|---|
| `database/migrations/2026_04_06_100000_create_cupons_table.php` | Criar |
| `database/migrations/2026_04_06_100001_create_cupom_usos_table.php` | Criar |
| `database/migrations/2026_04_06_100002_add_cupom_fields_to_pedidos.php` | Criar |
| `app/Models/Cupom.php` | Criar |
| `app/Models/CupomUso.php` | Criar |
| `app/Models/Pedido.php` | Modificar (fillable + casts) |
| `app/Http/Controllers/CupomController.php` | Criar |
| `app/Http/Controllers/AdminCupomController.php` | Criar |
| `app/Http/Controllers/MercadoPagoCheckoutController.php` | Modificar (prepare + persistPayment) |
| `app/Http/Controllers/CarrinhoController.php` | Modificar (recalcularValorTotal) |
| `routes/web.php` | Modificar (rotas cupom + admin cupons) |
| `resources/views/site/finalizar-compra.blade.php` | Modificar (campo cupom + JS) |
| `resources/views/admin/cupons.blade.php` | Criar |
| `resources/views/includes/header-admin.blade.php` | Modificar (link nav) |
| `tests/Feature/CupomCheckoutTest.php` | Criar |

---

### Task 1: Migrations

**Files:**
- Create: `database/migrations/2026_04_06_100000_create_cupons_table.php`
- Create: `database/migrations/2026_04_06_100001_create_cupom_usos_table.php`
- Create: `database/migrations/2026_04_06_100002_add_cupom_fields_to_pedidos.php`

- [ ] **Step 1: Criar migration da tabela cupons**

```php
<?php
// database/migrations/2026_04_06_100000_create_cupons_table.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('cupons', function (Blueprint $table) {
            $table->id();
            $table->string('codigo', 50)->unique();
            $table->enum('tipo', ['percentual', 'fixo']);
            $table->decimal('valor', 10, 2);
            $table->decimal('valor_minimo_pedido', 10, 2)->nullable();
            $table->unsignedInteger('limite_usos')->nullable();
            $table->unsignedInteger('usos_realizados')->default(0);
            $table->date('valido_ate')->nullable();
            $table->boolean('ativo')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cupons');
    }
};
```

- [ ] **Step 2: Criar migration da tabela cupom_usos**

```php
<?php
// database/migrations/2026_04_06_100001_create_cupom_usos_table.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('cupom_usos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cupom_id')->constrained('cupons')->cascadeOnDelete();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->foreignId('pedido_id')->constrained('pedidos')->cascadeOnDelete();
            $table->foreign('user_id')->references('id')->on('users')->nullOnDelete();
            $table->timestamp('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cupom_usos');
    }
};
```

- [ ] **Step 3: Criar migration de alteração na tabela pedidos**

```php
<?php
// database/migrations/2026_04_06_100002_add_cupom_fields_to_pedidos.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('pedidos', function (Blueprint $table) {
            $table->string('cupom_codigo', 50)->nullable()->after('frete_valor');
            $table->decimal('valor_desconto', 10, 2)->default(0)->after('cupom_codigo');
        });
    }

    public function down(): void
    {
        Schema::table('pedidos', function (Blueprint $table) {
            $table->dropColumn(['cupom_codigo', 'valor_desconto']);
        });
    }
};
```

- [ ] **Step 4: Rodar migrations**

```bash
docker exec laravel-app php artisan migrate --force
```

Saída esperada: três migrations novas listadas como `Migrating` → `Migrated`.

- [ ] **Step 5: Commit**

```bash
git add database/migrations/2026_04_06_100000_create_cupons_table.php \
        database/migrations/2026_04_06_100001_create_cupom_usos_table.php \
        database/migrations/2026_04_06_100002_add_cupom_fields_to_pedidos.php
git commit -m "feat: add cupons, cupom_usos tables and cupom fields to pedidos"
```

---

### Task 2: Models

**Files:**
- Create: `app/Models/Cupom.php`
- Create: `app/Models/CupomUso.php`
- Modify: `app/Models/Pedido.php`

- [ ] **Step 1: Criar model Cupom**

```php
<?php
// app/Models/Cupom.php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Cupom extends Model
{
    protected $fillable = [
        'codigo',
        'tipo',
        'valor',
        'valor_minimo_pedido',
        'limite_usos',
        'usos_realizados',
        'valido_ate',
        'ativo',
    ];

    protected $casts = [
        'ativo'    => 'boolean',
        'valido_ate' => 'date',
        'valor'    => 'decimal:2',
        'valor_minimo_pedido' => 'decimal:2',
    ];

    public function setCodigoAttribute(string $value): void
    {
        $this->attributes['codigo'] = strtoupper(trim($value));
    }

    public function usos(): HasMany
    {
        return $this->hasMany(CupomUso::class);
    }

    /**
     * Scope para cupons válidos: ativo + não expirado.
     */
    public function scopeValido($query)
    {
        return $query->where('ativo', true)
            ->where(function ($q) {
                $q->whereNull('valido_ate')->orWhere('valido_ate', '>=', now()->toDateString());
            });
    }

    /**
     * Calcula o valor do desconto para um dado subtotal.
     * Nunca retorna mais que o subtotal.
     */
    public function calcularDesconto(float $subtotal): float
    {
        if ($this->tipo === 'percentual') {
            return round($subtotal * ((float) $this->valor / 100), 2);
        }

        return min((float) $this->valor, $subtotal);
    }
}
```

- [ ] **Step 2: Criar model CupomUso**

```php
<?php
// app/Models/CupomUso.php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CupomUso extends Model
{
    public $timestamps = false;

    protected $fillable = ['cupom_id', 'user_id', 'pedido_id'];

    protected $casts = [
        'created_at' => 'datetime',
    ];

    public function cupom(): BelongsTo
    {
        return $this->belongsTo(Cupom::class);
    }

    public function pedido(): BelongsTo
    {
        return $this->belongsTo(Pedido::class);
    }
}
```

- [ ] **Step 3: Atualizar fillable e casts em Pedido**

Em `app/Models/Pedido.php`, adicionar `cupom_codigo` e `valor_desconto` ao `$fillable` e `$casts`:

```php
// app/Models/Pedido.php  — somente as seções modificadas

protected $fillable = [
    'user_id',
    'endereco_id',
    'status',
    'valor_total',
    'frete_tipo',
    'frete_valor',
    'cupom_codigo',      // novo
    'valor_desconto',    // novo
    'customer_name',
    'customer_email',
    'customer_phone',
    'guest_token',
    'checkout_mode',
    'codigo_rastreio',
];

protected $casts = [
    'valor_total'     => 'decimal:2',
    'frete_valor'     => 'decimal:2',
    'valor_desconto'  => 'decimal:2',  // novo
];
```

- [ ] **Step 4: Commit**

```bash
git add app/Models/Cupom.php app/Models/CupomUso.php app/Models/Pedido.php
git commit -m "feat: add Cupom and CupomUso models, update Pedido fillable"
```

---

### Task 3: CupomController e Rotas

**Files:**
- Create: `app/Http/Controllers/CupomController.php`
- Modify: `routes/web.php`

- [ ] **Step 1: Criar CupomController**

```php
<?php
// app/Http/Controllers/CupomController.php
namespace App\Http\Controllers;

use App\Models\Cupom;
use App\Services\CheckoutOrderService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CupomController extends Controller
{
    public function __construct(private CheckoutOrderService $checkoutOrderService) {}

    public function aplicar(Request $request): JsonResponse
    {
        $request->validate(['codigo' => 'required|string|max:50']);

        $carrinho = $this->checkoutOrderService->resolveActiveOrder($request, ['itens']);

        if (!$carrinho) {
            return response()->json(['success' => false, 'message' => 'Carrinho não encontrado.'], 422);
        }

        $subtotal = $carrinho->itens->sum(fn ($item) => (float) $item->preco * (int) $item->quantidade);

        $cupom = Cupom::whereRaw('UPPER(codigo) = ?', [strtoupper(trim($request->codigo))])->first();

        if (!$cupom) {
            return response()->json(['success' => false, 'message' => 'Cupom inválido.'], 422);
        }

        if (!$cupom->ativo) {
            return response()->json(['success' => false, 'message' => 'Este cupom está inativo.'], 422);
        }

        if ($cupom->valido_ate && $cupom->valido_ate->isPast()) {
            return response()->json(['success' => false, 'message' => 'Este cupom está expirado.'], 422);
        }

        if ($cupom->limite_usos !== null && $cupom->usos_realizados >= $cupom->limite_usos) {
            return response()->json(['success' => false, 'message' => 'Este cupom atingiu o limite de usos.'], 422);
        }

        if (Auth::check()) {
            $jaUsou = \App\Models\CupomUso::where('cupom_id', $cupom->id)
                ->where('user_id', Auth::id())
                ->exists();
            if ($jaUsou) {
                return response()->json(['success' => false, 'message' => 'Você já utilizou este cupom.'], 422);
            }
        }

        if ($cupom->valor_minimo_pedido !== null && $subtotal < (float) $cupom->valor_minimo_pedido) {
            $minFormatado = number_format((float) $cupom->valor_minimo_pedido, 2, ',', '.');
            return response()->json([
                'success' => false,
                'message' => "Este cupom exige pedido mínimo de R$ {$minFormatado}.",
            ], 422);
        }

        $desconto = $cupom->calcularDesconto($subtotal);

        $carrinho->update([
            'cupom_codigo'   => $cupom->codigo,
            'valor_desconto' => $desconto,
        ]);

        $freteValor = (float) ($carrinho->frete_valor ?? 0);
        $novoTotal  = max(0, round($subtotal - $desconto + $freteValor, 2));

        $tipoLabel = $cupom->tipo === 'percentual'
            ? number_format((float) $cupom->valor, 0) . '% de desconto'
            : 'R$ ' . number_format((float) $cupom->valor, 2, ',', '.') . ' de desconto';

        return response()->json([
            'success'   => true,
            'codigo'    => $cupom->codigo,
            'desconto'  => number_format($desconto, 2, ',', '.'),
            'novo_total' => number_format($novoTotal, 2, ',', '.'),
            'mensagem'  => "Cupom {$cupom->codigo} aplicado — {$tipoLabel}",
        ]);
    }

    public function remover(Request $request): JsonResponse
    {
        $carrinho = $this->checkoutOrderService->resolveActiveOrder($request, ['itens']);

        if (!$carrinho) {
            return response()->json(['success' => false, 'message' => 'Carrinho não encontrado.'], 422);
        }

        $carrinho->update(['cupom_codigo' => null, 'valor_desconto' => 0]);

        $subtotal   = $carrinho->itens->sum(fn ($item) => (float) $item->preco * (int) $item->quantidade);
        $freteValor = (float) ($carrinho->frete_valor ?? 0);
        $novoTotal  = round($subtotal + $freteValor, 2);

        return response()->json([
            'success'    => true,
            'novo_total' => number_format($novoTotal, 2, ',', '.'),
        ]);
    }
}
```

- [ ] **Step 2: Adicionar rotas públicas em routes/web.php**

Localizar a seção `// Rota do checkout` (linha ~51) e adicionar após a rota do finalizar-compra:

```php
// Cupons de desconto
Route::post('/cupom/aplicar', [App\Http\Controllers\CupomController::class, 'aplicar'])->name('cupom.aplicar');
Route::post('/cupom/remover', [App\Http\Controllers\CupomController::class, 'remover'])->name('cupom.remover');
```

- [ ] **Step 3: Testar rotas**

```bash
docker exec laravel-app php artisan route:list --name=cupom
```

Saída esperada: duas linhas com `cupom.aplicar` e `cupom.remover` como POST.

- [ ] **Step 4: Commit**

```bash
git add app/Http/Controllers/CupomController.php routes/web.php
git commit -m "feat: add CupomController with aplicar/remover endpoints"
```

---

### Task 4: Testes do CupomController

**Files:**
- Create: `tests/Feature/CupomCheckoutTest.php`

- [ ] **Step 1: Criar arquivo de teste**

```php
<?php
// tests/Feature/CupomCheckoutTest.php
namespace Tests\Feature;

use App\Models\Cupom;
use App\Models\ItemPedido;
use App\Models\Pedido;
use App\Models\Produto;
use App\Models\User;
use App\Services\CheckoutOrderService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CupomCheckoutTest extends TestCase
{
    use RefreshDatabase;

    private function makeCart(User $user, float $preco = 100.00, int $qtd = 1): Pedido
    {
        $produto = Produto::factory()->create(['preco' => $preco, 'estoque' => 10, 'ativo' => true]);
        $pedido  = Pedido::factory()->create(['user_id' => $user->id, 'status' => 'carrinho', 'valor_total' => $preco * $qtd, 'valor_desconto' => 0]);
        ItemPedido::factory()->create(['pedido_id' => $pedido->id, 'produto_id' => $produto->id, 'preco' => $preco, 'quantidade' => $qtd]);
        return $pedido;
    }

    private function makeCupom(array $attrs = []): Cupom
    {
        return Cupom::create(array_merge([
            'codigo'       => 'PROMO10',
            'tipo'         => 'percentual',
            'valor'        => 10,
            'ativo'        => true,
            'valido_ate'   => null,
            'limite_usos'  => null,
            'valor_minimo_pedido' => null,
        ], $attrs));
    }

    public function test_aplicar_cupom_percentual_retorna_desconto_correto(): void
    {
        $user   = User::factory()->create();
        $this->makeCart($user, 100.00);

        $cupom = $this->makeCupom(['tipo' => 'percentual', 'valor' => 10]);

        $this->actingAs($user)
            ->postJson(route('cupom.aplicar'), ['codigo' => 'PROMO10'])
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('desconto', '10,00')
            ->assertJsonPath('novo_total', '90,00');
    }

    public function test_aplicar_cupom_fixo_retorna_desconto_correto(): void
    {
        $user = User::factory()->create();
        $this->makeCart($user, 100.00);

        $cupom = $this->makeCupom(['codigo' => 'FIXO20', 'tipo' => 'fixo', 'valor' => 20]);

        $this->actingAs($user)
            ->postJson(route('cupom.aplicar'), ['codigo' => 'FIXO20'])
            ->assertOk()
            ->assertJsonPath('desconto', '20,00')
            ->assertJsonPath('novo_total', '80,00');
    }

    public function test_cupom_invalido_retorna_422(): void
    {
        $user = User::factory()->create();
        $this->makeCart($user);

        $this->actingAs($user)
            ->postJson(route('cupom.aplicar'), ['codigo' => 'INEXISTENTE'])
            ->assertStatus(422)
            ->assertJsonPath('success', false);
    }

    public function test_cupom_expirado_retorna_422(): void
    {
        $user = User::factory()->create();
        $this->makeCart($user);

        $this->makeCupom(['codigo' => 'VENCIDO', 'valido_ate' => now()->subDay()->toDateString()]);

        $this->actingAs($user)
            ->postJson(route('cupom.aplicar'), ['codigo' => 'VENCIDO'])
            ->assertStatus(422);
    }

    public function test_cupom_com_limite_esgotado_retorna_422(): void
    {
        $user = User::factory()->create();
        $this->makeCart($user);

        $this->makeCupom(['codigo' => 'ESGOTADO', 'limite_usos' => 5, 'usos_realizados' => 5]);

        $this->actingAs($user)
            ->postJson(route('cupom.aplicar'), ['codigo' => 'ESGOTADO'])
            ->assertStatus(422);
    }

    public function test_usuario_nao_pode_reusar_cupom(): void
    {
        $user  = User::factory()->create();
        $pedido = $this->makeCart($user);
        $cupom = $this->makeCupom();

        \App\Models\CupomUso::create([
            'cupom_id'  => $cupom->id,
            'user_id'   => $user->id,
            'pedido_id' => $pedido->id,
        ]);

        // Novo carrinho
        $this->makeCart($user);

        $this->actingAs($user)
            ->postJson(route('cupom.aplicar'), ['codigo' => 'PROMO10'])
            ->assertStatus(422)
            ->assertJsonPath('message', 'Você já utilizou este cupom.');
    }

    public function test_cupom_com_valor_minimo_nao_aplicado_retorna_422(): void
    {
        $user = User::factory()->create();
        $this->makeCart($user, 30.00); // subtotal = 30

        $this->makeCupom(['codigo' => 'MINIMO', 'valor_minimo_pedido' => 50.00]);

        $this->actingAs($user)
            ->postJson(route('cupom.aplicar'), ['codigo' => 'MINIMO'])
            ->assertStatus(422)
            ->assertJsonFragment(['message' => 'Este cupom exige pedido mínimo de R$ 50,00.']);
    }

    public function test_remover_cupom_zera_desconto(): void
    {
        $user    = User::factory()->create();
        $pedido  = $this->makeCart($user);
        $pedido->update(['cupom_codigo' => 'PROMO10', 'valor_desconto' => 10]);

        $this->actingAs($user)
            ->postJson(route('cupom.remover'))
            ->assertOk()
            ->assertJsonPath('success', true);

        $pedido->refresh();
        $this->assertNull($pedido->cupom_codigo);
        $this->assertEquals('0.00', $pedido->valor_desconto);
    }
}
```

- [ ] **Step 2: Rodar os testes**

```bash
docker exec laravel-app php artisan test --filter=CupomCheckoutTest
```

Saída esperada: 7 testes passando.

Se precisar de factories, verificar se `Pedido::factory()` e `User::factory()` já existem:
```bash
docker exec laravel-app php artisan test --filter=CupomCheckoutTest 2>&1 | head -40
```

- [ ] **Step 3: Commit**

```bash
git add tests/Feature/CupomCheckoutTest.php
git commit -m "test: add CupomCheckoutTest covering apply/remove/validation scenarios"
```

---

### Task 5: Atualizar MercadoPagoCheckoutController

**Files:**
- Modify: `app/Http/Controllers/MercadoPagoCheckoutController.php`

- [ ] **Step 1: Atualizar `prepare()` para subtrair o desconto**

Em `MercadoPagoCheckoutController::prepare()`, linha ~68-69, substituir:

```php
// Antes:
$subtotal = $pedido->itens->sum(fn ($item) => (float) $item->preco * (int) $item->quantidade);
$valorTotal = round($subtotal + (float) $frete['valor'], 2);
```

Por:

```php
// Depois:
$subtotal = $pedido->itens->sum(fn ($item) => (float) $item->preco * (int) $item->quantidade);
$desconto = (float) ($pedido->valor_desconto ?? 0);
$valorTotal = round(max(0, $subtotal - $desconto) + (float) $frete['valor'], 2);
```

Também atualizar o array retornado (`'subtotal'` pode continuar igual — é o subtotal bruto). Adicionar `'desconto'` no array da resposta:

```php
// No return response()->json, dentro de 'checkout' => [...]:
'subtotal'  => round($subtotal, 2),
'desconto'  => round($desconto, 2),    // adicionar esta linha
'frete'     => $frete,
```

- [ ] **Step 2: Atualizar `persistPayment()` para registrar uso do cupom**

Em `MercadoPagoCheckoutController::persistPayment()`, localizar:

```php
if ($newStatus === 'pago') {
    $this->affiliateService->handleOrderPaid($pedido);
}
```

Substituir por:

```php
if ($newStatus === 'pago') {
    $this->affiliateService->handleOrderPaid($pedido);

    if ($pedido->cupom_codigo) {
        \App\Models\Cupom::whereRaw('UPPER(codigo) = ?', [strtoupper($pedido->cupom_codigo)])
            ->increment('usos_realizados');

        $cupomObj = \App\Models\Cupom::whereRaw('UPPER(codigo) = ?', [strtoupper($pedido->cupom_codigo)])->first();
        if ($cupomObj) {
            \App\Models\CupomUso::firstOrCreate([
                'cupom_id'  => $cupomObj->id,
                'pedido_id' => $pedido->id,
            ], [
                'user_id' => $pedido->user_id,
            ]);
        }
    }
}
```

- [ ] **Step 3: Commit**

```bash
git add app/Http/Controllers/MercadoPagoCheckoutController.php
git commit -m "feat: apply cupom discount in checkout prepare and record usage on payment confirmed"
```

---

### Task 6: Atualizar CarrinhoController

**Files:**
- Modify: `app/Http/Controllers/CarrinhoController.php`

- [ ] **Step 1: Atualizar `recalcularValorTotal()` para incluir desconto**

Localizar o método `recalcularValorTotal` (~linha 343) e substituir por:

```php
private function recalcularValorTotal($carrinho)
{
    $subtotal = $carrinho->itens()->get()->sum(fn ($item) => $item->quantidade * $item->preco);
    $desconto = (float) ($carrinho->valor_desconto ?? 0);
    $carrinho->update(['valor_total' => max(0, $subtotal - $desconto)]);
}
```

- [ ] **Step 2: Commit**

```bash
git add app/Http/Controllers/CarrinhoController.php
git commit -m "feat: account for cupom discount in cart total recalculation"
```

---

### Task 7: Frontend — Campo de Cupom no Checkout

**Files:**
- Modify: `resources/views/site/finalizar-compra.blade.php`

- [ ] **Step 1: Adicionar campo de cupom no HTML do resumo do pedido**

Localizar no arquivo (linha ~277):
```html
                            <div class="flex justify-between text-sm">
                                <span class="text-gray-600">Subtotal:</span>
                                <span class="font-semibold">R$ {{ number_format($subtotalProdutos, 2, ',', '.') }}</span>
                            </div>
```

Adicionar **antes** deste bloco:

```html
                            <!-- Cupom de desconto -->
                            <div class="border border-[var(--color-lab-border)] p-3 mb-1">
                                <div id="cupom-form">
                                    <div class="flex gap-2">
                                        <input
                                            type="text"
                                            id="cupom-input"
                                            placeholder="Código do cupom"
                                            class="flex-1 border border-[var(--color-lab-border)] px-3 py-2 text-sm font-mono uppercase focus:outline-none focus:border-black"
                                            maxlength="50"
                                        >
                                        <button
                                            type="button"
                                            id="cupom-btn"
                                            class="bg-black text-white px-4 py-2 text-xs font-mono uppercase tracking-widest hover:bg-gray-900 transition-colors whitespace-nowrap"
                                        >
                                            Aplicar
                                        </button>
                                    </div>
                                    <p id="cupom-erro" class="text-red-600 text-xs mt-1 hidden"></p>
                                </div>
                                <div id="cupom-aplicado" class="hidden">
                                    <div class="flex items-center justify-between">
                                        <span class="text-sm font-mono text-green-700" id="cupom-label"></span>
                                        <button type="button" id="cupom-remover-btn" class="text-xs text-gray-500 underline hover:text-black ml-2">remover</button>
                                    </div>
                                </div>
                            </div>
```

- [ ] **Step 2: Adicionar linha de desconto no bloco de totais**

Localizar (linha ~277-327) o bloco de subtotal + frete + total. Após a linha de subtotal e antes do frete, adicionar linha de desconto:

```html
                            <div class="flex justify-between text-sm" id="desconto-resumo" style="display:none!important">
                                <span class="text-gray-600">Desconto:</span>
                                <span class="font-semibold text-green-700" id="desconto-valor">- R$ 0,00</span>
                            </div>
```

Nota: usar `style="display:none!important"` porque Tailwind não tem `hidden` com `id` fácil de toggle via jQuery `.show()`.

- [ ] **Step 3: Adicionar variável JS inicial e funções de cupom**

Após a linha `const subtotalProdutos = parseFloat('{{ $subtotalProdutos }}');` (linha ~348), adicionar:

```javascript
const cupomAplicado = {
    codigo: @json($carrinho->cupom_codigo),
    desconto: parseFloat('{{ $carrinho->valor_desconto ?? 0 }}'),
};
```

- [ ] **Step 4: Adicionar funções JS de aplicar/remover cupom**

Localizar a função `atualizarFreteSelecionado` (linha ~911). Substituí-la por versão que considera desconto, e adicionar funções de cupom **antes** dela:

```javascript
// --- Cupom de desconto ---
let descontoAtual = cupomAplicado.desconto || 0;

function formatarBRL(valor) {
    return 'R$ ' + valor.toFixed(2).replace('.', ',');
}

function atualizarTotalComDesconto(frete) {
    const fv = (typeof frete === 'number') ? frete : freteAtual;
    const total = Math.max(0, subtotalProdutos - descontoAtual + fv);
    $('#total-valor').text(formatarBRL(total));
}

function mostrarCupomAplicado(codigo, desconto, mensagem) {
    descontoAtual = desconto;
    $('#cupom-form').addClass('hidden');
    $('#cupom-aplicado').removeClass('hidden');
    $('#cupom-label').text('✓ ' + mensagem);
    $('#desconto-valor').text('- R$ ' + parseFloat(desconto).toFixed(2).replace('.', ','));
    $('#desconto-resumo').show();
    atualizarTotalComDesconto();
}

function restaurarFormCupom() {
    descontoAtual = 0;
    $('#cupom-form').removeClass('hidden');
    $('#cupom-aplicado').addClass('hidden');
    $('#cupom-input').val('');
    $('#cupom-erro').addClass('hidden').text('');
    $('#desconto-resumo').hide();
    atualizarTotalComDesconto();
}

$(document).ready(function () {
    // Restaurar estado se cupom já estava aplicado (ex: usuário voltou à página)
    if (cupomAplicado.codigo && cupomAplicado.desconto > 0) {
        mostrarCupomAplicado(cupomAplicado.codigo, cupomAplicado.desconto, cupomAplicado.codigo + ' aplicado');
    }

    $('#cupom-btn').on('click', function () {
        const codigo = $('#cupom-input').val().trim();
        if (!codigo) return;

        $('#cupom-btn').prop('disabled', true).text('...');
        $('#cupom-erro').addClass('hidden').text('');

        $.ajax({
            url: '{{ route("cupom.aplicar") }}',
            method: 'POST',
            data: { codigo: codigo, _token: $('meta[name="csrf-token"]').attr('content') },
            success: function (res) {
                mostrarCupomAplicado(res.codigo, parseFloat(res.desconto.replace(',', '.')), res.mensagem);
            },
            error: function (xhr) {
                const msg = xhr.responseJSON?.message || 'Cupom inválido.';
                $('#cupom-erro').removeClass('hidden').text(msg);
            },
            complete: function () {
                $('#cupom-btn').prop('disabled', false).text('Aplicar');
            },
        });
    });

    $('#cupom-remover-btn').on('click', function () {
        $.ajax({
            url: '{{ route("cupom.remover") }}',
            method: 'POST',
            data: { _token: $('meta[name="csrf-token"]').attr('content') },
            success: function () {
                restaurarFormCupom();
            },
        });
    });
});
```

- [ ] **Step 5: Atualizar `atualizarFreteSelecionado` para considerar desconto**

Substituir a função existente (linha ~911-915):

```javascript
// Antes:
function atualizarFreteSelecionado(valor) {
    $('#frete-valor').text('R$ ' + valor.toFixed(2).replace('.', ','));
    const total = subtotalProdutos + valor;
    $('#total-valor').text('R$ ' + total.toFixed(2).replace('.', ','));
}
```

Por:

```javascript
let freteAtual = (typeof savedFreteValue === 'number' && savedFreteValue > 0) ? savedFreteValue : 0;

function atualizarFreteSelecionado(valor) {
    freteAtual = valor;
    $('#frete-valor').text(formatarBRL(valor));
    atualizarTotalComDesconto(valor);
}
```

- [ ] **Step 6: Limpar view cache e testar manualmente**

```bash
docker exec laravel-app php artisan view:clear
docker exec laravel-app php artisan cache:clear
```

Abrir `/finalizar-compra` no browser, verificar que o campo de cupom aparece no resumo.

- [ ] **Step 7: Commit**

```bash
git add resources/views/site/finalizar-compra.blade.php
git commit -m "feat: add cupom discount field and JS to finalizar-compra checkout"
```

---

### Task 8: Admin — AdminCupomController

**Files:**
- Create: `app/Http/Controllers/AdminCupomController.php`
- Modify: `routes/web.php`

- [ ] **Step 1: Criar AdminCupomController**

```php
<?php
// app/Http/Controllers/AdminCupomController.php
namespace App\Http\Controllers;

use App\Models\Cupom;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AdminCupomController extends Controller
{
    private function checkAdmin()
    {
        if (!Auth::check()) {
            abort(401);
        }
    }

    public function index()
    {
        $this->checkAdmin();
        $cupons = Cupom::orderByDesc('created_at')->get();
        return view('admin.cupons', compact('cupons'));
    }

    public function store(Request $request)
    {
        $this->checkAdmin();
        $data = $request->validate([
            'codigo'               => 'required|string|max:50|unique:cupons,codigo',
            'tipo'                 => 'required|in:percentual,fixo',
            'valor'                => 'required|numeric|min:0.01',
            'valor_minimo_pedido'  => 'nullable|numeric|min:0',
            'limite_usos'          => 'nullable|integer|min:1',
            'valido_ate'           => 'nullable|date|after_or_equal:today',
            'ativo'                => 'boolean',
        ]);

        $data['codigo'] = strtoupper(trim($data['codigo']));
        $data['ativo']  = $request->boolean('ativo', true);

        $cupom = Cupom::create($data);

        return response()->json(['success' => true, 'cupom' => $cupom]);
    }

    public function update(Request $request, int $id)
    {
        $this->checkAdmin();
        $cupom = Cupom::findOrFail($id);

        $data = $request->validate([
            'codigo'               => 'required|string|max:50|unique:cupons,codigo,' . $id,
            'tipo'                 => 'required|in:percentual,fixo',
            'valor'                => 'required|numeric|min:0.01',
            'valor_minimo_pedido'  => 'nullable|numeric|min:0',
            'limite_usos'          => 'nullable|integer|min:1',
            'valido_ate'           => 'nullable|date',
            'ativo'                => 'boolean',
        ]);

        $data['codigo'] = strtoupper(trim($data['codigo']));
        $data['ativo']  = $request->boolean('ativo', $cupom->ativo);

        $cupom->update($data);

        return response()->json(['success' => true, 'cupom' => $cupom]);
    }

    public function destroy(int $id)
    {
        $this->checkAdmin();
        Cupom::findOrFail($id)->delete();
        return response()->json(['success' => true]);
    }

    public function toggle(int $id)
    {
        $this->checkAdmin();
        $cupom = Cupom::findOrFail($id);
        $cupom->update(['ativo' => !$cupom->ativo]);
        return response()->json(['success' => true, 'ativo' => $cupom->ativo]);
    }
}
```

- [ ] **Step 2: Adicionar rotas admin em routes/web.php**

Dentro do grupo `Route::prefix('admin')->name('admin.')->...` (logo antes do fechamento `});` do grupo admin, após as rotas de categorias ~linha 100), adicionar:

```php
    // Cupons
    Route::get('/cupons',              [AdminCupomController::class, 'index'])->name('cupons.index');
    Route::post('/cupons',             [AdminCupomController::class, 'store'])->name('cupons.store');
    Route::put('/cupons/{id}',         [AdminCupomController::class, 'update'])->name('cupons.update');
    Route::delete('/cupons/{id}',      [AdminCupomController::class, 'destroy'])->name('cupons.destroy');
    Route::post('/cupons/{id}/toggle', [AdminCupomController::class, 'toggle'])->name('cupons.toggle');
```

Adicionar import no topo do routes/web.php junto com os outros:
```php
use App\Http\Controllers\AdminCupomController;
```

- [ ] **Step 3: Verificar rotas**

```bash
docker exec laravel-app php artisan route:list --name=admin.cupons
```

Saída esperada: 5 rotas listadas.

- [ ] **Step 4: Commit**

```bash
git add app/Http/Controllers/AdminCupomController.php routes/web.php
git commit -m "feat: add AdminCupomController and admin cupons routes"
```

---

### Task 9: View Admin — Painel de Cupons

**Files:**
- Create: `resources/views/admin/cupons.blade.php`

- [ ] **Step 1: Criar view do painel de cupons**

```blade
{{-- resources/views/admin/cupons.blade.php --}}
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Cupons — Admin JFX Tech</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-[var(--color-lab-bg)] text-[var(--color-lab-ink)] antialiased">
@include('includes.header-admin')

<div class="flex min-h-[calc(100vh-4rem)]">
    {{-- Sidebar já inclusa no header-admin --}}
    <main class="flex-1 p-8">
        <div class="flex items-center justify-between mb-8">
            <h1 class="font-mono text-2xl font-bold uppercase tracking-widest">Cupons</h1>
            <button id="btn-novo-cupom" class="bg-black text-white px-5 py-2 font-mono text-xs uppercase tracking-widest hover:bg-gray-900 transition-colors">
                + Novo Cupom
            </button>
        </div>

        <div class="border border-[var(--color-lab-border)]">
            <table class="w-full text-sm font-mono" id="tabela-cupons">
                <thead class="bg-black text-white">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs uppercase tracking-widest">Código</th>
                        <th class="px-4 py-3 text-left text-xs uppercase tracking-widest">Tipo</th>
                        <th class="px-4 py-3 text-left text-xs uppercase tracking-widest">Valor</th>
                        <th class="px-4 py-3 text-left text-xs uppercase tracking-widest">Mínimo</th>
                        <th class="px-4 py-3 text-left text-xs uppercase tracking-widest">Usos</th>
                        <th class="px-4 py-3 text-left text-xs uppercase tracking-widest">Validade</th>
                        <th class="px-4 py-3 text-left text-xs uppercase tracking-widest">Status</th>
                        <th class="px-4 py-3 text-left text-xs uppercase tracking-widest">Ações</th>
                    </tr>
                </thead>
                <tbody>
                @forelse ($cupons as $cupom)
                    <tr class="border-t border-[var(--color-lab-border)] hover:bg-gray-50 cupom-row" data-id="{{ $cupom->id }}">
                        <td class="px-4 py-3 font-bold">{{ $cupom->codigo }}</td>
                        <td class="px-4 py-3">{{ $cupom->tipo === 'percentual' ? 'Percentual' : 'Fixo' }}</td>
                        <td class="px-4 py-3">
                            {{ $cupom->tipo === 'percentual' ? number_format($cupom->valor, 0) . '%' : 'R$ ' . number_format($cupom->valor, 2, ',', '.') }}
                        </td>
                        <td class="px-4 py-3">
                            {{ $cupom->valor_minimo_pedido ? 'R$ ' . number_format($cupom->valor_minimo_pedido, 2, ',', '.') : '—' }}
                        </td>
                        <td class="px-4 py-3">
                            {{ $cupom->usos_realizados }}{{ $cupom->limite_usos ? '/' . $cupom->limite_usos : '' }}
                        </td>
                        <td class="px-4 py-3">{{ $cupom->valido_ate ? $cupom->valido_ate->format('d/m/Y') : '—' }}</td>
                        <td class="px-4 py-3">
                            <button
                                class="toggle-status px-2 py-1 text-xs uppercase tracking-widest border {{ $cupom->ativo ? 'bg-black text-white border-black' : 'bg-white text-gray-500 border-gray-300' }}"
                                data-id="{{ $cupom->id }}"
                                data-ativo="{{ $cupom->ativo ? '1' : '0' }}"
                            >
                                {{ $cupom->ativo ? 'Ativo' : 'Inativo' }}
                            </button>
                        </td>
                        <td class="px-4 py-3 space-x-2">
                            <button class="btn-editar text-xs underline hover:text-black" data-cupom='@json($cupom)'>Editar</button>
                            <button class="btn-deletar text-xs underline text-red-600 hover:text-red-800" data-id="{{ $cupom->id }}">Excluir</button>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="px-4 py-8 text-center text-gray-500">Nenhum cupom cadastrado.</td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </main>
</div>

{{-- Modal criar/editar --}}
<div id="modal-cupom" class="fixed inset-0 bg-black/50 z-50 hidden items-center justify-center">
    <div class="bg-white border border-black p-8 w-full max-w-lg mx-4">
        <div class="flex items-center justify-between mb-6">
            <h2 class="font-mono text-lg font-bold uppercase tracking-widest" id="modal-titulo">Novo Cupom</h2>
            <button id="modal-fechar" class="text-gray-400 hover:text-black text-xl">&times;</button>
        </div>
        <form id="form-cupom" class="space-y-4">
            <input type="hidden" id="cupom-id" value="">
            <div>
                <label class="block text-xs font-mono uppercase tracking-widest mb-1">Código *</label>
                <input type="text" id="f-codigo" name="codigo" class="w-full border border-[var(--color-lab-border)] px-3 py-2 font-mono uppercase focus:outline-none focus:border-black" maxlength="50" required>
            </div>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-mono uppercase tracking-widest mb-1">Tipo *</label>
                    <select id="f-tipo" name="tipo" class="w-full border border-[var(--color-lab-border)] px-3 py-2 font-mono focus:outline-none focus:border-black" required>
                        <option value="percentual">Percentual (%)</option>
                        <option value="fixo">Valor Fixo (R$)</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-mono uppercase tracking-widest mb-1">Valor *</label>
                    <input type="number" id="f-valor" name="valor" class="w-full border border-[var(--color-lab-border)] px-3 py-2 font-mono focus:outline-none focus:border-black" step="0.01" min="0.01" required>
                </div>
            </div>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-mono uppercase tracking-widest mb-1">Pedido Mínimo (R$)</label>
                    <input type="number" id="f-minimo" name="valor_minimo_pedido" class="w-full border border-[var(--color-lab-border)] px-3 py-2 font-mono focus:outline-none focus:border-black" step="0.01" min="0">
                </div>
                <div>
                    <label class="block text-xs font-mono uppercase tracking-widest mb-1">Limite de Usos</label>
                    <input type="number" id="f-limite" name="limite_usos" class="w-full border border-[var(--color-lab-border)] px-3 py-2 font-mono focus:outline-none focus:border-black" min="1">
                </div>
            </div>
            <div>
                <label class="block text-xs font-mono uppercase tracking-widest mb-1">Válido até</label>
                <input type="date" id="f-validade" name="valido_ate" class="w-full border border-[var(--color-lab-border)] px-3 py-2 font-mono focus:outline-none focus:border-black">
            </div>
            <div class="flex items-center gap-2">
                <input type="checkbox" id="f-ativo" name="ativo" value="1" checked class="w-4 h-4">
                <label for="f-ativo" class="text-xs font-mono uppercase tracking-widest">Ativo</label>
            </div>
            <p id="form-erro" class="text-red-600 text-xs hidden"></p>
            <button type="submit" class="w-full bg-black text-white py-3 font-mono text-xs uppercase tracking-widest hover:bg-gray-900 transition-colors">
                Salvar
            </button>
        </form>
    </div>
</div>

{{-- Modal confirmar exclusão --}}
<div id="modal-deletar" class="fixed inset-0 bg-black/50 z-50 hidden items-center justify-center">
    <div class="bg-white border border-black p-8 w-full max-w-sm mx-4 text-center">
        <p class="font-mono text-sm mb-6">Tem certeza que deseja excluir este cupom?</p>
        <input type="hidden" id="deletar-id">
        <div class="flex gap-4 justify-center">
            <button id="confirmar-deletar" class="bg-black text-white px-6 py-2 font-mono text-xs uppercase tracking-widest hover:bg-gray-900">Excluir</button>
            <button id="cancelar-deletar" class="border border-black px-6 py-2 font-mono text-xs uppercase tracking-widest hover:bg-gray-100">Cancelar</button>
        </div>
    </div>
</div>

<script>
const routeCupons      = '{{ route("admin.cupons.index") }}';
const routeCuponsStore = '{{ route("admin.cupons.store") }}';

function cuponsUpdateUrl(id)  { return `/admin/cupons/${id}`; }
function cuponsToggleUrl(id)  { return `/admin/cupons/${id}/toggle`; }
function cuponsDeleteUrl(id)  { return `/admin/cupons/${id}`; }

function abrirModal(titulo, cupom = null) {
    $('#modal-titulo').text(titulo);
    $('#cupom-id').val(cupom ? cupom.id : '');
    $('#f-codigo').val(cupom ? cupom.codigo : '');
    $('#f-tipo').val(cupom ? cupom.tipo : 'percentual');
    $('#f-valor').val(cupom ? cupom.valor : '');
    $('#f-minimo').val(cupom ? (cupom.valor_minimo_pedido || '') : '');
    $('#f-limite').val(cupom ? (cupom.limite_usos || '') : '');
    $('#f-validade').val(cupom && cupom.valido_ate ? cupom.valido_ate.substring(0, 10) : '');
    $('#f-ativo').prop('checked', cupom ? !!cupom.ativo : true);
    $('#form-erro').addClass('hidden').text('');
    $('#modal-cupom').removeClass('hidden').addClass('flex');
}

function fecharModal() {
    $('#modal-cupom').addClass('hidden').removeClass('flex');
}

$(document).ready(function () {

    $('#btn-novo-cupom').on('click', function () {
        abrirModal('Novo Cupom');
    });

    $('#modal-fechar').on('click', fecharModal);
    $('#modal-cupom').on('click', function (e) {
        if ($(e.target).is('#modal-cupom')) fecharModal();
    });

    $(document).on('click', '.btn-editar', function () {
        const cupom = $(this).data('cupom');
        abrirModal('Editar Cupom', cupom);
    });

    $('#form-cupom').on('submit', function (e) {
        e.preventDefault();
        const id   = $('#cupom-id').val();
        const url  = id ? cuponsUpdateUrl(id) : routeCuponsStore;
        const meth = id ? 'PUT' : 'POST';

        $.ajax({
            url, method: meth,
            data: {
                _token:               $('meta[name="csrf-token"]').attr('content'),
                codigo:               $('#f-codigo').val(),
                tipo:                 $('#f-tipo').val(),
                valor:                $('#f-valor').val(),
                valor_minimo_pedido:  $('#f-minimo').val() || null,
                limite_usos:          $('#f-limite').val() || null,
                valido_ate:           $('#f-validade').val() || null,
                ativo:                $('#f-ativo').is(':checked') ? 1 : 0,
            },
            success: function () { window.location.reload(); },
            error: function (xhr) {
                const errors = xhr.responseJSON?.errors;
                const msg = errors
                    ? Object.values(errors).flat().join(' ')
                    : (xhr.responseJSON?.message || 'Erro ao salvar.');
                $('#form-erro').removeClass('hidden').text(msg);
            },
        });
    });

    $(document).on('click', '.toggle-status', function () {
        const id = $(this).data('id');
        $.post(cuponsToggleUrl(id), { _token: $('meta[name="csrf-token"]').attr('content') }, function () {
            window.location.reload();
        });
    });

    $(document).on('click', '.btn-deletar', function () {
        $('#deletar-id').val($(this).data('id'));
        $('#modal-deletar').removeClass('hidden').addClass('flex');
    });

    $('#cancelar-deletar').on('click', function () {
        $('#modal-deletar').addClass('hidden').removeClass('flex');
    });

    $('#confirmar-deletar').on('click', function () {
        const id = $('#deletar-id').val();
        $.ajax({
            url: cuponsDeleteUrl(id),
            method: 'DELETE',
            data: { _token: $('meta[name="csrf-token"]').attr('content') },
            success: function () { window.location.reload(); },
        });
    });

});
</script>
</body>
</html>
```

- [ ] **Step 2: Limpar cache de views**

```bash
docker exec laravel-app php artisan view:clear
```

- [ ] **Step 3: Commit**

```bash
git add resources/views/admin/cupons.blade.php
git commit -m "feat: add admin cupons management view with CRUD modals"
```

---

### Task 10: Nav Admin — Link Cupons

**Files:**
- Modify: `resources/views/includes/header-admin.blade.php`

- [ ] **Step 1: Adicionar link Cupons no menu lateral**

Localizar o bloco do link Afiliados (linha ~136) e adicionar **após** ele:

```html
                    <li>
                        <a href="{{ route('admin.cupons.index') }}" class="flex items-center space-x-3 px-3 py-2.5 font-mono text-xs uppercase tracking-widest transition-colors {{ request()->routeIs('admin.cupons*') ? 'bg-black text-white' : 'text-[var(--color-lab-muted)] hover:bg-gray-100 hover:text-black' }}">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 5H2v7l6.29 6.29c.94.94 2.48.94 3.42 0l3.58-3.58c.94-.94.94-2.48 0-3.42L9 5Z"/><path d="M6 9.01V9"/><path d="m15 5 6.3 6.3a2.4 2.4 0 0 1 0 3.4L17 19"/></svg>
                            <span>Cupons</span>
                        </a>
                    </li>
```

- [ ] **Step 2: Commit**

```bash
git add resources/views/includes/header-admin.blade.php
git commit -m "feat: add Cupons link to admin sidebar navigation"
```

---

### Task 11: Verificação End-to-End

- [ ] **Step 1: Rodar todos os testes**

```bash
docker exec laravel-app php artisan test
```

Saída esperada: todos os testes passando, incluindo `CupomCheckoutTest` (7 testes).

- [ ] **Step 2: Criar cupom via admin e testar no checkout**

1. Acessar `/admin/cupons`, clicar "+ Novo Cupom"
2. Criar: código `DESCONTO10`, tipo `percentual`, valor `10`, sem mínimo, sem limite
3. Adicionar produto ao carrinho (subtotal > 0)
4. Ir para `/finalizar-compra`
5. No campo de cupom, digitar `DESCONTO10`, clicar Aplicar
6. Verificar: linha "Desconto: - R$ X,XX" aparece, total atualiza
7. Selecionar frete — verificar que total considera tanto frete quanto desconto
8. Clicar remover — verificar que desconto some e total volta ao original

- [ ] **Step 3: Testar cenários de erro**

1. Digitar código inexistente → mensagem "Cupom inválido."
2. Criar cupom com `valido_ate` = ontem → testar → "Este cupom está expirado."
3. Criar cupom com `limite_usos = 1`, `usos_realizados = 1` → testar → "atingiu o limite"
4. Criar cupom com `valor_minimo_pedido = 999` → testar com carrinho pequeno → mensagem com valor mínimo

- [ ] **Step 4: Build de assets**

```bash
cd /var/www/html && npm run build
```
