# Design: Sistema de Variantes de Produto

**Data:** 2026-03-19
**Status:** Aprovado

## Contexto

A loja JFX Tech vende mousepads e outros periféricos que podem ter opções selecionáveis pelo cliente (cor, tamanho, tipo de espuma, etc.). Nem todos os produtos possuem variantes — apenas os que o administrador configurar. Variantes podem ter preço próprio diferente do produto base. O estoque pode ser compartilhado entre todas as variantes ou controlado individualmente por variante.

## Escopo

- Admin pode criar grupos de opções (ex: "Cor") e seus valores (ex: "Preto", "Branco") para qualquer produto
- Admin pode gerar as combinações (variantes) automaticamente via produto cartesiano dos valores
- Cada variante pode ter preço próprio (opcional; herda preço base se não configurado)
- Estoque pode ser compartilhado (usa `produtos.estoque`) ou individual por variante (usa `produto_variantes.estoque`)
- Cliente seleciona opções na página do produto antes de adicionar ao carrinho
- Cada combinação selecionada é um item distinto no carrinho

## Banco de Dados

### Nova tabela: `produto_opcao_grupos`

```
id                  bigint PK
produto_id          FK → produtos (cascade delete)
nome                string          ex: "Cor", "Tamanho"
ordem               integer         ordenação na UI
timestamps

UNIQUE(produto_id, nome)            — sem grupos duplicados por produto
```

### Nova tabela: `produto_opcao_valores`

```
id                  bigint PK
grupo_id            FK → produto_opcao_grupos (cascade delete)
valor               string          ex: "Preto", "450x400mm"
ordem               integer         ordenação na UI
timestamps
```

### Nova tabela: `produto_variantes`

```
id                  bigint PK
produto_id          FK → produtos (cascade delete)
valores             JSON            array de produto_opcao_valor_id (sempre ordenado)
preco               decimal(10,2) nullable   null = herda preco_com_desconto do produto pai
estoque             integer nullable         null = usa estoque compartilhado do produto pai
ativo               boolean default true
timestamps
```

### Alterações em tabelas existentes

**`produtos`** — nova coluna:
```
estoque_compartilhado   boolean default true
```

**`itens_pedido`** — novas colunas:
```
produto_variante_id     FK nullable → produto_variantes (set null on delete)
opcoes_snapshot         JSON nullable   ex: {"Cor": "Preto", "Tamanho": "M"}
```

## Models

### Novos

**`ProdutoOpcaoGrupo`**
- `belongsTo Produto`
- `hasMany ProdutoOpcaoValor` (ordenado por `ordem`)
- fillable: `produto_id`, `nome`, `ordem`

**`ProdutoOpcaoValor`**
- `belongsTo ProdutoOpcaoGrupo`
- fillable: `grupo_id`, `valor`, `ordem`

**`ProdutoVariante`**
- `belongsTo Produto`
- fillable: `produto_id`, `valores`, `preco`, `estoque`, `ativo`
- casts: `valores` → array, `preco` → decimal:2, `ativo` → boolean
- accessor `label` → resolve os IDs em valores legíveis (ex: "Preto / M"); **nunca faz queries** — depende de `valores` como relation carregada via eager loading ou de mapa passado pelo chamador
- accessor `preco_efetivo` → retorna `$this->preco` próprio **ou `round($this->produto->preco_com_desconto, 2)`** se null (herdando o preço com desconto do produto, arredondado para 2 casas para consistência com o cast decimal:2 da variante)
- accessor `estoque_efetivo` → retorna `$this->estoque` próprio ou `$this->produto->estoque` se `estoque_compartilhado = true`. Se `estoque_compartilhado = false` e `$this->estoque = null`, retorna `0` (sem estoque — admin deve configurar explicitamente)

### Alterações em models existentes

**`Produto`**
- `hasMany ProdutoOpcaoGrupo`
- `hasMany ProdutoVariante`
- fillable: adicionar `estoque_compartilhado`
- casts: `estoque_compartilhado` → boolean
- accessor `tem_variantes` → bool: `$this->opcaoGrupos()->exists()`

**`ItemPedido`**
- `belongsTo ProdutoVariante` (nullable)
- fillable: adicionar `produto_variante_id`, `opcoes_snapshot`
- casts: `opcoes_snapshot` → array

