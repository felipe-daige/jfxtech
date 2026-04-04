@extends('includes.header-admin')

@section('title', 'Gerenciar Produtos')

@section('content')
<style>
    .admin-description-preview .product-description-content {
        color: #374151;
        font-size: 0.95rem;
        line-height: 1.85;
    }

    .admin-description-preview .product-description-content > *:first-child {
        margin-top: 0;
    }

    .admin-description-preview .product-description-content > *:last-child {
        margin-bottom: 0;
    }

    .admin-description-preview .product-description-content p,
    .admin-description-preview .product-description-content ul,
    .admin-description-preview .product-description-content ol {
        margin: 0 0 1rem;
    }

    .admin-description-preview .product-description-content ul {
        list-style: none;
        padding-left: 0;
    }

    .admin-description-preview .product-description-content li {
        position: relative;
        padding-left: 1.15rem;
    }

    .admin-description-preview .product-description-content li::before {
        content: "";
        position: absolute;
        left: 0;
        top: 0.8rem;
        width: 0.4rem;
        height: 0.4rem;
        background: #111111;
    }

    .admin-description-preview .product-description-content strong {
        color: #111827;
        background: linear-gradient(transparent 62%, rgba(17, 17, 17, 0.12) 62%);
    }
</style>
<div class="space-y-6 lg:space-y-8">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:justify-between sm:items-end gap-4">
        <div>
            <p class="font-mono text-[10px] uppercase tracking-widest text-[var(--color-lab-muted)] mb-1">Gerenciamento</p>
            <h1 class="text-2xl sm:text-3xl font-bold text-black tracking-tight">Produtos</h1>
        </div>
        <button onclick="abrirModalProduto()" class="bg-black text-white px-5 py-2.5 text-xs font-bold tracking-widest uppercase hover:bg-gray-800 transition-colors w-full sm:w-auto flex items-center justify-center space-x-2">
            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14"/><path d="M12 5v14"/></svg>
            <span>Novo Produto</span>
        </button>
    </div>

    <!-- Filtros e Pesquisa -->
    <div class="border border-[var(--color-lab-border)] bg-white p-4 sm:p-6">
        <div class="space-y-4">
            <!-- Campo de Pesquisa -->
            <div class="flex flex-col sm:flex-row gap-3">
                <div class="flex-1">
                    <label for="pesquisa" class="font-mono text-[10px] uppercase tracking-widest text-[var(--color-lab-muted)] block mb-2">Pesquisar Produtos</label>
                    <div class="relative">
                        <input type="text"
                               name="pesquisa"
                               id="pesquisa"
                               value="{{ request('pesquisa') }}"
                               placeholder="Digite o nome do produto..."
                               class="w-full pl-10 pr-4 py-3 border border-[var(--color-lab-border)] text-sm font-mono focus:outline-none focus:border-black">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-[var(--color-lab-muted)]"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/></svg>
                        </div>
                        <div id="loadingIndicator" class="absolute inset-y-0 right-0 pr-3 flex items-center hidden">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="animate-spin text-[var(--color-lab-muted)]"><path d="M21 12a9 9 0 1 1-6.219-8.56"/></svg>
                        </div>
                    </div>
                </div>
                <div class="flex flex-col sm:flex-row gap-2 sm:items-end">
                    @if(request('pesquisa') || request('status') || request('destaque') || request('categoria_id'))
                        <a href="{{ route('admin.produtos') }}" class="bg-white border border-[var(--color-lab-border)] text-black px-5 py-2.5 text-xs font-bold tracking-widest uppercase hover:bg-gray-50 transition-colors flex items-center justify-center space-x-2">
                            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 6 6 18"/><path d="m6 6 12 12"/></svg>
                            <span>Limpar Filtros</span>
                        </a>
                    @endif
                </div>
            </div>

            <!-- Filtros -->
            <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4">
                <!-- Filtro de Status -->
                <div>
                    <label for="status" class="font-mono text-[10px] uppercase tracking-widest text-[var(--color-lab-muted)] block mb-2">Status</label>
                    <select name="status" id="status" class="w-full px-4 py-3 border border-[var(--color-lab-border)] text-sm font-mono focus:outline-none focus:border-black bg-white">
                        <option value="">Todos os status</option>
                        <option value="ativo" {{ request('status') === 'ativo' ? 'selected' : '' }}>Apenas Ativos</option>
                        <option value="inativo" {{ request('status') === 'inativo' ? 'selected' : '' }}>Apenas Inativos</option>
                    </select>
                </div>

                <!-- Filtro de Categoria -->
                <div>
                    <label for="categoria_id" class="font-mono text-[10px] uppercase tracking-widest text-[var(--color-lab-muted)] block mb-2">Categoria</label>
                    <select name="categoria_id" id="categoria_id_filtro" class="w-full px-4 py-3 border border-[var(--color-lab-border)] text-sm font-mono focus:outline-none focus:border-black bg-white">
                        <option value="">Todas as categorias</option>
                        @foreach($categorias as $categoria)
                            <option value="{{ $categoria->id }}" {{ request('categoria_id') == $categoria->id ? 'selected' : '' }}>{{ $categoria->nome }}</option>
                        @endforeach
                    </select>
                </div>

                <!-- Filtro de Destaque -->
                <div>
                    <label for="destaque" class="font-mono text-[10px] uppercase tracking-widest text-[var(--color-lab-muted)] block mb-2">Destaque</label>
                    <select name="destaque" id="destaque" class="w-full px-4 py-3 border border-[var(--color-lab-border)] text-sm font-mono focus:outline-none focus:border-black bg-white">
                        <option value="">Todos os produtos</option>
                        <option value="sim" {{ request('destaque') === 'sim' ? 'selected' : '' }}>Apenas em Destaque</option>
                        <option value="nao" {{ request('destaque') === 'nao' ? 'selected' : '' }}>Sem Destaque</option>
                    </select>
                </div>
            </div>
        </div>
    </div>

    {{-- Barra de Resultados / View Toolbar --}}
    <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-3
                py-3 border-b border-[var(--color-lab-border)]">

        {{-- Contador + Select-all --}}
        <div class="flex flex-wrap items-center gap-3">
            <label class="flex items-center gap-2 cursor-pointer group" title="Selecionar todos">
                <input type="checkbox" id="selectAll"
                       class="w-4 h-4 cursor-pointer accent-black flex-shrink-0">
                <span class="font-mono text-[10px] uppercase tracking-widest
                             text-[var(--color-lab-muted)] group-hover:text-black transition-colors
                             select-none hidden sm:inline">Todos</span>
            </label>
            <div class="w-px h-3 bg-[var(--color-lab-border)]" aria-hidden="true"></div>
            <span id="resultTotal"
                  class="font-mono text-[10px] uppercase tracking-widest text-[var(--color-lab-muted)]">
                {{ $produtos->total() }} {{ $produtos->total() === 1 ? 'produto' : 'produtos' }}
            </span>
        </div>

        {{-- Controles --}}
        <div class="flex flex-col sm:flex-row sm:items-center gap-3 sm:gap-0">

            {{-- Select per-page --}}
            <label class="font-mono text-[10px] uppercase tracking-widest text-[var(--color-lab-muted)] mr-2 hidden sm:inline"
                   for="per_page">Por página</label>
            <select id="per_page"
                    class="h-9 w-full sm:w-auto px-2 border border-[var(--color-lab-border)] bg-white font-mono text-[10px] uppercase tracking-widest text-black focus:outline-none focus:ring-1 focus:ring-black">
                @foreach([12, 24, 48, 96] as $opt)
                    <option value="{{ $opt }}" {{ $perPage == $opt ? 'selected' : '' }}>{{ $opt }}</option>
                @endforeach
            </select>

            {{-- Separador vertical --}}
            <div class="hidden sm:block w-px h-5 bg-[var(--color-lab-border)] mx-4" aria-hidden="true"></div>

            {{-- View toggle --}}
            <div class="flex items-center border border-[var(--color-lab-border)]"
                 role="group" aria-label="Modo de visualização">

                <button type="button" data-view="cards"
                        class="view-toggle-btn h-9 px-3 font-mono text-[10px] uppercase tracking-widest
                               border-r border-[var(--color-lab-border)] transition-colors
                               focus:outline-none focus:ring-1 focus:ring-black focus:ring-inset
                               {{ $viewMode === 'cards' ? 'bg-black text-white' : 'bg-white text-[var(--color-lab-muted)] hover:text-black hover:bg-[var(--color-lab-bg)]' }}"
                        aria-pressed="{{ $viewMode === 'cards' ? 'true' : 'false' }}"
                        aria-label="Visualização em cards">
                    Cards
                </button>

                <button type="button" data-view="lista"
                        class="view-toggle-btn h-9 px-3 font-mono text-[10px] uppercase tracking-widest
                               transition-colors
                               focus:outline-none focus:ring-1 focus:ring-black focus:ring-inset
                               {{ $viewMode === 'lista' ? 'bg-black text-white' : 'bg-white text-[var(--color-lab-muted)] hover:text-black hover:bg-[var(--color-lab-bg)]' }}"
                        aria-pressed="{{ $viewMode === 'lista' ? 'true' : 'false' }}"
                        aria-label="Visualização em lista">
                    Lista
                </button>

            </div>
        </div>
    </div>

    {{-- Barra de seleção (aparece quando há itens marcados) --}}
    <div id="selectionBar" class="hidden flex-col items-start sm:flex-row sm:items-center sm:justify-between gap-3
         py-2.5 px-3 mt-3 border border-[var(--color-lab-border)] bg-[var(--color-lab-bg)]">
        <span id="selectionCount"
              class="font-mono text-[10px] uppercase tracking-widest text-black whitespace-nowrap">
            0 selecionados
        </span>
        <div class="w-full overflow-x-auto admin-mobile-scroll">
        <div class="flex min-w-max items-center gap-2 justify-start sm:justify-end">
            {{-- Tags Actions --}}
            <div class="flex items-center gap-1 border-r border-[var(--color-lab-border)] pr-2 mr-1">
                <div class="relative group">
                    <button type="button" class="h-7 px-3 font-mono text-[10px] uppercase tracking-widest border border-[var(--color-lab-border)] bg-gray-100 text-black hover:bg-gray-200 transition-colors flex items-center gap-1">
                        + Tags
                        <svg class="w-3 h-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m6 9 6 6 6-6"/></svg>
                    </button>
                    <div class="absolute bottom-full left-0 mb-1 w-48 bg-white border border-[var(--color-lab-border)] shadow-lg hidden group-hover:block z-50">
                        <button onclick="bulkAction('set_exclusivo')" class="block w-full text-left px-4 py-2 font-mono text-[10px] uppercase tracking-widest text-black hover:bg-gray-50 border-b border-[var(--color-lab-border)]">+ Exclusivo</button>
                        <button onclick="bulkAction('remove_exclusivo')" class="block w-full text-left px-4 py-2 font-mono text-[10px] uppercase tracking-widest text-red-600 hover:bg-gray-50 border-b border-[var(--color-lab-border)]">- Exclusivo</button>
                        <button onclick="bulkAction('set_em_breve')" class="block w-full text-left px-4 py-2 font-mono text-[10px] uppercase tracking-widest text-black hover:bg-gray-50 border-b border-[var(--color-lab-border)]">+ Em Breve</button>
                        <button onclick="bulkAction('remove_em_breve')" class="block w-full text-left px-4 py-2 font-mono text-[10px] uppercase tracking-widest text-red-600 hover:bg-gray-50 border-b border-[var(--color-lab-border)]">- Em Breve</button>
                    </div>
                </div>
            </div>

            {{-- Status Actions --}}
            <button onclick="bulkAction('ativar')"
                    class="h-7 px-3 font-mono text-[10px] uppercase tracking-widest
                           border border-[var(--color-lab-border)] bg-white
                           hover:bg-black hover:text-white hover:border-black transition-colors">
                Ativar
            </button>
            <button onclick="bulkAction('desativar')"
                    class="h-7 px-3 font-mono text-[10px] uppercase tracking-widest
                           border border-[var(--color-lab-border)] bg-white
                           hover:bg-black hover:text-white hover:border-black transition-colors">
                Desativar
            </button>
            
            {{-- Delete & Clean --}}
            <button onclick="bulkAction('delete')"
                    class="h-7 px-3 font-mono text-[10px] uppercase tracking-widest
                           border border-red-300 text-red-500 bg-white
                           hover:bg-red-500 hover:text-white hover:border-red-500 transition-colors ml-1">
                Excluir
            </button>
            <button onclick="exportarSelecionados()"
                    class="h-7 px-3 font-mono text-[10px] uppercase tracking-widest
                           border border-gray-400 text-gray-700 bg-white
                           hover:bg-gray-800 hover:text-white hover:border-gray-800 transition-colors ml-1">
                &#x2193; Exportar XLSX
            </button>
            <button onclick="limparSelecao()"
                    class="h-7 px-3 font-mono text-[10px] uppercase tracking-widest
                           text-[var(--color-lab-muted)] hover:text-black transition-colors">
                &#x2715; Limpar
            </button>
        </div>
        </div>
    </div>

    <!-- Lista de Produtos -->
    @php
        $containerClass = $viewMode === 'lista'
            ? 'border-t border-[var(--color-lab-border)] bg-white overflow-hidden'
            : 'grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-2 xl:grid-cols-3 2xl:grid-cols-4 gap-4 lg:gap-6';
        $partial = $viewMode === 'lista'
            ? 'admin.includes.produtos-lista-linhas'
            : 'admin.includes.produtos-lista';
    @endphp
    <div id="produtosContainer" class="{{ $containerClass }}">
        @include($partial, ['produtos' => $produtos])
    </div>

    <!-- Paginacao -->
    <div id="paginacaoContainer">
        @include('admin.includes.paginacao', [
            'produtos'    => $produtos,
            'perPage'     => $perPage,
            'pesquisa'    => request('pesquisa'),
            'categoriaId' => request('categoria_id'),
            'categorias'  => $categorias,
        ])
    </div>
