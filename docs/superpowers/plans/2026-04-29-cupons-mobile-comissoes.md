# Cupons — Mobile, Métricas e Comissões — Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Redesenhar `/admin/cupons` com cards mobile-first, métricas de receita/comissão por cupom, e CRUD completo de pagamentos de comissão para parceiros.

**Architecture:** Duas novas migrations adicionam `comissao_percentual` à tabela `cupons` e criam `cupom_pagamentos` + FK nullable em `cupom_usos`. O `AdminCupomController` ganha 5 novos endpoints JSON e o `index` passa métricas calculadas para a view. A view é reescrita com cards Blade + modais JS (jQuery, padrão do projeto).

**Tech Stack:** Laravel 12, PHP 8.3, PostgreSQL, Blade, Tailwind CSS 4, jQuery (padrão do projeto). Testes: PHPUnit com `RefreshDatabase` + SQLite in-memory.

---

## Mapa de Arquivos

| Arquivo | Ação | Responsabilidade |
|---|---|---|
| `database/migrations/2026_04_29_000001_add_comissao_to_cupons.php` | Criar | Adiciona `comissao_percentual` à tabela `cupons` |
| `database/migrations/2026_04_29_000002_create_cupom_pagamentos.php` | Criar | Cria `cupom_pagamentos` + `cupom_pagamento_id` em `cupom_usos` |
| `app/Models/CupomPagamento.php` | Criar | Model de pagamento de comissão |
| `app/Models/Cupom.php` | Modificar | Adiciona `comissao_percentual` ao fillable/casts + relação `pagamentos` |
| `app/Models/CupomUso.php` | Modificar | Adiciona `cupom_pagamento_id` ao fillable + relação `pagamento` |
| `routes/web.php` | Modificar | 5 novas rotas admin de cupons/pagamentos |
| `app/Http/Controllers/AdminCupomController.php` | Modificar | `index` com métricas + 5 novos métodos |
| `resources/views/admin/cupons.blade.php` | Reescrever | Cards mobile-first + modais de histórico e pagamento |
| `tests/Feature/AdminCupomControllerTest.php` | Modificar | Testes para novos endpoints |

---

## Task 1: Migrations

**Files:**
- Create: `database/migrations/2026_04_29_000001_add_comissao_to_cupons.php`
- Create: `database/migrations/2026_04_29_000002_create_cupom_pagamentos.php`

- [ ] **Step 1: Criar migration para `comissao_percentual`**

```php
<?php
// database/migrations/2026_04_29_000001_add_comissao_to_cupons.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('cupons', function (Blueprint $table) {
            $table->decimal('comissao_percentual', 5, 2)->default(100)->after('ativo');
        });
    }

    public function down(): void
    {
        Schema::table('cupons', function (Blueprint $table) {
            $table->dropColumn('comissao_percentual');
        });
    }
};
```

- [ ] **Step 2: Criar migration para `cupom_pagamentos` + FK em `cupom_usos`**

```php
<?php
// database/migrations/2026_04_29_000002_create_cupom_pagamentos.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('cupom_pagamentos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cupom_id')->constrained('cupons')->cascadeOnDelete();
            $table->decimal('valor_pago', 10, 2);
            $table->text('observacao')->nullable();
            $table->date('pago_em');
            $table->timestamps();
        });

        Schema::table('cupom_usos', function (Blueprint $table) {
            $table->foreignId('cupom_pagamento_id')
                ->nullable()
                ->after('pedido_id')
                ->constrained('cupom_pagamentos')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('cupom_usos', function (Blueprint $table) {
            $table->dropForeign(['cupom_pagamento_id']);
            $table->dropColumn('cupom_pagamento_id');
        });
        Schema::dropIfExists('cupom_pagamentos');
    }
};
```

- [ ] **Step 3: Rodar migrations no container**

```bash
docker exec laravel-app php artisan migrate --force
```

Esperado: ambas as migrations aparecem como "Ran".

- [ ] **Step 4: Confirmar schema**

```bash
docker exec laravel-app php artisan migrate:status 2>&1 | grep "2026_04_29"
```

Esperado:
```
2026_04_29_000001_add_comissao_to_cupons .......... Ran
2026_04_29_000002_create_cupom_pagamentos ......... Ran
```

- [ ] **Step 5: Commit**

```bash
git add database/migrations/2026_04_29_000001_add_comissao_to_cupons.php database/migrations/2026_04_29_000002_create_cupom_pagamentos.php
git commit -m "feat: migrations para comissao_percentual e cupom_pagamentos"
```

---

## Task 2: Models

**Files:**
- Create: `app/Models/CupomPagamento.php`
- Modify: `app/Models/Cupom.php`
- Modify: `app/Models/CupomUso.php`

- [ ] **Step 1: Criar model `CupomPagamento`**

```php
<?php
// app/Models/CupomPagamento.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CupomPagamento extends Model
{
    protected $table = 'cupom_pagamentos';

    protected $fillable = ['cupom_id', 'valor_pago', 'observacao', 'pago_em'];

    protected $casts = [
        'pago_em'    => 'date',
        'valor_pago' => 'decimal:2',
    ];

    public function cupom(): BelongsTo
    {
        return $this->belongsTo(Cupom::class);
    }

    public function usos(): HasMany
    {
        return $this->hasMany(CupomUso::class);
    }
}
```

- [ ] **Step 2: Atualizar `app/Models/Cupom.php`**

Adicionar `comissao_percentual` ao `$fillable`, ao `$casts` e a relação `pagamentos`:

```php
// Substituir a definição do model pelo conteúdo abaixo (mantém tudo existente, adiciona as 3 mudanças)

protected $fillable = [
    'codigo',
    'user_id',
    'tipo',
    'valor',
    'valor_minimo_pedido',
    'limite_usos',
    'usos_realizados',
    'valido_ate',
    'ativo',
    'comissao_percentual',   // NOVO
];

protected $casts = [
    'ativo'                => 'boolean',
    'valido_ate'           => 'date',
    'valor'                => 'decimal:2',
    'valor_minimo_pedido'  => 'decimal:2',
    'comissao_percentual'  => 'decimal:2',  // NOVO
];

// Adicionar após o método user():
public function pagamentos(): HasMany
{
    return $this->hasMany(CupomPagamento::class);
}
```

Também adicionar o import no topo do arquivo:
```php
use Illuminate\Database\Eloquent\Relations\HasMany;
```

- [ ] **Step 3: Atualizar `app/Models/CupomUso.php`**

Adicionar `cupom_pagamento_id` ao `$fillable` e a relação `pagamento`:

```php
protected $fillable = ['cupom_id', 'user_id', 'pedido_id', 'cupom_pagamento_id'];

// Adicionar método após pedido():
public function pagamento(): BelongsTo
{
    return $this->belongsTo(CupomPagamento::class, 'cupom_pagamento_id');
}
```

- [ ] **Step 4: Verificar que os modelos carregam sem erro**

```bash
docker exec laravel-app php artisan tinker --execute="echo App\Models\CupomPagamento::count(); echo App\Models\Cupom::first()?->comissao_percentual;"
```

Esperado: `0` (sem pagamentos ainda) e o valor de comissao_percentual do primeiro cupom (100).

- [ ] **Step 5: Commit**

```bash
git add app/Models/CupomPagamento.php app/Models/Cupom.php app/Models/CupomUso.php
git commit -m "feat: model CupomPagamento e atualização de Cupom/CupomUso"
```

---

## Task 3: Rotas

**Files:**
- Modify: `routes/web.php`

- [ ] **Step 1: Adicionar 5 novas rotas ao grupo admin**

Localizar o bloco de rotas de cupons em `routes/web.php` (linhas ~135-140) e adicionar as novas rotas **antes** do `Route::put('/cupons/{id}')` para evitar conflito de captura:

```php
// Após Route::post('/cupons/{id}/toggle', ...) adicionar:

Route::get('/cupons/{id}/usos-pendentes',          [App\Http\Controllers\AdminCupomController::class, 'usosPendentes'])->name('cupons.usosPendentes');
Route::get('/cupons/{id}/pagamentos',              [App\Http\Controllers\AdminCupomController::class, 'pagamentos'])->name('cupons.pagamentos');
Route::post('/cupons/{id}/pagamentos',             [App\Http\Controllers\AdminCupomController::class, 'storePagamento'])->name('cupons.pagamentos.store');
Route::put('/cupons/{id}/pagamentos/{pid}',        [App\Http\Controllers\AdminCupomController::class, 'updatePagamento'])->name('cupons.pagamentos.update');
Route::delete('/cupons/{id}/pagamentos/{pid}',     [App\Http\Controllers\AdminCupomController::class, 'destroyPagamento'])->name('cupons.pagamentos.destroy');
```

