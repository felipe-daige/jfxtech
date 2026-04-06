{{-- resources/views/site/afiliados/comissoes.blade.php --}}
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Minhas Comissões — JFXTECH</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <link rel="stylesheet" href="{{ asset('css/site-styles.css') }}">
</head>
<body class="bg-white text-black font-mono">
@include('includes.header')

<main class="max-w-4xl mx-auto px-4 py-10">
    <div class="flex items-center justify-between mb-6">
        <h1 class="text-2xl font-bold uppercase tracking-widest">Minhas Comissões</h1>
        <a href="{{ route('afiliados.painel') }}" class="text-xs underline">← Painel</a>
    </div>

    <div class="grid grid-cols-3 gap-4 mb-8">
        @foreach([
            ['Pendente', $totais['pendente']],
            ['Aprovado', $totais['aprovado']],
            ['Pago', $totais['pago']],
        ] as [$label, $val])
        <div class="border border-black p-4 text-center">
            <p class="text-xs uppercase tracking-widest text-gray-500 mb-1">{{ $label }}</p>
            <p class="text-lg font-bold">R$ {{ number_format($val, 2, ',', '.') }}</p>
        </div>
        @endforeach
    </div>

    @if($comissoes->isEmpty())
        <p class="text-sm text-gray-400">Nenhuma comissão registrada ainda.</p>
    @else
        <table class="w-full text-sm border-collapse">
            <thead>
                <tr class="border-b border-black">
                    <th class="text-left py-2 text-xs uppercase tracking-widest">Data</th>
                    <th class="text-left py-2 text-xs uppercase tracking-widest">Pedido</th>
                    <th class="text-left py-2 text-xs uppercase tracking-widest">Valor</th>
                    <th class="text-left py-2 text-xs uppercase tracking-widest">Status</th>
                    <th class="text-left py-2 text-xs uppercase tracking-widest">Elegível em</th>
                </tr>
            </thead>
            <tbody>
                @foreach($comissoes as $com)
                <tr class="border-b border-gray-100 hover:bg-gray-50">
                    <td class="py-2">{{ $com->created_at->format('d/m/Y') }}</td>
                    <td class="py-2">#{{ $com->pedido_id }}</td>
                    <td class="py-2 font-bold">R$ {{ number_format($com->valor, 2, ',', '.') }}</td>
                    <td class="py-2 uppercase text-xs tracking-widest">{{ $com->status }}</td>
                    <td class="py-2">{{ $com->eligible_at?->format('d/m/Y') ?? '—' }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
        <div class="mt-4">{{ $comissoes->links() }}</div>
    @endif
</main>

@include('includes.footer')
</body>
</html>
