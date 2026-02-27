@extends('includes.header-admin')

@section('title', 'Gerenciar Produtos')

@section('content')
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
                    @if(request('pesquisa') || request('status') || request('destaque'))
                        <a href="{{ route('admin.produtos') }}" class="bg-white border border-[var(--color-lab-border)] text-black px-5 py-2.5 text-xs font-bold tracking-widest uppercase hover:bg-gray-50 transition-colors flex items-center justify-center space-x-2">
                            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 6 6 18"/><path d="m6 6 12 12"/></svg>
                            <span>Limpar Filtros</span>
                        </a>
                    @endif
                </div>
            </div>

            <!-- Filtros -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <!-- Filtro de Status -->
                <div>
                    <label for="status" class="font-mono text-[10px] uppercase tracking-widest text-[var(--color-lab-muted)] block mb-2">Status</label>
                    <select name="status" id="status" class="w-full px-4 py-3 border border-[var(--color-lab-border)] text-sm font-mono focus:outline-none focus:border-black bg-white">
                        <option value="">Todos os status</option>
                        <option value="ativo" {{ request('status') === 'ativo' ? 'selected' : '' }}>Apenas Ativos</option>
                        <option value="inativo" {{ request('status') === 'inativo' ? 'selected' : '' }}>Apenas Inativos</option>
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

    <!-- Lista de Produtos -->
    <div id="produtosContainer" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4 lg:gap-6">
        @include('admin.includes.produtos-lista', ['produtos' => $produtos])
    </div>

    <!-- Paginacao -->
    @if($produtos->hasPages())
    <div class="mt-6 border border-[var(--color-lab-border)] bg-white p-4">
        <!-- Informacoes de paginacao -->
        <div class="text-center font-mono text-xs text-[var(--color-lab-muted)] uppercase tracking-widest mb-4">
            Mostrando {{ $produtos->firstItem() ?? 0 }} a {{ $produtos->lastItem() ?? 0 }} de {{ $produtos->total() }} produtos
            @if(request('pesquisa'))
                para "{{ request('pesquisa') }}"
            @endif
        </div>

        <!-- Navegacao Centralizada -->
        <div class="flex justify-center items-center space-x-2">
            <!-- Botao Anterior -->
            @if($produtos->onFirstPage())
                <span class="px-4 py-2 font-mono text-xs uppercase tracking-widest text-gray-300 border border-[var(--color-lab-border)] cursor-not-allowed">
                    Anterior
                </span>
            @else
                <a href="{{ $produtos->previousPageUrl() }}" class="px-4 py-2 font-mono text-xs uppercase tracking-widest text-black border border-[var(--color-lab-border)] hover:border-black hover:bg-gray-50 transition-colors">
                    Anterior
                </a>
            @endif

            <!-- Numeros das paginas -->
            <div class="flex items-center space-x-1">
                @foreach($produtos->getUrlRange(1, $produtos->lastPage()) as $page => $url)
                    @if($page == $produtos->currentPage())
                        <span class="px-3 py-2 font-mono text-xs bg-black text-white">{{ $page }}</span>
                    @else
                        <a href="{{ $url }}" class="px-3 py-2 font-mono text-xs text-black border border-[var(--color-lab-border)] hover:border-black hover:bg-gray-50 transition-colors">{{ $page }}</a>
                    @endif
                @endforeach
            </div>

            <!-- Botao Proximo -->
            @if($produtos->hasMorePages())
                <a href="{{ $produtos->nextPageUrl() }}" class="px-4 py-2 font-mono text-xs uppercase tracking-widest text-black border border-[var(--color-lab-border)] hover:border-black hover:bg-gray-50 transition-colors">
                    Proximo
                </a>
            @else
                <span class="px-4 py-2 font-mono text-xs uppercase tracking-widest text-gray-300 border border-[var(--color-lab-border)] cursor-not-allowed">
                    Proximo
                </span>
            @endif
        </div>
    </div>
    @endif
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

                <div class="p-6 space-y-5">
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
                        <textarea name="descricao" id="descricao" rows="3" class="w-full px-4 py-3 border border-[var(--color-lab-border)] text-sm font-mono focus:outline-none focus:border-black" required></textarea>
                    </div>

                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
                        <div>
                            <label class="font-mono text-[10px] uppercase tracking-widest text-[var(--color-lab-muted)] block mb-2">Preco (R$)</label>
                            <input type="text" name="preco" id="preco" class="w-full px-4 py-3 border border-[var(--color-lab-border)] text-sm font-mono focus:outline-none focus:border-black" placeholder="0,00" required>
                        </div>
                        <div>
                            <label class="font-mono text-[10px] uppercase tracking-widest text-[var(--color-lab-muted)] block mb-2">Estoque</label>
                            <input type="number" name="estoque" id="estoque" min="0" class="w-full px-4 py-3 border border-[var(--color-lab-border)] text-sm font-mono focus:outline-none focus:border-black" required>
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

                    <!-- Secao de Destaque -->
                    <div>
                        <div class="flex items-center mb-2">
                            <input type="hidden" name="destaque" value="0">
                            <input type="checkbox" name="destaque" id="destaque" value="1" class="h-4 w-4 border-[var(--color-lab-border)] text-black focus:ring-black">
                            <label for="destaque" class="ml-2 font-mono text-xs text-black">Produto em destaque (maximo 3)</label>
                        </div>
                        <p class="font-mono text-[10px] text-[var(--color-lab-muted)] mb-4">Produtos em destaque aparecem na pagina inicial do site.</p>
                    </div>

                    <div>
                        <label class="font-mono text-[10px] uppercase tracking-widest text-[var(--color-lab-muted)] block mb-2">Imagens</label>
                        <input type="file" name="imagens[]" id="imagens" multiple accept="image/*" class="w-full px-4 py-3 border border-[var(--color-lab-border)] text-sm font-mono focus:outline-none focus:border-black file:mr-4 file:py-1 file:px-3 file:border-0 file:text-xs file:font-mono file:font-bold file:uppercase file:tracking-widest file:bg-black file:text-white">
                        <p class="font-mono text-[10px] text-[var(--color-lab-muted)] mt-1">Selecione uma ou mais imagens (maximo 2MB cada)</p>
                    </div>

                </div>

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