</div>

@include('admin.includes.modal-produto')
<!-- Modal de Confirmacao de Exclusao -->
<div id="modalConfirmacaoExclusao" class="fixed inset-0 bg-black/40 backdrop-blur-sm hidden z-50">
    <div class="flex items-center justify-center min-h-screen p-2 sm:p-4">
        <div class="bg-white border border-[var(--color-lab-border)] w-full max-w-md max-h-[92vh] overflow-y-auto" onclick="event.stopPropagation()">
            <div class="p-6">
                <div class="flex items-center justify-center mb-4">
                    <div class="w-10 h-10 border border-black flex items-center justify-center">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m21.73 18-8-14a2 2 0 0 0-3.48 0l-8 14A2 2 0 0 0 4 21h16a2 2 0 0 0 1.73-3Z"/><path d="M12 9v4"/><path d="M12 17h.01"/></svg>
                    </div>
                </div>
                <div class="text-center">
                    <h3 class="font-mono text-sm font-bold uppercase tracking-widest text-black mb-2">Confirmar Exclusao</h3>
                    <p class="font-mono text-xs text-[var(--color-lab-muted)] mb-6">Tem certeza que deseja excluir este produto? Esta acao nao pode ser desfeita.</p>
                    <div class="flex flex-col sm:flex-row space-y-2 sm:space-y-0 sm:space-x-3">
                        <button onclick="fecharModalConfirmacao()" class="flex-1 px-5 py-2.5 text-xs font-bold tracking-widest uppercase border border-[var(--color-lab-border)] text-black hover:bg-gray-50 transition-colors">
                            Cancelar
                        </button>
                        <button id="confirmarExclusao" class="flex-1 bg-black text-white px-5 py-2.5 text-xs font-bold tracking-widest uppercase hover:bg-gray-800 transition-colors">
                            Excluir
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal de Confirmacao de Destaque -->
<div id="modalConfirmacaoDestaque" class="fixed inset-0 bg-black/40 backdrop-blur-sm hidden z-50">
    <div class="flex items-center justify-center min-h-screen p-2 sm:p-4">
        <div class="bg-white border border-[var(--color-lab-border)] w-full max-w-md max-h-[92vh] overflow-y-auto" onclick="event.stopPropagation()">
            <div class="p-6">
                <div class="flex items-center justify-center mb-4">
                    <div class="w-10 h-10 border border-black flex items-center justify-center">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>
                    </div>
                </div>
                <div class="text-center">
                    <h3 class="font-mono text-sm font-bold uppercase tracking-widest text-black mb-2" id="tituloDestaque">Confirmar Alteracao</h3>
                    <p class="font-mono text-xs text-[var(--color-lab-muted)] mb-6" id="mensagemDestaque">Tem certeza que deseja alterar o status de destaque deste produto?</p>
                    <div class="flex flex-col sm:flex-row space-y-2 sm:space-y-0 sm:space-x-3">
                        <button onclick="fecharModalConfirmacao()" class="flex-1 px-5 py-2.5 text-xs font-bold tracking-widest uppercase border border-[var(--color-lab-border)] text-black hover:bg-gray-50 transition-colors">
                            Cancelar
                        </button>
                        <button id="confirmarDestaque" class="flex-1 bg-black text-white px-5 py-2.5 text-xs font-bold tracking-widest uppercase hover:bg-gray-800 transition-colors">
                            Confirmar
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal de Confirmacao de Status -->
<div id="modalConfirmacaoStatus" class="fixed inset-0 bg-black/40 backdrop-blur-sm hidden z-50">
    <div class="flex items-center justify-center min-h-screen p-2 sm:p-4">
        <div class="bg-white border border-[var(--color-lab-border)] w-full max-w-md max-h-[92vh] overflow-y-auto" onclick="event.stopPropagation()">
            <div class="p-6">
                <div class="flex items-center justify-center mb-4">
                    <div class="w-10 h-10 border border-black flex items-center justify-center">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 20V6a2 2 0 0 0-2-2H8a2 2 0 0 0-2 2v14"/><path d="M2 20h20"/><path d="M14 12v.01"/></svg>
                    </div>
                </div>
                <div class="text-center">
                    <h3 class="font-mono text-sm font-bold uppercase tracking-widest text-black mb-2" id="tituloStatus">Confirmar Alteracao</h3>
                    <p class="font-mono text-xs text-[var(--color-lab-muted)] mb-6" id="mensagemStatus">Tem certeza que deseja alterar o status deste produto?</p>
                    <div class="flex flex-col sm:flex-row space-y-2 sm:space-y-0 sm:space-x-3">
                        <button onclick="fecharModalConfirmacao()" class="flex-1 px-5 py-2.5 text-xs font-bold tracking-widest uppercase border border-[var(--color-lab-border)] text-black hover:bg-gray-50 transition-colors">
                            Cancelar
                        </button>
                        <button id="confirmarStatus" class="flex-1 bg-black text-white px-5 py-2.5 text-xs font-bold tracking-widest uppercase hover:bg-gray-800 transition-colors">
                            Confirmar
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal de Confirmacao de Remocao de Imagem -->
<div id="modalConfirmacaoRemoverImagem" class="fixed inset-0 bg-black/40 backdrop-blur-sm hidden z-[60]">
    <div class="flex items-center justify-center min-h-screen p-2 sm:p-4">
        <div class="bg-white border border-[var(--color-lab-border)] w-full max-w-md max-h-[92vh] overflow-y-auto" onclick="event.stopPropagation()">
            <div class="p-6">
                <div class="flex items-center justify-center mb-4">
                    <div class="w-10 h-10 border border-black flex items-center justify-center">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/><path d="M10 11v6"/><path d="M14 11v6"/><path d="M9 6V4a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v2"/></svg>
                    </div>
                </div>
                <div class="text-center">
                    <h3 class="font-mono text-sm font-bold uppercase tracking-widest text-black mb-2">Remover Imagem</h3>
                    <p class="font-mono text-xs text-[var(--color-lab-muted)] mb-6">Tem certeza que deseja remover esta imagem? Esta acao nao pode ser desfeita.</p>
                    <div class="flex flex-col sm:flex-row space-y-2 sm:space-y-0 sm:space-x-3">
                        <button onclick="fecharModalRemoverImagem()" class="flex-1 px-5 py-2.5 text-xs font-bold tracking-widest uppercase border border-[var(--color-lab-border)] text-black hover:bg-gray-50 transition-colors">
                            Cancelar
                        </button>
                        <button id="confirmarRemoverImagem" class="flex-1 bg-black text-white px-5 py-2.5 text-xs font-bold tracking-widest uppercase hover:bg-gray-800 transition-colors">
                            Remover
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@endsection

