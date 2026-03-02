<div class="flex items-center gap-3 px-4 py-3 border-b border-[var(--color-lab-border)] bg-white
            hover:bg-[var(--color-lab-bg)] transition-colors min-h-[56px]">

    {{-- Checkbox de seleção --}}
    <input type="checkbox"
           class="produto-checkbox w-4 h-4 cursor-pointer accent-black flex-shrink-0"
           data-id="{{ $produto->id }}"
           onclick="event.stopPropagation()">

    {{-- Thumbnail 40×40 --}}
    @if($produto->imagens->count() > 0)
        <img src="{{ asset('storage/' . $produto->imagens->first()->caminho) }}"
             alt="{{ $produto->nome }}"
             class="w-10 h-10 object-cover flex-shrink-0 border border-[var(--color-lab-border)]">
    @else
        <div class="w-10 h-10 bg-[var(--color-lab-bg)] border border-[var(--color-lab-border)] flex items-center justify-center flex-shrink-0">
            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none"
                 stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"
                 class="text-[var(--color-lab-muted)]">
                <rect width="18" height="18" x="3" y="3" rx="2" ry="2"/>
                <circle cx="9" cy="9" r="2"/>
                <path d="m21 15-3.086-3.086a2 2 0 0 0-2.828 0L6 21"/>
            </svg>
        </div>
    @endif

    {{-- Nome + Categoria --}}
    <div class="flex-1 min-w-0">
        <h3 class="font-mono text-sm font-bold text-black truncate">{{ $produto->nome }}</h3>
        <p class="font-mono text-[10px] uppercase tracking-widest text-[var(--color-lab-muted)] truncate">
            {{ $produto->categoria->nome }}
        </p>
    </div>

    {{-- Preço (oculto em xs) --}}
    <div class="hidden sm:block text-right flex-shrink-0 w-28">
        @if($produto->em_promocao && $produto->desconto_percentual > 0)
            <div class="font-mono text-sm font-bold text-black">R$ {{ number_format($produto->preco, 2, ',', '.') }}</div>
            <div class="font-mono text-[10px] text-[var(--color-lab-muted)] line-through">R$ {{ number_format($produto->preco_original, 2, ',', '.') }}</div>
        @else
            <span class="font-mono text-sm font-bold text-black">R$ {{ number_format($produto->preco, 2, ',', '.') }}</span>
        @endif
    </div>

    {{-- Estoque (oculto em xs/sm) --}}
    <div class="hidden md:flex flex-col items-center flex-shrink-0 w-16">
        <span class="font-mono text-[10px] uppercase tracking-widest text-[var(--color-lab-muted)]">Estoque</span>
        <span class="font-mono text-sm font-bold text-black">{{ $produto->estoque }}</span>
    </div>

    {{-- Badges ativo / destaque (oculto em xs/sm/md) --}}
    <div class="hidden lg:flex items-center gap-2 flex-shrink-0">
        @if($produto->ativo)
            <span class="inline-block px-2 py-0.5 font-mono text-[10px] uppercase tracking-widest border border-black text-black">
                Ativo
            </span>
        @else
            <span class="inline-block px-2 py-0.5 font-mono text-[10px] uppercase tracking-widest border border-gray-300 text-gray-400">
                Inativo
            </span>
        @endif

        @if($produto->destaque)
            <span class="inline-flex items-center gap-1 px-2 py-0.5 font-mono text-[10px] uppercase tracking-widest bg-black text-white">
                <svg xmlns="http://www.w3.org/2000/svg" width="8" height="8" viewBox="0 0 24 24"
                     fill="currentColor" stroke="currentColor" stroke-width="2">
                    <polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/>
                </svg>
                <span>Destaque</span>
            </span>
        @endif
    </div>

    {{-- Kebab menu --}}
    <div class="relative flex-shrink-0" id="menu-container-{{ $produto->id }}">
        <button onclick="toggleProdutoMenu(event, {{ $produto->id }})"
                class="w-8 h-8 flex items-center justify-center text-[var(--color-lab-muted)] hover:text-black border border-transparent hover:border-[var(--color-lab-border)] transition-colors"
                title="Ações">
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24"
                 fill="currentColor">
                <circle cx="12" cy="5" r="1.5"/><circle cx="12" cy="12" r="1.5"/><circle cx="12" cy="19" r="1.5"/>
            </svg>
        </button>

        <div id="pdrop-{{ $produto->id }}"
             class="hidden fixed w-48 bg-white border border-[var(--color-lab-border)] shadow-lg z-50">

            {{-- Visitar página --}}
            <a href="{{ route('site.produto.detalhes', $produto->slug) }}"
               target="_blank"
               class="flex items-center space-x-2 px-3 py-2 text-xs font-mono text-[var(--color-lab-muted)] hover:text-black hover:bg-[var(--color-lab-bg)] transition-colors">
                <svg class="w-3.5 h-3.5 flex-shrink-0" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"
                     fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M15 3h6v6"/><path d="M10 14 21 3"/>
                    <path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/>
                </svg>
                <span>Visitar página</span>
            </a>

            {{-- Editar --}}
            <button onclick="closeProdutoMenu({{ $produto->id }}); editarProduto({{ $produto->id }})"
                    class="w-full flex items-center space-x-2 px-3 py-2 text-xs font-mono text-[var(--color-lab-muted)] hover:text-black hover:bg-[var(--color-lab-bg)] transition-colors">
                <svg class="w-3.5 h-3.5 flex-shrink-0" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"
                     fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M17 3a2.85 2.83 0 1 1 4 4L7.5 20.5 2 22l1.5-5.5Z"/><path d="m15 5 4 4"/>
                </svg>
                <span>Editar</span>
            </button>

            {{-- Destaque (condicional) --}}
            @if($produto->destaque)
                <button onclick="closeProdutoMenu({{ $produto->id }}); confirmarAlteracaoDestaque({{ $produto->id }}, 0, 'remover do destaque')"
                        class="w-full flex items-center space-x-2 px-3 py-2 text-xs font-mono text-black hover:bg-[var(--color-lab-bg)] transition-colors">
                    <svg class="w-3.5 h-3.5 flex-shrink-0" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"
                         fill="currentColor" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/>
                    </svg>
                    <span>Remover destaque</span>
                </button>
            @else
                <button onclick="closeProdutoMenu({{ $produto->id }}); confirmarAlteracaoDestaque({{ $produto->id }}, 1, 'adicionar ao destaque')"
                        class="w-full flex items-center space-x-2 px-3 py-2 text-xs font-mono text-[var(--color-lab-muted)] hover:text-black hover:bg-[var(--color-lab-bg)] transition-colors">
                    <svg class="w-3.5 h-3.5 flex-shrink-0" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"
                         fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/>
                    </svg>
                    <span>Adicionar destaque</span>
                </button>
            @endif

            {{-- Status (condicional) --}}
            @if($produto->ativo)
                <button onclick="closeProdutoMenu({{ $produto->id }}); confirmarAlteracaoStatus({{ $produto->id }}, 0, 'desativar')"
                        class="w-full flex items-center space-x-2 px-3 py-2 text-xs font-mono text-[var(--color-lab-muted)] hover:text-black hover:bg-[var(--color-lab-bg)] transition-colors">
                    <svg class="w-3.5 h-3.5 flex-shrink-0" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"
                         fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <rect x="14" y="4" width="4" height="16" rx="1"/><rect x="6" y="4" width="4" height="16" rx="1"/>
                    </svg>
                    <span>Desativar</span>
                </button>
            @else
                <button onclick="closeProdutoMenu({{ $produto->id }}); confirmarAlteracaoStatus({{ $produto->id }}, 1, 'ativar')"
                        class="w-full flex items-center space-x-2 px-3 py-2 text-xs font-mono text-[var(--color-lab-muted)] hover:text-black hover:bg-[var(--color-lab-bg)] transition-colors">
                    <svg class="w-3.5 h-3.5 flex-shrink-0" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"
                         fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <polygon points="6 3 20 12 6 21 6 3"/>
                    </svg>
                    <span>Ativar</span>
                </button>
            @endif

            {{-- Separador --}}
            <div class="border-t border-[var(--color-lab-border)] my-1"></div>

            {{-- Excluir (destrutivo) --}}
            <button onclick="closeProdutoMenu({{ $produto->id }}); confirmarExclusao({{ $produto->id }})"
                    class="w-full flex items-center space-x-2 px-3 py-2 text-xs font-mono text-red-500 hover:text-red-700 hover:bg-red-50 transition-colors">
                <svg class="w-3.5 h-3.5 flex-shrink-0" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"
                     fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M3 6h18"/><path d="M19 6v14c0 1-1 2-2 2H7c-1 0-2-1-2-2V6"/>
                    <path d="M8 6V4c0-1 1-2 2-2h4c1 0 2 1 2 2v2"/>
                </svg>
                <span>Excluir</span>
            </button>
        </div>
    </div>
</div>
