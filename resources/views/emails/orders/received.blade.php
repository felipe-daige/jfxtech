@extends('emails.layout')

@section('header_label', 'PEDIDO RECEBIDO')

@section('preheader')
Recebemos seu pedido #{{ $pedido->id }}. Estamos aguardando a confirmação do pagamento.
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
        'iconUrl' => 'https://jfxtech.com.br/storage/images/emails/received.png',
        'iconAlt' => 'Recebido',
        'title' => 'Recebemos seu pedido',
    ])

    {{-- Subtitle --}}
    <p style="margin:0 0 4px;font-size:15px;line-height:1.6;color:#333333;">
        Seu pedido <strong style="color:#000000;font-weight:800;">#{{ $pedido->id }}</strong> foi recebido com sucesso.
    </p>
    <p style="margin:0 0 32px;font-size:15px;line-height:1.6;color:#333333;">
        Estamos aguardando a confirmação do pagamento para iniciar a preparação.
    </p>

    {{-- Stepper --}}
    @include('emails.orders._stepper', ['step' => 1])

    {{-- Order summary --}}
    @include('emails.orders._order-summary')

    {{-- CTA --}}
    @include('emails.orders._cta-button', ['buttonLabel' => 'ACOMPANHAR PEDIDO'])
@endsection
