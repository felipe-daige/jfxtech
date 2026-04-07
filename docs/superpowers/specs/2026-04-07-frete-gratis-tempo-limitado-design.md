# Spec: Frete Grátis por Tempo Limitado

**Data:** 2026-04-07  
**Status:** Aprovado

---

## Visão Geral

Adicionar uma opção de **Frete Grátis promocional** no checkout, controlada manualmente via `.env`. Quando ativa, aparece como terceira opção de frete (junto com PAC/SEDEX), é selecionada por padrão, e exibe comunicação visual de urgência (banner + badge "Tempo Limitado"). Qualquer valor de pedido qualifica — sem mínimo.

---

## Configuração

Duas variáveis de ambiente controlam o comportamento:

| Variável | Padrão | Descrição |
|---|---|---|
| `FRETE_GRATIS_ATIVO` | `false` | Liga/desliga a promoção |
| `FRETE_GRATIS_MINIMO` | `0` | Valor mínimo do pedido (0 = qualquer valor) |

Para **ativar:** `FRETE_GRATIS_ATIVO=true`  
Para **desativar:** `FRETE_GRATIS_ATIVO=false`

Nenhuma migration, nenhum painel admin — troca no `.env` e `php artisan config:clear`.

---

## Backend

### `config/services.php`

Adicionar chave `frete_gratis_ativo`:

```php
'frete_gratis_ativo' => env('FRETE_GRATIS_ATIVO', false),
'frete_gratis_minimo' => env('FRETE_GRATIS_MINIMO', 0),
```

### `FreteController::calcularFreteCarrinho()`

Lógica atual: só retorna opção `gratis` se `FRETE_GRATIS_MINIMO > 0` e subtotal atingir o mínimo.

Nova lógica:
1. Se `frete_gratis_ativo = true`:
   - Verificar se subtotal >= `frete_gratis_minimo` (com mínimo = 0, sempre passa)
   - Adicionar opção `gratis` ao array de opções com flag `tempo_limitado: true`
2. Se `frete_gratis_ativo = false`: comportamento atual (sem opção grátis)

Payload da opção `gratis`:

```json
{
  "nome": "Frete Grátis",
  "descricao": "Promoção por tempo limitado!",
  "valor": 0.00,
  "prazo": "5–7 dias úteis",
  "tempo_limitado": true
}
```

### `.env.example`

Adicionar:

```
FRETE_GRATIS_ATIVO=false
FRETE_GRATIS_MINIMO=0
```

---

## Frontend

### Banner no checkout (`finalizar-compra.blade.php`)

Quando `config('services.frete_gratis_ativo')` é `true`, renderizar banner acima da seção de resumo do pedido:

**Visual:** Fundo preto, texto branco, ícone de relógio, tipografia `font-mono` em uppercase. Exemplo:

```
⏰  FRETE GRÁTIS POR TEMPO LIMITADO — APROVEITE!
```

Implementado em Blade puro (`@if`), sem JS.

### Opção de frete no checkout (JS)

O JS de `calcularFrete()` já popula as opções dinamicamente a partir da resposta da API. Adicionar tratamento para a chave `gratis`:

- Se `data.opcoes.gratis` existir: renderizar um card de frete adicional acima de PAC/SEDEX
- **Card visual:** borda verde, badge preto "TEMPO LIMITADO" no canto superior direito, preço em verde ("R$ 0,00"), texto "Frete Grátis" em bold
- Selecionada automaticamente por padrão quando presente
- O radio input tem `value="gratis"` — funciona com o restante do fluxo de checkout sem alteração

### Opção estática no HTML (Blade)

Para evitar flash/CLS antes do JS carregar, adicionar o card `gratis` como elemento Blade oculto por padrão (renderizado apenas quando `frete_gratis_ativo = true`), revelado pelo JS quando a API confirmar.

---

## Fluxo de dados

```
[.env FRETE_GRATIS_ATIVO=true]
        ↓
[FreteController retorna opcoes.gratis]
        ↓
[JS recebe e renderiza card "Frete Grátis" selecionado por padrão]
        ↓
[Blade exibe banner no topo]
        ↓
[Usuário continua para pagamento com frete_tipo=gratis, frete_valor=0]
```

O `MercadoPagoCheckoutController` já lida com `frete_valor = 0` corretamente (lógica de `frete_gratis_minimo` existente).

---

## O que NÃO muda

- Rotas — nenhuma rota nova
- Models/migrations — zero alterações de banco
- PAC/SEDEX — comportamento inalterado
- Retirada na Loja — continua disponível como sempre
- Fluxo de pagamento — `frete_tipo='gratis'` já é aceito pelo controller de checkout

---

## Arquivos a modificar

| Arquivo | Mudança |
|---|---|
| `.env.example` | Adicionar `FRETE_GRATIS_ATIVO` e `FRETE_GRATIS_MINIMO` |
| `config/services.php` | Adicionar chave `frete_gratis_ativo` |
| `app/Http/Controllers/FreteController.php` | Refatorar lógica de frete grátis |
| `resources/views/site/finalizar-compra.blade.php` | Banner Blade + card estático + JS atualizado |
