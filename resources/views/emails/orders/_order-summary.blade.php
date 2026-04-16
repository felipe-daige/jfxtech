{{-- Order info card — lab-style, no border-radius, sharp edges --}}
<table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="border:1px solid #E5E5E5;margin:24px 0;">
    {{-- Card header --}}
    <tr>
        <td style="background-color:#000000;padding:12px 20px;">
            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0">
                <tr>
                    <td>
                        <span style="font-family:'Courier New',Courier,monospace;font-size:12px;font-weight:700;letter-spacing:0.1em;color:#E5E5E5;text-transform:uppercase;">Resumo do pedido</span>
                    </td>
                    <td align="right">
                        <span style="font-family:'Courier New',Courier,monospace;font-size:12px;font-weight:700;color:#FFFFFF;letter-spacing:0.06em;">#{{ $pedido->id }}</span>
                    </td>
                </tr>
            </table>
        </td>
    </tr>
    {{-- Status --}}
    <tr>
        <td style="padding:14px 20px;border-bottom:1px solid #E5E5E5;">
            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0">
                <tr>
                    <td>
                        <span style="font-family:'Courier New',Courier,monospace;font-size:9px;letter-spacing:0.15em;color:#888888;text-transform:uppercase;">Status</span>
                    </td>
                    <td align="right">
                        <span style="font-size:13px;font-weight:600;color:#111111;">{{ \App\Enums\PedidoStatus::label($pedido->status) }}</span>
                    </td>
                </tr>
            </table>
        </td>
    </tr>
    {{-- Frete --}}
    @if($pedido->frete_tipo || $pedido->frete_valor)
    <tr>
        <td style="padding:14px 20px;border-bottom:1px solid #E5E5E5;">
            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0">
                <tr>
                    <td>
                        <span style="font-family:'Courier New',Courier,monospace;font-size:9px;letter-spacing:0.15em;color:#888888;text-transform:uppercase;">Frete</span>
                    </td>
                    <td align="right">
                        <span style="font-size:13px;color:#111111;">
                            @if($pedido->frete_tipo){{ $pedido->frete_tipo }} — @endif
                            @if((float) $pedido->frete_valor > 0)
                                R$ {{ number_format((float) $pedido->frete_valor, 2, ',', '.') }}
                            @else
                                <span style="font-weight:700;">GRÁTIS</span>
                            @endif
                        </span>
                    </td>
                </tr>
            </table>
        </td>
    </tr>
    @endif
    {{-- Total --}}
    <tr>
        <td style="padding:14px 20px;background-color:#FAFAFA;">
            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0">
                <tr>
                    <td>
                        <span style="font-family:'Courier New',Courier,monospace;font-size:9px;letter-spacing:0.15em;color:#888888;text-transform:uppercase;">Total</span>
                    </td>
                    <td align="right">
                        <span style="font-size:18px;font-weight:800;color:#111111;">R$ {{ number_format((float) $pedido->valor_total, 2, ',', '.') }}</span>
                    </td>
                </tr>
            </table>
        </td>
    </tr>
</table>

{{-- Items list --}}
@if($pedido->itens->isNotEmpty())
<table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="margin:0 0 24px;">
    <tr>
        <td style="padding-bottom:12px;">
            <span style="font-family:'Courier New',Courier,monospace;font-size:12px;font-weight:700;letter-spacing:0.1em;color:#000000;text-transform:uppercase;">Itens do pedido</span>
        </td>
    </tr>
    @foreach($pedido->itens as $item)
    <tr>
        <td style="padding:12px 0;{{ !$loop->last ? 'border-bottom:1px solid #E5E5E5;' : '' }}">
            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0">
                <tr>
                    <td style="vertical-align:top;">
                        <span style="font-size:14px;color:#111111;font-weight:600;">{{ $item->produto->nome ?? 'Produto' }}</span><br>
                        <span style="font-family:'Courier New',Courier,monospace;font-size:10px;letter-spacing:0.1em;color:#888888;text-transform:uppercase;">QTD. {{ $item->quantidade }}</span>
                    </td>
                    <td align="right" style="vertical-align:top;white-space:nowrap;">
                        <span style="font-size:14px;font-weight:700;color:#111111;">R$ {{ number_format((float) $item->subtotal, 2, ',', '.') }}</span>
                    </td>
                </tr>
            </table>
        </td>
    </tr>
    @endforeach
</table>
@endif
