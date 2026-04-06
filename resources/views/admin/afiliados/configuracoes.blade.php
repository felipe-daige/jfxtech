{{-- resources/views/admin/afiliados/configuracoes.blade.php --}}
@extends('includes.header-admin')
@section('title', 'Configurações — Afiliados')
@section('content')
<div class="p-4 md:p-6 max-w-lg space-y-6">

    <div class="flex items-center justify-between">
        <h1 class="font-mono text-lg font-bold uppercase tracking-widest">Configurações de Afiliados</h1>
        <a href="{{ route('admin.afiliados.index') }}" class="font-mono text-xs underline">← Afiliados</a>
    </div>

    @if(session('success'))
        <div class="border border-black bg-white p-4 font-mono text-sm">{{ session('success') }}</div>
    @endif

    @if($errors->any())
        <div class="border border-black p-4 font-mono text-sm">
            @foreach($errors->all() as $err)<p>{{ $err }}</p>@endforeach
        </div>
    @endif

    <form method="POST" action="{{ route('admin.afiliados.configuracoes.salvar') }}" class="space-y-5">
        @csrf
        <div>
            <label class="block font-mono text-[10px] uppercase tracking-widest mb-1">Comissão padrão (%)</label>
            <input type="number" step="0.01" name="commission_percent_default"
                value="{{ $settings['commission_percent_default'] ?? '5.00' }}"
                class="w-full border border-black px-3 py-2 font-mono text-sm focus:outline-none">
            <p class="text-[10px] text-gray-400 mt-1">Aplicado a afiliados sem override individual.</p>
        </div>
        <div>
            <label class="block font-mono text-[10px] uppercase tracking-widest mb-1">Validade do cookie (dias)</label>
            <input type="number" name="cookie_days"
                value="{{ $settings['cookie_days'] ?? '30' }}"
                class="w-full border border-black px-3 py-2 font-mono text-sm focus:outline-none">
        </div>
        <div>
            <label class="block font-mono text-[10px] uppercase tracking-widest mb-1">Período de carência (dias)</label>
            <input type="number" name="grace_period_days"
                value="{{ $settings['grace_period_days'] ?? '30' }}"
                class="w-full border border-black px-3 py-2 font-mono text-sm focus:outline-none">
            <p class="text-[10px] text-gray-400 mt-1">Dias após conversão antes de a comissão poder ser aprovada.</p>
        </div>
        <button type="submit"
            class="w-full border border-black bg-black text-white py-3 font-mono text-xs uppercase tracking-widest hover:bg-white hover:text-black transition-colors">
            Salvar Configurações
        </button>
    </form>
</div>
@endsection
