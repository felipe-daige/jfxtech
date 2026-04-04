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
    <div class="border border-[var(--color-lab-border)] bg-white p-6">
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
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
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
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3
                py-3 border-b border-[var(--color-lab-border)]">

        {{-- Contador + Select-all --}}
        <div class="flex items-center gap-3">
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
        <div class="flex items-center">

            {{-- Select per-page --}}
            <label class="font-mono text-[10px] uppercase tracking-widest text-[var(--color-lab-muted)] mr-2 hidden sm:inline"
                   for="per_page">Por página</label>
            <select id="per_page"
                    class="h-9 px-2 border border-[var(--color-lab-border)] bg-white font-mono text-[10px] uppercase tracking-widest text-black focus:outline-none focus:ring-1 focus:ring-black">
                @foreach([12, 24, 48, 96] as $opt)
                    <option value="{{ $opt }}" {{ $perPage == $opt ? 'selected' : '' }}>{{ $opt }}</option>
                @endforeach
            </select>

            {{-- Separador vertical --}}
            <div class="w-px h-5 bg-[var(--color-lab-border)] mx-4" aria-hidden="true"></div>

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
    <div id="selectionBar" class="hidden flex-col sm:flex-row items-center sm:justify-between gap-3
         py-2.5 px-3 mt-3 border border-[var(--color-lab-border)] bg-[var(--color-lab-bg)]">
        <span id="selectionCount"
              class="font-mono text-[10px] uppercase tracking-widest text-black whitespace-nowrap">
            0 selecionados
        </span>
        <div class="flex flex-wrap items-center gap-2 justify-center sm:justify-end">
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

    <!-- Lista de Produtos -->
    @php
        $containerClass = $viewMode === 'lista'
            ? 'border-t border-[var(--color-lab-border)] bg-white'
            : 'grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4 lg:gap-6';
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

