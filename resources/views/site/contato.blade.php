<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <link rel="icon" type="image/png" href="{{ asset('favicon.png') }}">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Contato - JFXTECH</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <link rel="stylesheet" href="{{ asset('css/site-styles.css') }}">
</head>
<body class="min-h-screen flex flex-col bg-[var(--color-lab-bg)] text-[var(--color-lab-ink)] antialiased">

    @include('includes.header')

    <main class="flex-grow">
        <!-- Hero Section -->
        <section class="bg-black py-16 border-b border-[var(--color-lab-border)]">
            <div class="container mx-auto px-4 lg:px-8">
                <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
                    <div>
                        <span class="font-mono text-[10px] uppercase tracking-widest text-gray-400">Suporte</span>
                        <h2 class="text-3xl md:text-4xl font-bold text-white mb-2 uppercase tracking-wider">
                            Entre em Contato
                        </h2>
                        <p class="text-gray-400 text-sm">Estamos aqui para te ajudar. Entre em contato conosco e tire suas duvidas sobre nossos produtos.</p>
                    </div>
                    <div class="flex items-center gap-4">
                        <div class="bg-white border border-[var(--color-lab-border)] p-3">
                            <span class="text-[var(--color-lab-muted)] text-xs font-mono">Atendimento <span class="text-black font-bold">24h</span> via WhatsApp</span>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Main Content -->
        <section class="py-16">
            <div class="container mx-auto px-4 lg:px-8">
                <div class="grid lg:grid-cols-3 gap-8">

                    <!-- Contact Form -->
                    <div class="lg:col-span-2">
                        <div class="bg-white border border-[var(--color-lab-border)] p-8">
                            <span class="font-mono text-[10px] uppercase tracking-widest text-gray-500">Formulario</span>
                            <h2 class="text-2xl font-bold uppercase tracking-wider mb-2 mt-1">
                                Envie sua mensagem
                            </h2>
                            <p class="text-[var(--color-lab-muted)] text-sm mb-8">Preencha o formulario abaixo e retornaremos em ate 24 horas</p>

                            <form class="space-y-6">
                                <div class="grid md:grid-cols-2 gap-6">
                                    <div>
                                        <label class="font-mono text-[10px] uppercase tracking-widest text-gray-500 block mb-2">Nome Completo *</label>
                                        <input
                                            type="text"
                                            id="nome"
                                            name="nome"
                                            required
                                            class="w-full border border-[var(--color-lab-border)] px-4 py-3 text-sm font-mono focus:outline-none focus:border-black transition-colors"
                                            placeholder="Seu nome"
                                        >
                                    </div>
                                    <div>
                                        <label class="font-mono text-[10px] uppercase tracking-widest text-gray-500 block mb-2">E-mail *</label>
                                        <input
                                            type="email"
                                            id="email"
                                            name="email"
                                            required
                                            class="w-full border border-[var(--color-lab-border)] px-4 py-3 text-sm font-mono focus:outline-none focus:border-black transition-colors"
                                            placeholder="seu@email.com"
                                        >
                                    </div>
                                </div>

                                <div class="grid md:grid-cols-2 gap-6">
                                    <div>
                                        <label class="font-mono text-[10px] uppercase tracking-widest text-gray-500 block mb-2">Telefone *</label>
                                        <input
                                            type="tel"
                                            id="telefone"
                                            name="telefone"
                                            required
                                            class="w-full border border-[var(--color-lab-border)] px-4 py-3 text-sm font-mono focus:outline-none focus:border-black transition-colors"
                                            placeholder="(11) 98765-4321"
                                        >
                                    </div>
                                    <div>
                                        <label class="font-mono text-[10px] uppercase tracking-widest text-gray-500 block mb-2">Assunto *</label>
                                        <select
                                            id="assunto"
                                            name="assunto"
                                            required
                                            class="w-full border border-[var(--color-lab-border)] px-4 py-3 text-sm font-mono focus:outline-none focus:border-black transition-colors"
                                        >
                                            <option value="">Selecione...</option>
                                            <option value="duvida">Duvida sobre produto</option>
                                            <option value="pedido">Status do pedido</option>
                                            <option value="troca">Troca/Devolucao</option>
                                            <option value="sugestao">Sugestao</option>
                                            <option value="outro">Outro</option>
                                        </select>
                                    </div>
                                </div>

                                <div>
                                    <label class="font-mono text-[10px] uppercase tracking-widest text-gray-500 block mb-2">Mensagem *</label>
                                    <textarea
                                        id="mensagem"
                                        name="mensagem"
                                        required
                                        rows="6"
                                        class="w-full border border-[var(--color-lab-border)] px-4 py-3 text-sm font-mono focus:outline-none focus:border-black transition-colors resize-none"
                                        placeholder="Descreva sua mensagem..."
                                    ></textarea>
                                </div>

                                <button
                                    type="submit"
                                    class="w-full bg-black text-white font-mono uppercase tracking-widest py-4 text-sm hover:bg-gray-900 transition-colors"
                                >
                                    <svg class="w-4 h-4 mr-2 inline-block" fill="none" stroke="currentColor" viewBox="0 0 24 24"><line x1="22" y1="2" x2="11" y2="13"/><polygon points="22 2 15 22 11 13 2 9 22 2"/></svg>
                                    Enviar Mensagem
                                </button>
                            </form>
                        </div>
                    </div>

                    <!-- Contact Info -->
                    <div class="space-y-6">

                        <!-- Info Cards -->
                        <div class="bg-white border border-[var(--color-lab-border)] p-6">
                            <div class="flex items-start gap-4 mb-6">
                                <div class="bg-black text-white w-10 h-10 flex items-center justify-center flex-shrink-0">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                                </div>
                                <div>
                                    <h3 class="font-mono text-[10px] uppercase tracking-widest text-gray-500 mb-1">Endereco</h3>
                                    <p class="text-sm text-black">
                                        Av. das Pistas, 1500<br>
                                        Sao Paulo - SP<br>
                                        CEP: 01234-567
                                    </p>
                                </div>
                            </div>

                            <div class="flex items-start gap-4 mb-6">
                                <div class="bg-black text-white w-10 h-10 flex items-center justify-center flex-shrink-0">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/></svg>
                                </div>
                                <div>
                                    <h3 class="font-mono text-[10px] uppercase tracking-widest text-gray-500 mb-1">Telefone</h3>
                                    <p class="text-sm text-black">
                                        (11) 98765-4321<br>
                                        (11) 3456-7890
                                    </p>
                                </div>
                            </div>

                            <div class="flex items-start gap-4 mb-6">
                                <div class="bg-black text-white w-10 h-10 flex items-center justify-center flex-shrink-0">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                                </div>
                                <div>
                                    <h3 class="font-mono text-[10px] uppercase tracking-widest text-gray-500 mb-1">E-mail</h3>
                                    <p class="text-sm text-black">
                                        contato@motoxtre.me<br>
                                        vendas@motoxtre.me
                                    </p>
                                </div>
                            </div>

                            <div class="flex items-start gap-4">
                                <div class="bg-black text-white w-10 h-10 flex items-center justify-center flex-shrink-0">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                                </div>
                                <div>
                                    <h3 class="font-mono text-[10px] uppercase tracking-widest text-gray-500 mb-1">Horario</h3>
                                    <p class="text-sm text-black">
                                        Seg - Sex: 9h as 18h<br>
                                        Sab: 9h as 13h<br>
                                        Dom: Fechado
                                    </p>
                                </div>
                            </div>
                        </div>

                        <!-- Social Media -->
                        <div class="bg-white border border-[var(--color-lab-border)] p-6">
                            <h3 class="font-mono text-[10px] uppercase tracking-widest text-gray-500 mb-4">Redes Sociais</h3>
                            <div class="flex gap-3">
                                <a href="#" class="border border-[var(--color-lab-border)] w-10 h-10 flex items-center justify-center text-[var(--color-lab-muted)] hover:bg-black hover:text-white hover:border-black transition-colors">
                                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24"><path d="M18 2h-3a5 5 0 00-5 5v3H7v4h3v8h4v-8h3l1-4h-4V7a1 1 0 011-1h3z"/></svg>
                                </a>
                                <a href="https://www.instagram.com/jfxtech/" target="_blank" rel="noopener noreferrer" class="border border-[var(--color-lab-border)] w-10 h-10 flex items-center justify-center text-[var(--color-lab-muted)] hover:bg-black hover:text-white hover:border-black transition-colors">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><rect x="2" y="2" width="20" height="20" rx="5" ry="5"/><path d="M16 11.37A4 4 0 1112.63 8 4 4 0 0116 11.37z"/><line x1="17.5" y1="6.5" x2="17.51" y2="6.5"/></svg>
                                </a>
                                <a href="#" class="border border-[var(--color-lab-border)] w-10 h-10 flex items-center justify-center text-[var(--color-lab-muted)] hover:bg-black hover:text-white hover:border-black transition-colors">
                                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24"><path d="M22.54 6.42a2.78 2.78 0 00-1.94-2C18.88 4 12 4 12 4s-6.88 0-8.6.46a2.78 2.78 0 00-1.94 2A29 29 0 001 11.75a29 29 0 00.46 5.33A2.78 2.78 0 003.4 19.1c1.72.46 8.6.46 8.6.46s6.88 0 8.6-.46a2.78 2.78 0 001.94-2 29 29 0 00.46-5.25 29 29 0 00-.46-5.43z"/><polygon points="9.75 15.02 15.5 11.75 9.75 8.48 9.75 15.02" fill="white"/></svg>
                                </a>
                                <a href="#" class="border border-[var(--color-lab-border)] w-10 h-10 flex items-center justify-center text-[var(--color-lab-muted)] hover:bg-black hover:text-white hover:border-black transition-colors">
                                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347z"/><path d="M12 0C5.373 0 0 5.373 0 12c0 2.625.846 5.059 2.284 7.034L.789 23.492l4.624-1.213A11.944 11.944 0 0012 24c6.627 0 12-5.373 12-12S18.627 0 12 0zm0 21.82a9.788 9.788 0 01-5.034-1.391l-.361-.214-3.741.981 1-3.646-.235-.374A9.778 9.778 0 012.18 12c0-5.414 4.406-9.82 9.82-9.82S21.82 6.586 21.82 12s-4.406 9.82-9.82 9.82z"/></svg>
                                </a>
                            </div>
                        </div>

                        <!-- Quick Support -->
                        <div class="bg-black p-6 text-center">
                            <svg class="w-10 h-10 mx-auto mb-3 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 5.636l-3.536 3.536m0 5.656l3.536 3.536M9.172 9.172L5.636 5.636m3.536 9.192l-3.536 3.536M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-5 0a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
                            <h3 class="font-mono text-[10px] uppercase tracking-widest text-gray-400 mb-2">Suporte Rapido</h3>
                            <p class="text-sm mb-4 text-gray-400">Precisa de ajuda imediata?</p>
                            <a href="https://wa.me/5511987654321" target="_blank" class="inline-block bg-white text-black font-mono uppercase text-[10px] tracking-widest px-6 py-3 hover:bg-gray-100 transition-colors">
                                Chat no WhatsApp
                            </a>
                        </div>

                    </div>

                </div>
            </div>
        </section>

        <!-- Map Section -->
        <section class="py-16 border-t border-[var(--color-lab-border)]">
            <div class="container mx-auto px-4 lg:px-8">
                <div class="mb-8">
                    <span class="font-mono text-[10px] uppercase tracking-widest text-gray-500">Mapa</span>
                    <h2 class="text-2xl font-bold uppercase tracking-wider mt-1">
                        Nossa Localizacao
                    </h2>
                    <p class="text-[var(--color-lab-muted)] text-sm mt-1">Venha nos visitar pessoalmente</p>
                </div>

                <div class="bg-white border border-[var(--color-lab-border)] overflow-hidden h-96">
                    <iframe
                        src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3657.0977!2d-46.6333824!3d-23.5505199!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x0%3A0x0!2zMjPCsDMzJzAxLjkiUyA0NsKwMzgnMDAuMiJX!5e0!3m2!1spt-BR!2sbr!4v1234567890"
                        width="100%"
                        height="100%"
                        style="border:0;"
                        allowfullscreen=""
                        loading="lazy"
                        class="grayscale hover:grayscale-0 transition-all duration-500"
                    ></iframe>
                </div>
            </div>
        </section>

        <!-- FAQ Section -->
        <section class="py-16 border-t border-[var(--color-lab-border)]">
            <div class="container mx-auto px-4 lg:px-8">
                <div class="mb-12">
                    <span class="font-mono text-[10px] uppercase tracking-widest text-gray-500">FAQ</span>
                    <h2 class="text-2xl font-bold uppercase tracking-wider mt-1">
                        Perguntas Frequentes
                    </h2>
                    <p class="text-[var(--color-lab-muted)] text-sm mt-1">Respostas rapidas para duvidas comuns</p>
                </div>

                <div class="max-w-3xl mx-auto space-y-4">

                    <div class="bg-white border border-[var(--color-lab-border)] overflow-hidden">
                        <button class="faq-toggle w-full flex items-center justify-between p-6 text-left hover:bg-gray-50 transition-colors">
                            <span class="font-bold text-sm uppercase tracking-wider text-black">Qual o prazo de entrega?</span>
                            <svg class="w-4 h-4 text-black transition-transform faq-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24"><polyline points="6 9 12 15 18 9"/></svg>
                        </button>
                        <div class="faq-content hidden px-6 pb-6 text-[var(--color-lab-muted)] text-sm">
                            O prazo de entrega varia de acordo com sua regiao. Para Sao Paulo capital, entregamos em ate 2 dias uteis. Para outras regioes, o prazo pode variar entre 5 a 15 dias uteis.
                        </div>
                    </div>

                    <div class="bg-white border border-[var(--color-lab-border)] overflow-hidden">
                        <button class="faq-toggle w-full flex items-center justify-between p-6 text-left hover:bg-gray-50 transition-colors">
                            <span class="font-bold text-sm uppercase tracking-wider text-black">Como faco para trocar um produto?</span>
                            <svg class="w-4 h-4 text-black transition-transform faq-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24"><polyline points="6 9 12 15 18 9"/></svg>
                        </button>
                        <div class="faq-content hidden px-6 pb-6 text-[var(--color-lab-muted)] text-sm">
                            Voce tem ate 7 dias apos o recebimento para solicitar a troca. Entre em contato conosco atraves do formulario acima ou pelo WhatsApp informando o numero do pedido e o motivo da troca.
                        </div>
                    </div>

                    <div class="bg-white border border-[var(--color-lab-border)] overflow-hidden">
                        <button class="faq-toggle w-full flex items-center justify-between p-6 text-left hover:bg-gray-50 transition-colors">
                            <span class="font-bold text-sm uppercase tracking-wider text-black">Voces tem loja fisica?</span>
                            <svg class="w-4 h-4 text-black transition-transform faq-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24"><polyline points="6 9 12 15 18 9"/></svg>
                        </button>
                        <div class="faq-content hidden px-6 pb-6 text-[var(--color-lab-muted)] text-sm">
                            Sim! Nossa loja fisica esta localizada na Av. das Pistas, 1500 - Sao Paulo/SP. Funcionamos de segunda a sexta das 9h as 18h e aos sabados das 9h as 13h.
                        </div>
                    </div>

                    <div class="bg-white border border-[var(--color-lab-border)] overflow-hidden">
                        <button class="faq-toggle w-full flex items-center justify-between p-6 text-left hover:bg-gray-50 transition-colors">
                            <span class="font-bold text-sm uppercase tracking-wider text-black">Quais formas de pagamento aceitam?</span>
                            <svg class="w-4 h-4 text-black transition-transform faq-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24"><polyline points="6 9 12 15 18 9"/></svg>
                        </button>
                        <div class="faq-content hidden px-6 pb-6 text-[var(--color-lab-muted)] text-sm">
                            Aceitamos cartoes de credito (ate 12x), debito, PIX, boleto bancario e transferencia. Para compras acima de R$ 1.000, oferecemos condicoes especiais de parcelamento.
                        </div>
                    </div>

                    <div class="bg-white border border-[var(--color-lab-border)] overflow-hidden">
                        <button class="faq-toggle w-full flex items-center justify-between p-6 text-left hover:bg-gray-50 transition-colors">
                            <span class="font-bold text-sm uppercase tracking-wider text-black">Os produtos tem garantia?</span>
                            <svg class="w-4 h-4 text-black transition-transform faq-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24"><polyline points="6 9 12 15 18 9"/></svg>
                        </button>
                        <div class="faq-content hidden px-6 pb-6 text-[var(--color-lab-muted)] text-sm">
                            Sim! Todos os nossos produtos possuem garantia do fabricante. O prazo varia de acordo com o produto, mas geralmente e de 3 a 12 meses. Consulte a descricao de cada produto para mais detalhes.
                        </div>
                    </div>

                </div>
            </div>
        </section>
    </main>

    @include('includes.footer')

    <!-- Scroll to Top Button -->
    <button id="scrollToTop" class="fixed bottom-10 right-10 bg-black text-white w-12 h-12 shadow-lg hover:bg-gray-900 transition-all duration-300 opacity-0 pointer-events-none z-50">
        <svg class="w-5 h-5 mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24"><polyline points="18 15 12 9 6 15"/></svg>
    </button>

    <!-- Dropdowns JavaScript -->
    <script src="{{ asset('js/dropdowns.js') }}"></script>

    <script>
        // Scroll to top functionality
        const scrollToTopBtn = document.getElementById('scrollToTop');

        window.addEventListener('scroll', () => {
            if (window.pageYOffset > 300) {
                scrollToTopBtn.classList.remove('opacity-0', 'invisible');
                scrollToTopBtn.classList.add('pointer-events-auto');
            } else {
                scrollToTopBtn.classList.add('opacity-0', 'invisible');
                scrollToTopBtn.classList.remove('pointer-events-auto');
            }
        });

        scrollToTopBtn.addEventListener('click', () => {
            window.scrollTo({ top: 0, behavior: 'smooth' });
        });

        // FAQ Toggle
        document.querySelectorAll('.faq-toggle').forEach(button => {
            button.addEventListener('click', () => {
                const content = button.nextElementSibling;
                const icon = button.querySelector('.faq-icon');

                // Close all other FAQs
                document.querySelectorAll('.faq-content').forEach(item => {
                    if (item !== content) {
                        item.classList.add('hidden');
                        item.previousElementSibling.querySelector('.faq-icon').style.transform = 'rotate(0deg)';
                    }
                });

                // Toggle current FAQ
                content.classList.toggle('hidden');

                if (content.classList.contains('hidden')) {
                    icon.style.transform = 'rotate(0deg)';
                } else {
                    icon.style.transform = 'rotate(180deg)';
                }
            });
        });

        // Form submission (placeholder)
        document.querySelector('form').addEventListener('submit', (e) => {
            e.preventDefault();
            alert('Mensagem enviada com sucesso! Retornaremos em breve.');
            e.target.reset();
        });
    </script>

</body>
</html>
