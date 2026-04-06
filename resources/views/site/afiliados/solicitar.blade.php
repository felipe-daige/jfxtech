{{-- resources/views/site/afiliados/solicitar.blade.php --}}
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tornar-se Afiliado — JFXTECH</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <link rel="stylesheet" href="{{ asset('css/site-styles.css') }}">
</head>
<body class="bg-white text-black font-mono">
@include('includes.header')

<main class="max-w-xl mx-auto px-4 py-10">
    <h1 class="text-2xl font-bold uppercase tracking-widest mb-2">Tornar-se Afiliado</h1>
    <p class="text-sm text-gray-500 mb-8">Indique clientes e ganhe comissão nas vendas geradas.</p>

    @if($errors->any())
        <div class="border border-black p-4 mb-6 text-sm">
            @foreach($errors->all() as $error)
                <p>{{ $error }}</p>
            @endforeach
        </div>
    @endif

    <form method="POST" action="{{ route('afiliados.registrar') }}" class="space-y-5">
        @csrf
        <div>
            <label class="block text-xs uppercase tracking-widest mb-1">Chave PIX (opcional)</label>
            <input type="text" name="pix_key" value="{{ old('pix_key') }}"
                placeholder="CPF, e-mail, telefone ou chave aleatória"
                class="w-full border border-black px-3 py-2 text-sm focus:outline-none focus:ring-1 focus:ring-black">
        </div>
        <div>
            <label class="block text-xs uppercase tracking-widest mb-1">Dados bancários (opcional)</label>
            <textarea name="bank_info" rows="3"
                placeholder="Banco, agência, conta..."
                class="w-full border border-black px-3 py-2 text-sm focus:outline-none focus:ring-1 focus:ring-black">{{ old('bank_info') }}</textarea>
        </div>
        <button type="submit"
            class="w-full border border-black bg-black text-white py-3 text-xs uppercase tracking-widest hover:bg-white hover:text-black transition-colors">
            Enviar Solicitação
        </button>
    </form>
</main>

@include('includes.footer')
</body>
</html>
