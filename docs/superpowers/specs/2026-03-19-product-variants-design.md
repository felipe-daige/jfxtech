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
valores             JSON            array de produto_opcao_valor_id que formam a combinação
preco               decimal(10,2) nullable   null = herda preco do produto pai
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
- accessor `label` → resolve os IDs em valores legíveis (ex: "Preto / M")
- accessor `preco_efetivo` → retorna `preco` próprio ou `produto->preco` se null
- accessor `estoque_efetivo` → retorna `estoque` próprio ou `produto->estoque` se `estoque_compartilhado = true`

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

## Admin

### UI — Aba "Variantes" no modal de produto

A tela de edição de produto ganha uma terceira aba "Variantes" (ao lado das abas de dados e imagens já existentes).

**Fluxo de uso:**
1. Admin cria um grupo (ex: "Cor") e adiciona valores ("Preto", "Branco")
2. Pode criar múltiplos grupos (ex: "Cor" + "Tamanho")
3. Clica em "Gerar Combinações" → sistema faz produto cartesiano e cria variantes
4. Tabela de variantes exibe uma linha por combinação com campos editáveis de preço e estoque
5. Checkbox "Compartilhar estoque entre variantes" controla o campo `estoque_compartilhado` do produto

**Comportamento da geração:**
- Variantes já existentes são preservadas (preço/estoque não sobrescritos)
- Novas combinações são criadas com `preco = null` e `estoque = null`
- Combinações removidas (quando valores são deletados) ficam inativas

### Novos endpoints no `AdminController`

```
GET  /admin/produtos/{id}/variantes          → retorna grupos, valores e variantes
POST /admin/produtos/{id}/opcao-grupos       → cria/atualiza grupos e valores (batch)
POST /admin/produtos/{id}/variantes/gerar    → gera combinações via produto cartesiano
PUT  /admin/produtos/{id}/variantes          → salva preços/estoques/ativo em batch
```

Todos os endpoints retornam JSON e requerem `Auth::check()`.

## Frontend — Página do Produto

### Blade (`produto-detalhes.blade.php`)

Inserir bloco de seleção de variantes entre a descrição curta e o controle de quantidade, condicionado a `$produto->tem_variantes`:

```blade
@if($produto->tem_variantes)
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

Variantes são passadas como JSON inline para o JS via `data-variantes` no botão de adicionar ao carrinho.

### JS (`produto-detalhes.js`)

- Rastreia seleção atual por grupo: `{ grupoId: valorId, ... }`
- Ao completar seleção de todos os grupos:
  - Encontra a variante correspondente no JSON inline
  - Atualiza preço exibido
  - Atualiza estoque exibido
  - Preenche `data-variante-id` no botão de adicionar ao carrinho
  - Habilita o botão
- Enquanto seleção incompleta: botão desabilitado com texto "SELECIONE AS OPÇÕES"
- Botão de variante selecionada recebe classe `border-black bg-black text-white`

## Carrinho

**`CarrinhoController`** — método de adicionar ao carrinho:
- Aceita `produto_variante_id` opcional no request
- Se fornecido, valida que a variante pertence ao produto e está ativa
- Usa `estoque_efetivo` da variante para checar disponibilidade
- Grava `produto_variante_id` e `opcoes_snapshot` no `ItemPedido`

**Exibição no carrinho** (`cart.js` / view do carrinho):
- Exibe o `opcoes_snapshot` abaixo do nome do produto (ex: "Cor: Preto · Tamanho: M")

## O que não muda

- Models `Pedido`, `User`, `Categoria`, `ProdutoImagem`
- Lógica de frete, favoritos, checkout
- Estrutura geral do carrinho (continua baseado em `Pedido` com `status='carrinho'`)
- Produtos sem variantes funcionam exatamente como hoje

## Casos de borda

- **Produto sem variantes:** fluxo atual inalterado, `produto_variante_id` fica null no `ItemPedido`
- **Variante desativada:** não aparece como selecionável na página do produto
- **Variante deletada após compra:** `set null on delete` em `produto_variante_id`; `opcoes_snapshot` preserva o histórico legível
- **Estoque esgotado em variante individual:** botão desabilitado ao selecionar essa combinação
