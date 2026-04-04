# Dashboard UX Improvements Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Transform `/admin/dashboard` into an operational hub where merchants can fix product data and advance order statuses without navigating away, while adding performance analytics and collapsible sections.

**Architecture:** Single-page dashboard rewrite with two new JSON-only AJAX endpoints, a shared modal partial, expanded controller queries, and all interactivity in inline vanilla JS. No new views or routes for navigation.

**Tech Stack:** Laravel 12, PHP 8.3, Blade, Tailwind CSS 4 (Vite), vanilla JS, PostgreSQL. Commands run inside `laravel-app` Docker container.

---

## File Map

| File | Action | Purpose |
|---|---|---|
| `resources/views/admin/includes/modal-produto.blade.php` | **Create** | Extracted full product modal HTML (shared partial) |
| `resources/views/admin/produtos.blade.php` | **Modify** line 281–617 | Replace modal HTML with `@include` |
| `resources/views/includes/header-admin.blade.php` | **Modify** lines 19–26 | Add 2 new `window.routes` entries |
| `routes/web.php` | **Modify** lines 78, 83 | Add 2 new POST routes |
| `app/Http/Controllers/AdminController.php` | **Modify** lines 22–105 + append before line 1095 | Expand `dashboard()`, add `quickEditProduto()`, `quickStatusPedido()` |
| `resources/views/admin/dashboard.blade.php` | **Rewrite** | Full new dashboard with all sections and JS |

---

## Task 1: Extract product modal to shared partial

**Files:**
- Create: `resources/views/admin/includes/modal-produto.blade.php`
- Modify: `resources/views/admin/produtos.blade.php` lines 281–617

- [ ] **Step 1: Read the modal HTML**

```bash
sed -n '281,617p' /var/www/html/resources/views/admin/produtos.blade.php
```

This prints the full `<!-- Modal de Produto -->` block (lines 281–617).

- [ ] **Step 2: Create the partial file**

Copy the output of step 1 verbatim into a new file:

```bash
sed -n '281,617p' /var/www/html/resources/views/admin/produtos.blade.php \
  > /var/www/html/resources/views/admin/includes/modal-produto.blade.php
```

- [ ] **Step 3: Replace the inline HTML in produtos.blade.php with @include**

In `resources/views/admin/produtos.blade.php`, replace lines 281–617 with a single line:

Old (line 281 to 617):
```
<!-- Modal de Produto -->
<div id="modalProduto" class="fixed inset-0 ...">
    ... (337 lines of modal HTML)
</div>
```

New (single line at position 281):
```blade
@include('admin.includes.modal-produto')
```

Using sed:
```bash
# Remove lines 281-617, insert @include at line 281
head -n 280 /var/www/html/resources/views/admin/produtos.blade.php > /tmp/produtos_tmp.blade.php
echo "@include('admin.includes.modal-produto')" >> /tmp/produtos_tmp.blade.php
tail -n +618 /var/www/html/resources/views/admin/produtos.blade.php >> /tmp/produtos_tmp.blade.php
cp /tmp/produtos_tmp.blade.php /var/www/html/resources/views/admin/produtos.blade.php
```

- [ ] **Step 4: Clear view cache and verify /admin/produtos still works**

```bash
docker exec laravel-app php artisan view:clear
```

Visit `/admin/produtos` in the browser. Verify:
- Page loads without errors
- Clicking "Adicionar Produto" or editing a product opens the modal as before
- Saving a product still works

- [ ] **Step 5: Commit**

```bash
cd /var/www/html
git add resources/views/admin/includes/modal-produto.blade.php resources/views/admin/produtos.blade.php
git commit -m "refactor: extract product modal to shared partial"
```

---

## Task 2: Add new routes and window.routes entries

**Files:**
- Modify: `routes/web.php` — insert after line 78 and after line 83
- Modify: `resources/views/includes/header-admin.blade.php` — lines 19–26

- [ ] **Step 1: Add 2 new routes to routes/web.php**

In `routes/web.php`, after line 78 (`Route::post('/produtos/{id}/excluir'...`), add:

```php
    Route::post('/produtos/{id}/quick-edit', [AdminController::class, 'quickEditProduto'])->name('produtos.quick-edit');
```

And after line 83 (`Route::post('/pedidos/{id}/status'...`), add:

```php
    Route::post('/pedidos/{id}/quick-status', [AdminController::class, 'quickStatusPedido'])->name('pedidos.quick-status');
```

- [ ] **Step 2: Add 2 new window.routes entries in header-admin.blade.php**

In `resources/views/includes/header-admin.blade.php`, find the `window.routes` object (lines 19–26). Add 2 new keys:

```blade
        window.routes = {
            adminProdutosCriar: '{{ route("admin.produtos.criar") }}',
            adminProdutosEditar: '{{ route("admin.produtos.editar", ":id") }}',
            adminProdutosQuickEdit: '{{ route("admin.produtos.quick-edit", ":id") }}',
            adminCategoriasCriar: '{{ route("admin.categorias.criar") }}',
            adminCategoriasEditar: '{{ route("admin.categorias.editar", ":id") }}',
            adminPedidosStatus: '{{ route("admin.pedidos.status", ":id") }}',
            adminPedidosDetalhes: '{{ route("admin.pedidos.detalhes", ":id") }}',
            adminPedidosQuickStatus: '{{ route("admin.pedidos.quick-status", ":id") }}',
        };
```

- [ ] **Step 3: Verify routes are registered**

```bash
docker exec laravel-app php artisan route:list --name=admin.produtos.quick-edit
docker exec laravel-app php artisan route:list --name=admin.pedidos.quick-status
```

Expected output: two rows showing `POST` method for the respective URLs.

- [ ] **Step 4: Commit**

```bash
git add routes/web.php resources/views/includes/header-admin.blade.php
git commit -m "feat: add quick-edit and quick-status routes"
```

---

## Task 3: Add quickEditProduto and quickStatusPedido to AdminController

**Files:**
- Modify: `app/Http/Controllers/AdminController.php` — insert before the closing `}` of the class (line 1095)

- [ ] **Step 1: Add both methods to AdminController**

In `app/Http/Controllers/AdminController.php`, insert the following two methods before line 1095 (after `resolveItemCost`, before the class closing brace `}`):

```php
    public function quickEditProduto(Request $request, $id)
    {
        if (!Auth::check()) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $produto = Produto::findOrFail($id);
        $data = [];

        if ($request->filled('custo_compra')) {
            $data['custo_compra'] = $this->parseMoneyInput($request->custo_compra);
        } elseif ($request->has('custo_compra') && $request->custo_compra === '') {
            $data['custo_compra'] = null;
        }

        if ($request->filled('estoque')) {
            $data['estoque'] = max(0, (int) $request->estoque);
        }

        if (!empty($data)) {
            $produto->update($data);
            $produto->refresh();
        }

        return response()->json([
            'success'      => true,
            'custo_compra' => $produto->custo_compra,
            'estoque'      => $produto->estoque,
            'margem'       => $produto->margem_bruta_percentual,
            'lucro'        => $produto->lucro_bruto_unitario,
        ]);
    }

    public function quickStatusPedido(Request $request, $id)
    {
        if (!Auth::check()) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $pedido = Pedido::findOrFail($id);

        $request->validate([
            'status' => 'required|in:pendente,processando,enviado,entregue,cancelado',
        ]);

        $pedido->update(['status' => $request->status]);

        return response()->json([
            'success' => true,
            'status'  => $pedido->status,
        ]);
    }
```

- [ ] **Step 2: Smoke-test both endpoints with tinker**

```bash
docker exec -e HOME=/tmp laravel-app php artisan tinker --execute="
// Find any produto and pedido to test
\$p = App\Models\Produto::first();
\$pedido = App\Models\Pedido::first();
echo 'Produto id: ' . \$p->id . PHP_EOL;
echo 'Pedido id: ' . (\$pedido ? \$pedido->id : 'none') . PHP_EOL;
"
```

Then test quick-edit with curl (replace 120 with an actual produto id):

```bash
curl -s -X POST http://localhost/admin/produtos/120/quick-edit \
  -H "Content-Type: application/json" \
  -H "X-CSRF-TOKEN: $(docker exec laravel-app php artisan tinker --execute='echo csrf_token();' 2>/dev/null | tail -1)" \
  -d '{"estoque": 5}' | docker exec -i laravel-app php -r "echo json_encode(json_decode(file_get_contents('php://stdin')), JSON_PRETTY_PRINT);"
```