- [ ] **Step 2: Confirmar que as rotas resolvem**

```bash
docker exec laravel-app php artisan route:list 2>&1 | grep "cupons.*pagamentos"
```

Esperado: 4 linhas (GET, POST, PUT, DELETE) para `/admin/cupons/{id}/pagamentos`.

- [ ] **Step 3: Commit**

```bash
git add routes/web.php
git commit -m "feat: rotas admin para pagamentos de comissão de cupons"
```

---

## Task 4: Controller — Endpoints GET (`usosPendentes` e `pagamentos`)

**Files:**
- Modify: `app/Http/Controllers/AdminCupomController.php`
- Modify: `tests/Feature/AdminCupomControllerTest.php`

- [ ] **Step 1: Escrever testes para `usosPendentes`**

Adicionar ao final da classe em `AdminCupomControllerTest.php` (antes do `}`):

```php
public function test_usos_pendentes_retorna_somente_usos_sem_pagamento(): void
{
    $parceiro = User::factory()->create();
    $cupom = Cupom::create([
        'codigo' => 'PARCEIRO10',
        'user_id' => $parceiro->id,
        'tipo' => 'percentual',
        'valor' => 10,
        'ativo' => true,
        'comissao_percentual' => 100,
    ]);

    $pedido1 = \App\Models\Pedido::factory()->create(['valor_desconto' => 20.00, 'status' => 'entregue']);
    $pedido2 = \App\Models\Pedido::factory()->create(['valor_desconto' => 30.00, 'status' => 'entregue']);

    $uso1 = \App\Models\CupomUso::create(['cupom_id' => $cupom->id, 'pedido_id' => $pedido1->id]);
    $uso2 = \App\Models\CupomUso::create(['cupom_id' => $cupom->id, 'pedido_id' => $pedido2->id]);

    // uso2 já pago
    $pagamento = \App\Models\CupomPagamento::create([
        'cupom_id' => $cupom->id,
        'valor_pago' => 30.00,
        'pago_em' => '2026-04-01',
    ]);
    $uso2->update(['cupom_pagamento_id' => $pagamento->id]);

    $response = $this->actingAs($this->admin)
        ->getJson(route('admin.cupons.usosPendentes', $cupom->id))
        ->assertOk();

    $data = $response->json();
    $this->assertCount(1, $data);
    $this->assertEquals($uso1->id, $data[0]['id']);
    $this->assertEquals(20.00, $data[0]['valor_desconto']);
    $this->assertEquals(20.00, $data[0]['comissao_valor']); // 100% de 20
}

public function test_usos_pendentes_aplica_comissao_percentual_corretamente(): void
{
    $parceiro = User::factory()->create();
    $cupom = Cupom::create([
        'codigo' => 'PARCEIRO50',
        'user_id' => $parceiro->id,
        'tipo' => 'percentual',
        'valor' => 10,
        'ativo' => true,
        'comissao_percentual' => 50,
    ]);

    $pedido = \App\Models\Pedido::factory()->create(['valor_desconto' => 40.00, 'status' => 'entregue']);
    \App\Models\CupomUso::create(['cupom_id' => $cupom->id, 'pedido_id' => $pedido->id]);

    $response = $this->actingAs($this->admin)
        ->getJson(route('admin.cupons.usosPendentes', $cupom->id))
        ->assertOk();

    $this->assertEquals(20.00, $response->json('0.comissao_valor')); // 50% de 40
}
```

- [ ] **Step 2: Rodar testes — confirmar que falham**

```bash
docker exec laravel-app php artisan test --filter="test_usos_pendentes" 2>&1 | tail -20
```

Esperado: FAIL com "Method not found" ou similar.

- [ ] **Step 3: Escrever testes para `pagamentos`**

Adicionar ao final da classe em `AdminCupomControllerTest.php`:

```php
public function test_pagamentos_retorna_lista_com_comissao_pendente(): void
{
    $parceiro = User::factory()->create();
    $cupom = Cupom::create([
        'codigo' => 'HIST10',
        'user_id' => $parceiro->id,
        'tipo' => 'percentual',
        'valor' => 10,
        'ativo' => true,
        'comissao_percentual' => 100,
    ]);

    $pedido1 = \App\Models\Pedido::factory()->create(['valor_desconto' => 50.00, 'status' => 'entregue']);
    $pedido2 = \App\Models\Pedido::factory()->create(['valor_desconto' => 30.00, 'status' => 'entregue']);
    \App\Models\CupomUso::create(['cupom_id' => $cupom->id, 'pedido_id' => $pedido1->id]);
    \App\Models\CupomUso::create(['cupom_id' => $cupom->id, 'pedido_id' => $pedido2->id]);

    $pagamento = \App\Models\CupomPagamento::create([
        'cupom_id' => $cupom->id,
        'valor_pago' => 50.00,
        'observacao' => 'Ref. março',
        'pago_em' => '2026-03-31',
    ]);
    \App\Models\CupomUso::where('pedido_id', $pedido1->id)->update(['cupom_pagamento_id' => $pagamento->id]);

    $response = $this->actingAs($this->admin)
        ->getJson(route('admin.cupons.pagamentos', $cupom->id))
        ->assertOk();

    $this->assertEquals(30.00, $response->json('comissao_pendente'));
    $this->assertCount(1, $response->json('pagamentos'));
    $this->assertEquals(50.00, $response->json('pagamentos.0.valor_pago'));
    $this->assertEquals(1, $response->json('pagamentos.0.usos_count'));
    $this->assertEquals('Ref. março', $response->json('pagamentos.0.observacao'));
}
```

- [ ] **Step 4: Implementar `usosPendentes` e `pagamentos` no controller**

Adicionar imports ao topo de `AdminCupomController.php`:

```php
use App\Models\CupomPagamento;
use App\Models\CupomUso;
```

Adicionar os dois métodos à classe:

```php
public function usosPendentes(int $id)
{
    $this->checkAdmin();
    $cupom = Cupom::findOrFail($id);
    $rate = (float) $cupom->comissao_percentual / 100;

    $usos = CupomUso::with('pedido')
        ->where('cupom_id', $id)
        ->whereNull('cupom_pagamento_id')
        ->orderBy('created_at')
        ->get()
        ->map(fn ($uso) => [
            'id'             => $uso->id,
            'pedido_id'      => $uso->pedido_id,
            'created_at'     => $uso->created_at?->format('d/m/Y'),
            'valor_desconto' => (float) ($uso->pedido?->valor_desconto ?? 0),
            'comissao_valor' => round((float) ($uso->pedido?->valor_desconto ?? 0) * $rate, 2),
        ]);

    return response()->json($usos);
}

public function pagamentos(int $id)
{
    $this->checkAdmin();
    $cupom = Cupom::with(['pagamentos.usos.pedido', 'usos.pedido'])->findOrFail($id);
    $rate = (float) $cupom->comissao_percentual / 100;

    $comissaoPendente = $cupom->usos
        ->filter(fn ($uso) => is_null($uso->cupom_pagamento_id))
        ->sum(fn ($uso) => round((float) ($uso->pedido?->valor_desconto ?? 0) * $rate, 2));

    $pagamentos = $cupom->pagamentos
        ->sortByDesc('pago_em')
        ->values()
        ->map(fn ($pag) => [
            'id'         => $pag->id,
            'valor_pago' => (float) $pag->valor_pago,
            'observacao' => $pag->observacao,
            'pago_em'    => $pag->pago_em->format('d/m/Y'),
            'usos_count' => $pag->usos->count(),
            'usos'       => $pag->usos->map(fn ($uso) => [
                'id'             => $uso->id,
                'pedido_id'      => $uso->pedido_id,
                'created_at'     => $uso->created_at?->format('d/m/Y'),
                'valor_desconto' => (float) ($uso->pedido?->valor_desconto ?? 0),
                'comissao_valor' => round((float) ($uso->pedido?->valor_desconto ?? 0) * $rate, 2),
            ]),
        ]);

    return response()->json(compact('comissaoPendente', 'pagamentos'));
}
```

- [ ] **Step 5: Rodar testes — confirmar que passam**

