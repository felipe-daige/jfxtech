# Design: Desconto PIX Configurável

**Data:** 2026-05-15  
**Status:** Aprovado

---

## Contexto

O percentual de desconto PIX (atualmente 5% hardcoded nas views) precisa ser configurável pelo admin não-técnico, tanto globalmente quanto por produto individual. A lógica atual multiplica `preco_com_desconto * 0.95` diretamente nas views Blade e no JS de variantes.

---

## Objetivo

- Admin edita o desconto PIX global pelo dashboard (sem tocar em código)
- Admin pode sobrescrever o percentual por produto individual
- Fallback: produto usa o próprio valor → global do banco → 5% hardcoded

---

## Dados

### Nova tabela: `configuracoes`

Key-value store para configurações da loja. Reutilizável para futuras configs.

| Campo  | Tipo             | Descrição                        |
|--------|------------------|----------------------------------|
| `id`   | bigint PK        |                                  |
| `chave`| varchar unique   | Ex: `desconto_pix_global`        |
| `valor`| varchar          | Ex: `5.00`                       |

Seed inicial: `desconto_pix_global = 5.00`.

### Nova coluna: `produtos.desconto_pix`

| Campo          | Tipo              | Descrição                              |
|----------------|-------------------|----------------------------------------|
| `desconto_pix` | decimal(5,2) null | `null` = herda global; valor = override|

---

## Model `Configuracao`

- `Configuracao::get($chave, $default)` — lê do banco com cache de 60s
- `Configuracao::set($chave, $valor)` — salva e invalida o cache

O cache evita queries repetidas em cada card de produto renderizado.

## Accessor no model `Produto`

```php
public function getDescontoPix(): float
{
    return (float) ($this->desconto_pix 
        ?? Configuracao::get('desconto_pix_global', 5.0));
}
```

Usado em qualquer lugar que precise do percentual efetivo do produto.

---

## Admin UI

### Card no Dashboard (`/admin/dashboard`)

- Campo numérico "Desconto PIX Global" com o valor atual
- POST `/admin/configuracoes` → `AdminConfiguracaoController@update`
- Feedback inline via JSON (sem redirect de página)
- Exibe o valor atual ao carregar

### Campo por produto (`produto-ver.blade.php`)

- Campo `desconto_pix` nullable no bloco de preço/promoção
- Placeholder mostra o valor global atual: "vazio = usa global (X%)"
- Enviado via o fluxo `quickEdit` existente do `AdminController`
- Se apagado/nulo, o produto volta a herdar o global

---

## Views Blade

Substituir `* 0.95` hardcoded em dois arquivos:

**`resources/views/components/product-card.blade.php`**
```php
@php
    $fator = 1 - ($produto->getDescontoPix() / 100);
    $precoPix = $produto->preco_com_desconto * $fator;
    $valorParcela = $produto->preco_com_desconto / 10;
@endphp
```

**`resources/views/site/produto-detalhes.blade.php`**
```php
@php
    $fator = 1 - ($produto->getDescontoPix() / 100);
    $precoPix = $produto->preco_com_desconto * $fator;
    $valorParcela = $produto->preco_com_desconto / 10;
@endphp
```

O bloco `#price-pix` recebe `data-desconto-pix` com o valor efetivo:
```html
<span id="price-pix" data-desconto-pix="{{ $produto->getDescontoPix() }}">
```

---

## JS de Variantes (`public/js/produto-detalhes.js`)

Substituir o `0.95` fixo pelo valor lido do `data-attribute`:

```js
var descontoPix = parseFloat(priceEl.dataset.descontoPix) || 5;
var pixPreco = varianteEncontrada.preco_efetivo * (1 - descontoPix / 100);
```

O percentual é lido do DOM uma vez, no momento em que o produto carrega — não precisa de chamada extra ao servidor.

---

## Rotas Novas

| Método | Rota                     | Controller                          |
|--------|--------------------------|-------------------------------------|
| POST   | `/admin/configuracoes`   | `AdminConfiguracaoController@update`|

Dentro do grupo `prefix('admin')->middleware(['auth','admin'])` existente.

O `quickEdit` de produto já existe — apenas aceitar o novo campo `desconto_pix` no `$fillable` e no handler.

---

## Migrações

1. `create_configuracoes_table` — cria a tabela e faz seed de `desconto_pix_global = 5.00`
2. `add_desconto_pix_to_produtos_table` — adiciona coluna nullable

---

## Fora de Escopo

- Configurações admin de outros valores além do desconto PIX (a tabela suporta, mas a UI não será construída agora)
- Número de parcelas (10x fixo, não configurável neste spec)
- Desconto PIX por variante (apenas por produto)
