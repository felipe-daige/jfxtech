# Spec: View Dedicada de Reembolso / Garantia de Fábrica

## Contexto

Clientes que desejam solicitar reembolso ou acionar garantia precisam de uma página dedicada, didática e tranquilizadora. O fluxo é manual — o contato acontece via WhatsApp — mas a página deve educar o cliente sobre seus direitos (Art. 49 CDC), mostrar o resumo do pedido, e guiar cada passo do processo. Nenhuma automação de reembolso é feita pelo sistema.

---

## URL e Acesso

- **Rota:** `GET /pedidos/{pedido}/reembolso` → named `site.pedidos.reembolso`
- **Acesso:** Mesmo controle de `show` — usa `CheckoutOrderService::canAccessOrder()`. Clientes não autenticados com `guest_token` válido também acessam.
- **Entrada:** Botão "Solicitar Reembolso" ou "Garantia de Fábrica" na view `show.blade.php` (já implementado) leva para esta URL.

---

## Lógica de Prazo (CDC Art. 49)

Baseada em `$pedido->updated_at` quando `status = entregue` (proxy para data de entrega):

| Condição | Modo |
|---|---|
| Status `pago` / `processando` / `enviado` | Reembolso (produto não recebido) |
| Status `entregue` + `updated_at` ≤ 7 dias | Reembolso (dentro do prazo legal) |
| Status `entregue` + `updated_at` > 7 dias | Garantia de Fábrica |
| Status `cancelado` / `pendente` / `carrinho` | Redireciona para `site.pedidos.show` |

---

## Estrutura da Página

### Seção 1 — Hero com breadcrumb
- Breadcrumb: Início → Meus Pedidos → Pedido #ID → Reembolso/Garantia
- Título dinâmico: "Solicitar Reembolso" ou "Garantia de Fábrica"
- Subtítulo explicativo conforme o modo

### Seção 2 — Status do Prazo (card de destaque)
**Modo reembolso (produto não recebido):**
- Faixa verde: "Você está dentro do prazo de reembolso"
- Texto: "Compras online têm direito a reembolso integral em até 7 dias após o recebimento (Art. 49, CDC)."
- Linha do tempo: `Pedido realizado → Aguardando recebimento → [Prazo inicia na entrega]`

**Modo reembolso (entregue ≤ 7 dias):**
- Faixa verde: "Você está dentro do prazo de reembolso"
- Texto: "Você tem até [data = updated_at + 7 dias] para solicitar reembolso (Art. 49, CDC)."
- Linha do tempo: `Entregue em DD/MM → Hoje → Prazo até DD/MM`
- Dias restantes em destaque: "X dias restantes"

**Modo garantia (entregue > 7 dias):**
- Faixa cinza: "Prazo de reembolso encerrado"
- Texto: "O prazo de 7 dias para arrependimento encerrou em [data]. Produtos com defeito têm cobertura pela Garantia de Fábrica."
- Linha do tempo: `Entregue em DD/MM → Prazo encerrou em DD/MM → Hoje`

### Seção 3 — Resumo do Pedido
- Lista de itens: foto (miniatura 64×64), nome do produto, quantidade, preço unitário
- Linha de subtotal, frete, desconto (se houver) e total pago
- Número do pedido e data de criação

### Seção 4 — Como Funciona (3 passos numerados)
**Modo reembolso:**
1. **Entre em contato** — Clique no botão abaixo e envie a mensagem pelo WhatsApp
2. **Análise da solicitação** — Nossa equipe analisa em até 5 a 10 dias úteis
3. **Reembolso processado** — O valor é devolvido pelo mesmo método de pagamento utilizado

**Modo garantia:**
1. **Entre em contato** — Clique no botão abaixo e envie a mensagem pelo WhatsApp
2. **Avaliação do defeito** — Nossa equipe orienta sobre envio ou retirada do produto
3. **Resolução** — Reparo, substituição ou reembolso conforme análise técnica

### Seção 5 — Ação (CTA)
- Botão WhatsApp grande: `wa.me/5567999844366?text=...`
- Mensagem pré-preenchida inclui: número do pedido, modo (reembolso/garantia), data de entrega se aplicável
- Aviso abaixo do botão: "Nosso atendimento responde pelo WhatsApp. Tenha o número do pedido em mãos."
- Link secundário: "← Voltar ao pedido"

---

## Arquivos a Criar/Modificar

| Arquivo | Ação |
|---|---|
| `resources/views/site/pedidos/reembolso.blade.php` | Criar — view dedicada |
| `app/Http/Controllers/PedidoController.php` | Adicionar método `reembolso()` |
| `routes/web.php` | Adicionar rota GET |

---

## Mensagens WhatsApp Pré-preenchidas

**Reembolso (não recebido):**
> "Olá, gostaria de solicitar o reembolso do pedido #ID (ainda não recebi o produto)."

**Reembolso (entregue ≤ 7 dias):**
> "Olá, gostaria de solicitar o reembolso do pedido #ID, recebido em DD/MM/YYYY."

**Garantia de Fábrica:**
> "Olá, tenho um produto com problema no pedido #ID. Gostaria de acionar a garantia de fábrica."

---

## Verificação

1. Acessar `/pedidos/{id}/reembolso` com pedido `enviado` → página modo reembolso (sem produto recebido)
2. Acessar com pedido `entregue` atualizado há ≤ 7 dias → modo reembolso com linha do tempo e dias restantes
3. Acessar com pedido `entregue` atualizado há > 7 dias → modo garantia com prazo encerrado
4. Acessar com pedido `cancelado` → redireciona para `show`
5. Botão WhatsApp abre `wa.me/5567999844366` com mensagem correta para cada modo
6. Guest com token válido acessa normalmente; sem token redireciona para login
7. Resumo do pedido exibe itens, fotos e totais corretamente
