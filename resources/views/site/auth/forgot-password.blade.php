<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <link rel="icon" type="image/png" href="{{ asset('favicon.png') }}">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="robots" content="noindex, nofollow">
    <title>Redefinir Senha - JFXTECH</title>
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
                    <h1 class="text-2xl font-bold tracking-tight uppercase mb-1">REDEFINIR SENHA</h1>
                    <p class="text-gray-500 text-sm font-mono">ENVIAREMOS UM LINK PARA SEU E-MAIL</p>
                </div>

                <form method="POST" action="{{ route('site.password.email') }}" class="space-y-5">
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
                        <label class="block text-[10px] font-mono font-bold uppercase tracking-widest mb-2 text-gray-500">EMAIL</label>
                        <input type="email" name="email" value="{{ old('email') }}" placeholder="seu@email.com"
                               class="w-full border px-4 py-3 text-sm font-mono focus:outline-none focus:border-black transition-colors bg-white @error('email') border-red-500 @else border-[var(--color-lab-border)] @enderror" required>
                        @error('email')
                            <p class="text-red-500 text-xs mt-1 font-mono">{{ $message }}</p>
                        @enderror
                    </div>

                    <button type="submit" class="w-full bg-black text-white py-4 font-bold tracking-widest uppercase text-sm hover:bg-gray-800 transition-colors">
                        ENVIAR LINK
                    </button>
                </form>

                <div class="mt-6 text-center">
                    <a href="{{ route('site.login') }}" class="font-bold text-black hover:text-gray-600 transition-colors uppercase tracking-wider text-xs">VOLTAR AO LOGIN</a>
                </div>
            </div>
        </div>
    </main>

    @include('includes.footer')
</body>
</html>
