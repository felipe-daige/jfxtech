# ERP Dashboard Pipeline + Rastreio Design

**Date:** 2026-04-06  
**Status:** Approved

## Context

The admin dashboard currently defines "pedidos que precisam de ação" (orders needing action) as `status = 'pendente'` — but `pendente` means the customer hasn't paid yet. This is wrong. Orders that need admin action are those with confirmed payment that haven't been delivered: `pago` and `processando`.

Additionally, the shop needs ERP-like fulfillment pipeline visibility and a shipping tracking code field so that after shipping via Correios, the admin can register the tracking code and customers can follow their package.

**Operational flow:**
1. Customer pays → `status = pago`
2. Admin prepares/packs order → changes to `processando`
3. Admin ships via Correios → enters tracking code → changes to `enviado`
4. Customer receives → `entregue`

## Scope

**In scope:**
- Fix "pedidos que precisam de ação" query (pendente → pago + processando)
- Add fulfillment pipeline visual to dashboard (Pago → Processando → Enviado → Entregue)
- SLA alerts on dashboard
- `codigo_rastreio` field on Pedido model + migration
- Admin: tracking code field in existing pedido-detalhes modal
- Customer: tracking code + Correios link on Meus Pedidos (index + show)

**Out of scope:** NFE generation, Correios API integration, new admin pages.

## Section 1: Database & Model

**Migration:** Add `codigo_rastreio VARCHAR(50) NULLABLE` to `pedidos` table.

```php
$table->string('codigo_rastreio', 50)->nullable()->after('frete_valor');
```

**Pedido model** (`app/Models/Pedido.php`): Add `codigo_rastreio` to `$fillable`.

## Section 2: Dashboard — Pipeline & SLA Alerts

**File:** `app/Http/Controllers/AdminController.php` → `getDashboardAnalyticsData()`

### Fix "pedidos que precisam de ação"

```php
// BEFORE (wrong)
$pedidos_acao = Pedido::where('status', 'pendente')->...->get();

// AFTER (correct)
$pedidos_acao = Pedido::whereIn('status', ['pago', 'processando'])
    ->with('user:id,name')
    ->orderBy('created_at')
    ->get();
```

### SLA Alert Queries (add to getDashboardAnalyticsData)

```php
$sla_pago_sem_processar = Pedido::where('status', 'pago')
    ->where('updated_at', '<', now()->subHours(24))
    ->count();

$sla_processando_sem_enviar = Pedido::where('status', 'processando')
    ->where('updated_at', '<', now()->subDays(3))
    ->count();

$sla_enviado_sem_entregar = Pedido::where('status', 'enviado')
    ->where('updated_at', '<', now()->subDays(15))
    ->count();
```

### Pipeline Counts (add to getDashboardAnalyticsData)

```php
// Already exist: pedidos_pagos, pedidos_processando, pedidos_enviados, pedidos_entregues
// Just pass them to the view — the pipeline visual uses these existing variables.
```

### Dashboard View (`resources/views/admin/dashboard.blade.php`)

Replace the current "pedidos que precisam de ação" section with:

1. **SLA Alert banners** (if count > 0, yellow/red): shown above pipeline
   - "X pedidos pagos há mais de 24h sem processar"
   - "X pedidos em processamento há mais de 3 dias sem enviar"
   - "X pedidos enviados há mais de 15 dias sem confirmação de entrega"

2. **Pipeline visual** — 4 cards in a row:
   ```
   ┌─────────┬─────────────┬─────────┬──────────┐
   │  PAGO   │ PROCESSANDO │ ENVIADO │ ENTREGUE │
   │   12    │      5      │    8    │   143    │
   │ ⚡ ação │   ⚡ ação   │ aguard. │   ✓ ok   │
   └─────────┴─────────────┴─────────┴──────────┘
   ```
   Each card links to `/admin/pedidos?status={status}` for filtering.

3. **Action list** below pipeline — lists `pedidos_acao` (pago + processando) with:
   - Order ID, customer name, value, age (created_at)
   - Quick status advance button (existing logic, same UX)

## Section 3: Admin — Tracking Code in Pedido-Detalhes Modal

**Files:**
- `resources/views/admin/includes/pedido-detalhes.blade.php` — add tracking field
- `app/Http/Controllers/AdminController.php` — add `atualizarRastreio()` method
- `routes/web.php` — add PATCH route
- `public/js/admin.js` — add AJAX handler for saving

**New route:**
```php
Route::patch('/admin/pedidos/{pedido}/rastreio', [AdminController::class, 'atualizarRastreio'])
    ->name('admin.pedidos.rastreio');
```

**New controller method:**
```php
public function atualizarRastreio(Request $request, Pedido $pedido): JsonResponse
{
    if (!Auth::check()) abort(403);
    $request->validate(['codigo_rastreio' => 'nullable|string|max:50']);
    $pedido->update(['codigo_rastreio' => $request->codigo_rastreio]);
    return response()->json(['success' => true]);
}
```

**Modal UI addition** (in pedido-detalhes.blade.php):
```html
<div class="mt-4">
    <label>Código de Rastreio (Correios)</label>
    <div class="flex gap-2">
        <input type="text" id="rastreio-input" value="{{ $pedido->codigo_rastreio }}" 
               placeholder="Ex: BR123456789BR" maxlength="50">
        <button onclick="salvarRastreio({{ $pedido->id }})">Salvar</button>
    </div>
    <span id="rastreio-feedback" class="hidden text-green-600">Salvo!</span>
</div>
```

**JS (admin.js):** `salvarRastreio(pedidoId)` — PATCH via fetch, shows "Salvo!" feedback for 2s.

## Section 4: Customer — Meus Pedidos

**Files:**
- `resources/views/site/pedidos/index.blade.php`
- `resources/views/site/pedidos/show.blade.php`

**No controller changes needed** — `codigo_rastreio` is on the Pedido model and already loaded.

**Correios link format:**
```
https://rastreamento.correios.com.br/app/index.php?objetos={codigo_rastreio}
```

**In index.blade.php** — add below status badge (only if `$pedido->codigo_rastreio`):
```html
@if($pedido->codigo_rastreio)
<div class="text-sm">
    📦 Rastreio: <span>{{ $pedido->codigo_rastreio }}</span>
    <a href="https://rastreamento.correios.com.br/app/index.php?objetos={{ $pedido->codigo_rastreio }}"
       target="_blank">Rastrear ↗</a>
</div>
@endif
```

**In show.blade.php** — same info, more prominent block with title "Rastreamento do Pedido".

## Verification

1. Run migration: `docker exec laravel-app php artisan migrate --force`
2. Dashboard shows pipeline with 4 stages; "pedidos que precisam de ação" shows `pago` + `processando` orders
3. SLA alerts appear when orders are stuck beyond thresholds (can test by manually updating `updated_at`)
4. In admin pedido-detalhes modal: enter a tracking code, click Salvar, see "Salvo!" and verify DB updated
5. As customer: view order with tracking code, see "Rastrear ↗" link pointing to Correios URL
6. Run tests: `docker exec laravel-app php artisan test`
