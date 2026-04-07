@extends('includes.header-admin')

@section('title', 'Gerenciar Pedidos')

@section('content')
@php use App\Enums\PedidoStatus; @endphp
<div class="space-y-6 lg:space-y-8">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:justify-between sm:items-end gap-4">
        <div>
            <p class="font-mono text-[10px] uppercase tracking-widest text-[var(--color-lab-muted)] mb-1">Gerenciamento</p>
            <h1 class="text-2xl sm:text-3xl font-bold text-black tracking-tight">Pedidos</h1>
        </div>
    </div>

    <!-- Filtros -->
    <div class="border border-[var(--color-lab-border)] bg-white p-4 sm:p-6">
        <p class="font-mono text-[10px] uppercase tracking-widest text-[var(--color-lab-muted)] mb-4">Filtros</p>
        <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-[minmax(0,1fr)_minmax(0,1fr)_auto] gap-4">
                <select id="filtroStatus" class="flex-1 px-4 py-3 border border-[var(--color-lab-border)] text-sm font-mono focus:outline-none focus:border-black bg-white">
                    <option value="">Todos os status</option>
                    @foreach(PedidoStatus::adminValues() as $status)
                        <option value="{{ $status }}" @selected(request('status') == $status)>{{ PedidoStatus::label($status) }}</option>
                    @endforeach
                </select>

            <input type="date" id="filtroData" value="{{ request('data') }}" class="flex-1 px-4 py-3 border border-[var(--color-lab-border)] text-sm font-mono focus:outline-none focus:border-black">

            <button onclick="aplicarFiltros()" class="bg-black text-white px-5 py-2.5 text-xs font-bold tracking-widest uppercase hover:bg-gray-800 transition-colors w-full sm:w-auto flex items-center justify-center space-x-2">
                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="22 3 2 3 10 12.46 10 19 14 21 14 12.46 22 3"/></svg>
                <span>Filtrar</span>
            </button>
        </div>
    </div>

    <!-- Lista de Pedidos -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-2 xl:grid-cols-3 2xl:grid-cols-4 gap-4 lg:gap-6">
        @forelse($pedidos as $pedido)
        <div class="border border-[var(--color-lab-border)] bg-white p-4 sm:p-5">
            <div class="flex justify-between items-start mb-4">
                <div class="flex-1 min-w-0">
                    <div class="flex items-center space-x-2 mb-2">
                        <div class="flex-shrink-0">
                            <div class="w-8 h-8 bg-black flex items-center justify-center">
                                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M6 2 3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4Z"/><path d="M3 6h18"/><path d="M16 10a4 4 0 0 1-8 0"/></svg>
                            </div>
                        </div>
                        <div class="flex-1 min-w-0">
                            <h3 class="font-mono text-sm font-bold text-black">#{{ $pedido->id }}</h3>
                            <p class="font-mono text-[10px] uppercase tracking-widest text-[var(--color-lab-muted)]">{{ $pedido->created_at->format('d/m/Y H:i') }}</p>
                        </div>
                    </div>
                    <div class="space-y-0.5">
                        <div class="font-mono text-sm text-black break-words sm:truncate">{{ $pedido->user->name }}</div>
                        <div class="font-mono text-[10px] text-[var(--color-lab-muted)] break-all sm:truncate">{{ $pedido->user->email }}</div>
                    </div>
                </div>
                <div class="flex space-x-1 ml-2 flex-shrink-0">
                    <button onclick="verDetalhes({{ $pedido->id }})" class="text-[var(--color-lab-muted)] hover:text-black p-1.5 border border-transparent hover:border-[var(--color-lab-border)] transition-colors" title="Ver detalhes">
                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M2 12s3-7 10-7 10 7 10 7-3 7-10 7-10-7-10-7Z"/><circle cx="12" cy="12" r="3"/></svg>
                    </button>
                    <button onclick="alterarStatus({{ $pedido->id }}, '{{ $pedido->status }}')" class="text-[var(--color-lab-muted)] hover:text-black p-1.5 border border-transparent hover:border-[var(--color-lab-border)] transition-colors" title="Alterar status">
                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 3a2.85 2.83 0 1 1 4 4L7.5 20.5 2 22l1.5-5.5Z"/><path d="m15 5 4 4"/></svg>
                    </button>
                </div>
            </div>

            <div class="space-y-2 border-t border-[var(--color-lab-border)] pt-3">
                <!-- Valor -->
                <div class="flex items-center justify-between gap-3">
                    <span class="font-mono text-[10px] uppercase tracking-widest text-[var(--color-lab-muted)]">Valor:</span>
                    <span class="font-mono text-sm font-bold text-black">R$ {{ number_format($pedido->valor_total, 2, ',', '.') }}</span>
                </div>

                <!-- Status -->
                <div class="flex items-center justify-between gap-3">
                    <span class="font-mono text-[10px] uppercase tracking-widest text-[var(--color-lab-muted)]">Status:</span>
                    @php $badgeColors = PedidoStatus::badgeClasses(); @endphp
                    <span class="inline-block px-3 py-1 font-mono text-[10px] uppercase tracking-widest border {{ $badgeColors[$pedido->status] ?? 'border-gray-300 text-gray-500' }}">
                        {{ PedidoStatus::label($pedido->status) }}
                    </span>
                </div>
            </div>
        </div>
        @empty
        <div class="col-span-full border border-[var(--color-lab-border)] bg-white p-12 text-center">
            <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1" stroke-linecap="round" stroke-linejoin="round" class="mx-auto text-gray-300 mb-4"><path d="M6 2 3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4Z"/><path d="M3 6h18"/><path d="M16 10a4 4 0 0 1-8 0"/></svg>
            <h3 class="font-mono text-sm font-bold uppercase tracking-widest text-black mb-2">Nenhum pedido encontrado</h3>
            <p class="font-mono text-xs text-[var(--color-lab-muted)]">Os pedidos aparecerao aqui quando os clientes fizerem compras</p>
        </div>
        @endforelse
    </div>

    <!-- Paginacao -->
    @if($pedidos->hasPages())
    <div class="mt-6">
        {{ $pedidos->links() }}
    </div>
    @endif
