{{-- resources/views/site/afiliados/painel.blade.php --}}
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Painel Afiliado — JFXTECH</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <link rel="stylesheet" href="{{ asset('css/site-styles.css') }}">
</head>
<body class="bg-white text-black font-mono">
@include('includes.header')

<main class="max-w-4xl mx-auto px-4 py-10">
    <h1 class="text-2xl font-bold uppercase tracking-widest mb-8">Painel Afiliado</h1>

    @if(session('success'))
        <div class="border border-black bg-white p-4 mb-6 text-sm">{{ session('success') }}</div>
    @endif

    @if($affiliate->status === 'pendente')
        <div class="border border-black p-8 text-center">
            <p class="text-sm uppercase tracking-widest text-gray-500 mb-2">Status</p>
            <p class="text-lg font-bold uppercase">Solicitação em análise</p>
            <p class="text-sm text-gray-500 mt-2">O administrador avaliará sua solicitação em breve.</p>
        </div>
    @else
        {{-- Referral link --}}
        <div class="border border-black p-6 mb-8">
            <p class="text-xs uppercase tracking-widest text-gray-500 mb-2">Seu Link de Indicação</p>
            <div class="flex items-center gap-3">
                <input id="linkAfiliado" type="text" readonly
                    value="{{ $linkIndicacao }}"
                    class="flex-1 border border-gray-300 px-3 py-2 text-sm font-mono bg-gray-50 focus:outline-none">
                <button onclick="copiarLink()" class="border border-black px-4 py-2 text-xs uppercase tracking-widest hover:bg-black hover:text-white transition-colors">
                    Copiar
                </button>
            </div>
            <p class="text-xs text-gray-400 mt-2">Código: <strong>{{ $affiliate->codigo }}</strong></p>
        </div>

        {{-- Stats cards --}}
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-8">
            @foreach([
                ['Total indicações', $stats['total_indicacoes']],
                ['Convertidas', $stats['convertidas']],
                ['Comissões pendentes', 'R$ ' . number_format($stats['comissoes_pendentes'], 2, ',', '.')],
                ['Comissões pagas', 'R$ ' . number_format($stats['comissoes_pagas'], 2, ',', '.')],
            ] as [$label, $val])
            <div class="border border-black p-4 text-center">
                <p class="text-xs uppercase tracking-widest text-gray-500 mb-1">{{ $label }}</p>
                <p class="text-xl font-bold">{{ $val }}</p>
            </div>
            @endforeach
        </div>

        {{-- Recent referrals + commissions --}}
        <div class="grid md:grid-cols-2 gap-6">
            <div>
                <div class="flex items-center justify-between mb-3">
                    <h2 class="text-xs uppercase tracking-widest font-bold">Últimas indicações</h2>
                    <a href="{{ route('afiliados.indicacoes') }}" class="text-xs underline">Ver todas</a>
                </div>
                @forelse($ultimasIndicacoes as $ref)
                <div class="border-b border-gray-100 py-2 flex justify-between items-center text-sm">
                    <span class="text-gray-700">{{ substr($ref->referredUser?->name ?? 'Usuário', 0, 3) }}***</span>
                    <span class="text-xs uppercase tracking-widest {{ $ref->status === 'convertido' ? 'text-black' : 'text-gray-400' }}">
                        {{ $ref->status }}
                    </span>
                </div>
                @empty
                <p class="text-sm text-gray-400">Nenhuma indicação ainda.</p>
                @endforelse
            </div>

            <div>
                <div class="flex items-center justify-between mb-3">
                    <h2 class="text-xs uppercase tracking-widest font-bold">Últimas comissões</h2>
                    <a href="{{ route('afiliados.comissoes') }}" class="text-xs underline">Ver todas</a>
                </div>
                @forelse($ultimasComissoes as $com)
                <div class="border-b border-gray-100 py-2 flex justify-between items-center text-sm">
                    <span>R$ {{ number_format($com->valor, 2, ',', '.') }}</span>
                    <span class="text-xs uppercase tracking-widest {{ $com->status === 'pago' ? 'text-black' : 'text-gray-400' }}">
                        {{ $com->status }}
                    </span>
                </div>
                @empty
                <p class="text-sm text-gray-400">Nenhuma comissão ainda.</p>
                @endforelse
            </div>
        </div>
    @endif
</main>

@include('includes.footer')
<script src="{{ asset('js/afiliados.js') }}"></script>
</body>
</html>
