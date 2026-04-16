# MP Payment Bricks Mobile Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Melhorar compatibilidade mobile do Payment Brick do Mercado Pago — layout externo responsivo e customização visual do Brick.

**Architecture:** Dois arquivos independentes. Task 1 corrige o template HTML gerado pelo JS inline em `checkout.blade.php`. Task 2 adiciona `customization.visual` no objeto de configuração do Brick em `checkout-mercadopago.js`.

**Tech Stack:** Laravel 12 Blade, Tailwind CSS 4, Mercado Pago JS SDK v2

---

## Arquivos a Modificar

| Arquivo | Mudança |
|---|---|
| `resources/views/site/checkout.blade.php` | Template `renderMercadoPagoCheckout`: padding, header flex, ordem mobile |
| `public/js/checkout-mercadopago.js` | Adicionar `customization.visual.style.customVariables` |

---

## Task 1: Layout externo responsivo (`checkout.blade.php`)

**Files:**
- Modify: `resources/views/site/checkout.blade.php:689-705`

O template está dentro de uma template literal JS (backtick) na função `renderMercadoPagoCheckout`. As 3 mudanças são nos atributos `class` de elementos dentro dessa string.

- [ ] **Step 1: Corrigir padding do container do Brick**

Em `resources/views/site/checkout.blade.php`, linha 689, trocar:

```
<div class="bg-white border border-[var(--color-lab-border)] p-8">
```

Por:

```
<div class="bg-white border border-[var(--color-lab-border)] p-4 sm:p-8 order-last lg:order-first">
```

- [ ] **Step 2: Corrigir header "Pagamento / Total"**

Linha 690, trocar:

```
<div class="flex items-start justify-between gap-6 mb-8">
```

Por:

```
<div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-2 sm:gap-6 mb-6 sm:mb-8">
```

- [ ] **Step 3: Adicionar order ao aside do resumo**

Linha 705, trocar:

```
<aside class="bg-white border border-[var(--color-lab-border)] p-6 h-fit">
```

Por:

```
<aside class="bg-white border border-[var(--color-lab-border)] p-6 h-fit order-first lg:order-last">
```

- [ ] **Step 4: Limpar view cache**

```bash
docker exec laravel-app php artisan view:clear
docker exec laravel-app php artisan cache:clear
```

Expected output:
```
INFO  Blade templates cleared successfully.
INFO  Application cache cleared successfully.
```

- [ ] **Step 5: Verificar resultado no browser**

Abrir `/checkout` com itens no carrinho e clicar em "Continuar para Pagamento" para chegar na tela do Brick. Verificar em viewport 375px (DevTools):

- Resumo aparece acima do Brick
- Header "Pagamento" não fica espremido ao lado do total
- Container do Brick usa padding menor no mobile

- [ ] **Step 6: Commit**

```bash
git add resources/views/site/checkout.blade.php
git commit -m "feat: layout responsivo do Payment Brick no mobile"
```

---

## Task 2: Customização visual do Brick (`checkout-mercadopago.js`)

**Files:**
- Modify: `public/js/checkout-mercadopago.js:555-562`

O objeto `customization` atual (linha 555) só tem `paymentMethods`. Vamos adicionar `visual` dentro dele.

- [ ] **Step 1: Adicionar customização visual**

Em `public/js/checkout-mercadopago.js`, trocar o bloco `customization` (linhas 555–562):

```js
            customization: {
                paymentMethods: {
                    creditCard: 'all',
                    debitCard: 'all',
                    ticket: 'all',
                    bankTransfer: 'all',
                },
            },
```

Por:

```js
            customization: {
                paymentMethods: {
                    creditCard: 'all',
                    debitCard: 'all',
                    ticket: 'all',
                    bankTransfer: 'all',
                },
                visual: {
                    style: {
                        customVariables: {
                            borderRadiusSmall: '0px',
                            borderRadiusMedium: '0px',
                            borderRadiusLarge: '0px',
                            baseFontSize: '14px',
                            formPadding: '8px',
                        },
                    },
                },
            },
```

**Por que cada variável:**
- `borderRadius*: '0px'` — remove bordas arredondadas internas do Brick, alinhando com o design sober tech (bordas quadradas) do site
- `baseFontSize: '14px'` — o padrão MP é 16px; em iOS isso força zoom automático nos inputs, e o seletor de parcelas fica largo demais em telas < 390px
- `formPadding: '8px'` — reduz o espaçamento interno dos grupos de campos, dando mais espaço para o conteúdo em telas estreitas

- [ ] **Step 2: Verificar no browser**

Abrir `/checkout` e chegar na tela do Brick. Verificar em viewport 375px:

- Campos do cartão têm bordas quadradas (sem border-radius)
- Seletor de parcelas não quebra em 2 linhas
- Campos não causam zoom automático no iOS (font-size ≥ 16px causaria — o Brick usa a variável internamente de forma segura)

- [ ] **Step 3: Commit**

```bash
git add public/js/checkout-mercadopago.js
git commit -m "feat: customização visual do Payment Brick para mobile"
```

---

## Self-Review

**Spec coverage:**

| Requisito | Task |
|---|---|
| Padding `p-8` → `p-4 sm:p-8` | Task 1 Step 1 |
| Header flex responsivo | Task 1 Step 2 |
| Resumo aparece antes do Brick no mobile | Task 1 Step 3 |
| `borderRadius*: '0px'` | Task 2 Step 1 |
| `baseFontSize: '14px'` | Task 2 Step 1 |
| `formPadding: '8px'` | Task 2 Step 1 |

**Placeholder scan:** nenhum TBD ou TODO.

**Constraint verificada:** Não usa `!important`, não injeta CSS nos seletores internos do Brick, não muda versão do SDK.
