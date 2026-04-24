# Analytics Produto — UI Redesign Spec

**Date:** 2026-04-24  
**Route:** `/admin/analytics/produtos/{id}`  
**View:** `resources/views/admin/analytics-produto.blade.php`  
**Controller:** `AdminController::analyticsProductShow()` + `getProductAnalyticsData()`

---

## Context

The product analytics detail page currently shows only static KPI cards in a single vertical column with no charts or visualizations. The goal is to enrich the UI with a professional analytics feel: a reorganized KPI grid, three time-series charts (ApexCharts via CDN), and a more visually expressive commercial/catalog section — without changing any business logic.

---

## Architecture Overview

### Frontend
- **ApexCharts** loaded via CDN `<script>` tag in the blade view (no npm/build step)
- Chart data passed from blade → JS via `@json($timeSeries)` inline variable
- All chart JS written inline in the blade (consistent with existing pattern in admin pages)
- No changes to `public/js/admin.js`

### Backend
- New private method `getProductTimeSeries(Produto $produto, string $period): array` in `AdminController`
- Called inside `analyticsProductShow()` and passed to the view as `$timeSeries`
- Returns array of `['date' => string, 'receita' => float, 'unidades' => int, 'margem' => float]`

---

## Section 1 — KPI Grid

**Change:** Single vertical column → **3×2 grid** (3 cols desktop, 2 tablet, 1 mobile)

Card order:
```
[ Receita Total ]  [ Lucro Bruto ]   [ Margem Bruta ]
[ Unidades Vend.]  [ Ticket Médio ]  [ Itens s/ Custo ]
```

Each card gets:
- Small monospace label (top)
- Large bold primary value
- Subtitle with context (e.g., "em X pedidos", "por unidade")
- Discrete health indicator (colored dot or badge) — existing color logic unchanged

No business logic changes. Layout and typography only.

---

## Section 2 — Charts

Three charts stacked vertically below the KPI grid. Each has a title and visible period label.

### Chart 1 — Receita ao Longo do Tempo (area + gradient)
- X: dates | Y: R$ value
- Tooltip: date + receita + nº pedidos
- Gradient fill under the line

### Chart 2 — Unidades Vendidas (vertical bars)
- X: dates | Y: quantity
- Monochromatic color consistent with admin design language

### Chart 3 — Margem Bruta % (line)
- X: dates | Y: percentage
- Horizontal reference line at 20% ("healthy margin" threshold)
- Color zones: green above 20%, red below 0%

### Granularity by period
| Period   | Grouping  |
|----------|-----------|
| 30 days  | by day    |
| 90 days  | by week   |
| 365 days | by week   |
| Total    | by month  |

### Backend method: `getProductTimeSeries()`
```php
private function getProductTimeSeries(Produto $produto, string $period): array
```
- Queries `ItemPedido` joined with `Pedido` (same status filter as existing metrics)
- Groups by day/week/month depending on `$period`
- Returns `[['date' => '2026-03-01', 'receita' => 1234.56, 'unidades' => 5, 'margem' => 32.4], ...]`
- If `$period === 'total'`, groups by `DATE_TRUNC('month', pedidos.created_at)`
- Uses same revenue calculation logic (net factor) as `getProductAnalyticsData()`

---

## Section 3 — Posição Comercial + Dados de Catálogo

**Keeps** the 2-column XL layout. Visual improvements:

**Left — Posição Comercial:**
- Metrics displayed as a more readable mini-table
- Health statement becomes a **status banner**: colored soft background + icon + text
  - Healthy (≥20%): green-tinted background
  - Low (<20%): yellow-tinted background
  - Negative (<0%): red-tinted background

**Right — Dados de Catálogo:**
- Price, cost, unit profit, margin keep existing color logic
- Margin gets a **mini progress bar** (0–100% visual scale, capped at 100%)
- Stock and status badges unchanged

---

## Files to Modify

| File | Change |
|------|--------|
| `resources/views/admin/analytics-produto.blade.php` | Full layout redesign + ApexCharts CDN + chart JS |
| `app/Http/Controllers/AdminController.php` | Add `getProductTimeSeries()` private method; call it in `analyticsProductShow()` |

---

## Verification

1. `docker exec laravel-app php artisan view:clear && docker exec laravel-app php artisan cache:clear`
2. Visit `/admin/analytics/produtos/{id}` for each period (30/90/365/total)
3. Confirm KPI grid renders in 3×2 on desktop, collapses correctly on mobile
4. Confirm 3 charts load with correct data per period
5. Confirm granularity switches correctly (daily → weekly → monthly)
6. Confirm health banner color matches margin threshold
7. Confirm no regression in product edit modal (same page)
8. Run `docker exec laravel-app php artisan test` — no failures
