@extends('emails.layout')

@section('header_label', 'EM PREPARAÇÃO')

@section('preheader')
Seu pedido #{{ $pedido->id }} está sendo preparado pela nossa equipe!
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
        'iconUrl' => 'https://jfxtech.com.br/storage/images/emails/processing.png',
        'iconAlt' => 'Em preparação',
        'title' => 'Pedido em preparação',
    ])

    {{-- Subtitle --}}
    <p style="margin:0 0 4px;font-size:15px;line-height:1.6;color:#333333;">
        Boas notícias! Seu pedido <strong style="color:#000000;font-weight:800;">#{{ $pedido->id }}</strong> está sendo preparado.
    </p>
    <p style="margin:0 0 32px;font-size:15px;line-height:1.6;color:#333333;">
        Em breve ele será enviado para o endereço informado.
    </p>

    {{-- Stepper --}}
    @include('emails.orders._stepper', ['step' => 3])

    {{-- Order summary --}}
    @include('emails.orders._order-summary')

    {{-- CTA --}}
    @include('emails.orders._cta-button', ['buttonLabel' => 'ACOMPANHAR PEDIDO'])
@endsection
