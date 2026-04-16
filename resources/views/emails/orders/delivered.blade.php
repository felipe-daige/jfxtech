@extends('emails.layout')

@section('header_label', 'PEDIDO ENTREGUE')

@section('preheader')
Seu pedido #{{ $pedido->id }} foi entregue! Esperamos que goste dos seus novos produtos.
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
        'iconUrl' => 'https://jfxtech.com.br/storage/images/emails/delivered.png',
        'iconAlt' => 'Entregue',
        'title' => 'Pedido entregue',
    ])

    {{-- Subtitle --}}
    <p style="margin:0 0 32px;font-size:15px;line-height:1.6;color:#333333;">
        Seu pedido <strong style="color:#000000;font-weight:800;">#{{ $pedido->id }}</strong> foi marcado como entregue. Esperamos que aproveite seus novos produtos!
    </p>

    {{-- Stepper — all complete --}}
    @include('emails.orders._stepper', ['step' => 5])

    {{-- Feedback card --}}
    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="margin:24px 0;border:1px solid #E5E5E5;">
        <tr>
            <td style="background-color:#000000;padding:8px 20px;">
                <span style="font-family:'Courier New',Courier,monospace;font-size:12px;font-weight:700;letter-spacing:0.1em;color:#E5E5E5;text-transform:uppercase;">Feedback</span>
            </td>
        </tr>
        <tr>
            <td style="padding:20px;background-color:#FAFAFA;">
                <p style="margin:0 0 8px;font-size:14px;font-weight:700;color:#111111;">Gostou da experiência?</p>
                <p style="margin:0;font-size:13px;color:#666666;line-height:1.6;">
                    Sua opinião é muito importante para nós. Se tiver qualquer dúvida ou sugestão, entre em contato pelo
                    <a href="mailto:contato@jfxtech.com.br" style="color:#111111;text-decoration:underline;font-weight:600;">contato@jfxtech.com.br</a>.
                </p>
            </td>
        </tr>
    </table>

    {{-- Order summary --}}
    @include('emails.orders._order-summary')

    {{-- CTA --}}
    @include('emails.orders._cta-button', ['buttonLabel' => 'VER PEDIDO'])
@endsection
