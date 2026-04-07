# Frete Grátis Fix + Mobile UI Improvements

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Corrigir dois bugs que impedem o frete grátis de aparecer/funcionar corretamente no frontend e melhorar a experiência mobile nas páginas de checkout, catálogo e detalhe de produto.

**Architecture:** Todos os bugs são no lado do cliente (Blade template + JS inline) e no FreteController. Nenhuma migration ou rota nova. As melhorias mobile são puramente de Tailwind classes responsivas e reorganização de markup.

**Tech Stack:** Laravel 12 Blade, Tailwind CSS 4, jQuery (inline JS em finalizar-compra.blade.php)

---

## Diagnóstico dos bugs do Frete Grátis

**Bug 1 — Campo errado no controller:** `FreteController::calcularFreteCarrinho()` usa `$item->preco_unitario` mas `ItemPedido` expõe o campo como `preco`. O subtotal calculado é sempre 0 (null * qty = null → sum = 0). Mesmo assim passa em `0 >= 0`, então **não bloqueia o feature** mas é incorreto.

**Bug 2 — Elemento inexistente no DOM:** O handler de change no radio de frete faz `$('#gratis-valor')` mas esse ID não existe no HTML. Quando o usuário clica manualmente em "Frete Grátis", o valor fica `NaN` e o total quebra.

**Bug 3 — Env não configurado:** Por padrão `FRETE_GRATIS_ATIVO=false`. O elemento Blade e a opção da API só aparecem quando `true`. O usuário precisa setar o `.env`.

---

## Arquivos a Modificar

| Arquivo | Mudança |
|---|---|
| `app/Http/Controllers/FreteController.php` | `preco_unitario` → `preco` |
| `resources/views/site/finalizar-compra.blade.php` | Fix JS bug (`#gratis-valor`), melhorias mobile |
| `resources/views/site/produtos.blade.php` | Melhorias mobile para filtros e grid |
| `resources/views/site/produto-detalhes.blade.php` | Melhorias mobile para galeria e CTA |

---

## Task 1: Corrigir bugs do Frete Grátis

**Files:**
- Modify: `app/Http/Controllers/FreteController.php:226`
- Modify: `resources/views/site/finalizar-compra.blade.php:257-267,355-359`

- [ ] **Step 1: Corrigir campo preco_unitario → preco no FreteController**

Em `app/Http/Controllers/FreteController.php`, linha 226, trocar:

```php
$subtotal = $carrinho->itens->sum(fn($item) => $item->preco_unitario * $item->quantidade);
```

Por:

```php
$subtotal = $carrinho->itens->sum(fn($item) => $item->preco * $item->quantidade);
```

- [ ] **Step 2: Adicionar id="gratis-valor" no elemento de preço da opção gratis**

Em `finalizar-compra.blade.php`, dentro do `<label id="frete-gratis-option"...>`, o `<div>` que mostra "R$ 0,00" não tem ID. Trocar:

```html
<div class="text-right">
    <div class="font-mono font-bold text-green-700">R$ 0,00</div>
</div>
```

Por:

```html
<div class="text-right">
    <div class="font-mono font-bold text-green-700" id="gratis-valor">R$ 0,00</div>
</div>
```

- [ ] **Step 3: Corrigir o handler de change para o frete grátis**

O handler atual em finalizar-compra.blade.php (dentro do `$(document).ready`, evento `change` em `input[name="frete"]`) faz:

```javascript
const valor = parseFloat($('#' + tipo + '-valor').text().replace('R$ ', '').replace(',', '.'));
```

Quando `tipo = 'gratis'`, o texto é `"R$ 0,00"` e o parse retorna `0` — isso funcionará agora com o `id="gratis-valor"` do Step 2. Nenhuma mudança adicional necessária no handler.

- [ ] **Step 4: Ativar o frete grátis no ambiente de produção**

Rodar no host:

```bash
# Adicionar/editar no .env do servidor
# FRETE_GRATIS_ATIVO=true

docker exec laravel-app php artisan config:clear
docker exec laravel-app php artisan cache:clear
```

- [ ] **Step 5: Testar manualmente**

