{{-- JFXTECH Footer --}}
<footer class="bg-white border-t border-[var(--color-lab-border)] py-12 mt-20">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 grid grid-cols-1 md:grid-cols-4 gap-8">
        {{-- Brand --}}
        <div>
            <div class="flex items-center gap-2 mb-4">
                <img src="{{ asset('storage/images/jfxtech-logo-500x500-removebg-preview.png') }}" alt="JFXTECH" class="h-6 w-6 object-contain">
                <span class="font-bold tracking-tight">JFXTECH</span>
            </div>
            <p class="text-sm text-gray-500 font-mono leading-relaxed">Hardware gamer com engenharia de precisão para a elite competitiva.</p>
            <a href="https://www.instagram.com/jfxtech/" target="_blank" rel="noopener noreferrer" class="inline-flex items-center gap-2 mt-4 text-xs font-mono text-gray-500 hover:text-black transition-colors group">
                <svg class="w-4 h-4 group-hover:scale-110 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><rect x="2" y="2" width="20" height="20" rx="5" ry="5"/><path d="M16 11.37A4 4 0 1112.63 8 4 4 0 0116 11.37z"/><line x1="17.5" y1="6.5" x2="17.51" y2="6.5"/></svg>
                @jfxtech
            </a>
        </div>

        {{-- Products / Categories --}}
        <div>
            <h4 class="font-mono text-xs font-bold uppercase tracking-widest mb-4">Produtos</h4>
            <ul class="space-y-2 text-sm text-gray-600">
                @if(isset($categorias_footer) && count($categorias_footer) > 0)
                    @foreach($categorias_footer as $cat)
                        <li><a href="{{ route('site.produtos', ['categoria' => $cat->id]) }}" class="hover:text-black transition-colors">{{ $cat->nome }}</a></li>
                    @endforeach
                @else
                    <li><a href="{{ route('site.produtos') }}" class="hover:text-black transition-colors">Ver Catálogo</a></li>
                @endif
            </ul>
        </div>

        {{-- Support --}}
        <div>
            <h4 class="font-mono text-xs font-bold uppercase tracking-widest mb-4">Suporte</h4>
            <ul class="space-y-2 text-sm text-gray-600">
                <li><a href="{{ route('site.contato') }}" class="hover:text-black transition-colors">Fale Conosco</a></li>
                <li><a href="{{ route('site.garantia') }}" class="hover:text-black transition-colors">Garantia</a></li>
                <li><a href="{{ route('site.trocas-devolucoes') }}" class="hover:text-black transition-colors">Trocas e Devoluções</a></li>
                <li><a href="{{ route('site.rastreamento') }}" class="hover:text-black transition-colors">Rastreamento</a></li>
                <li><a href="mailto:contato@jfxtech.com.br" class="hover:text-black transition-colors">contato@jfxtech.com.br</a></li>
            </ul>
        </div>

        {{-- Contact --}}
        <div>
            <h4 class="font-mono text-xs font-bold uppercase tracking-widest mb-4">Contato</h4>
            <ul class="space-y-2 text-sm text-gray-600">
                <li><a href="mailto:contato@jfxtech.com.br" class="hover:text-black transition-colors">contato@jfxtech.com.br</a></li>
                <li><a href="{{ route('site.contato') }}" class="hover:text-black transition-colors">Formulário de contato</a></li>
                <li class="text-gray-400 text-xs font-mono pt-1">Seg a Sex, 9h–18h</li>
            </ul>
        </div>
    </div>

    {{-- Bottom Bar --}}
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 mt-12 pt-8 border-t border-gray-100 flex flex-col md:flex-row justify-between items-center text-xs text-gray-400 font-mono tracking-wider">
        <p>&copy; {{ date('Y') }} JFXTECH. TODOS OS DIREITOS RESERVADOS.</p>
        <div class="flex space-x-4 mt-4 md:mt-0">
            <a href="{{ route('site.termos') }}" class="hover:text-gray-900 transition-colors">TERMOS</a>
            <a href="{{ route('site.privacidade') }}" class="hover:text-gray-900 transition-colors">PRIVACIDADE</a>
        </div>
    </div>
</footer>

{{-- Scroll to Top Button --}}
<button id="scrollToTop" class="fixed right-8 {{ request()->routeIs('site.produto.detalhes') ? 'bottom-24 sm:bottom-8' : 'bottom-8' }} bg-black text-white w-10 h-10 flex items-center justify-center shadow-lg hover:bg-gray-800 transition-all duration-300 opacity-0 pointer-events-none z-50">
    <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m18 15-6-6-6 6"/></svg>
</button>

{{-- Scroll to Top + IntersectionObserver for reveal animations --}}
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Scroll to top
    const scrollToTopBtn = document.getElementById('scrollToTop');
    window.addEventListener('scroll', function() {
        if (window.pageYOffset > 300) {
            scrollToTopBtn.classList.remove('opacity-0', 'pointer-events-none');
            scrollToTopBtn.classList.add('opacity-100');
        } else {
            scrollToTopBtn.classList.add('opacity-0', 'pointer-events-none');
            scrollToTopBtn.classList.remove('opacity-100');
        }
    });
    scrollToTopBtn.addEventListener('click', function() {
        window.scrollTo({ top: 0, behavior: 'smooth' });
    });

    // IntersectionObserver for reveal animations
    const observerOptions = { threshold: 0.1, rootMargin: '0px 0px -50px 0px' };
    const observer = new IntersectionObserver(function(entries) {
        entries.forEach(function(entry) {
            if (entry.isIntersecting) {
                entry.target.classList.add('visible');
                observer.unobserve(entry.target);
            }
        });
    }, observerOptions);

    document.querySelectorAll('.reveal, .reveal-left, .reveal-right').forEach(function(el) {
        observer.observe(el);
    });
});
</script>
