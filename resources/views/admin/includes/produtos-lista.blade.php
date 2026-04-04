@forelse($produtos as $produto)
<div class="border border-[var(--color-lab-border)] bg-white p-4 sm:p-5">
    <div class="flex justify-between items-start gap-3 mb-3">
        <div class="flex-1 min-w-0">
            <div class="flex items-start space-x-3 mb-2">
                {{-- Checkbox de seleção --}}
                <input type="checkbox"
                       class="produto-checkbox w-4 h-4 cursor-pointer accent-black flex-shrink-0"
                       name="produtos_selecionados[]"
                       id="produto_select_{{ $produto->id }}"
                       data-id="{{ $produto->id }}"
                       onclick="event.stopPropagation()"
                       aria-label="Selecionar produto {{ $produto->nome }}">
                @if($produto->imagens->count() > 0)
                    <img src="{{ asset('storage/' . $produto->imagens->first()->caminho) }}" alt="{{ $produto->nome }}" class="w-10 h-10 object-cover flex-shrink-0 border border-[var(--color-lab-border)]">
                @else
                    <div class="w-10 h-10 bg-[var(--color-lab-bg)] border border-[var(--color-lab-border)] flex items-center justify-center flex-shrink-0">
                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" class="text-[var(--color-lab-muted)]"><rect width="18" height="18" x="3" y="3" rx="2" ry="2"/><circle cx="9" cy="9" r="2"/><path d="m21 15-3.086-3.086a2 2 0 0 0-2.828 0L6 21"/></svg>
                    </div>
                @endif
                <div class="flex-1 min-w-0">
                    <h3 class="font-mono text-sm font-bold text-black break-words sm:truncate">{{ $produto->nome }}</h3>
                    <p class="font-mono text-[10px] uppercase tracking-widest text-[var(--color-lab-muted)] break-words">{{ $produto->categoria->nome }}</p>
                </div>
            </div>
            <p class="font-mono text-xs text-[var(--color-lab-muted)] line-clamp-3 mb-2">{{ Str::limit($produto->descricao, 90) }}</p>
        </div>
        <div class="relative ml-2 flex-shrink-0" id="menu-container-{{ $produto->id }}">
            {{-- Botão três pontos --}}
            <button
                onclick="toggleProdutoMenu(event, {{ $produto->id }})"
                class="w-8 h-8 flex items-center justify-center text-[var(--color-lab-muted)] hover:text-black border border-transparent hover:border-[var(--color-lab-border)] transition-colors"
                title="Ações">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24"
                     fill="currentColor"><circle cx="12" cy="5" r="1.5"/><circle cx="12" cy="12" r="1.5"/><circle cx="12" cy="19" r="1.5"/></svg>
            </button>

            {{-- Dropdown --}}
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

    <div class="space-y-3 border-t border-[var(--color-lab-border)] pt-3">
        <!-- Preco -->
        <div class="flex items-start justify-between gap-3">
            <span class="font-mono text-[10px] uppercase tracking-widest text-[var(--color-lab-muted)]">Preco:</span>
            @if($produto->em_promocao && $produto->desconto_percentual > 0)
                <div class="text-right">
                    <div class="font-mono text-sm font-bold text-black">R$ {{ number_format($produto->preco, 2, ',', '.') }}</div>
                    <div class="font-mono text-[10px] text-[var(--color-lab-muted)] line-through">R$ {{ number_format($produto->preco_original, 2, ',', '.') }}</div>
                    <div class="font-mono text-[10px] font-bold text-black">{{ $produto->desconto_percentual }}% OFF</div>
                </div>
            @else
                <span class="font-mono text-sm font-bold text-black">R$ {{ number_format($produto->preco, 2, ',', '.') }}</span>
            @endif
        </div>

        <!-- Estoque -->
        <div class="flex items-center justify-between gap-3">
            <span class="font-mono text-[10px] uppercase tracking-widest text-[var(--color-lab-muted)]">Estoque:</span>
            <span class="font-mono text-sm font-bold text-black">{{ $produto->estoque }}</span>
        </div>

        <!-- Status e Destaque -->
        <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
            <div class="flex flex-wrap items-center gap-2">
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
                    <span class="inline-block px-2 py-0.5 font-mono text-[10px] uppercase tracking-widest bg-black text-white flex items-center space-x-1">
                        <svg xmlns="http://www.w3.org/2000/svg" width="8" height="8" viewBox="0 0 24 24" fill="currentColor" stroke="currentColor" stroke-width="2"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>
                        <span>Destaque</span>
                    </span>
                @endif
            </div>
        </div>
    </div>
</div>
@once
<script>
function toggleProdutoMenu(event, id) {
    event.stopPropagation();
    const menu = document.getElementById('pdrop-' + id);
    const isHidden = menu.classList.contains('hidden');
    const trigger = event.currentTarget;

    // Fecha todos os dropdowns abertos
    document.querySelectorAll('[id^="pdrop-"]').forEach(m => m.classList.add('hidden'));

    if (isHidden) {
        menu.classList.remove('hidden');
        const rect = trigger.getBoundingClientRect();
        const margin = 12;
        let left = rect.right - menu.offsetWidth;
        let top = rect.bottom + 8;

        if (left < margin) left = margin;
        if (left + menu.offsetWidth > window.innerWidth - margin) {
            left = window.innerWidth - menu.offsetWidth - margin;
        }
        if (top + menu.offsetHeight > window.innerHeight - margin) {
            top = Math.max(margin, rect.top - menu.offsetHeight - 8);
        }

        menu.style.left = left + 'px';
        menu.style.top  = top + 'px';
    }
}

function closeProdutoMenu(id) {
    document.getElementById('pdrop-' + id).classList.add('hidden');
}

document.addEventListener('click', function(e) {
    if (!e.target.closest('[id^="menu-container-"]')) {
        document.querySelectorAll('[id^="pdrop-"]').forEach(m => m.classList.add('hidden'));
    }
});
</script>
@endonce

@empty
<div class="col-span-full border border-[var(--color-lab-border)] bg-white p-12 text-center">
    <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1" stroke-linecap="round" stroke-linejoin="round" class="mx-auto text-gray-300 mb-4"><path d="m7.5 4.27 9 5.15"/><path d="M21 8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16Z"/><path d="m3.3 7 8.7 5 8.7-5"/><path d="M12 22V12"/></svg>
    <h3 class="font-mono text-sm font-bold uppercase tracking-widest text-black mb-2">Nenhum produto encontrado</h3>
    <p class="font-mono text-xs text-[var(--color-lab-muted)] mb-6">Nenhum produto corresponde aos criterios de pesquisa.</p>
    <button onclick="abrirModalProduto()" class="bg-black text-white px-5 py-2.5 text-xs font-bold tracking-widest uppercase hover:bg-gray-800 transition-colors inline-flex items-center space-x-2">
        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14"/><path d="M12 5v14"/></svg>
        <span>Adicionar Primeiro Produto</span>
    </button>
</div>
@endforelse
