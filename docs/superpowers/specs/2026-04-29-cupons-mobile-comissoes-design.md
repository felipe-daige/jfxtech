# Spec: Cupons — Mobile, Métricas de Receita e Gestão de Comissões

**Data:** 2026-04-29
**Escopo:** Página `/admin/cupons` — redesign mobile-first com cards, métricas por cupom e CRUD de pagamentos de comissão para parceiros.

---

## Contexto

A página atual é uma tabela de 10 colunas sem suporte mobile. Não existe nenhum sistema de rastreio de comissões — o sistema antigo de afiliados (`affiliate_commissions`) foi dropado em abril/2026. A comissão devida ao parceiro não é calculada em nenhum lugar.

---

## Modelo de Dados

### 1. Alterar tabela `cupons`

Adicionar coluna:

```
comissao_percentual  DECIMAL(5,2)  NOT NULL  DEFAULT 100
```

Define quanto % do desconto gerado pelo cupom vai para o parceiro. Padrão 100 (o parceiro recebe tudo que gerou de desconto). Pode ser configurado individualmente por cupom (ex: 50 = parceiro recebe metade do desconto).

### 2. Nova tabela `cupom_pagamentos`

```
id                  BIGINT PK
cupom_id            FK → cupons (cascade delete)
valor_pago          DECIMAL(10,2) NOT NULL
observacao          TEXT nullable
pago_em             DATE NOT NULL
created_at
updated_at
```

Representa um pagamento registrado ao parceiro de um cupom. Um pagamento pode cobrir um ou vários usos do cupom.

### 3. Alterar tabela `cupom_usos`

Adicionar coluna:

```
cupom_pagamento_id  BIGINT nullable FK → cupom_pagamentos (SET NULL on delete)
```

Quando `null` = comissão pendente. Quando preenchido = comissão quitada por aquele pagamento. Deletar um pagamento libera os usos de volta para pendente via `SET NULL`.

### Cálculo de comissão por uso (dinâmico)

```
comissao_uso = pedido.valor_desconto × (cupom.comissao_percentual / 100)
```

Calculado dinamicamente. Usos **não pagos** refletem mudanças no `comissao_percentual` do cupom. Usos já vinculados a um `cupom_pagamento` são imutáveis historicamente.

---

## Novos Models

### `CupomPagamento`

```php
// Tabela: cupom_pagamentos
$fillable = ['cupom_id', 'valor_pago', 'observacao', 'pago_em']
$casts    = ['pago_em' => 'date', 'valor_pago' => 'decimal:2']

belongsTo Cupom
hasMany   CupomUso  (via cupom_pagamento_id)
```

### Atualizar `CupomUso`

Adicionar ao `$fillable`: `cupom_pagamento_id`
Adicionar relação: `belongsTo CupomPagamento`

### Atualizar `Cupom`

Adicionar ao `$fillable`: `comissao_percentual`
Adicionar ao `$casts`: `'comissao_percentual' => 'decimal:2'`
Adicionar relação: `hasMany CupomPagamento`

---

## Novas Rotas (dentro do grupo admin)

```
GET    /admin/cupons/{id}/pagamentos           → AdminCupomController@pagamentos
POST   /admin/cupons/{id}/pagamentos           → AdminCupomController@storePagamento
PUT    /admin/cupons/{id}/pagamentos/{pid}     → AdminCupomController@updatePagamento
DELETE /admin/cupons/{id}/pagamentos/{pid}     → AdminCupomController@destroyPagamento
GET    /admin/cupons/{id}/usos-pendentes       → AdminCupomController@usosPendentes
```

---

## Controller — Novos Métodos em `AdminCupomController`

### `index` (atualizar)

Carregar junto:
- `usos` com `pedido` (para calcular receita/descontos/comissão)
- `pagamentos`
- `user`

Calcular por cupom e passar para a view:
- `receita_gerada` = soma de `pedidos.valor_total` dos usos
- `descontos_dados` = soma de `pedidos.valor_desconto` dos usos
- `comissao_total` = soma de `(pedido.valor_desconto × comissao_percentual / 100)` de todos os usos
- `comissao_paga` = soma de `valor_pago` dos `cupom_pagamentos` do cupom
- `comissao_pendente` = `comissao_total - comissao_paga`

### `pagamentos(int $id)` → JSON

Retorna lista de pagamentos do cupom com seus usos cobertos (pedido id, data, valor comissão).

### `usosPendentes(int $id)` → JSON

