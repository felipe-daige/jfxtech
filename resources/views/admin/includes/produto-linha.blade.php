@php
    $smLinha = $salesMetrics[$produto->id] ?? null;
    $precoEfetivoLinha = $produto->em_promocao && $produto->desconto_percentual > 0
        ? round($produto->preco * (1 - $produto->desconto_percentual / 100), 2)
        : (float) $produto->preco;
    $custoEfetivoLinha = ($produto->custo_compra ?? 0) + ($produto->frete_compra ?? 0);
    $margemLinha = ($custoEfetivoLinha > 0 && $precoEfetivoLinha > 0)
        ? round(($precoEfetivoLinha - $custoEfetivoLinha) / $precoEfetivoLinha * 100, 1)
        : null;
@endphp
<div class="flex flex-col sm:flex-row sm:items-center gap-3 px-4 py-4 border-b border-[var(--color-lab-border)] bg-white hover:bg-[var(--color-lab-bg)] transition-colors min-h-[56px]">
    <div class="flex items-start gap-3 w-full min-w-0">
        <input type="checkbox"
               class="produto-checkbox w-4 h-4 cursor-pointer accent-black flex-shrink-0 mt-1"
               name="produtos_selecionados[]"
               id="produto_select_{{ $produto->id }}"
               data-id="{{ $produto->id }}"
               onclick="event.stopPropagation()"
               aria-label="Selecionar produto {{ $produto->nome }}">

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

        <div class="flex-1 min-w-0">
            <div class="flex items-start justify-between gap-3">
                <div class="min-w-0">
                    <h3 class="font-mono text-sm font-bold text-black break-words sm:truncate">{{ $produto->nome }}</h3>
                    <p class="font-mono text-[10px] uppercase tracking-widest text-[var(--color-lab-muted)] break-words sm:truncate">
                        {{ $produto->categoria->nome }}
                    </p>
                </div>

                <div class="relative flex-shrink-0" id="menu-container-{{ $produto->id }}">
                    <button onclick="toggleProdutoMenu(event, {{ $produto->id }})"
                            class="w-8 h-8 flex items-center justify-center text-[var(--color-lab-muted)] hover:text-black border border-transparent hover:border-[var(--color-lab-border)] transition-colors"
                            title="Ações">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="currentColor">
                            <circle cx="12" cy="5" r="1.5"/><circle cx="12" cy="12" r="1.5"/><circle cx="12" cy="19" r="1.5"/>
                        </svg>
                    </button>

                    <div id="pdrop-{{ $produto->id }}"
                         class="hidden fixed w-48 bg-white border border-[var(--color-lab-border)] shadow-lg z-50">
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

                        <a href="{{ route('admin.produtos.ver', $produto->id) }}"
                           class="flex items-center space-x-2 px-3 py-2 text-xs font-mono text-[var(--color-lab-muted)] hover:text-black hover:bg-[var(--color-lab-bg)] transition-colors">
                            <svg class="w-3.5 h-3.5 flex-shrink-0" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"
                                 fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M2 12s3-7 10-7 10 7 10 7-3 7-10 7-10-7-10-7Z"/>
                                <circle cx="12" cy="12" r="3"/>
                            </svg>
                            <span>Ver detalhes</span>
                        </a>

                        <button onclick="closeProdutoMenu({{ $produto->id }}); editarProduto({{ $produto->id }})"
                                class="w-full flex items-center space-x-2 px-3 py-2 text-xs font-mono text-[var(--color-lab-muted)] hover:text-black hover:bg-[var(--color-lab-bg)] transition-colors">
                            <svg class="w-3.5 h-3.5 flex-shrink-0" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"
                                 fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M17 3a2.85 2.83 0 1 1 4 4L7.5 20.5 2 22l1.5-5.5Z"/><path d="m15 5 4 4"/>
                            </svg>
                            <span>Editar</span>
                        </button>

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

                        <div class="border-t border-[var(--color-lab-border)] my-1"></div>

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

            <div class="flex flex-wrap items-center gap-2 mt-3 sm:hidden">
                <span class="font-mono text-[10px] uppercase tracking-widest text-[var(--color-lab-muted)]">Preço</span>
                <span class="font-mono text-xs font-bold text-black">R$ {{ number_format($produto->preco, 2, ',', '.') }}</span>
                <span class="font-mono text-[10px] uppercase tracking-widest text-[var(--color-lab-muted)] ml-2">Estoque</span>
                <span class="font-mono text-xs font-bold text-black">{{ $produto->estoque }}</span>
            </div>
            <div class="flex flex-wrap items-center gap-2 mt-3 lg:hidden">
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
        </div>
    </div>

    <div class="hidden sm:block text-right flex-shrink-0 w-28">
        @if($produto->em_promocao && $produto->desconto_percentual > 0)
            <div class="font-mono text-sm font-bold text-black">R$ {{ number_format($produto->preco, 2, ',', '.') }}</div>
            <div class="font-mono text-[10px] text-[var(--color-lab-muted)] line-through">R$ {{ number_format($produto->preco_original, 2, ',', '.') }}</div>
        @else
            <span class="font-mono text-sm font-bold text-black">R$ {{ number_format($produto->preco, 2, ',', '.') }}</span>
        @endif
    </div>

    <div class="hidden md:flex flex-col items-center flex-shrink-0 w-16">
        <span class="font-mono text-[10px] uppercase tracking-widest text-[var(--color-lab-muted)]">Estoque</span>
        <span class="font-mono text-sm font-bold text-black">{{ $produto->estoque }}</span>
    </div>

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

    {{-- Margem (visível a partir de xl) --}}
    <div class="hidden xl:flex flex-col items-end flex-shrink-0 w-20">
        <span class="font-mono text-[9px] uppercase tracking-widest text-[var(--color-lab-muted)]">Margem</span>
        <span class="font-mono text-xs font-bold mt-0.5
            {{ $margemLinha === null ? 'text-gray-400' : ($margemLinha < 0 ? 'text-red-600' : ($margemLinha < 20 ? 'text-yellow-600' : 'text-black')) }}">
            {{ $margemLinha !== null ? number_format($margemLinha, 1, ',', '.') . '%' : '—' }}
        </span>
    </div>

    {{-- Receita (visível a partir de xl) --}}
    <div class="hidden xl:flex flex-col items-end flex-shrink-0 w-28">
        <span class="font-mono text-[9px] uppercase tracking-widest text-[var(--color-lab-muted)]">Receita</span>
        <span class="font-mono text-xs font-bold text-black mt-0.5">
            {{ $smLinha ? 'R$&nbsp;' . number_format($smLinha->receita_bruta, 0, ',', '.') : '—' }}
        </span>
        @if($smLinha)
            <span class="font-mono text-[9px] text-[var(--color-lab-muted)]">{{ $smLinha->unidades_vendidas }} un.</span>
        @endif
    </div>
</div>
