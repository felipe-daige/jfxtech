@if($produtos->hasPages())
<div class="mt-6 border border-[var(--color-lab-border)] bg-white p-4">
    <!-- Informacoes de paginacao -->
    <div class="text-center font-mono text-xs text-[var(--color-lab-muted)] uppercase tracking-widest mb-4">
        Mostrando {{ $produtos->firstItem() ?? 0 }} a {{ $produtos->lastItem() ?? 0 }} de {{ $produtos->total() }} produtos
        @if(!empty($pesquisa))
            para "{{ $pesquisa }}"
        @endif
        @if(!empty($categoriaId) && isset($categorias))
            @php $categoriaSelecionada = $categorias->firstWhere('id', $categoriaId); @endphp
            @if($categoriaSelecionada)
                na categoria "{{ $categoriaSelecionada->nome }}"
            @endif
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
