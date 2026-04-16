@extends('emails.layout')

@section('header_label', 'PAGAMENTO APROVADO')

@section('preheader')
Pagamento aprovado! Seu pedido #{{ $pedido->id }} será preparado em breve.
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
        'iconUrl' => 'https://jfxtech.com.br/storage/images/emails/approved.png',
        'iconAlt' => 'Pagamento aprovado',
        'title' => 'Pagamento aprovado',
    ])

    {{-- Subtitle --}}
    <p style="margin:0 0 4px;font-size:15px;line-height:1.6;color:#333333;">
        O pagamento do seu pedido <strong style="color:#000000;font-weight:800;">#{{ $pedido->id }}</strong> foi confirmado.
    </p>
    <p style="margin:0 0 32px;font-size:15px;line-height:1.6;color:#333333;">
        Vamos iniciar a preparação do seu pedido em breve.
    </p>

    {{-- Stepper --}}
    @include('emails.orders._stepper', ['step' => 2])

    {{-- Order summary --}}
    @include('emails.orders._order-summary')

    {{-- CTA --}}
    @include('emails.orders._cta-button', ['buttonLabel' => 'ACOMPANHAR PEDIDO'])
@endsection
