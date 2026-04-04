@extends('includes.header-admin')

@section('title', 'Dashboard')

@section('content')
<div class="space-y-6 lg:space-y-8">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:justify-between sm:items-end gap-4">
        <div>
            <p class="font-mono text-[10px] uppercase tracking-widest text-[var(--color-lab-muted)] mb-1">Overview</p>
            <h1 class="text-2xl sm:text-3xl font-bold text-black tracking-tight">Dashboard</h1>
        </div>
        <div class="font-mono text-xs text-[var(--color-lab-muted)] uppercase tracking-widest">
            {{ now()->format('d/m/Y H:i') }}
        </div>
    </div>

    <!-- Cards de Estatisticas -->
    <div class="grid grid-cols-2 lg:grid-cols-4 xl:grid-cols-6 gap-4 lg:gap-6">
        <!-- Total de Produtos -->
        <div class="border border-[var(--color-lab-border)] bg-white p-6">
            <div class="flex items-start justify-between">
                <div>
                    <p class="font-mono text-[10px] uppercase tracking-widest text-[var(--color-lab-muted)] mb-2">Produtos</p>
                    <p class="text-2xl sm:text-3xl font-bold text-black font-mono">{{ $total_produtos }}</p>
                </div>
                <div class="text-[var(--color-lab-muted)]">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="m7.5 4.27 9 5.15"/><path d="M21 8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16Z"/><path d="m3.3 7 8.7 5 8.7-5"/><path d="M12 22V12"/></svg>
                </div>
            </div>
        </div>

        <!-- Total de Pedidos -->
        <div class="border border-[var(--color-lab-border)] bg-white p-6">
            <div class="flex items-start justify-between">
                <div>
                    <p class="font-mono text-[10px] uppercase tracking-widest text-[var(--color-lab-muted)] mb-2">Pedidos</p>
                    <p class="text-2xl sm:text-3xl font-bold text-black font-mono">{{ $total_pedidos }}</p>
                </div>
                <div class="text-[var(--color-lab-muted)]">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M6 2 3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4Z"/><path d="M3 6h18"/><path d="M16 10a4 4 0 0 1-8 0"/></svg>
                </div>
            </div>
        </div>

        <!-- Pedidos Pendentes -->
        <div class="border border-[var(--color-lab-border)] bg-white p-6">
            <div class="flex items-start justify-between">
                <div>
                    <p class="font-mono text-[10px] uppercase tracking-widest text-[var(--color-lab-muted)] mb-2">Pendentes</p>
                    <p class="text-2xl sm:text-3xl font-bold text-black font-mono">{{ $pedidos_pendentes }}</p>
                </div>
                <div class="text-[var(--color-lab-muted)]">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                </div>
            </div>
        </div>

        <!-- Receita Total -->
        <div class="border border-[var(--color-lab-border)] bg-white p-6">
            <div class="flex items-start justify-between">
                <div>
                    <p class="font-mono text-[10px] uppercase tracking-widest text-[var(--color-lab-muted)] mb-2">Receita</p>
                    <p class="text-lg sm:text-2xl font-bold text-black font-mono">R$ {{ number_format($receita_total, 2, ',', '.') }}</p>
                </div>
                <div class="text-[var(--color-lab-muted)]">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><line x1="12" x2="12" y1="2" y2="22"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>
                </div>
            </div>
        </div>

        <div class="border border-[var(--color-lab-border)] bg-white p-6">
            <div class="flex items-start justify-between">
                <div>
                    <p class="font-mono text-[10px] uppercase tracking-widest text-[var(--color-lab-muted)] mb-2">Custo</p>
                    <p class="text-lg sm:text-2xl font-bold text-black font-mono">R$ {{ number_format($custo_total, 2, ',', '.') }}</p>
                </div>
                <div class="text-[var(--color-lab-muted)]">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2v20"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7H14a3.5 3.5 0 0 1 0 7H6"/></svg>
                </div>
            </div>
        </div>

        <div class="border border-[var(--color-lab-border)] bg-white p-6">
            <div class="flex items-start justify-between">
                <div>
                    <p class="font-mono text-[10px] uppercase tracking-widest text-[var(--color-lab-muted)] mb-2">Lucro Bruto</p>
                    <p class="text-lg sm:text-2xl font-bold text-black font-mono">R$ {{ number_format($lucro_bruto_total, 2, ',', '.') }}</p>
                </div>
                <div class="text-[var(--color-lab-muted)]">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="m3 17 6-6 4 4 8-8"/><path d="M14 7h7v7"/></svg>
                </div>
            </div>
        </div>

        <div class="border border-[var(--color-lab-border)] bg-white p-6">
            <div class="flex items-start justify-between">
                <div>
                    <p class="font-mono text-[10px] uppercase tracking-widest text-[var(--color-lab-muted)] mb-2">Margem</p>
                    <p class="text-2xl sm:text-3xl font-bold text-black font-mono">{{ number_format($margem_bruta_percentual, 2, ',', '.') }}%</p>
                </div>
                <div class="text-[var(--color-lab-muted)]">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M16 8h-6a2 2 0 0 0 0 4h4a2 2 0 0 1 0 4H8"/><path d="M12 18V6"/></svg>
                </div>
            </div>
        </div>
    </div>

    <!-- Status dos Pedidos + Acoes Rapidas -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-4 lg:gap-6">
        <!-- Status dos Pedidos -->
        <div class="border border-[var(--color-lab-border)] bg-white p-6">
            <p class="font-mono text-[10px] uppercase tracking-widest text-[var(--color-lab-muted)] mb-6">Status dos Pedidos</p>
            <div class="space-y-4">
                <div class="flex items-center justify-between">
                    <div class="flex items-center space-x-3">
                        <div class="w-2 h-2 bg-gray-400"></div>
                        <span class="font-mono text-sm text-black">Pendentes</span>
                    </div>
                    <span class="font-mono text-sm font-bold text-black">{{ $pedidos_pendentes }}</span>
                </div>
                <div class="flex items-center justify-between">
                    <div class="flex items-center space-x-3">
                        <div class="w-2 h-2 bg-gray-600"></div>
                        <span class="font-mono text-sm text-black">Processando</span>
                    </div>
                    <span class="font-mono text-sm font-bold text-black">{{ $pedidos_processando }}</span>
                </div>
                <div class="flex items-center justify-between">
                    <div class="flex items-center space-x-3">
                        <div class="w-2 h-2 bg-gray-800"></div>
                        <span class="font-mono text-sm text-black">Enviados</span>
                    </div>
                    <span class="font-mono text-sm font-bold text-black">{{ $pedidos_enviados }}</span>
                </div>
                <div class="flex items-center justify-between">
                    <div class="flex items-center space-x-3">
                        <div class="w-2 h-2 bg-black"></div>
                        <span class="font-mono text-sm text-black">Entregues</span>
                    </div>
                    <span class="font-mono text-sm font-bold text-black">{{ $pedidos_entregues }}</span>
                </div>
                <div class="flex items-center justify-between">
                    <div class="flex items-center space-x-3">
                        <div class="w-2 h-2 bg-gray-300"></div>
                        <span class="font-mono text-sm text-black">Cancelados</span>
                    </div>
                    <span class="font-mono text-sm font-bold text-black">{{ $pedidos_cancelados }}</span>
                </div>
                <div class="pt-4 border-t border-[var(--color-lab-border)]">
                    <div class="flex items-center justify-between">
                        <span class="font-mono text-sm text-black">Itens entregues sem custo</span>
                        <span class="font-mono text-sm font-bold text-black">{{ $itens_sem_custo }}</span>
                    </div>
                    <p class="font-mono text-[10px] uppercase tracking-widest text-[var(--color-lab-muted)] mt-2">Custos e lucro consideram apenas pedidos entregues</p>
                </div>
            </div>
        </div>

        <!-- Acoes Rapidas -->
        <div class="border border-[var(--color-lab-border)] bg-white p-6">
            <p class="font-mono text-[10px] uppercase tracking-widest text-[var(--color-lab-muted)] mb-6">Acoes Rapidas</p>
            <div class="space-y-2">
                <a href="{{ route('admin.produtos') }}" class="flex items-center space-x-3 px-4 py-3 border border-[var(--color-lab-border)] hover:border-black hover:bg-gray-50 transition-colors">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14"/><path d="M12 5v14"/></svg>
                    <span class="font-mono text-xs uppercase tracking-widest text-black">Adicionar Produto</span>
                </a>
                <a href="{{ route('admin.categorias') }}" class="flex items-center space-x-3 px-4 py-3 border border-[var(--color-lab-border)] hover:border-black hover:bg-gray-50 transition-colors">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2H2v10l9.29 9.29c.94.94 2.48.94 3.42 0l6.58-6.58c.94-.94.94-2.48 0-3.42L12 2Z"/><path d="M7 7h.01"/></svg>
                    <span class="font-mono text-xs uppercase tracking-widest text-black">Gerenciar Categorias</span>
                </a>
                <a href="{{ route('admin.pedidos') }}" class="flex items-center space-x-3 px-4 py-3 border border-[var(--color-lab-border)] hover:border-black hover:bg-gray-50 transition-colors">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M6 2 3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4Z"/><path d="M3 6h18"/><path d="M16 10a4 4 0 0 1-8 0"/></svg>
                    <span class="font-mono text-xs uppercase tracking-widest text-black">Ver Pedidos</span>
                </a>
            </div>
        </div>
    </div>

    <!-- Pedidos Recentes -->
    <div class="border border-[var(--color-lab-border)] bg-white">
        <div class="p-6 border-b border-[var(--color-lab-border)]">
            <p class="font-mono text-[10px] uppercase tracking-widest text-[var(--color-lab-muted)]">Pedidos Recentes</p>
        </div>
        <div class="p-6">
            @forelse($pedidos_recentes as $pedido)
            <div class="border border-[var(--color-lab-border)] p-4 mb-3 last:mb-0">
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between">
                    <div class="flex-1 min-w-0">
                        <div class="flex items-center space-x-3 mb-1">
                            <span class="font-mono text-sm font-bold text-black">#{{ $pedido->id }}</span>
                            <span class="font-mono text-[10px] uppercase tracking-widest text-[var(--color-lab-muted)]">{{ $pedido->created_at->format('d/m/Y H:i') }}</span>
                        </div>
                        <div class="font-mono text-sm text-black truncate">{{ $pedido->user->name }}</div>
                        <div class="font-mono text-xs text-[var(--color-lab-muted)] truncate">{{ $pedido->user->email }}</div>
                    </div>
                    <div class="flex flex-col sm:flex-row sm:items-center space-y-2 sm:space-y-0 sm:space-x-4 mt-2 sm:mt-0">
                        <div class="text-right">
                            <div class="font-mono text-sm font-bold text-black">R$ {{ number_format($pedido->valor_total, 2, ',', '.') }}</div>
                        </div>
                        <div>
                            @php
                                $statusColors = [
                                    'pendente' => 'border-gray-400 text-gray-600',
                                    'processando' => 'border-gray-600 text-gray-700',
                                    'enviado' => 'border-gray-800 text-gray-800',
                                    'entregue' => 'border-black text-black',
                                    'cancelado' => 'border-gray-300 text-gray-400'
                                ];
                            @endphp
                            <span class="inline-block px-3 py-1 font-mono text-[10px] uppercase tracking-widest border {{ $statusColors[$pedido->status] ?? 'border-gray-300 text-gray-500' }}">
                                {{ ucfirst($pedido->status) }}
                            </span>
                        </div>
                    </div>
                </div>
            </div>
            @empty
            <div class="text-center py-12">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1" stroke-linecap="round" stroke-linejoin="round" class="mx-auto text-gray-300 mb-3"><path d="M6 2 3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4Z"/><path d="M3 6h18"/><path d="M16 10a4 4 0 0 1-8 0"/></svg>
                <p class="font-mono text-xs uppercase tracking-widest text-[var(--color-lab-muted)]">Nenhum pedido encontrado</p>
            </div>
            @endforelse
        </div>
    </div>
</div>
@endsection
