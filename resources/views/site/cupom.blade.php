<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <link rel="icon" type="image/png" href="{{ asset('favicon.png') }}">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="robots" content="noindex, nofollow">
    <title>Meu Cupom - JFXTECH</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <link rel="stylesheet" href="{{ asset('css/site-styles.css') }}">
</head>
<body class="min-h-screen flex flex-col bg-[var(--color-lab-bg)] text-[var(--color-lab-ink)] antialiased">
    @include('includes.header')

    <main class="flex-grow">
        <section class="py-16">
            <div class="container mx-auto px-4 lg:px-8">
                <div class="max-w-5xl mx-auto">
                    <div class="mb-10 flex flex-col gap-4 md:flex-row md:items-end md:justify-between">
                        <div>
                            <span class="font-mono text-[10px] uppercase tracking-widest text-gray-500">Parceiro</span>
                            <h1 class="text-3xl font-bold uppercase tracking-wider text-black mt-1">Meu Cupom</h1>
                            <p class="text-sm text-gray-500 font-mono mt-2">Acompanhe seus cupons, vendas pagas e sua progressão de comissão.</p>
                        </div>
                        <a href="{{ route('site.perfil') }}" class="inline-flex items-center border border-[var(--color-lab-border)] px-4 py-2 text-xs font-mono uppercase tracking-widest hover:bg-white transition-colors">
                            Voltar ao Perfil
                        </a>
                    </div>

                    <div class="grid gap-4 md:grid-cols-3 mb-8">
                        <div class="bg-white border border-[var(--color-lab-border)] p-6">
                            <p class="font-mono text-[10px] uppercase tracking-widest text-gray-500 mb-2">Cupons vinculados</p>
                            <span class="font-mono text-3xl font-bold text-black">{{ $cupons->count() }}</span>
                        </div>
                        <div class="bg-white border border-[var(--color-lab-border)] p-6">
                            <p class="font-mono text-[10px] uppercase tracking-widest text-gray-500 mb-2">Vendas pagas</p>
                            <span class="font-mono text-3xl font-bold text-black">{{ $progress['total_sales'] }}</span>
                        </div>
                        <div class="bg-black text-white border border-black p-6">
                            <p class="font-mono text-[10px] uppercase tracking-widest text-white/70 mb-2">Comissão atual</p>
                            <span class="font-mono text-3xl font-bold">{{ $progress['current_rate'] }}%</span>
                            <p class="font-mono text-[10px] uppercase tracking-widest mt-2 text-white/70">{{ $progress['current_label'] }}</p>
                        </div>
                    </div>

                    <div class="grid gap-8 lg:grid-cols-[1.1fr,0.9fr]">
                        <div class="space-y-8">
                            <div class="bg-white border border-[var(--color-lab-border)] p-6">
                                <div class="flex items-center justify-between mb-4">
                                    <h2 class="font-mono text-sm font-bold uppercase tracking-widest">Seus códigos</h2>
                                    @if($progress['next_threshold'] !== null)
                                        <span class="text-[10px] font-mono uppercase tracking-widest text-gray-500">
                                            Faltam {{ $progress['sales_to_next'] }} vendas para {{ $progress['next_threshold'] }}+
                                        </span>
                                    @else
                                        <span class="text-[10px] font-mono uppercase tracking-widest text-gray-500">Faixa máxima atingida</span>
                                    @endif
                                </div>
                                <div class="flex flex-wrap gap-3">
                                    @foreach($cupons as $cupom)
                                        <div class="border border-[var(--color-lab-border)] px-4 py-3 min-w-[170px]">
                                            <p class="font-mono text-xs font-bold uppercase tracking-widest text-black">{{ $cupom->codigo }}</p>
                                            <p class="text-[10px] font-mono uppercase tracking-widest text-gray-500 mt-2">
                                                {{ $cupom->tipo === 'percentual' ? number_format($cupom->valor, 0) . '% off' : 'R$ ' . number_format($cupom->valor, 2, ',', '.') . ' off' }}
                                            </p>
                                        </div>
                                    @endforeach
                                </div>
                            </div>

                            <div class="bg-white border border-[var(--color-lab-border)] p-6">
                                <h2 class="font-mono text-sm font-bold uppercase tracking-widest mb-4">Últimas vendas pagas</h2>
                                @if($allSales->isEmpty())
                                    <p class="text-sm text-gray-500 font-mono">Nenhuma venda paga com seus cupons ainda.</p>
                                @else
                                    <div class="overflow-x-auto">
                                        <table class="w-full">
                                            <thead>
                                                <tr class="border-b border-[var(--color-lab-border)]">
                                                    <th class="px-4 py-3 text-left font-mono text-[10px] uppercase tracking-widest text-gray-500">Pedido</th>
                                                    <th class="px-4 py-3 text-left font-mono text-[10px] uppercase tracking-widest text-gray-500">Cupom</th>
                                                    <th class="px-4 py-3 text-left font-mono text-[10px] uppercase tracking-widest text-gray-500">Valor</th>
                                                    <th class="px-4 py-3 text-left font-mono text-[10px] uppercase tracking-widest text-gray-500">Data</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach($allSales as $sale)
                                                    <tr class="border-b border-[var(--color-lab-border)] last:border-0">
                                                        <td class="px-4 py-4 font-mono text-sm">#{{ $sale->id }}</td>
                                                        <td class="px-4 py-4 font-mono text-sm">{{ $sale->cupom_codigo }}</td>
                                                        <td class="px-4 py-4 font-mono text-sm">R$ {{ number_format($sale->valor_total, 2, ',', '.') }}</td>
                                                        <td class="px-4 py-4 font-mono text-sm text-gray-500">{{ $sale->created_at->format('d/m/Y') }}</td>
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                @endif
                            </div>
                        </div>

                        <div class="bg-white border border-[var(--color-lab-border)] p-6">
                            <h2 class="font-mono text-sm font-bold uppercase tracking-widest mb-4">Tabela de Progressão</h2>
                            <div class="space-y-3">
                                @foreach($progress['tiers'] as $tier)
                                    @php
                                        $isCurrent = $progress['current_rate'] === $tier['rate'] && $progress['current_label'] === $tier['label'];
                                    @endphp
                                    <div class="border px-4 py-4 {{ $isCurrent ? 'border-black bg-black text-white' : 'border-[var(--color-lab-border)]' }}">
                                        <div class="flex items-center justify-between gap-4">
                                            <div>
                                                <p class="font-mono text-[10px] uppercase tracking-widest {{ $isCurrent ? 'text-white/70' : 'text-gray-500' }}">Faixa</p>
                                                <p class="font-mono text-sm font-bold uppercase tracking-widest">{{ $tier['label'] }}</p>
                                            </div>
                                            <div class="text-right">
                                                <p class="font-mono text-[10px] uppercase tracking-widest {{ $isCurrent ? 'text-white/70' : 'text-gray-500' }}">Comissão</p>
                                                <p class="font-mono text-2xl font-bold">{{ $tier['rate'] }}%</p>
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </main>

    @include('includes.footer')
</body>
</html>