Expected: `{"success": true, "custo_compra": ..., "estoque": 5, "margem": ..., "lucro": ...}`

Note: CSRF will block direct curl in production. Verification in browser is sufficient — confirm no 500 errors in `docker exec laravel-app tail -50 storage/logs/laravel.log`.

- [ ] **Step 3: Commit**

```bash
git add app/Http/Controllers/AdminController.php
git commit -m "feat: add quickEditProduto and quickStatusPedido JSON endpoints"
```

---

## Task 4: Expand dashboard() with new queries and product collections in alertas

**Files:**
- Modify: `app/Http/Controllers/AdminController.php` — replace `dashboard()` method (lines 22–105)

- [ ] **Step 1: Replace the dashboard() method**

Replace the entire `dashboard()` method (from `// Dashboard administrativo` to the closing `}`) with:

```php
    // Dashboard administrativo
    public function dashboard()
    {
        if (!Auth::check()) {
            return redirect()->route('site.login');
        }

        // Métricas de pedidos
        $total_produtos      = Produto::count();
        $total_pedidos       = Pedido::count();
        $pedidos_pendentes   = Pedido::where('status', 'pendente')->count();
        $pedidos_processando = Pedido::where('status', 'processando')->count();
        $pedidos_enviados    = Pedido::where('status', 'enviado')->count();
        $pedidos_entregues   = Pedido::where('status', 'entregue')->count();
        $pedidos_cancelados  = Pedido::where('status', 'cancelado')->count();
        $receita_total       = Pedido::where('status', 'entregue')->sum('valor_total');

        // Custo e lucro (apenas pedidos entregues)
        $itensEntregues = ItemPedido::with([
            'produto:id,custo_compra',
            'produtoVariante:id,produto_id,custo_compra',
        ])->whereHas('pedido', function ($query) {
            $query->where('status', 'entregue');
        })->get();

        $custo_total     = 0;
        $itens_sem_custo = 0;
        foreach ($itensEntregues as $item) {
            $custoUnitario = $this->resolveItemCost($item);
            if ($custoUnitario === null) {
                $itens_sem_custo += $item->quantidade;
                $custoUnitario = 0;
            }
            $custo_total += $item->quantidade * $custoUnitario;
        }

        $lucro_bruto_total       = $receita_total - $custo_total;
        $margem_bruta_percentual = $receita_total > 0
            ? round(($lucro_bruto_total / $receita_total) * 100, 2) : 0;

        // Produtos para analytics e alertas
        $produtos_analytics = Produto::select(
            'id', 'nome', 'marca', 'preco', 'preco_original', 'custo_compra',
            'desconto_percentual', 'em_promocao', 'estoque', 'ativo'
        )->with('imagemCapa:id,produto_id')->orderBy('nome')->get();

        // Colections para alertas expansíveis
        $margemMinima          = 20;
        $produtosSemCusto      = $produtos_analytics->whereNull('custo_compra');
        $produtosEstoqueZerado = $produtos_analytics->where('ativo', true)->where('estoque', 0);
        $produtosMargemNeg     = $produtos_analytics->filter(
            fn($p) => $p->custo_compra !== null && $p->margem_bruta_percentual !== null && $p->margem_bruta_percentual < 0
        );
        $produtosMargemZero    = $produtos_analytics->filter(
            fn($p) => $p->custo_compra !== null && $p->margem_bruta_percentual !== null && $p->margem_bruta_percentual == 0
        );
        $produtosMargemBaixa   = $produtos_analytics->filter(
            fn($p) => $p->custo_compra !== null && $p->margem_bruta_percentual !== null
                && $p->margem_bruta_percentual > 0 && $p->margem_bruta_percentual < $margemMinima
        );

        $alertas = [
            'margem_negativa'         => $produtosMargemNeg->count(),
            'margem_zero'             => $produtosMargemZero->count(),
            'margem_baixa'            => $produtosMargemBaixa->count(),
            'estoque_zerado'          => $produtosEstoqueZerado->count(),
            'sem_custo'               => $produtosSemCusto->count(),
            'sem_imagem'              => $produtos_analytics->where('ativo', true)
                                            ->filter(fn($p) => $p->imagemCapa === null)->count(),
            'inativos'                => $produtos_analytics->where('ativo', false)->count(),
            // Product collections for expandable alert rows
            'produtos_sem_custo'      => $produtosSemCusto->values(),
            'produtos_estoque_zerado' => $produtosEstoqueZerado->values(),
            'produtos_margem_negativa'=> $produtosMargemNeg->values(),
            'produtos_margem_zero'    => $produtosMargemZero->values(),
            'produtos_margem_baixa'   => $produtosMargemBaixa->values(),
        ];

        // Performance
        $top_produtos = ItemPedido::select(
            'produto_id',
            \DB::raw('SUM(quantidade) as total_vendido'),
            \DB::raw('SUM(preco * quantidade) as receita_gerada')
        )->whereHas('pedido', fn($q) => $q->where('status', 'entregue'))
            ->groupBy('produto_id')
            ->orderByDesc('total_vendido')
            ->limit(5)
            ->with('produto:id,nome,marca')
            ->get();

        $receita_categoria = \DB::table('itens_pedido')
            ->join('pedidos', 'pedidos.id', '=', 'itens_pedido.pedido_id')
            ->join('produtos', 'produtos.id', '=', 'itens_pedido.produto_id')
            ->join('categorias', 'categorias.id', '=', 'produtos.categoria_id')
            ->where('pedidos.status', 'entregue')
            ->select('categorias.nome', \DB::raw('SUM(itens_pedido.preco * itens_pedido.quantidade) as receita'))
            ->groupBy('categorias.id', 'categorias.nome')
            ->orderByDesc('receita')
            ->get();

        $ticket_medio        = $pedidos_entregues > 0 ? round($receita_total / $pedidos_entregues, 2) : 0;
        $total_unidades      = ItemPedido::whereHas('pedido', fn($q) => $q->where('status', 'entregue'))->sum('quantidade');
        $total_ativos        = Produto::where('ativo', true)->count();

        // Pedidos que precisam de ação
        $pedidos_acao = Pedido::whereIn('status', ['pendente', 'processando'])
            ->with('user:id,name')
            ->orderBy('created_at')
            ->get();

        $pedidos_recentes = Pedido::with('user')->orderBy('created_at', 'desc')->limit(5)->get();

        return view('admin.dashboard', compact(
            'total_produtos', 'total_pedidos', 'pedidos_pendentes',
            'pedidos_processando', 'pedidos_enviados', 'pedidos_entregues', 'pedidos_cancelados',
            'receita_total', 'custo_total', 'lucro_bruto_total', 'margem_bruta_percentual',
            'itens_sem_custo', 'pedidos_recentes',
            'produtos_analytics', 'alertas',
            'top_produtos', 'receita_categoria',
            'ticket_medio', 'total_unidades', 'total_ativos',
            'pedidos_acao'
        ));
    }
```

- [ ] **Step 2: Verify dashboard() loads without errors**

```bash
docker exec -e HOME=/tmp laravel-app php artisan tinker --execute="
\$ctrl = app(App\Http\Controllers\AdminController::class);
echo 'Controller instantiated OK' . PHP_EOL;
// Verify new queries work
\$top = App\Models\ItemPedido::select('produto_id', \DB::raw('SUM(quantidade) as total_vendido'))
    ->whereHas('pedido', fn(\$q) => \$q->where('status', 'entregue'))
    ->groupBy('produto_id')->orderByDesc('total_vendido')->limit(5)->get();
echo 'Top produtos query OK: ' . count(\$top) . ' rows' . PHP_EOL;
\$cats = \DB::table('itens_pedido')
    ->join('pedidos','pedidos.id','=','itens_pedido.pedido_id')
    ->join('produtos','produtos.id','=','itens_pedido.produto_id')
    ->join('categorias','categorias.id','=','produtos.categoria_id')
    ->where('pedidos.status','entregue')
    ->select('categorias.nome', \DB::raw('SUM(itens_pedido.preco * itens_pedido.quantidade) as receita'))
    ->groupBy('categorias.id','categorias.nome')->orderByDesc('receita')->get();
echo 'Receita categoria query OK: ' . count(\$cats) . ' rows' . PHP_EOL;
"
```

Expected: both queries return without exceptions.

- [ ] **Step 3: Commit**

```bash
git add app/Http/Controllers/AdminController.php
git commit -m "feat: expand dashboard() with performance queries and alert product collections"
```

---