```bash
docker exec laravel-app php artisan test --filter="test_usos_pendentes|test_pagamentos_retorna" 2>&1 | tail -20
```

Esperado: 3 testes PASS.

- [ ] **Step 6: Commit**

```bash
git add app/Http/Controllers/AdminCupomController.php tests/Feature/AdminCupomControllerTest.php
git commit -m "feat: endpoints GET usosPendentes e pagamentos de comissão"
```

---

## Task 5: Controller — Endpoints de Mutação (`storePagamento`, `updatePagamento`, `destroyPagamento`)

**Files:**
- Modify: `app/Http/Controllers/AdminCupomController.php`
- Modify: `tests/Feature/AdminCupomControllerTest.php`

- [ ] **Step 1: Escrever testes para os 3 endpoints de mutação**

Adicionar ao final da classe em `AdminCupomControllerTest.php`:

```php
public function test_store_pagamento_cria_pagamento_e_vincula_usos(): void
{
    $parceiro = User::factory()->create();
    $cupom = Cupom::create([
        'codigo' => 'PAG10',
        'user_id' => $parceiro->id,
        'tipo' => 'percentual',
        'valor' => 10,
        'ativo' => true,
        'comissao_percentual' => 100,
    ]);

    $pedido1 = \App\Models\Pedido::factory()->create(['valor_desconto' => 20.00, 'status' => 'entregue']);
    $pedido2 = \App\Models\Pedido::factory()->create(['valor_desconto' => 30.00, 'status' => 'entregue']);
    $uso1 = \App\Models\CupomUso::create(['cupom_id' => $cupom->id, 'pedido_id' => $pedido1->id]);
    $uso2 = \App\Models\CupomUso::create(['cupom_id' => $cupom->id, 'pedido_id' => $pedido2->id]);

    $this->actingAs($this->admin)
        ->postJson(route('admin.cupons.pagamentos.store', $cupom->id), [
            'valor_pago' => 50.00,
            'pago_em' => '2026-04-30',
            'observacao' => 'Pagamento abril',
            'uso_ids' => [$uso1->id, $uso2->id],
        ])
        ->assertOk()
        ->assertJsonPath('success', true);

    $this->assertDatabaseHas('cupom_pagamentos', [
        'cupom_id'   => $cupom->id,
        'valor_pago' => 50.00,
        'observacao' => 'Pagamento abril',
    ]);
    $this->assertDatabaseHas('cupom_usos', ['id' => $uso1->id, 'cupom_pagamento_id' => 1]);
    $this->assertDatabaseHas('cupom_usos', ['id' => $uso2->id, 'cupom_pagamento_id' => 1]);
}

public function test_store_pagamento_sem_uso_ids_cria_pagamento_sem_vincular(): void
{
    $cupom = Cupom::create([
        'codigo' => 'SEMUSO',
        'tipo' => 'percentual',
        'valor' => 10,
        'ativo' => true,
        'comissao_percentual' => 100,
    ]);

    $this->actingAs($this->admin)
        ->postJson(route('admin.cupons.pagamentos.store', $cupom->id), [
            'valor_pago' => 100.00,
            'pago_em' => '2026-04-30',
        ])
        ->assertOk()
        ->assertJsonPath('success', true);

    $this->assertDatabaseHas('cupom_pagamentos', ['cupom_id' => $cupom->id, 'valor_pago' => 100.00]);
}

public function test_update_pagamento_atualiza_campos(): void
{
    $cupom = Cupom::create([
        'codigo' => 'UPAG10',
        'tipo' => 'percentual',
        'valor' => 10,
        'ativo' => true,
        'comissao_percentual' => 100,
    ]);
    $pagamento = \App\Models\CupomPagamento::create([
        'cupom_id' => $cupom->id,
        'valor_pago' => 50.00,
        'pago_em' => '2026-04-01',
    ]);

    $this->actingAs($this->admin)
        ->putJson(route('admin.cupons.pagamentos.update', [$cupom->id, $pagamento->id]), [
            'valor_pago' => 75.00,
            'pago_em' => '2026-04-15',
            'observacao' => 'Ajuste de valor',
        ])
        ->assertOk()
        ->assertJsonPath('success', true);

    $this->assertDatabaseHas('cupom_pagamentos', [
        'id'         => $pagamento->id,
        'valor_pago' => 75.00,
        'observacao' => 'Ajuste de valor',
    ]);
}

public function test_destroy_pagamento_libera_usos_vinculados(): void
{
    $cupom = Cupom::create([
        'codigo' => 'DESTPAG',
        'tipo' => 'percentual',
        'valor' => 10,
        'ativo' => true,
        'comissao_percentual' => 100,
    ]);
    $pedido = \App\Models\Pedido::factory()->create(['valor_desconto' => 20.00, 'status' => 'entregue']);
    $pagamento = \App\Models\CupomPagamento::create([
        'cupom_id' => $cupom->id,
        'valor_pago' => 20.00,
        'pago_em' => '2026-04-01',
    ]);
    $uso = \App\Models\CupomUso::create([
        'cupom_id' => $cupom->id,
        'pedido_id' => $pedido->id,
        'cupom_pagamento_id' => $pagamento->id,
    ]);

    $this->actingAs($this->admin)
        ->deleteJson(route('admin.cupons.pagamentos.destroy', [$cupom->id, $pagamento->id]))
        ->assertOk()
        ->assertJsonPath('success', true);

    $this->assertDatabaseMissing('cupom_pagamentos', ['id' => $pagamento->id]);
    $this->assertDatabaseHas('cupom_usos', ['id' => $uso->id, 'cupom_pagamento_id' => null]);
}
```

- [ ] **Step 2: Rodar testes — confirmar que falham**

```bash
docker exec laravel-app php artisan test --filter="test_store_pagamento|test_update_pagamento|test_destroy_pagamento" 2>&1 | tail -20
```

Esperado: FAIL.

- [ ] **Step 3: Implementar `storePagamento`, `updatePagamento`, `destroyPagamento`**

Adicionar ao `AdminCupomController`:

```php
public function storePagamento(Request $request, int $id)
{
    $this->checkAdmin();
    $cupom = Cupom::findOrFail($id);

    $data = $request->validate([
        'valor_pago' => 'required|numeric|min:0.01',
        'pago_em'    => 'required|date',
        'observacao' => 'nullable|string|max:500',
        'uso_ids'    => 'nullable|array',
        'uso_ids.*'  => 'integer|exists:cupom_usos,id',
    ]);

    $pagamento = CupomPagamento::create([
        'cupom_id'   => $cupom->id,
        'valor_pago' => $data['valor_pago'],
        'pago_em'    => $data['pago_em'],
        'observacao' => $data['observacao'] ?? null,
    ]);

    if (!empty($data['uso_ids'])) {
        CupomUso::whereIn('id', $data['uso_ids'])
            ->where('cupom_id', $cupom->id)
            ->whereNull('cupom_pagamento_id')
            ->update(['cupom_pagamento_id' => $pagamento->id]);
    }

    return response()->json(['success' => true, 'pagamento_id' => $pagamento->id]);
}

public function updatePagamento(Request $request, int $id, int $pid)
{
    $this->checkAdmin();
    $pagamento = CupomPagamento::where('cupom_id', $id)->findOrFail($pid);

    $data = $request->validate([
        'valor_pago' => 'required|numeric|min:0.01',
        'pago_em'    => 'required|date',
        'observacao' => 'nullable|string|max:500',
    ]);

    $pagamento->update($data);

    return response()->json(['success' => true]);
}

public function destroyPagamento(int $id, int $pid)
{
    $this->checkAdmin();
    $pagamento = CupomPagamento::where('cupom_id', $id)->findOrFail($pid);
    $pagamento->delete();

    return response()->json(['success' => true]);
}
```

- [ ] **Step 4: Rodar testes — confirmar que passam**

```bash
docker exec laravel-app php artisan test --filter="test_store_pagamento|test_update_pagamento|test_destroy_pagamento" 2>&1 | tail -20
```

Esperado: 4 testes PASS.

- [ ] **Step 5: Rodar todos os testes de cupons**

```bash
docker exec laravel-app php artisan test --filter=AdminCupomControllerTest 2>&1 | tail -20
```

Esperado: todos PASS.

- [ ] **Step 6: Commit**

```bash
git add app/Http/Controllers/AdminCupomController.php tests/Feature/AdminCupomControllerTest.php
git commit -m "feat: endpoints storePagamento, updatePagamento, destroyPagamento"
```

