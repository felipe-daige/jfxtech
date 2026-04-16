# TODO: Sistema de E-mails Transacionais da JFXTech

## Objetivo

Implementar e-mails transacionais no site usando um unico remetente:

```text
contato@jfxtech.com.br
```

O sistema deve cobrir reset de senha e eventos essenciais de pedido, usando fila para nao travar checkout ou pagamento. Inicialmente pode rodar com `MAIL_MAILER=log` para validacao; depois troca para SMTP real no `.env`.

## Configuracao

- [x] Configurar remetente padrao no `.env`/`.env.example`:

```env
MAIL_MAILER=log
MAIL_FROM_ADDRESS=contato@jfxtech.com.br
MAIL_FROM_NAME="JFXTech"
```

- [ ] Em producao, trocar para SMTP real com a senha de `contato@jfxtech.com.br`:

```env
MAIL_MAILER=smtp
MAIL_SCHEME=null
MAIL_HOST=smtp.hostinger.com
MAIL_PORT=587
MAIL_USERNAME=contato@jfxtech.com.br
MAIL_PASSWORD=
MAIL_FROM_ADDRESS=contato@jfxtech.com.br
MAIL_FROM_NAME="JFXTech"
```

- [x] Configurar DNS do dominio no provedor escolhido:
  - SPF
  - DKIM
  - DMARC

- [ ] Rodar limpeza de cache apos alterar `.env`:

```bash
docker exec -e HOME=/tmp laravel-app php artisan config:clear
docker exec -e HOME=/tmp laravel-app php artisan cache:clear
```

## Reset de Senha

- [x] Verificar/criar migration da tabela `password_reset_tokens`.
- [x] Criar rotas publicas:
  - `GET /forgot-password`
  - `POST /forgot-password`
  - `GET /reset-password/{token}`
  - `POST /reset-password`
- [x] Criar tela para solicitar reset de senha.
- [x] Criar tela para definir nova senha.
- [x] Enviar link de redefinicao por e-mail.
- [x] Validar token, e-mail, senha e confirmacao.
- [x] Fazer login ou redirecionar para login apos troca bem-sucedida.

## E-mails de Pedido

- [x] Criar mailables/notifications enfileiraveis para:
  - pedido criado/recebido;
  - pagamento aprovado;
  - pedido em processamento;
  - pedido enviado;
  - pedido entregue.
- [x] Para pedido enviado, incluir `codigo_rastreio` quando existir.
- [x] Para cliente autenticado, enviar para:
  - `Pedido.customer_email`, se preenchido;
  - senao `Pedido.user.email`.
- [x] Para guest checkout, enviar para `Pedido.customer_email`.
- [x] Integrar disparos no fluxo existente de pedido/status.
- [x] Manter webhook n8n funcionando em paralelo.
- [x] Nao enviar e-mail comum para status `pendente` de carrinho abandonado.

## Templates

- [x] Criar templates Blade em portugues com identidade simples da JFXTech.
- [x] Todos os e-mails devem conter:
  - nome do cliente quando disponivel;
  - resumo curto do evento;
  - numero do pedido;
  - botao/link para ver pedido quando aplicavel;
  - suporte via `contato@jfxtech.com.br`.

### Assuntos sugeridos

- [x] `Redefinicao de senha - JFXTech`
- [x] `Recebemos seu pedido #123`
- [x] `Pagamento aprovado - Pedido #123`
- [x] `Pedido #123 em processamento`
- [x] `Pedido #123 enviado`
- [x] `Pedido #123 entregue`

## Fila

- [x] Garantir que os envios usem queue.
- [x] Manter `QUEUE_CONNECTION=database`.
- [ ] Confirmar que o container/servico `laravel-worker` esta rodando.
- [ ] Se necessario, verificar jobs:

```bash
docker ps | grep laravel-worker
```

## Testes

- [x] Criar `PasswordResetTest` cobrindo:
  - usuario solicita link e e-mail e enviado/enfileirado;
  - token valido permite trocar senha;
  - token invalido/expirado falha;
  - nova senha autentica corretamente.

- [x] Criar `OrderEmailNotificationTest` cobrindo:
  - pagamento aprovado dispara e-mail para cliente autenticado;
  - pagamento aprovado dispara e-mail para guest usando `customer_email`;
  - status `processando`, `enviado` e `entregue` disparam e-mail correto;
  - status `enviado` inclui rastreio quando preenchido;
  - status `pendente` nao dispara e-mail comum.

- [ ] Rodar testes:

```bash
docker exec -e HOME=/tmp laravel-app php artisan test --filter=PasswordResetTest
docker exec -e HOME=/tmp laravel-app php artisan test --filter=OrderEmailNotificationTest
docker exec -e HOME=/tmp laravel-app php artisan test --filter=MercadoPagoCheckoutTest
docker exec -e HOME=/tmp laravel-app php artisan test --filter=OrderStatusNotificationTest
```

## Criterios de aceite

- [x] Usuario consegue solicitar reset de senha e receber link.
- [x] Usuario consegue redefinir senha com token valido.
- [x] Cliente recebe e-mail quando pagamento for aprovado.
- [x] Cliente recebe e-mail quando pedido mudar para `processando`, `enviado` e `entregue`.
- [x] Guest checkout recebe e-mails no e-mail informado no pedido.
- [x] E-mails nao bloqueiam checkout/pagamento se o provedor estiver lento.
- [x] Webhook n8n continua funcionando sem regressao.

## Fora do escopo inicial

- Newsletter.
- Marketing automation.
- Carrinho abandonado por e-mail dentro do Laravel.
- Multiplos remetentes como `pedidos@`, `suporte@` ou `conta@`.