## Admin — UI

### Tabs no modal de produto

O modal de produto atual é um formulário linear sem sistema de tabs. A aba "Variantes" implica **criar um sistema de tabs no modal** (`admin.js` + HTML do modal) — isso é trabalho novo, não adição a estrutura existente.

A tela de edição de produto ganha uma terceira aba "Variantes".

**Fluxo de uso:**
1. Admin cria um grupo (ex: "Cor") e adiciona valores ("Preto", "Branco")
2. Pode criar múltiplos grupos (ex: "Cor" + "Tamanho")
3. Clica em "Gerar Combinações" → sistema faz produto cartesiano e cria variantes
4. Tabela de variantes exibe uma linha por combinação com campos editáveis de preço e estoque
5. Checkbox "Compartilhar estoque entre variantes" controla o campo `estoque_compartilhado` do produto

**Comportamento da geração:**
- Variantes já existentes são preservadas (preço/estoque não sobrescritos)
- Novas combinações são criadas com `preco = null` e `estoque = null`
- IDs dos valores são sempre **ordenados** antes de comparar/inserir para evitar duplicatas por ordem diferente
- Combinações removidas (quando valores são deletados) ficam inativas

## Admin — Endpoints

```
GET  /admin/produtos/{id}/opcoes             → retorna grupos, valores e variantes
POST /admin/produtos/{id}/opcao-grupos       → cria/atualiza grupos e valores (batch)
POST /admin/produtos/{id}/variantes/gerar    → gera combinações via produto cartesiano (idempotente)
PUT  /admin/produtos/{id}/variantes          → salva preços/estoques/ativo em batch (DB::transaction)
```

**Registro de rotas:** estas rotas devem ser registradas em `web.php` **imediatamente antes da linha** `Route::get('/admin/produtos/{id}', ...)`. O URI `opcoes` (não `variantes`) é usado no GET para evitar ambiguidade com o path `/variantes/gerar`.

Todos os endpoints retornam JSON e requerem `Auth::check()`.

**`GET opcoes`:** carrega `opcaoGrupos.valores` e `variantes` com eager loading. O campo `label` de cada variante é resolvido no servidor a partir do mapa de valores já carregado — sem N+1.

**`POST opcao-grupos` — validação:**
- `grupos` array required
- cada grupo: `nome` string required max:50; unique por produto
- cada valor dentro do grupo: `valor` string required max:100, `ordem` integer optional

**`PUT variantes` — payload e validação:**
```json
{
  "estoque_compartilhado": true,
  "variantes": [
    { "id": 1, "preco": 149.90, "estoque": null, "ativo": true },
    { "id": 2, "preco": null,   "estoque": 5,    "ativo": false }
  ]
}
```
- Campos de cada variante são todos opcionais (partial update)
- `preco` nullable numeric min:0; `estoque` nullable integer min:0; `ativo` boolean
- Toda a operação em `DB::transaction()`

## Frontend — Página do Produto

### Blade (`produto-detalhes.blade.php`)

Condição de exibição: `$produto->tem_variantes && $produto->variantesAtivas->count() > 0` (não exibir se grupos existem mas nenhuma variante ativa foi gerada).

Inserir bloco de seleção entre a descrição curta e o controle de quantidade:

```blade
@if($produto->tem_variantes && $produto->variantesAtivas->count() > 0)
  @foreach($produto->opcaoGrupos as $grupo)
    <div class="mb-4">
      <span class="text-xs font-mono font-bold uppercase tracking-widest">{{ $grupo->nome }}</span>
      <div class="flex flex-wrap gap-2 mt-2">
        @foreach($grupo->valores as $valor)
          <button class="opcao-btn border border-[var(--color-lab-border)] px-4 py-2 text-sm font-mono hover:border-black transition-colors"
                  data-grupo="{{ $grupo->id }}"
                  data-valor="{{ $valor->id }}">
            {{ $valor->valor }}
          </button>
        @endforeach
      </div>
    </div>
  @endforeach
@endif
```

Variantes ativas passadas como JSON inline via `data-variantes` no botão de adicionar ao carrinho (inclui `id`, `valores` array de valor_ids, `preco_efetivo`, `estoque_efetivo`).

### JS (`produto-detalhes.js`)

