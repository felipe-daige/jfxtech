<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title>{{ $title ?? 'JFXTech' }}</title>
    <!--[if mso]>
    <style>table,td,div,p{font-family:Arial,sans-serif !important;}</style>
    <![endif]-->
</head>
<body style="margin:0;padding:0;background-color:#FAFAFA;color:#111111;font-family:'Inter','Segoe UI',Roboto,Helvetica,Arial,sans-serif;-webkit-font-smoothing:antialiased;">

    {{-- Preheader --}}
    @hasSection('preheader')
    <div style="display:none;max-height:0;overflow:hidden;mso-hide:all;">@yield('preheader')</div>
    @endif

    {{-- Outer wrapper --}}
    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="background-color:#FAFAFA;">
        <tr>
            <td align="center" style="padding:24px 16px;">

                {{-- Top bar — black status bar --}}
                <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="max-width:600px;">
                    <tr>
                        <td style="background-color:#000000;padding:12px 24px;">
                            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0">
                                <tr>
                                    <td style="font-family:'Courier New',Courier,monospace;font-size:12px;font-weight:600;letter-spacing:0.1em;color:#E5E5E5;text-transform:uppercase;">
                                        STATUS: ONLINE <span style="color:#22c55e;font-size:14px;">●</span>
                                    </td>
                                    <td align="right" style="font-family:'Courier New',Courier,monospace;font-size:12px;font-weight:600;letter-spacing:0.1em;color:#AAAAAA;text-transform:uppercase;">
                                        JFXTECH.COM.BR
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                </table>

                {{-- Email card --}}
                <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="max-width:600px;background-color:#FFFFFF;border-left:1px solid #E5E5E5;border-right:1px solid #E5E5E5;">

                    {{-- Header with logo --}}
                    <tr>
                        <td style="background-color:#FFFFFF;padding:24px 32px;border-bottom:1px solid #E5E5E5;">
                            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0">
                                <tr>
                                    <td style="vertical-align:middle;">
                                        <a href="https://jfxtech.com.br" target="_blank" rel="noopener noreferrer" style="text-decoration:none;color:#111111;">
                                            <table role="presentation" cellspacing="0" cellpadding="0" border="0">
                                                <tr>
                                                    <td style="vertical-align:middle;padding-right:12px;">
                                                        <img src="https://jfxtech.com.br/storage/images/jfxtech-logo-500x500-removebg-preview.png" alt="JFXTech" width="40" height="40" style="display:block;border:0;outline:none;">
                                                    </td>
                                                    <td style="vertical-align:middle;">
                                                        <span style="font-size:24px;font-weight:900;color:#000000;letter-spacing:0.02em;font-family:'Inter','Segoe UI',Roboto,Helvetica,Arial,sans-serif;-webkit-text-fill-color:#000000;">JFXTECH</span>
                                                    </td>
                                                </tr>
                                            </table>
                                        </a>
                                    </td>
                                    <td align="right" style="vertical-align:middle;">
                                        <span style="font-family:'Courier New',Courier,monospace;font-size:12px;font-weight:800;letter-spacing:0.1em;color:#000000;text-transform:uppercase;">
                                            @yield('header_label', 'NOTIFICAÇÃO')
                                        </span>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    {{-- Main content --}}
                    <tr>
                        <td style="padding:32px;">
                            @yield('content')
                        </td>
                    </tr>

                    {{-- Footer --}}
                    <tr>
                        <td style="border-top:1px solid #E5E5E5;padding:24px 32px;background-color:#FAFAFA;">
                            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0">
                                {{-- Quick links --}}
                                <tr>
                                    <td style="padding-bottom:16px;text-align:center;">
                                        <a href="https://jfxtech.com.br/produtos" style="font-family:'Courier New',Courier,monospace;font-size:12px;font-weight:700;letter-spacing:0.1em;color:#333333;text-transform:uppercase;text-decoration:none;padding:0 8px;">PRODUTOS</a>
                                        <span style="color:#E5E5E5;">|</span>
                                        <a href="https://jfxtech.com.br/meus-pedidos" style="font-family:'Courier New',Courier,monospace;font-size:12px;font-weight:700;letter-spacing:0.1em;color:#333333;text-transform:uppercase;text-decoration:none;padding:0 8px;">PEDIDOS</a>
                                        <span style="color:#E5E5E5;">|</span>
                                        <a href="https://jfxtech.com.br/contato" style="font-family:'Courier New',Courier,monospace;font-size:12px;font-weight:700;letter-spacing:0.1em;color:#333333;text-transform:uppercase;text-decoration:none;padding:0 8px;">CONTATO</a>
                                    </td>
                                </tr>
                                {{-- Support text --}}
                                <tr>
                                    <td style="text-align:center;padding-bottom:12px;">
                                        <span style="font-size:12px;color:#888888;line-height:1.6;">
                                            Precisa de ajuda? Responda este e‑mail ou escreva para
                                            <a href="mailto:contato@jfxtech.com.br" style="color:#111111;text-decoration:underline;">contato@jfxtech.com.br</a>
                                        </span>
                                    </td>
                                </tr>
                                {{-- Instagram --}}
                                <tr>
                                    <td style="text-align:center;padding-bottom:12px;">
                                        <a href="https://www.instagram.com/jfxtech/" target="_blank" rel="noopener noreferrer" style="font-family:'Courier New',Courier,monospace;font-size:10px;letter-spacing:0.08em;color:#888888;text-decoration:none;">
                                            @jfxtech
                                        </a>
                                    </td>
                                </tr>
                                {{-- Copyright --}}
                                <tr>
                                    <td style="text-align:center;">
                                        <span style="font-family:'Courier New',Courier,monospace;font-size:9px;letter-spacing:0.12em;color:#CCCCCC;text-transform:uppercase;">
                                            &copy; {{ date('Y') }} JFXTECH. TODOS OS DIREITOS RESERVADOS.
                                        </span>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                </table>

                {{-- Bottom bar --}}
                <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="max-width:600px;">
                    <tr>
                        <td style="background-color:#000000;padding:8px 24px;white-space:nowrap;">
                            <span style="font-family:'Courier New',Courier,monospace;font-size:11px;font-weight:600;letter-spacing:0.1em;color:#AAAAAA;text-transform:uppercase;">
                                Hardware gamer com engenharia de precisão.
                            </span>
                        </td>
                    </tr>
                </table>

            </td>
        </tr>
    </table>

</body>
</html>
