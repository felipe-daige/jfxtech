<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex">
    <title>Em Manutenção | JFXTECH</title>
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            background: #000;
            color: #fff;
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            -webkit-font-smoothing: antialiased;
        }
        .topbar {
            background: #000;
            color: #fff;
            font-family: 'JetBrains Mono', 'Courier New', monospace;
            font-size: 10px;
            padding: 6px 16px;
            letter-spacing: 0.15em;
            text-transform: uppercase;
            border-bottom: 1px solid rgba(255,255,255,0.1);
        }
        .header {
            background: rgba(255,255,255,0.05);
            border-bottom: 1px solid rgba(255,255,255,0.1);
            padding: 0 24px;
        }
        .header-inner {
            max-width: 1280px;
            margin: 0 auto;
            height: 64px;
            display: flex;
            align-items: center;
        }
        .logo {
            display: flex;
            align-items: center;
            gap: 8px;
            text-decoration: none;
            color: #fff;
        }
        .logo-dot {
            width: 32px;
            height: 32px;
            background: #fff;
            border-radius: 4px;
        }
        .logo-name {
            font-weight: 700;
            font-size: 18px;
            letter-spacing: -0.02em;
        }
        main {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
            overflow: hidden;
        }
        .grid-bg {
            position: absolute;
            inset: 0;
            background-image:
                linear-gradient(rgba(255,255,255,0.03) 1px, transparent 1px),
                linear-gradient(90deg, rgba(255,255,255,0.03) 1px, transparent 1px);
            background-size: 40px 40px;
            pointer-events: none;
        }
        .content {
            position: relative;
            z-index: 10;
            text-align: center;
            padding: 64px 24px;
            max-width: 600px;
        }
        .label {
            font-family: 'JetBrains Mono', 'Courier New', monospace;
            font-size: 10px;
            text-transform: uppercase;
            letter-spacing: 0.2em;
            color: #6b7280;
            display: block;
            margin-bottom: 16px;
        }
        .code {
            font-family: 'JetBrains Mono', 'Courier New', monospace;
            font-size: clamp(64px, 12vw, 96px);
            font-weight: 700;
            color: #fff;
            line-height: 1;
            margin-bottom: 16px;
            letter-spacing: -0.04em;
        }
        h1 {
            font-size: clamp(20px, 4vw, 28px);
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.1em;
            margin-bottom: 16px;
        }
        p {
            color: #9ca3af;
            font-size: 14px;
            line-height: 1.6;
            margin-bottom: 40px;
        }
        .ping-wrapper {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: rgba(255,255,255,0.05);
            border: 1px solid rgba(255,255,255,0.1);
            padding: 8px 20px;
            font-family: 'JetBrains Mono', 'Courier New', monospace;
            font-size: 11px;
            letter-spacing: 0.1em;
            text-transform: uppercase;
            color: #9ca3af;
        }
        .ping-dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background: #f59e0b;
            animation: ping 1.5s ease-in-out infinite;
        }
        @keyframes ping {
            0%, 100% { opacity: 1; transform: scale(1); }
            50% { opacity: 0.5; transform: scale(0.8); }
        }
        footer {
            border-top: 1px solid rgba(255,255,255,0.1);
            padding: 24px 16px;
            text-align: center;
        }
        .footer-text {
            font-family: 'JetBrains Mono', 'Courier New', monospace;
            font-size: 10px;
            text-transform: uppercase;
            letter-spacing: 0.15em;
            color: #4b5563;
        }
    </style>
</head>
<body>

    <div class="topbar">JFXTECH — HARDWARE GAMER</div>

    <header class="header">
        <div class="header-inner">
            <a href="/" class="logo">
                <div class="logo-dot"></div>
                <span class="logo-name">JFXTECH</span>
            </a>
        </div>
    </header>

    <main>
        <div class="grid-bg"></div>
        <div class="content">

            <span class="label">Manutenção Programada</span>

            <div class="code">503</div>

            <h1>Em Manutenção</h1>

            <p>
                Estamos realizando melhorias no sistema para oferecer uma experiência ainda melhor.
                Voltamos em breve!
            </p>

            <div class="ping-wrapper">
                <div class="ping-dot"></div>
                EM MANUTENÇÃO — VOLTAMOS EM BREVE
            </div>

        </div>
    </main>

    <footer>
        <p class="footer-text">&copy; {{ date('Y') }} JFXTECH — Todos os direitos reservados</p>
    </footer>

</body>
</html>
