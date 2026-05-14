@extends('includes.header-admin')

@section('title', 'Pedido #' . $pedido->id)

@section('content')
<div class="space-y-4 lg:space-y-6">
    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <p class="font-mono text-[10px] uppercase tracking-widest text-[var(--color-lab-muted)] mb-1">Pedido</p>
            <h1 class="text-2xl sm:text-3xl font-bold text-black tracking-tight">#{{ $pedido->id }}</h1>
        </div>
        <a href="{{ route('admin.pedidos') }}"
           class="inline-flex w-fit items-center justify-center px-4 py-2.5 border border-[var(--color-lab-border)] bg-white font-mono text-[10px] uppercase tracking-widest text-black hover:border-black transition-colors">
            Voltar aos pedidos
        </a>
    </div>

    @include('admin.includes.pedido-detalhes', ['isFullOrderPage' => true])
</div>
@endsection