<script>
document.addEventListener('DOMContentLoaded', function() {
    const pesquisaInput = document.getElementById('pesquisa');
    const statusSelect = document.getElementById('status');
    const categoriaSelect = document.getElementById('categoria_id_filtro');
    const destaqueSelect = document.getElementById('destaque');
    const loadingIndicator = document.getElementById('loadingIndicator');
    const produtosContainer = document.getElementById('produtosContainer');
    let searchTimeout;
    let isSearching = false;
    let currentViewMode = '{{ $viewMode }}';

    // Funcao para realizar a pesquisa via AJAX
    window.realizarPesquisa = realizarPesquisa;
    function realizarPesquisa() {
        if (isSearching) return;

        isSearching = true;
        loadingIndicator.classList.remove('hidden');

        // Coletar todos os filtros
        const termo = pesquisaInput.value.trim();
        const status = statusSelect.value;
        const categoriaId = categoriaSelect.value;
        const destaque = destaqueSelect.value;
        const perPage = document.getElementById('per_page').value;

        // Fazer requisicao AJAX
        const url = new URL('{{ route("admin.produtos.search") }}', window.location.origin);
        if (termo) url.searchParams.set('pesquisa', termo);
        if (status) url.searchParams.set('status', status);
        if (categoriaId) url.searchParams.set('categoria_id', categoriaId);
        if (destaque) url.searchParams.set('destaque', destaque);
        url.searchParams.set('per_page', perPage);
        url.searchParams.set('view_mode', currentViewMode);

        fetch(url.toString(), {
            method: 'GET',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            }
        })
        .then(response => response.json())
        .then(data => {
            // Atualizar classe do container conforme view_mode
            const isLista = data.view_mode === 'lista';
            produtosContainer.className = isLista
                ? 'border-t border-[var(--color-lab-border)] bg-white'
                : 'grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-2 xl:grid-cols-3 2xl:grid-cols-4 gap-4 lg:gap-6';

            // Atualizar o container com os novos produtos
            produtosContainer.innerHTML = data.html;

            // Atualizar paginacao
            const paginacaoContainer = document.getElementById('paginacaoContainer');
            if (paginacaoContainer) {
                paginacaoContainer.innerHTML = data.paginacao_html;
            }

            // Limpar seleção após refresh da lista
            if (typeof limparSelecao === 'function') limparSelecao();

            // Atualizar contagem de resultados
            const totalEl = document.getElementById('resultTotal');
            if (totalEl) {
                totalEl.textContent = data.total + (data.total === 1 ? ' produto' : ' produtos');
            }

            // Atualizar URL sem recarregar a pagina
            const currentUrl = new URL(window.location);
            if (termo) {
                currentUrl.searchParams.set('pesquisa', termo);
            } else {
                currentUrl.searchParams.delete('pesquisa');
            }
            if (status) {
                currentUrl.searchParams.set('status', status);
            } else {
                currentUrl.searchParams.delete('status');
            }
            if (categoriaId) {
                currentUrl.searchParams.set('categoria_id', categoriaId);
            } else {
                currentUrl.searchParams.delete('categoria_id');
            }
            if (destaque) {
                currentUrl.searchParams.set('destaque', destaque);
            } else {
                currentUrl.searchParams.delete('destaque');
            }
            currentUrl.searchParams.set('per_page', perPage);
            currentUrl.searchParams.set('view_mode', currentViewMode);
            window.history.pushState({}, '', currentUrl);
        })
        .catch(error => {
            console.error('Erro na pesquisa:', error);
            // Em caso de erro, recarregar a pagina
            window.location.reload();
        })
        .finally(() => {
            loadingIndicator.classList.add('hidden');
            isSearching = false;
        });
    }

    // Event listener para o campo de pesquisa
    pesquisaInput.addEventListener('input', function() {
        clearTimeout(searchTimeout);
        loadingIndicator.classList.remove('hidden');
        searchTimeout = setTimeout(function() {
            realizarPesquisa();
        }, 300);
    });

    // Event listeners para os filtros de status e destaque
    statusSelect.addEventListener('change', function() {
        clearTimeout(searchTimeout);
        loadingIndicator.classList.remove('hidden');
        searchTimeout = setTimeout(function() {
            realizarPesquisa();
        }, 300);
    });

    categoriaSelect.addEventListener('change', function() {
        clearTimeout(searchTimeout);
        loadingIndicator.classList.remove('hidden');
        searchTimeout = setTimeout(function() {
            realizarPesquisa();
        }, 300);
    });

    destaqueSelect.addEventListener('change', function() {
        clearTimeout(searchTimeout);
        loadingIndicator.classList.remove('hidden');
        searchTimeout = setTimeout(function() {
            realizarPesquisa();
        }, 300);
    });

    // Per-page select
    document.getElementById('per_page').addEventListener('change', function() {
        clearTimeout(searchTimeout);
        realizarPesquisa();
    });

    // Event listeners para os botoes de view toggle
    document.querySelectorAll('.view-toggle-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            currentViewMode = this.dataset.view;

            // Atualiza visual dos botoes
            document.querySelectorAll('.view-toggle-btn').forEach(b => {
                const isActive = b.dataset.view === currentViewMode;
                b.classList.toggle('bg-black', isActive);
                b.classList.toggle('text-white', isActive);
                b.classList.toggle('bg-white', !isActive);
                b.classList.toggle('text-[var(--color-lab-muted)]', !isActive);
            });

            realizarPesquisa();
        });
    });

    // Event listener para Enter (pesquisa imediata)
    pesquisaInput.addEventListener('keypress', function(e) {
        if (e.key === 'Enter') {
            e.preventDefault();
            clearTimeout(searchTimeout);
            realizarPesquisa();
        }
    });

    // Event listener para Escape (limpar pesquisa)
    pesquisaInput.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            this.value = '';
            statusSelect.value = '';
            categoriaSelect.value = '';
            destaqueSelect.value = '';
            clearTimeout(searchTimeout);
            realizarPesquisa();
        }
    });
});
</script>