---

## Task 6: Controller — Atualizar `index` com métricas

**Files:**
- Modify: `app/Http/Controllers/AdminCupomController.php`
- Modify: `tests/Feature/AdminCupomControllerTest.php`

- [ ] **Step 1: Escrever teste para métricas no index**

Adicionar ao `AdminCupomControllerTest.php`:

```php
public function test_index_calcula_metricas_de_comissao_por_cupom(): void
{
    $parceiro = User::factory()->create();
    $cupom = Cupom::create([
        'codigo' => 'METRIC10',
        'user_id' => $parceiro->id,
        'tipo' => 'percentual',
        'valor' => 10,
        'ativo' => true,
        'comissao_percentual' => 50,
    ]);

    $pedido1 = \App\Models\Pedido::factory()->create(['valor_total' => 100.00, 'valor_desconto' => 10.00, 'status' => 'entregue']);
    $pedido2 = \App\Models\Pedido::factory()->create(['valor_total' => 200.00, 'valor_desconto' => 20.00, 'status' => 'entregue']);
    $uso1 = \App\Models\CupomUso::create(['cupom_id' => $cupom->id, 'pedido_id' => $pedido1->id]);
    \App\Models\CupomUso::create(['cupom_id' => $cupom->id, 'pedido_id' => $pedido2->id]);

    // Registrar pagamento cobrindo apenas uso1
    $pagamento = \App\Models\CupomPagamento::create([
        'cupom_id' => $cupom->id, 'valor_pago' => 5.00, 'pago_em' => '2026-04-01',
    ]);
    $uso1->update(['cupom_pagamento_id' => $pagamento->id]);

    $response = $this->actingAs($this->admin)
        ->get(route('admin.cupons.index'))
        ->assertOk();

    // Extrair o cupom da view
    $cupons = $response->viewData('cupons');
    $c = $cupons->firstWhere('codigo', 'METRIC10');

    $this->assertEquals(300.00, $c->receita_gerada);     // 100 + 200
    $this->assertEquals(30.00,  $c->descontos_dados);    // 10 + 20
    $this->assertEquals(15.00,  $c->comissao_total);     // (10+20) × 50%
    $this->assertEquals(5.00,   $c->comissao_paga);      // pagamento registrado
    $this->assertEquals(10.00,  $c->comissao_pendente);  // 15 - 5
}
```

- [ ] **Step 2: Rodar teste — confirmar que falha**

```bash
docker exec laravel-app php artisan test --filter="test_index_calcula_metricas" 2>&1 | tail -20
```

Esperado: FAIL (receita_gerada não existe ainda).

- [ ] **Step 3: Atualizar método `index` do controller**

Substituir o método `index` existente:

```php
public function index()
{
    $this->checkAdmin();
    $cupons = Cupom::with(['user', 'usos.pedido', 'pagamentos'])->orderByDesc('created_at')->get();

    $cupons->each(function (Cupom $cupom) {
        $rate = (float) $cupom->comissao_percentual / 100;

        $cupom->receita_gerada    = $cupom->usos->sum(fn ($u) => (float) ($u->pedido?->valor_total ?? 0));
        $cupom->descontos_dados   = $cupom->usos->sum(fn ($u) => (float) ($u->pedido?->valor_desconto ?? 0));
        $cupom->comissao_total    = $cupom->usos->sum(fn ($u) => round((float) ($u->pedido?->valor_desconto ?? 0) * $rate, 2));
        $cupom->comissao_paga     = $cupom->pagamentos->sum(fn ($p) => (float) $p->valor_pago);
        $cupom->comissao_pendente = max(0, $cupom->comissao_total - $cupom->comissao_paga);
    });

    return view('admin.cupons', compact('cupons'));
}
```

- [ ] **Step 4: Rodar testes — confirmar que passam**

```bash
docker exec laravel-app php artisan test --filter=AdminCupomControllerTest 2>&1 | tail -20
```

Esperado: todos PASS.

- [ ] **Step 5: Commit**

```bash
git add app/Http/Controllers/AdminCupomController.php tests/Feature/AdminCupomControllerTest.php
git commit -m "feat: index com métricas de receita e comissão por cupom"
```

---

## Task 7: View — Redesign Cards Mobile-first + Modal de Cupom Atualizado

**Files:**
- Modify: `resources/views/admin/cupons.blade.php`

- [ ] **Step 1: Reescrever a view com cards e modais**

Substituir o conteúdo completo de `resources/views/admin/cupons.blade.php`:

