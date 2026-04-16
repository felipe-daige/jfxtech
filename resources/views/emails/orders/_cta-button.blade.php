{{-- CTA Button — JFXTech style: sharp, black, monospace uppercase --}}
@if(isset($orderUrl) && $orderUrl)
<table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="margin:28px 0 8px;">
    <tr>
        <td align="center">
            <table role="presentation" cellspacing="0" cellpadding="0" border="0">
                <tr>
                    <td style="background-color:#000000;">
                        <a href="{{ $orderUrl }}" target="_blank" rel="noopener noreferrer" style="display:inline-block;padding:14px 32px;font-family:'Courier New',Courier,monospace;font-size:10px;font-weight:700;color:#FFFFFF;text-decoration:none;letter-spacing:0.15em;text-transform:uppercase;">
                            {{ $buttonLabel ?? 'VER PEDIDO' }}
                        </a>
                    </td>
                </tr>
            </table>
        </td>
    </tr>
</table>
@endif
