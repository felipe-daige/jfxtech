@extends('emails.layout')

@section('header_label', 'ATUALIZAÇÃO')

@section('preheader')
Atualização do pedido #{{ $pedido->id }} — {{ $title }}
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
        'iconUrl' => $iconUrl,
        'iconAlt' => $iconAlt,
        'title' => $title,
    ])

    {{-- Body text --}}
    <p style="margin:0 0 24px;font-size:14px;line-height:1.6;color:#666666;">
        {{ $bodyText }}
    </p>

    {{-- Order summary --}}
    @include('emails.orders._order-summary')

    {{-- CTA --}}
    @include('emails.orders._cta-button', ['buttonLabel' => 'VER PEDIDO'])
@endsection
