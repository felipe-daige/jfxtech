@php
    use App\Enums\PedidoStatus;

    $summary = $analytics['summary'];
    $items = $analytics['items'];
    $paymentMix = $analytics['payment_mix'];
    $badgeClass = PedidoStatus::badgeClass($pedido->status);
    $margin = $summary['margem_percentual_estimada'];
    $marginTone = $margin === null ? 'text-blue-700 border-blue-200 bg-blue-50' : ($margin < 0 ? 'text-red-700 border-red-200 bg-red-50' : ($margin < 20 ? 'text-yellow-700 border-yellow-200 bg-yellow-50' : 'text-emerald-700 border-emerald-200 bg-emerald-50'));
    $marginAccent = $margin === null ? 'bg-blue-500' : ($margin < 0 ? 'bg-red-500' : ($margin < 20 ? 'bg-yellow-500' : 'bg-emerald-500'));

    $phone = $pedido->user?->phone ?? $pedido->customer_phone ?? null;
    $wa = $phone ? preg_replace('/\D/', '', $phone) : null;
    if ($wa && strlen($wa) <= 11) $wa = '55' . $wa;
    elseif ($wa && strlen($wa) > 13) $wa = preg_replace('/\D/', '', $phone);
@endphp

<div class="space-y-6" data-status="{{ $pedido->status }}">
    <section class="border border-[var(--color-lab-border)] bg-white overflow-hidden">
        <div class="grid grid-cols-1 xl:grid-cols-[minmax(0,1.3fr)_minmax(320px,0.9fr)]">
            <div class="px-5 py-6 sm:px-6 sm:py-7 border-b xl:border-b-0 xl:border-r border-[var(--color-lab-border)] bg-[linear-gradient(135deg,#ffffff_0%,#f7f7f5_55%,#eef2f7_100%)]">
                <div class="flex flex-wrap items-start justify-between gap-4">
                    <div>
                        <p class="font-mono text-[10px] uppercase tracking-[0.22em] text-[var(--color-lab-muted)] mb-2">Order Intelligence</p>
                        <div class="flex flex-wrap items-center gap-3">
                            <h3 class="text-2xl sm:text-3xl font-bold tracking-tight text-black">Pedido #{{ $pedido->id }}</h3>
                            <span class="inline-flex items-center px-3 py-1 border font-mono text-[10px] uppercase tracking-widest {{ $badgeClass }}">
                                {{ PedidoStatus::label($pedido->status) }}
                            </span>
                        </div>
                        <p class="mt-3 max-w-2xl text-sm text-[var(--color-lab-muted)]">
                            Leitura operacional da venda com margem estimada por item, concentração de receita e alertas de custo do catálogo atual.
                        </p>
                    </div>

                    <div class="grid grid-cols-2 gap-2 text-right">
                        <div class="border border-[var(--color-lab-border)] bg-white px-3 py-3">
                            <p class="font-mono text-[9px] uppercase tracking-[0.18em] text-[var(--color-lab-muted)]">Criado em</p>
                            <p class="mt-1 font-mono text-xs font-bold text-black">{{ $pedido->created_at->format('d/m/Y H:i') }}</p>
                        </div>
                        <div class="border border-[var(--color-lab-border)] bg-white px-3 py-3">
                            <p class="font-mono text-[9px] uppercase tracking-[0.18em] text-[var(--color-lab-muted)]">Checkout</p>
                            <p class="mt-1 font-mono text-xs font-bold text-black">{{ strtoupper((string) ($pedido->checkout_mode ?? ($pedido->user_id ? 'user' : 'guest'))) }}</p>
                        </div>
                    </div>
                </div>

                <div class="mt-6 grid grid-cols-1 sm:grid-cols-3 gap-3">
                    <div class="border border-[var(--color-lab-border)] bg-white px-4 py-4">
                        <p class="font-mono text-[9px] uppercase tracking-[0.18em] text-[var(--color-lab-muted)]">Cliente</p>
                        <p class="mt-2 text-sm font-semibold text-black break-words">{{ $pedido->user?->name ?? $pedido->customer_name ?? 'Guest' }}</p>
                        <p class="mt-1 font-mono text-[10px] text-[var(--color-lab-muted)] break-all">{{ $pedido->user?->email ?? $pedido->customer_email ?? 'E-mail não informado' }}</p>
                        @if($phone)
                        <a href="https://wa.me/{{ $wa }}" target="_blank" rel="noopener"
                           class="mt-1 font-mono text-[10px] text-[var(--color-lab-muted)] hover:text-black flex items-center gap-1 break-words">
                            <svg xmlns="http://www.w3.org/2000/svg" width="10" height="10" viewBox="0 0 24 24" fill="currentColor"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347z"/><path d="M12 0C5.373 0 0 5.373 0 12c0 2.117.549 4.107 1.51 5.843L.057 23.5a.5.5 0 0 0 .635.605l5.752-1.464A11.945 11.945 0 0 0 12 24c6.627 0 12-5.373 12-12S18.627 0 12 0zm0 22a9.944 9.944 0 0 1-5.071-1.38l-.361-.214-3.754.956.993-3.651-.232-.374A9.952 9.952 0 0 1 2 12C2 6.477 6.477 2 12 2s10 4.477 10 10-4.477 10-10 10z"/></svg>
                            {{ $phone }}
                        </a>
                        @else
                        <p class="mt-1 font-mono text-[10px] text-[var(--color-lab-muted)]">Telefone não informado</p>
                        @endif
                    </div>
                    <div class="border border-[var(--color-lab-border)] bg-white px-4 py-4">
                        <p class="font-mono text-[9px] uppercase tracking-[0.18em] text-[var(--color-lab-muted)]">Ticket</p>
                        <p class="mt-2 font-mono text-xl font-bold text-black">R$ {{ number_format($summary['valor_total_pedido'], 2, ',', '.') }}</p>
                        <p class="mt-1 font-mono text-[10px] text-[var(--color-lab-muted)]">{{ $summary['unidades'] }} un. em {{ $summary['linhas'] }} linha(s)</p>
                    </div>
                    <div class="border border-[var(--color-lab-border)] {{ $marginTone }} px-4 py-4">
                        <p class="font-mono text-[9px] uppercase tracking-[0.18em]">Margem estimada</p>
                        <p class="mt-2 font-mono text-xl font-bold">{{ $margin !== null ? number_format($margin, 1, ',', '.') . '%' : 'Sem custo base' }}</p>
                        <p class="mt-1 font-mono text-[10px]">{{ $summary['itens_sem_custo'] > 0 ? $summary['itens_sem_custo'] . ' item(ns) sem custo cadastrado' : 'Calculada com custo atual do catálogo' }}</p>
                    </div>
                </div>
            </div>

            <div class="px-5 py-6 sm:px-6 sm:py-7 bg-[var(--color-lab-bg)]">
                <div class="flex items-center justify-between gap-3 mb-4">
                    <p class="font-mono text-[10px] uppercase tracking-[0.22em] text-[var(--color-lab-muted)]">Snapshot financeiro</p>
                    <span class="font-mono text-[10px] uppercase tracking-[0.18em] text-[var(--color-lab-muted)]">Estimado</span>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div class="border border-[var(--color-lab-border)] bg-white px-4 py-4">
                        <p class="font-mono text-[9px] uppercase tracking-[0.16em] text-[var(--color-lab-muted)]">Receita itens</p>
                        <p class="mt-2 font-mono text-lg font-bold text-black">R$ {{ number_format($summary['receita_itens'], 2, ',', '.') }}</p>
                    </div>
                    <div class="border border-[var(--color-lab-border)] bg-white px-4 py-4">
                        <p class="font-mono text-[9px] uppercase tracking-[0.16em] text-[var(--color-lab-muted)]">Custo total</p>
                        <p class="mt-2 font-mono text-lg font-bold text-black">R$ {{ number_format($summary['custo_total_estimado'], 2, ',', '.') }}</p>
                    </div>
                    <div class="border border-[var(--color-lab-border)] bg-white px-4 py-4">
                        <p class="font-mono text-[9px] uppercase tracking-[0.16em] text-[var(--color-lab-muted)]">Lucro líquido</p>
                        <p class="mt-2 font-mono text-lg font-bold {{ $summary['lucro_total_estimado'] < 0 ? 'text-red-600' : 'text-black' }}">R$ {{ number_format($summary['lucro_total_estimado'], 2, ',', '.') }}</p>
                        @if($summary['desconto'] > 0)
                        <p class="mt-1 font-mono text-[10px] text-[var(--color-lab-muted)]">Após desconto de R$ {{ number_format($summary['desconto'], 2, ',', '.') }}</p>
                        @endif
                    </div>
                    <div class="border border-[var(--color-lab-border)] bg-white px-4 py-4">
                        <p class="font-mono text-[9px] uppercase tracking-[0.16em] text-[var(--color-lab-muted)]">Frete / desconto</p>
                        <p class="mt-2 font-mono text-lg font-bold text-black">R$ {{ number_format($summary['frete'], 2, ',', '.') }}</p>
                        <p class="mt-1 font-mono text-[10px] text-[var(--color-lab-muted)]">Desconto: R$ {{ number_format($summary['desconto'], 2, ',', '.') }}</p>
                    </div>
                </div>

                <div class="mt-4 border border-[var(--color-lab-border)] bg-white px-4 py-4">
                    <div class="flex items-center justify-between gap-3">
                        <div>
                            <p class="font-mono text-[9px] uppercase tracking-[0.18em] text-[var(--color-lab-muted)]">Leitura de margem</p>
                            <p class="mt-1 text-sm text-black">
                                @if($margin === null)
                                    Parte da venda não possui custo cadastrado. O lucro exibido é parcial.
                                @elseif($margin < 0)
                                    Pedido vendido abaixo do custo estimado atual.
                                @elseif($margin < 20)
                                    Pedido com margem comprimida. Vale revisar preço ou custo do mix vendido.
                                @else
                                    Pedido com margem saudável considerando o custo atual cadastrado.
                                @endif
                            </p>
                        </div>
                        <div class="hidden sm:block w-20 h-20 rounded-full border border-[var(--color-lab-border)] bg-[var(--color-lab-bg)] relative overflow-hidden">
                            <div class="absolute inset-x-0 bottom-0 {{ $marginAccent }}" style="height: {{ $margin !== null ? max(14, min(100, round(abs($margin)))) : 30 }}%;"></div>
                            <div class="absolute inset-0 flex items-center justify-center font-mono text-xs font-bold text-black">
                                {{ $margin !== null ? number_format($margin, 0, ',', '.') . '%' : 'N/A' }}
                            </div>
                        </div>
                    </div>
                    <p class="mt-3 font-mono text-[10px] uppercase tracking-[0.16em] text-[var(--color-lab-muted)]">
                        Lucro líquido = receita dos itens − desconto do cupom − custo cadastrado no catálogo. Frete não é incluído na margem.
                    </p>
                </div>
            </div>
        </div>
    </section>

    <section class="grid grid-cols-2 lg:grid-cols-4 gap-3">
        <div class="border border-[var(--color-lab-border)] bg-white px-4 py-4">
            <p class="font-mono text-[9px] uppercase tracking-[0.16em] text-[var(--color-lab-muted)]">Maior concentração</p>
            <p class="mt-2 font-mono text-2xl font-bold text-black">{{ $items->max('receita_share_percentual') ? number_format((float) $items->max('receita_share_percentual'), 1, ',', '.') . '%' : '0,0%' }}</p>
            <p class="mt-1 font-mono text-[10px] text-[var(--color-lab-muted)]">participação máxima na receita do pedido</p>
        </div>
        <div class="border border-[var(--color-lab-border)] bg-white px-4 py-4">
            <p class="font-mono text-[9px] uppercase tracking-[0.16em] text-[var(--color-lab-muted)]">Itens sem custo</p>
            <p class="mt-2 font-mono text-2xl font-bold {{ $summary['itens_sem_custo'] > 0 ? 'text-blue-600' : 'text-black' }}">{{ $summary['itens_sem_custo'] }}</p>
            <p class="mt-1 font-mono text-[10px] text-[var(--color-lab-muted)]">linhas com lucro parcial ou indisponível</p>
        </div>
        <div class="border border-[var(--color-lab-border)] bg-white px-4 py-4">
            <p class="font-mono text-[9px] uppercase tracking-[0.16em] text-[var(--color-lab-muted)]">Métodos pagos</p>
            <p class="mt-2 font-mono text-2xl font-bold text-black">{{ $paymentMix->count() }}</p>
            <p class="mt-1 font-mono text-[10px] text-[var(--color-lab-muted)]">{{ $paymentMix->pluck('label')->implode(' · ') ?: 'Sem pagamento' }}</p>
        </div>
        <div class="border border-[var(--color-lab-border)] bg-white px-4 py-4">
            <p class="font-mono text-[9px] uppercase tracking-[0.16em] text-[var(--color-lab-muted)]">Lucro por unidade</p>
            <p class="mt-2 font-mono text-2xl font-bold text-black">
                {{ $summary['unidades'] > 0 ? 'R$ ' . number_format($summary['lucro_total_estimado'] / $summary['unidades'], 2, ',', '.') : 'R$ 0,00' }}
            </p>
            <p class="mt-1 font-mono text-[10px] text-[var(--color-lab-muted)]">média direta sobre unidades do pedido</p>
        </div>
    </section>

    <section class="border border-[var(--color-lab-border)] bg-white">
        <div class="px-5 py-4 sm:px-6 border-b border-[var(--color-lab-border)]">
            <div class="flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-between">
                <div>
                    <p class="font-mono text-[10px] uppercase tracking-[0.22em] text-[var(--color-lab-muted)]">Itens vendidos</p>
                    <h4 class="mt-1 text-lg font-bold tracking-tight text-black">Margem por produto no pedido</h4>
                </div>
                <p class="font-mono text-[10px] uppercase tracking-[0.16em] text-[var(--color-lab-muted)]">Receita, custo, lucro, participação e risco</p>
            </div>
        </div>

        <div class="p-4 sm:p-6 space-y-4">
            @foreach($items as $item)
                @php
                    $toneClasses = match ($item['health']) {
                        'negative' => 'border-red-200 bg-[linear-gradient(135deg,#fff5f5_0%,#ffffff_100%)]',
                        'low' => 'border-yellow-200 bg-[linear-gradient(135deg,#fffceb_0%,#ffffff_100%)]',
                        'missing_cost' => 'border-blue-200 bg-[linear-gradient(135deg,#eff6ff_0%,#ffffff_100%)]',
                        default => 'border-[var(--color-lab-border)] bg-white',
                    };
                    $barClass = match ($item['health']) {
                        'negative' => 'bg-red-500',
                        'low' => 'bg-yellow-500',
                        'missing_cost' => 'bg-blue-500',
                        default => 'bg-black',
                    };
                    $badgeText = match ($item['health']) {
                        'negative' => 'Margem negativa',
                        'low' => 'Margem baixa',
                        'missing_cost' => 'Sem custo',
                        default => 'Saudável',
                    };
                @endphp
                <article class="border {{ $toneClasses }} overflow-hidden">
                    <div class="grid grid-cols-1 xl:grid-cols-[minmax(0,1.35fr)_minmax(300px,0.95fr)]">
                        <div class="p-4 sm:p-5 border-b xl:border-b-0 xl:border-r border-[var(--color-lab-border)]">
                            <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                                <div class="flex gap-4 min-w-0">
                                    <div class="w-16 h-16 shrink-0 border border-[var(--color-lab-border)] bg-white overflow-hidden">
                                        @if($item['image_path'])
                                            <img src="{{ asset('storage/' . $item['image_path']) }}" alt="{{ $item['produto_nome'] }}" class="w-full h-full object-cover">
                                        @else
                                            <div class="w-full h-full flex items-center justify-center text-[var(--color-lab-muted)]">
                                                <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><path d="m21 15-5-5L5 21"/></svg>
                                            </div>
                                        @endif
                                    </div>

                                    <div class="min-w-0">
                                        <div class="flex flex-wrap items-center gap-2">
                                            <h5 class="text-base font-bold text-black break-words">{{ $item['produto_nome'] }}</h5>
                                            <span class="inline-flex items-center px-2 py-1 font-mono text-[10px] uppercase tracking-widest border {{ $item['health'] === 'negative' ? 'border-red-300 text-red-700 bg-red-50' : ($item['health'] === 'low' ? 'border-yellow-300 text-yellow-700 bg-yellow-50' : ($item['health'] === 'missing_cost' ? 'border-blue-300 text-blue-700 bg-blue-50' : 'border-emerald-300 text-emerald-700 bg-emerald-50')) }}">
                                                {{ $badgeText }}
                                            </span>
                                            @if($item['is_top_profit'])
                                                <span class="inline-flex items-center px-2 py-1 font-mono text-[10px] uppercase tracking-widest border border-black bg-black text-white">Top lucro</span>
                                            @endif
                                            @if($item['is_worst_margin'])
                                                <span class="inline-flex items-center px-2 py-1 font-mono text-[10px] uppercase tracking-widest border border-red-300 bg-white text-red-600">Pior margem</span>
                                            @endif
                                        </div>

                                        @if($item['variant_label'])
                                            <p class="mt-2 font-mono text-[10px] uppercase tracking-[0.16em] text-[var(--color-lab-muted)] break-words">{{ $item['variant_label'] }}</p>
                                        @endif

                                        <div class="mt-4 grid grid-cols-4 gap-2">
                                            <div class="border border-[var(--color-lab-border)] bg-white px-3 py-3 min-w-0 overflow-hidden">
                                                <p class="font-mono text-[9px] uppercase tracking-[0.16em] text-[var(--color-lab-muted)] truncate">Qtd</p>
                                                <p class="mt-1 font-mono text-sm font-bold text-black truncate">{{ $item['quantidade'] }}</p>
                                            </div>
                                            <div class="border border-[var(--color-lab-border)] bg-white px-3 py-3 min-w-0 overflow-hidden">
                                                <p class="font-mono text-[9px] uppercase tracking-[0.16em] text-[var(--color-lab-muted)] truncate">Venda un.</p>
                                                <p class="mt-1 font-mono text-sm font-bold text-black truncate">R$ {{ number_format($item['preco_unitario'], 2, ',', '.') }}</p>
                                            </div>
                                            <div class="border border-[var(--color-lab-border)] bg-white px-3 py-3 min-w-0 overflow-hidden">
                                                <p class="font-mono text-[9px] uppercase tracking-[0.16em] text-[var(--color-lab-muted)] truncate">Receita</p>
                                                <p class="mt-1 font-mono text-sm font-bold text-black truncate">R$ {{ number_format($item['receita'], 2, ',', '.') }}</p>
                                            </div>
                                            <div class="border border-[var(--color-lab-border)] bg-white px-3 py-3 min-w-0 overflow-hidden">
                                                <p class="font-mono text-[9px] uppercase tracking-[0.16em] text-[var(--color-lab-muted)] truncate">Fonte custo</p>
                                                <p class="mt-1 font-mono text-sm font-bold text-black truncate">{{ strtoupper(str_replace('_', ' ', $item['cost_source'])) }}</p>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="sm:text-right">
                                    <p class="font-mono text-[9px] uppercase tracking-[0.18em] text-[var(--color-lab-muted)]">Participação na receita</p>
                                    <p class="mt-1 font-mono text-2xl font-bold text-black">{{ number_format($item['receita_share_percentual'], 1, ',', '.') }}%</p>
                                    <p class="mt-1 font-mono text-[10px] text-[var(--color-lab-muted)]">
                                        @if($item['lucro_share_percentual'] !== null)
                                            {{ number_format($item['lucro_share_percentual'], 1, ',', '.') }}% do lucro conhecido
                                        @else
                                            Lucro parcial indisponível
                                        @endif
                                    </p>
                                </div>
                            </div>
                        </div>

                        <div class="p-4 sm:p-5 bg-white/70">
                            <div class="grid grid-cols-2 gap-2">
                                <div class="border border-[var(--color-lab-border)] bg-white px-3 py-3">
                                    <p class="font-mono text-[9px] uppercase tracking-[0.16em] text-[var(--color-lab-muted)]">Custo un.</p>
                                    <p class="mt-1 font-mono text-sm font-bold text-black">
                                        {{ $item['custo_unitario'] !== null ? 'R$ ' . number_format($item['custo_unitario'], 2, ',', '.') : 'N/A' }}
                                    </p>
                                </div>
                                <div class="border border-[var(--color-lab-border)] bg-white px-3 py-3">
                                    <p class="font-mono text-[9px] uppercase tracking-[0.16em] text-[var(--color-lab-muted)]">Custo total</p>
                                    <p class="mt-1 font-mono text-sm font-bold text-black">
                                        {{ $item['custo_total'] !== null ? 'R$ ' . number_format($item['custo_total'], 2, ',', '.') : 'N/A' }}
                                    </p>
                                </div>
                                <div class="border border-[var(--color-lab-border)] bg-white px-3 py-3">
                                    <p class="font-mono text-[9px] uppercase tracking-[0.16em] text-[var(--color-lab-muted)]">Lucro un.</p>
                                    <p class="mt-1 font-mono text-sm font-bold {{ $item['lucro_unitario'] !== null && $item['lucro_unitario'] < 0 ? 'text-red-600' : 'text-black' }}">
                                        {{ $item['lucro_unitario'] !== null ? 'R$ ' . number_format($item['lucro_unitario'], 2, ',', '.') : 'N/A' }}
                                    </p>
                                </div>
                                <div class="border border-[var(--color-lab-border)] bg-white px-3 py-3">
                                    <p class="font-mono text-[9px] uppercase tracking-[0.16em] text-[var(--color-lab-muted)]">Lucro total</p>
                                    <p class="mt-1 font-mono text-sm font-bold {{ $item['lucro_total'] !== null && $item['lucro_total'] < 0 ? 'text-red-600' : 'text-black' }}">
                                        {{ $item['lucro_total'] !== null ? 'R$ ' . number_format($item['lucro_total'], 2, ',', '.') : 'N/A' }}
                                    </p>
                                </div>
                            </div>

                            <div class="mt-4 border border-[var(--color-lab-border)] bg-[var(--color-lab-bg)] px-3 py-3">
                                <div class="flex items-center justify-between gap-3">
                                    <p class="font-mono text-[9px] uppercase tracking-[0.18em] text-[var(--color-lab-muted)]">Barra de margem</p>
                                    <p class="font-mono text-xs font-bold {{ $item['margem_percentual'] !== null && $item['margem_percentual'] < 0 ? 'text-red-600' : 'text-black' }}">
                                        {{ $item['margem_percentual'] !== null ? number_format($item['margem_percentual'], 1, ',', '.') . '%' : 'Sem base de custo' }}
                                    </p>
                                </div>
                                <div class="mt-3 h-2 bg-white border border-[var(--color-lab-border)] overflow-hidden">
                                    <div class="h-full {{ $barClass }}" style="width: {{ $item['margin_bar_percent'] }}%;"></div>
                                </div>
                                <div class="mt-3 flex items-center justify-between gap-3 font-mono text-[10px] uppercase tracking-[0.14em] text-[var(--color-lab-muted)]">
                                    <span>Rank lucro #{{ $item['rank_receita'] }}</span>
                                    <span>Rank margem #{{ $item['rank_margem'] }}</span>
                                </div>
                            </div>

                            @if($item['cost_source'] === 'sem_custo')
                                <p class="mt-3 font-mono text-[10px] uppercase tracking-[0.16em] text-blue-700">
                                    Sem custo cadastrado para este SKU. A margem do item não pode ser fechada com precisão.
                                </p>
                            @endif
                        </div>
                    </div>
                </article>
            @endforeach
        </div>
    </section>

    <section class="grid grid-cols-1 xl:grid-cols-[minmax(0,1fr)_minmax(0,0.95fr)] gap-4">
        <div class="border border-[var(--color-lab-border)] bg-[var(--color-lab-bg)] p-5">
            <p class="font-mono text-[10px] uppercase tracking-widest text-[var(--color-lab-muted)] mb-4">Dados do cliente e entrega</p>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="border border-[var(--color-lab-border)] bg-white p-4">
                    <p class="font-mono text-[9px] uppercase tracking-[0.16em] text-[var(--color-lab-muted)] mb-2">Cliente</p>
                    <p class="text-sm font-semibold text-black break-words">{{ $pedido->user?->name ?? $pedido->customer_name ?? 'Guest' }}</p>
                    <p class="mt-1 font-mono text-[10px] text-[var(--color-lab-muted)] break-all">{{ $pedido->user?->email ?? $pedido->customer_email ?? 'E-mail não informado' }}</p>
                    @if($phone)
                    <a href="https://wa.me/{{ $wa }}" target="_blank" rel="noopener"
                       class="mt-1 font-mono text-[10px] text-[var(--color-lab-muted)] hover:text-black flex items-center gap-1 break-words">
                        <svg xmlns="http://www.w3.org/2000/svg" width="10" height="10" viewBox="0 0 24 24" fill="currentColor"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347z"/><path d="M12 0C5.373 0 0 5.373 0 12c0 2.117.549 4.107 1.51 5.843L.057 23.5a.5.5 0 0 0 .635.605l5.752-1.464A11.945 11.945 0 0 0 12 24c6.627 0 12-5.373 12-12S18.627 0 12 0zm0 22a9.944 9.944 0 0 1-5.071-1.38l-.361-.214-3.754.956.993-3.651-.232-.374A9.952 9.952 0 0 1 2 12C2 6.477 6.477 2 12 2s10 4.477 10 10-4.477 10-10 10z"/></svg>
                        {{ $phone }}
                    </a>
                    @else
                    <p class="mt-1 font-mono text-[10px] text-[var(--color-lab-muted)]">Telefone não informado</p>
                    @endif
                </div>
                <div class="border border-[var(--color-lab-border)] bg-white p-4">
                    <p class="font-mono text-[9px] uppercase tracking-[0.16em] text-[var(--color-lab-muted)] mb-2">Entrega</p>
                    @if($pedido->endereco)
                        <div class="space-y-1 font-mono text-[10px] text-[var(--color-lab-muted)]">
                            <p class="text-black">{{ $pedido->endereco->rua }}, {{ $pedido->endereco->numero }}</p>
                            @if($pedido->endereco->complemento)
                                <p>{{ $pedido->endereco->complemento }}</p>
                            @endif
                            <p>{{ $pedido->endereco->bairro }} · {{ $pedido->endereco->cidade }}/{{ $pedido->endereco->estado }}</p>
                            <p>CEP {{ $pedido->endereco->cep }} · {{ $pedido->endereco->pais ?? 'Brasil' }}</p>
                        </div>
                    @else
                        <p class="font-mono text-[10px] text-[var(--color-lab-muted)]">Endereço não informado</p>
                    @endif
                </div>
            </div>
        </div>

        <div class="space-y-4">
            @if($pedido->pagamentos && $pedido->pagamentos->count())
                <div class="border border-[var(--color-lab-border)] bg-white p-5">
                    <p class="font-mono text-[10px] uppercase tracking-widest text-[var(--color-lab-muted)] mb-4">Mix de pagamentos</p>
                    <div class="space-y-3">
                        @foreach($paymentMix as $entry)
                            <div class="flex items-center justify-between gap-3 border border-[var(--color-lab-border)] bg-[var(--color-lab-bg)] px-3 py-3">
                                <div>
                                    <p class="font-mono text-xs font-bold text-black">{{ $entry['label'] }}</p>
                                    <p class="font-mono text-[10px] text-[var(--color-lab-muted)]">{{ $entry['count'] }} transação(ões)</p>
                                </div>
                                <p class="font-mono text-sm font-bold text-black">R$ {{ number_format($entry['value'], 2, ',', '.') }}</p>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

            <div class="border border-[var(--color-lab-border)] bg-white p-5">
                <p class="font-mono text-[10px] uppercase tracking-widest text-[var(--color-lab-muted)] mb-3">Rastreamento (Correios)</p>
                <div class="flex gap-2 items-center">
                    <input type="text"
                           id="rastreio-input-{{ $pedido->id }}"
                           value="{{ $pedido->codigo_rastreio ?? '' }}"
                           placeholder="Ex: BR123456789BR"
                           maxlength="50"
                           class="flex-1 font-mono text-sm px-3 py-2 border border-[var(--color-lab-border)] bg-white focus:outline-none focus:border-black transition-colors uppercase">
                    <button onclick="salvarRastreio({{ $pedido->id }})"
                            id="rastreio-btn-{{ $pedido->id }}"
                            class="font-mono text-[10px] uppercase tracking-widest px-3 py-2 border border-black text-black hover:bg-black hover:text-white transition-colors whitespace-nowrap">
                        Salvar
                    </button>
                </div>
                <p id="rastreio-feedback-{{ $pedido->id }}" class="font-mono text-[10px] text-green-700 mt-1 hidden">Salvo!</p>
                @if($pedido->codigo_rastreio)
                    <p class="font-mono text-[10px] text-[var(--color-lab-muted)] mt-2">
                        <a href="https://rastreamento.correios.com.br/app/index.php?objetos={{ $pedido->codigo_rastreio }}"
                           target="_blank"
                           class="underline hover:text-black">Rastrear no site dos Correios &#8599;</a>
                    </p>
                @endif
            </div>
        </div>
    </section>
</div>