</div>

<!-- Modal de Detalhes do Pedido -->
<div id="modalDetalhes" class="fixed inset-0 bg-black/40 backdrop-blur-sm hidden z-50">
    <div class="flex items-center justify-center min-h-screen p-2 sm:p-4">
        <div class="bg-white border border-[var(--color-lab-border)] w-full max-w-4xl max-h-[95vh] sm:max-h-[90vh] overflow-y-auto">
            <div class="p-6 border-b border-[var(--color-lab-border)]">
                <div class="flex justify-between items-center">
                    <h3 class="font-mono text-sm font-bold uppercase tracking-widest text-black">Detalhes do Pedido</h3>
                    <button onclick="fecharModalDetalhes()" class="text-[var(--color-lab-muted)] hover:text-black p-1">
                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 6 6 18"/><path d="m6 6 12 12"/></svg>
                    </button>
                </div>
            </div>

            <div id="conteudoDetalhes" class="p-6">
                <!-- Conteudo sera carregado via JavaScript -->
            </div>
        </div>
    </div>
</div>

<!-- Modal de Alterar Status -->
<div id="modalStatus" class="fixed inset-0 bg-black/40 backdrop-blur-sm hidden z-50">
    <div class="flex items-center justify-center min-h-screen p-2 sm:p-4">
        <div class="bg-white border border-[var(--color-lab-border)] w-full max-w-md max-h-[92vh] overflow-y-auto">
            <div class="p-6 border-b border-[var(--color-lab-border)]">
                <div class="flex justify-between items-center">
                    <h3 class="font-mono text-sm font-bold uppercase tracking-widest text-black">Alterar Status</h3>
                    <button onclick="fecharModalStatus()" class="text-[var(--color-lab-muted)] hover:text-black p-1">
                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 6 6 18"/><path d="m6 6 12 12"/></svg>
                    </button>
                </div>
            </div>

            <form id="formStatus" method="POST">
                @csrf

                <div class="p-6">
                    <div>
                        <label class="font-mono text-[10px] uppercase tracking-widest text-[var(--color-lab-muted)] block mb-2">Novo Status</label>
                        <select name="status" id="novoStatus" class="w-full px-4 py-3 border border-[var(--color-lab-border)] text-sm font-mono focus:outline-none focus:border-black bg-white" required>
                            @foreach(PedidoStatus::adminValues() as $status)
                                <option value="{{ $status }}">{{ PedidoStatus::label($status) }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="p-6 border-t border-[var(--color-lab-border)] flex flex-col sm:flex-row justify-end space-y-2 sm:space-y-0 sm:space-x-3">
                    <button type="button" onclick="fecharModalStatus()" class="w-full sm:w-auto px-5 py-2.5 text-xs font-bold tracking-widest uppercase border border-[var(--color-lab-border)] text-black hover:bg-gray-50 transition-colors">
                        Cancelar
                    </button>
                    <button type="submit" class="w-full sm:w-auto bg-black text-white px-5 py-2.5 text-xs font-bold tracking-widest uppercase hover:bg-gray-800 transition-colors">
                        Atualizar Status
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@endsection