1. Acesse `/finalizar-compra` com itens no carrinho
2. Informe um CEP válido
3. Confirme que a opção "Frete Grátis" aparece com borda verde e badge "Tempo Limitado"
4. Clique nela — o total deve mostrar o valor sem frete (R$ 0,00 de frete)
5. Troque para PAC — total deve atualizar corretamente
6. Volte para Grátis — total deve voltar a 0 de frete

- [ ] **Step 6: Commit**

```bash
git add app/Http/Controllers/FreteController.php resources/views/site/finalizar-compra.blade.php
git commit -m "fix: corrigir campo preco e id gratis-valor no frete grátis"
```

---

## Task 2: Melhorias mobile — finalizar-compra.blade.php

**Files:**
- Modify: `resources/views/site/finalizar-compra.blade.php`

Os problemas atuais em mobile:
- Título `text-4xl` é grande demais em telas pequenas
- Coluna de resumo do pedido aparece abaixo do formulário de endereço — no mobile, o usuário vê o formulário primeiro sem saber o que está comprando
- Sidebar com `sticky top-6` só funciona no desktop
- As opções de frete têm targets de toque pequenos (`p-3`)
- O botão "Continuar" está dentro do sidebar — longe do início da tela no mobile

- [ ] **Step 1: Título responsivo e header section**

Trocar no `<div class="bg-white border-b...">` section:

```html
<h1 id="checkout-title" class="text-4xl font-bold tracking-tight mb-2">ENDEREÇO DE ENTREGA</h1>
<p id="checkout-subtitle" class="text-gray-500 font-mono text-sm">CONFIRME SEU ENDEREÇO PARA CONTINUAR</p>
```

Por:

```html
<h1 id="checkout-title" class="text-2xl sm:text-4xl font-bold tracking-tight mb-2">ENDEREÇO DE ENTREGA</h1>
<p id="checkout-subtitle" class="text-gray-500 font-mono text-xs sm:text-sm">CONFIRME SEU ENDEREÇO PARA CONTINUAR</p>
```

- [ ] **Step 2: Reorganizar layout mobile — resumo primeiro**

Atualmente o grid é `grid-cols-1 lg:grid-cols-3`. No mobile, o formulário (col-span-2) aparece antes do resumo. Trocar a ordem no DOM e usar `order` para inverter no desktop:

Trocar:
```html
<div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
    <!-- Address Form -->
    <div class="lg:col-span-2">
```

Por:
```html
<div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
    <!-- Order Summary — aparece primeiro no mobile via order -->
    <div class="lg:col-span-1 order-first lg:order-last">
```

E mover o bloco do formulário de endereço para depois, trocando as `div`s de lugar no HTML (resumo primeiro no DOM, formulário depois) e usando:
- No formulário: `<div class="lg:col-span-2 order-last lg:order-first">`
- No resumo: `<div class="lg:col-span-1 order-first lg:order-last">`

Dessa forma no mobile o resumo fica no topo (mostra os itens e o total), e no desktop continua na coluna da direita.

- [ ] **Step 3: Remover sticky do sidebar no mobile**

Trocar:
```html
<div class="bg-white border border-[var(--color-lab-border)] p-6 sticky top-6">
```

Por:
```html
<div class="bg-white border border-[var(--color-lab-border)] p-6 lg:sticky lg:top-6">
```

- [ ] **Step 4: Melhorar touch targets das opções de frete**

Para os três labels de frete (gratis, pac, sedex), trocar `p-3` por `p-4` e garantir altura mínima adequada:

Trocar (em todos os 3 labels de frete):
```html
<label ... class="... p-3 border ...">
```

Por:
```html
<label ... class="... p-4 border ...">
```

- [ ] **Step 5: Padding responsivo no sidebar/resumo**

Trocar:
```html
<div class="bg-white border border-[var(--color-lab-border)] p-6 lg:sticky lg:top-6">
    <h3 class="text-xl font-bold tracking-tight mb-6">Resumo do Pedido</h3>
```

Por:
```html
<div class="bg-white border border-[var(--color-lab-border)] p-4 sm:p-6 lg:sticky lg:top-6">
    <h3 class="text-lg sm:text-xl font-bold tracking-tight mb-4 sm:mb-6">Resumo do Pedido</h3>
```

