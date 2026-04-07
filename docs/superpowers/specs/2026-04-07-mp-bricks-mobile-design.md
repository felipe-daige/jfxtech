# MP Payment Bricks — Mobile UX Design

**Goal:** Melhorar a compatibilidade mobile do Payment Brick do Mercado Pago, corrigindo o layout externo da página e aplicando customização visual ao Brick.

**Approach:** Layout externo (HTML/Tailwind) + customização de variáveis CSS do SDK do MP. Sem redesign completo.

**Files afetados:**
- `resources/views/site/checkout.blade.php` — template `renderMercadoPagoCheckout` (JS inline)
- `public/js/checkout-mercadopago.js` — objeto `customization` na chamada `bricksBuilder.create`

---

## Seção 1 — Layout externo

### 1.1 Padding do container do Brick

**Problema:** `p-8` deixa ~295px de largura para o Brick em tela de 375px. O MP precisa de ~320px mínimo para não quebrar os campos internos.

**Fix:** Trocar `p-8` por `p-4 sm:p-8` no `<div>` que envolve `#paymentBrick_container`.

```html
<!-- DE -->
<div class="bg-white border border-[var(--color-lab-border)] p-8">

<!-- PARA -->
<div class="bg-white border border-[var(--color-lab-border)] p-4 sm:p-8">
```

### 1.2 Header "Pagamento / Total"

**Problema:** `flex items-start justify-between gap-6 mb-8` coloca título e valor lado a lado — em telas estreitas os dois ficam espremidos.

**Fix:** Virar coluna no mobile.

```html
<!-- DE -->
<div class="flex items-start justify-between gap-6 mb-8">

<!-- PARA -->
<div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-2 sm:gap-6 mb-6 sm:mb-8">
```

### 1.3 Ordem mobile: resumo antes do Brick

**Problema:** O grid `grid-cols-1 lg:grid-cols-[minmax(0,1fr)_320px]` renderiza o Brick (coluna 1) antes do resumo (aside, coluna 2). No mobile, o usuário preenche o cartão sem ver o total.

**Fix:** Usar `order` para inverter no mobile.

```html
<!-- Div principal do Brick -->
<div class="bg-white border border-[var(--color-lab-border)] p-4 sm:p-8 order-last lg:order-first">

<!-- Aside do resumo -->
<aside class="bg-white border border-[var(--color-lab-border)] p-6 h-fit order-first lg:order-last">
```

---

## Seção 2 — Customização visual do Brick

O SDK do MP aceita `customization.visual.style.customVariables` com variáveis CSS que controlam a aparência interna dos campos.

### 2.1 Variáveis a aplicar

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

**Efeitos:**
- `borderRadius*: '0px'` — remove bordas arredondadas dos campos, alinha com o design sober tech
- `baseFontSize: '14px'` — reduz de 16px padrão; evita que o campo número do cartão force zoom no iOS e que o seletor de parcelamento quebre em 2 linhas
- `formPadding: '8px'` — reduz o padding interno dos grupos de campos

**Não alteramos:** cores (`baseColor`, `inputBackgroundColor`, `textPrimaryColor`) — não há problema visual nelas e mudar pode impactar acessibilidade.

---

## Constraints

- Não usar workaround de `!important` ou injeção de CSS global nos seletores internos do Brick (frágil, quebra com updates do SDK)
- Não mudar o SDK versão — continua `https://sdk.mercadopago.com/js/v2`
- Manter `max-w-5xl mx-auto` no wrapper externo

---

## Teste esperado

Em viewport 375px (iPhone SE):
- Resumo do pedido aparece acima do Brick
- Header "Pagamento" não fica espremido com o total
- Campos do cartão têm largura suficiente (sem scroll horizontal)
- Seletor de parcelamento não quebra em 2 linhas
- Borda dos campos é quadrada (sem border-radius)