```blade
@extends('includes.header-admin')

@section('title', 'Cupons')

@section('content')

{{-- Header --}}
<div class="flex items-center justify-between mb-6">
    <h1 class="font-mono text-xl font-bold uppercase tracking-widest">Cupons</h1>
    <button id="btn-novo-cupom" class="bg-black text-white px-4 py-2 font-mono text-xs uppercase tracking-widest hover:bg-gray-900 transition-colors">
        + Novo Cupom
    </button>
</div>

{{-- Grid de Cards --}}
<div class="grid grid-cols-1 lg:grid-cols-2 gap-4" id="grid-cupons">
@forelse ($cupons as $cupom)
<div class="border border-[var(--color-lab-border)] bg-white" data-cupom-id="{{ $cupom->id }}">

    {{-- Cabeçalho do card --}}
    <div class="p-4 flex items-start justify-between gap-3">
        <div class="min-w-0">
            <div class="flex items-center gap-2 flex-wrap">
                <span class="font-mono font-bold text-sm tracking-widest">{{ $cupom->codigo }}</span>
                <span class="inline-flex items-center gap-1 px-2 py-0.5 text-[10px] font-mono uppercase tracking-widest border
                    {{ $cupom->ativo ? 'bg-black text-white border-black' : 'border-gray-300 text-gray-400' }}">
                    {{ $cupom->ativo ? '● Ativo' : '○ Inativo' }}
                </span>
            </div>
            @if($cupom->user)
                <p class="mt-1 text-xs font-mono text-gray-500 truncate">{{ $cupom->user->name }} · {{ $cupom->user->email }}</p>
            @else
                <p class="mt-1 text-xs font-mono text-gray-300">Sem parceiro</p>
            @endif
        </div>

        {{-- Menu de ações --}}
        <div class="relative shrink-0 card-menu-wrapper">
            <button class="btn-card-menu p-1 text-gray-400 hover:text-black font-mono text-lg leading-none" data-id="{{ $cupom->id }}">···</button>
            <div class="card-menu hidden absolute right-0 top-7 z-20 bg-white border border-[var(--color-lab-border)] shadow-sm w-44 py-1">
                <button class="btn-editar block w-full text-left px-4 py-2 text-xs font-mono hover:bg-gray-50"
                    data-cupom='@json($cupom->makeHidden(["receita_gerada","descontos_dados","comissao_total","comissao_paga","comissao_pendente","usos","pagamentos"]))'>
                    Editar
                </button>
                <button class="toggle-status block w-full text-left px-4 py-2 text-xs font-mono hover:bg-gray-50" data-id="{{ $cupom->id }}">
                    {{ $cupom->ativo ? 'Desativar' : 'Ativar' }}
                </button>
                @if($cupom->user)
                <button class="btn-registrar-pagamento block w-full text-left px-4 py-2 text-xs font-mono hover:bg-gray-50"
                    data-id="{{ $cupom->id }}" data-codigo="{{ $cupom->codigo }}">
                    Registrar pagamento
                </button>
                @endif
                <hr class="my-1 border-gray-100">
                <button class="btn-deletar block w-full text-left px-4 py-2 text-xs font-mono text-red-600 hover:bg-red-50" data-id="{{ $cupom->id }}">
                    Excluir
                </button>
            </div>
        </div>
    </div>

    {{-- Detalhes do cupom --}}
    <div class="px-4 pb-3 border-t border-gray-100 pt-3">
        <p class="text-xs font-mono text-gray-500">
            {{ $cupom->tipo === 'percentual' ? number_format($cupom->valor, 0).'% desconto' : 'R$ '.number_format($cupom->valor, 2, ',', '.').' desconto' }}
            @if($cupom->user) · Comissão: {{ number_format($cupom->comissao_percentual, 0) }}% @endif
        </p>
        <p class="text-xs font-mono text-gray-400 mt-0.5">
            {{ $cupom->usos_realizados }}{{ $cupom->limite_usos ? '/'.$cupom->limite_usos : '' }} usos
            @if($cupom->valor_minimo_pedido) · Mín. R$ {{ number_format($cupom->valor_minimo_pedido, 2, ',', '.') }} @endif
            @if($cupom->valido_ate) · Até {{ $cupom->valido_ate->format('d/m/Y') }} @endif
        </p>
    </div>

    @if($cupom->user)
    {{-- Métricas de receita e comissão --}}
    <div class="border-t border-gray-100 px-4 py-3 space-y-1.5">
        <div class="flex justify-between text-xs font-mono">
            <span class="text-gray-400">Receita gerada</span>
            <span>R$ {{ number_format($cupom->receita_gerada, 2, ',', '.') }}</span>
        </div>
        <div class="flex justify-between text-xs font-mono">
            <span class="text-gray-400">Descontos dados</span>
            <span>R$ {{ number_format($cupom->descontos_dados, 2, ',', '.') }}</span>
        </div>
        <div class="flex justify-between text-xs font-mono border-t border-gray-100 pt-1.5 mt-1">
            <span class="text-gray-600">Comissão devida</span>
            <span class="{{ $cupom->comissao_pendente > 0 ? 'font-bold text-black' : 'text-gray-400' }}">
                R$ {{ number_format($cupom->comissao_pendente, 2, ',', '.') }}
            </span>
        </div>
        <div class="flex justify-between text-xs font-mono">
            <span class="text-gray-400">Já pago</span>
            <span class="text-gray-400">R$ {{ number_format($cupom->comissao_paga, 2, ',', '.') }}</span>
        </div>
    </div>

    {{-- Link histórico --}}
    <div class="border-t border-gray-100 px-4 py-3">
        <button class="btn-historico text-xs font-mono underline text-gray-500 hover:text-black transition-colors"
            data-id="{{ $cupom->id }}" data-codigo="{{ $cupom->codigo }}">
            Ver histórico de pagamentos →
        </button>
    </div>
    @endif

</div>
@empty
<div class="col-span-2 py-16 text-center font-mono text-sm text-gray-400">Nenhum cupom cadastrado.</div>
@endforelse
</div>

{{-- ============================================================ --}}
{{-- Modal Criar/Editar Cupom --}}
{{-- ============================================================ --}}
<div id="modal-cupom" class="fixed inset-0 bg-black/50 z-50 hidden items-center justify-center p-4">
    <div class="bg-white border border-black w-full max-w-lg max-h-[90vh] overflow-y-auto">
        <div class="flex items-center justify-between p-6 pb-4">
            <h2 class="font-mono text-base font-bold uppercase tracking-widest" id="modal-titulo">Novo Cupom</h2>
            <button id="modal-fechar" class="text-gray-400 hover:text-black text-xl leading-none">&times;</button>
        </div>
        <form id="form-cupom" class="px-6 pb-6 space-y-4">
            <input type="hidden" id="cupom-id" value="">
            <input type="hidden" id="f-user-id" name="user_id" value="">

            <div>
                <label class="block text-xs font-mono uppercase tracking-widest mb-1">Código *</label>
                <input type="text" id="f-codigo" name="codigo"
                    class="w-full border border-[var(--color-lab-border)] px-3 py-2 font-mono uppercase focus:outline-none focus:border-black"
                    maxlength="50" required>
            </div>

            <div class="relative">
                <label class="block text-xs font-mono uppercase tracking-widest mb-1">Parceiro / Streamer</label>
                <input type="text" id="f-user-search"
                    class="w-full border border-[var(--color-lab-border)] px-3 py-2 font-mono focus:outline-none focus:border-black"
                    placeholder="Buscar por nome ou e-mail" autocomplete="off">
                <div id="f-user-results" class="hidden absolute z-10 mt-1 max-h-40 w-full overflow-y-auto border border-[var(--color-lab-border)] bg-white"></div>
                <div class="mt-1 flex items-center justify-between gap-3">
                    <p id="f-user-selected" class="text-xs text-gray-500"></p>
                    <button type="button" id="f-user-clear" class="hidden text-xs underline text-gray-500 hover:text-black">remover vínculo</button>
                </div>
            </div>

            <div class="flex items-center gap-2">
                <input type="checkbox" id="f-coupon-portal-enabled" name="coupon_portal_enabled" value="1" class="w-4 h-4" disabled>
                <label for="f-coupon-portal-enabled" class="text-xs font-mono uppercase tracking-widest">Liberar acesso ao portal /cupom</label>
            </div>
            <p id="f-coupon-portal-help" class="text-[10px] text-gray-500">Selecione um usuário para controlar o acesso ao portal.</p>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-mono uppercase tracking-widest mb-1">Tipo *</label>
                    <select id="f-tipo" name="tipo"
                        class="w-full border border-[var(--color-lab-border)] px-3 py-2 font-mono focus:outline-none focus:border-black" required>
                        <option value="percentual">Percentual (%)</option>
                        <option value="fixo">Valor Fixo (R$)</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-mono uppercase tracking-widest mb-1">Valor *</label>
                    <input type="number" id="f-valor" name="valor"
                        class="w-full border border-[var(--color-lab-border)] px-3 py-2 font-mono focus:outline-none focus:border-black"
                        step="0.01" min="0.01" required>
                </div>
            </div>

            <div>
                <label class="block text-xs font-mono uppercase tracking-widest mb-1">Comissão ao parceiro (%)</label>
                <input type="number" id="f-comissao-percentual" name="comissao_percentual"
                    class="w-full border border-[var(--color-lab-border)] px-3 py-2 font-mono focus:outline-none focus:border-black"
                    step="1" min="0" max="100" value="100" placeholder="100">
                <p class="text-[10px] text-gray-400 mt-1">% do desconto gerado que vai para o parceiro. Padrão: 100.</p>
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-mono uppercase tracking-widest mb-1">Pedido Mínimo (R$)</label>
                    <input type="number" id="f-minimo" name="valor_minimo_pedido"
                        class="w-full border border-[var(--color-lab-border)] px-3 py-2 font-mono focus:outline-none focus:border-black"
                        step="0.01" min="0">
                </div>
                <div>
                    <label class="block text-xs font-mono uppercase tracking-widest mb-1">Limite de Usos</label>
                    <input type="number" id="f-limite" name="limite_usos"
                        class="w-full border border-[var(--color-lab-border)] px-3 py-2 font-mono focus:outline-none focus:border-black"
                        min="1">
                </div>
            </div>

            <div>
                <label class="block text-xs font-mono uppercase tracking-widest mb-1">Válido até</label>
                <input type="date" id="f-validade" name="valido_ate"
                    class="w-full border border-[var(--color-lab-border)] px-3 py-2 font-mono focus:outline-none focus:border-black">
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
<div id="modal-deletar" class="fixed inset-0 bg-black/50 z-50 hidden items-center justify-center p-4">
    <div class="bg-white border border-black p-8 w-full max-w-sm text-center">
        <p class="font-mono text-sm mb-6">Tem certeza que deseja excluir este cupom?</p>
        <input type="hidden" id="deletar-id">
        <div class="flex gap-4 justify-center">
            <button id="confirmar-deletar" class="bg-black text-white px-6 py-2 font-mono text-xs uppercase tracking-widest hover:bg-gray-900">Excluir</button>
            <button id="cancelar-deletar" class="border border-black px-6 py-2 font-mono text-xs uppercase tracking-widest hover:bg-gray-100">Cancelar</button>
        </div>
    </div>
</div>

{{-- ============================================================ --}}
{{-- Modal Histórico de Pagamentos --}}
{{-- ============================================================ --}}
<div id="modal-historico" class="fixed inset-0 bg-black/50 z-50 hidden items-center justify-center p-4">
    <div class="bg-white border border-[var(--color-lab-border)] w-full max-w-lg max-h-[90vh] overflow-y-auto">
        <div class="flex items-center justify-between p-5 border-b border-[var(--color-lab-border)]">
            <div>
                <p class="font-mono text-[10px] uppercase tracking-widest text-gray-400">Pagamentos</p>
                <h2 class="font-mono font-bold text-sm tracking-widest" id="historico-codigo">—</h2>
            </div>
            <button id="historico-fechar" class="text-gray-400 hover:text-black text-xl leading-none">&times;</button>
        </div>

        {{-- Barra de pendente + botão registrar --}}
        <div class="flex items-center justify-between gap-4 px-5 py-4 bg-gray-50 border-b border-[var(--color-lab-border)]">
            <div>
                <p class="text-[10px] font-mono uppercase tracking-widest text-gray-400">Comissão pendente</p>
                <p class="font-mono font-bold text-base" id="historico-pendente">R$ 0,00</p>
            </div>
            <button id="btn-novo-pagamento" class="bg-black text-white px-4 py-2 font-mono text-xs uppercase tracking-widest hover:bg-gray-900 shrink-0">
                + Registrar
            </button>
        </div>

        {{-- Lista de pagamentos --}}
        <div id="historico-lista" class="divide-y divide-gray-100">
            <div class="py-10 text-center text-gray-400 font-mono text-xs">Carregando...</div>
        </div>
    </div>
</div>

{{-- ============================================================ --}}
{{-- Modal Registrar / Editar Pagamento --}}
{{-- ============================================================ --}}
<div id="modal-pagamento" class="fixed inset-0 bg-black/60 z-[60] hidden items-center justify-center p-4">
    <div class="bg-white border border-[var(--color-lab-border)] w-full max-w-lg max-h-[90vh] overflow-y-auto">
        <div class="flex items-center justify-between p-5 border-b border-[var(--color-lab-border)]">
            <h2 class="font-mono font-bold text-sm uppercase tracking-widest" id="pagamento-titulo">Registrar Pagamento</h2>
            <button id="pagamento-fechar" class="text-gray-400 hover:text-black text-xl leading-none">&times;</button>
        </div>

        <div class="p-5 space-y-4">
            <input type="hidden" id="pagamento-id" value="">

            {{-- Usos pendentes (só exibe no modo criar) --}}
            <div id="bloco-usos-pendentes">
                <p class="font-mono text-[10px] uppercase tracking-widest text-gray-400 mb-2">Usos pendentes</p>
                <div id="lista-usos-pendentes" class="space-y-1 max-h-48 overflow-y-auto border border-gray-100 p-2">
                    <div class="text-xs text-gray-400 font-mono py-2 text-center">Carregando...</div>
                </div>
                <div class="flex items-center justify-between mt-2">
                    <button type="button" id="btn-selecionar-todos" class="text-xs font-mono underline text-gray-500 hover:text-black">Selecionar todos</button>
                    <p class="text-xs font-mono text-gray-500">Total: <span id="total-selecionado" class="font-bold">R$ 0,00</span></p>
                </div>
            </div>

            <div>
                <label class="block text-xs font-mono uppercase tracking-widest mb-1">Valor pago (R$) *</label>
                <input type="number" id="p-valor-pago" step="0.01" min="0.01"
                    class="w-full border border-[var(--color-lab-border)] px-3 py-2 font-mono focus:outline-none focus:border-black" required>
            </div>
            <div>
                <label class="block text-xs font-mono uppercase tracking-widest mb-1">Data do pagamento *</label>
                <input type="date" id="p-pago-em"
                    class="w-full border border-[var(--color-lab-border)] px-3 py-2 font-mono focus:outline-none focus:border-black" required>
            </div>
            <div>
                <label class="block text-xs font-mono uppercase tracking-widest mb-1">Observação</label>
                <input type="text" id="p-observacao" maxlength="500"
                    class="w-full border border-[var(--color-lab-border)] px-3 py-2 font-mono focus:outline-none focus:border-black"
                    placeholder="ex: Referente a abril/2026">
            </div>

            <p id="pagamento-erro" class="text-red-600 text-xs hidden"></p>

            <div class="flex gap-3 pt-2">
                <button type="button" id="pagamento-cancelar"
                    class="flex-1 border border-black px-4 py-2 font-mono text-xs uppercase tracking-widest hover:bg-gray-100">
                    Cancelar
                </button>
                <button type="button" id="pagamento-salvar"
                    class="flex-1 bg-black text-white px-4 py-2 font-mono text-xs uppercase tracking-widest hover:bg-gray-900">
                    Salvar
                </button>
            </div>
        </div>
    </div>
</div>

{{-- ============================================================ --}}
{{-- Modal Ver Usos de um Pagamento --}}
{{-- ============================================================ --}}
<div id="modal-usos-pagamento" class="fixed inset-0 bg-black/60 z-[60] hidden items-center justify-center p-4">
    <div class="bg-white border border-[var(--color-lab-border)] w-full max-w-sm max-h-[80vh] overflow-y-auto">
        <div class="flex items-center justify-between p-5 border-b border-[var(--color-lab-border)]">
            <h2 class="font-mono font-bold text-sm uppercase tracking-widest">Usos cobertos</h2>
            <button id="usos-pagamento-fechar" class="text-gray-400 hover:text-black text-xl leading-none">&times;</button>
        </div>
        <div id="usos-pagamento-lista" class="p-5 space-y-2 text-xs font-mono"></div>
    </div>
</div>

<script>
// ============================================================
// URLs
// ============================================================
function urlCuponsUpdate(id)           { return '/admin/cupons/' + id; }
function urlCuponsToggle(id)           { return '/admin/cupons/' + id + '/toggle'; }
function urlCuponsDelete(id)           { return '/admin/cupons/' + id; }
function urlCuponsUsosPendentes(id)    { return '/admin/cupons/' + id + '/usos-pendentes'; }
function urlCuponsPagamentos(id)       { return '/admin/cupons/' + id + '/pagamentos'; }
function urlCuponsPagamentosStore(id)  { return '/admin/cupons/' + id + '/pagamentos'; }
function urlCuponsPagamentosUpdate(id, pid) { return '/admin/cupons/' + id + '/pagamentos/' + pid; }
function urlCuponsPagamentosDelete(id, pid) { return '/admin/cupons/' + id + '/pagamentos/' + pid; }
function cuponsBuscarUsuariosUrl()     { return '{{ route("admin.cupons.buscarUsuarios") }}'; }

// ============================================================
// Estado global
// ============================================================
let _cupomIdAtivo = null;    // ID do cupom com histórico aberto
let _cupomCodigoAtivo = '';
let couponUserSearchRequest = null;

// ============================================================
// Helpers de formatação
// ============================================================
function fmtMoeda(v) {
    return 'R$ ' + Number(v).toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
}
function csrfToken() {
    return $('meta[name="csrf-token"]').attr('content');
}

// ============================================================
// Menu de três pontinhos
// ============================================================
$(document).on('click', '.btn-card-menu', function (e) {
    e.stopPropagation();
    const $menu = $(this).siblings('.card-menu');
    $('.card-menu').not($menu).addClass('hidden');
    $menu.toggleClass('hidden');
});
$(document).on('click', function () {
    $('.card-menu').addClass('hidden');
});

// ============================================================
// Modal Cupom — criar/editar
// ============================================================
function renderUserSelection(user) {
    if (user && user.id) {
        $('#f-user-id').val(user.id);
        $('#f-user-search').val(user.name || '');
        $('#f-user-selected').text((user.name || '') + ' (' + (user.email || '') + ')');
        $('#f-user-clear').removeClass('hidden');
        $('#f-coupon-portal-enabled').prop('disabled', false).prop('checked', !!user.coupon_portal_enabled);
        $('#f-coupon-portal-help').text('Este usuário poderá acessar o portal /cupom se a liberação estiver marcada.');
        return;
    }
    $('#f-user-id').val('');
    $('#f-user-search').val('');
    $('#f-user-selected').text('');
    $('#f-user-clear').addClass('hidden');
    $('#f-coupon-portal-enabled').prop('disabled', true).prop('checked', false);
    $('#f-coupon-portal-help').text('Selecione um usuário para controlar o acesso ao portal.');
}
function hideUserResults() { $('#f-user-results').addClass('hidden').empty(); }
function showUserResults(users) {
    const $r = $('#f-user-results').empty();
    if (!users.length) {
        $r.append('<div class="px-3 py-2 text-xs text-gray-500">Nenhum usuário encontrado.</div>');
    } else {
        users.forEach(function (u) {
            const $btn = $('<button type="button" class="block w-full border-b border-[var(--color-lab-border)] px-3 py-2 text-left text-xs hover:bg-gray-50"></button>');
            $btn.append($('<div class="font-semibold font-mono"></div>').text(u.name));
            $btn.append($('<div class="text-gray-500 font-mono"></div>').text(u.email));
            $btn.on('click', function () { renderUserSelection(u); hideUserResults(); });
            $r.append($btn);
        });
    }
    $r.removeClass('hidden');
}
function abrirModalCupom(titulo, cupom) {
    $('#modal-titulo').text(titulo);
    $('#cupom-id').val(cupom ? cupom.id : '');
    $('#f-codigo').val(cupom ? cupom.codigo : '');
    renderUserSelection(cupom ? cupom.user : null);
    $('#f-tipo').val(cupom ? cupom.tipo : 'percentual');
    $('#f-valor').val(cupom ? cupom.valor : '');
    $('#f-comissao-percentual').val(cupom ? (cupom.comissao_percentual || 100) : 100);
    $('#f-minimo').val(cupom ? (cupom.valor_minimo_pedido || '') : '');
    $('#f-limite').val(cupom ? (cupom.limite_usos || '') : '');
    $('#f-validade').val(cupom && cupom.valido_ate ? cupom.valido_ate.substring(0, 10) : '');
    $('#f-ativo').prop('checked', cupom ? !!cupom.ativo : true);
    $('#form-erro').addClass('hidden').text('');
    hideUserResults();
    $('#modal-cupom').removeClass('hidden').addClass('flex');
}
function fecharModalCupom() {
    hideUserResults();
    $('#modal-cupom').addClass('hidden').removeClass('flex');
}

// ============================================================
// Modal Histórico
// ============================================================
function abrirHistorico(cupomId, codigo) {
    _cupomIdAtivo = cupomId;
    _cupomCodigoAtivo = codigo;
    $('#historico-codigo').text(codigo);
    $('#historico-pendente').text('...');
    $('#historico-lista').html('<div class="py-10 text-center text-gray-400 font-mono text-xs">Carregando...</div>');
    $('#modal-historico').removeClass('hidden').addClass('flex');
    carregarHistorico();
}
function fecharHistorico() {
    $('#modal-historico').addClass('hidden').removeClass('flex');
    _cupomIdAtivo = null;
}
function carregarHistorico() {
    $.getJSON(urlCuponsPagamentos(_cupomIdAtivo), function (data) {
        $('#historico-pendente').text(fmtMoeda(data.comissaoPendente || 0));
        renderListaPagamentos(data.pagamentos || []);
    }).fail(function () {
        $('#historico-lista').html('<div class="py-8 text-center text-red-500 font-mono text-xs">Erro ao carregar.</div>');
    });
}
function renderListaPagamentos(pagamentos) {
    if (!pagamentos.length) {
        $('#historico-lista').html('<div class="py-10 text-center text-gray-400 font-mono text-xs">Nenhum pagamento registrado.</div>');
        return;
    }
    let html = '';
    pagamentos.forEach(function (p) {
        html += '<div class="px-5 py-4">' +
            '<div class="flex items-start justify-between gap-3">' +
                '<div>' +
                    '<p class="font-mono font-bold text-sm">' + fmtMoeda(p.valor_pago) + '</p>' +
                    '<p class="text-xs font-mono text-gray-400">' + p.pago_em + (p.observacao ? ' · ' + escapeHtml(p.observacao) : '') + '</p>' +
                    '<p class="text-xs font-mono text-gray-300 mt-0.5">' + p.usos_count + ' uso(s) coberto(s)</p>' +
                '</div>' +
                '<div class="relative pagamento-menu-wrapper shrink-0">' +
                    '<button class="btn-pagamento-menu p-1 text-gray-400 hover:text-black font-mono" data-pid="' + p.id + '">···</button>' +
                    '<div class="pagamento-menu hidden absolute right-0 top-6 z-10 bg-white border border-[var(--color-lab-border)] shadow-sm w-44 py-1">' +
                        '<button class="btn-ver-usos block w-full text-left px-4 py-2 text-xs font-mono hover:bg-gray-50" data-usos=\'' + JSON.stringify(p.usos) + '\'>Ver usos</button>' +
                        '<button class="btn-editar-pagamento block w-full text-left px-4 py-2 text-xs font-mono hover:bg-gray-50" data-pid="' + p.id + '" data-valor="' + p.valor_pago + '" data-pago-em="' + p.pago_em + '" data-obs="' + escapeHtml(p.observacao || '') + '">Editar</button>' +
                        '<button class="btn-deletar-pagamento block w-full text-left px-4 py-2 text-xs font-mono text-red-600 hover:bg-red-50" data-pid="' + p.id + '">Excluir</button>' +
                    '</div>' +
                '</div>' +
            '</div>' +
        '</div>';
    });
    $('#historico-lista').html(html);
}
function escapeHtml(str) {
    return $('<div>').text(str || '').html();
}

// ============================================================
// Modal Registrar Pagamento
// ============================================================
function abrirModalPagamento(modo) {
    // modo: 'criar' ou 'editar'
    $('#pagamento-titulo').text(modo === 'criar' ? 'Registrar Pagamento' : 'Editar Pagamento');
    $('#pagamento-id').val('');
    $('#p-valor-pago').val('');
    $('#p-pago-em').val('');
    $('#p-observacao').val('');
    $('#pagamento-erro').addClass('hidden').text('');
    $('#bloco-usos-pendentes').toggle(modo === 'criar');
    if (modo === 'criar') {
        $('#lista-usos-pendentes').html('<div class="text-xs text-gray-400 font-mono py-2 text-center">Carregando...</div>');
        atualizarTotalSelecionado();
        $.getJSON(urlCuponsUsosPendentes(_cupomIdAtivo), function (usos) {
            renderUsosPendentes(usos);
        });
    }
    $('#modal-pagamento').removeClass('hidden').addClass('flex');
}
function fecharModalPagamento() {
    $('#modal-pagamento').addClass('hidden').removeClass('flex');
}
function renderUsosPendentes(usos) {
    if (!usos.length) {
        $('#lista-usos-pendentes').html('<div class="text-xs text-gray-400 font-mono py-2 text-center">Nenhum uso pendente.</div>');
        return;
    }
    let html = '';
    usos.forEach(function (u) {
        html += '<label class="flex items-center gap-2 py-1 cursor-pointer hover:bg-gray-50 px-1">' +
            '<input type="checkbox" class="uso-checkbox w-4 h-4" value="' + u.id + '" data-valor="' + u.comissao_valor + '">' +
            '<span class="text-xs font-mono">Pedido #' + u.pedido_id + ' · ' + u.created_at + ' · <strong>' + fmtMoeda(u.comissao_valor) + '</strong></span>' +
        '</label>';
    });
    $('#lista-usos-pendentes').html(html);
}
function atualizarTotalSelecionado() {
    let total = 0;
    $('.uso-checkbox:checked').each(function () {
        total += parseFloat($(this).data('valor')) || 0;
    });
    $('#total-selecionado').text(fmtMoeda(total));
    if (total > 0 && !$('#pagamento-id').val()) {
        $('#p-valor-pago').val(total.toFixed(2));
    }
}

// ============================================================
// Eventos
// ============================================================
$(document).ready(function () {

    // Abrir modal novo cupom
    $('#btn-novo-cupom').on('click', function () { abrirModalCupom('Novo Cupom', null); });
    $('#modal-fechar').on('click', fecharModalCupom);
    $('#modal-cupom').on('click', function (e) { if ($(e.target).is('#modal-cupom')) fecharModalCupom(); });

    // Busca de usuário no modal de cupom
    $('#f-user-search').on('input', function () {
        const q = $(this).val().trim();
        if (q.length < 2) { hideUserResults(); return; }
        if (couponUserSearchRequest) couponUserSearchRequest.abort();
        couponUserSearchRequest = $.get(cuponsBuscarUsuariosUrl(), { q })
            .done(showUserResults).always(function () { couponUserSearchRequest = null; });
    });
    $('#f-user-clear').on('click', function () { renderUserSelection(null); hideUserResults(); });
    $(document).on('click', function (e) {
        if (!$(e.target).closest('#f-user-search, #f-user-results').length) hideUserResults();
    });

    // Editar cupom
    $(document).on('click', '.btn-editar', function () {
        abrirModalCupom('Editar Cupom', $(this).data('cupom'));
    });

    // Submit form cupom
    $('#form-cupom').on('submit', function (e) {
        e.preventDefault();
        const id   = $('#cupom-id').val();
        const url  = id ? urlCuponsUpdate(id) : '{{ route("admin.cupons.store") }}';
        const meth = id ? 'PUT' : 'POST';
        $.ajax({
            url, method: meth,
            data: {
                _token: csrfToken(),
                codigo: $('#f-codigo').val(),
                user_id: $('#f-user-id').val() || null,
                coupon_portal_enabled: $('#f-coupon-portal-enabled').is(':checked') ? 1 : 0,
                tipo: $('#f-tipo').val(),
                valor: $('#f-valor').val(),
                comissao_percentual: $('#f-comissao-percentual').val() || 100,
                valor_minimo_pedido: $('#f-minimo').val() || null,
                limite_usos: $('#f-limite').val() || null,
                valido_ate: $('#f-validade').val() || null,
                ativo: $('#f-ativo').is(':checked') ? 1 : 0,
            },
            success: function () { window.location.reload(); },
            error: function (xhr) {
                const errors = xhr.responseJSON && xhr.responseJSON.errors;
                const msg = errors ? Object.values(errors).flat().join(' ') : ((xhr.responseJSON && xhr.responseJSON.message) || 'Erro ao salvar.');
                $('#form-erro').removeClass('hidden').text(msg);
            },
        });
    });

    // Toggle status
    $(document).on('click', '.toggle-status', function () {
        const id = $(this).data('id');
        $.post(urlCuponsToggle(id), { _token: csrfToken() }, function () { window.location.reload(); });
    });

    // Deletar cupom
    $(document).on('click', '.btn-deletar', function () {
        $('#deletar-id').val($(this).data('id'));
        $('#modal-deletar').removeClass('hidden').addClass('flex');
    });
    $('#cancelar-deletar').on('click', function () { $('#modal-deletar').addClass('hidden').removeClass('flex'); });
    $('#confirmar-deletar').on('click', function () {
        $.ajax({ url: urlCuponsDelete($('#deletar-id').val()), method: 'DELETE',
            data: { _token: csrfToken() }, success: function () { window.location.reload(); } });
    });

    // Abrir histórico
    $(document).on('click', '.btn-historico, .btn-registrar-pagamento', function () {
        abrirHistorico($(this).data('id'), $(this).data('codigo'));
        if ($(this).hasClass('btn-registrar-pagamento')) {
            setTimeout(function () { abrirModalPagamento('criar'); }, 100);
        }
    });
    $('#historico-fechar').on('click', fecharHistorico);
    $('#modal-historico').on('click', function (e) { if ($(e.target).is('#modal-historico')) fecharHistorico(); });

    // Botão registrar dentro do histórico
    $('#btn-novo-pagamento').on('click', function () { abrirModalPagamento('criar'); });

    // Menu de pagamento (··· no histórico)
    $(document).on('click', '.btn-pagamento-menu', function (e) {
        e.stopPropagation();
        const $m = $(this).siblings('.pagamento-menu');
        $('.pagamento-menu').not($m).addClass('hidden');
        $m.toggleClass('hidden');
    });
    $(document).on('click', function () { $('.pagamento-menu').addClass('hidden'); });

    // Ver usos de um pagamento
    $(document).on('click', '.btn-ver-usos', function () {
        const usos = $(this).data('usos') || [];
        if (!usos.length) {
            $('#usos-pagamento-lista').html('<p class="text-gray-400">Nenhum uso vinculado.</p>');
        } else {
            let html = '';
            usos.forEach(function (u) {
                html += '<div class="flex justify-between gap-2">' +
                    '<span class="text-gray-500">Pedido #' + u.pedido_id + ' · ' + u.created_at + '</span>' +
                    '<span class="font-bold">' + fmtMoeda(u.comissao_valor) + '</span>' +
                '</div>';
            });
            $('#usos-pagamento-lista').html(html);
        }
        $('#modal-usos-pagamento').removeClass('hidden').addClass('flex');
    });
    $('#usos-pagamento-fechar').on('click', function () {
        $('#modal-usos-pagamento').addClass('hidden').removeClass('flex');
    });

    // Editar pagamento
    $(document).on('click', '.btn-editar-pagamento', function () {
        const pid = $(this).data('pid');
        abrirModalPagamento('editar');
        $('#pagamento-id').val(pid);
        $('#p-valor-pago').val($(this).data('valor'));
        const pagoEm = $(this).data('pago-em') || '';
        // Converter dd/mm/YYYY → YYYY-MM-DD para o input date
        const parts = pagoEm.split('/');
        if (parts.length === 3) $('#p-pago-em').val(parts[2] + '-' + parts[1] + '-' + parts[0]);
        $('#p-observacao').val($(this).data('obs') || '');
    });

    // Deletar pagamento
    $(document).on('click', '.btn-deletar-pagamento', function () {
        if (!confirm('Excluir este pagamento? Os usos voltarão para pendente.')) return;
        const pid = $(this).data('pid');
        $.ajax({
            url: urlCuponsPagamentosDelete(_cupomIdAtivo, pid),
            method: 'DELETE',
            data: { _token: csrfToken() },
            success: function () { carregarHistorico(); },
            error: function () { alert('Erro ao excluir pagamento.'); },
        });
    });

    // Checkboxes de usos pendentes
    $(document).on('change', '.uso-checkbox', atualizarTotalSelecionado);
    $('#btn-selecionar-todos').on('click', function () {
        const todos = $('.uso-checkbox');
        const algumDesmarcado = todos.filter(':not(:checked)').length > 0;
        todos.prop('checked', algumDesmarcado);
        atualizarTotalSelecionado();
    });

    // Salvar pagamento
    $('#pagamento-salvar').on('click', function () {
        const pid = $('#pagamento-id').val();
        const valorPago = $('#p-valor-pago').val();
        const pagoEm   = $('#p-pago-em').val();
        const obs      = $('#p-observacao').val();

        if (!valorPago || !pagoEm) {
            $('#pagamento-erro').removeClass('hidden').text('Preencha valor e data.');
            return;
        }

        if (pid) {
            // Editar
            $.ajax({
                url: urlCuponsPagamentosUpdate(_cupomIdAtivo, pid),
                method: 'PUT',
                data: { _token: csrfToken(), valor_pago: valorPago, pago_em: pagoEm, observacao: obs },
                success: function () { fecharModalPagamento(); carregarHistorico(); },
                error: function (xhr) {
                    const msg = xhr.responseJSON?.message || 'Erro ao salvar.';
                    $('#pagamento-erro').removeClass('hidden').text(msg);
                },
            });
        } else {
            // Criar
            const usoIds = [];
            $('.uso-checkbox:checked').each(function () { usoIds.push($(this).val()); });
            $.ajax({
                url: urlCuponsPagamentosStore(_cupomIdAtivo),
                method: 'POST',
                data: { _token: csrfToken(), valor_pago: valorPago, pago_em: pagoEm, observacao: obs, 'uso_ids[]': usoIds },
                success: function () { fecharModalPagamento(); carregarHistorico(); },
                error: function (xhr) {
                    const msg = xhr.responseJSON?.message || 'Erro ao salvar.';
                    $('#pagamento-erro').removeClass('hidden').text(msg);
                },
            });
        }
    });
    $('#pagamento-fechar, #pagamento-cancelar').on('click', fecharModalPagamento);
    $('#modal-pagamento').on('click', function (e) { if ($(e.target).is('#modal-pagamento')) fecharModalPagamento(); });

});
</script>
@endsection
```

