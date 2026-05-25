@php
    use App\Enums\PedidoStatus;
    use App\Models\ItemPedido;

    $summary = $analytics['summary'];
    $items = $analytics['items'];
    $paymentMix = $analytics['payment_mix'];
    $costComparison = $analytics['cost_comparison'] ?? ['summary' => [], 'rows' => collect(), 'recent' => collect()];
    $costComparisonSummary = $costComparison['summary'] ?? [];
    $badgeClass = PedidoStatus::badgeClass($pedido->status);
    $productMargin = $summary['margem_produtos_percentual'];
    $ticketMargin = $summary['margem_ticket_percentual'];
    $productMarginTone = $productMargin === null ? 'text-blue-700 border-blue-200 bg-blue-50' : ($productMargin < 0 ? 'text-red-700 border-red-200 bg-red-50' : ($productMargin < 20 ? 'text-yellow-700 border-yellow-200 bg-yellow-50' : 'text-emerald-700 border-emerald-200 bg-emerald-50'));
    $ticketMarginTone = $ticketMargin === null ? 'text-blue-700 border-blue-200 bg-blue-50' : ($ticketMargin < 0 ? 'text-red-700 border-red-200 bg-red-50' : ($ticketMargin < 20 ? 'text-yellow-700 border-yellow-200 bg-yellow-50' : 'text-emerald-700 border-emerald-200 bg-emerald-50'));
    $ticketMarginAccent = $ticketMargin === null ? 'bg-blue-500' : ($ticketMargin < 0 ? 'bg-red-500' : ($ticketMargin < 20 ? 'bg-yellow-500' : 'bg-emerald-500'));
    $partnerCommissionTotal = $summary['comissao_parceiro_total'] ?? null;
    $hasPartnerCommission = $partnerCommissionTotal !== null;
    $partnerRate = $summary['parceiro_taxa_percentual'] ?? null;
    $partnerName = $summary['parceiro_nome'] ?? null;
    $hasDeclaredCosts = ($summary['itens_com_custo_declarado'] ?? 0) > 0;
    $costModeLabel = $hasDeclaredCosts ? 'Real/declarado' : 'Estimado';
    $preparationStatusLabels = ItemPedido::preparationStatusLabels();

    $couponCode = trim((string) ($pedido->cupom_codigo ?? ''));
    $hasCoupon = $couponCode !== '';
    $shippingType = strtolower((string) ($pedido->frete_tipo ?? ''));
    $shippingLabel = match ($shippingType) {
        'pac' => 'PAC',
        'sedex' => 'SEDEX',
        'retirada' => 'Retirada',
        'gratis' => 'Grátis',
        default => $shippingType !== '' ? strtoupper($shippingType) : 'Não informado',
    };
    $checkoutLabel = strtoupper((string) ($pedido->checkout_mode ?? ($pedido->user_id ? 'user' : 'guest')));

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
                            @if(empty($isFullOrderPage))
                                <a href="{{ route('admin.pedidos.detalhes', $pedido->id) }}"
                                   class="inline-flex items-center gap-1.5 px-3 py-1 border border-black font-mono text-[10px] uppercase tracking-widest text-black hover:bg-black hover:text-white transition-colors">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/><polyline points="15 3 21 3 21 9"/><line x1="10" y1="14" x2="21" y2="3"/></svg>
                                    Ver pedido
                                </a>
                            @endif
                        </div>
                        <p class="mt-3 max-w-2xl text-sm text-[var(--color-lab-muted)]">
                            Leitura operacional da venda com ticket, cupom, frete, margem por produto e custo declarado no preparo quando informado.
                        </p>
                    </div>

                    <div class="grid grid-cols-2 gap-2 text-right">
                        <div class="border border-[var(--color-lab-border)] bg-white px-3 py-3 min-w-0">
                            <p class="font-mono text-[9px] uppercase tracking-[0.18em] text-[var(--color-lab-muted)]">Criado em</p>
                            <p class="mt-1 font-mono text-xs font-bold text-black">{{ $pedido->created_at->format('d/m/Y H:i') }}</p>
                        </div>
                        <div class="border border-[var(--color-lab-border)] bg-white px-3 py-3 min-w-0">
                            <p class="font-mono text-[9px] uppercase tracking-[0.18em] text-[var(--color-lab-muted)]">Checkout</p>
                            <p class="mt-1 font-mono text-xs font-bold text-black truncate">{{ $checkoutLabel }}</p>
                        </div>
                    </div>
                </div>

                <div class="mt-6 grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-3">
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
                        @if(($summary['estorno_total'] ?? 0) > 0)
                            <p class="mt-1 font-mono text-[10px] text-red-700">Estorno: - R$ {{ number_format($summary['estorno_total'], 2, ',', '.') }}</p>
                            <p class="mt-0.5 font-mono text-[10px] text-[var(--color-lab-muted)]">Original: R$ {{ number_format($summary['valor_total_original'] ?? $pedido->valor_total, 2, ',', '.') }}</p>
                        @endif
                    </div>
                    <div class="border border-[var(--color-lab-border)] bg-white px-4 py-4">
                        <p class="font-mono text-[9px] uppercase tracking-[0.18em] text-[var(--color-lab-muted)]">Cupom</p>
                        <p class="mt-2 font-mono text-xl font-bold {{ $hasCoupon ? 'text-emerald-700' : 'text-black' }} break-words">{{ $hasCoupon ? strtoupper($couponCode) : 'Sem cupom' }}</p>
                        <p class="mt-1 font-mono text-[10px] text-[var(--color-lab-muted)]">{{ $hasCoupon ? 'Desconto: - R$ ' . number_format($summary['desconto'], 2, ',', '.') : 'Nenhum desconto aplicado' }}</p>
                        @if($hasPartnerCommission)
                            <p class="mt-1 font-mono text-[10px] text-emerald-700">{{ number_format($partnerRate, 1, ',', '.') }}% parceiro{{ $partnerName ? ' · ' . $partnerName : '' }}</p>
                        @elseif($hasCoupon)
                            <p class="mt-1 font-mono text-[10px] text-[var(--color-lab-muted)]">Sem parceiro vinculado</p>
                        @endif
                    </div>
                    <div class="border border-[var(--color-lab-border)] bg-white px-4 py-4">
                        <p class="font-mono text-[9px] uppercase tracking-[0.18em] text-[var(--color-lab-muted)]">Frete</p>
                        @php $freteGratis = (float)$summary['frete'] == 0; @endphp
                        <div class="mt-2 flex items-start justify-between gap-2">
                            <div>
                                <p class="font-mono text-[9px] uppercase tracking-[0.14em] text-[var(--color-lab-muted)]">Escolhido</p>
                                <div class="mt-1 flex items-center gap-1.5 flex-wrap">
                                    <p class="font-mono text-lg font-bold text-black">{{ $shippingLabel }}</p>
                                    @if($freteGratis)
                                        <span class="px-1.5 py-0.5 font-mono text-[9px] uppercase tracking-widest border border-emerald-400 text-emerald-700 bg-emerald-50">Grátis</span>
                                    @else
                                        <span class="px-1.5 py-0.5 font-mono text-[9px] uppercase tracking-widest border border-[var(--color-lab-border)] text-[var(--color-lab-muted)]">Pago</span>
                                    @endif
                                </div>
                            </div>
                            <div class="text-right">
                                <p class="font-mono text-[9px] uppercase tracking-[0.14em] text-[var(--color-lab-muted)]">Estimativa</p>
                                <p class="mt-1 font-mono text-lg font-bold {{ $freteGratis ? 'text-emerald-700' : 'text-black' }}">
                                    {{ $freteGratis ? 'Grátis' : 'R$ ' . number_format($summary['frete'], 2, ',', '.') }}
                                </p>
                            </div>
                        </div>
                    </div>
                    <div class="border border-[var(--color-lab-border)] {{ $productMarginTone }} px-4 py-4">
                        <p class="font-mono text-[9px] uppercase tracking-[0.18em]">Margem produtos</p>
                        <p class="mt-2 font-mono text-xl font-bold">{{ $productMargin !== null ? number_format($productMargin, 1, ',', '.') . '%' : 'Sem custo base' }}</p>
                        <p class="mt-1 font-mono text-[10px]">{{ $summary['itens_sem_custo'] > 0 ? $summary['itens_sem_custo'] . ' item(ns) sem custo cadastrado' : 'Somente itens vendidos contra custo' }}</p>
                    </div>
                </div>
            </div>

            <div class="px-5 py-6 sm:px-6 sm:py-7 bg-[var(--color-lab-bg)]">
                <div class="flex items-center justify-between gap-3 mb-4">
                    <p class="font-mono text-[10px] uppercase tracking-[0.22em] text-[var(--color-lab-muted)]">Snapshot financeiro</p>
                    <span class="font-mono text-[10px] uppercase tracking-[0.18em] text-[var(--color-lab-muted)]">{{ $costModeLabel }}</span>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div class="border border-[var(--color-lab-border)] bg-white px-4 py-4">
                        <p class="font-mono text-xs uppercase tracking-[0.16em] text-[var(--color-lab-muted)]">Receita itens</p>
                        <p class="mt-2 font-mono text-lg font-bold text-black">R$ {{ number_format($summary['receita_itens'], 2, ',', '.') }}</p>
                    </div>
                    <div class="border border-[var(--color-lab-border)] {{ ($summary['estorno_total'] ?? 0) > 0 ? 'bg-red-50 border-red-200' : 'bg-white' }} px-4 py-4">
                        <p class="font-mono text-xs uppercase tracking-[0.16em] {{ ($summary['estorno_total'] ?? 0) > 0 ? 'text-red-700' : 'text-[var(--color-lab-muted)]' }}">Estornos</p>
                        <p class="mt-2 font-mono text-lg font-bold {{ ($summary['estorno_total'] ?? 0) > 0 ? 'text-red-700' : 'text-black' }}">R$ {{ number_format($summary['estorno_total'] ?? 0, 2, ',', '.') }}</p>
                        <p class="mt-1 font-mono text-[10px] {{ ($summary['estorno_total'] ?? 0) > 0 ? 'text-red-700' : 'text-[var(--color-lab-muted)]' }}">Itens cancelados na separação</p>
                    </div>
                    <div class="border border-[var(--color-lab-border)] bg-white px-4 py-4">
                        <p class="font-mono text-xs uppercase tracking-[0.16em] text-[var(--color-lab-muted)]">Custo total</p>
                        <p class="mt-2 font-mono text-lg font-bold text-black">R$ {{ number_format($summary['custo_total_estimado'], 2, ',', '.') }}</p>
                    </div>
                    <div class="border border-[var(--color-lab-border)] bg-white px-4 py-4">
                        <p class="font-mono text-xs uppercase tracking-[0.16em] text-[var(--color-lab-muted)]">Lucro líquido real</p>
                        <p class="mt-2 font-mono text-lg font-bold {{ $summary['lucro_ticket_estimado'] < 0 ? 'text-red-600' : 'text-black' }}">R$ {{ number_format($summary['lucro_ticket_estimado'], 2, ',', '.') }}</p>
                        <p class="mt-1 font-mono text-[10px] text-[var(--color-lab-muted)]">Ticket menos custo cadastrado{{ $hasPartnerCommission ? ' e comissão parceiro' : '' }}</p>
                        @if($summary['desconto'] > 0)
                        <p class="mt-1 font-mono text-[10px] text-[var(--color-lab-muted)]">Desconto aplicado: R$ {{ number_format($summary['desconto'], 2, ',', '.') }}</p>
                        @endif
                    </div>
                    <div class="border border-[var(--color-lab-border)] bg-white px-4 py-4">
                        <p class="font-mono text-xs uppercase tracking-[0.16em] text-[var(--color-lab-muted)]">Comissão parceiro</p>
                        <p class="mt-2 font-mono text-lg font-bold {{ $hasPartnerCommission ? 'text-emerald-700' : 'text-black' }}">
                            {{ $hasPartnerCommission ? 'R$ ' . number_format($partnerCommissionTotal, 2, ',', '.') : 'N/A' }}
                        </p>
                        <p class="mt-1 font-mono text-[10px] text-[var(--color-lab-muted)]">
                            {{ $hasPartnerCommission ? number_format($partnerRate, 1, ',', '.') . '% sobre itens após desconto' : 'Sem cupom de parceiro' }}
                        </p>
                    </div>
                    <div class="border border-[var(--color-lab-border)] {{ $ticketMarginTone }} px-4 py-4"
                         title="Margem do ticket: lucro líquido real dividido pelo valor total do pedido. A margem dos produtos mede apenas SKU contra custo.">
                        <p class="font-mono text-xs uppercase tracking-[0.16em]">Margem ticket</p>
                        <p class="mt-2 font-mono text-lg font-bold">{{ $ticketMargin !== null ? number_format($ticketMargin, 1, ',', '.') . '%' : 'Sem custo base' }}</p>
                        <p class="mt-1 font-mono text-[10px]">Lucro líquido real sobre o ticket</p>
                    </div>
                    <div class="border border-[var(--color-lab-border)] bg-white px-4 py-4">
                        <p class="font-mono text-xs uppercase tracking-[0.16em] text-[var(--color-lab-muted)]">Frete</p>
                        <div class="mt-2 space-y-1.5">
                            <div class="flex items-center justify-between gap-2">
                                <p class="font-mono text-[10px] text-[var(--color-lab-muted)]">Escolhido</p>
                                <div class="flex items-center gap-1.5">
                                    <p class="font-mono text-sm font-bold text-black">{{ $shippingLabel }}</p>
                                    @if($freteGratis)
                                        <span class="px-1.5 py-0.5 font-mono text-[9px] uppercase tracking-widest border border-emerald-400 text-emerald-700 bg-emerald-50">Grátis</span>
                                    @else
                                        <span class="px-1.5 py-0.5 font-mono text-[9px] uppercase tracking-widest border border-[var(--color-lab-border)] text-[var(--color-lab-muted)]">Pago</span>
                                    @endif
                                </div>
                            </div>
                            <div class="flex items-center justify-between">
                                <p class="font-mono text-[10px] text-[var(--color-lab-muted)]">Estimativa</p>
                                <p class="font-mono text-sm font-bold {{ $freteGratis ? 'text-emerald-700' : 'text-black' }}">
                                    {{ $freteGratis ? 'Grátis' : 'R$ ' . number_format($summary['frete'], 2, ',', '.') }}
                                </p>
                            </div>
                            <div class="flex items-center justify-between">
                                <p class="font-mono text-[10px] text-[var(--color-lab-muted)]">Desconto</p>
                                <p class="font-mono text-sm font-bold text-black">R$ {{ number_format($summary['desconto'], 2, ',', '.') }}</p>
                            </div>
                        </div>
                        @if($pedido->frete_custo_real !== null)
                        <div class="mt-2 pt-2 border-t border-[var(--color-lab-border)] space-y-1">
                            <div class="flex justify-between font-mono text-xs">
                                <span class="text-gray-500">Frete cobrado</span>
                                <span>R$ {{ number_format($pedido->frete_valor, 2, ',', '.') }}</span>
                            </div>
                            <div class="flex justify-between font-mono text-xs">
                                <span class="text-gray-500">Frete real pago</span>
                                <span>R$ {{ number_format($pedido->frete_custo_real, 2, ',', '.') }}</span>
                            </div>
                            <div class="flex justify-between font-mono text-xs font-bold border-t border-[var(--color-lab-border)] pt-1">
                                <span>Resultado frete</span>
                                @php $resultado = $pedido->resultado_frete; @endphp
                                <span class="{{ $resultado >= 0 ? 'text-black' : 'text-red-600' }}">
                                    {{ $resultado >= 0 ? '+' : '' }}R$ {{ number_format($resultado, 2, ',', '.') }}
                                </span>
                            </div>
                        </div>
                        @endif
                    </div>
                </div>

                <div class="mt-4 border border-[var(--color-lab-border)] bg-white px-4 py-4">
                    <div class="flex items-center justify-between gap-3">
                        <div>
                            <p class="font-mono text-[9px] uppercase tracking-[0.18em] text-[var(--color-lab-muted)]">Leitura de margem</p>
                            <p class="mt-1 text-sm text-black">
                                @if($ticketMargin === null)
                                    Parte da venda não possui custo cadastrado. O lucro exibido é parcial.
                                @elseif($ticketMargin < 0)
                                    Ticket vendido abaixo do custo estimado atual.
                                @elseif($ticketMargin < 20)
                                    Ticket com margem comprimida. Vale revisar preço, frete ou custo do mix vendido.
                                @else
                                    Ticket com margem saudável considerando o custo atual cadastrado.
                                @endif
                            </p>
                        </div>
                        <div class="hidden sm:block w-20 h-20 rounded-full border border-[var(--color-lab-border)] bg-[var(--color-lab-bg)] relative overflow-hidden">
                            <div class="absolute inset-x-0 bottom-0 {{ $ticketMarginAccent }}" style="height: {{ $ticketMargin !== null ? max(14, min(100, round(abs($ticketMargin)))) : 30 }}%;"></div>
                            <div class="absolute inset-0 flex items-center justify-center font-mono text-xs font-bold text-black">
                                {{ $ticketMargin !== null ? number_format($ticketMargin, 0, ',', '.') . '%' : 'N/A' }}
                            </div>
                        </div>
                    </div>
                    <p class="mt-3 font-mono text-[10px] uppercase tracking-[0.16em] text-[var(--color-lab-muted)]">
                        Margem ticket = lucro líquido real dividido pelo ticket. Margem produtos mede apenas a venda dos itens contra o custo cadastrado.
                    </p>
                </div>
            </div>
        </div>
    </section>

    <section class="grid grid-cols-2 lg:grid-cols-4 gap-3">
        <div class="border border-[var(--color-lab-border)] bg-white px-4 py-4">
            <p class="font-mono text-xs uppercase tracking-[0.16em] text-[var(--color-lab-muted)]">Maior concentração</p>
            <p class="mt-2 font-mono text-2xl font-bold text-black">{{ $items->max('receita_share_percentual') ? number_format((float) $items->max('receita_share_percentual'), 1, ',', '.') . '%' : '0,0%' }}</p>
            <p class="mt-1 font-mono text-[10px] text-[var(--color-lab-muted)]">participação máxima na receita do pedido</p>
        </div>
        <div class="border border-[var(--color-lab-border)] bg-white px-4 py-4">
            <p class="font-mono text-xs uppercase tracking-[0.16em] text-[var(--color-lab-muted)]">Itens sem custo</p>
            <p class="mt-2 font-mono text-2xl font-bold {{ $summary['itens_sem_custo'] > 0 ? 'text-blue-600' : 'text-black' }}">{{ $summary['itens_sem_custo'] }}</p>
            <p class="mt-1 font-mono text-[10px] text-[var(--color-lab-muted)]">linhas com lucro parcial ou indisponível</p>
        </div>
        <div class="border border-[var(--color-lab-border)] bg-white px-4 py-4">
            <p class="font-mono text-xs uppercase tracking-[0.16em] text-[var(--color-lab-muted)]">Métodos pagos</p>
            <p class="mt-2 font-mono text-2xl font-bold text-black">{{ $paymentMix->count() }}</p>
            <p class="mt-1 font-mono text-[10px] text-[var(--color-lab-muted)]">{{ $paymentMix->pluck('label')->implode(' · ') ?: 'Sem pagamento' }}</p>
        </div>
        <div class="border border-[var(--color-lab-border)] bg-white px-4 py-4">
            <p class="font-mono text-xs uppercase tracking-[0.16em] text-[var(--color-lab-muted)]">Lucro médio/un.</p>
            <p class="mt-2 font-mono text-2xl font-bold text-black">
                {{ $summary['unidades'] > 0 ? 'R$ ' . number_format($summary['lucro_ticket_estimado'] / $summary['unidades'], 2, ',', '.') : 'R$ 0,00' }}
            </p>
            <p class="mt-1 font-mono text-[10px] text-[var(--color-lab-muted)]">lucro líquido real dividido pelo total de unidades; não é lucro individual por SKU</p>
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
                        'canceled' => 'border-gray-300 bg-[linear-gradient(135deg,#f3f4f6_0%,#ffffff_100%)]',
                        'negative' => 'border-red-200 bg-[linear-gradient(135deg,#fff5f5_0%,#ffffff_100%)]',
                        'low' => 'border-yellow-200 bg-[linear-gradient(135deg,#fffceb_0%,#ffffff_100%)]',
                        'missing_cost' => 'border-blue-200 bg-[linear-gradient(135deg,#eff6ff_0%,#ffffff_100%)]',
                        default => 'border-[var(--color-lab-border)] bg-white',
                    };
                    $barClass = match ($item['health']) {
                        'canceled' => 'bg-gray-300',
                        'negative' => 'bg-red-500',
                        'low' => 'bg-yellow-500',
                        'missing_cost' => 'bg-blue-500',
                        default => 'bg-black',
                    };
                    $badgeText = match ($item['health']) {
                        'canceled' => 'Cancelado',
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
                                            <span class="inline-flex items-center px-2 py-1 font-mono text-[10px] uppercase tracking-widest border {{ $item['health'] === 'canceled' ? 'border-gray-300 text-gray-500 bg-gray-50' : ($item['health'] === 'negative' ? 'border-red-300 text-red-700 bg-red-50' : ($item['health'] === 'low' ? 'border-yellow-300 text-yellow-700 bg-yellow-50' : ($item['health'] === 'missing_cost' ? 'border-blue-300 text-blue-700 bg-blue-50' : 'border-emerald-300 text-emerald-700 bg-emerald-50'))) }}">
                                                {{ $badgeText }}
                                            </span>
                                            <span class="inline-flex items-center px-2 py-1 font-mono text-[10px] uppercase tracking-widest border border-[var(--color-lab-border)] text-[var(--color-lab-muted)] bg-white">
                                                {{ $item['status_preparacao_label'] }}
                                            </span>
                                            @if($item['is_top_price'])
                                                <span class="inline-flex items-center px-2 py-1 font-mono text-[10px] uppercase tracking-widest border border-black bg-black text-white">Top preço</span>
                                            @endif
                                            @if($item['is_worst_margin'])
                                                <span class="inline-flex items-center px-2 py-1 font-mono text-[10px] uppercase tracking-widest border border-red-300 bg-white text-red-600">Pior margem</span>
                                            @endif
                                        </div>

                                        @if($item['variant_label'])
                                            <p class="mt-2 font-mono text-[10px] uppercase tracking-[0.16em] text-[var(--color-lab-muted)] break-words">{{ $item['variant_label'] }}</p>
                                        @endif

                                        <div class="mt-4 grid grid-cols-2 gap-2">
                                            <div class="border border-[var(--color-lab-border)] bg-white px-3 py-3 min-w-0 overflow-hidden">
                                                <p class="font-mono text-xs uppercase tracking-[0.16em] text-[var(--color-lab-muted)] truncate">Qtd</p>
                                                <p class="mt-1 font-mono text-sm font-bold text-black truncate">{{ $item['quantidade'] }}</p>
                                            </div>
                                            <div class="border border-[var(--color-lab-border)] bg-white px-3 py-3 min-w-0 overflow-hidden">
                                                <p class="font-mono text-xs uppercase tracking-[0.16em] text-[var(--color-lab-muted)] truncate">Venda un.</p>
                                                <p class="mt-1 font-mono text-sm font-bold {{ !$item['is_canceled'] && $item['desconto_rateado'] > 0 ? 'text-[var(--color-lab-muted)] line-through' : 'text-black' }} truncate">R$ {{ number_format($item['preco_unitario'], 2, ',', '.') }}</p>
                                            </div>
                                            @if(!$item['is_canceled'] && $item['desconto_rateado'] > 0)
                                            <div class="border border-emerald-200 bg-emerald-50 px-3 py-3 min-w-0 overflow-hidden">
                                                <p class="font-mono text-xs uppercase tracking-[0.16em] text-emerald-700 truncate">Pago un.</p>
                                                <p class="mt-1 font-mono text-sm font-bold text-emerald-800 truncate">R$ {{ number_format($item['receita_liquida'] / $item['quantidade'], 2, ',', '.') }}</p>
                                            </div>
                                            <div class="border border-emerald-200 bg-emerald-50 px-3 py-3 min-w-0 overflow-hidden">
                                                <p class="font-mono text-xs uppercase tracking-[0.16em] text-emerald-700 truncate">Desconto item</p>
                                                <p class="mt-1 font-mono text-sm font-bold text-emerald-800 truncate">- R$ {{ number_format($item['desconto_rateado'], 2, ',', '.') }}</p>
                                            </div>
                                            @endif
                                            <div class="border border-[var(--color-lab-border)] bg-white px-3 py-3 min-w-0 overflow-hidden">
                                                <p class="font-mono text-xs uppercase tracking-[0.16em] text-[var(--color-lab-muted)] truncate">Receita</p>
                                                <p class="mt-1 font-mono text-sm font-bold text-black truncate">R$ {{ number_format($item['receita'], 2, ',', '.') }}</p>
                                            </div>
                                            @if($item['is_canceled'])
                                            <div class="border border-red-200 bg-red-50 px-3 py-3 min-w-0 overflow-hidden">
                                                <p class="font-mono text-xs uppercase tracking-[0.16em] text-red-700 truncate">Estorno</p>
                                                <p class="mt-1 font-mono text-sm font-bold text-red-700 truncate">- R$ {{ number_format($item['estorno'], 2, ',', '.') }}</p>
                                            </div>
                                            @endif
                                            <div class="border border-[var(--color-lab-border)] bg-white px-3 py-3 min-w-0 overflow-hidden">
                                                <p class="font-mono text-xs uppercase tracking-[0.16em] text-[var(--color-lab-muted)] truncate">Fonte custo</p>
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
                                    <p class="font-mono text-xs uppercase tracking-[0.16em] text-[var(--color-lab-muted)]">Custo un.</p>
                                    <p class="mt-1 font-mono text-sm font-bold text-black">
                                        {{ $item['custo_unitario'] !== null ? 'R$ ' . number_format($item['custo_unitario'], 2, ',', '.') : 'N/A' }}
                                    </p>
                                </div>
                                <div class="border border-[var(--color-lab-border)] bg-white px-3 py-3">
                                    <p class="font-mono text-xs uppercase tracking-[0.16em] text-[var(--color-lab-muted)]">Custo total</p>
                                    <p class="mt-1 font-mono text-sm font-bold text-black">
                                        {{ $item['custo_total'] !== null ? 'R$ ' . number_format($item['custo_total'], 2, ',', '.') : 'N/A' }}
                                    </p>
                                </div>
                                <div class="border border-[var(--color-lab-border)] bg-white px-3 py-3">
                                    <p class="font-mono text-xs uppercase tracking-[0.16em] text-[var(--color-lab-muted)]">Lucro un.</p>
                                    <p class="mt-1 font-mono text-sm font-bold {{ $item['lucro_unitario'] !== null && $item['lucro_unitario'] < 0 ? 'text-red-600' : 'text-black' }}">
                                        {{ $item['lucro_unitario'] !== null ? 'R$ ' . number_format($item['lucro_unitario'], 2, ',', '.') : 'N/A' }}
                                    </p>
                                </div>
                                <div class="border border-[var(--color-lab-border)] bg-white px-3 py-3">
                                    <p class="font-mono text-xs uppercase tracking-[0.16em] text-[var(--color-lab-muted)]">Lucro total</p>
                                    <p class="mt-1 font-mono text-sm font-bold {{ $item['lucro_total'] !== null && $item['lucro_total'] < 0 ? 'text-red-600' : 'text-black' }}">
                                        {{ $item['lucro_total'] !== null ? 'R$ ' . number_format($item['lucro_total'], 2, ',', '.') : 'N/A' }}
                                    </p>
                                </div>
                                <div class="border border-[var(--color-lab-border)] bg-white px-3 py-3">
                                    <p class="font-mono text-xs uppercase tracking-[0.16em] text-[var(--color-lab-muted)]">Comissão parceiro</p>
                                    <p class="mt-1 font-mono text-sm font-bold {{ $item['comissao_parceiro'] !== null ? 'text-emerald-700' : 'text-black' }}">
                                        {{ $item['comissao_parceiro'] !== null ? 'R$ ' . number_format($item['comissao_parceiro'], 2, ',', '.') : 'N/A' }}
                                    </p>
                                </div>
                                <div class="border border-[var(--color-lab-border)] bg-white px-3 py-3">
                                    <p class="font-mono text-xs uppercase tracking-[0.16em] text-[var(--color-lab-muted)]">Lucro após parceiro</p>
                                    <p class="mt-1 font-mono text-sm font-bold {{ $item['lucro_apos_parceiro'] !== null && $item['lucro_apos_parceiro'] < 0 ? 'text-red-600' : 'text-black' }}">
                                        {{ $item['lucro_apos_parceiro'] !== null ? 'R$ ' . number_format($item['lucro_apos_parceiro'], 2, ',', '.') : 'N/A' }}
                                    </p>
                                </div>
                            </div>

                            <div class="mt-4 border border-[var(--color-lab-border)] bg-[var(--color-lab-bg)] px-3 py-3">
                                <div class="flex items-center justify-between gap-3">
                                    <p class="font-mono text-[9px] uppercase tracking-[0.18em] text-[var(--color-lab-muted)]">Margem produto</p>
                                    <p class="font-mono text-xs font-bold {{ $item['margem_percentual'] !== null && $item['margem_percentual'] < 0 ? 'text-red-600' : 'text-black' }}">
                                        {{ $item['margem_percentual'] !== null ? number_format($item['margem_percentual'], 1, ',', '.') . '%' : 'Sem base de custo' }}
                                    </p>
                                </div>
                                <div class="mt-3 h-2 bg-white border border-[var(--color-lab-border)] overflow-hidden">
                                    <div class="h-full {{ $barClass }}" style="width: {{ $item['margin_bar_percent'] }}%;"></div>
                                </div>
                                <div class="mt-3 flex items-center justify-between gap-3 font-mono text-[10px] uppercase tracking-[0.14em] text-[var(--color-lab-muted)]">
                                    <span>Rank preço {{ $item['rank_preco'] ? '#' . $item['rank_preco'] : 'N/A' }}</span>
                                    <span>Rank margem {{ $item['rank_margem'] ? '#' . $item['rank_margem'] : 'N/A' }}</span>
                                </div>
                                <p class="mt-2 font-mono text-[10px] text-[var(--color-lab-muted)]">
                                    Margem apenas deste SKU contra custo cadastrado. Comissão calculada sobre item líquido após desconto.
                                </p>
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

    <section class="border border-[var(--color-lab-border)] bg-white">
        <div class="px-5 py-4 sm:px-6 border-b border-[var(--color-lab-border)]">
            <div class="flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-between">
                <div>
                    <p class="font-mono text-[10px] uppercase tracking-[0.22em] text-[var(--color-lab-muted)]">Receita vs custo</p>
                    <h4 class="mt-1 text-lg font-bold tracking-tight text-black">Margem por item vendido</h4>
                </div>
                @if(($summary['itens_sem_custo'] ?? 0) > 0)
                    <p class="font-mono text-[10px] uppercase tracking-[0.16em] text-[var(--color-lab-muted)]">{{ $summary['itens_sem_custo'] }} item(ns) sem custo cadastrado</p>
                @endif
            </div>
        </div>

        <div class="p-4 sm:p-6 space-y-4">
            {{-- 4 summary cards --}}
            <div class="grid grid-cols-2 xl:grid-cols-4 gap-3">
                <div class="border border-[var(--color-lab-border)] bg-[var(--color-lab-bg)] px-4 py-4">
                    <p class="font-mono text-xs uppercase tracking-[0.16em] text-[var(--color-lab-muted)]">Receita (itens)</p>
                    <p class="mt-2 font-mono text-xl font-bold text-black">R$ {{ number_format($summary['receita_itens'] ?? 0, 2, ',', '.') }}</p>
                    <p class="mt-1 font-mono text-[10px] text-[var(--color-lab-muted)]">Após desconto de cupom</p>
                </div>
                <div class="border border-[var(--color-lab-border)] bg-[var(--color-lab-bg)] px-4 py-4">
                    <p class="font-mono text-xs uppercase tracking-[0.16em] text-[var(--color-lab-muted)]">Custo total</p>
                    <p class="mt-2 font-mono text-xl font-bold text-black">R$ {{ number_format($summary['custo_total_estimado'] ?? 0, 2, ',', '.') }}</p>
                    <p class="mt-1 font-mono text-[10px] text-[var(--color-lab-muted)]">{{ $costModeLabel }}</p>
                </div>
                <div class="border border-[var(--color-lab-border)] bg-[var(--color-lab-bg)] px-4 py-4">
                    @php $lucro = $summary['lucro_produtos_estimado'] ?? null; @endphp
                    <p class="font-mono text-xs uppercase tracking-[0.16em] text-[var(--color-lab-muted)]">Lucro bruto</p>
                    <p class="mt-2 font-mono text-xl font-bold {{ $lucro !== null && $lucro < 0 ? 'text-red-600' : 'text-black' }}">
                        @if($lucro !== null)
                            R$ {{ number_format($lucro, 2, ',', '.') }}
                        @else
                            N/A
                        @endif
                    </p>
                </div>
                <div class="border border-[var(--color-lab-border)] bg-[var(--color-lab-bg)] px-4 py-4">
                    @php $margem = $summary['margem_produtos_percentual'] ?? null; @endphp
                    <p class="font-mono text-xs uppercase tracking-[0.16em] text-[var(--color-lab-muted)]">Margem</p>
                    @if($margem !== null)
                        <p class="mt-2 font-mono text-xl font-bold {{ $margem < 0 ? 'text-red-600' : 'text-black' }}">{{ number_format($margem, 1, ',', '.') }}%</p>
                    @else
                        <p class="mt-2 font-mono text-xl font-bold text-gray-400">N/A</p>
                        @if(($summary['itens_sem_custo'] ?? 0) > 0)
                            <p class="mt-1 font-mono text-[10px] text-[var(--color-lab-muted)]">{{ $summary['itens_sem_custo'] }} sem custo</p>
                        @endif
                    @endif
                </div>
            </div>

            {{-- Per-item table --}}
            @php $activeItems = collect($items)->reject(fn($item) => $item['is_canceled'])->values(); @endphp
            @if($activeItems->count())
                <div class="overflow-x-auto border border-[var(--color-lab-border)]">
                    <table class="w-full min-w-[860px] text-sm">
                        <thead>
                            <tr class="border-b border-[var(--color-lab-border)] bg-[var(--color-lab-bg)]">
                                <th class="px-4 py-3 text-left font-mono text-[10px] uppercase tracking-widest text-[var(--color-lab-muted)]">Produto</th>
                                <th class="px-4 py-3 text-right font-mono text-[10px] uppercase tracking-widest text-[var(--color-lab-muted)]">Qtd</th>
                                <th class="px-4 py-3 text-right font-mono text-[10px] uppercase tracking-widest text-[var(--color-lab-muted)]">Venda/un.</th>
                                <th class="px-4 py-3 text-right font-mono text-[10px] uppercase tracking-widest text-[var(--color-lab-muted)]">Custo/un.</th>
                                <th class="px-4 py-3 text-right font-mono text-[10px] uppercase tracking-widest text-[var(--color-lab-muted)]">Lucro/un.</th>
                                <th class="px-4 py-3 text-right font-mono text-[10px] uppercase tracking-widest text-[var(--color-lab-muted)]">Lucro total</th>
                                <th class="px-4 py-3 text-right font-mono text-[10px] uppercase tracking-widest text-[var(--color-lab-muted)]">Margem</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($activeItems as $row)
                                <tr class="border-b border-[var(--color-lab-border)] last:border-b-0">
                                    <td class="px-4 py-3">
                                        <p class="font-mono text-xs font-bold text-black break-words">{{ $row['produto_nome'] }}</p>
                                        @if(!empty($row['variant_label']))
                                            <p class="mt-0.5 font-mono text-[10px] text-[var(--color-lab-muted)]">{{ $row['variant_label'] }}</p>
                                        @endif
                                        <span class="mt-1 inline-block font-mono text-[9px] uppercase tracking-[0.14em] px-1.5 py-0.5 {{ $row['cost_source'] === 'declarado' ? 'border border-black text-black' : 'text-gray-400' }}">
                                            {{ $row['cost_source'] === 'declarado' ? 'Custo real' : ($row['cost_source'] === 'catalogo' ? 'Catálogo' : 'Sem custo') }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 text-right font-mono text-xs text-black">{{ $row['quantidade'] }}</td>
                                    <td class="px-4 py-3 text-right font-mono text-xs text-black">R$ {{ number_format($row['preco_unitario'], 2, ',', '.') }}</td>
                                    <td class="px-4 py-3 text-right font-mono text-xs {{ $row['custo_unitario'] === null ? 'text-gray-400' : 'text-black' }}">
                                        {{ $row['custo_unitario'] !== null ? 'R$ ' . number_format($row['custo_unitario'], 2, ',', '.') : 'Sem custo' }}
                                    </td>
                                    <td class="px-4 py-3 text-right font-mono text-xs {{ $row['lucro_unitario'] === null ? 'text-gray-400' : ($row['lucro_unitario'] < 0 ? 'text-red-600' : 'text-black') }}">
                                        {{ $row['lucro_unitario'] !== null ? 'R$ ' . number_format($row['lucro_unitario'], 2, ',', '.') : 'N/A' }}
                                    </td>
                                    <td class="px-4 py-3 text-right font-mono text-xs {{ $row['lucro_total'] === null ? 'text-gray-400' : ($row['lucro_total'] < 0 ? 'text-red-600' : 'text-black') }}">
                                        {{ $row['lucro_total'] !== null ? 'R$ ' . number_format($row['lucro_total'], 2, ',', '.') : 'N/A' }}
                                    </td>
                                    <td class="px-4 py-3 text-right font-mono text-xs {{ $row['margem_percentual'] === null ? 'text-gray-400' : ($row['margem_percentual'] < 0 ? 'text-red-600 font-bold' : 'text-black') }}">
                                        {{ $row['margem_percentual'] !== null ? number_format($row['margem_percentual'], 1, ',', '.') . '%' : 'N/A' }}
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                        <tfoot>
                            <tr class="border-t-2 border-[var(--color-lab-border)] bg-[var(--color-lab-bg)]">
                                <td class="px-4 py-3 font-mono text-[10px] uppercase tracking-widest text-[var(--color-lab-muted)]">Total</td>
                                <td class="px-4 py-3 text-right font-mono text-xs font-bold text-black">{{ $summary['unidades'] ?? '—' }}</td>
                                <td class="px-4 py-3 text-right font-mono text-xs font-bold text-black">R$ {{ number_format($summary['receita_itens'] ?? 0, 2, ',', '.') }}</td>
                                <td class="px-4 py-3 text-right font-mono text-xs font-bold text-black">R$ {{ number_format($summary['custo_total_estimado'] ?? 0, 2, ',', '.') }}</td>
                                <td class="px-4 py-3"></td>
                                @php $lucroTotal = $summary['lucro_produtos_estimado'] ?? null; @endphp
                                <td class="px-4 py-3 text-right font-mono text-xs font-bold {{ $lucroTotal !== null && $lucroTotal < 0 ? 'text-red-600' : 'text-black' }}">
                                    {{ $lucroTotal !== null ? 'R$ ' . number_format($lucroTotal, 2, ',', '.') : '—' }}
                                </td>
                                @php $margemTotal = $summary['margem_produtos_percentual'] ?? null; @endphp
                                <td class="px-4 py-3 text-right font-mono text-xs font-bold {{ $margemTotal === null ? 'text-gray-400' : ($margemTotal < 0 ? 'text-red-600' : 'text-black') }}">
                                    {{ $margemTotal !== null ? number_format($margemTotal, 1, ',', '.') . '%' : 'N/A' }}
                                </td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            @else
                <p class="font-mono text-xs text-[var(--color-lab-muted)]">Todos os itens do pedido foram cancelados.</p>
            @endif

            {{-- Audit section: shown only when there are declared costs to compare --}}
            @if($hasDeclaredCosts && ($costComparison['rows'] ?? collect())->count())
                <details class="border border-[var(--color-lab-border)]">
                    <summary class="px-4 py-3 cursor-pointer font-mono text-xs uppercase tracking-[0.16em] text-[var(--color-lab-muted)] hover:text-black select-none list-none flex items-center justify-between">
                        <span>Auditoria — custo declarado vs catálogo</span>
                        <span>{{ $costComparison['rows']->count() }} item(ns)</span>
                    </summary>
                    <div class="border-t border-[var(--color-lab-border)] overflow-x-auto">
                        <table class="w-full min-w-[780px] text-sm">
                            <thead>
                                <tr class="border-b border-[var(--color-lab-border)] bg-[var(--color-lab-bg)]">
                                    <th class="px-4 py-3 text-left font-mono text-[10px] uppercase tracking-widest text-[var(--color-lab-muted)]">Produto</th>
                                    <th class="px-4 py-3 text-right font-mono text-[10px] uppercase tracking-widest text-[var(--color-lab-muted)]">Qtd</th>
                                    <th class="px-4 py-3 text-right font-mono text-[10px] uppercase tracking-widest text-[var(--color-lab-muted)]">Declarado/un.</th>
                                    <th class="px-4 py-3 text-right font-mono text-[10px] uppercase tracking-widest text-[var(--color-lab-muted)]">Catálogo/un.</th>
                                    <th class="px-4 py-3 text-right font-mono text-[10px] uppercase tracking-widest text-[var(--color-lab-muted)]">Dif. total</th>
                                    <th class="px-4 py-3 text-right font-mono text-[10px] uppercase tracking-widest text-[var(--color-lab-muted)]">%</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($costComparison['rows'] as $row)
                                    <tr class="border-b border-[var(--color-lab-border)] last:border-b-0">
                                        <td class="px-4 py-3">
                                            <p class="font-mono text-xs font-bold text-black break-words">{{ $row['produto_nome'] }}</p>
                                            <p class="mt-1 font-mono text-[10px] text-[var(--color-lab-muted)]">{{ $row['variant_label'] ?: $row['status_preparacao_label'] }}</p>
                                        </td>
                                        <td class="px-4 py-3 text-right font-mono text-xs text-black">{{ $row['quantidade'] }}</td>
                                        <td class="px-4 py-3 text-right font-mono text-xs text-black">R$ {{ number_format($row['real_unit_cost'], 2, ',', '.') }}</td>
                                        <td class="px-4 py-3 text-right font-mono text-xs {{ $row['catalog_unit_cost'] === null ? 'text-gray-400' : 'text-black' }}">
                                            {{ $row['catalog_unit_cost'] !== null ? 'R$ ' . number_format($row['catalog_unit_cost'], 2, ',', '.') : 'Sem custo' }}
                                        </td>
                                        <td class="px-4 py-3 text-right font-mono text-xs font-bold {{ $row['delta_total'] > 0 ? 'text-red-600' : ($row['delta_total'] < 0 ? 'text-emerald-700' : 'text-black') }}">
                                            @if($row['delta_total'] === null)
                                                N/A
                                            @else
                                                {{ $row['delta_total'] > 0 ? '+ ' : ($row['delta_total'] < 0 ? '- ' : '') }}R$ {{ number_format(abs($row['delta_total']), 2, ',', '.') }}
                                            @endif
                                        </td>
                                        <td class="px-4 py-3 text-right font-mono text-xs text-[var(--color-lab-muted)]">
                                            {{ $row['delta_percent'] !== null ? number_format($row['delta_percent'], 1, ',', '.') . '%' : 'N/A' }}
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </details>
            @endif
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
                <p class="font-mono text-[10px] uppercase tracking-widest text-[var(--color-lab-muted)] mb-3">Preparação e custos</p>
                <form method="POST" action="{{ route('admin.pedidos.preparacao', $pedido) }}" enctype="multipart/form-data" class="space-y-4">
                    @csrf

                    <div class="space-y-2 font-mono text-[10px] text-[var(--color-lab-muted)]">
                        <p>
                            <span class="text-black font-bold">{{ $summary['itens_com_custo_declarado'] ?? 0 }}</span>
                            linha(s) com custo real declarado
                        </p>
                        <p>
                            <span class="text-black font-bold">{{ $summary['itens_cancelados'] ?? 0 }}</span>
                            linha(s) cancelada(s) na preparação
                        </p>
                        @if($pedido->nota_fiscal_imagem_path)
                            <a href="{{ asset('storage/' . $pedido->nota_fiscal_imagem_path) }}"
                               target="_blank"
                               rel="noopener"
                               class="inline-flex items-center gap-1 underline hover:text-black">
                                Ver comprovante / nota fiscal &#8599;
                            </a>
                        @else
                            <p>Nenhum comprovante ou nota fiscal anexado.</p>
                        @endif
                    </div>

                    <div>
                        <label class="font-mono text-[10px] uppercase tracking-widest text-[var(--color-lab-muted)] block mb-2">Comprovante / imagem da nota fiscal</label>
                        <input type="file" name="nota_fiscal_imagem" accept="image/jpeg,image/png,image/webp"
                               class="w-full px-3 py-2 border border-[var(--color-lab-border)] text-xs font-mono bg-white focus:outline-none focus:border-black">
                    </div>

                    <div class="space-y-3">
                        @foreach($items as $item)
                            <div class="border border-[var(--color-lab-border)] bg-[var(--color-lab-bg)] p-3">
                                <div class="flex items-start justify-between gap-3 mb-2">
                                    <div class="min-w-0">
                                        <p class="font-mono text-xs font-bold text-black break-words">{{ $item['produto_nome'] }}</p>
                                        <p class="mt-0.5 font-mono text-[10px] text-[var(--color-lab-muted)]">
                                            Qtd {{ $item['quantidade'] }}{{ $item['variant_label'] ? ' · ' . $item['variant_label'] : '' }}
                                        </p>
                                    </div>
                                    <span class="shrink-0 font-mono text-[9px] uppercase tracking-widest border border-[var(--color-lab-border)] px-2 py-1 text-[var(--color-lab-muted)]">
                                        {{ strtoupper(str_replace('_', ' ', $item['cost_source'])) }}
                                    </span>
                                </div>
                                <label class="font-mono text-[10px] uppercase tracking-widest text-[var(--color-lab-muted)] block mb-1">Confirmação do item</label>
                                <select name="itens[{{ $item['id'] }}][status_preparacao]"
                                        class="w-full mb-3 px-3 py-2 border border-[var(--color-lab-border)] text-sm font-mono focus:outline-none focus:border-black bg-white">
                                    @foreach($preparationStatusLabels as $status => $label)
                                        <option value="{{ $status }}" @selected($item['status_preparacao'] === $status)>{{ $label }}</option>
                                    @endforeach
                                </select>
                                <label class="font-mono text-[10px] uppercase tracking-widest text-[var(--color-lab-muted)] block mb-1">Custo real unitário</label>
                                <input name="itens[{{ $item['id'] }}][custo_unitario_declarado]"
                                       value="{{ $item['custo_unitario'] !== null ? number_format($item['custo_unitario'], 2, ',', '.') : '' }}"
                                       placeholder="ex: 599,90"
                                       class="w-full px-3 py-2 border border-[var(--color-lab-border)] text-sm font-mono focus:outline-none focus:border-black bg-white">
                                <p class="mt-1 font-mono text-[10px] text-[var(--color-lab-muted)]">Obrigatório para itens comprados/entregues. Item cancelado entra como estorno da separação.</p>
                            </div>
                        @endforeach
                    </div>

                    <button type="submit"
                            class="w-full bg-black text-white px-4 py-2.5 text-xs font-bold tracking-widest uppercase hover:bg-gray-800 transition-colors">
                        Salvar preparação
                    </button>
                </form>
            </div>

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
                <div class="mt-3">
                    <label class="block font-mono text-[10px] uppercase tracking-widest text-[var(--color-lab-muted)] mb-1">Custo real do frete (R$)</label>
                    <input type="number"
                           step="0.01"
                           min="0"
                           id="frete-custo-real-input-{{ $pedido->id }}"
                           name="frete_custo_real"
                           value="{{ $pedido->frete_custo_real }}"
                           placeholder="0,00"
                           class="w-full font-mono text-sm px-3 py-2 border border-[var(--color-lab-border)] bg-white focus:outline-none focus:border-black transition-colors">
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
