@extends('emails.layout')

@section('header_label', 'PEDIDO ENVIADO')

@section('preheader')
Seu pedido #{{ $pedido->id }} foi enviado!{{ $pedido->codigo_rastreio ? ' Rastreio: ' . $pedido->codigo_rastreio : '' }}
@endsection

@section('content')
    @php
        $clienteNome = $pedido->customer_name ?? $pedido->user?->name;
    @endphp

    {{-- Greeting --}}
    @if($clienteNome)
        <p style="margin:0 0 4px;font-size:14px;color:#666666;">Olá, {{ $clienteNome }}.</p>
    @endif

    @include('emails.orders._hero-title', [
        'iconUrl' => 'https://jfxtech.com.br/storage/images/emails/shipped.png',
        'iconAlt' => 'Enviado',
        'title' => 'Pedido enviado!',
    ])

    {{-- Subtitle --}}
    <p style="margin:0 0 4px;font-size:15px;line-height:1.6;color:#333333;">
        Seu pedido <strong style="color:#000000;font-weight:800;">#{{ $pedido->id }}</strong> saiu para entrega.
    </p>
    <p style="margin:0 0 32px;font-size:15px;line-height:1.6;color:#333333;">
        Fique de olho — ele está a caminho!
    </p>

    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="margin:0 0 24px;border:1px solid #E5E5E5;">
        <tr>
            <td style="background-color:#000000;padding:8px 20px;">
                <span style="font-family:'Courier New',Courier,monospace;font-size:12px;font-weight:700;letter-spacing:0.1em;color:#E5E5E5;text-transform:uppercase;">Código de rastreio</span>
            </td>
        </tr>
        <tr>
            <td style="padding:20px;text-align:center;background-color:#FAFAFA;">
                <span style="font-family:'Courier New',Courier,monospace;font-size:18px;font-weight:700;color:#111111;letter-spacing:0.08em;">
                    {{ $pedido->codigo_rastreio }}
                </span>
            </td>
        </tr>
        @if(isset($trackingUrl) && $trackingUrl)
        <tr>
            <td style="padding:0 20px 20px;text-align:center;background-color:#FAFAFA;">
                <a href="{{ $trackingUrl }}" target="_blank" rel="noopener noreferrer" style="display:inline-block;padding:12px 24px;font-family:'Courier New',Courier,monospace;font-size:10px;font-weight:700;color:#000000;text-decoration:none;letter-spacing:0.15em;text-transform:uppercase;border:1px solid #000000;background-color:#FFFFFF;">
                    RASTREAR NOS CORREIOS
                </a>
            </td>
        </tr>
        @endif
    </table>

    @if(isset($deliveryConfirmationUrl) && $deliveryConfirmationUrl)
    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="margin:0 0 24px;">
        <tr>
            <td align="center">
                <table role="presentation" cellspacing="0" cellpadding="0" border="0">
                    <tr>
                        <td style="background-color:#FFFFFF;border:1px solid #000000;">
                            <a href="{{ $deliveryConfirmationUrl }}" target="_blank" rel="noopener noreferrer" style="display:inline-block;padding:14px 32px;font-family:'Courier New',Courier,monospace;font-size:10px;font-weight:700;color:#000000;text-decoration:none;letter-spacing:0.15em;text-transform:uppercase;">
                                CONFIRMAR ENTREGA
                            </a>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
    @endif

    {{-- Stepper --}}
    @include('emails.orders._stepper', ['step' => 4])

    {{-- Order summary --}}
    @include('emails.orders._order-summary')

    {{-- CTA --}}
    @include('emails.orders._cta-button', ['buttonLabel' => 'VER DETALHES DO ENVIO'])
@endsection