- [ ] **Step 2: Limpar cache de views**

```bash
docker exec laravel-app php artisan view:clear
```

- [ ] **Step 3: Verificar que a página renderiza sem erro**

```bash
docker exec laravel-app php artisan route:list 2>&1 | grep "cupons.index"
# Acessar https://jfxtech.com.br/admin/cupons no browser e confirmar que carrega sem 500
```

- [ ] **Step 4: Rodar todos os testes**

```bash
docker exec laravel-app php artisan test 2>&1 | tail -20
```

Esperado: todos os testes PASS (sem regressões).

- [ ] **Step 5: Commit**

```bash
git add resources/views/admin/cupons.blade.php
git commit -m "feat: redesign mobile-first de /admin/cupons com cards, métricas e modais de comissão"
```

---

## Verificação Final

- [ ] Abrir `/admin/cupons` no mobile — cards empilhados sem tabela horizontal
- [ ] Abrir `/admin/cupons` no desktop — grid 2 colunas
- [ ] Menu `···` abre e fecha corretamente
- [ ] Editar um cupom e alterar `comissao_percentual` — salva e reflete nos cards
- [ ] Clicar "Ver histórico" em um cupom com parceiro — modal abre com lista de pagamentos e total pendente
- [ ] Registrar pagamento selecionando usos — usos somem da lista de pendentes após salvar
- [ ] Excluir pagamento — usos voltam para pendentes
- [ ] Rodar `docker exec laravel-app php artisan test` — tudo PASS
