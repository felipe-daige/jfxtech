@extends('includes.header-admin')

@section('title', 'Sorteios')

@section('content')
<div class="space-y-6">
    <div class="flex flex-col lg:flex-row lg:items-end lg:justify-between gap-4">
        <div>
            <p class="font-mono text-[10px] uppercase tracking-widest text-[var(--color-lab-muted)] mb-2">Leads e campanhas</p>
            <h1 class="font-mono text-2xl font-bold uppercase tracking-widest">Sorteios</h1>
        </div>
        <a href="{{ route('site.sorteio.index') }}" target="_blank" class="inline-flex justify-center border border-black px-5 py-3 font-mono text-xs uppercase tracking-widest hover:bg-black hover:text-white transition-colors">
            Ver rota do usuario
        </a>
    </div>

    <div class="border border-[var(--color-lab-border)] bg-white">
        <div class="px-5 py-4 border-b border-[var(--color-lab-border)]">
            <h2 class="font-mono text-sm font-bold uppercase tracking-widest">Novo sorteio</h2>
        </div>
        <form method="POST" action="{{ route('admin.sorteios.store') }}" class="p-5 grid grid-cols-1 lg:grid-cols-2 gap-4">
            @csrf
            <div>
                <label class="block text-xs font-mono uppercase tracking-widest mb-1">Titulo *</label>
                <input type="text" name="titulo" value="{{ old('titulo') }}" class="w-full border border-[var(--color-lab-border)] px-3 py-2 font-mono focus:outline-none focus:border-black" required>
            </div>
            <div>
                <label class="block text-xs font-mono uppercase tracking-widest mb-1">Slug</label>
                <input type="text" name="slug" value="{{ old('slug') }}" placeholder="gerado automaticamente" class="w-full border border-[var(--color-lab-border)] px-3 py-2 font-mono focus:outline-none focus:border-black">
            </div>
            <div>
                <label class="block text-xs font-mono uppercase tracking-widest mb-1">Premio</label>
                <input type="text" name="premio" value="{{ old('premio') }}" class="w-full border border-[var(--color-lab-border)] px-3 py-2 font-mono focus:outline-none focus:border-black">
            </div>
            <div>
                <label class="block text-xs font-mono uppercase tracking-widest mb-1">Produto da loja</label>
                <select name="produto_id" class="w-full border border-[var(--color-lab-border)] px-3 py-2 font-mono focus:outline-none focus:border-black">
                    <option value="">Sem produto vinculado</option>
                    @foreach($produtos as $produto)
                        <option value="{{ $produto->id }}" @selected((int) old('produto_id') === (int) $produto->id)>
                            #{{ $produto->id }} - {{ $produto->nome }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-xs font-mono uppercase tracking-widest mb-1">URL do post no Instagram</label>
                <input type="url" name="instagram_post_url" value="{{ old('instagram_post_url') }}" class="w-full border border-[var(--color-lab-border)] px-3 py-2 font-mono focus:outline-none focus:border-black">
            </div>
            <div>
                <label class="block text-xs font-mono uppercase tracking-widest mb-1">Inicio</label>
                <input type="datetime-local" name="starts_at" value="{{ old('starts_at') }}" class="w-full border border-[var(--color-lab-border)] px-3 py-2 font-mono focus:outline-none focus:border-black">
            </div>
            <div>
                <label class="block text-xs font-mono uppercase tracking-widest mb-1">Fim</label>
                <input type="datetime-local" name="ends_at" value="{{ old('ends_at') }}" class="w-full border border-[var(--color-lab-border)] px-3 py-2 font-mono focus:outline-none focus:border-black">
            </div>
            <div>
                <label class="block text-xs font-mono uppercase tracking-widest mb-1">Numero inicial *</label>
                <input type="number" name="numero_inicial" value="{{ old('numero_inicial', 1) }}" min="1" class="w-full border border-[var(--color-lab-border)] px-3 py-2 font-mono focus:outline-none focus:border-black" required>
            </div>
            <div>
                <label class="block text-xs font-mono uppercase tracking-widest mb-1">Limite de participantes</label>
                <input type="number" name="max_participantes" value="{{ old('max_participantes') }}" min="1" class="w-full border border-[var(--color-lab-border)] px-3 py-2 font-mono focus:outline-none focus:border-black">
            </div>
            <div class="lg:col-span-2">
                <label class="block text-xs font-mono uppercase tracking-widest mb-1">Descricao</label>
                <textarea name="descricao" rows="3" class="w-full border border-[var(--color-lab-border)] px-3 py-2 font-mono focus:outline-none focus:border-black">{{ old('descricao') }}</textarea>
            </div>
            <div class="lg:col-span-2 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <label class="flex items-center gap-2 text-xs font-mono uppercase tracking-widest">
                    <input type="checkbox" name="ativo" value="1" checked class="w-4 h-4">
                    Ativo
                </label>
                <button type="submit" class="bg-black text-white px-6 py-3 font-mono text-xs uppercase tracking-widest hover:bg-gray-900 transition-colors">
                    Criar sorteio
                </button>
            </div>
        </form>
    </div>

    <div class="grid grid-cols-1 xl:grid-cols-2 gap-4">
        @forelse($sorteios as $sorteio)
            <article class="border border-[var(--color-lab-border)] bg-white">
                <div class="px-5 py-4 border-b border-[var(--color-lab-border)] flex items-start justify-between gap-4">
                    <div>
                        <div class="flex items-center gap-2 mb-1">
                            <h2 class="font-mono font-bold uppercase tracking-widest">{{ $sorteio->titulo }}</h2>
                            <span class="px-2 py-0.5 text-[10px] font-mono uppercase tracking-widest border {{ $sorteio->ativo ? 'bg-black text-white border-black' : 'border-gray-300 text-gray-400' }}">
                                {{ $sorteio->ativo ? 'Ativo' : 'Inativo' }}
                            </span>
                        </div>
                        <p class="font-mono text-xs text-[var(--color-lab-muted)]">/{{ $sorteio->slug }} · {{ $sorteio->participantes_count }} participantes</p>
                    </div>
                    <a href="{{ route('admin.sorteios.show', $sorteio) }}" class="shrink-0 bg-black text-white px-4 py-2 font-mono text-[10px] uppercase tracking-widest hover:bg-gray-900 transition-colors">
                        Gerenciar
                    </a>
                </div>

                <div class="p-5 space-y-4">
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 text-sm">
                        <div class="border border-[var(--color-lab-border)] p-3">
                            <p class="font-mono text-[10px] uppercase tracking-widest text-[var(--color-lab-muted)] mb-1">Premio</p>
                            <p class="font-semibold">{{ $sorteio->premio ?: 'Nao informado' }}</p>
                        </div>
                        <div class="border border-[var(--color-lab-border)] p-3">
                            <p class="font-mono text-[10px] uppercase tracking-widest text-[var(--color-lab-muted)] mb-1">Produto</p>
                            @if($sorteio->produto)
                                <p class="font-semibold">{{ $sorteio->produto->nome }}</p>
                                <p class="font-mono text-xs text-[var(--color-lab-muted)]">R$ {{ number_format($sorteio->produto->preco, 2, ',', '.') }}</p>
                            @else
                                <p class="text-gray-500">Sem vinculo</p>
                            @endif
                        </div>
                        <div class="border border-[var(--color-lab-border)] p-3">
                            <p class="font-mono text-[10px] uppercase tracking-widest text-[var(--color-lab-muted)] mb-1">Resultado</p>
                            @if($sorteio->resultadoPublicado() && $sorteio->ganhador)
                                <p class="font-semibold">#{{ $sorteio->ganhador->numeroFormatado() }} · {{ $sorteio->ganhador->user?->name }}</p>
                            @elseif($sorteio->ganhador)
                                <p class="font-semibold">Candidato #{{ $sorteio->ganhador->numeroFormatado() }}</p>
                            @else
                                <p class="text-gray-500">Nao publicado</p>
                            @endif
                        </div>
                    </div>

                    <div class="flex flex-col sm:flex-row gap-2">
                        <a href="{{ route('site.sorteio.show', $sorteio) }}" target="_blank" class="inline-flex justify-center border border-black px-4 py-2 font-mono text-[10px] uppercase tracking-widest hover:bg-black hover:text-white transition-colors">
                            Rota publica
                        </a>
                        <a href="{{ route('admin.sorteios.show', $sorteio) }}" class="inline-flex justify-center border border-[var(--color-lab-border)] px-4 py-2 font-mono text-[10px] uppercase tracking-widest hover:border-black transition-colors">
                            Participantes
                        </a>
                    </div>

                    <details class="border border-[var(--color-lab-border)]">
                        <summary class="cursor-pointer px-4 py-3 font-mono text-xs uppercase tracking-widest hover:bg-gray-50">Editar configuracao</summary>
                        <form method="POST" action="{{ route('admin.sorteios.update', $sorteio) }}" class="p-4 grid grid-cols-1 sm:grid-cols-2 gap-3 border-t border-[var(--color-lab-border)]">
                            @csrf
                            @method('PUT')
                            <input type="text" name="titulo" value="{{ old('titulo', $sorteio->titulo) }}" class="border border-[var(--color-lab-border)] px-3 py-2 font-mono text-sm focus:outline-none focus:border-black" required>
                            <input type="text" name="slug" value="{{ old('slug', $sorteio->slug) }}" class="border border-[var(--color-lab-border)] px-3 py-2 font-mono text-sm focus:outline-none focus:border-black">
                            <input type="text" name="premio" value="{{ old('premio', $sorteio->premio) }}" class="border border-[var(--color-lab-border)] px-3 py-2 font-mono text-sm focus:outline-none focus:border-black" placeholder="Premio">
                            <select name="produto_id" class="border border-[var(--color-lab-border)] px-3 py-2 font-mono text-sm focus:outline-none focus:border-black">
                                <option value="">Sem produto vinculado</option>
                                @foreach($produtos as $produto)
                                    <option value="{{ $produto->id }}" @selected((int) old('produto_id', $sorteio->produto_id) === (int) $produto->id)>
                                        #{{ $produto->id }} - {{ $produto->nome }}
                                    </option>
                                @endforeach
                            </select>
                            <input type="url" name="instagram_post_url" value="{{ old('instagram_post_url', $sorteio->instagram_post_url) }}" class="border border-[var(--color-lab-border)] px-3 py-2 font-mono text-sm focus:outline-none focus:border-black" placeholder="URL Instagram">
                            <input type="datetime-local" name="starts_at" value="{{ old('starts_at', $sorteio->starts_at?->format('Y-m-d\TH:i')) }}" class="border border-[var(--color-lab-border)] px-3 py-2 font-mono text-sm focus:outline-none focus:border-black">
                            <input type="datetime-local" name="ends_at" value="{{ old('ends_at', $sorteio->ends_at?->format('Y-m-d\TH:i')) }}" class="border border-[var(--color-lab-border)] px-3 py-2 font-mono text-sm focus:outline-none focus:border-black">
                            <input type="number" name="numero_inicial" value="{{ old('numero_inicial', $sorteio->numero_inicial) }}" min="1" class="border border-[var(--color-lab-border)] px-3 py-2 font-mono text-sm focus:outline-none focus:border-black" required>
                            <input type="number" name="max_participantes" value="{{ old('max_participantes', $sorteio->max_participantes) }}" min="1" class="border border-[var(--color-lab-border)] px-3 py-2 font-mono text-sm focus:outline-none focus:border-black" placeholder="Limite">
                            <textarea name="descricao" rows="3" class="sm:col-span-2 border border-[var(--color-lab-border)] px-3 py-2 font-mono text-sm focus:outline-none focus:border-black">{{ old('descricao', $sorteio->descricao) }}</textarea>
                            <div class="sm:col-span-2 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                                <label class="flex items-center gap-2 text-xs font-mono uppercase tracking-widest">
                                    <input type="checkbox" name="ativo" value="1" class="w-4 h-4" @checked(old('ativo', $sorteio->ativo))>
                                    Ativo
                                </label>
                                <div class="flex gap-2">
                                    <button type="submit" class="bg-black text-white px-5 py-2 font-mono text-xs uppercase tracking-widest hover:bg-gray-900">Salvar</button>
                                </div>
                            </div>
                        </form>
                    </details>

                    <form method="POST" action="{{ route('admin.sorteios.destroy', $sorteio) }}" onsubmit="return confirm('Excluir este sorteio e todas as participacoes?');">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="text-red-600 font-mono text-[10px] uppercase tracking-widest hover:underline">Excluir sorteio</button>
                    </form>
                </div>
            </article>
        @empty
            <div class="xl:col-span-2 border border-[var(--color-lab-border)] bg-white p-10 text-center font-mono text-sm text-[var(--color-lab-muted)]">
                Nenhum sorteio cadastrado.
            </div>
        @endforelse
    </div>

    {{ $sorteios->links() }}
</div>
@endsection
