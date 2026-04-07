# Rename /finalizar-compra to /checkout Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Renomear a rota `/finalizar-compra` para `/checkout` em todos os lugares — rota, nome, controller, views, testes.

**Architecture:** Mudança puramente de nomenclatura. A rota nomeada `site.finalizar-compra` vira `site.checkout`. O método do controller `finalizar_compra` vira `checkout`. A view `site/finalizar-compra.blade.php` vira `site/checkout.blade.php`. Nenhuma lógica é alterada.

**Tech Stack:** Laravel 12, Blade, PHP

---

## Arquivos a Modificar/Renomear

| Arquivo | Mudança |
|---|---|
| `routes/web.php:52` | URL `/finalizar-compra` → `/checkout`, nome `site.finalizar-compra` → `site.checkout`, método `finalizar_compra` → `checkout` |
| `app/Http/Controllers/SiteController.php:528` | Renomear método `finalizar_compra` → `checkout` |
| `resources/views/site/finalizar-compra.blade.php` | Renomear arquivo para `checkout.blade.php` |
| `resources/views/includes/header.blade.php:261` | `route('site.finalizar-compra')` → `route('site.checkout')` |
| `app/Http/Controllers/PedidoController.php:118,153` | `route('site.finalizar-compra')` → `route('site.checkout')` |
| `tests/Feature/GuestCheckoutTest.php:45` | `route('site.finalizar-compra')` → `route('site.checkout')` |
| `tests/Feature/CheckoutRecoveryTest.php:37` | `route('site.finalizar-compra')` → `route('site.checkout')` |

---

## Task 1: Renomear a rota e o método do controller

**Files:**
- Modify: `routes/web.php:52`
- Modify: `app/Http/Controllers/SiteController.php:528`

- [ ] **Step 1: Atualizar `routes/web.php`**

Trocar a linha 52 de:
```php
Route::get('/finalizar-compra', [SiteController::class, 'finalizar_compra'])->name('site.finalizar-compra');
```
Para:
```php
Route::get('/checkout', [SiteController::class, 'checkout'])->name('site.checkout');
```

- [ ] **Step 2: Renomear o método em `SiteController.php`**

Localizar o método (linha ~528):
```php
public function finalizar_compra()
```
Renomear para:
```php
public function checkout()
```

- [ ] **Step 3: Verificar que a rota existe**

```bash
docker exec laravel-app php artisan route:list | grep checkout
```

Expected: linha com `GET /checkout ... site.checkout`

- [ ] **Step 4: Commit**

```bash
git add routes/web.php app/Http/Controllers/SiteController.php
git commit -m "feat: renomear rota /finalizar-compra para /checkout"
```

---

## Task 2: Renomear a view Blade

**Files:**
- Rename: `resources/views/site/finalizar-compra.blade.php` → `resources/views/site/checkout.blade.php`

- [ ] **Step 1: Renomear o arquivo**

```bash
mv /var/www/html/resources/views/site/finalizar-compra.blade.php \
   /var/www/html/resources/views/site/checkout.blade.php
```

- [ ] **Step 2: Atualizar a chamada `view()` no SiteController**

Em `app/Http/Controllers/SiteController.php`, na linha do `return view(...)` dentro do método `checkout()`, trocar:
```php
return view('site.finalizar-compra', compact('carrinho', 'enderecos'));
```
Para:
```php
return view('site.checkout', compact('carrinho', 'enderecos'));
```

- [ ] **Step 3: Limpar cache de views**

```bash
docker exec laravel-app php artisan view:clear
```

- [ ] **Step 4: Commit**

```bash
git add app/Http/Controllers/SiteController.php \
        resources/views/site/checkout.blade.php
git commit -m "feat: renomear view finalizar-compra para checkout"
```

---

## Task 3: Atualizar todas as referências à rota nomeada

**Files:**
- Modify: `resources/views/includes/header.blade.php:261`
- Modify: `app/Http/Controllers/PedidoController.php:118,153`

- [ ] **Step 1: Atualizar `header.blade.php`**

Na linha 261, trocar:
```php
<a href="{{ route('site.finalizar-compra') }}"
```
Para:
```php
<a href="{{ route('site.checkout') }}"
```

- [ ] **Step 2: Atualizar `PedidoController.php`**

Linha 118:
```php
'redirect_url' => route('site.finalizar-compra')
```
→
```php
'redirect_url' => route('site.checkout')
```

Linha 153:
```php
return redirect()->route('site.finalizar-compra');
```
→
```php
return redirect()->route('site.checkout');
```

- [ ] **Step 3: Verificar que não há mais referências antigas**

```bash
grep -rn "finalizar.compra\|finalizar_compra" /var/www/html/app /var/www/html/resources /var/www/html/routes /var/www/html/public/js
```

Expected: nenhum resultado (exceto possivelmente comentários irrelevantes).

- [ ] **Step 4: Commit**

```bash
git add resources/views/includes/header.blade.php \
        app/Http/Controllers/PedidoController.php
git commit -m "feat: atualizar referências de rota para site.checkout"
```

---

## Task 4: Atualizar testes e verificar tudo

**Files:**
- Modify: `tests/Feature/GuestCheckoutTest.php:45`
- Modify: `tests/Feature/CheckoutRecoveryTest.php:17,37`

- [ ] **Step 1: Atualizar `GuestCheckoutTest.php`**

Na linha 45, trocar:
```php
$this->get(route('site.finalizar-compra'))
```
Para:
```php
$this->get(route('site.checkout'))
```

- [ ] **Step 2: Atualizar `CheckoutRecoveryTest.php`**

Na linha 37, trocar:
```php
$response = $this->actingAs($user)->get(route('site.finalizar-compra'));
```
Para:
```php
$response = $this->actingAs($user)->get(route('site.checkout'));
```

- [ ] **Step 3: Rodar os testes afetados**

```bash
docker exec laravel-app php artisan test --filter=GuestCheckoutTest
docker exec laravel-app php artisan test --filter=CheckoutRecoveryTest
```

Expected: todos PASS.

- [ ] **Step 4: Rodar suite completa**

```bash
docker exec laravel-app php artisan test
```

Expected: todos PASS (ou mesmo número de falhas de antes desta tarefa).

- [ ] **Step 5: Commit**

```bash
git add tests/Feature/GuestCheckoutTest.php \
        tests/Feature/CheckoutRecoveryTest.php
git commit -m "test: atualizar testes para usar rota site.checkout"
```
