{{-- Banner Promocional — Frete Grátis de Inauguração --}}
{{-- Uso: @include('includes.banner-frete-gratis') --}}

<div class="relative overflow-hidden bg-gradient-to-r from-zinc-800 via-gray-700 to-zinc-800 border-b border-zinc-600" id="promo-frete-gratis">
    {{-- Shimmer overlay --}}
    <div class="absolute inset-0 opacity-[0.04]" style="background:linear-gradient(90deg,transparent,#fff 50%,transparent);animation:promo-shimmer 4s ease-in-out infinite"></div>
    {{-- Top accent line --}}
    <div class="absolute top-0 left-0 right-0 h-[1px] bg-gradient-to-r from-transparent via-white/20 to-transparent"></div>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-2.5 sm:py-3 flex items-center gap-3 sm:gap-4 relative z-10">
        {{-- Icon --}}
        <div class="hidden sm:flex w-9 h-9 border border-white/20 items-center justify-center flex-shrink-0">
            <svg class="w-4 h-4 text-white" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M14 18V6a2 2 0 0 0-2-2H4a2 2 0 0 0-2 2v11a1 1 0 0 0 1 1h2"/>
                <path d="M15 18H9"/>
                <path d="M19 18h2a1 1 0 0 0 1-1v-3.65a1 1 0 0 0-.22-.624l-3.48-4.35A1 1 0 0 0 17.52 8H14"/>
                <circle cx="17" cy="18" r="2"/>
                <circle cx="7" cy="18" r="2"/>
            </svg>
        </div>

        {{-- Text --}}
        <div class="flex-1 min-w-0 flex items-center gap-2 sm:gap-3 flex-wrap">
            <span class="bg-white text-black text-[9px] sm:text-[10px] font-mono font-bold tracking-[0.15em] uppercase px-1.5 py-0.5 leading-tight flex-shrink-0">INAUGURAÇÃO</span>
            <p class="text-white text-[11px] sm:text-[13px] tracking-wide m-0">
                <strong class="font-extrabold tracking-wider">FRETE GRÁTIS</strong> em todos os produtos — sem valor mínimo!
            </p>
        </div>

        {{-- CTA --}}
        <a href="{{ route('site.produtos') }}"
           class="hidden sm:inline-flex items-center gap-1.5 bg-white text-black px-4 py-2 text-[10px] font-extrabold tracking-[0.12em] uppercase no-underline hover:bg-white/90 transition-all flex-shrink-0 group">
            VER PRODUTOS
            <svg class="w-3 h-3 group-hover:translate-x-0.5 transition-transform" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="m9 18 6-6-6-6"/></svg>
        </a>

        {{-- Close --}}
        <button onclick="this.closest('#promo-frete-gratis').style.display='none'"
                class="w-6 h-6 flex items-center justify-center border border-white/15 text-white/40 hover:text-white hover:border-white/40 transition-colors flex-shrink-0 bg-transparent cursor-pointer"
                aria-label="Fechar banner">
            <svg class="w-2.5 h-2.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M18 6 6 18"/><path d="m6 6 12 12"/></svg>
        </button>
    </div>
</div>

<style>
@keyframes promo-shimmer { 0%{transform:translateX(-100%)} 100%{transform:translateX(100%)} }
</style>
