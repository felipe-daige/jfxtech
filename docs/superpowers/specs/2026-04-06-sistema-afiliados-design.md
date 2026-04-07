# Sistema de Afiliados — Design Spec

**Data:** 2026-04-06  
**Projeto:** JFX Tech — Laravel 12 + PostgreSQL + Tailwind v4  
**Status:** Aprovado para implementação

---

## Contexto

O projeto JFX Tech precisa de um sistema de marketing de afiliados gerenciável pelo admin. Afiliados indicam novos usuários via link personalizado; recebem comissão quando o indicado realiza a primeira compra paga. O admin aprova afiliados, gerencia comissões e configura parâmetros globais sem redeploy.

---

## Modelo de Dados

### Tabela `affiliates`
| Campo | Tipo | Notas |
|-------|------|-------|
| `id` | bigint PK | |
| `user_id` | bigint FK users | UNIQUE — 1 registro por usuário |
| `codigo` | string(8) | UNIQUE, gerado automaticamente |
| `commission_type` | enum('percent', 'fixed') | tipo de comissão individual |
| `commission_value` | decimal(8,2) nullable | NULL = usa padrão global |
| `status` | enum('pendente', 'ativo', 'inativo') | default 'pendente' |
| `pix_key` | string nullable | chave PIX para pagamento |
| `bank_info` | text nullable | JSON: banco, agência, conta |
| `approved_at` | timestamp nullable | |
| `created_at`, `updated_at` | timestamps | |

### Tabela `affiliate_referrals`
| Campo | Tipo | Notas |
|-------|------|-------|
| `id` | bigint PK | |
| `affiliate_id` | bigint FK affiliates | |
| `referred_user_id` | bigint FK users | UNIQUE — 1 referral por usuário |
| `status` | enum('pendente', 'convertido', 'cancelado') | default 'pendente' |
| `converted_at` | timestamp nullable | quando primeira compra foi paga |
| `created_at`, `updated_at` | timestamps | |

### Tabela `affiliate_commissions`
| Campo | Tipo | Notas |
|-------|------|-------|
| `id` | bigint PK | |
| `affiliate_id` | bigint FK affiliates | |
| `referral_id` | bigint FK affiliate_referrals | |
| `pedido_id` | bigint FK pedidos | pedido que gerou a comissão |
| `valor` | decimal(8,2) | valor calculado no momento |
| `status` | enum('pendente', 'aprovado', 'pago', 'rejeitado') | default 'pendente' |
| `eligible_at` | timestamp | now() + grace_period_days |
| `paid_at` | timestamp nullable | |
| `created_at`, `updated_at` | timestamps | |

### Tabela `affiliate_settings`
| `key` (unique) | Descrição | Valor padrão |
|----------------|-----------|--------------|
| `commission_percent_default` | % padrão de comissão | `5.00` |
| `cookie_days` | Validade do cookie de rastreio (dias) | `30` |
| `grace_period_days` | Carência antes de elegível para pagamento (dias) | `30` |
| `commission_trigger` | Evento que gera comissão | `first_paid_order` |

---

## Fluxo Completo

### 1. Clique no link de indicação
```
URL: https://loja.com/?ref=ABC123
→ Middleware TrackAffiliateReferral (grupo 'web', todas as rotas)
→ Valida: código existe + afiliado.status = 'ativo'
→ Seta cookie 'affiliate_ref' = 'ABC123' (duração: affiliate_settings.cookie_days)
→ Ignora silenciosamente se código inválido
```

### 2. Cadastro do indicado
```
SiteController::register() — ao final do método existente:
→ Lê cookie 'affiliate_ref'
→ Se vazio: nada a fazer
→ Valida anti-auto-indicação: affiliate.user_id !== novo user.id
→ Verifica se referred_user já tem um referral (UNIQUE constraint)
→ Cria affiliate_referrals: {affiliate_id, referred_user_id, status='pendente'}
→ Apaga cookie 'affiliate_ref'
```

### 3. Primeira compra paga
```
MercadoPagoCheckoutController (onde Pedido.status → 'pago'):
→ Chama AffiliateService::handleOrderPaid(Pedido $pedido)

AffiliateService::handleOrderPaid():
→ Se pedido.user_id é null (guest): retorna sem ação
→ Busca referral do user (status='pendente')
→ Se não encontrar: retorna sem ação
→ Verifica se é a PRIMEIRA compra paga do user
   (COUNT pedidos com status='pago' e user_id = X) === 1
→ Calcula comissão:
   rate = affiliate.commission_value ?? settings.commission_percent_default
   type = affiliate.commission_type ?? 'percent'
   valor = type='percent' ? pedido.valor_total * rate / 100 : rate
→ Cria affiliate_commissions: {valor, status='pendente', eligible_at=now()+grace_days}
→ Atualiza referral: status='convertido', converted_at=now()
```

