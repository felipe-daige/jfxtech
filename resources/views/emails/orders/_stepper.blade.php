{{--
    Stepper partial for order emails.
    Usage: @include('emails.orders._stepper', ['step' => 2])
    Steps: 1=Recebido, 2=Pago, 3=Preparando, 4=Enviado, 5=Entregue
--}}
@php
    $currentStep = $step ?? 1;
    $steps = [
        1 => 'RECEBIDO',
        2 => 'PAGO',
        3 => 'PREPARANDO',
        4 => 'ENVIADO',
        5 => 'ENTREGUE',
    ];
@endphp
<table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="margin:24px 0 8px;border:1px solid #E5E5E5;">
    <tr>
        <td style="background-color:#000000;padding:8px 20px;">
            <span style="font-family:'Courier New',Courier,monospace;font-size:12px;font-weight:700;letter-spacing:0.1em;color:#E5E5E5;text-transform:uppercase;">Progresso do pedido</span>
        </td>
    </tr>
    <tr>
        <td style="padding:20px;">
            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0">
                <tr>
                    @foreach($steps as $i => $label)
                        @php
                            $isComplete = $i < $currentStep;
                            $isCurrent = $i === $currentStep;
                            $bgColor = ($isComplete || $isCurrent) ? '#000000' : '#E5E5E5';
                            $textColor = ($isComplete || $isCurrent) ? '#FFFFFF' : '#AAAAAA';
                            $labelColor = ($isComplete || $isCurrent) ? '#111111' : '#AAAAAA';
                            $display = $isComplete ? '✓' : $i;
                        @endphp
                        <td align="center" style="vertical-align:top;width:20%;">
                            <div style="width:28px;height:28px;background-color:{{ $bgColor }};color:{{ $textColor }};font-size:12px;font-weight:700;line-height:28px;text-align:center;margin:0 auto;">{{ $display }}</div>
                            <p style="margin:6px 0 0;font-family:'Courier New',Courier,monospace;font-size:8px;letter-spacing:0.1em;color:{{ $labelColor }};text-transform:uppercase;font-weight:{{ ($isComplete || $isCurrent) ? '700' : '400' }};">{{ $label }}</p>
                        </td>
                        @if(!$loop->last)
                        <td style="vertical-align:top;padding-top:13px;width:0;">
                            <div style="height:2px;width:100%;background-color:{{ $isComplete ? '#000000' : '#E5E5E5' }};"></div>
                        </td>
                        @endif
                    @endforeach
                </tr>
            </table>
        </td>
    </tr>
</table>
