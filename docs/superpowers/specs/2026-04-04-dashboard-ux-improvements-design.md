# Dashboard UX Improvements — Design Spec
**Date:** 2026-04-04
**Status:** Approved

---

## Context

The admin dashboard at `/admin/dashboard` has analytics and alerts, but all actions require navigating away to `/admin/produtos` or `/admin/pedidos` to fix problems. The goal is to make the dashboard a true operational hub: merchants can identify and resolve issues (missing costs, zero stock, pending orders) without leaving the page.

**User goals:**
- Click a product with a problem → fix it inline without leaving the dashboard
- See top-selling products, revenue by category, ticket average
- Quick shortcuts to common actions always visible without scrolling
- Sections stay out of the way when not needed (collapsible)

---

## Architecture

**Single page** — `/admin/dashboard` (no new views). Collapsible sections with `localStorage` persistence per section.

**New backend pieces:**
- `POST /admin/produtos/{id}/quick-edit` → `AdminController::quickEditProduto()` — saves custo_compra + estoque, returns JSON
- New queries in `dashboard()`: top products, revenue by category, ticket médio, pending-action orders

**Existing pieces reused:**
- `admin.js` and `window.routes`/`window.baseUrl` are already loaded on ALL admin pages via `resources/views/includes/header-admin.blade.php` — no extra setup needed on dashboard
- `GET /admin/produtos/{id}` → `buscarProduto()` — JSON endpoint used by `editarProduto(id)` in admin.js

**New backend pieces needed for inline actions:**
- `POST /admin/produtos/{id}/quick-edit` → new `quickEditProduto()` — returns JSON (custo_compra, estoque, margem, lucro)
- `POST /admin/pedidos/{id}/quick-status` → new `quickStatusPedido()` — returns JSON `{success, status}`. The existing `atualizarStatusPedido()` returns a redirect and cannot be used for AJAX — a new slim JSON-only method is needed.

**Modal HTML note:** The full product create/edit modal HTML is defined only in `produtos.blade.php`. To use `abrirModalProduto()` from the dashboard action bar, the modal HTML must be available on the dashboard. Solution: extract modal HTML to `resources/views/admin/includes/modal-produto.blade.php` and `@include` it from both `produtos.blade.php` and `dashboard.blade.php`. This is required for the "+ Novo Produto" action bar button to work.

---

## Layout

```
┌──────────────────────────────────────────────────────────────┐
│ ACTION BAR: [+ Produto] [Pendentes:N] [Processando:N] [→Pedidos] [Exportar] │
├──────────────────────────────────────────────────────────────┤
│ KPI CARDS: Receita | Lucro | Margem % | Pedidos Pendentes    │
├──────────────────────────────────────────────────────────────┤
│ ▼ ALERTAS + STATUS (dobrável, default open)                  │
│   [Alertas expansíveis] [Status pedidos] [Ações rápidas]     │
├──────────────────────────────────────────────────────────────┤
│ ▼ PEDIDOS QUE PRECISAM DE AÇÃO (dobrável, default open)      │
│   Pendentes + Processando com botão avançar status inline    │
├──────────────────────────────────────────────────────────────┤
│ ▼ PERFORMANCE (dobrável, default open)                       │
│   Top 5 vendidos | Receita/categoria | Ticket médio          │
├──────────────────────────────────────────────────────────────┤
│ ▼ ANALYTICS DE PRODUTOS (dobrável, default open)             │
│   Tabela sortável + botão quick-fix por linha                │
├──────────────────────────────────────────────────────────────┤
│ ▼ PEDIDOS RECENTES (dobrável, default collapsed)             │
└──────────────────────────────────────────────────────────────┘
```

---

## Components

### 1. Action Bar

Horizontal strip between the page header and KPI cards. Always visible.

Buttons:
| Button | Action |
|---|---|
| `+ Novo Produto` | Calls `abrirModalProduto()` from existing admin.js |
| `Pendentes: N` | Link to `/admin/pedidos` |
| `Processando: N` | Link to `/admin/pedidos` |
| `Ver Pedidos` | Link to `/admin/pedidos` |
| `Exportar CSV` | `POST /admin/produtos/exportar` (existing route) |

Requires: products modal HTML included in dashboard + `admin.js` + `window.routes` and `window.baseUrl` set.

---

### 2. Alerts Card — Expandable Rows with Quick-Fix

Each alert line that maps to fixable products becomes expandable. On click, it toggles a sub-list of affected products.

**Expandable alerts:**
- "Preço de compra não cadastrado" → shows products with `custo_compra IS NULL`
- "Estoque zerado (ativo)" → shows products with `ativo=true AND estoque=0`
- "Margem negativa" → shows products with negative margin
- "Margem abaixo de 20%" → shows products with margin 0–19%

**Non-expandable (counts only):**
- Sem imagem, Produtos inativos — no inline fix available

**Expanded row format:**
```
▼ Preço de compra não cadastrado                              15
  ┌─────────────────────────────────────────────────────────┐
  │ BenQ Zowie XL2586X+ 600Hz   R$ 10.000  [✏ Editar custo]│
  │ Alienware AW2524HF 500Hz    R$  4.500  [✏ Editar custo]│
  └─────────────────────────────────────────────────────────┘
```