### 4. Aprovação e pagamento (admin)
```
Admin lista comissões com status e eligible_at
→ Aprova individualmente ou em lote → status='aprovado'
→ Marca como pago → status='pago', paid_at=now()
Proteção: só pode aprovar comissões onde eligible_at <= now()
```

---

## Painel do Afiliado (`/afiliados`)

**Middleware:** `auth` em todas as rotas — redireciona para login se não autenticado.

| Rota | Controller@Método | View |
|------|-------------------|------|
| `GET /afiliados` | `AffiliadoController@painel` | `site/afiliados/painel.blade.php` |
| `GET /afiliados/solicitar` | `AffiliadoController@solicitar` | `site/afiliados/solicitar.blade.php` |
| `POST /afiliados/solicitar` | `AffiliadoController@registrar` | — redirect |
| `GET /afiliados/indicacoes` | `AffiliadoController@indicacoes` | `site/afiliados/indicacoes.blade.php` |
| `GET /afiliados/comissoes` | `AffiliadoController@comissoes` | `site/afiliados/comissoes.blade.php` |

**Painel principal** (`/afiliados`):
- Se usuário não é afiliado → redireciona para `/afiliados/solicitar`
- Se status='pendente' → mensagem "Solicitação em análise"
- Se ativo → dashboard com:
  - Link de indicação com botão "Copiar" (vanilla JS `navigator.clipboard`)
  - Cards: Total indicações | Convertidas | Comissões pendentes (R$) | Comissões pagas (R$)
  - Últimas 5 indicações + últimas 5 comissões

**Formulário de solicitação:**
- Campos: PIX key (opcional), dados bancários (opcional, texto livre)
- Submissão cria `affiliates` com status='pendente' e código único de 8 chars

**Design:** segue o padrão "Sober Tech" existente — preto/branco, Tailwind v4, vanilla JS.

---

## Painel Admin (`/admin/afiliados`)

**Auth:** mesmo padrão existente — `if (!Auth::check())` em cada método.

> **Atenção (ordem de rotas):** `GET /admin/afiliados/comissoes` e `GET /admin/afiliados/configuracoes` devem ser declaradas **antes** de `GET /admin/afiliados/{id}` em `routes/web.php` para evitar conflito de parâmetro.

| Rota | Controller@Método | Descrição |
|------|-------------------|-----------|
| `GET /admin/afiliados` | `AdminAfiliadoController@index` | Dashboard + lista de afiliados |
| `GET /admin/afiliados/stream` | `AdminAfiliadoController@stream` | SSE de métricas (30s) |
| `GET /admin/afiliados/{id}` | `AdminAfiliadoController@show` | JSON para modal |
| `POST /admin/afiliados/{id}/aprovar` | `AdminAfiliadoController@aprovar` | Ativar afiliado |
| `POST /admin/afiliados/{id}/suspender` | `AdminAfiliadoController@suspender` | Inativar afiliado |
| `POST /admin/afiliados/{id}/comissao` | `AdminAfiliadoController@editarComissao` | Override de comissão |
| `GET /admin/afiliados/comissoes` | `AdminAfiliadoController@comissoes` | Gestão de comissões |
| `POST /admin/afiliados/comissoes/bulk` | `AdminAfiliadoController@bulkComissoes` | Aprovar/rejeitar/pago em lote |
| `GET /admin/afiliados/configuracoes` | `AdminAfiliadoController@configuracoes` | Form de settings |
| `POST /admin/afiliados/configuracoes` | `AdminAfiliadoController@salvarConfiguracoes` | Salva settings |

