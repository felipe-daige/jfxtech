@extends('emails.layout')

@section('header_label', 'SORTEIO')

@section('preheader')
Sua participação no sorteio {{ $sorteio->titulo }} foi confirmada. Seu número é {{ $participacao->numeroFormatado() }}.
@endsection

@section('content')
    @php
        $clienteNome = $user?->name;
        $instagramUsername = '@'.ltrim((string) $participacao->instagram_username, '@');
        $instagramFriend1 = '@'.ltrim((string) $participacao->instagram_friend_1, '@');
        $instagramFriend2 = '@'.ltrim((string) $participacao->instagram_friend_2, '@');
    @endphp

    @if($clienteNome)
        <p style="margin:0 0 4px;font-size:14px;color:#666666;">Olá, {{ $clienteNome }}.</p>
    @endif

    @include('emails.orders._hero-title', [
        'iconUrl' => 'https://jfxtech.com.br/storage/images/emails/approved.png',
        'iconAlt' => 'Participação confirmada',
        'title' => 'Participação confirmada',
    ])

    <p style="margin:0 0 20px;font-size:15px;line-height:1.6;color:#333333;">
        Sua participação no sorteio <strong style="color:#000000;font-weight:800;">{{ $sorteio->titulo }}</strong> foi registrada com sucesso.
    </p>

    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="margin:0 0 24px;background-color:#FAFAFA;border:1px solid #E5E5E5;">
        <tr>
            <td style="padding:24px;text-align:center;">
                <p style="margin:0 0 8px;font-family:'Courier New',Courier,monospace;font-size:11px;font-weight:700;letter-spacing:0.1em;color:#666666;text-transform:uppercase;">Seu número</p>
                <p style="margin:0;font-family:'Courier New',Courier,monospace;font-size:48px;line-height:1;font-weight:800;letter-spacing:0.02em;color:#000000;">{{ $participacao->numeroFormatado() }}</p>
            </td>
        </tr>
    </table>

    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="margin:0 0 24px;border-top:1px solid #E5E5E5;">
        <tr>
            <td style="padding:14px 0;border-bottom:1px solid #E5E5E5;font-size:13px;color:#666666;">Instagram</td>
            <td align="right" style="padding:14px 0;border-bottom:1px solid #E5E5E5;font-family:'Courier New',Courier,monospace;font-size:13px;font-weight:700;color:#000000;">{{ $instagramUsername }}</td>
        </tr>
        <tr>
            <td style="padding:14px 0;border-bottom:1px solid #E5E5E5;font-size:13px;color:#666666;">Amigos marcados</td>
            <td align="right" style="padding:14px 0;border-bottom:1px solid #E5E5E5;font-family:'Courier New',Courier,monospace;font-size:13px;font-weight:700;color:#000000;">{{ $instagramFriend1 }} / {{ $instagramFriend2 }}</td>
        </tr>
    </table>

    <p style="margin:0 0 28px;font-size:14px;line-height:1.6;color:#666666;">
        Guarde este e-mail. O resultado final será publicado na página do sorteio após a auditoria.
    </p>

    @include('emails.orders._cta-button', [
        'buttonLabel' => 'ACOMPANHAR SORTEIO',
        'orderUrl' => $acompanharUrl,
    ])
@endsection
