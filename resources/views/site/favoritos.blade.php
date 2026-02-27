<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <link rel="icon" type="image/png" href="{{ asset('favicon.png') }}">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Meus Favoritos - JFXTECH</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <link rel="stylesheet" href="{{ asset('css/site-styles.css') }}">

    <!-- jQuery e jMask -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery.mask/1.14.16/jquery.mask.min.js"></script>
</head>
<body class="min-h-screen flex flex-col bg-[var(--color-lab-bg)] text-[var(--color-lab-ink)] antialiased">

    @include('includes.header')

    <main class="flex-grow">
        <!-- Favoritos Section -->
        <section class="py-16">
            <div class="container mx-auto px-4 lg:px-8">
                <div class="max-w-6xl mx-auto">

                    <!-- Header -->
                    <div class="mb-12">
                        <span class="font-mono text-[10px] uppercase tracking-widest text-gray-500">Colecao</span>
                        <h1 class="text-3xl font-bold uppercase tracking-wider text-black mt-1">Meus Favoritos</h1>
                        <p class="text-[var(--color-lab-muted)] text-sm mt-2">Produtos que voce salvou</p>
                    </div>

                    <!-- Alertas -->
                    @if(session('success'))
                        <div class="border border-black bg-white px-4 py-3 mb-6">
                            <div class="flex items-center">
                                <svg class="w-4 h-4 mr-2 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                <span class="text-sm font-mono">{{ session('success') }}</span>
                            </div>
                        </div>
                    @endif

                    @if(session('error'))
                        <div class="border border-red-500 bg-white px-4 py-3 mb-6">
                            <div class="flex items-center">
                                <svg class="w-4 h-4 mr-2 text-red-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/></svg>
                                <span class="text-sm font-mono">{{ session('error') }}</span>
                            </div>
                        </div>
                    @endif

                    <!-- Filtros e Acoes -->
                    <div class="bg-white border border-[var(--color-lab-border)] p-6 mb-8">
                        <div class="flex flex-col md:flex-row md:items-center md:justify-between space-y-4 md:space-y-0 md:space-x-6">
                            <div class="flex flex-col md:flex-row md:items-center space-y-4 md:space-y-0 md:space-x-4">
                                <div class="flex items-center space-x-3">
                                    <svg class="w-4 h-4 text-black" fill="none" stroke="currentColor" viewBox="0 0 24 24"><polygon points="22 3 2 3 10 12.46 10 19 14 21 14 12.46 22 3"/></svg>
                                    <span class="font-mono text-[10px] uppercase tracking-widest text-gray-500">Filtrar por:</span>
                                </div>

                                <select class="border border-[var(--color-lab-border)] px-4 py-3 text-sm font-mono focus:outline-none focus:border-black transition-colors">
                                    <option value="">Todas as categorias</option>
                                    <option value="acessorios">Acessorios</option>
                                    <option value="equipamentos">Equipamentos</option>
                                    <option value="pecas">Pecas</option>
                                    <option value="roupas">Roupas</option>
                                </select>
                            </div>

                            <div class="flex items-center space-x-4">
                                <span class="text-sm text-[var(--color-lab-muted)] font-mono">Total:</span>
                                <span class="border border-black px-3 py-1 text-[10px] font-mono font-bold uppercase">{{ $favoritos->total() }}</span>
                                @if($favoritos->count() > 0)
                                <button id="limpar-todos-favoritos" class="bg-black text-white px-4 py-2 text-[10px] font-mono uppercase tracking-widest hover:bg-gray-900 transition-colors">
                                    <svg class="w-3 h-3 mr-1 inline-block" fill="none" stroke="currentColor" viewBox="0 0 24 24"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 01-2 2H7a2 2 0 01-2-2V6m3 0V4a2 2 0 012-2h4a2 2 0 012 2v2"/></svg>
                                    Limpar Todos
                                </button>
                                @endif
                            </div>
                        </div>
                    </div>

                    <!-- Grid de Produtos Favoritos -->
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
                        @forelse($favoritos as $produto)
                        <!-- Produto Favorito -->
                        <div class="bg-white border border-[var(--color-lab-border)] overflow-hidden group produto-card">
                            <div class="relative">
                                <!-- Imagem do Produto -->
                                @if($produto->primeira_imagem)
                                    <img src="{{ asset('storage/' . $produto->primeira_imagem) }}" alt="{{ $produto->nome }}" class="w-full h-48 object-cover">
                                @else
                                    <div class="w-full h-48 bg-[var(--color-lab-bg)] flex items-center justify-center">
                                        <svg class="w-10 h-10 text-[var(--color-lab-border)]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/></svg>
                                    </div>
                                @endif

                                <!-- Badge de Favorito -->
                                <button class="favorito-btn absolute top-3 right-3 w-10 h-10 bg-black text-white flex items-center justify-center hover:bg-gray-900 transition-colors"
                                        data-produto-id="{{ $produto->id }}">
                                    <svg class="w-4 h-4" fill="currentColor" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/></svg>
                                </button>

                                <!-- Badge de Desconto -->
                                @if($produto->em_promocao && $produto->desconto_percentual > 0)
                                    <div class="absolute top-3 left-3 bg-black text-white px-2 py-1 text-[10px] font-mono uppercase tracking-widest">
                                        -{{ number_format($produto->desconto_percentual, 0) }}%
                                    </div>
                                @endif
                            </div>

                            <div class="p-6">
                                <!-- Categoria -->
                                <div class="font-mono text-[10px] uppercase tracking-widest text-gray-500 mb-2">{{ $produto->categoria->nome ?? 'Categoria' }}</div>

                                <!-- Nome do Produto -->
                                <h3 class="text-sm font-bold text-black mb-2 group-hover:underline transition-colors">
                                    {{ $produto->nome }}
                                </h3>

                                <!-- Descricao -->
                                <p class="text-xs text-[var(--color-lab-muted)] mb-4 line-clamp-2">
                                    {{ Str::limit($produto->descricao, 80) }}
                                </p>

                                <!-- Precos -->
                                <div class="flex items-center space-x-2 mb-4">
                                    @if($produto->em_promocao && $produto->preco_original && $produto->preco_original > $produto->preco)
                                        <span class="text-xs text-[var(--color-lab-muted)] line-through font-mono">R$ {{ number_format($produto->preco_original, 2, ',', '.') }}</span>
                                    @endif
                                    <span class="text-lg font-mono font-bold text-black">R$ {{ number_format($produto->preco, 2, ',', '.') }}</span>
                                </div>

                                <!-- Acoes -->
                                <div class="flex space-x-2">
                                    <button class="flex-1 bg-black text-white py-2 px-4 text-[10px] font-mono uppercase tracking-widest hover:bg-gray-900 transition-colors">
                                        <svg class="w-3 h-3 mr-1 inline-block" fill="none" stroke="currentColor" viewBox="0 0 24 24"><circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/><path d="M1 1h4l2.68 13.39a2 2 0 002 1.61h9.72a2 2 0 002-1.61L23 6H6"/></svg>
                                        Adicionar
                                    </button>
                                    <button class="border border-[var(--color-lab-border)] text-black py-2 px-3 hover:bg-gray-50 transition-colors">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                    </button>
                                    <button class="remover-favorito-btn border border-[var(--color-lab-border)] text-black py-2 px-3 hover:bg-gray-50 transition-colors"
                                            data-produto-id="{{ $produto->id }}">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                    </button>
                                </div>
                            </div>
                        </div>
                        @empty
                        <!-- Estado Vazio -->
                        <div class="col-span-full bg-white border border-[var(--color-lab-border)] p-12 text-center">
                            <div class="w-24 h-24 border border-[var(--color-lab-border)] flex items-center justify-center mx-auto mb-6">
                                <svg class="w-10 h-10 text-[var(--color-lab-muted)]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/></svg>
                            </div>
                            <h3 class="text-xl font-bold text-black mb-4">Nenhum favorito encontrado</h3>
                            <p class="text-[var(--color-lab-muted)] text-sm mb-8">Voce ainda nao adicionou nenhum produto aos favoritos.</p>
                            <a href="{{ route('site.produtos') }}" class="inline-block bg-black text-white px-8 py-3 text-sm font-mono uppercase tracking-widest hover:bg-gray-900 transition-colors">
                                Ver Produtos
                            </a>
                        </div>
                        @endforelse
                    </div>

                    <!-- Paginacao -->
                    @if($favoritos->hasPages())
                    <div class="mt-12 flex justify-center">
                        <nav class="flex items-center space-x-1">
                            @if($favoritos->onFirstPage())
                            <button class="px-3 py-2 text-[var(--color-lab-muted)] border border-[var(--color-lab-border)] font-mono text-sm" disabled>
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><polyline points="15 18 9 12 15 6"/></svg>
                            </button>
                            @else
                            <a href="{{ $favoritos->previousPageUrl() }}" class="px-3 py-2 text-black border border-[var(--color-lab-border)] hover:bg-black hover:text-white font-mono text-sm transition-colors">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><polyline points="15 18 9 12 15 6"/></svg>
                            </a>
                            @endif

                            @foreach($favoritos->getUrlRange(1, $favoritos->lastPage()) as $page => $url)
                                @if($page == $favoritos->currentPage())
                                    <span class="px-4 py-2 bg-black text-white font-mono text-sm">{{ $page }}</span>
                                @else
                                    <a href="{{ $url }}" class="px-4 py-2 text-black border border-[var(--color-lab-border)] hover:bg-black hover:text-white font-mono text-sm transition-colors">{{ $page }}</a>
                                @endif
                            @endforeach

                            @if($favoritos->hasMorePages())
                            <a href="{{ $favoritos->nextPageUrl() }}" class="px-3 py-2 text-black border border-[var(--color-lab-border)] hover:bg-black hover:text-white font-mono text-sm transition-colors">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><polyline points="9 18 15 12 9 6"/></svg>
                            </a>
                            @else
                            <button class="px-3 py-2 text-[var(--color-lab-muted)] border border-[var(--color-lab-border)] font-mono text-sm" disabled>
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><polyline points="9 18 15 12 9 6"/></svg>
                            </button>
                            @endif
                        </nav>
                    </div>
                    @endif

                </div>
            </div>
        </section>
    </main>

    @include('includes.footer')

    <!-- JavaScript para interacoes -->
    <script src="{{ asset('js/dropdowns.js') }}"></script>
    <script src="{{ asset('js/favoritos.js') }}"></script>
    <script src="{{ asset('js/favoritos-produtos.js') }}"></script>

    <script>
        // Funcionalidade especifica para pagina de favoritos
        document.addEventListener('DOMContentLoaded', function() {
            console.log('JFXTECH - Pagina de favoritos carregada');

            // Botao limpar todos os favoritos
            const limparTodosBtn = document.getElementById('limpar-todos-favoritos');
            if (limparTodosBtn) {
                limparTodosBtn.addEventListener('click', function() {
                    if (confirm('Deseja remover todos os produtos dos favoritos?')) {
                        fetch('/favoritos/limpar', {
                            method: 'DELETE',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                            }
                        })
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                location.reload();
                            } else {
                                alert(data.message);
                            }
                        })
                        .catch(error => {
                            console.error('Erro ao limpar favoritos:', error);
                            alert('Erro ao limpar favoritos. Tente novamente.');
                        });
                    }
                });
            }

            // Botoes de remover favorito individual
            document.addEventListener('click', function(e) {
                if (e.target.closest('.remover-favorito-btn')) {
                    e.preventDefault();
                    const button = e.target.closest('.remover-favorito-btn');
                    const produtoId = button.getAttribute('data-produto-id');
                    const produtoCard = button.closest('.produto-card');

                    fetch('/favoritos/remover', {
                        method: 'DELETE',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                        },
                        body: JSON.stringify({
                            produto_id: produtoId
                        })
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            // Animacao de remocao
                            produtoCard.style.transition = 'all 0.3s ease';
                            produtoCard.style.transform = 'scale(0.8)';
                            produtoCard.style.opacity = '0';

                            setTimeout(() => {
                                // Atualizar contador antes de remover
                                const produtosRestantes = document.querySelectorAll('.grid .produto-card');
                                const contador = document.querySelector('.border.border-black.px-3.py-1');
                                if (contador) {
                                    contador.textContent = produtosRestantes.length - 1;
                                }

                                produtoCard.remove();

                                // Verificar se nao ha mais favoritos
                                if (produtosRestantes.length - 1 === 0) {
                                    const grid = document.querySelector('.grid');
                                    if (grid) {
                                        grid.innerHTML = `
                                            <div class="col-span-full bg-white border border-[var(--color-lab-border)] p-12 text-center">
                                                <div class="w-24 h-24 border border-[var(--color-lab-border)] flex items-center justify-center mx-auto mb-6">
                                                    <svg class="w-10 h-10 text-[var(--color-lab-muted)]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/></svg>
                                                </div>
                                                <h3 class="text-xl font-bold text-black mb-4">Nenhum favorito encontrado</h3>
                                                <p class="text-[var(--color-lab-muted)] text-sm mb-8">Voce ainda nao adicionou nenhum produto aos favoritos.</p>
                                                <a href="{{ route('site.produtos') }}" class="inline-block bg-black text-white px-8 py-3 text-sm font-mono uppercase tracking-widest hover:bg-gray-900 transition-colors">
                                                    Ver Produtos
                                                </a>
                                            </div>
                                        `;
                                    }

                                    // Atualizar contador
                                    const contadorEl = document.querySelector('.border.border-black.px-3.py-1');
                                    if (contadorEl) {
                                        contadorEl.textContent = '0';
                                    }

                                    // Esconder botao limpar todos
                                    const limparBtn = document.getElementById('limpar-todos-favoritos');
                                    if (limparBtn) {
                                        limparBtn.style.display = 'none';
                                    }
                                }
                            }, 300);
                        } else {
                            alert(data.message);
                        }
                    })
                    .catch(error => {
                        console.error('Erro ao remover favorito:', error);
                        alert('Erro ao remover favorito. Tente novamente.');
                    });
                }
            });
        });
    </script>
</body>
</html>