- [ ] **Step 6: Itens do carrinho compactos no mobile**

Os itens do carrinho usam `space-x-3` e imagem `w-16 h-16`. No mobile com o resumo no topo, deixar mais compacto:

Trocar:
```html
<div class="space-y-4 mb-6">
    @foreach($carrinho->itens as $item)
    <div class="flex items-center space-x-3">
        <div class="w-16 h-16 bg-gray-100 rounded-xl overflow-hidden">
```

Por:
```html
<div class="space-y-3 mb-4 sm:mb-6">
    @foreach($carrinho->itens as $item)
    <div class="flex items-center space-x-3">
        <div class="w-14 h-14 sm:w-16 sm:h-16 bg-gray-100 flex-shrink-0 overflow-hidden">
```

(Remove `rounded-xl` para manter consistência com o design "sober tech" e impede que o bloco fique muito grande no mobile.)

- [ ] **Step 7: Clear view cache e testar**

```bash
docker exec laravel-app php artisan view:clear
docker exec laravel-app php artisan cache:clear
```

Verificar no browser em viewport mobile (375px):
- Resumo do pedido aparece antes do formulário
- Título está em tamanho menor
- Opções de frete têm altura adequada para toque

- [ ] **Step 8: Commit**

```bash
git add resources/views/site/finalizar-compra.blade.php
git commit -m "feat: melhorar layout mobile do checkout — resumo primeiro, títulos responsivos"
```

---

## Task 3: Melhorias mobile — produtos.blade.php (catálogo)

**Files:**
- Modify: `resources/views/site/produtos.blade.php`

Problemas no mobile:
- O sidebar de filtros ocupa `w-full` acima dos produtos — se expandido, empurra o grid muito para baixo
- Título `text-4xl` é grande demais
- O grid de produtos precisa de breakpoints adequados

- [ ] **Step 1: Título responsivo**

Trocar:
```html
<h1 class="text-4xl font-bold tracking-tight mb-2">CATÁLOGO DE HARDWARE</h1>
<p class="text-gray-500 font-mono text-sm">NAVEGUE PELO NOSSO ARSENAL COMPLETO DE EQUIPAMENTOS DE ALTA PERFORMANCE</p>
```

Por:
```html
<h1 class="text-2xl sm:text-4xl font-bold tracking-tight mb-2">CATÁLOGO DE HARDWARE</h1>
<p class="text-gray-500 font-mono text-xs sm:text-sm leading-relaxed">NAVEGUE PELO NOSSO ARSENAL COMPLETO DE EQUIPAMENTOS DE ALTA PERFORMANCE</p>
```

- [ ] **Step 2: Sidebar de filtros — oculto por padrão no mobile com botão toggle**

A `<aside>` atualmente tem `w-full lg:w-64`. No mobile ela aparece empilhada acima dos produtos. Adicionar classe para ocultar no mobile e um botão flutuante para abrir.

Trocar a tag `<aside>`:
```html
<aside class="w-full lg:w-64 flex-shrink-0 filter-sidebar">
```

Por:
```html
<aside class="hidden lg:block w-full lg:w-64 flex-shrink-0 filter-sidebar" id="filtros-sidebar">
```

Adicionar, logo após a `<section class="max-w-7xl...">` e antes do `<div class="flex flex-col lg:flex-row...">`, um botão de filtro visível apenas no mobile:

```html
{{-- Botão de filtro mobile --}}
<div class="lg:hidden flex items-center justify-between mb-4">
    <p class="font-mono text-xs text-gray-500 uppercase tracking-widest">
        {{ $produtos->total() }} produto(s)
    </p>
    <button id="mobileFilterOpen" class="flex items-center gap-2 border border-[var(--color-lab-border)] px-3 py-2 text-xs font-mono uppercase tracking-widest hover:bg-black hover:text-white transition-colors">
        <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="4" y1="6" x2="20" y2="6"/><line x1="8" y1="12" x2="16" y2="12"/><line x1="11" y1="18" x2="13" y2="18"/></svg>
        FILTROS
    </button>
</div>
```