- Rastreia seleção atual por grupo: `{ grupoId: valorId, ... }`
- Ao completar seleção de todos os grupos:
  - Encontra a variante correspondente no JSON inline (comparando arrays ordenados de valor_ids)
  - Atualiza preço exibido com `variante.preco_efetivo`
  - Atualiza estoque exibido com `variante.estoque_efetivo`
  - Preenche `data-variante-id` no botão de adicionar ao carrinho
  - Habilita o botão (se variante ativa e em estoque)
- Enquanto seleção incompleta: botão desabilitado com texto "SELECIONE AS OPÇÕES"
- Botão de variante selecionada recebe classe `border-black bg-black text-white`

## Carrinho — `CarrinhoController`

### Chave composta obrigatória

**Dois itens com o mesmo `produto_id` mas `produto_variante_id` diferentes são sempre linhas distintas em `itens_pedido` — nunca devem ser mesclados.** Os métodos `adicionar()`, `remover()`, `atualizar_quantidade()` e `verificar_produto()` devem usar `(produto_id, produto_variante_id)` como chave composta em todas as queries WHERE. `produto_variante_id = null` é um valor de chave válido (produtos sem variante).

### Validação de `produto_variante_id` em `adicionar()`

Quando `produto_variante_id` é fornecido no request:
1. Validar `produto_variante_id` como `nullable|exists:produto_variantes,id`
2. Validar que `produto_variante_id` pertence ao `produto_id` enviado (cross-FK check: `produto_variantes.produto_id = request.produto_id`) — impede que um cliente aplique o preço de uma variante de outro produto
3. Validar que a variante está ativa (`ativo = true`)

### Preço gravado no item

`itens_pedido.preco` = `$variante->preco_efetivo` quando variante presente; `$produto->preco` quando sem variante.

**Caminho de item existente (`$itemExistente` encontrado em `adicionar()`):** o `preco` não é atualizado — mantém o valor registrado no momento da adição original. Apenas a `quantidade` é incrementada. Isso preserva o preço contratado quando o produto muda de preço após a adição.

### Validação de estoque

- `adicionar()`: usa `$variante->estoque_efetivo` quando variante presente; `$produto->estoque` quando sem variante
- `atualizar_quantidade()`: idem — carrega a variante a partir do `ItemPedido` existente e usa `$variante->estoque_efetivo`

### `opcoes_snapshot`

Montado pelo servidor em `adicionar()`. Carrega `$variante->with('valores.grupo')` e monta `{grupo->nome: valor->valor}`. Nunca aceita snapshot do frontend.

### Endpoint `itens()`

Eager-load `produtoVariante` nos itens do carrinho. Incluir `produto_variante_id` e `opcoes_snapshot` no JSON retornado para que `cart.js` exiba as opções abaixo do nome do produto.

### Endpoint `verificar_produto()`

Quando `produto_variante_id` é fornecido, a resposta deve incluir:
```json
{ "no_carrinho": true, "quantidade": 2, "produto_variante_id": 5 }
```
Se o produto existe no carrinho mas com variante diferente (ou sem variante), `no_carrinho = false`.

## O que não muda

- Models `Pedido`, `User`, `Categoria`, `ProdutoImagem`
- Lógica de frete, favoritos, checkout
- Estrutura geral do carrinho (continua baseado em `Pedido` com `status='carrinho'`)
- Produtos sem variantes funcionam exatamente como hoje

## Interação com promoções

Variantes com `preco` próprio não herdam `em_promocao`/`desconto_percentual` do produto pai — seu preço já é o preço final configurado pelo admin. O accessor `preco_efetivo` retorna o valor direto sem aplicar desconto adicional. Variantes com `preco = null` herdam `$produto->preco_com_desconto` (que já pode refletir desconto de promoção).

## Casos de borda

- **Produto sem variantes:** fluxo atual inalterado, `produto_variante_id` fica null no `ItemPedido`
- **Variante desativada:** não aparece como selecionável na página do produto
- **Variante deletada após compra:** `set null on delete` em `produto_variante_id`; `opcoes_snapshot` preserva o histórico legível
- **Estoque esgotado em variante individual:** botão desabilitado ao selecionar essa combinação
- **Grupos sem valores/variantes geradas:** bloco de seleção não exibido ao cliente (`variantesAtivas->count() = 0`)