<script>
// === SISTEMA DE SELEÇÃO EM MASSA ===
let selectedIds = new Set();

// Event delegation no container (sobrevive ao AJAX innerHTML replace)
document.addEventListener('DOMContentLoaded', function () {
    document.getElementById('produtosContainer').addEventListener('change', function (e) {
        if (!e.target.classList.contains('produto-checkbox')) return;
        const id = e.target.dataset.id;
        if (e.target.checked) selectedIds.add(id);
        else selectedIds.delete(id);
        atualizarEstadoSelecao();
    });

    document.getElementById('selectAll').addEventListener('change', function () {
        const checkboxes = document.querySelectorAll('.produto-checkbox');
        checkboxes.forEach(cb => {
            cb.checked = this.checked;
            if (this.checked) selectedIds.add(cb.dataset.id);
            else selectedIds.delete(cb.dataset.id);
        });
        atualizarEstadoSelecao();
    });
});

function atualizarEstadoSelecao() {
    const count = selectedIds.size;
    const total = document.querySelectorAll('.produto-checkbox').length;

    document.getElementById('selectionCount').textContent =
        count + (count === 1 ? ' selecionado' : ' selecionados');

    const bar = document.getElementById('selectionBar');
    if (count > 0) {
        bar.classList.remove('hidden');
        bar.classList.add('flex');
    } else {
        bar.classList.add('hidden');
        bar.classList.remove('flex');
    }

    const sa = document.getElementById('selectAll');
    sa.indeterminate = count > 0 && count < total;
    sa.checked = count === total && total > 0;
}

