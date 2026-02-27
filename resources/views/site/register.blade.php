<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <link rel="icon" type="image/png" href="{{ asset('favicon.png') }}">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Cadastro - JFXTECH</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <link rel="stylesheet" href="{{ asset('css/site-styles.css') }}">
</head>
<body class="min-h-screen flex flex-col bg-[var(--color-lab-bg)] text-[var(--color-lab-ink)] antialiased">
    @include('includes.header')

    <main class="flex-grow flex items-center justify-center py-16">
        <div class="w-full max-w-md mx-auto px-4">
            {{-- Register Card --}}
            <div class="bg-white border border-[var(--color-lab-border)] p-8">
                <div class="text-center mb-8">
                    <div class="flex items-center justify-center gap-2 mb-4">
                        <img src="{{ asset('storage/images/jfxtech-logo-500x500-removebg-preview.png') }}" alt="JFXTECH" class="h-8 w-8 object-contain">
                        <span class="font-bold text-lg tracking-tight">JFXTECH</span>
                    </div>
                    <h1 class="text-2xl font-bold tracking-tight uppercase mb-1">CADASTRO</h1>
                    <p class="text-gray-500 text-sm font-mono">CRIE SUA CONTA</p>
                </div>

                {{-- Form --}}
                <form method="POST" action="{{ route('site.register.post') }}" class="space-y-5">
                    @csrf

                    @if ($errors->any())
                        <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 text-sm">
                            <ul class="list-disc list-inside">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    @if (session('success'))
                        <div class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 text-sm">
                            {{ session('success') }}
                        </div>
                    @endif

                    <div>
                        <label class="block text-[10px] font-mono font-bold uppercase tracking-widest mb-2 text-gray-500">NOME COMPLETO</label>
                        <input type="text" name="name" value="{{ old('name') }}" placeholder="Seu nome completo"
                               class="w-full border px-4 py-3 text-sm font-mono focus:outline-none focus:border-black transition-colors bg-white @error('name') border-red-500 @else border-[var(--color-lab-border)] @enderror" required>
                        @error('name')
                            <p class="text-red-500 text-xs mt-1 font-mono">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label class="block text-[10px] font-mono font-bold uppercase tracking-widest mb-2 text-gray-500">EMAIL</label>
                        <input type="email" name="email" value="{{ old('email') }}" placeholder="seu@email.com"
                               class="w-full border px-4 py-3 text-sm font-mono focus:outline-none focus:border-black transition-colors bg-white @error('email') border-red-500 @else border-[var(--color-lab-border)] @enderror" required>
                        @error('email')
                            <p class="text-red-500 text-xs mt-1 font-mono">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label class="block text-[10px] font-mono font-bold uppercase tracking-widest mb-2 text-gray-500">TELEFONE</label>
                        <input type="tel" name="phone" id="phone" value="{{ old('phone') }}" placeholder="(11) 98765-4321"
                               class="w-full border px-4 py-3 text-sm font-mono focus:outline-none focus:border-black transition-colors bg-white @error('phone') border-red-500 @else border-[var(--color-lab-border)] @enderror" required>
                        @error('phone')
                            <p class="text-red-500 text-xs mt-1 font-mono">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label class="block text-[10px] font-mono font-bold uppercase tracking-widest mb-2 text-gray-500">SENHA</label>
                        <div class="relative">
                            <input type="password" name="password" id="password" placeholder="••••••••"
                                   class="w-full border px-4 py-3 pr-12 text-sm font-mono focus:outline-none focus:border-black transition-colors bg-white @error('password') border-red-500 @else border-[var(--color-lab-border)] @enderror" required>
                            <button type="button" id="togglePassword" class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-black transition-colors">
                                <svg class="w-5 h-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M2 12s3-7 10-7 10 7 10 7-3 7-10 7-10-7-10-7Z"/><circle cx="12" cy="12" r="3"/></svg>
                            </button>
                        </div>
                        <p class="text-[10px] text-gray-400 mt-1 font-mono">MÍNIMO 8 CARACTERES</p>
                        @error('password')
                            <p class="text-red-500 text-xs mt-1 font-mono">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label class="block text-[10px] font-mono font-bold uppercase tracking-widest mb-2 text-gray-500">CONFIRMAR SENHA</label>
                        <input type="password" name="password_confirmation" id="password_confirmation" placeholder="••••••••"
                               class="w-full border border-[var(--color-lab-border)] px-4 py-3 text-sm font-mono focus:outline-none focus:border-black transition-colors bg-white" required>
                    </div>

                    <div class="flex items-start gap-2">
                        <input type="checkbox" id="terms" name="terms" class="tech-checkbox mt-1 @error('terms') border-red-500 @enderror" required>
                        <label for="terms" class="text-xs text-gray-600">
                            Aceito os <a href="#" class="font-bold text-black hover:text-gray-600">Termos de Uso</a> e a <a href="#" class="font-bold text-black hover:text-gray-600">Política de Privacidade</a>
                        </label>
                    </div>
                    @error('terms')
                        <p class="text-red-500 text-xs font-mono">{{ $message }}</p>
                    @enderror

                    <div class="flex items-start gap-2">
                        <input type="checkbox" id="newsletter" name="newsletter" class="tech-checkbox mt-1">
                        <label for="newsletter" class="text-xs text-gray-600">Quero receber ofertas e novidades</label>
                    </div>

                    <button type="submit" class="w-full bg-black text-white py-4 font-bold tracking-widest uppercase text-sm hover:bg-gray-800 transition-colors">
                        CRIAR CONTA
                    </button>
                </form>

                <div class="mt-6 text-center">
                    <p class="text-sm text-gray-500">
                        Já tem conta?
                        <a href="{{ route('site.login') }}" class="font-bold text-black hover:text-gray-600 transition-colors uppercase tracking-wider text-xs ml-1">FAÇA LOGIN</a>
                    </p>
                </div>
            </div>

            <div class="mt-4 text-center">
                <div class="inline-flex items-center gap-2 text-[10px] text-gray-400 font-mono uppercase tracking-widest">
                    <svg class="w-3 h-3" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="18" height="11" x="3" y="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                    SEUS DADOS ESTÃO PROTEGIDOS
                </div>
            </div>
        </div>
    </main>

    @include('includes.footer')

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery.mask/1.14.16/jquery.mask.min.js"></script>
    <script>
        $(document).ready(function() { $('#phone').mask('(00) 00000-0000'); });
        document.getElementById('togglePassword').addEventListener('click', function() {
            var input = document.getElementById('password');
            input.type = input.type === 'password' ? 'text' : 'password';
        });
    </script>
</body>
</html>