## Task 5: Rewrite dashboard.blade.php

**Files:**
- Rewrite: `resources/views/admin/dashboard.blade.php`

- [ ] **Step 1: Write the new dashboard view**

Replace the entire contents of `resources/views/admin/dashboard.blade.php` with:

```blade
@extends('includes.header-admin')

@section('title', 'Dashboard')

@section('content')
<div class="space-y-4 lg:space-y-6">

    {{-- Header --}}
    <div class="flex flex-col sm:flex-row sm:justify-between sm:items-end gap-4">
        <div>
            <p class="font-mono text-[10px] uppercase tracking-widest text-[var(--color-lab-muted)] mb-1">Overview</p>
            <h1 class="text-2xl sm:text-3xl font-bold text-black tracking-tight">Dashboard</h1>
        </div>
        <div class="font-mono text-xs text-[var(--color-lab-muted)] uppercase tracking-widest">
            {{ now()->format('d/m/Y H:i') }}
        </div>
    </div>

    {{-- ── ACTION BAR ─────────────────────────────────────────────────── --}}
    <div class="flex flex-wrap gap-2">
        <button onclick="abrirModalProduto()"
                class="inline-flex items-center gap-2 px-4 py-2 bg-black text-white font-mono text-[10px] uppercase tracking-widest hover:bg-gray-800 transition-colors">
            <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14"/><path d="M12 5v14"/></svg>
            Novo Produto
        </button>
        <a href="{{ route('admin.pedidos') }}"
           class="inline-flex items-center gap-2 px-4 py-2 border border-[var(--color-lab-border)] font-mono text-[10px] uppercase tracking-widest hover:border-black transition-colors {{ $pedidos_pendentes > 0 ? 'border-yellow-400 text-yellow-700' : 'text-black' }}">
            Pendentes: {{ $pedidos_pendentes }}
        </a>
        <a href="{{ route('admin.pedidos') }}"
           class="inline-flex items-center gap-2 px-4 py-2 border border-[var(--color-lab-border)] font-mono text-[10px] uppercase tracking-widest text-black hover:border-black transition-colors">
            Processando: {{ $pedidos_processando }}
        </a>
        <a href="{{ route('admin.pedidos') }}"
           class="inline-flex items-center gap-2 px-4 py-2 border border-[var(--color-lab-border)] font-mono text-[10px] uppercase tracking-widest text-black hover:border-black transition-colors">
            Ver Pedidos
        </a>
        <form action="{{ route('admin.produtos.exportar') }}" method="POST" class="inline">
            @csrf
            <button type="submit"
                    class="inline-flex items-center gap-2 px-4 py-2 border border-[var(--color-lab-border)] font-mono text-[10px] uppercase tracking-widest text-black hover:border-black transition-colors">
                <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" x2="12" y1="15" y2="3"/></svg>
                Exportar CSV
            </button>
        </form>
    </div>

    {{-- ── KPI CARDS ───────────────────────────────────────────────────── --}}
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="border border-[var(--color-lab-border)] bg-white p-6">
            <p class="font-mono text-[10px] uppercase tracking-widest text-[var(--color-lab-muted)] mb-2">Receita Total</p>
            <p class="text-lg sm:text-2xl font-bold text-black font-mono">R$&nbsp;{{ number_format($receita_total, 2, ',', '.') }}</p>
            <p class="font-mono text-[10px] text-[var(--color-lab-muted)] mt-1">pedidos entregues</p>
        </div>
        <div class="border border-[var(--color-lab-border)] bg-white p-6">
            <p class="font-mono text-[10px] uppercase tracking-widest text-[var(--color-lab-muted)] mb-2">Lucro Bruto</p>
            <p class="text-lg sm:text-2xl font-bold font-mono {{ $lucro_bruto_total >= 0 ? 'text-black' : 'text-red-600' }}">R$&nbsp;{{ number_format($lucro_bruto_total, 2, ',', '.') }}</p>
            <p class="font-mono text-[10px] text-[var(--color-lab-muted)] mt-1">receita &minus; custo</p>
        </div>
        <div class="border border-[var(--color-lab-border)] bg-white p-6">
            <p class="font-mono text-[10px] uppercase tracking-widest text-[var(--color-lab-muted)] mb-2">Margem Bruta</p>
            <p class="text-2xl sm:text-3xl font-bold font-mono
                @if($margem_bruta_percentual >= 20) text-black
                @elseif($margem_bruta_percentual >= 0) text-yellow-600
                @else text-red-600 @endif">{{ number_format($margem_bruta_percentual, 1, ',', '.') }}%</p>
            <p class="font-mono text-[10px] text-[var(--color-lab-muted)] mt-1">sobre receita</p>
        </div>
        <div class="border border-[var(--color-lab-border)] bg-white p-6">
            <p class="font-mono text-[10px] uppercase tracking-widest text-[var(--color-lab-muted)] mb-2">Pendentes</p>
            <p class="text-2xl sm:text-3xl font-bold text-black font-mono">{{ $pedidos_pendentes }}</p>
            <p class="font-mono text-[10px] text-[var(--color-lab-muted)] mt-1">aguardando</p>
        </div>
    </div>

    {{-- ── SECTION: ALERTAS + STATUS ───────────────────────────────────── --}}
    <div data-section="alertas" data-default-open="true" class="border border-[var(--color-lab-border)] bg-white">
        <div data-section-toggle class="flex items-center justify-between px-6 py-4 cursor-pointer hover:bg-gray-50 border-b border-[var(--color-lab-border)] select-none">
            <p class="font-mono text-[10px] uppercase tracking-widest text-[var(--color-lab-muted)]">
                <span data-section-arrow>▼</span>&nbsp; Alertas &amp; Status
            </p>
        </div>
        <div data-section-content class="p-6">
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

                {{-- Alertas card --}}
                <div>
                    <p class="font-mono text-[10px] uppercase tracking-widest text-[var(--color-lab-muted)] mb-4">Alertas</p>
                    @php
                        $temCritico  = $alertas['margem_negativa'] > 0 || $alertas['margem_zero'] > 0;
                        $temAtencao  = $alertas['margem_baixa'] > 0 || $alertas['estoque_zerado'] > 0;
                        $temSemCusto = $alertas['sem_custo'] > 0;
                        $temInfo     = $alertas['sem_imagem'] > 0 || $alertas['inativos'] > 0;
                    @endphp

                    @if(!$temCritico && !$temAtencao && !$temSemCusto)
                    <div class="flex items-center gap-2 px-3 py-2 bg-green-50 border border-green-200 mb-3">
                        <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-green-600 shrink-0"><path d="M20 6 9 17l-5-5"/></svg>
                        <span class="font-mono text-[10px] uppercase tracking-widest text-green-700">Tudo OK</span>
                    </div>
                    @endif

                    <div class="space-y-1">
                        @if($temCritico)
                        <p class="font-mono text-[10px] uppercase tracking-widest text-red-500 mt-3 mb-1">Crítico</p>
                        @endif

                        @if($alertas['margem_negativa'] > 0)
                        <div>
                            <div data-expandable="alert-margem-neg" class="flex items-center justify-between py-2 border-l-2 border-red-500 pl-3 cursor-pointer hover:bg-red-50 select-none">
                                <div class="flex items-center gap-2">
                                    <span data-expand-arrow class="font-mono text-[10px] text-red-400">▶</span>
                                    <span class="font-mono text-xs text-black">Margem negativa</span>
                                </div>
                                <span class="font-mono text-xs font-bold text-red-600">{{ $alertas['margem_negativa'] }}</span>
                            </div>
                            <div id="alert-margem-neg" class="hidden ml-3 mt-1 space-y-1">
                                @foreach($alertas['produtos_margem_negativa'] as $ap)
                                <div class="flex items-center justify-between py-1.5 px-3 bg-red-50" data-alert-produto="{{ $ap->id }}">
                                    <span class="font-mono text-xs text-black truncate max-w-[140px]">{{ $ap->nome }}</span>
                                    <button onclick="abrirQuickFix({{ $ap->id }}, '{{ addslashes($ap->nome) }}', '{{ $ap->custo_compra }}', {{ $ap->estoque }})"
                                            class="font-mono text-[10px] uppercase tracking-widest px-2 py-1 border border-red-300 text-red-600 hover:bg-red-100 shrink-0">
                                        ✏ Editar
                                    </button>
                                </div>
                                @endforeach
                            </div>
                        </div>
                        @endif

                        @if($alertas['margem_zero'] > 0)
                        <div>
                            <div data-expandable="alert-margem-zero" class="flex items-center justify-between py-2 border-l-2 border-red-500 pl-3 cursor-pointer hover:bg-red-50 select-none">
                                <div class="flex items-center gap-2">
                                    <span data-expand-arrow class="font-mono text-[10px] text-red-400">▶</span>
                                    <span class="font-mono text-xs text-black">Margem zero</span>
                                </div>
                                <span class="font-mono text-xs font-bold text-red-600">{{ $alertas['margem_zero'] }}</span>
                            </div>
                            <div id="alert-margem-zero" class="hidden ml-3 mt-1 space-y-1">
                                @foreach($alertas['produtos_margem_zero'] as $ap)
                                <div class="flex items-center justify-between py-1.5 px-3 bg-red-50" data-alert-produto="{{ $ap->id }}">
                                    <span class="font-mono text-xs text-black truncate max-w-[140px]">{{ $ap->nome }}</span>
                                    <button onclick="abrirQuickFix({{ $ap->id }}, '{{ addslashes($ap->nome) }}', '{{ $ap->custo_compra }}', {{ $ap->estoque }})"
                                            class="font-mono text-[10px] uppercase tracking-widest px-2 py-1 border border-red-300 text-red-600 hover:bg-red-100 shrink-0">
                                        ✏ Editar
                                    </button>
                                </div>
                                @endforeach
                            </div>
                        </div>
                        @endif

                        @if($temAtencao)
                        <p class="font-mono text-[10px] uppercase tracking-widest text-yellow-600 mt-3 mb-1">Atenção</p>
                        @endif

                        @if($alertas['margem_baixa'] > 0)
                        <div>
                            <div data-expandable="alert-margem-baixa" class="flex items-center justify-between py-2 border-l-2 border-yellow-400 pl-3 cursor-pointer hover:bg-yellow-50 select-none">
                                <div class="flex items-center gap-2">
                                    <span data-expand-arrow class="font-mono text-[10px] text-yellow-400">▶</span>
                                    <span class="font-mono text-xs text-black">Margem abaixo de 20%</span>
                                </div>
                                <span class="font-mono text-xs font-bold text-yellow-600">{{ $alertas['margem_baixa'] }}</span>
                            </div>
                            <div id="alert-margem-baixa" class="hidden ml-3 mt-1 space-y-1">
                                @foreach($alertas['produtos_margem_baixa'] as $ap)
                                <div class="flex items-center justify-between py-1.5 px-3 bg-yellow-50" data-alert-produto="{{ $ap->id }}">
                                    <div>
                                        <span class="font-mono text-xs text-black truncate max-w-[120px] block">{{ $ap->nome }}</span>
                                        <span class="font-mono text-[10px] text-yellow-600">{{ number_format($ap->margem_bruta_percentual, 1, ',', '.') }}%</span>
                                    </div>
                                    <button onclick="abrirQuickFix({{ $ap->id }}, '{{ addslashes($ap->nome) }}', '{{ $ap->custo_compra }}', {{ $ap->estoque }})"
                                            class="font-mono text-[10px] uppercase tracking-widest px-2 py-1 border border-yellow-300 text-yellow-700 hover:bg-yellow-100 shrink-0">
                                        ✏ Editar
                                    </button>
                                </div>
                                @endforeach
                            </div>
                        </div>
                        @endif

                        @if($alertas['estoque_zerado'] > 0)
                        <div>
                            <div data-expandable="alert-estoque" class="flex items-center justify-between py-2 border-l-2 border-yellow-400 pl-3 cursor-pointer hover:bg-yellow-50 select-none">
                                <div class="flex items-center gap-2">
                                    <span data-expand-arrow class="font-mono text-[10px] text-yellow-400">▶</span>
                                    <span class="font-mono text-xs text-black">Estoque zerado (ativo)</span>
                                </div>
                                <span class="font-mono text-xs font-bold text-yellow-600">{{ $alertas['estoque_zerado'] }}</span>
                            </div>
                            <div id="alert-estoque" class="hidden ml-3 mt-1 space-y-1">
                                @foreach($alertas['produtos_estoque_zerado'] as $ap)
                                <div class="flex items-center justify-between py-1.5 px-3 bg-yellow-50" data-alert-produto="{{ $ap->id }}">
                                    <span class="font-mono text-xs text-black truncate max-w-[140px]">{{ $ap->nome }}</span>
                                    <button onclick="abrirQuickFix({{ $ap->id }}, '{{ addslashes($ap->nome) }}', '{{ $ap->custo_compra }}', {{ $ap->estoque }})"
                                            class="font-mono text-[10px] uppercase tracking-widest px-2 py-1 border border-yellow-300 text-yellow-700 hover:bg-yellow-100 shrink-0">
                                        ✏ Estoque
                                    </button>
                                </div>
                                @endforeach
                            </div>
                        </div>
                        @endif

                        @if($temSemCusto)
                        <p class="font-mono text-[10px] uppercase tracking-widest text-blue-600 mt-3 mb-1">Dados Incompletos</p>
                        <div>
                            <div data-expandable="alert-sem-custo" class="flex items-center justify-between py-2 border-l-2 border-blue-400 pl-3 cursor-pointer hover:bg-blue-50 select-none">
                                <div class="flex items-center gap-2">
                                    <span data-expand-arrow class="font-mono text-[10px] text-blue-400">▶</span>
                                    <div>
                                        <span class="font-mono text-xs font-bold text-black">Preço de compra não cadastrado</span>
                                        <p class="font-mono text-[10px] text-blue-500">Margem indisponível — cadastre o custo de aquisição</p>
                                    </div>
                                </div>
                                <span class="font-mono text-xs font-bold text-blue-600 shrink-0">{{ $alertas['sem_custo'] }}</span>
                            </div>
                            <div id="alert-sem-custo" class="hidden ml-3 mt-1 space-y-1">
                                @foreach($alertas['produtos_sem_custo'] as $ap)
                                <div class="flex items-center justify-between py-1.5 px-3 bg-blue-50" data-alert-produto="{{ $ap->id }}">
                                    <div>
                                        <span class="font-mono text-xs text-black truncate max-w-[120px] block">{{ $ap->nome }}</span>
                                        <span class="font-mono text-[10px] text-gray-400">R$ {{ number_format($ap->preco_com_desconto, 2, ',', '.') }}</span>
                                    </div>
                                    <button onclick="abrirQuickFix({{ $ap->id }}, '{{ addslashes($ap->nome) }}', '', {{ $ap->estoque }})"
                                            class="font-mono text-[10px] uppercase tracking-widest px-2 py-1 border border-blue-300 text-blue-600 hover:bg-blue-100 shrink-0">
                                        ✏ Editar custo
                                    </button>
                                </div>
                                @endforeach
                            </div>
                        </div>
                        @endif

                        @if($temInfo)
                        <p class="font-mono text-[10px] uppercase tracking-widest text-[var(--color-lab-muted)] mt-3 mb-1">Informação</p>
                        @if($alertas['sem_imagem'] > 0)
                        <div class="flex items-center justify-between py-1.5 border-l-2 border-gray-300 pl-3">
                            <span class="font-mono text-xs text-black">Sem imagem (ativo)</span>
                            <span class="font-mono text-xs font-bold text-gray-500">{{ $alertas['sem_imagem'] }}</span>
                        </div>
                        @endif
                        @if($alertas['inativos'] > 0)
                        <div class="flex items-center justify-between py-1.5 border-l-2 border-gray-300 pl-3">
                            <span class="font-mono text-xs text-black">Produtos inativos</span>
                            <span class="font-mono text-xs font-bold text-gray-500">{{ $alertas['inativos'] }}</span>
                        </div>
                        @endif
                        @endif
                    </div>
                </div>

                {{-- Status dos pedidos --}}
                <div>
                    <p class="font-mono text-[10px] uppercase tracking-widest text-[var(--color-lab-muted)] mb-4">Status dos Pedidos</p>
                    <div class="space-y-3">
                        @foreach([
                            ['label'=>'Pendentes',   'val'=>$pedidos_pendentes,   'color'=>'bg-gray-400'],
                            ['label'=>'Processando', 'val'=>$pedidos_processando, 'color'=>'bg-gray-600'],
                            ['label'=>'Enviados',    'val'=>$pedidos_enviados,    'color'=>'bg-gray-800'],
                            ['label'=>'Entregues',   'val'=>$pedidos_entregues,   'color'=>'bg-black'],
                            ['label'=>'Cancelados',  'val'=>$pedidos_cancelados,  'color'=>'bg-gray-300'],
                        ] as $s)
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-2">
                                <div class="w-2 h-2 {{ $s['color'] }} shrink-0"></div>
                                <span class="font-mono text-sm text-black">{{ $s['label'] }}</span>
                            </div>
                            <span class="font-mono text-sm font-bold text-black">{{ $s['val'] }}</span>
                        </div>
                        @endforeach
                        <div class="pt-3 border-t border-[var(--color-lab-border)]">
                            <div class="flex items-center justify-between">
                                <span class="font-mono text-xs text-[var(--color-lab-muted)]">Total pedidos</span>
                                <span class="font-mono text-xs font-bold text-black">{{ $total_pedidos }}</span>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Ações rápidas + financeiro --}}
                <div>
                    <p class="font-mono text-[10px] uppercase tracking-widest text-[var(--color-lab-muted)] mb-4">Ações Rápidas</p>
                    <div class="space-y-2 mb-4">
                        <a href="{{ route('admin.produtos') }}" class="flex items-center gap-3 px-4 py-3 border border-[var(--color-lab-border)] hover:border-black hover:bg-gray-50 transition-colors">
                            <span class="font-mono text-xs uppercase tracking-widest text-black">Gerenciar Produtos</span>
                        </a>
                        <a href="{{ route('admin.categorias') }}" class="flex items-center gap-3 px-4 py-3 border border-[var(--color-lab-border)] hover:border-black hover:bg-gray-50 transition-colors">
                            <span class="font-mono text-xs uppercase tracking-widest text-black">Gerenciar Categorias</span>
                        </a>
                        <a href="{{ route('admin.pedidos') }}" class="flex items-center gap-3 px-4 py-3 border border-[var(--color-lab-border)] hover:border-black hover:bg-gray-50 transition-colors">
                            <span class="font-mono text-xs uppercase tracking-widest text-black">Ver Todos os Pedidos</span>
                        </a>
                    </div>
                    <div class="border-t border-[var(--color-lab-border)] pt-4 space-y-2">
                        <div class="flex justify-between">
                            <span class="font-mono text-xs text-[var(--color-lab-muted)]">Custo total</span>
                            <span class="font-mono text-xs font-bold text-black">R$&nbsp;{{ number_format($custo_total, 2, ',', '.') }}</span>
                        </div>
                        @if($itens_sem_custo > 0)
                        <div class="flex justify-between">
                            <span class="font-mono text-xs text-yellow-600">Itens s/ custo (entregues)</span>
                            <span class="font-mono text-xs font-bold text-yellow-600">{{ $itens_sem_custo }}</span>
                        </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- ── SECTION: PEDIDOS QUE PRECISAM DE AÇÃO ───────────────────────── --}}
    @if($pedidos_acao->isNotEmpty())
    <div data-section="pedidos-acao" data-default-open="true" class="border border-[var(--color-lab-border)] bg-white">
        <div data-section-toggle class="flex items-center justify-between px-6 py-4 cursor-pointer hover:bg-gray-50 border-b border-[var(--color-lab-border)] select-none">
            <p class="font-mono text-[10px] uppercase tracking-widest text-[var(--color-lab-muted)]">
                <span data-section-arrow>▼</span>&nbsp; Pedidos que Precisam de Ação
                <span class="ml-2 px-2 py-0.5 bg-yellow-100 text-yellow-700 text-[10px] font-bold">{{ $pedidos_acao->count() }}</span>
            </p>
        </div>
        <div data-section-content id="lista-pedidos-acao">
            @foreach($pedidos_acao as $pedido)
            <div id="pedido-acao-{{ $pedido->id }}" class="flex flex-col sm:flex-row sm:items-center sm:justify-between px-6 py-4 border-b border-[var(--color-lab-border)] last:border-0">
                <div class="flex-1 min-w-0">
                    <div class="flex items-center gap-3 mb-0.5">
                        <span class="font-mono text-sm font-bold text-black">#{{ $pedido->id }}</span>
                        <span class="font-mono text-[10px] text-[var(--color-lab-muted)]">{{ $pedido->created_at->format('d/m H:i') }}</span>
                        <span data-status-badge class="inline-block px-2 py-0.5 font-mono text-[10px] border {{ $pedido->status === 'pendente' ? 'border-gray-400 text-gray-600' : 'border-gray-600 text-gray-700' }}">
                            {{ ucfirst($pedido->status) }}
                        </span>
                    </div>
                    <div class="font-mono text-sm text-black">{{ $pedido->user->name }}</div>
                    <div class="font-mono text-xs text-[var(--color-lab-muted)]">R$&nbsp;{{ number_format($pedido->valor_total, 2, ',', '.') }}</div>
                </div>
                <div class="mt-2 sm:mt-0">
                    @if($pedido->status === 'pendente')
                    <button onclick="avancarStatusPedido(this, {{ $pedido->id }}, 'processando')"
                            class="font-mono text-[10px] uppercase tracking-widest px-3 py-2 border border-black text-black hover:bg-black hover:text-white transition-colors">
                        → Processando
                    </button>
                    @elseif($pedido->status === 'processando')
                    <button onclick="avancarStatusPedido(this, {{ $pedido->id }}, 'enviado')"
                            class="font-mono text-[10px] uppercase tracking-widest px-3 py-2 border border-black text-black hover:bg-black hover:text-white transition-colors">
                        → Enviado
                    </button>
                    @endif
                </div>
            </div>
            @endforeach
        </div>
    </div>
    @endif

    {{-- ── SECTION: PERFORMANCE ─────────────────────────────────────────── --}}
    <div data-section="performance" data-default-open="true" class="border border-[var(--color-lab-border)] bg-white">
        <div data-section-toggle class="flex items-center justify-between px-6 py-4 cursor-pointer hover:bg-gray-50 border-b border-[var(--color-lab-border)] select-none">
            <p class="font-mono text-[10px] uppercase tracking-widest text-[var(--color-lab-muted)]">
                <span data-section-arrow>▼</span>&nbsp; Performance
            </p>
        </div>
        <div data-section-content class="p-6 grid grid-cols-1 lg:grid-cols-3 gap-6">

            {{-- Top 5 vendidos --}}
            <div>
                <p class="font-mono text-[10px] uppercase tracking-widest text-[var(--color-lab-muted)] mb-4">Top 5 Mais Vendidos</p>
                @forelse($top_produtos as $i => $item)
                <div class="flex items-start gap-3 mb-3">
                    <span class="font-mono text-xs text-[var(--color-lab-muted)] w-4 shrink-0">{{ $i + 1 }}</span>
                    <div class="flex-1 min-w-0">
                        <p class="font-mono text-xs text-black truncate">{{ $item->produto?->nome ?? '—' }}</p>
                        <p class="font-mono text-[10px] text-[var(--color-lab-muted)]">{{ $item->total_vendido }} un. &middot; R$&nbsp;{{ number_format($item->receita_gerada, 2, ',', '.') }}</p>
                    </div>
                </div>
                @empty
                <p class="font-mono text-xs text-[var(--color-lab-muted)]">Nenhuma venda entregue ainda.</p>
                @endforelse
            </div>

            {{-- Receita por categoria --}}
            <div>
                <p class="font-mono text-[10px] uppercase tracking-widest text-[var(--color-lab-muted)] mb-4">Receita por Categoria</p>
                @php $maxReceita = $receita_categoria->max('receita') ?: 1; @endphp
                @forelse($receita_categoria as $cat)
                <div class="mb-3">
                    <div class="flex justify-between mb-1">
                        <span class="font-mono text-xs text-black">{{ $cat->nome }}</span>
                        <span class="font-mono text-xs text-[var(--color-lab-muted)]">R$&nbsp;{{ number_format($cat->receita, 0, ',', '.') }}</span>
                    </div>
                    <div class="h-1 bg-gray-100">
                        <div class="h-1 bg-black" style="width: {{ round(($cat->receita / $maxReceita) * 100) }}%"></div>
                    </div>
                </div>
                @empty
                <p class="font-mono text-xs text-[var(--color-lab-muted)]">Nenhuma venda entregue ainda.</p>
                @endforelse
            </div>

            {{-- Métricas gerais --}}
            <div>
                <p class="font-mono text-[10px] uppercase tracking-widest text-[var(--color-lab-muted)] mb-4">Métricas Gerais</p>
                <div class="space-y-4">
                    <div>
                        <p class="font-mono text-[10px] text-[var(--color-lab-muted)] mb-1">Ticket Médio</p>
                        <p class="font-mono text-xl font-bold text-black">R$&nbsp;{{ number_format($ticket_medio, 2, ',', '.') }}</p>
                        <p class="font-mono text-[10px] text-[var(--color-lab-muted)]">por pedido entregue</p>
                    </div>
                    <div>
                        <p class="font-mono text-[10px] text-[var(--color-lab-muted)] mb-1">Unidades Vendidas</p>
                        <p class="font-mono text-xl font-bold text-black">{{ $total_unidades }}</p>
                        <p class="font-mono text-[10px] text-[var(--color-lab-muted)]">pedidos entregues</p>
                    </div>
                    <div>
                        <p class="font-mono text-[10px] text-[var(--color-lab-muted)] mb-1">Produtos Ativos</p>
                        <p class="font-mono text-xl font-bold text-black">{{ $total_ativos }}</p>
                        <p class="font-mono text-[10px] text-[var(--color-lab-muted)]">de {{ $total_produtos }} total</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- ── SECTION: ANALYTICS DE PRODUTOS ─────────────────────────────── --}}
    <div data-section="analytics" data-default-open="true" class="border border-[var(--color-lab-border)] bg-white">
        <div data-section-toggle class="flex items-center justify-between px-6 py-4 cursor-pointer hover:bg-gray-50 border-b border-[var(--color-lab-border)] select-none">
            <p class="font-mono text-[10px] uppercase tracking-widest text-[var(--color-lab-muted)]">
                <span data-section-arrow>▼</span>&nbsp; Analytics de Produtos
            </p>
            <span class="font-mono text-xs text-[var(--color-lab-muted)]">{{ $produtos_analytics->count() }} produtos &middot; clique nos cabeçalhos para ordenar</span>
        </div>
        <div data-section-content class="overflow-x-auto">
            <table id="tabela-analytics" class="w-full text-sm">
                <thead>
                    <tr class="border-b border-[var(--color-lab-border)]">
                        <th data-col="nome"    class="analytics-th text-left    px-4 py-3 font-mono text-[10px] uppercase tracking-widest text-[var(--color-lab-muted)] cursor-pointer hover:text-black select-none whitespace-nowrap">Produto <span class="sort-arrow ml-1"></span></th>
                        <th data-col="marca"   class="analytics-th text-left    px-4 py-3 font-mono text-[10px] uppercase tracking-widest text-[var(--color-lab-muted)] cursor-pointer hover:text-black select-none whitespace-nowrap">Marca <span class="sort-arrow ml-1"></span></th>
                        <th data-col="preco"   class="analytics-th text-right   px-4 py-3 font-mono text-[10px] uppercase tracking-widest text-[var(--color-lab-muted)] cursor-pointer hover:text-black select-none whitespace-nowrap">Preço Venda <span class="sort-arrow ml-1"></span></th>
                        <th data-col="custo"   class="analytics-th text-right   px-4 py-3 font-mono text-[10px] uppercase tracking-widest text-[var(--color-lab-muted)] cursor-pointer hover:text-black select-none whitespace-nowrap">Custo <span class="sort-arrow ml-1"></span></th>
                        <th data-col="lucro"   class="analytics-th text-right   px-4 py-3 font-mono text-[10px] uppercase tracking-widest text-[var(--color-lab-muted)] cursor-pointer hover:text-black select-none whitespace-nowrap">Lucro Unit. <span class="sort-arrow ml-1"></span></th>
                        <th data-col="margem"  class="analytics-th text-right   px-4 py-3 font-mono text-[10px] uppercase tracking-widest text-[var(--color-lab-muted)] cursor-pointer hover:text-black select-none whitespace-nowrap">Margem % <span class="sort-arrow ml-1"></span></th>
                        <th data-col="estoque" class="analytics-th text-right   px-4 py-3 font-mono text-[10px] uppercase tracking-widest text-[var(--color-lab-muted)] cursor-pointer hover:text-black select-none whitespace-nowrap">Estoque <span class="sort-arrow ml-1"></span></th>
                        <th data-col="status"  class="analytics-th text-center  px-4 py-3 font-mono text-[10px] uppercase tracking-widest text-[var(--color-lab-muted)] cursor-pointer hover:text-black select-none whitespace-nowrap">Status <span class="sort-arrow ml-1"></span></th>
                        <th class="px-4 py-3 font-mono text-[10px] uppercase tracking-widest text-[var(--color-lab-muted)] whitespace-nowrap">Ações</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($produtos_analytics as $p)
                    @php $margem = $p->margem_bruta_percentual; $lucro = $p->lucro_bruto_unitario; @endphp
                    <tr class="border-b border-[var(--color-lab-border)] hover:bg-gray-50 transition-colors"
                        data-id="{{ $p->id }}"
                        data-nome="{{ strtolower($p->nome) }}"
                        data-marca="{{ strtolower($p->marca ?? '') }}"
                        data-preco="{{ $p->preco_com_desconto }}"
                        data-custo="{{ $p->custo_compra ?? '' }}"
                        data-lucro="{{ $lucro ?? '' }}"
                        data-margem="{{ $margem ?? '' }}"
                        data-estoque="{{ $p->estoque }}"
                        data-status="{{ $p->ativo ? '1' : '0' }}">
                        <td class="px-4 py-3 font-mono text-xs text-black">{{ $p->nome }}</td>
                        <td class="px-4 py-3 font-mono text-xs text-[var(--color-lab-muted)]">{{ $p->marca ?? '—' }}</td>
                        <td class="px-4 py-3 font-mono text-xs text-black text-right">R$&nbsp;{{ number_format($p->preco_com_desconto, 2, ',', '.') }}</td>
                        <td class="px-4 py-3 font-mono text-xs text-right {{ $p->custo_compra ? 'text-black' : 'text-gray-300' }}" data-cell="custo">
                            {{ $p->custo_compra ? 'R$ ' . number_format($p->custo_compra, 2, ',', '.') : '—' }}
                        </td>
                        <td class="px-4 py-3 font-mono text-xs text-right {{ $lucro === null ? 'text-gray-300' : ($lucro >= 0 ? 'text-black' : 'text-red-600') }}" data-cell="lucro">
                            {{ $lucro !== null ? 'R$ ' . number_format($lucro, 2, ',', '.') : '—' }}
                        </td>
                        <td class="px-4 py-3 text-right" data-cell="margem">
                            @if($margem === null)
                                <span class="inline-block px-2 py-0.5 font-mono text-[10px] bg-blue-50 text-blue-500 border border-blue-200" title="Preço de compra não cadastrado — margem incalculável">Sem custo</span>
                            @elseif($margem < 0)
                                <span class="inline-block px-2 py-0.5 font-mono text-[10px] bg-red-100 text-red-700 font-bold">{{ number_format($margem, 1, ',', '.') }}%</span>
                            @elseif($margem < 20)
                                <span class="inline-block px-2 py-0.5 font-mono text-[10px] bg-yellow-100 text-yellow-700 font-bold">{{ number_format($margem, 1, ',', '.') }}%</span>
                            @else
                                <span class="inline-block px-2 py-0.5 font-mono text-[10px] bg-green-100 text-green-700 font-bold">{{ number_format($margem, 1, ',', '.') }}%</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 font-mono text-xs text-right {{ $p->estoque == 0 && $p->ativo ? 'text-yellow-600 font-bold' : 'text-black' }}" data-cell="estoque">{{ $p->estoque }}</td>
                        <td class="px-4 py-3 text-center">
                            <span class="inline-block px-2 py-0.5 font-mono text-[10px] border {{ $p->ativo ? 'border-black text-black' : 'border-gray-300 text-gray-400' }}">
                                {{ $p->ativo ? 'Ativo' : 'Inativo' }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-center">
                            <button onclick="abrirQuickFix({{ $p->id }}, '{{ addslashes($p->nome) }}', '{{ $p->custo_compra ?? '' }}', {{ $p->estoque }})"
                                    class="font-mono text-[10px] px-2 py-1 border border-[var(--color-lab-border)] text-[var(--color-lab-muted)] hover:border-black hover:text-black transition-colors"
                                    title="Editar custo e estoque">
                                ✏
                            </button>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    {{-- ── SECTION: PEDIDOS RECENTES ───────────────────────────────────── --}}
    <div data-section="pedidos-recentes" data-default-open="false" class="border border-[var(--color-lab-border)] bg-white">
        <div data-section-toggle class="flex items-center justify-between px-6 py-4 cursor-pointer hover:bg-gray-50 select-none">
            <p class="font-mono text-[10px] uppercase tracking-widest text-[var(--color-lab-muted)]">
                <span data-section-arrow>▶</span>&nbsp; Pedidos Recentes
            </p>
        </div>
        <div data-section-content class="hidden p-6">
            @forelse($pedidos_recentes as $pedido)
            <div class="border border-[var(--color-lab-border)] p-4 mb-3 last:mb-0">
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between">
                    <div class="flex-1 min-w-0">
                        <div class="flex items-center gap-3 mb-1">
                            <span class="font-mono text-sm font-bold text-black">#{{ $pedido->id }}</span>
                            <span class="font-mono text-[10px] uppercase tracking-widest text-[var(--color-lab-muted)]">{{ $pedido->created_at->format('d/m/Y H:i') }}</span>
                        </div>
                        <div class="font-mono text-sm text-black truncate">{{ $pedido->user->name }}</div>
                        <div class="font-mono text-xs text-[var(--color-lab-muted)] truncate">{{ $pedido->user->email }}</div>
                    </div>
                    <div class="flex flex-col sm:flex-row sm:items-center gap-2 sm:gap-4 mt-2 sm:mt-0">
                        <div class="font-mono text-sm font-bold text-black text-right">R$&nbsp;{{ number_format($pedido->valor_total, 2, ',', '.') }}</div>
                        @php $sc = ['pendente'=>'border-gray-400 text-gray-600','processando'=>'border-gray-600 text-gray-700','enviado'=>'border-gray-800 text-gray-800','entregue'=>'border-black text-black','cancelado'=>'border-gray-300 text-gray-400']; @endphp
                        <span class="inline-block px-3 py-1 font-mono text-[10px] uppercase tracking-widest border {{ $sc[$pedido->status] ?? 'border-gray-300 text-gray-500' }}">
                            {{ ucfirst($pedido->status) }}
                        </span>
                    </div>
                </div>
            </div>
            @empty
            <p class="font-mono text-xs uppercase tracking-widest text-[var(--color-lab-muted)] text-center py-8">Nenhum pedido</p>
            @endforelse
        </div>
    </div>

</div>

{{-- ── QUICK-FIX MODAL ─────────────────────────────────────────────── --}}
<div id="modal-quickfix" class="fixed inset-0 bg-black/40 backdrop-blur-sm hidden z-50" onclick="fecharQuickFix()">
    <div class="flex items-center justify-center min-h-screen p-4">
        <div class="bg-white border border-[var(--color-lab-border)] w-full max-w-sm" onclick="event.stopPropagation()">
            <div class="p-4 border-b border-[var(--color-lab-border)] flex justify-between items-center">
                <div>
                    <p class="font-mono text-[10px] uppercase tracking-widest text-[var(--color-lab-muted)]">Edição Rápida</p>
                    <p id="qf-nome" class="font-mono text-sm font-bold text-black mt-0.5 truncate max-w-[240px]"></p>
                </div>
                <button onclick="fecharQuickFix()" class="text-[var(--color-lab-muted)] hover:text-black p-1">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 6 6 18"/><path d="m6 6 12 12"/></svg>
                </button>
            </div>
            <form id="form-quickfix" class="p-4 space-y-4">
                @csrf
                <div>
                    <label class="font-mono text-[10px] uppercase tracking-widest text-[var(--color-lab-muted)] block mb-1">Custo de Compra (R$)</label>
                    <input id="qf-custo" type="text" placeholder="ex: 599,90"
                           class="w-full border border-[var(--color-lab-border)] px-3 py-2 font-mono text-sm focus:outline-none focus:border-black">
                    <p class="font-mono text-[10px] text-[var(--color-lab-muted)] mt-1">Use vírgula como decimal: 1.234,56</p>
                </div>
                <div>
                    <label class="font-mono text-[10px] uppercase tracking-widest text-[var(--color-lab-muted)] block mb-1">Estoque</label>
                    <input id="qf-estoque" type="number" min="0"
                           class="w-full border border-[var(--color-lab-border)] px-3 py-2 font-mono text-sm focus:outline-none focus:border-black">
                </div>
                <div class="flex gap-2 pt-2">
                    <button type="submit" id="qf-btn-salvar"
                            class="flex-1 bg-black text-white font-mono text-[10px] uppercase tracking-widest py-2 hover:bg-gray-800 transition-colors">
                        Salvar
                    </button>
                    <button type="button" onclick="fecharQuickFix()"
                            class="px-4 border border-[var(--color-lab-border)] font-mono text-[10px] uppercase tracking-widest text-black hover:border-black transition-colors">
                        Cancelar
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- Full product modal (for "+ Novo Produto" action bar button) --}}
@include('admin.includes.modal-produto')

<script>
(function () {
    const CSRF = document.querySelector('meta[name="csrf-token"]').content;

    // ── COLLAPSIBLE SECTIONS ────────────────────────────────────────────
    function initCollapsibles() {
        document.querySelectorAll('[data-section]').forEach(section => {
            const id      = section.dataset.section;
            const content = section.querySelector('[data-section-content]');
            const arrow   = section.querySelector('[data-section-arrow]');
            const toggle  = section.querySelector('[data-section-toggle]');
            if (!content || !toggle) return;

            const saved  = localStorage.getItem('dash-section-' + id);
            const defOpen = section.dataset.defaultOpen !== 'false';
            const isOpen  = saved === null ? defOpen : (saved === 'open');

            if (!isOpen) { content.classList.add('hidden'); if (arrow) arrow.textContent = '▶'; }
            else          { content.classList.remove('hidden'); if (arrow) arrow.textContent = '▼'; }

            toggle.addEventListener('click', () => {
                const open = !content.classList.contains('hidden');
                content.classList.toggle('hidden', open);
                if (arrow) arrow.textContent = open ? '▶' : '▼';
                localStorage.setItem('dash-section-' + id, open ? 'closed' : 'open');
            });
        });
    }

    // ── EXPANDABLE ALERT ROWS ───────────────────────────────────────────
    function initExpandableAlerts() {
        document.querySelectorAll('[data-expandable]').forEach(row => {
            row.addEventListener('click', () => {
                const target = document.getElementById(row.dataset.expandable);
                const arrow  = row.querySelector('[data-expand-arrow]');
                if (!target) return;
                const open = !target.classList.contains('hidden');
                target.classList.toggle('hidden', open);
                if (arrow) arrow.textContent = open ? '▶' : '▼';
            });
        });
    }

    // ── QUICK-FIX MODAL ─────────────────────────────────────────────────
    let quickFixId = null;

    window.abrirQuickFix = function(id, nome, custo, estoque) {
        quickFixId = id;
        document.getElementById('qf-nome').textContent    = nome;
        document.getElementById('qf-custo').value         = custo ? String(custo).replace('.', ',') : '';
        document.getElementById('qf-estoque').value       = estoque;
        document.getElementById('qf-btn-salvar').disabled = false;
        document.getElementById('qf-btn-salvar').textContent = 'Salvar';
        document.getElementById('modal-quickfix').classList.remove('hidden');
        document.getElementById('qf-custo').focus();
    };

    window.fecharQuickFix = function() {
        document.getElementById('modal-quickfix').classList.add('hidden');
        quickFixId = null;
    };

    document.getElementById('form-quickfix').addEventListener('submit', async function(e) {
        e.preventDefault();
        if (!quickFixId) return;

        const btn = document.getElementById('qf-btn-salvar');
        btn.disabled    = true;
        btn.textContent = 'Salvando...';

        const custo   = document.getElementById('qf-custo').value.trim();
        const estoque = document.getElementById('qf-estoque').value.trim();

        try {
            const url = (window.routes.adminProdutosQuickEdit || '').replace(':id', quickFixId);
            const res = await fetch(url, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF },
                body: JSON.stringify({ custo_compra: custo, estoque: estoque }),
            });
            const data = await res.json();
            if (!data.success) throw new Error('Falha ao salvar');

            // Update analytics table row
            const row = document.querySelector('#tabela-analytics tr[data-id="' + quickFixId + '"]');
            if (row) {
                row.dataset.custo   = data.custo_compra ?? '';
                row.dataset.margem  = data.margem ?? '';
                row.dataset.lucro   = data.lucro ?? '';
                row.dataset.estoque = data.estoque;

                const cellCusto = row.querySelector('[data-cell="custo"]');
                if (cellCusto) cellCusto.textContent = data.custo_compra
                    ? 'R$ ' + fmtMoney(data.custo_compra) : '—';

                const cellLucro = row.querySelector('[data-cell="lucro"]');
                if (cellLucro) cellLucro.textContent = data.lucro !== null
                    ? 'R$ ' + fmtMoney(data.lucro) : '—';

                const cellMargem = row.querySelector('[data-cell="margem"]');
                if (cellMargem) atualizarBadgeMargem(cellMargem, data.margem);

                const cellEstoque = row.querySelector('[data-cell="estoque"]');
                if (cellEstoque) cellEstoque.textContent = data.estoque;
            }

            // Remove product from all expanded alert lists
            document.querySelectorAll('[data-alert-produto="' + quickFixId + '"]').forEach(el => el.remove());

            fecharQuickFix();
            mostrarToast('Salvo com sucesso');
        } catch (err) {
            alert('Erro ao salvar. Tente novamente.');
            btn.disabled    = false;
            btn.textContent = 'Salvar';
        }
    });

    function fmtMoney(val) {
        return parseFloat(val).toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    }

    function atualizarBadgeMargem(cell, margem) {
        if (margem === null || margem === undefined || margem === '') {
            cell.innerHTML = '<span class="inline-block px-2 py-0.5 font-mono text-[10px] bg-blue-50 text-blue-500 border border-blue-200" title="Preço de compra não cadastrado">Sem custo</span>';
        } else {
            const m = parseFloat(margem);
            const fmt = m.toLocaleString('pt-BR', { minimumFractionDigits: 1, maximumFractionDigits: 1 }) + '%';
            if (m < 0)  cell.innerHTML = `<span class="inline-block px-2 py-0.5 font-mono text-[10px] bg-red-100 text-red-700 font-bold">${fmt}</span>`;
            else if (m < 20) cell.innerHTML = `<span class="inline-block px-2 py-0.5 font-mono text-[10px] bg-yellow-100 text-yellow-700 font-bold">${fmt}</span>`;
            else cell.innerHTML = `<span class="inline-block px-2 py-0.5 font-mono text-[10px] bg-green-100 text-green-700 font-bold">${fmt}</span>`;
        }
    }

    // ── INLINE ORDER STATUS ─────────────────────────────────────────────
    window.avancarStatusPedido = async function(btn, pedidoId, novoStatus) {
        btn.disabled    = true;
        const original  = btn.textContent;
        btn.textContent = '...';

        try {
            const url = (window.routes.adminPedidosQuickStatus || '').replace(':id', pedidoId);
            const res = await fetch(url, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF },
                body: JSON.stringify({ status: novoStatus }),
            });
            const data = await res.json();
            if (!data.success) throw new Error('Falha');

            const row = document.getElementById('pedido-acao-' + pedidoId);
            if (!row) return;

            if (novoStatus === 'enviado') {
                row.style.transition = 'opacity 0.4s';
                row.style.opacity    = '0';
                setTimeout(() => row.remove(), 400);
            } else {
                // pendente → processando: update badge + swap button label
                const badge = row.querySelector('[data-status-badge]');
                if (badge) { badge.textContent = 'Processando'; badge.className = badge.className.replace('border-gray-400 text-gray-600','border-gray-600 text-gray-700'); }
                btn.textContent = '→ Enviado';
                btn.onclick     = () => window.avancarStatusPedido(btn, pedidoId, 'enviado');
                btn.disabled    = false;
            }

            mostrarToast('Status atualizado');
        } catch (err) {
            btn.disabled    = false;
            btn.textContent = original;
            alert('Erro ao atualizar status.');
        }
    };

    // ── TOAST ────────────────────────────────────────────────────────────
    function mostrarToast(msg) {
        const t = document.createElement('div');
        t.className   = 'fixed bottom-4 right-4 bg-black text-white font-mono text-xs px-4 py-2 z-[100]';
        t.textContent = msg;
        document.body.appendChild(t);
        setTimeout(() => { t.style.transition = 'opacity 0.3s'; t.style.opacity = '0'; setTimeout(() => t.remove(), 300); }, 2000);
    }

    // ── TABLE SORTING ────────────────────────────────────────────────────
    const table   = document.getElementById('tabela-analytics');
    const numCols = ['preco','custo','lucro','margem','estoque','status'];
    let currentCol = 'margem', currentDir = 'asc';

    function sortTable(col, dir) {
        const tbody = table.querySelector('tbody');
        const rows  = Array.from(tbody.querySelectorAll('tr'));
        rows.sort((a, b) => {
            let av = a.dataset[col] ?? '', bv = b.dataset[col] ?? '';
            if (numCols.includes(col)) {
                const an = av === '' ? (dir === 'asc' ? Infinity : -Infinity) : parseFloat(av);
                const bn = bv === '' ? (dir === 'asc' ? Infinity : -Infinity) : parseFloat(bv);
                return dir === 'asc' ? an - bn : bn - an;
            }
            if (av === '' && bv !== '') return 1;
            if (bv === '' && av !== '') return -1;
            return dir === 'asc' ? av.localeCompare(bv) : bv.localeCompare(av);
        });
        rows.forEach(r => tbody.appendChild(r));
    }

    function updateArrows(activeCol, dir) {
        if (!table) return;
        table.querySelectorAll('th.analytics-th').forEach(th => {
            const arrow = th.querySelector('.sort-arrow');
            if (!arrow) return;
            if (th.dataset.col === activeCol) {
                arrow.textContent = dir === 'asc' ? '↑' : '↓';
                th.classList.add('text-black');
                th.classList.remove('text-[var(--color-lab-muted)]');
            } else {
                arrow.textContent = '';
                th.classList.remove('text-black');
                th.classList.add('text-[var(--color-lab-muted)]');
            }
        });
    }

    if (table) {
        table.querySelectorAll('th.analytics-th[data-col]').forEach(th => {
            th.addEventListener('click', () => {
                const col = th.dataset.col;
                const dir = (col === currentCol && currentDir === 'asc') ? 'desc' : 'asc';
                currentCol = col; currentDir = dir;
                sortTable(col, dir);
                updateArrows(col, dir);
            });
        });
        sortTable('margem', 'asc');
        updateArrows('margem', 'asc');
    }

    // ── INIT ─────────────────────────────────────────────────────────────
    initCollapsibles();
    initExpandableAlerts();

    document.addEventListener('keydown', e => {
        if (e.key === 'Escape') fecharQuickFix();
    });
})();
</script>
@endsection
```

