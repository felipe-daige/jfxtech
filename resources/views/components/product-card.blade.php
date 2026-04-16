{{-- JFXTECH Product Card Component --}}
{{-- Usage: @include('components.product-card', ['produto' => $produto]) --}}

<div class="product-card group relative bg-white border border-[var(--color-lab-border)] overflow-hidden flex flex-col h-full cursor-pointer"
     onclick="window.location='{{ route('site.produto.detalhes', $produto->slug) }}'">

    {{-- Product Tags --}}
    <div class="absolute top-4 left-4 z-10 flex flex-col gap-1 items-start">
        {{-- Exemplos de tags baseadas em atributos do produto. Adapte as condições de acordo com seu banco de dados --}}
        
        {{-- Tag Esgotado (Baseado em estoque zerado) --}}
        @if(isset($produto->estoque) && $produto->estoque <= 0)
            <div class="bg-black text-white text-[9px] font-mono px-2 py-1 uppercase tracking-widest">
                Esgotado
            </div>
        @endif

        {{-- Tag Últimas Unidades (Baseado em estoque baixo) --}}
        @if(isset($produto->estoque) && $produto->estoque > 0 && $produto->estoque <= 5)
            <div class="bg-black text-white text-[9px] font-mono px-2 py-1 uppercase tracking-widest">
                Últimas Unid.
            </div>
        @endif

        {{-- Tag Novo / Lançamento (Ex: cadastrado há menos de 30 dias) --}}
        @if(isset($produto->created_at) && $produto->created_at->diffInDays(now()) <= 30)
            <div class="bg-black text-white text-[9px] font-mono px-2 py-1 uppercase tracking-widest">
                Novo
            </div>
        @endif

        {{-- Dynamic Tags from the JSON column --}}
        @if(isset($produto->tags) && is_array($produto->tags))
            @foreach($produto->tags as $tag)
                <div class="bg-black text-white text-[9px] font-mono px-2 py-1 uppercase tracking-widest">
                    {{ $tag }}
                </div>
            @endforeach
        @endif
    </div>

    {{-- Discount Badge --}}
    @if($produto->em_promocao && $produto->desconto_percentual > 0)
        <div class="absolute top-4 right-4 z-10 bg-red-600 text-white text-[9px] font-mono px-2 py-1 uppercase tracking-widest">
            -{{ number_format($produto->desconto_percentual, 0) }}%
        </div>
    @endif

    {{-- Image Container --}}
    <div class="relative aspect-square bg-[var(--color-lab-bg)] overflow-hidden flex items-center justify-center p-6">
        @if($produto->primeira_imagem)
            <img
                src="{{ asset('storage/' . $produto->primeira_imagem) }}"
                alt="{{ $produto->nome }}"
                class="card-image w-full h-full object-contain mix-blend-multiply"
                loading="lazy"
            >
        @else
            <div class="w-full h-full flex items-center justify-center">
                <svg class="w-16 h-16 text-gray-300" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1" stroke-linecap="round" stroke-linejoin="round"><rect width="18" height="18" x="3" y="3" rx="2" ry="2"/><circle cx="9" cy="9" r="2"/><path d="m21 15-3.086-3.086a2 2 0 0 0-2.828 0L6 21"/></svg>
            </div>
        @endif

        {{-- Hover Overlay with Actions --}}
        <div class="card-overlay absolute inset-0 bg-black/5 flex items-center justify-center gap-2 backdrop-blur-[2px]">
            <a href="{{ route('site.produto.detalhes', $produto->slug) }}" class="w-10 h-10 bg-white flex items-center justify-center text-black hover:bg-black hover:text-white transition-colors shadow-sm" title="Ver detalhes" onclick="event.stopPropagation()">
                <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M2 12s3-7 10-7 10 7 10 7-3 7-10 7-10-7-10-7Z"/><circle cx="12" cy="12" r="3"/></svg>
            </a>
            <button
                type="button"
                class="add-to-cart-btn w-10 h-10 bg-black flex items-center justify-center text-white hover:bg-gray-800 transition-colors shadow-sm"
                title="Adicionar ao carrinho"
                data-produto-id="{{ $produto->id }}"
                onclick="return window.handleProductCardAddToCart ? window.handleProductCardAddToCart(event, this) : false"
            >
                <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="8" cy="21" r="1"/><circle cx="19" cy="21" r="1"/><path d="M2.05 2.05h2l2.66 12.42a2 2 0 0 0 2 1.58h9.78a2 2 0 0 0 1.95-1.57l1.65-7.43H5.12"/></svg>
            </button>
        </div>
    </div>

    {{-- Content --}}
    <div class="p-5 flex flex-col flex-1">
        <a href="{{ route('site.produto.detalhes', $produto->slug) }}" class="block mb-2">
            <h3 class="font-bold text-base tracking-tight group-hover:text-gray-600 transition-colors line-clamp-1 uppercase">
                {{ $produto->nome }}
            </h3>
        </a>

        @if($produto->marca)
            <span class="text-[9px] font-mono text-gray-400 uppercase tracking-widest -mt-1 mb-2 block">
                {{ $produto->marca }}
            </span>
        @endif



        {{-- Price & Action --}}
        <div class="flex flex-col items-start gap-2 pt-4 border-t border-[var(--color-lab-border)] mt-auto sm:flex-row sm:items-center sm:justify-between sm:gap-4">
            <div class="min-w-0">
                @if($produto->em_promocao && $produto->preco_original && $produto->preco_original > $produto->preco)
                    <span class="text-xs text-gray-400 line-through block font-mono">R$ {{ number_format($produto->preco_original, 2, ',', '.') }}</span>
                @endif
                <span class="font-mono font-bold text-lg whitespace-nowrap">R$ {{ number_format($produto->preco, 2, ',', '.') }}</span>
            </div>
            <a href="{{ route('site.produto.detalhes', $produto->slug) }}" class="text-xs font-bold uppercase tracking-widest text-gray-500 hover:text-black transition-colors flex items-center gap-1 sm:self-center">
                DETALHES
                <svg class="w-3 h-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m9 18 6-6-6-6"/></svg>
            </a>
        </div>
    </div>
</div>
