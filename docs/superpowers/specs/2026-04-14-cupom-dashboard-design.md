# Design Spec: Portal do Parceiro — Dashboard /cupom

**Data:** 2026-04-14  
**Status:** Aprovado  

---

## Contexto

A rota `/cupom` é o portal exclusivo para colaboradores/parceiros da loja. O acesso exige autenticação e `coupon_portal_enabled = true` no usuário. Atualmente a página exibe 3 cards de stats simples, uma lista de cupons em card-format e uma tabela de últimas vendas com poucas colunas. O objetivo é transformar essa página num dashboard profissional com métricas úteis, duas planilhas detalhadas e efeitos visuais em CSS/JS puro (sem bibliotecas externas de gráfico).

---

## Estrutura da Página

### 1. Header

- Título "Portal do Parceiro" + subtítulo com nome do usuário
- Link "← Voltar ao perfil"
- Badge de tier atual (ex: `Tier 3 — 7%`) com cor destaque

---

### 2. KPIs — Grid 3×2 (topo)

Seis cards em grid responsivo (3 colunas desktop, 2 tablet, 1 mobile):

| Card | Cálculo | Fonte |
|---|---|---|
| Cupons vinculados | `count($cupons)` | `$cupons` |
| Vendas pagas | `$progress['total_sales']` | `progressForUser()` |
| Comissão atual | `$progress['current_rate']%` | `progressForUser()` |
| Comissão acumulada | `SUM((pedido.total - pedido.valor_desconto) × taxa)` | query em `recentSales` + taxa atual |
| Total em vendas geradas | `SUM(pedido.total - pedido.valor_desconto)` | `recentSales` |
| Média por pedido | total vendas ÷ count vendas | calculado no controller |

> **Cálculo de comissão por venda:** `valor_liquido = total - valor_desconto`, `comissao = valor_liquido × (taxa / 100)`  
> A taxa usada é a **atual** (`current_rate`) aplicada uniformemente a todas as vendas históricas (modelo simplificado).

---

### 3. Banner de Progressão (abaixo dos KPIs)

Barra de progresso horizontal em CSS puro:
- Mostra posição no tier atual
- Label: `"32 / 60 vendas — faltam 28 para 8%"` ou `"Tier máximo atingido! 🏆"`
- A barra é preenchida proporcionalmente dentro do intervalo do tier (`current - min) / (max - min`)

---

### 4. Painel Principal — Duas Colunas

**Desktop:** 65% esquerda / 35% direita. **Mobile:** empilha (vendas → cupons → progressão).

#### 4a. Coluna Esquerda — Planilha de Vendas

Tabela estilo planilha com:
- Cabeçalho fixo (sticky thead) com ordenação por coluna (clique asc/desc via JS puro)
- Linhas zebradas (CSS `:nth-child`)
- Linha de **totais** fixada no rodapé

**Colunas:**

| # | Pedido | Cupom | Valor Bruto | Desconto | Valor Líquido | Comissão | Data |
|---|---|---|---|---|---|---|---|

- Paginação: 20 linhas por página, controles Anterior/Próximo + indicador "Página X de Y"
- Tooltip em "Comissão": mostra `"taxa atual: 7%"`
- Todas as colunas numéricas formatadas como `R$ X.XXX,XX`
- Coluna Data formatada `DD/MM/AAAA`

#### 4b. Coluna Direita — Bloco 1: Planilha de Cupons

Tabela compacta com as seguintes colunas:

| Código | Tipo | Valor | Usos | Validade | Status |
|---|---|---|---|---|---|

- Tipo exibido como badge: `"% desconto"` ou `"R$ fixo"`
- Status: badge verde `"Ativo"` ou cinza `"Expirado"` (baseado em `ativo` e `valido_ate`)
- Sem paginação (parceiros terão poucos cupons)

#### 4c. Coluna Direita — Bloco 2: Tabela de Progressão de Tiers

Tabela de 4 linhas (uma por tier), tier atual com fundo preto e texto branco:

| Faixa | Vendas | Comissão |
|---|---|---|
| Tier 1 | 0–14 | 5% |
| **Tier 2** | **15–29** | **6%** ← atual |
| Tier 3 | 30–59 | 7% |
| Tier 4 | 60+ | 8% |

---

## Dados & Controller

O método `SiteController@cupom` precisa passar variáveis adicionais para a view:

### Variáveis novas a calcular no controller:

```php
// Comissão acumulada e total vendas geradas
$taxa = $progress['current_rate'] / 100;
$totalLiquido = $recentSales->sum(fn($p) => $p->total - $p->valor_desconto);
$comissaoAcumulada = $totalLiquido * $taxa;
$mediaPorPedido = $recentSales->count() > 0 ? $totalLiquido / $recentSales->count() : 0;

// Passar TODAS as vendas (não só 10) para paginação no front
// Recentar query: remover limit, incluir todos os pedidos pagos com cupons do user
```

### Campos necessários nos pedidos:
- `id`, `cupom_codigo`, `total`, `valor_desconto`, `created_at`
- `valor_liquido = total - valor_desconto` (calculado na view ou no controller)

---

## Efeitos Visuais (CSS/JS Puro)

- **Barra de progressão:** `<div>` com `width` calculado em PHP, transição CSS `transition: width 0.8s ease`
- **Ordenação de tabela:** JS puro — listener no `<th>`, sort do DOM por `dataset.value`
- **Paginação:** JS puro — controla visibilidade das `<tr>` por página
- **Tooltips:** CSS puro via `data-tooltip` + `::after` pseudo-elemento
- **Contador animado nos KPIs:** JS puro — `requestAnimationFrame` incrementa valor de 0 até o final em ~600ms
- **Linhas zebradas:** CSS `:nth-child(odd)` no tbody
- **Sticky thead:** CSS `position: sticky; top: 0`

---

## Arquivos a Modificar

| Arquivo | Mudança |
|---|---|
| `resources/views/site/cupom.blade.php` | Reescrever view completa |
| `app/Http/Controllers/SiteController.php` | Adicionar métricas: `totalLiquido`, `comissaoAcumulada`, `mediaPorPedido`; remover limit da query de vendas |

---

## Verificação

```bash
# Limpar cache e testar
docker exec laravel-app php artisan view:clear
docker exec laravel-app php artisan cache:clear

# Acessar como parceiro:
# https://jfxtech.com.br/cupom (usuário com coupon_portal_enabled=true e cupons vinculados)

# Testar:
# - KPIs exibem valores corretos
# - Barra de progressão proporcional ao tier
# - Ordenação de colunas funciona
# - Paginação navega corretamente
# - Totais no rodapé da planilha de vendas batem com KPIs
# - Responsividade mobile (empilha corretamente)
```