Retorna usos sem `cupom_pagamento_id`, com: `id`, `pedido_id`, `created_at`, `valor_desconto` do pedido, `comissao_valor` calculado.

### `storePagamento(Request $request, int $id)`

Valida: `valor_pago` (required, numeric), `pago_em` (required, date), `observacao` (nullable), `uso_ids` (array de ids de `cupom_usos` pertencentes ao cupom e ainda pendentes).

Cria `CupomPagamento` e atualiza `cupom_usos.cupom_pagamento_id` dos ids selecionados.

### `updatePagamento` / `destroyPagamento`

Update: atualiza `valor_pago`, `observacao`, `pago_em` (não altera os usos vinculados).
Destroy: deleta o pagamento — `SET NULL` na FK libera os usos automaticamente.

---

## UI — View `/admin/cupons`

### Layout

- **Mobile (< lg):** 1 coluna de cards empilhados
- **Desktop (≥ lg):** grid 2 colunas de cards

### Estrutura do card por cupom

```
┌─────────────────────────────────────┐
│ FULANO2024          [● ATIVO]  [···] │  ← código + badge status + menu
│ Felipe Silva · felipe@email.com      │  ← parceiro (ou "—" se sem vínculo)
├─────────────────────────────────────┤
│ 10% desconto · Comissão: 100%        │
│ 23 usos · Mín. R$50 · Até 30/06     │
├─────────────────────────────────────┤
│ Receita gerada    R$ 4.320,00        │
│ Descontos dados   R$   432,00        │
│ Comissão devida   R$   432,00        │  ← pendente
│ Já pago           R$   200,00        │
├─────────────────────────────────────┤
│ [Ver histórico de pagamentos]        │
└─────────────────────────────────────┘
```

Menu `···`: Editar · Toggle ativo/inativo · Excluir · Registrar pagamento

Cupons sem parceiro vinculado (`user_id = null`) não exibem o bloco de métricas de comissão.

### Modal de edição de cupom (atualizar)

Adicionar campo `comissao_percentual` (número, 0–100) com label "Comissão ao parceiro (%)".

### Modal de histórico de pagamentos

Abre via "Ver histórico de pagamentos" ou "Registrar pagamento" no menu.

```
┌─────────────────────────────────────────────┐
│ Pagamentos · FULANO2024              [✕]     │
├─────────────────────────────────────────────┤
│ Pendente: R$ 232,00         [+ Registrar]   │
├─────────────────────────────────────────────┤
│ 10/05/2026  R$ 200,00  "Ref. abril/2026" [···] │
│ 3 usos cobertos                              │
│                                              │
│ 02/04/2026  R$ 150,00  —               [···] │
│ 5 usos cobertos                              │
└─────────────────────────────────────────────┘
```

Menu `···` por pagamento: Ver usos cobertos · Editar · Excluir

### Modal de registrar/editar pagamento

```
┌─────────────────────────────────────┐
│ Registrar Pagamento · FULANO2024    │
├─────────────────────────────────────┤
│ USOS PENDENTES                       │
│ ☑ Pedido #142 · 15/04 · R$ 48,00   │
│ ☑ Pedido #155 · 22/04 · R$ 32,00   │
│ ☐ Pedido #161 · 28/04 · R$ 48,00   │
│ [Selecionar todos]                   │
│ Total selecionado: R$ 80,00          │
├─────────────────────────────────────┤
│ Valor pago *    [80,00     ]         │
│ Data pagamento  [_________ ]         │
│ Observação      [_________ ]         │
├─────────────────────────────────────┤
│            [Cancelar]  [Salvar]      │
└─────────────────────────────────────┘
```

Selecionar usos preenche "valor pago" automaticamente mas o campo é editável. Modal de edição não mostra seleção de usos (só valor, data e observação).

---

## Comportamentos e Regras

- Cupom sem `user_id` não tem comissão exibida no card (não há parceiro para pagar)
- Deletar um `CupomPagamento` libera os usos via `SET NULL` — eles voltam à fila de pendentes automaticamente (comportamento do banco, sem lógica extra no controller)
- `comissao_percentual` aceita 0 a 100; 0 = cupom não gera comissão
- O "valor pago" pode diferir do total calculado (admin pode arredondar ou negociar)
- Usos de pedidos cancelados: incluídos no cálculo (simplificação — admin ajusta manualmente se necessário)

---

## Escopo fora deste spec

- Notificação ao parceiro sobre pagamento
- Relatório exportável de comissões
- Histórico de alterações de `comissao_percentual`
