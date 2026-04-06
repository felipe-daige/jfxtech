{{-- resources/views/admin/afiliados/index.blade.php --}}
@extends('includes.header-admin')
@section('title', 'Afiliados')
@section('content')
<div class="p-4 md:p-6 space-y-6">

    {{-- Header --}}
    <div class="flex items-center justify-between">
        <h1 class="font-mono text-lg font-bold uppercase tracking-widest">Afiliados</h1>
        <a href="{{ route('admin.afiliados.configuracoes') }}"
           class="border border-black px-3 py-1.5 font-mono text-xs uppercase tracking-widest hover:bg-black hover:text-white transition-colors">
            Configurações
        </a>
    </div>

    @if(session('success'))
        <div class="border border-black bg-white p-4 font-mono text-sm">{{ session('success') }}</div>
    @endif

    {{-- SSE Metrics Cards --}}
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
        <div class="border border-black p-4 text-center">
            <p class="font-mono text-[10px] uppercase tracking-widest text-gray-500 mb-1">Afiliados ativos</p>
            <p id="sse-ativos" class="font-mono text-2xl font-bold">{{ $metrics['ativos'] }}</p>
        </div>
        <div class="border border-black p-4 text-center">
            <p class="font-mono text-[10px] uppercase tracking-widest text-gray-500 mb-1">Indicações hoje</p>
            <p id="sse-indicacoes-hoje" class="font-mono text-2xl font-bold">{{ $metrics['indicacoes_hoje'] }}</p>
        </div>
        <div class="border border-black p-4 text-center">
            <p class="font-mono text-[10px] uppercase tracking-widest text-gray-500 mb-1">Comissões pendentes</p>
            <p id="sse-pendentes" class="font-mono text-2xl font-bold">R$ {{ number_format($metrics['comissoes_pendentes'], 2, ',', '.') }}</p>
        </div>
        <div class="border border-black p-4 text-center">
            <p class="font-mono text-[10px] uppercase tracking-widest text-gray-500 mb-1">Comissões pagas</p>
            <p id="sse-pagas" class="font-mono text-2xl font-bold">R$ {{ number_format($metrics['comissoes_pagas'], 2, ',', '.') }}</p>
        </div>
    </div>

    {{-- Pending affiliates section --}}
    @if($metrics['pendentes'] > 0)
    <div class="border border-black p-4 bg-gray-50 font-mono text-sm">
        <strong>{{ $metrics['pendentes'] }}</strong> afiliado(s) aguardando aprovação.
    </div>
    @endif

    {{-- Affiliates table --}}
    <div class="border border-black overflow-x-auto">
        <table class="w-full text-xs font-mono border-collapse">
            <thead>
                <tr class="border-b border-black bg-gray-50">
                    <th class="text-left px-4 py-2 uppercase tracking-widest">Nome</th>
                    <th class="text-left px-4 py-2 uppercase tracking-widest">Código</th>
                    <th class="text-left px-4 py-2 uppercase tracking-widest">Indicações</th>
                    <th class="text-left px-4 py-2 uppercase tracking-widest">Comissão</th>
                    <th class="text-left px-4 py-2 uppercase tracking-widest">Status</th>
                    <th class="text-left px-4 py-2 uppercase tracking-widest">Ações</th>
                </tr>
            </thead>
            <tbody>
                @foreach($afiliados as $aff)
                <tr class="border-b border-gray-100 hover:bg-gray-50">
                    <td class="px-4 py-2">
                        {{ $aff->user?->name ?? '—' }}<br>
                        <span class="text-gray-400">{{ $aff->user?->email ?? '—' }}</span>
                    </td>
                    <td class="px-4 py-2 font-bold">{{ $aff->codigo }}</td>
                    <td class="px-4 py-2">{{ $aff->referrals_count }} ({{ $aff->convertidas_count }} conv.)</td>
                    <td class="px-4 py-2">
                        @if($aff->commission_value)
                            {{ $aff->commission_type === 'percent' ? $aff->commission_value . '%' : 'R$ ' . number_format($aff->commission_value, 2, ',', '.') }}
                        @else
                            <span class="text-gray-400">Global</span>
                        @endif
                    </td>
                    <td class="px-4 py-2">
                        <span class="uppercase tracking-widest {{ $aff->status === 'ativo' ? 'text-black' : 'text-gray-400' }}">
                            {{ $aff->status }}
                        </span>
                    </td>
                    <td class="px-4 py-2 flex gap-2 flex-wrap">
                        @if($aff->status === 'pendente')
                        <form method="POST" action="{{ route('admin.afiliados.aprovar', $aff->id) }}">
                            @csrf
                            <button class="border border-black px-2 py-1 text-[10px] uppercase tracking-widest hover:bg-black hover:text-white transition-colors">Aprovar</button>
                        </form>
                        @endif
                        @if($aff->status === 'ativo')
                        <form method="POST" action="{{ route('admin.afiliados.suspender', $aff->id) }}">
                            @csrf
                            <button class="border border-black px-2 py-1 text-[10px] uppercase tracking-widest hover:bg-black hover:text-white transition-colors">Suspender</button>
                        </form>
                        @endif
                        <button onclick="abrirModalComissao({{ $aff->id }}, '{{ $aff->commission_type }}', '{{ $aff->commission_value ?? '' }}')"
                            class="border border-black px-2 py-1 text-[10px] uppercase tracking-widest hover:bg-black hover:text-white transition-colors">
                            Comissão
                        </button>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <div class="font-mono">{{ $afiliados->links() }}</div>

    <a href="{{ route('admin.afiliados.comissoes') }}"
       class="inline-block border border-black px-4 py-2 font-mono text-xs uppercase tracking-widest hover:bg-black hover:text-white transition-colors">
        Gerenciar Comissões →
    </a>
</div>

{{-- Modal: Edit commission --}}
<div id="modalComissao" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-black/50">
    <div class="bg-white border border-black p-6 w-full max-w-sm font-mono">
        <h2 class="text-xs uppercase tracking-widest font-bold mb-4">Editar Comissão</h2>
        <form id="formComissao" method="POST">
            @csrf
            <div class="mb-3">
                <label class="block text-[10px] uppercase tracking-widest mb-1">Tipo</label>
                <select id="mc-type" name="commission_type" class="w-full border border-black px-2 py-1 text-xs">
                    <option value="percent">Percentual (%)</option>
                    <option value="fixed">Fixo (R$)</option>
                </select>
            </div>
            <div class="mb-4">
                <label class="block text-[10px] uppercase tracking-widest mb-1">Valor (vazio = global)</label>
                <input id="mc-value" type="number" step="0.01" name="commission_value"
                    class="w-full border border-black px-2 py-1 text-xs">
            </div>
            <div class="flex gap-2">
                <button type="submit" class="flex-1 border border-black bg-black text-white py-2 text-[10px] uppercase tracking-widest hover:bg-white hover:text-black transition-colors">Salvar</button>
                <button type="button" onclick="fecharModalComissao()" class="flex-1 border border-black py-2 text-[10px] uppercase tracking-widest hover:bg-black hover:text-white transition-colors">Cancelar</button>
            </div>
        </form>
    </div>
</div>

<script src="{{ asset('js/afiliados-admin.js') }}?v={{ time() }}"></script>
<script>
    window.routes = window.routes || {};
    window.routes.adminAfiliadosComissao = '{{ route("admin.afiliados.comissao", ":id") }}';
    window.routes.adminAfiliadosStream   = '{{ route("admin.afiliados.stream") }}';
</script>
@endsection
