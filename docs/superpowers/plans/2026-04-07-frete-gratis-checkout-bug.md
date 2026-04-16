# Frete Grátis — Bug no Checkout Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Corrigir o bug que faz o checkout rejeitar a opção "Frete Grátis" mesmo quando ela está habilitada no `.env`.

**Architecture:** Fix de uma linha de condição lógica em `MercadoPagoCheckoutController::resolveFrete()`. O `.env` já tem `FRETE_GRATIS_ATIVO=true` e `FRETE_GRATIS_MINIMO=0`. O frontend já mostra a opção corretamente. O problema é exclusivamente no servidor, quando o usuário tenta finalizar a compra com frete grátis.

**Tech Stack:** Laravel 12 PHP

---

## Diagnóstico

**Arquivo:** `app/Http/Controllers/MercadoPagoCheckoutController.php:423-439`

```php
protected function resolveFrete(Pedido $pedido, string $cep, string $tipo): ?array
{
    if ($tipo === 'gratis') {
        $minimoFrete = (float) config('services.frete_gratis_minimo', 0);
        $subtotal = $pedido->itens->sum(fn($item) => $item->preco * $item->quantidade);

        if ($minimoFrete <= 0 || $subtotal < $minimoFrete) {  // ← BUG AQUI
            return null;
        }
        // ...
    }
}
```

Com `.env` tendo `FRETE_GRATIS_MINIMO=0`:
- `$minimoFrete = 0.0`
- `$minimoFrete <= 0` → `0.0 <= 0` → **TRUE**
- Retorna `null` imediatamente
- O controller pai interpreta `null` como erro e responde 422 "Não foi possível calcular o frete selecionado."

**Intenção original:** rejeitar se não há mínimo configurado. **Lógica correta:** mínimo 0 = sem restrição de valor mínimo. Só rejeitar se `FRETE_GRATIS_ATIVO=false` ou se `$minimoFrete > 0` e subtotal for insuficiente.

**Problema secundário:** o método não verifica `frete_gratis_ativo`, então poderia aceitar `'gratis'` mesmo com a feature desabilitada no `.env` (se a condição não matasse antes). Corrigir junto.

---

## Arquivos a Modificar

| Arquivo | Mudança |
|---|---|
| `app/Http/Controllers/MercadoPagoCheckoutController.php:423-439` | Corrigir condição de validação do frete grátis |

---

## Task 1: Corrigir `resolveFrete` em `MercadoPagoCheckoutController`

**Files:**
- Modify: `app/Http/Controllers/MercadoPagoCheckoutController.php:423-439`

- [ ] **Step 1: Aplicar o fix**

Em `app/Http/Controllers/MercadoPagoCheckoutController.php`, substituir o bloco `if ($tipo === 'gratis')` inteiro (linhas 425–439):

**DE:**
```php
if ($tipo === 'gratis') {
    $minimoFrete = (float) config('services.frete_gratis_minimo', 0);
    $subtotal = $pedido->itens->sum(fn($item) => $item->preco * $item->quantidade);

    if ($minimoFrete <= 0 || $subtotal < $minimoFrete) {
        return null;
    }

    return [
        'tipo' => 'gratis',
        'label' => 'FRETE GRÁTIS',
        'valor' => 0.00,
        'prazo' => '5-7 dias úteis',
    ];
}
```

**PARA:**
```php
if ($tipo === 'gratis') {
    if (!config('services.frete_gratis_ativo', false)) {
        return null;
    }

    $minimoFrete = (float) config('services.frete_gratis_minimo', 0);
    $subtotal = $pedido->itens->sum(fn($item) => $item->preco * $item->quantidade);

    if ($minimoFrete > 0 && $subtotal < $minimoFrete) {
        return null;
    }

    return [
        'tipo' => 'gratis',
        'label' => 'FRETE GRÁTIS',
        'valor' => 0.00,
        'prazo' => '5-7 dias úteis',
    ];
}
```

**O que mudou:**
1. Adicionou verificação de `frete_gratis_ativo` antes de tudo
2. Trocou `$minimoFrete <= 0 || $subtotal < $minimoFrete` por `$minimoFrete > 0 && $subtotal < $minimoFrete` — mínimo 0 agora significa "sem mínimo" (frete grátis para qualquer subtotal)

- [ ] **Step 2: Limpar cache de config**

```bash
docker exec laravel-app php artisan config:clear
docker exec laravel-app php artisan cache:clear
```

Expected output: `Configuration cache cleared successfully.` e `Application cache cleared successfully.`

- [ ] **Step 3: Testar manualmente**

1. Acesse `/checkout` com itens no carrinho
2. Informe um CEP válido — a opção "Frete Grátis" deve aparecer com borda preta e badge
3. Selecione "Frete Grátis"
4. Clique em "Continuar para Pagamento"
5. Confirme que **não** retorna erro 422
6. No resumo exibido antes do pagamento, confirme `frete: { label: 'FRETE GRÁTIS', valor: 0 }`

- [ ] **Step 4: Commit**

```bash
git add app/Http/Controllers/MercadoPagoCheckoutController.php
git commit -m "fix: corrigir condição de frete grátis — mínimo 0 não deve bloquear"
```

---

## Cenários de Configuração Após o Fix

| `.env` | Comportamento |
|---|---|
| `FRETE_GRATIS_ATIVO=false` | Opção grátis nunca é aceita no checkout |
| `FRETE_GRATIS_ATIVO=true`, `FRETE_GRATIS_MINIMO=0` | Frete grátis para qualquer valor de pedido |
| `FRETE_GRATIS_ATIVO=true`, `FRETE_GRATIS_MINIMO=200` | Frete grátis apenas se subtotal ≥ R$ 200 |

---

## Self-Review

| Requisito | Tarefa |
|---|---|
| Checkout aceita `frete_tipo=gratis` quando `FRETE_GRATIS_ATIVO=true` | Task 1 |
| Checkout rejeita `gratis` quando feature desabilitada | Task 1 (novo check `frete_gratis_ativo`) |
| Mínimo de valor funciona quando configurado > 0 | Task 1 (nova condição `$minimoFrete > 0 && ...`) |
