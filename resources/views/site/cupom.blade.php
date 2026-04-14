<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <link rel="icon" type="image/png" href="{{ asset('favicon.png') }}">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="robots" content="noindex, nofollow">
    <title>Portal do Parceiro - JFXTECH</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <link rel="stylesheet" href="{{ asset('css/site-styles.css') }}">
    <style>
        /* ── Progress bar ── */
        .progress-track {
            background: #e5e7eb;
            height: 8px;
            overflow: hidden;
        }
        .progress-fill {
            height: 100%;
            background: #000;
            transition: width 0.8s cubic-bezier(.4,0,.2,1);
        }

        /* ── Spreadsheet table ── */
        .sheet-table { width: 100%; border-collapse: collapse; font-family: monospace; font-size: 12px; }
        .sheet-table thead th {
            position: sticky;
            top: 0;
            background: #000;
            color: #fff;
            text-align: left;
            padding: 10px 12px;
            font-size: 10px;
            letter-spacing: .08em;
            text-transform: uppercase;
            white-space: nowrap;
            user-select: none;
        }
        .sheet-table thead th .sort-icon { margin-left: 4px; opacity: .5; font-size: 9px; }
        .sheet-table tbody tr:nth-child(odd)  { background: #fafafa; }
        .sheet-table tbody tr:nth-child(even) { background: #fff; }
        .sheet-table tbody tr:hover { background: #f3f4f6; }
        .sheet-table tbody td { padding: 9px 12px; border-bottom: 1px solid #e5e7eb; white-space: nowrap; }
        .sheet-table tfoot td {
            padding: 10px 12px;
            border-top: 2px solid #000;
            font-weight: 700;
            background: #f9fafb;
            white-space: nowrap;
        }

        /* ── Tooltip ── */
        [data-tooltip] { position: relative; cursor: help; }
        [data-tooltip]::after {
            content: attr(data-tooltip);
            position: absolute;
            bottom: calc(100% + 6px);
            left: 50%;
            transform: translateX(-50%);
            background: #111;
            color: #fff;
            font-size: 10px;
            font-family: monospace;
            letter-spacing: .05em;
            padding: 4px 8px;
            white-space: nowrap;
            pointer-events: none;
            opacity: 0;
            transition: opacity .15s;
            z-index: 10;
        }
        [data-tooltip]:hover::after { opacity: 1; }

        /* ── Badge ── */
        .badge { display: inline-block; padding: 2px 8px; font-size: 10px; font-family: monospace; letter-spacing: .06em; text-transform: uppercase; }
        .badge-green { background: #d1fae5; color: #065f46; }
        .badge-gray  { background: #f3f4f6; color: #6b7280; }
        .badge-black { background: #000; color: #fff; }
        .badge-outline { border: 1px solid #d1d5db; color: #374151; }

        /* ── KPI cards ── */
        .kpi-value { font-family: monospace; font-size: 28px; font-weight: 700; line-height: 1; }
        .kpi-label { font-family: monospace; font-size: 10px; text-transform: uppercase; letter-spacing: .08em; }

        /* ── Pagination ── */
        .pag-btn {
            border: 1px solid #d1d5db;
            background: #fff;
            padding: 4px 14px;
            font-family: monospace;
            font-size: 11px;
            cursor: pointer;
            text-transform: uppercase;
            letter-spacing: .06em;
        }
        .pag-btn:hover:not(:disabled) { background: #000; color: #fff; border-color: #000; }
        .pag-btn:disabled { opacity: .35; cursor: default; }

        /* ── Right column compact table ── */
        .compact-table { width: 100%; border-collapse: collapse; font-family: monospace; font-size: 11px; }
        .compact-table th {
            background: #f9fafb;
            border-bottom: 2px solid #000;
            padding: 8px 10px;
            text-align: left;
            font-size: 9px;
            letter-spacing: .08em;
            text-transform: uppercase;
        }
        .compact-table td { padding: 8px 10px; border-bottom: 1px solid #e5e7eb; }
        .compact-table tr:last-child td { border-bottom: none; }

        /* ── Tier table ── */
        .tier-row { display: flex; justify-content: space-between; align-items: center; padding: 12px 16px; border: 1px solid #e5e7eb; margin-bottom: -1px; }
        .tier-row.active { background: #000; color: #fff; border-color: #000; z-index: 1; position: relative; }
        .tier-row .tier-label { font-family: monospace; font-size: 10px; text-transform: uppercase; letter-spacing: .07em; }
        .tier-row .tier-rate  { font-family: monospace; font-size: 22px; font-weight: 700; }

        /* ── Responsive ── */
        @media (max-width: 1023px) {
            .dashboard-cols { display: block !important; }
            .dashboard-cols > * + * { margin-top: 24px; }
        }
    </style>
</head>
<body class="min-h-screen flex flex-col bg-[var(--color-lab-bg)] text-[var(--color-lab-ink)] antialiased">
    @include('includes.header')

    <main class="flex-grow">
        <section class="py-16">
            <div class="container mx-auto px-4 lg:px-8">
                <div class="max-w-7xl mx-auto">

                    {{-- ── Header ── --}}
                    <div class="mb-8 flex flex-col gap-4 md:flex-row md:items-end md:justify-between">
                        <div>
                            <span class="font-mono text-[10px] uppercase tracking-widest text-gray-500">Parceiro</span>
                            <h1 class="text-3xl font-bold uppercase tracking-wider text-black mt-1">Portal do Parceiro</h1>
                            <p class="text-sm text-gray-500 font-mono mt-1">{{ $usuario->name }}</p>
                        </div>
                        <div class="flex items-center gap-3">
                            <span class="badge badge-black">Tier atual: {{ $progress['current_rate'] }}%</span>
                            <a href="{{ route('site.perfil') }}" class="inline-flex items-center border border-[var(--color-lab-border)] px-4 py-2 text-xs font-mono uppercase tracking-widest hover:bg-white transition-colors">
                                ← Perfil
                            </a>
                        </div>
                    </div>

                    {{-- ── KPIs 3×2 ── --}}
                    <div class="grid grid-cols-2 md:grid-cols-3 gap-4 mb-6">
                        {{-- Cupons vinculados --}}
                        <div class="bg-white border border-[var(--color-lab-border)] p-5">
                            <p class="kpi-label text-gray-500 mb-3">Cupons vinculados</p>
                            <span class="kpi-value text-black"
                                  data-animate-counter
                                  data-value="{{ $cupons->count() }}"
                                  data-type="integer">{{ $cupons->count() }}</span>
                            <p class="font-mono text-[10px] text-gray-400 mt-2">{{ $cupons->pluck('codigo')->implode(', ') }}</p>
                        </div>

                        {{-- Vendas pagas --}}
                        <div class="bg-white border border-[var(--color-lab-border)] p-5">
                            <p class="kpi-label text-gray-500 mb-3">Vendas pagas</p>
                            <span class="kpi-value text-black"
                                  data-animate-counter
                                  data-value="{{ $progress['total_sales'] }}"
                                  data-type="integer">{{ $progress['total_sales'] }}</span>
                            <p class="font-mono text-[10px] text-gray-400 mt-2">{{ $progress['current_label'] }}</p>
                        </div>

                        {{-- Comissão atual --}}
                        <div class="bg-black text-white p-5">
                            <p class="kpi-label text-white/60 mb-3">Comissão atual</p>
                            <span class="kpi-value"
                                  data-animate-counter
                                  data-value="{{ $progress['current_rate'] }}"
                                  data-type="integer">{{ $progress['current_rate'] }}</span><span class="font-mono text-xl font-bold">%</span>
                            @if($progress['next_threshold'])
                                <p class="font-mono text-[10px] text-white/50 mt-2">Faltam {{ $progress['sales_to_next'] }} para o próximo tier</p>
                            @else
                                <p class="font-mono text-[10px] text-white/50 mt-2">Tier máximo atingido</p>
                            @endif
                        </div>

                        {{-- Comissão acumulada --}}
                        <div class="bg-white border border-[var(--color-lab-border)] p-5">
                            <p class="kpi-label text-gray-500 mb-3">Comissão acumulada</p>
                            <span class="kpi-value text-black font-mono text-xl"
                                  data-animate-counter
                                  data-value="{{ number_format($comissaoAcumulada, 2, '.', '') }}"
                                  data-type="monetary">R$ {{ number_format($comissaoAcumulada, 2, ',', '.') }}</span>
                            <p class="font-mono text-[10px] text-gray-400 mt-2">taxa {{ $progress['current_rate'] }}% s/ líquido</p>
                        </div>

                        {{-- Total em vendas --}}
                        <div class="bg-white border border-[var(--color-lab-border)] p-5">
                            <p class="kpi-label text-gray-500 mb-3">Total em vendas geradas</p>
                            <span class="kpi-value text-black font-mono text-xl"
                                  data-animate-counter
                                  data-value="{{ number_format($totalLiquido, 2, '.', '') }}"
                                  data-type="monetary">R$ {{ number_format($totalLiquido, 2, ',', '.') }}</span>
                            <p class="font-mono text-[10px] text-gray-400 mt-2">valor líquido (após desconto)</p>
                        </div>

                        {{-- Média por pedido --}}
                        <div class="bg-white border border-[var(--color-lab-border)] p-5">
                            <p class="kpi-label text-gray-500 mb-3">Média por pedido</p>
                            <span class="kpi-value text-black font-mono text-xl"
                                  data-animate-counter
                                  data-value="{{ number_format($mediaPorPedido, 2, '.', '') }}"
                                  data-type="monetary">R$ {{ number_format($mediaPorPedido, 2, ',', '.') }}</span>
                            <p class="font-mono text-[10px] text-gray-400 mt-2">{{ $allSales->count() }} pedidos</p>
                        </div>
                    </div>

                    {{-- ── Progress banner ── --}}
                    <div class="bg-white border border-[var(--color-lab-border)] p-5 mb-8">
                        <div class="flex justify-between items-center mb-3">
                            <span class="font-mono text-[10px] uppercase tracking-widest text-gray-500">Progressão de tier</span>
                            @if($progress['next_threshold'])
                                <span class="font-mono text-[10px] text-gray-500">
                                    {{ $progress['total_sales'] }} / {{ $progress['next_threshold'] }} vendas
                                    &mdash; faltam {{ $progress['sales_to_next'] }} para o próximo tier
                                </span>
                            @else
                                <span class="font-mono text-[10px] text-black font-bold uppercase tracking-widest">Tier máximo atingido</span>
                            @endif
                        </div>
                        <div class="progress-track">
                            <div class="progress-fill" style="width: {{ $progressPct }}%"></div>
                        </div>
                        <div class="flex justify-between mt-2">
                            @foreach($progress['tiers'] as $tier)
                                <span class="font-mono text-[9px] uppercase tracking-widest {{ $tier['rate'] === $progress['current_rate'] ? 'text-black font-bold' : 'text-gray-400' }}">{{ $tier['rate'] }}%</span>
                            @endforeach
                        </div>
                    </div>

                    {{-- ── Main two-column layout ── --}}
                    <div class="dashboard-cols" style="display:grid;grid-template-columns:65fr 35fr;gap:24px;align-items:start;">

                        {{-- ── LEFT: Planilha de Vendas ── --}}
                        <div class="bg-white border border-[var(--color-lab-border)]">
                            <div class="px-5 py-4 border-b border-[var(--color-lab-border)] flex items-center justify-between">
                                <h2 class="font-mono text-xs font-bold uppercase tracking-widest">Planilha de Vendas</h2>
                                <span class="font-mono text-[10px] text-gray-400">{{ $allSales->count() }} registros</span>
                            </div>

                            @if($allSales->isEmpty())
                                <div class="p-6">
                                    <p class="text-sm text-gray-500 font-mono">Nenhuma venda paga com seus cupons ainda.</p>
                                </div>
                            @else
                                <div class="overflow-x-auto">
                                    <table class="sheet-table" id="sales-table">
                                        <thead>
                                            <tr>
                                                <th>#</th>
                                                <th data-sort="id" data-numeric="true"># Pedido <span class="sort-icon">↕</span></th>
                                                <th data-sort="cupom">Cupom <span class="sort-icon">↕</span></th>
                                                <th data-sort="bruto" data-numeric="true">Valor Bruto <span class="sort-icon">↕</span></th>
                                                <th data-sort="desconto" data-numeric="true">Desconto <span class="sort-icon">↕</span></th>
                                                <th data-sort="liquido" data-numeric="true">Valor Líquido <span class="sort-icon">↕</span></th>
                                                <th data-sort="comissao" data-numeric="true">Comissão <span class="sort-icon">↕</span></th>
                                                <th data-sort="data" data-numeric="true">Data <span class="sort-icon">↕</span></th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($allSales as $i => $sale)
                                                @php
                                                    $liquido = $sale->valor_total;
                                                    $bruto = $sale->valor_total + $sale->valor_desconto;
                                                    $comissao = $liquido * $taxa;
                                                @endphp
                                                <tr class="sale-row"
                                                    data-id="{{ $sale->id }}"
                                                    data-cupom="{{ $sale->cupom_codigo }}"
                                                    data-bruto="{{ number_format($bruto, 2, '.', '') }}"
                                                    data-desconto="{{ number_format($sale->valor_desconto, 2, '.', '') }}"
                                                    data-liquido="{{ number_format($liquido, 2, '.', '') }}"
                                                    data-comissao="{{ number_format($comissao, 2, '.', '') }}"
                                                    data-data="{{ $sale->created_at->timestamp }}">
                                                    <td class="text-gray-400">{{ $i + 1 }}</td>
                                                    <td class="font-bold">#{{ $sale->id }}</td>
                                                    <td><span class="badge badge-outline">{{ $sale->cupom_codigo }}</span></td>
                                                    <td>R$ {{ number_format($bruto, 2, ',', '.') }}</td>
                                                    <td class="text-red-600">-R$ {{ number_format($sale->valor_desconto, 2, ',', '.') }}</td>
                                                    <td class="font-bold">R$ {{ number_format($liquido, 2, ',', '.') }}</td>
                                                    <td class="text-green-700 font-bold"
                                                        data-tooltip="Taxa: {{ $progress['current_rate'] }}% sobre líquido">
                                                        R$ {{ number_format($comissao, 2, ',', '.') }}
                                                    </td>
                                                    <td class="text-gray-400">{{ $sale->created_at->format('d/m/Y') }}</td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                        <tfoot>
                                            @php
                                                $totalBruto = $allSales->sum(fn($s) => $s->valor_total + $s->valor_desconto);
                                                $totalDesc  = $allSales->sum('valor_desconto');
                                            @endphp
                                            <tr>
                                                <td colspan="3" class="text-gray-500 uppercase tracking-widest text-[10px]">Totais</td>
                                                <td>R$ {{ number_format($totalBruto, 2, ',', '.') }}</td>
                                                <td class="text-red-600">-R$ {{ number_format($totalDesc, 2, ',', '.') }}</td>
                                                <td>R$ {{ number_format($totalLiquido, 2, ',', '.') }}</td>
                                                <td class="text-green-700">R$ {{ number_format($comissaoAcumulada, 2, ',', '.') }}</td>
                                                <td></td>
                                            </tr>
                                        </tfoot>
                                    </table>
                                </div>

                                {{-- Paginação --}}
                                <div class="px-5 py-3 border-t border-[var(--color-lab-border)] flex items-center justify-between">
                                    <div class="flex gap-2">
                                        <button class="pag-btn" id="sales-table-prev">← Anterior</button>
                                        <button class="pag-btn" id="sales-table-next">Próxima →</button>
                                    </div>
                                    <span class="font-mono text-[10px] text-gray-500" id="sales-table-page-info"></span>
                                </div>
                            @endif
                        </div>

                        {{-- ── RIGHT column ── --}}
                        <div class="space-y-6">

                            {{-- Planilha de Cupons --}}
                            <div class="bg-white border border-[var(--color-lab-border)]">
                                <div class="px-5 py-4 border-b border-[var(--color-lab-border)]">
                                    <h2 class="font-mono text-xs font-bold uppercase tracking-widest">Meus Cupons</h2>
                                </div>
                                <div class="overflow-x-auto">
                                    <table class="compact-table">
                                        <thead>
                                            <tr>
                                                <th>Código</th>
                                                <th>Tipo</th>
                                                <th>Valor</th>
                                                <th>Usos</th>
                                                <th>Validade</th>
                                                <th>Status</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($cupons as $cupom)
                                                @php
                                                    $expirado = $cupom->valido_ate && \Carbon\Carbon::parse($cupom->valido_ate)->isPast();
                                                    $ativo = $cupom->ativo && !$expirado;
                                                @endphp
                                                <tr>
                                                    <td class="font-bold tracking-widest">{{ $cupom->codigo }}</td>
                                                    <td>
                                                        @if($cupom->tipo === 'percentual')
                                                            <span class="badge badge-outline">% desc</span>
                                                        @else
                                                            <span class="badge badge-outline">R$ fixo</span>
                                                        @endif
                                                    </td>
                                                    <td>
                                                        @if($cupom->tipo === 'percentual')
                                                            {{ number_format($cupom->valor, 0) }}%
                                                        @else
                                                            R$ {{ number_format($cupom->valor, 2, ',', '.') }}
                                                        @endif
                                                    </td>
                                                    <td>{{ $cupom->usos_realizados }}{{ $cupom->limite_usos ? '/'.$cupom->limite_usos : '' }}</td>
                                                    <td>{{ $cupom->valido_ate ? \Carbon\Carbon::parse($cupom->valido_ate)->format('d/m/Y') : '—' }}</td>
                                                    <td>
                                                        @if($ativo)
                                                            <span class="badge badge-green">Ativo</span>
                                                        @else
                                                            <span class="badge badge-gray">{{ $expirado ? 'Expirado' : 'Inativo' }}</span>
                                                        @endif
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </div>

                            {{-- Tabela de Progressão --}}
                            <div class="bg-white border border-[var(--color-lab-border)]">
                                <div class="px-5 py-4 border-b border-[var(--color-lab-border)]">
                                    <h2 class="font-mono text-xs font-bold uppercase tracking-widest">Tabela de Progressão</h2>
                                </div>
                                <div>
                                    @foreach($progress['tiers'] as $tier)
                                        @php
                                            $isCurrent = $tier['rate'] === $progress['current_rate'];
                                        @endphp
                                        <div class="tier-row {{ $isCurrent ? 'active' : '' }}">
                                            <div>
                                                <p class="tier-label {{ $isCurrent ? 'text-white/60' : 'text-gray-400' }}">Faixa</p>
                                                <p class="font-mono text-sm font-bold uppercase tracking-wide">{{ $tier['label'] }}</p>
                                                @if($isCurrent)
                                                    <span class="font-mono text-[9px] text-white/50 uppercase tracking-widest">← atual</span>
                                                @endif
                                            </div>
                                            <div class="text-right">
                                                <p class="tier-label {{ $isCurrent ? 'text-white/60' : 'text-gray-400' }}">Comissão</p>
                                                <p class="tier-rate">{{ $tier['rate'] }}%</p>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>

                        </div>{{-- end right --}}
                    </div>{{-- end dashboard-cols --}}

                </div>
            </div>
        </section>
    </main>

    @include('includes.footer')
    <script src="{{ asset('js/cupom-dashboard.js') }}"></script>
</body>
</html>
