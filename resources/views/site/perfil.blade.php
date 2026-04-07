<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <link rel="icon" type="image/png" href="{{ asset('favicon.png') }}">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="robots" content="noindex, nofollow">
    <title>Meu Perfil - JFXTECH</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <link rel="stylesheet" href="{{ asset('css/site-styles.css') }}">

    <!-- jQuery e jMask -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery.mask/1.14.16/jquery.mask.min.js"></script>
</head>
<body class="min-h-screen flex flex-col bg-[var(--color-lab-bg)] text-[var(--color-lab-ink)] antialiased">

    @include('includes.header')

    <main class="flex-grow">
        <!-- Perfil Section -->
        <section class="py-16">
            <div class="container mx-auto px-4 lg:px-8">
                <div class="max-w-4xl mx-auto">

                    <!-- Header do Perfil -->
                    <div class="mb-12">
                        <span class="font-mono text-[10px] uppercase tracking-widest text-gray-500">Conta</span>
                        <h1 class="text-3xl font-bold uppercase tracking-wider text-black mt-1">Meu Perfil</h1>
                        <p class="text-[var(--color-lab-muted)] text-sm mt-2">Gerencie suas informacoes pessoais</p>
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

                    <!-- Header do Perfil Card -->
                    <div class="bg-white border border-[var(--color-lab-border)] p-8 mb-8">
                        <div class="flex flex-col md:flex-row items-center md:items-start space-y-6 md:space-y-0 md:space-x-8">
                            <!-- Avatar -->
                            <div class="relative">
                                <div class="w-24 h-24 bg-black flex items-center justify-center">
                                    <span class="text-white text-2xl font-bold font-mono">{{ substr($usuario->name, 0, 1) }}</span>
                                </div>
                            </div>

                            <!-- Informacoes Basicas -->
                            <div class="flex-1 text-center md:text-left">
                                <h1 class="text-2xl font-bold text-black mb-1">{{ $usuario->name }}</h1>
                                <p class="text-[var(--color-lab-muted)] text-sm font-mono mb-4">{{ $usuario->email }}</p>
                                <div class="flex flex-wrap justify-center md:justify-start gap-3">
                                    <span class="inline-flex items-center px-3 py-1 border border-[var(--color-lab-border)] text-[10px] font-mono uppercase tracking-widest text-gray-500">
                                        <svg class="w-3 h-3 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                        Conta Ativa
                                    </span>
                                    <span class="inline-flex items-center px-3 py-1 border border-[var(--color-lab-border)] text-[10px] font-mono uppercase tracking-widest text-gray-500">
                                        <svg class="w-3 h-3 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
                                        Membro desde {{ $usuario->created_at->format('m/Y') }}
                                    </span>
                                </div>
                            </div>

                            <!-- Botao de Acao -->
                            <div class="flex-shrink-0">
                                <button data-edit-profile class="bg-black text-white px-6 py-3 text-sm font-mono uppercase tracking-widest hover:bg-gray-900 transition-colors">
                                    <svg class="w-4 h-4 mr-2 inline-block" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>Editar Perfil
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Grid de Informacoes -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">

                        <!-- Dados Pessoais -->
                        <div class="bg-white border border-[var(--color-lab-border)] p-6">
                            <div class="flex items-center space-x-3 mb-6">
                                <div class="w-10 h-10 bg-black flex items-center justify-center">
                                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                                </div>
                                <h3 class="text-sm font-mono uppercase tracking-widest text-black font-bold">Dados Pessoais</h3>
                            </div>

                            <div class="space-y-4">
                                <div>
                                    <label class="font-mono text-[10px] uppercase tracking-widest text-gray-500 block mb-1">Nome Completo</label>
                                    <p class="text-black text-sm">{{ $usuario->name }}</p>
                                </div>

                                <div>
                                    <label class="font-mono text-[10px] uppercase tracking-widest text-gray-500 block mb-1">E-mail</label>
                                    <p class="text-black text-sm">{{ $usuario->email }}</p>
                                </div>

                                <div>
                                    <label class="font-mono text-[10px] uppercase tracking-widest text-gray-500 block mb-1">Telefone</label>
                                    <p class="text-black text-sm" data-phone-display>{{ $usuario->phone ?? 'Nao informado' }}</p>
                                </div>
                            </div>
                        </div>

                        <!-- Estatisticas -->
                        <div class="bg-white border border-[var(--color-lab-border)] p-6">
                            <div class="flex items-center space-x-3 mb-6">
                                <div class="w-10 h-10 bg-black flex items-center justify-center">
                                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
                                </div>
                                <h3 class="text-sm font-mono uppercase tracking-widest text-black font-bold">Estatisticas</h3>
                            </div>

                            <div class="space-y-4">
                                <div class="flex items-center justify-between py-3 border-b border-[var(--color-lab-border)]">
                                    <div class="flex items-center space-x-3">
                                        <svg class="w-4 h-4 text-[var(--color-lab-muted)]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/></svg>
                                        <span class="text-sm text-[var(--color-lab-muted)]">Pedidos</span>
                                    </div>
                                    <span class="text-xl font-mono font-bold text-black">0</span>
                                </div>

                                <div class="flex items-center justify-between py-3 border-b border-[var(--color-lab-border)]">
                                    <div class="flex items-center space-x-3">
                                        <svg class="w-4 h-4 text-[var(--color-lab-muted)]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/></svg>
                                        <span class="text-sm text-[var(--color-lab-muted)]">Favoritos</span>
                                    </div>
                                    <span class="text-xl font-mono font-bold text-black">0</span>
                                </div>

                                <div class="flex items-center justify-between py-3">
                                    <div class="flex items-center space-x-3">
                                        <svg class="w-4 h-4 text-[var(--color-lab-muted)]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>
                                        <span class="text-sm text-[var(--color-lab-muted)]">Avaliacoes</span>
                                    </div>
                                    <span class="text-xl font-mono font-bold text-black">0</span>
                                </div>
                            </div>
                        </div>

                    </div>

                </div>
            </div>
        </section>
    </main>

    <!-- Modal de Editar Perfil -->
    <div id="edit-profile-modal" class="fixed inset-0 bg-black/30 backdrop-blur-sm z-50 opacity-0 invisible transition-all duration-300">
        <div class="flex items-center justify-center min-h-screen p-4">
            <div class="bg-white border border-[var(--color-lab-border)] w-full max-w-md">
                <!-- Header do Modal -->
                <div class="flex items-center justify-between p-6 border-b border-[var(--color-lab-border)]">
                    <h3 class="text-sm font-mono uppercase tracking-widest text-black font-bold">Editar Perfil</h3>
                    <button id="close-edit-modal" class="text-[var(--color-lab-muted)] hover:text-black transition-colors">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>

                <!-- Body do Modal -->
                <div class="modal-body p-6">
                    <form id="edit-profile-form" action="{{ route('site.perfil.update') }}" method="POST">
                        @csrf
                        @method('PUT')

                        <!-- Telefone -->
                        <div class="mb-6">
                            <label for="phone" class="font-mono text-[10px] uppercase tracking-widest text-gray-500 block mb-2">
                                Telefone
                            </label>
                            <input
                                type="text"
                                id="phone"
                                name="phone"
                                value="{{ $usuario->phone ?? '' }}"
                                placeholder="(11) 99999-9999"
                                class="w-full border border-[var(--color-lab-border)] px-4 py-3 text-sm font-mono focus:outline-none focus:border-black transition-colors"
                            >
                        </div>

                        <!-- Senha Atual -->
                        <div class="mb-6">
                            <label for="current_password" class="font-mono text-[10px] uppercase tracking-widest text-gray-500 block mb-2">
                                Senha Atual
                            </label>
                            <input
                                type="password"
                                id="current_password"
                                name="current_password"
                                placeholder="Digite sua senha atual"
                                class="w-full border border-[var(--color-lab-border)] px-4 py-3 text-sm font-mono focus:outline-none focus:border-black transition-colors"
                            >
                            <p class="text-[10px] text-[var(--color-lab-muted)] font-mono mt-1">Necessario apenas se quiser alterar a senha</p>
                        </div>

                        <!-- Nova Senha -->
                        <div class="mb-6">
                            <label for="new_password" class="font-mono text-[10px] uppercase tracking-widest text-gray-500 block mb-2">
                                Nova Senha
                            </label>
                            <input
                                type="password"
                                id="new_password"
                                name="new_password"
                                placeholder="Digite sua nova senha"
                                class="w-full border border-[var(--color-lab-border)] px-4 py-3 text-sm font-mono focus:outline-none focus:border-black transition-colors"
                            >
                        </div>

                        <!-- Confirmar Nova Senha -->
                        <div class="mb-6">
                            <label for="confirm_password" class="font-mono text-[10px] uppercase tracking-widest text-gray-500 block mb-2">
                                Confirmar Nova Senha
                            </label>
                            <input
                                type="password"
                                id="confirm_password"
                                name="new_password_confirmation"
                                placeholder="Confirme sua nova senha"
                                class="w-full border border-[var(--color-lab-border)] px-4 py-3 text-sm font-mono focus:outline-none focus:border-black transition-colors"
                            >
                        </div>

                        <!-- Botoes -->
                        <div class="flex space-x-4">
                            <button
                                type="button"
                                id="cancel-edit-modal"
                                class="flex-1 border border-[var(--color-lab-border)] text-black py-3 px-4 text-sm font-mono uppercase tracking-widest hover:bg-gray-50 transition-colors"
                            >
                                Cancelar
                            </button>
                            <button
                                type="submit"
                                id="submit-edit-profile"
                                class="flex-1 bg-black text-white py-3 px-4 text-sm font-mono uppercase tracking-widest hover:bg-gray-900 transition-colors"
                            >
                                Salvar
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    @include('includes.footer')

    <!-- JavaScript para interacoes -->
    <script src="{{ asset('js/dropdowns.js') }}"></script>
    <script src="{{ asset('js/profile-edit.js') }}"></script>
</body>
</html>
