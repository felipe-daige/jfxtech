<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <link rel="icon" type="image/png" href="{{ asset('favicon.png') }}">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="robots" content="noindex, nofollow">
    <title>Nova Senha - JFXTECH</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <link rel="stylesheet" href="{{ asset('css/site-styles.css') }}">
</head>
<body class="min-h-screen flex flex-col bg-[var(--color-lab-bg)] text-[var(--color-lab-ink)] antialiased">
    @include('includes.header')

    <main class="flex-grow flex items-center justify-center py-16">
        <div class="w-full max-w-md mx-auto px-4">
            <div class="bg-white border border-[var(--color-lab-border)] p-8">
                <div class="text-center mb-8">
                    <div class="flex items-center justify-center gap-2 mb-4">
                        <img src="{{ asset('storage/images/jfxtech-logo-500x500-removebg-preview.png') }}" alt="JFXTECH" class="h-8 w-8 object-contain">
                        <span class="font-bold text-lg tracking-tight">JFXTECH</span>
                    </div>
                    <h1 class="text-2xl font-bold tracking-tight uppercase mb-1">NOVA SENHA</h1>
                    <p class="text-gray-500 text-sm font-mono">CADASTRE UMA SENHA SEGURA</p>
                </div>

                <form method="POST" action="{{ route('site.password.update') }}" class="space-y-5">
                    @csrf
                    <input type="hidden" name="token" value="{{ $token }}">

                    @if ($errors->any())
                        <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 text-sm">
                            <ul class="list-disc list-inside">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <div>
                        <label class="block text-[10px] font-mono font-bold uppercase tracking-widest mb-2 text-gray-500">EMAIL</label>
                        <input type="email" name="email" value="{{ old('email', $email) }}" placeholder="seu@email.com"
                               class="w-full border px-4 py-3 text-sm font-mono focus:outline-none focus:border-black transition-colors bg-white @error('email') border-red-500 @else border-[var(--color-lab-border)] @enderror" required>
                    </div>

                    <div>
                        <label class="block text-[10px] font-mono font-bold uppercase tracking-widest mb-2 text-gray-500">NOVA SENHA</label>
                        <input type="password" name="password" placeholder="••••••••"
                               class="w-full border px-4 py-3 text-sm font-mono focus:outline-none focus:border-black transition-colors bg-white @error('password') border-red-500 @else border-[var(--color-lab-border)] @enderror" required>
                        <p class="text-[10px] text-gray-400 mt-1 font-mono">MÍNIMO 8 CARACTERES</p>
                    </div>

                    <div>
                        <label class="block text-[10px] font-mono font-bold uppercase tracking-widest mb-2 text-gray-500">CONFIRMAR SENHA</label>
                        <input type="password" name="password_confirmation" placeholder="••••••••"
                               class="w-full border border-[var(--color-lab-border)] px-4 py-3 text-sm font-mono focus:outline-none focus:border-black transition-colors bg-white" required>
                    </div>

                    <button type="submit" class="w-full bg-black text-white py-4 font-bold tracking-widest uppercase text-sm hover:bg-gray-800 transition-colors">
                        SALVAR SENHA
                    </button>
                </form>
            </div>
        </div>
    </main>

    @include('includes.footer')
</body>
</html>
