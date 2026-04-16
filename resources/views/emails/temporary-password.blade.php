@extends('emails.layout')

@section('header_title', 'SUA CONTA FOI CRIADA')
@section('header_subtitle', 'BEM-VINDO À JFXTECH')

@section('content')
<div style="margin-bottom: 24px;">
    <p style="font-family: inherit; font-size: 16px; font-weight: 500; font-style: normal; margin: 0 0 16px 0; padding: 0;">Olá, {{ explode(' ', $user->name)[0] }}!</p>
    <p style="font-family: inherit; font-size: 14px; line-height: 1.6; margin: 0; padding: 0; color: #4b5563;">
        Como você realizou uma compra em nosso site, nós criamos automaticamente uma conta para você acompanhar o status dos seus pedidos com muito mais facilidade.
    </p>
</div>

<!-- Senha Box -->
<div style="background-color: #f9fafb; border: 1px solid #e5e7eb; padding: 24px; margin-bottom: 32px; text-align: center;">
    <p style="font-family: inherit; font-size: 12px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.1em; color: #6b7280; margin: 0 0 8px 0; padding: 0;">Sua Senha Temporária</p>
    <p style="font-family: monospace; font-size: 24px; font-weight: 700; color: #111827; margin: 0; padding: 0; letter-spacing: 0.1em;">{{ $tempPassword }}</p>
</div>

<div style="margin-bottom: 24px;">
    <p style="font-family: inherit; font-size: 14px; line-height: 1.6; margin: 0 0 16px 0; padding: 0; color: #4b5563;">
        Você pode usar essa senha junto com o seu e-mail (<strong>{{ $user->email }}</strong>) para fazer login na sua conta. Se quiser, depois você pode alterar a senha normalmente nas opções da conta ou pelo fluxo de recuperação.
    </p>
</div>

<!-- Botão de Acesso -->
<div style="margin-bottom: 32px;">
    <a href="{{ route('site.login') }}" style="display: inline-block; background-color: #000000; color: #ffffff; padding: 12px 24px; text-decoration: none; font-size: 12px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.1em;">
        Acessar Minha Conta &rarr;
    </a>
</div>

<div style="margin-bottom: 0;">
    <p style="font-family: inherit; font-size: 14px; line-height: 1.6; margin: 0; padding: 0; color: #4b5563;">
        Se você tiver alguma dúvida, é só responder a este e-mail.
    </p>
</div>
@endsection
