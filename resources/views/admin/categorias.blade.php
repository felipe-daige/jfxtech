@extends('includes.header-admin')

@section('title', 'Gerenciar Categorias')

@section('content')
<div class="space-y-6 lg:space-y-8">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:justify-between sm:items-end gap-4">
        <div>
            <p class="font-mono text-[10px] uppercase tracking-widest text-[var(--color-lab-muted)] mb-1">Gerenciamento</p>
            <h1 class="text-2xl sm:text-3xl font-bold text-black tracking-tight">Categorias</h1>
        </div>
        <button onclick="abrirModalCategoria()" class="bg-black text-white px-5 py-2.5 text-xs font-bold tracking-widest uppercase hover:bg-gray-800 transition-colors w-full sm:w-auto flex items-center justify-center space-x-2">
            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14"/><path d="M12 5v14"/></svg>
            <span>Nova Categoria</span>
        </button>
    </div>

    <!-- Lista de Categorias -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-2 xl:grid-cols-3 2xl:grid-cols-4 gap-4 lg:gap-6">
        @forelse($categorias as $categoria)
        <div class="border border-[var(--color-lab-border)] bg-white p-4 sm:p-5">
            <div class="flex justify-between items-start mb-4">
                <div class="flex-1 min-w-0">
                    <h3 class="font-mono text-sm font-bold text-black break-words sm:truncate uppercase tracking-wide">{{ $categoria->nome }}</h3>
                    @if($categoria->descricao)
                        <p class="font-mono text-xs text-[var(--color-lab-muted)] mt-1 line-clamp-3">{{ $categoria->descricao }}</p>
                    @endif
                </div>
                <div class="flex space-x-1 ml-2 flex-shrink-0">
                    <button onclick="editarCategoria({{ $categoria->id }}, '{{ $categoria->nome }}', '{{ $categoria->descricao }}')" class="text-[var(--color-lab-muted)] hover:text-black p-1.5 border border-transparent hover:border-[var(--color-lab-border)] transition-colors" title="Editar categoria">
                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 3a2.85 2.83 0 1 1 4 4L7.5 20.5 2 22l1.5-5.5Z"/><path d="m15 5 4 4"/></svg>
                    </button>
                    <form method="POST" action="{{ route('admin.categorias.excluir', $categoria->id) }}" class="inline" onsubmit="return confirm('Tem certeza que deseja excluir esta categoria?')">
                        @csrf
                        <button type="submit" class="text-[var(--color-lab-muted)] hover:text-black p-1.5 border border-transparent hover:border-[var(--color-lab-border)] transition-colors" title="Excluir categoria">
                            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 6h18"/><path d="M19 6v14c0 1-1 2-2 2H7c-1 0-2-1-2-2V6"/><path d="M8 6V4c0-1 1-2 2-2h4c1 0 2 1 2 2v2"/><line x1="10" x2="10" y1="11" y2="17"/><line x1="14" x2="14" y1="11" y2="17"/></svg>
                        </button>
                    </form>
                </div>
            </div>

            <div class="flex items-center justify-between border-t border-[var(--color-lab-border)] pt-3">
                <div class="flex items-center space-x-2">
                    <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-[var(--color-lab-muted)]"><path d="m7.5 4.27 9 5.15"/><path d="M21 8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16Z"/><path d="m3.3 7 8.7 5 8.7-5"/><path d="M12 22V12"/></svg>
                    <span class="font-mono text-[10px] uppercase tracking-widest text-[var(--color-lab-muted)]">{{ $categoria->produtos_count }} produto(s)</span>
                </div>
                <a href="{{ route('admin.produtos') }}?categoria_id={{ $categoria->id }}" class="font-mono text-[10px] uppercase tracking-widest text-black hover:text-gray-600 font-bold flex items-center space-x-1">
                    <span>Ver</span>
                    <svg xmlns="http://www.w3.org/2000/svg" width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14"/><path d="m12 5 7 7-7 7"/></svg>
                </a>
            </div>
        </div>
        @empty
        <div class="col-span-full border border-[var(--color-lab-border)] bg-white p-12 text-center">
            <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1" stroke-linecap="round" stroke-linejoin="round" class="mx-auto text-gray-300 mb-4"><path d="M12 2H2v10l9.29 9.29c.94.94 2.48.94 3.42 0l6.58-6.58c.94-.94.94-2.48 0-3.42L12 2Z"/><path d="M7 7h.01"/></svg>
            <h3 class="font-mono text-sm font-bold uppercase tracking-widest text-black mb-2">Nenhuma categoria encontrada</h3>
            <p class="font-mono text-xs text-[var(--color-lab-muted)] mb-6">Comece criando sua primeira categoria</p>
            <button onclick="abrirModalCategoria()" class="bg-black text-white px-5 py-2.5 text-xs font-bold tracking-widest uppercase hover:bg-gray-800 transition-colors inline-flex items-center space-x-2">
                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14"/><path d="M12 5v14"/></svg>
                <span>Criar Primeira Categoria</span>
            </button>
        </div>
        @endforelse
    </div>
</div>

<!-- Modal de Categoria -->
<div id="modalCategoria" class="fixed inset-0 bg-black/40 backdrop-blur-sm hidden z-50">
    <div class="flex items-center justify-center min-h-screen p-2 sm:p-4">
        <div class="bg-white border border-[var(--color-lab-border)] w-full max-w-md max-h-[92vh] overflow-y-auto">
            <div class="p-6 border-b border-[var(--color-lab-border)]">
                <div class="flex justify-between items-center">
                    <h3 class="font-mono text-sm font-bold uppercase tracking-widest text-black" id="modalTitulo">Nova Categoria</h3>
                    <button onclick="fecharModalCategoria()" class="text-[var(--color-lab-muted)] hover:text-black">
                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 6 6 18"/><path d="m6 6 12 12"/></svg>
                    </button>
                </div>
            </div>

            <form id="formCategoria" method="POST">
                @csrf
                <div id="formMethod" style="display: none;"></div>

                <div class="p-6 space-y-5">
                    <div>
                        <label class="font-mono text-[10px] uppercase tracking-widest text-[var(--color-lab-muted)] block mb-2">Nome da Categoria</label>
                        <input type="text" name="nome" id="nome" class="w-full px-4 py-3 border border-[var(--color-lab-border)] text-sm font-mono focus:outline-none focus:border-black" required>
                    </div>

                    <div>
                        <label class="font-mono text-[10px] uppercase tracking-widest text-[var(--color-lab-muted)] block mb-2">Descricao (opcional)</label>
                        <textarea name="descricao" id="descricao" rows="3" class="w-full px-4 py-3 border border-[var(--color-lab-border)] text-sm font-mono focus:outline-none focus:border-black"></textarea>
                    </div>
                </div>

                <div class="p-6 border-t border-[var(--color-lab-border)] flex flex-col sm:flex-row justify-end space-y-2 sm:space-y-0 sm:space-x-3">
                    <button type="button" onclick="fecharModalCategoria()" class="w-full sm:w-auto px-5 py-2.5 text-xs font-bold tracking-widest uppercase border border-[var(--color-lab-border)] text-black hover:bg-gray-50 transition-colors">
                        Cancelar
                    </button>
                    <button type="submit" class="w-full sm:w-auto bg-black text-white px-5 py-2.5 text-xs font-bold tracking-widest uppercase hover:bg-gray-800 transition-colors">
                        Salvar Categoria
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@endsection
