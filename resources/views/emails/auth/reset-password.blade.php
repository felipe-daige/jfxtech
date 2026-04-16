@extends('emails.layout')

@section('header_label', 'SEGURANÇA')

@section('preheader')
Você solicitou a redefinição da sua senha na JFXTech.
@endsection

@section('content')
    {{-- Title with padlock --}}
    <table role="presentation" cellspacing="0" cellpadding="0" border="0" style="background-color:#FFFFFF;padding:16px 24px;margin:0 0 24px;border:1px solid #E5E5E5;">
        <tr>
            <td style="padding-right:12px;vertical-align:middle;">
                <img src="https://jfxtech.com.br/storage/images/emails/lock.png" width="32" height="32" alt="Segurança" style="display:block;border:0;outline:none;">
            </td>
            <td style="vertical-align:middle;">
                <h1 style="margin:0;font-size:24px;font-weight:900;color:#000000;line-height:1.2;letter-spacing:-0.02em;text-transform:uppercase;white-space:nowrap;">
                    Redefinição de senha
                </h1>
            </td>
        </tr>
    </table>

    {{-- Body text --}}
    <p style="margin:0 0 8px;font-size:15px;line-height:1.6;color:#333333;">
        Recebemos uma solicitação para redefinir a senha da sua conta JFXTech.
    </p>
    <p style="margin:0 0 36px;font-size:15px;line-height:1.6;color:#333333;">
        Clique no botão abaixo para criar uma nova senha. Este link expira em <strong style="color:#000000;font-weight:800;">60 minutos</strong>.
    </p>

    {{-- CTA Button --}}
    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="margin:0 0 28px;">
        <tr>
            <td align="center">
                <table role="presentation" cellspacing="0" cellpadding="0" border="0">
                    <tr>
                        <td style="background-color:#000000;">
                            <a href="{{ $url }}" target="_blank" rel="noopener noreferrer" style="display:inline-block;padding:14px 36px;font-family:'Courier New',Courier,monospace;font-size:10px;font-weight:700;color:#FFFFFF;text-decoration:none;letter-spacing:0.15em;text-transform:uppercase;">
                                REDEFINIR SENHA
                            </a>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

    {{-- Security notice --}}
    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="margin:0 0 24px;border:1px solid #E5E5E5;">
        <tr>
            <td style="background-color:#000000;padding:8px 20px;">
                <span style="font-family:'Courier New',Courier,monospace;font-size:12px;font-weight:700;letter-spacing:0.1em;color:#E5E5E5;text-transform:uppercase;">Aviso de segurança</span>
            </td>
        </tr>
        <tr>
            <td style="padding:16px 20px;background-color:#FAFAFA;">
                <p style="margin:0;font-size:13px;color:#666666;line-height:1.6;">
                    <strong style="color:#111111;">Não solicitou esta redefinição?</strong><br>
                    Ignore este e‑mail. Sua senha continuará a mesma e nenhuma ação será tomada.
                </p>
            </td>
        </tr>
    </table>

    {{-- Fallback URL --}}
    <p style="margin:0;font-size:12px;color:#666666;line-height:1.5;word-break:break-all;">
        <span style="font-family:'Courier New',Courier,monospace;font-size:11px;font-weight:700;letter-spacing:0.1em;color:#444444;text-transform:uppercase;">Link alternativo:</span>
        <a href="{{ $url }}" style="color:#000000;text-decoration:underline;font-weight:600;margin-left:4px;">{{ $url }}</a>
    </p>
@endsection