<!-- Modal de Produto -->
<div id="modalProduto" class="fixed inset-0 bg-black/40 backdrop-blur-sm hidden z-50">
    <div class="flex items-center justify-center min-h-screen p-2 sm:p-4">
        <div class="bg-white border border-[var(--color-lab-border)] w-full max-w-2xl max-h-[95vh] sm:max-h-[90vh] overflow-y-auto" onclick="event.stopPropagation()">
            <div class="p-6 border-b border-[var(--color-lab-border)]">
                <div class="flex justify-between items-center">
                    <h3 class="font-mono text-sm font-bold uppercase tracking-widest text-black" id="modalTitulo">Novo Produto</h3>
                    <button onclick="fecharModalProduto()" class="text-[var(--color-lab-muted)] hover:text-black p-1">
                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 6 6 18"/><path d="m6 6 12 12"/></svg>
                    </button>
                </div>
            </div>

            <form id="formProduto" method="POST" enctype="multipart/form-data">
                @csrf
                <div id="formMethod" style="display: none;"></div>
                <input type="hidden" id="produtoId" name="produto_id" value="">

                <div class="px-6 pt-4">
                    {{-- Tab navigation --}}
                    <div class="flex border-b border-gray-200 mb-6" id="produto-modal-tabs">
                        <button type="button"
                                onclick="switchProdutoTab('dados')"
                                class="produto-tab-btn px-4 py-2 text-sm font-mono uppercase tracking-widest border-b-2 -mb-px transition-colors border-black text-black"
                                data-tab="dados">
                            DADOS
                        </button>
                        <button type="button"
                                onclick="switchProdutoTab('imagens')"
                                class="produto-tab-btn px-4 py-2 text-sm font-mono uppercase tracking-widest border-b-2 -mb-px transition-colors border-transparent text-gray-400 hover:text-black"
                                data-tab="imagens">
                            IMAGENS
                        </button>
                        <button type="button"
                                onclick="switchProdutoTab('variantes')"
                                class="produto-tab-btn px-4 py-2 text-sm font-mono uppercase tracking-widest border-b-2 -mb-px transition-colors border-transparent text-gray-400 hover:text-black"
                                data-tab="variantes">
                            VARIANTES
                        </button>
                        <button type="button"
                                onclick="switchProdutoTab('specs')"
                                class="produto-tab-btn px-4 py-2 text-sm font-mono uppercase tracking-widest border-b-2 -mb-px transition-colors border-transparent text-gray-400 hover:text-black"
                                data-tab="specs">
                            SPECS
                        </button>
                    </div>
                </div>

                {{-- Tab panels --}}
                <div id="tab-dados" class="produto-tab-panel">
                <div id="formFields" class="px-6 pb-6 space-y-5">
                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
                        <div>
                            <label class="font-mono text-[10px] uppercase tracking-widest text-[var(--color-lab-muted)] block mb-2">Nome do Produto</label>
                            <input type="text" name="nome" id="nome" class="w-full px-4 py-3 border border-[var(--color-lab-border)] text-sm font-mono focus:outline-none focus:border-black" required>
                        </div>
                        <div>
                            <label class="font-mono text-[10px] uppercase tracking-widest text-[var(--color-lab-muted)] block mb-2">Categoria</label>
                            <select name="categoria_id" id="categoria_id" class="w-full px-4 py-3 border border-[var(--color-lab-border)] text-sm font-mono focus:outline-none focus:border-black bg-white" required>
                                <option value="">Selecione uma categoria</option>
                                @foreach($categorias as $categoria)
                                    <option value="{{ $categoria->id }}">{{ $categoria->nome }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div>
                        <label class="font-mono text-[10px] uppercase tracking-widest text-[var(--color-lab-muted)] block mb-2">Descricao</label>
                        <p class="text-xs text-[var(--color-lab-muted)] leading-6 mb-3">
                            Use HTML simples para destacar a leitura: <code>&lt;p&gt;</code>, <code>&lt;strong&gt;</code>, <code>&lt;ul&gt;</code> e <code>&lt;li&gt;</code>.
                            Exemplo: um parágrafo curto, um bloco com <strong>Principais destaques</strong> e uma lista de benefícios.
                        </p>
                        <textarea name="descricao" id="descricao" rows="12" class="w-full px-4 py-3 border border-[var(--color-lab-border)] text-sm font-mono focus:outline-none focus:border-black" required></textarea>
                        <div class="admin-description-preview mt-4 border border-[var(--color-lab-border)] bg-[var(--color-lab-bg)]">
                            <div class="border-b border-[var(--color-lab-border)] px-4 py-3 flex items-center justify-between gap-3">
                                <div>
                                    <p class="font-mono text-[10px] uppercase tracking-widest text-[var(--color-lab-muted)]">Prévia da descrição</p>
                                    <p class="text-xs text-[var(--color-lab-muted)] mt-1">A prévia renderiza o HTML permitido antes de salvar.</p>
                                </div>
                                <span id="descricaoPreviewStatus" class="font-mono text-[10px] uppercase tracking-widest text-[var(--color-lab-muted)]">Aguardando texto</span>
                            </div>
                            <div id="descricaoPreview" class="p-4 sm:p-5">
                                <div class="product-description-content text-sm text-gray-500">
                                    <p>Digite a descrição usando HTML simples para ver a renderização aqui.</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
                        <div>
                            <label class="font-mono text-[10px] uppercase tracking-widest text-[var(--color-lab-muted)] block mb-2">Preco (R$)</label>
                            <input type="text" name="preco" id="preco" class="w-full px-4 py-3 border border-[var(--color-lab-border)] text-sm font-mono focus:outline-none focus:border-black" placeholder="0,00" required>
                        </div>
                        <div>
                            <label class="font-mono text-[10px] uppercase tracking-widest text-[var(--color-lab-muted)] block mb-2">Custo de Compra (R$)</label>
                            <input type="text" name="custo_compra" id="custo_compra" class="w-full px-4 py-3 border border-[var(--color-lab-border)] text-sm font-mono focus:outline-none focus:border-black" placeholder="0,00">
                        </div>
                    </div>

                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
                        <div>
                            <label class="font-mono text-[10px] uppercase tracking-widest text-[var(--color-lab-muted)] block mb-2">Estoque</label>
                            <input type="number" name="estoque" id="estoque" min="0" class="w-full px-4 py-3 border border-[var(--color-lab-border)] text-sm font-mono focus:outline-none focus:border-black" required>
                        </div>
                        <div id="resumoFinanceiroProduto" class="border border-[var(--color-lab-border)] bg-[var(--color-lab-bg)] p-4">
                            <div class="font-mono text-sm text-black space-y-1">
                                <div class="flex justify-between">
                                    <span class="text-[var(--color-lab-muted)]">Venda efetiva:</span>
                                    <span id="precoVendaResumoDisplay">R$ 0,00</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-[var(--color-lab-muted)]">Custo:</span>
                                    <span id="custoCompraDisplay">R$ 0,00</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-[var(--color-lab-muted)]">Lucro unitario:</span>
                                    <span id="lucroUnitarioDisplay">R$ 0,00</span>
                                </div>
                                <div class="flex justify-between font-bold text-base border-t border-[var(--color-lab-border)] pt-2 mt-2">
                                    <span>Margem bruta:</span>
                                    <span id="margemBrutaDisplay">0,00%</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Secao de Promocao -->
                    <div>
                        <div class="flex items-center mb-4">
                            <input type="hidden" name="em_promocao" value="0">
                            <input type="checkbox" name="em_promocao" id="em_promocao" value="1" class="h-4 w-4 border-[var(--color-lab-border)] text-black focus:ring-black">
                            <label for="em_promocao" class="ml-2 font-mono text-xs text-black">Produto em promocao</label>
                        </div>

                        <div id="camposPromocao" class="hidden">
                            <div class="grid grid-cols-1 gap-4">
                                <div>
                                    <label class="font-mono text-[10px] uppercase tracking-widest text-[var(--color-lab-muted)] block mb-2">Desconto (%)</label>
                                    <input type="number" name="desconto_percentual" id="desconto_percentual" min="0" max="100" step="0.01" class="w-full px-4 py-3 border border-[var(--color-lab-border)] text-sm font-mono focus:outline-none focus:border-black" placeholder="0">
                                </div>
                            </div>
                        </div>

                        <div id="resumoPromocao" class="mt-3 p-4 border border-[var(--color-lab-border)] bg-[var(--color-lab-bg)] hidden">
                            <div class="font-mono text-sm text-black space-y-1">
                                <div class="flex justify-between">
                                    <span class="text-[var(--color-lab-muted)]">Preco original:</span>
                                    <span id="precoOriginalDisplay">R$ 0,00</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-[var(--color-lab-muted)]">Desconto:</span>
                                    <span id="descontoDisplay">R$ 0,00</span>
                                </div>
                                <div class="flex justify-between font-bold text-base border-t border-[var(--color-lab-border)] pt-2 mt-2">
                                    <span>Preco com desconto:</span>
                                    <span id="precoFinalDisplay">R$ 0,00</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Secao de Destaque e Tags -->
                    <div>
                        <div class="flex items-center mb-4">
                            <input type="hidden" name="destaque" value="0">
                            <input type="checkbox" name="destaque" id="destaque" value="1" class="h-4 w-4 border-[var(--color-lab-border)] text-black focus:ring-black">
                            <label for="destaque" class="ml-2 font-mono text-xs text-black">Produto em destaque (maximo 3)</label>
                        </div>

                        <label class="font-mono text-[10px] uppercase tracking-widest text-[var(--color-lab-muted)] block mb-2">Tags do Produto (Máx. 2)</label>
                        <div class="space-y-2 mt-2" id="tagsSelectionGroup">
                            <div class="flex items-center">
                                <input type="checkbox" name="tags[]" id="tag_exclusivo" value="Exclusivo" class="tag-checkbox h-4 w-4 border-[var(--color-lab-border)] text-black focus:ring-black">
                                <label for="tag_exclusivo" class="ml-2 font-mono text-xs text-black">Exclusivo</label>
                            </div>

                            <div class="flex items-center">
                                <input type="checkbox" name="tags[]" id="tag_em_breve" value="Em Breve" class="tag-checkbox h-4 w-4 border-[var(--color-lab-border)] text-black focus:ring-black">
                                <label for="tag_em_breve" class="ml-2 font-mono text-xs text-black">Em Breve</label>
                            </div>

                            <div class="flex items-center">
                                <input type="checkbox" name="tags[]" id="tag_lancamento" value="Lançamento" class="tag-checkbox h-4 w-4 border-[var(--color-lab-border)] text-black focus:ring-black">
                                <label for="tag_lancamento" class="ml-2 font-mono text-xs text-black">Lançamento</label>
                            </div>

                            <div class="flex items-center">
                                <input type="checkbox" name="tags[]" id="tag_oferta" value="Oferta Especial" class="tag-checkbox h-4 w-4 border-[var(--color-lab-border)] text-black focus:ring-black">
                                <label for="tag_oferta" class="ml-2 font-mono text-xs text-black">Oferta Especial</label>
                            </div>
                        </div>
                        <p id="tags_warning" class="hidden mt-1 font-mono text-[10px] text-red-500">Você só pode selecionar até 2 tags.</p>
                    </div>

                </div>
                </div>{{-- end #tab-dados --}}

                <div id="tab-imagens" class="produto-tab-panel hidden">
                <div class="px-6 pb-6 space-y-5">
                    <div>
                        <label class="font-mono text-[10px] uppercase tracking-widest text-[var(--color-lab-muted)] block mb-2">Imagens</label>
                        <input type="file" name="imagens[]" id="imagens" multiple accept="image/*" class="w-full px-4 py-3 border border-[var(--color-lab-border)] text-sm font-mono focus:outline-none focus:border-black file:mr-4 file:py-1 file:px-3 file:border-0 file:text-xs file:font-mono file:font-bold file:uppercase file:tracking-widest file:bg-black file:text-white">
                        <p class="font-mono text-[10px] text-[var(--color-lab-muted)] mt-1">Selecione uma ou mais imagens (maximo 2MB cada)</p>
                        <div id="novasImagensPreview" class="hidden mt-3 grid grid-cols-3 gap-2"></div>
                    </div>
                </div>
                </div>{{-- end #tab-imagens --}}

                <div id="tab-variantes" class="produto-tab-panel hidden">
                <div class="px-6 pb-6">
                    <div id="variantes-loading" class="text-center py-8 text-gray-400 font-mono text-sm">CARREGANDO...</div>
                    <div id="variantes-content" class="hidden">

                        {{-- Estoque compartilhado --}}
                        <div class="flex items-center gap-3 mb-6 p-4 border border-gray-200">
                            <input type="checkbox" id="estoque-compartilhado" class="w-4 h-4">
                            <label for="estoque-compartilhado" class="text-sm font-mono uppercase tracking-widest">
                                Compartilhar estoque entre variantes
                            </label>
                        </div>

                        {{-- Grupos e valores --}}
                        <div id="grupos-container" class="space-y-4 mb-6"></div>
                        <button type="button" id="add-grupo-btn"
                                onclick="addGrupoOpcoes()"
                                class="text-sm font-mono uppercase tracking-widest border border-dashed border-gray-400 px-4 py-2 hover:border-black transition-colors w-full mb-6">
                            + ADICIONAR GRUPO DE OPÇÕES
                        </button>

                        {{-- Tabela de variantes --}}
                        <div id="variantes-tabela-container" class="hidden">
                            <h4 class="font-mono text-xs uppercase tracking-widest text-gray-500 mb-3">VARIANTES GERADAS</h4>
                            <div id="variantes-tabela" class="space-y-2"></div>
                        </div>

                        {{-- Botões salvar + recarregar --}}
                        <div class="mt-4">
                            <button type="button" id="salvar-btn"
                                    onclick="salvarTudo(window.currentProdutoId)"
                                    class="w-full bg-black text-white font-mono uppercase tracking-widest text-sm px-6 py-3 hover:bg-gray-800 transition-colors">
                                SALVAR
                            </button>
                        </div>
                    </div>
                </div>
                </div>{{-- end #tab-variantes --}}

                <div id="tab-specs" class="produto-tab-panel hidden">
                <div class="px-6 pb-6 space-y-5">
                    <p class="font-mono text-[10px] uppercase tracking-widest text-[var(--color-lab-muted)]">Especificações técnicas — deixe em branco os campos não aplicáveis</p>
                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
                        <div>
                            <label class="font-mono text-[10px] uppercase tracking-widest text-[var(--color-lab-muted)] block mb-2">Sensor</label>
                            <input type="text" name="specs[sensor]" id="spec_sensor" class="w-full px-4 py-3 border border-[var(--color-lab-border)] text-sm font-mono focus:outline-none focus:border-black" placeholder="ex: Pulsar XS-1 Optical">
                        </div>
                        <div>
                            <label class="font-mono text-[10px] uppercase tracking-widest text-[var(--color-lab-muted)] block mb-2">DPI Máximo</label>
                            <input type="text" name="specs[dpi_maximo]" id="spec_dpi_maximo" class="w-full px-4 py-3 border border-[var(--color-lab-border)] text-sm font-mono focus:outline-none focus:border-black" placeholder="ex: 32.000 DPI">
                        </div>
                        <div>
                            <label class="font-mono text-[10px] uppercase tracking-widest text-[var(--color-lab-muted)] block mb-2">Switches</label>
                            <input type="text" name="specs[switches]" id="spec_switches" class="w-full px-4 py-3 border border-[var(--color-lab-border)] text-sm font-mono focus:outline-none focus:border-black" placeholder="ex: Óptico Pulsar (100M cliques)">
                        </div>
                        <div>
                            <label class="font-mono text-[10px] uppercase tracking-widest text-[var(--color-lab-muted)] block mb-2">Peso</label>
                            <input type="text" name="specs[peso]" id="spec_peso" class="w-full px-4 py-3 border border-[var(--color-lab-border)] text-sm font-mono focus:outline-none focus:border-black" placeholder="ex: 43 g">
                        </div>
                        <div>
                            <label class="font-mono text-[10px] uppercase tracking-widest text-[var(--color-lab-muted)] block mb-2">Conexão</label>
                            <input type="text" name="specs[conexao]" id="spec_conexao" class="w-full px-4 py-3 border border-[var(--color-lab-border)] text-sm font-mono focus:outline-none focus:border-black" placeholder="ex: 2,4 GHz Wireless + USB-C">
                        </div>
                        <div>
                            <label class="font-mono text-[10px] uppercase tracking-widest text-[var(--color-lab-muted)] block mb-2">Polling Rate</label>
                            <input type="text" name="specs[polling_rate]" id="spec_polling_rate" class="w-full px-4 py-3 border border-[var(--color-lab-border)] text-sm font-mono focus:outline-none focus:border-black" placeholder="ex: 1.000 Hz">
                        </div>
                        <div>
                            <label class="font-mono text-[10px] uppercase tracking-widest text-[var(--color-lab-muted)] block mb-2">Dimensões</label>
                            <input type="text" name="specs[dimensoes]" id="spec_dimensoes" class="w-full px-4 py-3 border border-[var(--color-lab-border)] text-sm font-mono focus:outline-none focus:border-black" placeholder="ex: 119,6 × 67,1 × 41 mm">
                        </div>
                        <div>
                            <label class="font-mono text-[10px] uppercase tracking-widest text-[var(--color-lab-muted)] block mb-2">Cabo</label>
                            <input type="text" name="specs[cabo]" id="spec_cabo" class="w-full px-4 py-3 border border-[var(--color-lab-border)] text-sm font-mono focus:outline-none focus:border-black" placeholder="ex: USB-C 1,8 m paracord">
                        </div>
                        <div>
                            <label class="font-mono text-[10px] uppercase tracking-widest text-[var(--color-lab-muted)] block mb-2">Iluminação</label>
                            <input type="text" name="specs[iluminacao]" id="spec_iluminacao" class="w-full px-4 py-3 border border-[var(--color-lab-border)] text-sm font-mono focus:outline-none focus:border-black" placeholder="ex: RGB / Sem RGB">
                        </div>
                        <div>
                            <label class="font-mono text-[10px] uppercase tracking-widest text-[var(--color-lab-muted)] block mb-2">Garantia</label>
                            <input type="text" name="specs[garantia]" id="spec_garantia" class="w-full px-4 py-3 border border-[var(--color-lab-border)] text-sm font-mono focus:outline-none focus:border-black" placeholder="ex: 1 ano">
                        </div>
                        <div>
                            <label class="font-mono text-[10px] uppercase tracking-widest text-[var(--color-lab-muted)] block mb-2">Layout</label>
                            <input type="text" name="specs[layout]" id="spec_layout" class="w-full px-4 py-3 border border-[var(--color-lab-border)] text-sm font-mono focus:outline-none focus:border-black" placeholder="ex: TKL, 60%, 65%, 75%">
                        </div>
                        <div>
                            <label class="font-mono text-[10px] uppercase tracking-widest text-[var(--color-lab-muted)] block mb-2">Superfície</label>
                            <input type="text" name="specs[superficie]" id="spec_superficie" class="w-full px-4 py-3 border border-[var(--color-lab-border)] text-sm font-mono focus:outline-none focus:border-black" placeholder="ex: Cloth — Alta Controle">
                        </div>
                        <div>
                            <label class="font-mono text-[10px] uppercase tracking-widest text-[var(--color-lab-muted)] block mb-2">Base</label>
                            <input type="text" name="specs[base]" id="spec_base" class="w-full px-4 py-3 border border-[var(--color-lab-border)] text-sm font-mono focus:outline-none focus:border-black" placeholder="ex: Borracha antiderrapante">
                        </div>
                        <div>
                            <label class="font-mono text-[10px] uppercase tracking-widest text-[var(--color-lab-muted)] block mb-2">Drivers</label>
                            <input type="text" name="specs[drivers]" id="spec_drivers" class="w-full px-4 py-3 border border-[var(--color-lab-border)] text-sm font-mono focus:outline-none focus:border-black" placeholder="ex: 40mm Neodymium">
                        </div>
                        <div>
                            <label class="font-mono text-[10px] uppercase tracking-widest text-[var(--color-lab-muted)] block mb-2">Frequência</label>
                            <input type="text" name="specs[frequencia]" id="spec_frequencia" class="w-full px-4 py-3 border border-[var(--color-lab-border)] text-sm font-mono focus:outline-none focus:border-black" placeholder="ex: 20 Hz – 20.000 Hz">
                        </div>
                        <div>
                            <label class="font-mono text-[10px] uppercase tracking-widest text-[var(--color-lab-muted)] block mb-2">Microfone</label>
                            <input type="text" name="specs[microfone]" id="spec_microfone" class="w-full px-4 py-3 border border-[var(--color-lab-border)] text-sm font-mono focus:outline-none focus:border-black" placeholder="ex: Retrátil bidirecional">
                        </div>
                        <div>
                            <label class="font-mono text-[10px] uppercase tracking-widest text-[var(--color-lab-muted)] block mb-2">Bateria</label>
                            <input type="text" name="specs[bateria]" id="spec_bateria" class="w-full px-4 py-3 border border-[var(--color-lab-border)] text-sm font-mono focus:outline-none focus:border-black" placeholder="ex: 22 horas">
                        </div>
                    </div>
                </div>
                </div>{{-- end #tab-specs --}}

                <div class="p-6 border-t border-[var(--color-lab-border)] flex flex-col sm:flex-row justify-end space-y-2 sm:space-y-0 sm:space-x-3">
                    <button type="button" onclick="fecharModalProduto()" class="w-full sm:w-auto px-5 py-2.5 text-xs font-bold tracking-widest uppercase border border-[var(--color-lab-border)] text-black hover:bg-gray-50 transition-colors">
                        Cancelar
                    </button>
                    <button type="submit" class="w-full sm:w-auto bg-black text-white px-5 py-2.5 text-xs font-bold tracking-widest uppercase hover:bg-gray-800 transition-colors">
                        Salvar Produto
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Lightbox de imagem -->
<div id="lightboxImagem" class="fixed inset-0 bg-black/80 hidden z-[60] flex items-center justify-center p-4" onclick="fecharLightbox()">
    <button onclick="fecharLightbox()" class="absolute top-4 right-4 text-white text-2xl font-mono leading-none hover:text-gray-300">✕</button>
    <img id="lightboxImg" src="" alt="" class="max-h-[90vh] max-w-[90vw] object-contain" onclick="event.stopPropagation()">
</div>

<!-- Modal de Confirmacao de Exclusao -->
<div id="modalConfirmacaoExclusao" class="fixed inset-0 bg-black/40 backdrop-blur-sm hidden z-50">
    <div class="flex items-center justify-center min-h-screen p-2 sm:p-4">
        <div class="bg-white border border-[var(--color-lab-border)] w-full max-w-md" onclick="event.stopPropagation()">
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
        <div class="bg-white border border-[var(--color-lab-border)] w-full max-w-md" onclick="event.stopPropagation()">
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
        <div class="bg-white border border-[var(--color-lab-border)] w-full max-w-md" onclick="event.stopPropagation()">
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
        <div class="bg-white border border-[var(--color-lab-border)] w-full max-w-md" onclick="event.stopPropagation()">
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
                : 'grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4 lg:gap-6';

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