@endsection

<script>
document.addEventListener('DOMContentLoaded', function() {
    const pesquisaInput = document.getElementById('pesquisa');
    const statusSelect = document.getElementById('status');
    const destaqueSelect = document.getElementById('destaque');
    const loadingIndicator = document.getElementById('loadingIndicator');
    const produtosContainer = document.getElementById('produtosContainer');
    let searchTimeout;
    let isSearching = false;

    // Funcao para realizar a pesquisa via AJAX
    function realizarPesquisa() {
        if (isSearching) return;

        isSearching = true;
        loadingIndicator.classList.remove('hidden');

        // Coletar todos os filtros
        const termo = pesquisaInput.value.trim();
        const status = statusSelect.value;
        const destaque = destaqueSelect.value;

        // Fazer requisicao AJAX
        const url = new URL('{{ route("admin.produtos.search") }}', window.location.origin);
        if (termo) url.searchParams.set('pesquisa', termo);
        if (status) url.searchParams.set('status', status);
        if (destaque) url.searchParams.set('destaque', destaque);

        fetch(url.toString(), {
            method: 'GET',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            }
        })
        .then(response => response.json())
        .then(data => {
            // Atualizar o container com os novos produtos
            produtosContainer.innerHTML = data.html;

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
            if (destaque) {
                currentUrl.searchParams.set('destaque', destaque);
            } else {
                currentUrl.searchParams.delete('destaque');
            }
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
        // Limpar timeout anterior
        clearTimeout(searchTimeout);

        // Mostrar indicador de carregamento
        loadingIndicator.classList.remove('hidden');

        // Definir novo timeout para evitar muitas requisicoes
        searchTimeout = setTimeout(function() {
            realizarPesquisa();
        }, 300);
    });

    // Event listeners para os filtros
    statusSelect.addEventListener('change', function() {
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
            destaqueSelect.value = '';
            clearTimeout(searchTimeout);
            realizarPesquisa();
        }
    });
});
</script>
