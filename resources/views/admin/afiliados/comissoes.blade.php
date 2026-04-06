{{-- resources/views/admin/afiliados/comissoes.blade.php --}}
@extends('includes.header-admin')
@section('title', 'Comissões — Afiliados')
@section('content')
<div class="p-4 md:p-6 space-y-6">

    <div class="flex items-center justify-between">
        <h1 class="font-mono text-lg font-bold uppercase tracking-widest">Comissões</h1>
        <a href="{{ route('admin.afiliados.index') }}" class="font-mono text-xs underline">← Afiliados</a>
    </div>

    @if(session('success'))
        <div class="border border-black bg-white p-4 font-mono text-sm">{{ session('success') }}</div>
    @endif

    {{-- Totals --}}
    <div class="grid grid-cols-3 gap-4">
        @foreach([
            ['Pendente', $totais['pendente']],
            ['Aprovado', $totais['aprovado']],
            ['Pago', $totais['pago']],
        ] as [$label, $val])
        <div class="border border-black p-4 text-center font-mono">
            <p class="text-[10px] uppercase tracking-widest text-gray-500 mb-1">{{ $label }}</p>
            <p class="text-xl font-bold">R$ {{ number_format($val, 2, ',', '.') }}</p>
        </div>
        @endforeach
    </div>

    {{-- Filter --}}
    <form method="GET" action="{{ route('admin.afiliados.comissoes') }}" class="flex gap-2">
        <select name="status" class="border border-black px-3 py-1.5 font-mono text-xs">
            <option value="">Todos os status</option>
            <option value="pendente" {{ request('status') === 'pendente' ? 'selected' : '' }}>Pendente</option>
            <option value="aprovado" {{ request('status') === 'aprovado' ? 'selected' : '' }}>Aprovado</option>
            <option value="pago"     {{ request('status') === 'pago' ? 'selected' : '' }}>Pago</option>
            <option value="rejeitado" {{ request('status') === 'rejeitado' ? 'selected' : '' }}>Rejeitado</option>
        </select>
        <button class="border border-black px-3 py-1.5 font-mono text-xs uppercase tracking-widest hover:bg-black hover:text-white transition-colors">Filtrar</button>
    </form>

    {{-- Bulk form --}}
    <form id="bulkForm" method="POST" action="{{ route('admin.afiliados.comissoes.bulk') }}">
        @csrf
        <input type="hidden" name="action" id="bulkAction" value="">
        <div class="border border-black overflow-x-auto">
            <table class="w-full text-xs font-mono border-collapse">
                <thead>
                    <tr class="border-b border-black bg-gray-50">
                        <th class="px-4 py-2"><input type="checkbox" id="selectAll" onclick="toggleAll()"></th>
                        <th class="text-left px-4 py-2 uppercase tracking-widest">Afiliado</th>
                        <th class="text-left px-4 py-2 uppercase tracking-widest">Pedido</th>
                        <th class="text-left px-4 py-2 uppercase tracking-widest">Valor</th>
                        <th class="text-left px-4 py-2 uppercase tracking-widest">Status</th>
                        <th class="text-left px-4 py-2 uppercase tracking-widest">Elegível em</th>
                        <th class="text-left px-4 py-2 uppercase tracking-widest">Pago em</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($comissoes as $com)
                    <tr class="border-b border-gray-100 hover:bg-gray-50">
                        <td class="px-4 py-2"><input type="checkbox" name="ids[]" value="{{ $com->id }}"></td>
                        <td class="px-4 py-2">{{ $com->affiliate?->user?->name ?? '—' }}</td>
                        <td class="px-4 py-2">#{{ $com->pedido_id }}</td>
                        <td class="px-4 py-2 font-bold">R$ {{ number_format($com->valor, 2, ',', '.') }}</td>
                        <td class="px-4 py-2 uppercase tracking-widest">{{ $com->status }}</td>
                        <td class="px-4 py-2">{{ $com->eligible_at?->format('d/m/Y') ?? '—' }}</td>
                        <td class="px-4 py-2">{{ $com->paid_at?->format('d/m/Y') ?? '—' }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="flex items-center gap-2 mt-3">
            <button type="button" onclick="submitBulk('aprovar')"
                class="border border-black px-3 py-1.5 text-[10px] font-mono uppercase tracking-widest hover:bg-black hover:text-white transition-colors">
                Aprovar selecionadas
            </button>
            <button type="button" onclick="submitBulk('rejeitar')"
                class="border border-black px-3 py-1.5 text-[10px] font-mono uppercase tracking-widest hover:bg-black hover:text-white transition-colors">
                Rejeitar selecionadas
            </button>
            <button type="button" onclick="submitBulk('marcar_pago')"
                class="border border-black px-3 py-1.5 text-[10px] font-mono uppercase tracking-widest hover:bg-black hover:text-white transition-colors">
                Marcar como pagas
            </button>
        </div>
    </form>

    <div class="font-mono">{{ $comissoes->links() }}</div>
</div>

<script>
function toggleAll() {
    const all = document.getElementById('selectAll').checked;
    document.querySelectorAll('input[name="ids[]"]').forEach(cb => cb.checked = all);
}
function submitBulk(action) {
    document.getElementById('bulkAction').value = action;
    document.getElementById('bulkForm').submit();
}
</script>
@endsection
