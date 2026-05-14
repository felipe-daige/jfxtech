@extends('includes.header-admin')

@section('title', 'Gerenciar Sorteio')

@section('content')
<div class="space-y-6">
    <div class="flex flex-col xl:flex-row xl:items-end xl:justify-between gap-4">
        <div>
            <p class="font-mono text-[10px] uppercase tracking-widest text-[var(--color-lab-muted)] mb-2">Sorteio</p>
            <h1 class="font-mono text-2xl font-bold uppercase tracking-widest">{{ $sorteio->titulo }}</h1>
            <p class="font-mono text-xs text-[var(--color-lab-muted)] mt-2">{{ route('site.sorteio.show', $sorteio) }}</p>
        </div>
        <div class="flex flex-col sm:flex-row gap-2">
            <a href="{{ route('site.sorteio.show', $sorteio) }}" target="_blank" class="inline-flex justify-center border border-black px-5 py-3 font-mono text-xs uppercase tracking-widest hover:bg-black hover:text-white transition-colors">
                Rota publica
            </a>
            <a href="{{ route('admin.sorteios.index') }}" class="inline-flex justify-center bg-black text-white px-5 py-3 font-mono text-xs uppercase tracking-widest hover:bg-gray-900 transition-colors">
                Voltar
            </a>
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <div class="border border-[var(--color-lab-border)] bg-white p-5">
            <p class="font-mono text-[10px] uppercase tracking-widest text-[var(--color-lab-muted)] mb-2">Total</p>
            <p class="font-mono text-3xl font-bold">{{ $statusCounts->sum() }}</p>
        </div>
        <div class="border border-[var(--color-lab-border)] bg-white p-5">
            <p class="font-mono text-[10px] uppercase tracking-widest text-[var(--color-lab-muted)] mb-2">Pendentes</p>
            <p class="font-mono text-3xl font-bold">{{ $statusCounts[\App\Models\SorteioParticipante::STATUS_PENDENTE] ?? 0 }}</p>
        </div>
        <div class="border border-[var(--color-lab-border)] bg-white p-5">
            <p class="font-mono text-[10px] uppercase tracking-widest text-[var(--color-lab-muted)] mb-2">Validados</p>
            <p class="font-mono text-3xl font-bold">{{ $statusCounts[\App\Models\SorteioParticipante::STATUS_VALIDADO] ?? 0 }}</p>
        </div>
        <div class="border border-[var(--color-lab-border)] bg-white p-5">
            <p class="font-mono text-[10px] uppercase tracking-widest text-[var(--color-lab-muted)] mb-2">Desclassificados</p>
            <p class="font-mono text-3xl font-bold">{{ $statusCounts[\App\Models\SorteioParticipante::STATUS_DESCLASSIFICADO] ?? 0 }}</p>
        </div>
    </div>

    <div class="grid grid-cols-1 xl:grid-cols-[0.95fr_1.05fr] gap-6">
        <section class="border border-[var(--color-lab-border)] bg-white">
            <div class="px-5 py-4 border-b border-[var(--color-lab-border)]">
                <h2 class="font-mono text-sm font-bold uppercase tracking-widest">Resultado</h2>
            </div>
            <div class="p-5 space-y-5">
                @if($sorteio->ganhador)
                    <div class="border border-black p-4">
                        <p class="font-mono text-[10px] uppercase tracking-widest text-[var(--color-lab-muted)] mb-2">
                            {{ $sorteio->resultadoPublicado() ? 'Resultado publicado' : 'Candidato sorteado' }}
                        </p>
                        <p class="font-mono text-3xl font-bold">#{{ $sorteio->ganhador->numeroFormatado() }}</p>
                        <p class="text-sm mt-2">{{ $sorteio->ganhador->user?->name }} · {{ $sorteio->ganhador->user?->email }}</p>
                        <p class="font-mono text-xs text-[var(--color-lab-muted)] mt-1">{{ '@'.ltrim((string) $sorteio->ganhador->instagram_username, '@') }}</p>
                        @if($sorteio->resultadoPublicado())
                            <p class="font-mono text-[10px] uppercase tracking-widest text-[var(--color-lab-muted)] mt-3">
                                Publicado em {{ $sorteio->resultado_publicado_at->format('d/m/Y H:i') }}
                            </p>
                        @endif
                    </div>
                @else
                    <div class="border border-[var(--color-lab-border)] p-4 text-sm text-[var(--color-lab-muted)]">
                        Nenhum candidato ou resultado definido.
                    </div>
                @endif

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <form method="POST" action="{{ route('admin.sorteios.sortear', $sorteio) }}">
                        @csrf
                        <button type="submit" class="w-full bg-black text-white px-5 py-3 font-mono text-xs uppercase tracking-widest hover:bg-gray-900 transition-colors">
                            Sortear candidato
                        </button>
                    </form>
                    <form method="POST" action="{{ route('admin.sorteios.resultado.limpar', $sorteio) }}">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="w-full border border-black px-5 py-3 font-mono text-xs uppercase tracking-widest hover:bg-black hover:text-white transition-colors">
                            Limpar resultado
                        </button>
                    </form>
                </div>

                <form method="POST" action="{{ route('admin.sorteios.resultado', $sorteio) }}" class="space-y-3">
                    @csrf
                    <div>
                        <label class="block text-xs font-mono uppercase tracking-widest mb-1">Publicar ganhador</label>
                        <select name="ganhador_participante_id" class="w-full border border-[var(--color-lab-border)] px-3 py-2 font-mono focus:outline-none focus:border-black" required>
                            <option value="">Selecione um participante</option>
                            @foreach($participantesParaResultado as $participante)
                                <option value="{{ $participante->id }}" @selected((int) old('ganhador_participante_id', $sorteio->ganhador_participante_id) === (int) $participante->id)>
                                    #{{ $participante->numeroFormatado() }} - {{ $participante->user?->name }} ({{ '@'.ltrim((string) $participante->instagram_username, '@') }})
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <button type="submit" class="w-full bg-black text-white px-5 py-3 font-mono text-xs uppercase tracking-widest hover:bg-gray-900 transition-colors">
                        Publicar resultado final
                    </button>
                </form>
            </div>
        </section>

        <section class="border border-[var(--color-lab-border)] bg-white">
            <div class="px-5 py-4 border-b border-[var(--color-lab-border)]">
                <h2 class="font-mono text-sm font-bold uppercase tracking-widest">Configuracao</h2>
            </div>
            <form method="POST" action="{{ route('admin.sorteios.update', $sorteio) }}" class="p-5 grid grid-cols-1 sm:grid-cols-2 gap-4">
                @csrf
                @method('PUT')
                <div>
                    <label class="block text-xs font-mono uppercase tracking-widest mb-1">Titulo *</label>
                    <input type="text" name="titulo" value="{{ old('titulo', $sorteio->titulo) }}" class="w-full border border-[var(--color-lab-border)] px-3 py-2 font-mono focus:outline-none focus:border-black" required>
                </div>
                <div>
                    <label class="block text-xs font-mono uppercase tracking-widest mb-1">Slug</label>
                    <input type="text" name="slug" value="{{ old('slug', $sorteio->slug) }}" class="w-full border border-[var(--color-lab-border)] px-3 py-2 font-mono focus:outline-none focus:border-black">
                </div>
                <div>
                    <label class="block text-xs font-mono uppercase tracking-widest mb-1">Premio</label>
                    <input type="text" name="premio" value="{{ old('premio', $sorteio->premio) }}" class="w-full border border-[var(--color-lab-border)] px-3 py-2 font-mono focus:outline-none focus:border-black">
                </div>
                <div>
                    <label class="block text-xs font-mono uppercase tracking-widest mb-1">Produto da loja</label>
                    <select name="produto_id" class="w-full border border-[var(--color-lab-border)] px-3 py-2 font-mono focus:outline-none focus:border-black">
                        <option value="">Sem produto vinculado</option>
                        @foreach($produtos as $produto)
                            <option value="{{ $produto->id }}" @selected((int) old('produto_id', $sorteio->produto_id) === (int) $produto->id)>
                                #{{ $produto->id }} - {{ $produto->nome }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-mono uppercase tracking-widest mb-1">Post Instagram</label>
                    <input type="url" name="instagram_post_url" value="{{ old('instagram_post_url', $sorteio->instagram_post_url) }}" class="w-full border border-[var(--color-lab-border)] px-3 py-2 font-mono focus:outline-none focus:border-black">
                </div>
                <div>
                    <label class="block text-xs font-mono uppercase tracking-widest mb-1">Inicio</label>
                    <input type="datetime-local" name="starts_at" value="{{ old('starts_at', $sorteio->starts_at?->format('Y-m-d\TH:i')) }}" class="w-full border border-[var(--color-lab-border)] px-3 py-2 font-mono focus:outline-none focus:border-black">
                </div>
                <div>
                    <label class="block text-xs font-mono uppercase tracking-widest mb-1">Fim</label>
                    <input type="datetime-local" name="ends_at" value="{{ old('ends_at', $sorteio->ends_at?->format('Y-m-d\TH:i')) }}" class="w-full border border-[var(--color-lab-border)] px-3 py-2 font-mono focus:outline-none focus:border-black">
                </div>
                <div>
                    <label class="block text-xs font-mono uppercase tracking-widest mb-1">Numero inicial *</label>
                    <input type="number" name="numero_inicial" value="{{ old('numero_inicial', $sorteio->numero_inicial) }}" min="1" class="w-full border border-[var(--color-lab-border)] px-3 py-2 font-mono focus:outline-none focus:border-black" required>
                </div>
                <div>
                    <label class="block text-xs font-mono uppercase tracking-widest mb-1">Limite</label>
                    <input type="number" name="max_participantes" value="{{ old('max_participantes', $sorteio->max_participantes) }}" min="1" class="w-full border border-[var(--color-lab-border)] px-3 py-2 font-mono focus:outline-none focus:border-black">
                </div>
                <div class="sm:col-span-2">
                    <label class="block text-xs font-mono uppercase tracking-widest mb-1">Descricao</label>
                    <textarea name="descricao" rows="3" class="w-full border border-[var(--color-lab-border)] px-3 py-2 font-mono focus:outline-none focus:border-black">{{ old('descricao', $sorteio->descricao) }}</textarea>
                </div>
                <div class="sm:col-span-2 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                    <label class="flex items-center gap-2 text-xs font-mono uppercase tracking-widest">
                        <input type="checkbox" name="ativo" value="1" class="w-4 h-4" @checked(old('ativo', $sorteio->ativo))>
                        Ativo
                    </label>
                    <button type="submit" class="bg-black text-white px-6 py-3 font-mono text-xs uppercase tracking-widest hover:bg-gray-900 transition-colors">
                        Salvar configuracao
                    </button>
                </div>
            </form>
        </section>
    </div>

    <section class="border border-[var(--color-lab-border)] bg-white">
        <div class="px-5 py-4 border-b border-[var(--color-lab-border)] flex flex-col xl:flex-row xl:items-center xl:justify-between gap-4">
            <h2 class="font-mono text-sm font-bold uppercase tracking-widest">Participantes</h2>
            <form method="GET" action="{{ route('admin.sorteios.show', $sorteio) }}" class="grid grid-cols-1 sm:grid-cols-[1fr_auto_auto] gap-2 w-full xl:w-auto">
                <input type="text" name="q" value="{{ request('q') }}" placeholder="Nome, email, CPF, Instagram ou numero" class="border border-[var(--color-lab-border)] px-3 py-2 font-mono text-xs focus:outline-none focus:border-black">
                <select name="status" class="border border-[var(--color-lab-border)] px-3 py-2 font-mono text-xs focus:outline-none focus:border-black">
                    <option value="">Todos</option>
                    @foreach($statusLabels as $value => $label)
                        <option value="{{ $value }}" @selected(request('status') === $value)>{{ $label }}</option>
                    @endforeach
                </select>
                <button type="submit" class="bg-black text-white px-4 py-2 font-mono text-xs uppercase tracking-widest hover:bg-gray-900 transition-colors">Filtrar</button>
            </form>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-[1100px] w-full text-sm">
                <thead class="bg-gray-50 border-b border-[var(--color-lab-border)]">
                    <tr class="text-left font-mono text-[10px] uppercase tracking-widest text-[var(--color-lab-muted)]">
                        <th class="px-4 py-3">Numero</th>
                        <th class="px-4 py-3">Cliente</th>
                        <th class="px-4 py-3">Contato</th>
                        <th class="px-4 py-3">Instagram</th>
                        <th class="px-4 py-3">Status</th>
                        <th class="px-4 py-3">Auditoria</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-[var(--color-lab-border)]">
                    @forelse($participantes as $participante)
                        <tr class="{{ (int) $sorteio->ganhador_participante_id === (int) $participante->id ? 'bg-yellow-50' : '' }}">
                            <td class="px-4 py-4 align-top">
                                <span class="font-mono text-lg font-bold">#{{ $participante->numeroFormatado() }}</span>
                                @if((int) $sorteio->ganhador_participante_id === (int) $participante->id)
                                    <p class="font-mono text-[10px] uppercase tracking-widest text-yellow-700 mt-1">Selecionado</p>
                                @endif
                            </td>
                            <td class="px-4 py-4 align-top">
                                <p class="font-semibold">{{ $participante->user?->name }}</p>
                                <p class="font-mono text-xs text-[var(--color-lab-muted)]">ID usuario: {{ $participante->user_id }}</p>
                                <p class="font-mono text-xs text-[var(--color-lab-muted)]">CPF: {{ $participante->user?->cpf ?: '-' }}</p>
                            </td>
                            <td class="px-4 py-4 align-top">
                                <p class="font-mono text-xs">{{ $participante->user?->email }}</p>
                                <p class="font-mono text-xs text-[var(--color-lab-muted)]">{{ $participante->user?->phone ?: '-' }}</p>
                            </td>
                            <td class="px-4 py-4 align-top">
                                <p class="font-mono">{{ '@'.ltrim((string) $participante->instagram_username, '@') }}</p>
                                <p class="font-mono text-xs text-[var(--color-lab-muted)]">{{ '@'.ltrim((string) $participante->instagram_friend_1, '@') }} / {{ '@'.ltrim((string) $participante->instagram_friend_2, '@') }}</p>
                            </td>
                            <td class="px-4 py-4 align-top">
                                <span class="inline-flex px-2 py-1 font-mono text-[10px] uppercase tracking-widest border {{ $participante->status === \App\Models\SorteioParticipante::STATUS_VALIDADO ? 'border-black text-black' : ($participante->status === \App\Models\SorteioParticipante::STATUS_DESCLASSIFICADO ? 'border-red-300 text-red-600' : 'border-gray-300 text-gray-500') }}">
                                    {{ $participante->statusLabel() }}
                                </span>
                                <p class="font-mono text-[10px] text-[var(--color-lab-muted)] mt-2">{{ $participante->created_at->format('d/m/Y H:i') }}</p>
                            </td>
                            <td class="px-4 py-4 align-top">
                                <form method="POST" action="{{ route('admin.sorteios.participantes.update', [$sorteio, $participante]) }}" class="space-y-2 min-w-64">
                                    @csrf
                                    @method('PUT')
                                    <select name="status" class="w-full border border-[var(--color-lab-border)] px-3 py-2 font-mono text-xs focus:outline-none focus:border-black">
                                        @foreach($statusLabels as $value => $label)
                                            <option value="{{ $value }}" @selected($participante->status === $value)>{{ $label }}</option>
                                        @endforeach
                                    </select>
                                    <textarea name="audit_notes" rows="2" placeholder="Observacoes da auditoria" class="w-full border border-[var(--color-lab-border)] px-3 py-2 font-mono text-xs focus:outline-none focus:border-black">{{ $participante->audit_notes }}</textarea>
                                    <button type="submit" class="w-full border border-black px-3 py-2 font-mono text-[10px] uppercase tracking-widest hover:bg-black hover:text-white transition-colors">
                                        Salvar auditoria
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-4 py-10 text-center font-mono text-sm text-[var(--color-lab-muted)]">
                                Nenhum participante encontrado.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="px-5 py-4 border-t border-[var(--color-lab-border)]">
            {{ $participantes->links() }}
        </div>
    </section>
</div>
@endsection