- [ ] **Step 2: Clear view cache**

```bash
docker exec laravel-app php artisan view:clear && docker exec laravel-app php artisan cache:clear
```

- [ ] **Step 3: Verify dashboard loads**

```bash
docker exec laravel-app php artisan tinker --execute="
\$req = new Illuminate\Http\Request();
echo 'Dashboard view variables check' . PHP_EOL;
echo 'top_produtos query: ' . App\Models\ItemPedido::select('produto_id', \DB::raw('SUM(quantidade) as total_vendido'))->whereHas('pedido', fn(\$q) => \$q->where('status','entregue'))->groupBy('produto_id')->count() . ' rows' . PHP_EOL;
echo 'pedidos_acao: ' . App\Models\Pedido::whereIn('status',['pendente','processando'])->count() . PHP_EOL;
" 2>/dev/null | grep -v 'Writing\|PsySH'
```

Visit `/admin/dashboard` in browser. Verify:
- All 5 sections render
- Action bar "+ Novo Produto" opens the full product modal
- Collapsible sections toggle (click section headers)
- Collapsible state persists on page reload (localStorage)
- Alert rows expand on click showing affected products
- Quick-fix modal opens from alert row button and from table ✏ button
- Saving quick-fix updates the table row without page reload
- "Sem custo" badge changes to colored margin badge after saving
- Order advance buttons work inline
- Table column sorting works

- [ ] **Step 4: Commit**

```bash
git add resources/views/admin/dashboard.blade.php
git commit -m "feat: dashboard v2 — action bar, collapsible sections, quick-fix modal, inline order status, performance analytics"
```

---

## Final verification

```bash
# No PHP errors in log
docker exec laravel-app tail -20 storage/logs/laravel.log

# All admin routes still registered
docker exec laravel-app php artisan route:list --path=admin | grep -E 'GET|POST'

# Dashboard renders (HTTP 200)
curl -s -o /dev/null -w "%{http_code}" http://localhost/admin/dashboard
```

Expected: no errors in log, all routes present, HTTP 200 (or 302 redirect to login if not authenticated — both are correct).
