# Pedido Detail — Comparativo Receita vs Custo Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Replace the confusing "Custo real vs sistema" section on the order detail page with a clear "Receita vs Custo" margin table that compares selling price against purchase cost.

**Architecture:** Blade-only change. All necessary data is already computed in `buildOrderDetailAnalytics()` and passed to the view via `$analytics['items']` and `$analytics['summary']`. No PHP controller or service changes needed. The section at lines 484–562 of `resources/views/admin/includes/pedido-detalhes.blade.php` is replaced.

**Tech Stack:** Blade + Tailwind CSS 4 (Sober Tech — monochromatic black/white, no color except semantic alerts)

---

## Problem Statement

On `/admin/pedidos/{id}`, the section titled "Custo real vs sistema" compares:
- `real_unit_cost` = `itens_pedido.custo_unitario_declarado` (what the admin typed during preparation)
- `catalog_unit_cost` = `produtos.custo_compra` (what's stored in the catalog)

This is **cost vs cost** — it only shows rows where the admin has already declared a cost, and it gives no insight into whether the order was profitable. The user wants **price vs cost** — what we charged the customer vs what it cost us.

The data needed is already in `$analytics['items']`:
- `preco_unitario` — selling price per unit (from `itens_pedido.preco`)
- `custo_unitario` — best available cost (declared > catalog > null)
- `lucro_unitario` — `preco_unitario - custo_unitario`
- `lucro_total` — `lucro_unitario × quantidade`
- `margem_percentual` — `(lucro_total / receita) × 100`
- `cost_source` — `'declarado'` | `'catalogo'` | `'sem_custo'`
- `receita` — net revenue after coupon discount rateio (0 if item is canceled)
- `is_canceled` — whether item was canceled during preparation
- `produto_nome`, `quantidade`, `variant_label`

And in `$analytics['summary']`:
- `receita_itens` — total net revenue across active items
- `custo_total_estimado` — total known cost across active items
- `lucro_produtos_estimado` — total profit (revenue - cost)
- `margem_produtos_percentual` — overall margin % (null if any item has no cost)
- `itens_sem_custo` — count of items with no cost

---

## File Structure

**Single file changed:**
- Modify: `resources/views/admin/includes/pedido-detalhes.blade.php:484–562`

---

### Task 1: Replace section "Custo real vs sistema" with "Receita vs Custo"

**Files:**
- Modify: `resources/views/admin/includes/pedido-detalhes.blade.php:484–562`

**Context — what the current block looks like (lines 484–562):**

```html
<section class="border border-[var(--color-lab-border)] bg-white">
    <div class="px-5 py-4 sm:px-6 border-b border-[var(--color-lab-border)]">
        <div class="flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <p class="font-mono text-[10px] uppercase tracking-[0.22em] text-[var(--color-lab-muted)]">Custo real vs sistema</p>
                <h4 class="mt-1 text-lg font-bold tracking-tight text-black">Comparativo do preço confirmado</h4>
            </div>
            <p class="font-mono text-[10px] uppercase tracking-[0.16em] text-[var(--color-lab-muted)]">{{ $costComparisonSummary['items_count'] ?? 0 }} item(ns) com custo real</p>
        </div>
    </div>
    ... (4 summary cards + table of declared-vs-catalog rows) ...
</section>
```

**What to do:**

- [ ] **Step 1: Read the exact current block to confirm line numbers**

  ```bash
  sed -n '484,562p' resources/views/admin/includes/pedido-detalhes.blade.php
  ```

  Expected: the `<section class="border...">` block for "Custo real vs sistema" covering lines 484–562.

- [ ] **Step 2: Replace lines 484–562 with the new section**

  In `resources/views/admin/includes/pedido-detalhes.blade.php`, replace the entire block from the opening `<section` on line 484 to the closing `</section>` on line 562 with:

  ```blade
  <section class="border border-[var(--color-lab-border)] bg-white">
      <div class="px-5 py-4 sm:px-6 border-b border-[var(--color-lab-border)]">
          <div class="flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-between">
              <div>
                  <p class="font-mono text-[10px] uppercase tracking-[0.22em] text-[var(--color-lab-muted)]">Receita vs custo</p>
                  <h4 class="mt-1 text-lg font-bold tracking-tight text-black">Margem por item vendido</h4>
              </div>
              <p class="font-mono text-[10px] uppercase tracking-[0.16em] text-[var(--color-lab-muted)]">{{ $summary['itens_sem_custo'] > 0 ? $summary['itens_sem_custo'] . ' item(ns) sem custo' : 'Todos os itens com custo' }}</p>
          </div>
      </div>

      <div class="p-4 sm:p-6 space-y-4">
          {{-- Summary cards --}}
          <div class="grid grid-cols-2 xl:grid-cols-4 gap-3">
              <div class="border border-[var(--color-lab-border)] bg-[var(--color-lab-bg)] px-4 py-4">
                  <p class="font-mono text-xs uppercase tracking-[0.16em] text-[var(--color-lab-muted)]">Receita (itens)</p>
                  <p class="mt-2 font-mono text-xl font-bold text-black">R$ {{ number_format($summary['receita_itens'], 2, ',', '.') }}</p>
                  <p class="mt-1 font-mono text-[10px] text-[var(--color-lab-muted)]">Após desconto de cupom</p>
              </div>
              <div class="border border-[var(--color-lab-border)] bg-[var(--color-lab-bg)] px-4 py-4">
                  <p class="font-mono text-xs uppercase tracking-[0.16em] text-[var(--color-lab-muted)]">Custo total</p>
                  <p class="mt-2 font-mono text-xl font-bold text-black">R$ {{ number_format($summary['custo_total_estimado'], 2, ',', '.') }}</p>
                  <p class="mt-1 font-mono text-[10px] text-[var(--color-lab-muted)]">{{ $costModeLabel }}</p>
              </div>
              @php $lucroProdutos = $summary['lucro_produtos_estimado'] ?? 0; @endphp
              <div class="border border-[var(--color-lab-border)] bg-[var(--color-lab-bg)] px-4 py-4">
                  <p class="font-mono text-xs uppercase tracking-[0.16em] text-[var(--color-lab-muted)]">Lucro bruto</p>
                  <p class="mt-2 font-mono text-xl font-bold {{ $lucroProdutos < 0 ? 'text-red-600' : 'text-black' }}">
                      R$ {{ number_format($lucroProdutos, 2, ',', '.') }}
                  </p>
                  <p class="mt-1 font-mono text-[10px] text-[var(--color-lab-muted)]">Receita menos custo</p>
              </div>
              @php $mpct = $summary['margem_produtos_percentual']; @endphp
              <div class="border border-[var(--color-lab-border)] bg-[var(--color-lab-bg)] px-4 py-4">
                  <p class="font-mono text-xs uppercase tracking-[0.16em] text-[var(--color-lab-muted)]">Margem</p>
                  <p class="mt-2 font-mono text-xl font-bold {{ $mpct === null ? 'text-[var(--color-lab-muted)]' : ($mpct < 0 ? 'text-red-600' : 'text-black') }}">
                      {{ $mpct !== null ? number_format($mpct, 1, ',', '.') . '%' : 'N/A' }}
                  </p>
                  <p class="mt-1 font-mono text-[10px] text-[var(--color-lab-muted)]">{{ $mpct === null ? $summary['itens_sem_custo'] . ' item(ns) sem custo' : 'Sobre receita líquida itens' }}</p>
              </div>
          </div>

          {{-- Per-item table --}}
          @php $activeItems = collect($items)->reject(fn($item) => $item['is_canceled'])->values(); @endphp
          @if($activeItems->count())
              <div class="overflow-x-auto border border-[var(--color-lab-border)]">
                  <table class="w-full min-w-[700px] text-sm">
                      <thead>
                          <tr class="border-b border-[var(--color-lab-border)] bg-[var(--color-lab-bg)]">
                              <th class="px-4 py-3 text-left font-mono text-[10px] uppercase tracking-widest text-[var(--color-lab-muted)]">Produto</th>
                              <th class="px-4 py-3 text-right font-mono text-[10px] uppercase tracking-widest text-[var(--color-lab-muted)]">Qtd</th>
                              <th class="px-4 py-3 text-right font-mono text-[10px] uppercase tracking-widest text-[var(--color-lab-muted)]">Venda/un.</th>
                              <th class="px-4 py-3 text-right font-mono text-[10px] uppercase tracking-widest text-[var(--color-lab-muted)]">Custo/un.</th>
                              <th class="px-4 py-3 text-right font-mono text-[10px] uppercase tracking-widest text-[var(--color-lab-muted)]">Lucro/un.</th>
                              <th class="px-4 py-3 text-right font-mono text-[10px] uppercase tracking-widest text-[var(--color-lab-muted)]">Lucro total</th>
                              <th class="px-4 py-3 text-right font-mono text-[10px] uppercase tracking-widest text-[var(--color-lab-muted)]">Margem</th>
                          </tr>
                      </thead>
                      <tbody>
                          @foreach($activeItems as $row)
                              @php
                                  $hasCost = $row['cost_source'] !== 'sem_custo';
                                  $lucroUn = $row['lucro_unitario'];
                                  $lucroTot = $row['lucro_total'];
                                  $margem = $row['margem_percentual'];
                                  $lucroBad = $lucroTot !== null && $lucroTot < 0;
                              @endphp
                              <tr class="border-b border-[var(--color-lab-border)] last:border-b-0">
                                  <td class="px-4 py-3">
                                      <p class="font-mono text-xs font-bold text-black break-words">{{ $row['produto_nome'] }}</p>
                                      @if($row['variant_label'])
                                          <p class="mt-0.5 font-mono text-[10px] text-[var(--color-lab-muted)]">{{ $row['variant_label'] }}</p>
                                      @endif
                                      @if($row['cost_source'] === 'declarado')
                                          <span class="mt-1 inline-block font-mono text-[9px] uppercase tracking-widest border border-black px-1 py-0.5">Custo real</span>
                                      @elseif($row['cost_source'] === 'catalogo')
                                          <span class="mt-1 inline-block font-mono text-[9px] uppercase tracking-widest border border-[var(--color-lab-border)] px-1 py-0.5 text-[var(--color-lab-muted)]">Catálogo</span>
                                      @else
                                          <span class="mt-1 inline-block font-mono text-[9px] uppercase tracking-widest border border-[var(--color-lab-border)] px-1 py-0.5 text-[var(--color-lab-muted)]">Sem custo</span>
                                      @endif
                                  </td>
                                  <td class="px-4 py-3 text-right font-mono text-xs text-black">{{ $row['quantidade'] }}</td>
                                  <td class="px-4 py-3 text-right font-mono text-xs text-black">R$ {{ number_format($row['preco_unitario'], 2, ',', '.') }}</td>
                                  <td class="px-4 py-3 text-right font-mono text-xs {{ $hasCost ? 'text-black' : 'text-gray-400' }}">
                                      {{ $hasCost ? 'R$ ' . number_format($row['custo_unitario'], 2, ',', '.') : 'Sem custo' }}
                                  </td>
                                  <td class="px-4 py-3 text-right font-mono text-xs font-bold {{ $lucroUn === null ? 'text-gray-400' : ($lucroUn < 0 ? 'text-red-600' : 'text-black') }}">
                                      {{ $lucroUn !== null ? 'R$ ' . number_format($lucroUn, 2, ',', '.') : 'N/A' }}
                                  </td>
                                  <td class="px-4 py-3 text-right font-mono text-xs font-bold {{ $lucroTot === null ? 'text-gray-400' : ($lucroBad ? 'text-red-600' : 'text-black') }}">
                                      {{ $lucroTot !== null ? 'R$ ' . number_format($lucroTot, 2, ',', '.') : 'N/A' }}
                                  </td>
                                  <td class="px-4 py-3 text-right font-mono text-xs {{ $margem === null ? 'text-gray-400' : ($margem < 0 ? 'text-red-600 font-bold' : 'text-black') }}">
                                      {{ $margem !== null ? number_format($margem, 1, ',', '.') . '%' : 'N/A' }}
                                  </td>
                              </tr>
                          @endforeach
                      </tbody>
                      <tfoot>
                          <tr class="border-t-2 border-[var(--color-lab-border)] bg-[var(--color-lab-bg)]">
                              <td class="px-4 py-3 font-mono text-[10px] uppercase tracking-widest text-[var(--color-lab-muted)]">Total</td>
                              <td class="px-4 py-3 text-right font-mono text-xs font-bold text-black">{{ $summary['unidades'] }}</td>
                              <td class="px-4 py-3 text-right font-mono text-xs font-bold text-black">R$ {{ number_format($summary['receita_itens'], 2, ',', '.') }}</td>
                              <td class="px-4 py-3 text-right font-mono text-xs font-bold text-black">R$ {{ number_format($summary['custo_total_estimado'], 2, ',', '.') }}</td>
                              @php $lpTotal = $summary['lucro_produtos_estimado'] ?? 0; @endphp
                              <td class="px-4 py-3"></td>
                              <td class="px-4 py-3 text-right font-mono text-xs font-bold {{ $lpTotal < 0 ? 'text-red-600' : 'text-black' }}">R$ {{ number_format($lpTotal, 2, ',', '.') }}</td>
                              <td class="px-4 py-3 text-right font-mono text-xs font-bold {{ $mpct === null ? 'text-gray-400' : ($mpct < 0 ? 'text-red-600' : 'text-black') }}">
                                  {{ $mpct !== null ? number_format($mpct, 1, ',', '.') . '%' : 'N/A' }}
                              </td>
                          </tr>
                      </tfoot>
                  </table>
              </div>
          @else
              <p class="font-mono text-xs text-[var(--color-lab-muted)]">Todos os itens do pedido foram cancelados.</p>
          @endif

          {{-- Audit: declared vs catalog cost — only shown when admin has declared costs --}}
          @if($hasDeclaredCosts && ($costComparison['rows'] ?? collect())->count())
              <details class="border border-[var(--color-lab-border)]">
                  <summary class="flex items-center justify-between gap-3 px-4 py-3 cursor-pointer select-none bg-[var(--color-lab-bg)] hover:bg-[var(--color-lab-border)] transition-colors">
                      <span class="font-mono text-[10px] uppercase tracking-[0.18em] text-[var(--color-lab-muted)]">Auditoria — custo declarado vs catálogo</span>
                      <span class="font-mono text-[10px] text-[var(--color-lab-muted)]">{{ ($costComparisonSummary['items_count'] ?? 0) }} item(ns)</span>
                  </summary>
                  <div class="overflow-x-auto border-t border-[var(--color-lab-border)]">
                      <table class="w-full min-w-[680px] text-sm">
                          <thead>
                              <tr class="border-b border-[var(--color-lab-border)] bg-[var(--color-lab-bg)]">
                                  <th class="px-4 py-3 text-left font-mono text-[10px] uppercase tracking-widest text-[var(--color-lab-muted)]">Produto</th>
                                  <th class="px-4 py-3 text-right font-mono text-[10px] uppercase tracking-widest text-[var(--color-lab-muted)]">Qtd</th>
                                  <th class="px-4 py-3 text-right font-mono text-[10px] uppercase tracking-widest text-[var(--color-lab-muted)]">Declarado/un.</th>
                                  <th class="px-4 py-3 text-right font-mono text-[10px] uppercase tracking-widest text-[var(--color-lab-muted)]">Catálogo/un.</th>
                                  <th class="px-4 py-3 text-right font-mono text-[10px] uppercase tracking-widest text-[var(--color-lab-muted)]">Dif. total</th>
                                  <th class="px-4 py-3 text-right font-mono text-[10px] uppercase tracking-widest text-[var(--color-lab-muted)]">%</th>
                              </tr>
                          </thead>
                          <tbody>
                              @foreach($costComparison['rows'] as $row)
                                  <tr class="border-b border-[var(--color-lab-border)] last:border-b-0">
                                      <td class="px-4 py-3">
                                          <p class="font-mono text-xs font-bold text-black break-words">{{ $row['produto_nome'] }}</p>
                                          <p class="mt-0.5 font-mono text-[10px] text-[var(--color-lab-muted)]">{{ $row['variant_label'] ?: $row['status_preparacao_label'] }}</p>
                                      </td>
                                      <td class="px-4 py-3 text-right font-mono text-xs text-black">{{ $row['quantidade'] }}</td>
                                      <td class="px-4 py-3 text-right font-mono text-xs text-black">R$ {{ number_format($row['real_unit_cost'], 2, ',', '.') }}</td>
                                      <td class="px-4 py-3 text-right font-mono text-xs {{ $row['catalog_unit_cost'] === null ? 'text-gray-400' : 'text-black' }}">
                                          {{ $row['catalog_unit_cost'] !== null ? 'R$ ' . number_format($row['catalog_unit_cost'], 2, ',', '.') : 'Sem custo' }}
                                      </td>
                                      <td class="px-4 py-3 text-right font-mono text-xs font-bold {{ $row['delta_total'] > 0 ? 'text-red-600' : ($row['delta_total'] < 0 ? 'text-emerald-700' : 'text-black') }}">
                                          @if($row['delta_total'] === null)
                                              N/A
                                          @else
                                              {{ $row['delta_total'] > 0 ? '+ ' : ($row['delta_total'] < 0 ? '- ' : '') }}R$ {{ number_format(abs($row['delta_total']), 2, ',', '.') }}
                                          @endif
                                      </td>
                                      <td class="px-4 py-3 text-right font-mono text-xs text-[var(--color-lab-muted)]">
                                          {{ $row['delta_percent'] !== null ? number_format($row['delta_percent'], 1, ',', '.') . '%' : 'N/A' }}
                                      </td>
                                  </tr>
                              @endforeach
                          </tbody>
                      </table>
                  </div>
              </details>
          @endif
      </div>
  </section>
  ```

- [ ] **Step 3: Clear view cache and verify the page loads**

  ```bash
  docker exec laravel-app php artisan view:clear
  docker exec laravel-app php artisan cache:clear
  ```

  Then open `/admin/pedidos/{any_id}` in the browser and confirm:
  - Section title now reads "Receita vs custo" / "Margem por item vendido"
  - Summary cards show: Receita (itens), Custo total, Lucro bruto, Margem
  - Table shows each active item row with columns: Produto | Qtd | Venda/un. | Custo/un. | Lucro/un. | Lucro total | Margem
  - Totals row at the bottom of the table
  - For orders where the admin has declared costs: collapsed `<details>` section "Auditoria — custo declarado vs catálogo" appears below the table
  - For orders with no declared costs: audit section is hidden

- [ ] **Step 4: Run the tests to confirm nothing broke**

  ```bash
  docker exec laravel-app php artisan test --filter=AdminOrderDetailsAnalyticsTest
  docker exec laravel-app php artisan test --filter=MercadoPagoCheckoutTest
  ```

  Expected: both test suites pass. If any test references `$costComparisonSummary` keys or the old section labels, update the test assertions to match the new section's output — the underlying data keys (`cost_comparison`, `summary`, etc.) are unchanged.

- [ ] **Step 5: Commit**

  ```bash
  git add resources/views/admin/includes/pedido-detalhes.blade.php
  git commit -m "refactor: substituir seção custo-vs-sistema por tabela receita-vs-custo no detalhe do pedido"
  ```

---

## Edge Cases to Verify Manually

| Scenario | Expected behavior |
|---|---|
| Order with no cost registered for any item | Custo/un. column shows "Sem custo", Lucro/un. and Margem show "N/A", summary Margem shows "N/A" |
| Order with mix of declared and catalog costs | Each item row shows appropriate badge; totals still computed |
| Order where all items were canceled in preparation | Table body is empty, message "Todos os itens do pedido foram cancelados." shown |
| Order with admin-declared cost AND catalog cost | Audit `<details>` appears below table; click to expand shows declared vs catalog delta |
| Order with coupon discount | Receita (itens) correctly shows net revenue after coupon rateio |
| Negative margin item | Margem cell and Lucro cells render in red |