**SSE (Server-Sent Events):**
```php
// AdminAfiliadoController@stream
return response()->stream(function () {
    set_time_limit(0);   // evita timeout do PHP-FPM
    while (true) {
        $data = [
            'afiliados_ativos' => Affiliate::where('status', 'ativo')->count(),
            'indicacoes_hoje'  => AffiliateReferral::whereDate('created_at', today())->count(),
            'comissoes_pendentes_valor' => AffiliateCommission::where('status', 'pendente')->sum('valor'),
            'comissoes_pagas_valor'     => AffiliateCommission::where('status', 'pago')->sum('valor'),
        ];
        echo "data: " . json_encode($data) . "\n\n";
        ob_flush(); flush();
        sleep(30);
    }
}, 200, [
    'Content-Type'  => 'text/event-stream',
    'Cache-Control' => 'no-cache',
    'X-Accel-Buffering' => 'no',  // necessário para Nginx
]);
```
JS: `new EventSource('/admin/afiliados/stream')` — atualiza cards sem reload.

---

## Arquitetura de Código

### Novos arquivos
```
app/Http/Middleware/TrackAffiliateReferral.php
app/Http/Controllers/AffiliadoController.php
app/Http/Controllers/AdminAfiliadoController.php
app/Services/AffiliateService.php
app/Models/Affiliate.php
app/Models/AffiliateReferral.php
app/Models/AffiliateCommission.php
app/Models/AffiliateSetting.php

database/migrations/2026_04_06_000001_create_affiliates_table.php
database/migrations/2026_04_06_000002_create_affiliate_referrals_table.php
database/migrations/2026_04_06_000003_create_affiliate_commissions_table.php
database/migrations/2026_04_06_000004_create_affiliate_settings_table.php

resources/views/site/afiliados/painel.blade.php
resources/views/site/afiliados/solicitar.blade.php
resources/views/site/afiliados/indicacoes.blade.php
resources/views/site/afiliados/comissoes.blade.php
resources/views/admin/afiliados/index.blade.php
resources/views/admin/afiliados/comissoes.blade.php
resources/views/admin/afiliados/configuracoes.blade.php

public/js/afiliados.js
public/js/afiliados-admin.js
```

### Arquivos modificados
```
routes/web.php                              — rotas /afiliados/* e /admin/afiliados/*
app/Http/Controllers/SiteController.php    — register(): adicionar rastreio de referral
app/Http/Controllers/MercadoPagoCheckoutController.php — chamar AffiliateService::handleOrderPaid()
bootstrap/app.php                          — registrar middleware TrackAffiliateReferral (Laravel 12 usa withMiddleware())
resources/views/includes/header-admin.blade.php — link "Afiliados" no sidebar
resources/views/includes/header.blade.php  — link "Painel Afiliado" no menu do usuário
```

### AffiliateService — métodos principais
```php
public function trackReferral(Request $request): void          // chamado pelo middleware
public function recordReferralOnRegister(User $user): void     // chamado após registro
public function handleOrderPaid(Pedido $pedido): void          // chamado após pagamento
public function generateUniqueCode(): string                   // 8 chars alfanumérico único
public function calculateCommission(Affiliate $aff, Pedido $p): float
public function getSetting(string $key, mixed $default = null): mixed
```

---

## Regras de Negócio

1. **Anti-auto-indicação:** `affiliate.user_id !== referred_user.id` — checado no registro
2. **Um referral por usuário:** `UNIQUE(referred_user_id)` — constraint no banco
3. **Primeira compra paga:** COUNT de pedidos com `status='pago'` para o user === 1
4. **Carência:** `eligible_at = created_at + grace_period_days` — admin vê botão de aprovar só após esse prazo
5. **Afiliado ativo:** cookie só é setado se `affiliate.status = 'ativo'`
6. **Guest checkout:** ignorado — `if ($pedido->user_id === null) return`

---

## Verificação (Testes E2E)

```bash
# 1. Migrations
docker exec laravel-app php artisan migrate --force

# 2. Testes automatizados
docker exec laravel-app php artisan test --filter=AffiliateTest

# Fluxo manual:
# a. Criar afiliado via /afiliados/solicitar
# b. Admin aprova em /admin/afiliados
# c. Acessar loja com ?ref=CODIGO
# d. Registrar nova conta
# e. Verificar affiliate_referrals no banco
# f. Completar compra → verificar affiliate_commissions criada
# g. Admin aprova comissão em /admin/afiliados/comissoes
# h. Admin marca como pago
```

---

## Fora do Escopo

- Notificações por email ao afiliado (pode ser adicionado depois via Laravel Mail)
- Dashboard com gráficos temporais (métricas são contadores simples)
- Multi-nível de afiliados (apenas 1 nível)
- Regras de elegibilidade customizáveis além do "first_paid_order"