- [ ] **Step 3: Sidebar mobile como drawer (panel deslizante)**

Modificar a `<aside id="filtros-sidebar">` para funcionar como overlay no mobile. Adicionar ao início do sidebar:

```html
<aside class="fixed inset-0 z-50 bg-white overflow-y-auto p-6 lg:static lg:inset-auto lg:z-auto lg:bg-transparent lg:overflow-visible lg:p-0 hidden lg:block w-full lg:w-64 flex-shrink-0 filter-sidebar" id="filtros-sidebar">
    {{-- Cabeçalho mobile do sidebar --}}
    <div class="flex items-center justify-between mb-6 lg:hidden">
        <h2 class="font-mono text-sm font-bold uppercase tracking-widest">FILTROS</h2>
        <button id="mobileFilterClose" class="p-1 text-gray-500 hover:text-black" aria-label="Fechar filtros">
            <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 6 6 18"/><path d="m6 6 12 12"/></svg>
        </button>
    </div>
```

Remover o botão `mobileFilterClose` existente dentro do `<div class="flex items-center justify-between mb-6">` para evitar duplicata (só manter o do drawer).

- [ ] **Step 4: JS para abrir/fechar sidebar mobile**

Localizar o `<script>` de produtos.blade.php (ou o arquivo `public/js/produtos.js`) e adicionar os handlers:

```javascript
// Mobile filter drawer
document.getElementById('mobileFilterOpen')?.addEventListener('click', function() {
    const sidebar = document.getElementById('filtros-sidebar');
    sidebar.classList.remove('hidden');
    document.body.style.overflow = 'hidden';
});

document.getElementById('mobileFilterClose')?.addEventListener('click', function() {
    const sidebar = document.getElementById('filtros-sidebar');
    sidebar.classList.add('hidden');
    document.body.style.overflow = '';
});
```

Verificar onde o JS de filtros está — se em `public/js/produtos.js`, adicionar lá. Se inline na view, adicionar no `<script>` existente.

- [ ] **Step 5: Grid responsivo para produtos**

Localizar o grid de produtos (normalmente `grid-cols-1 sm:grid-cols-2 lg:grid-cols-3` ou similar) e garantir:

```html
<div class="grid grid-cols-2 sm:grid-cols-2 md:grid-cols-3 gap-3 sm:gap-6">
```

(2 colunas no mobile em vez de 1 — product cards são compactos o suficiente)

- [ ] **Step 6: Clear cache e testar**

```bash
docker exec laravel-app php artisan view:clear
```

Verificar no mobile:
- Filtros não aparecem por padrão
- Botão "FILTROS" está visível e ao clicar abre o drawer fullscreen
- Grid tem 2 colunas no mobile
- Título está em tamanho menor

- [ ] **Step 7: Commit**

```bash
git add resources/views/site/produtos.blade.php public/js/produtos.js
git commit -m "feat: melhorias mobile no catálogo — drawer de filtros e grid 2 colunas"
```

---

## Task 4: Melhorias mobile — produto-detalhes.blade.php

**Files:**
- Modify: `resources/views/site/produto-detalhes.blade.php`

Problemas no mobile:
- `gap-16` entre galeria e infos do produto é enorme no mobile
- Breadcrumb longo pode quebrar em múltiplas linhas de forma feia
- O botão "Adicionar ao Carrinho" pode estar longe do início da tela

- [ ] **Step 1: Gap responsivo no grid de produto**

Trocar:
```html
<div class="grid grid-cols-1 lg:grid-cols-2 gap-16">
```

Por:
```html
<div class="grid grid-cols-1 lg:grid-cols-2 gap-8 lg:gap-16">
```

- [ ] **Step 2: Breadcrumb com truncate**

No breadcrumb do produto-detalhes, o nome do produto pode ser longo. Adicionar truncate no span final:

Trocar:
```html
<span class="text-black">{{ strtoupper($produto->nome) }}</span>
```

Por:
```html
<span class="text-black truncate max-w-[140px] sm:max-w-none inline-block align-bottom">{{ strtoupper($produto->nome) }}</span>
```

