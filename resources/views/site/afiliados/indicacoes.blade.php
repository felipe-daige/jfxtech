{{-- resources/views/site/afiliados/indicacoes.blade.php --}}
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Minhas Indicações — JFXTECH</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <link rel="stylesheet" href="{{ asset('css/site-styles.css') }}">
</head>
<body class="bg-white text-black font-mono">
@include('includes.header')

<main class="max-w-4xl mx-auto px-4 py-10">
    <div class="flex items-center justify-between mb-8">
        <h1 class="text-2xl font-bold uppercase tracking-widest">Minhas Indicações</h1>
        <a href="{{ route('afiliados.painel') }}" class="text-xs underline">← Painel</a>
    </div>

    @if($indicacoes->isEmpty())
        <p class="text-sm text-gray-400">Nenhuma indicação registrada ainda.</p>
    @else
        <table class="w-full text-sm border-collapse">
            <thead>
                <tr class="border-b border-black">
                    <th class="text-left py-2 text-xs uppercase tracking-widest">Usuário</th>
                    <th class="text-left py-2 text-xs uppercase tracking-widest">Data</th>
                    <th class="text-left py-2 text-xs uppercase tracking-widest">Status</th>
                    <th class="text-left py-2 text-xs uppercase tracking-widest">Convertido em</th>
                </tr>
            </thead>
            <tbody>
                @foreach($indicacoes as $ref)
                <tr class="border-b border-gray-100 hover:bg-gray-50">
                    <td class="py-2">{{ substr($ref->referredUser?->name ?? 'Usuário', 0, 3) }}***</td>
                    <td class="py-2">{{ $ref->created_at->format('d/m/Y') }}</td>
                    <td class="py-2 uppercase text-xs tracking-widest">{{ $ref->status }}</td>
                    <td class="py-2">{{ $ref->converted_at?->format('d/m/Y') ?? '—' }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
        <div class="mt-4">{{ $indicacoes->links() }}</div>
    @endif
</main>

@include('includes.footer')
</body>
</html>