function limparSelecao() {
    selectedIds.clear();
    document.querySelectorAll('.produto-checkbox').forEach(cb => cb.checked = false);
    const sa = document.getElementById('selectAll');
    sa.checked = false;
    sa.indeterminate = false;
    atualizarEstadoSelecao();
}

async function bulkAction(action) {
    if (selectedIds.size === 0) return;

    if (action === 'delete') {
        const n = selectedIds.size;
        if (!confirm('Excluir ' + n + ' produto' + (n > 1 ? 's' : '') + ' permanentemente? Esta ação não pode ser desfeita.')) return;
    }

    const ids = Array.from(selectedIds);
    try {
        const res = await fetch('{{ route("admin.produtos.bulk") }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify({ action, ids })
        });
        const data = await res.json();
        if (data.success) {
            limparSelecao();
            if (typeof window.realizarPesquisa === 'function') window.realizarPesquisa();
        } else {
            alert(data.error || 'Erro ao executar a ação.');
        }
    } catch (err) {
        console.error(err);
        alert('Erro de comunicação com o servidor.');
    }
}

function exportarSelecionados() {
    const ids = Array.from(selectedIds);
    if (ids.length === 0) return;

    const form = document.createElement('form');
    form.method = 'POST';
    form.action = '{{ route("admin.produtos.exportar") }}';

    const csrf = document.createElement('input');
    csrf.type = 'hidden';
    csrf.name = '_token';
    csrf.value = document.querySelector('meta[name="csrf-token"]').content;
    form.appendChild(csrf);

    ids.forEach(id => {
        const input = document.createElement('input');
        input.type = 'hidden';
        input.name = 'ids[]';
        input.value = id;
        form.appendChild(input);
    });

    document.body.appendChild(form);
    form.submit();
    document.body.removeChild(form);
}
</script>