- [ ] **Step 3: Padding vertical da section no mobile**

Trocar:
```html
<section class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
```

Por:
```html
<section class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6 sm:py-12">
```

- [ ] **Step 4: Botão CTA fixo no mobile**

No produto-detalhes, o botão "Adicionar ao Carrinho" fica dentro do grid — no mobile, depois de rolar a galeria + descrição o usuário tem que procurar o botão. Adicionar uma barra CTA fixa no fundo do mobile:

Localizar o botão principal de adicionar ao carrinho (geralmente `id="add-to-cart-btn"` ou similar). Adicionar após o `</main>` e antes do `@include('includes.footer')`:

```html
{{-- Barra CTA mobile fixa --}}
<div class="fixed bottom-0 left-0 right-0 z-40 lg:hidden bg-white border-t border-[var(--color-lab-border)] p-4" id="mobile-cta-bar">
    <div class="flex items-center gap-3">
        <div class="flex-1">
            <p class="font-mono text-xs text-gray-500 uppercase tracking-widest">{{ $produto->nome }}</p>
            <p class="font-mono font-bold text-sm">
                @if($produto->esta_em_promocao)
                    R$ {{ number_format($produto->preco_com_desconto, 2, ',', '.') }}
                @else
                    R$ {{ number_format($produto->preco, 2, ',', '.') }}
                @endif
            </p>
        </div>
        @if($produto->em_estoque)
        <button class="bg-black text-white font-mono font-bold text-xs uppercase tracking-widest px-4 py-3 hover:bg-gray-900 transition-colors" id="mobile-add-to-cart-btn">
            ADICIONAR
        </button>
        @else
        <button class="bg-gray-300 text-gray-500 font-mono font-bold text-xs uppercase tracking-widest px-4 py-3 cursor-not-allowed" disabled>
            SEM ESTOQUE
        </button>
        @endif
    </div>
</div>
{{-- Espaçador para a barra fixa não cobrir o footer --}}
<div class="h-20 lg:hidden"></div>
```

Adicionar JS para conectar o botão mobile ao carrinho (localizar onde `add-to-cart-btn` está definido no JS existente e replicar o behavior):

```javascript
// Mobile CTA bar — delega ao botão principal
document.getElementById('mobile-add-to-cart-btn')?.addEventListener('click', function() {
    document.getElementById('add-to-cart-btn')?.click();
});
```

- [ ] **Step 5: Verificar IDs do botão add-to-cart**

Antes de commitar, ler o `produto-detalhes.blade.php` (ou `public/js/produto-detalhes.js`) para confirmar o ID exato do botão principal de adicionar ao carrinho. Ajustar o `getElementById` se necessário.

- [ ] **Step 6: Clear cache e testar**

```bash
docker exec laravel-app php artisan view:clear
```

Verificar no mobile:
- Barra CTA fixa aparece no fundo
- Clicar "ADICIONAR" funciona igual ao botão principal
- Gap entre galeria e info está menor
- Breadcrumb não quebra feio

- [ ] **Step 7: Commit**

```bash
git add resources/views/site/produto-detalhes.blade.php
git commit -m "feat: melhorias mobile no detalhe de produto — gap, breadcrumb, barra CTA fixa"
```

---

## Self-Review: Spec Coverage

| Requisito | Tarefa |
|---|---|
| Frete grátis mostra no frontend | Task 1 (bug fix preco, id gratis-valor, env) |
| Valor correto ao selecionar frete grátis | Task 1 Step 2+3 |
| Mobile checkout — resumo primeiro | Task 2 Step 2 |
| Mobile checkout — título responsivo | Task 2 Step 1 |
| Mobile checkout — sticky só desktop | Task 2 Step 3 |
| Mobile checkout — touch targets | Task 2 Step 4 |
| Mobile catálogo — filtros como drawer | Task 3 Steps 2-4 |
| Mobile catálogo — grid 2 colunas | Task 3 Step 5 |
| Mobile produto — gap menor | Task 4 Step 1 |
| Mobile produto — breadcrumb truncado | Task 4 Step 2 |
| Mobile produto — CTA fixo | Task 4 Steps 4-5 |
