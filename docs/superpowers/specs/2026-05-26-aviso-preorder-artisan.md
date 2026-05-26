# Spec: Aviso de Pré-encomenda para Produtos Artisan

**Data:** 2026-05-26  
**Status:** Aprovado  

---

## Contexto

Os 5 produtos da marca Artisan vendidos na JFXTech são importados direto do Japão e, por um período temporário, precisam ser encomendados à loja antes do envio ao cliente. O prazo estimado é de até 15 dias para chegada. Os clientes precisam ser informados disso antes de comprar e no momento de finalizar o checkout.

Produtos afetados (IDs no banco):
- Artisan FX Hien (id 160)
- Artisan FX Key-83 (id 159)
- Artisan FX Type-99 (id 155)
- Artisan NINJA FX Zero (id 154)
- Artisan FX Raiden (id 161)

Todos atualmente têm `marca = NULL`. A migration corrige isso.

---

## Design

### 1. Migration — Preencher campo `marca`

Arquivo: `database/migrations/2026_05_26_000000_set_artisan_marca_on_produtos.php`

Executa:
```sql
UPDATE produtos SET marca = 'Artisan' WHERE nome ILIKE '%artisan%'
```

Sem nova coluna, sem alteração de schema. Apenas normaliza o dado que estava incompleto.

---

### 2. Partial — `aviso-preorder-artisan.blade.php`

Arquivo: `resources/views/includes/aviso-preorder-artisan.blade.php`

Estilo Sober Tech (B&W, font-mono, bordas). Conteúdo:
- Badge `PRÉ-ENCOMENDA` em preto com texto branco
- Ícone de relógio/navio ao lado
- Mensagem: "Este produto é importado direto do Japão e precisa ser encomendado à loja antes do envio. Prazo estimado: até 15 dias para chegada."
- Sem botão de fechar — aviso sempre visível
- Borda preta sólida esquerda (bordel-l-4 border-black) ou borda completa, fundo off-white (bg-zinc-50)

---

### 3. Integração — Página do Produto

Arquivo: `resources/views/site/produto-detalhes.blade.php`

Posição: imediatamente após o bloco "Stock Status" (linha ~171) e antes da grade de specs (linha ~173).

```blade
@if($produto->marca === 'Artisan')
    @include('includes.aviso-preorder-artisan')
@endif
```

---

### 4. Integração — Checkout

Arquivo: `resources/views/site/checkout.blade.php`

Posição: no topo do painel "Resumo do Pedido" (antes da lista de itens, linha ~163), dentro do `<div>` do resumo.

Condição: verifica se qualquer item do carrinho é de um produto Artisan.

```blade
@if($carrinho->itens->contains(fn($i) => $i->produto->marca === 'Artisan'))
    @include('includes.aviso-preorder-artisan')
@endif
```

> Nota: `$carrinho->itens` já é carregado na view via eager loading — sem query adicional.

---

## Remoção futura

Quando os produtos Artisan estiverem em estoque local e o aviso não for mais necessário:

1. Deletar `resources/views/includes/aviso-preorder-artisan.blade.php`
2. Remover os dois blocos `@if($produto->marca === 'Artisan') @include(...)` das views
3. O campo `marca = 'Artisan'` pode permanecer no banco — é dado correto e não prejudica nada.

---

## O que este spec não cobre

- Mudança no comportamento do botão "Adicionar ao Carrinho" (permanece igual)
- Prazo dinâmico configurável (hardcoded em 15 dias)
- Notificação por e-mail sobre pré-encomenda