The affected product lists are passed from the controller: `$alertas['produtos_sem_custo']`, `$alertas['produtos_estoque_zerado']`, `$alertas['produtos_margem_negativa']`, `$alertas['produtos_margem_baixa']` — collections with `id`, `nome`, `preco`.

Controller change: expand `$alertas` to include the actual product collections, not just counts.

---

### 3. Quick-Fix Modal

Lightweight modal (distinct from the full product edit modal).

**Trigger:** `abrirQuickFix(id, nome, custo_atual, estoque_atual)` — called from alert sub-list and from analytics table row button.

**Fields:**
- Nome do produto (read-only, for context)
- Custo de compra (R$) — pre-filled with current value or empty
- Estoque — pre-filled with current value

**Save:** `fetch('POST /admin/produtos/{id}/quick-edit', { custo_compra, estoque })`

**On success (JSON response):**
- Close modal
- Update the matching row in the analytics table (`data-custo`, `data-lucro`, `data-margem` attributes + visible cells + margin badge)
- Re-evaluate and update the alert counts in the alerts card (or remove the product from the expanded list)
- Show brief success toast: "Salvo"

**New endpoint:** `POST /admin/produtos/{id}/quick-edit`
```php
public function quickEditProduto(Request $request, $id)
{
    $produto = Produto::findOrFail($id);
    $data = [];
    if ($request->filled('custo_compra')) {
        $data['custo_compra'] = $this->parseMoneyInput($request->custo_compra);
    }
    if ($request->filled('estoque')) {
        $data['estoque'] = (int) $request->estoque;
    }
    $produto->update($data);
    return response()->json([
        'success'         => true,
        'custo_compra'    => $produto->custo_compra,
        'estoque'         => $produto->estoque,
        'margem'          => $produto->margem_bruta_percentual,
        'lucro'           => $produto->lucro_bruto_unitario,
    ]);
}
```

---

### 4. Analytics Table — Quick-Fix Button per Row

Add a final column `Ações` with a small pencil icon button:
```html
<td><button onclick="abrirQuickFix({{ $p->id }}, '...', '...', {{ $p->estoque }})">✏</button></td>
```

Data attributes on each `<tr>` already exist (`data-custo`, `data-margem`, etc.) — the JS success handler reads/updates these.

---

### 5. Section: Pedidos que Precisam de Ação

Shows only `pendente` and `processando` orders. Each row has a "→ Avançar" button.

**Status progression:**
- `pendente` → button says "→ Processando"
- `processando` → button says "→ Enviado"

**On click:** `fetch('POST /admin/pedidos/{id}/quick-status', { status: 'processando' })` — new JSON endpoint `quickStatusPedido()`. The existing `atualizarStatusPedido()` returns a redirect and cannot be used for AJAX calls.

**On success:** Row transitions to new status badge; if "enviado", row fades out and is removed after 1s.

**Data:** `$pedidos_acao` — `Pedido::whereIn('status', ['pendente', 'processando'])->with('user:id,name')->orderBy('created_at')->get()`

---

### 6. Section: Performance

Three sub-blocks in a 3-column grid:

**Top 5 Mais Vendidos:**
- Source: `ItemPedido` grouped by `produto_id`, sum `quantidade`, only from `entregue` orders, limit 5
- Shows: rank, nome, marca, unidades vendidas, receita gerada

**Receita por Categoria:**
- Source: join `pedidos` → `itens_pedido` → `produtos` → `categorias`, group by `categorias.id`, sum `preco * quantidade`, only `entregue`
- Shows: nome da categoria, receita, simple percentage bar

**Métricas Gerais:**
- Ticket médio: `$receita_total / max($pedidos_entregues, 1)`
- Total unidades vendidas: `ItemPedido::whereHas('pedido', entregue)->sum('quantidade')`
- Produtos ativos: `Produto::where('ativo', true)->count()`

---

### 7. Collapsible Sections

Each section has a header with a toggle arrow (`▼ / ▶`). State persisted in `localStorage` keyed by section ID.

**Default states:**
- Alertas + Status: open
- Pedidos que precisam de ação: open
- Performance: open
- Analytics de Produtos: open
- Pedidos Recentes: **collapsed** (least urgent)

---

## Files to Modify

| File | Change |
|---|---|
| `app/Http/Controllers/AdminController.php` | Expand `dashboard()` with new queries; add `quickEditProduto()` and `quickStatusPedido()` |
| `routes/web.php` | Add `POST /admin/produtos/{id}/quick-edit` and `POST /admin/pedidos/{id}/quick-status` |
| `resources/views/admin/dashboard.blade.php` | Full rewrite with all new sections |
| `resources/views/admin/produtos.blade.php` | Replace inline modal HTML with `@include('admin.includes.modal-produto')` |
| `resources/views/admin/includes/modal-produto.blade.php` | **New file** — extracted modal HTML from produtos.blade.php |

---

## Verification

1. `/admin/dashboard` loads without errors
2. Action bar: "+ Novo Produto" opens the full product modal; pending/processing counts match reality
3. Alerts expand on click showing correct affected products
4. Quick-fix modal opens from alert row and from table row button; saving updates the row without reload
5. Order status advances inline; row disappears after reaching "enviado"
6. Performance section shows correct top-5, category breakdown, ticket médio
7. Collapsible sections toggle and persist across page reloads (localStorage)
8. No regression on `/admin/produtos` or `/admin/pedidos`
