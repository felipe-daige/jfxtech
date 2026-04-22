@props([
    'description' => '',
])

@php
    $descricaoRaw = \App\Support\ProdutoDescricaoFormatter::sanitize((string) $description);
    $descricaoTemHtml = preg_match('/<\/?(p|br|ul|ol|li|h[1-6]|strong|em|b|i|a|blockquote|table|tr|td|th|div|span)\b/i', $descricaoRaw) === 1;

    if ($descricaoTemHtml) {
        $descricaoHtml = $descricaoRaw;
    } else {
        $descricaoPartes = preg_split("/\R{2,}/u", $descricaoRaw) ?: [];
        $descricaoHtml = collect($descricaoPartes)
            ->map(fn ($parte) => trim($parte))
            ->filter()
            ->map(function ($parte) {
                return '<p>' . nl2br(e($parte), false) . '</p>';
            })
            ->implode('');
    }

    $descricaoTextoLimpo = preg_replace('/\s+/u', ' ', trim(strip_tags($descricaoRaw)));
    $descricaoLonga = mb_strlen($descricaoTextoLimpo) > 420;
    $descricaoResumo = $descricaoLonga ? Str::limit($descricaoTextoLimpo, 420) : $descricaoTextoLimpo;
@endphp

@if($descricaoTextoLimpo !== '')
<section class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
    <div class="border border-[var(--color-lab-border)] bg-white overflow-hidden">
        <div class="grid lg:grid-cols-[240px_minmax(0,1fr)]">
            <div class="border-b lg:border-b-0 lg:border-r border-[var(--color-lab-border)] bg-[var(--color-lab-bg)] p-6 sm:p-8">
                <p class="font-mono text-[10px] font-bold uppercase tracking-[0.3em] text-gray-500 mb-3">Descrição</p>
                <h3 class="text-2xl sm:text-3xl font-bold tracking-tight text-gray-900">Detalhes do produto</h3>
                <p class="mt-4 text-sm leading-7 text-gray-600">Informações, características e observações relevantes sobre este item.</p>
            </div>

            <div class="p-6 sm:p-8 lg:p-10 js-product-description">
                @if($descricaoLonga)
                <div class="border border-[var(--color-lab-border)] bg-[var(--color-lab-bg)] p-5 sm:p-6" data-description-summary>
                    <p class="font-mono text-[10px] uppercase tracking-[0.25em] text-gray-500 mb-3">Visão geral</p>
                    <p class="text-base sm:text-lg leading-8 text-gray-700">{{ $descricaoResumo }}</p>
                </div>
                @endif

                <div class="product-description-content max-w-3xl {{ $descricaoLonga ? 'hidden mt-6' : '' }}" data-description-full>
                    {!! $descricaoHtml !!}
                </div>

                @if($descricaoLonga)
                <div class="mt-6 flex items-center gap-4">
                    <button type="button"
                            class="inline-flex items-center gap-2 border border-black px-5 py-3 text-xs font-mono font-bold uppercase tracking-[0.22em] text-black transition-colors hover:bg-black hover:text-white"
                            data-description-toggle
                            aria-expanded="false">
                        <span data-expand-label>Ler descrição completa</span>
                        <svg class="h-4 w-4 transition-transform" data-expand-icon viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <path d="m6 9 6 6 6-6"/>
                        </svg>
                    </button>
                    <p class="text-sm text-gray-500">Expanda para ver detalhes, listas e observações completas.</p>
                </div>
                @endif
            </div>
        </div>
    </div>
</section>
@endif
