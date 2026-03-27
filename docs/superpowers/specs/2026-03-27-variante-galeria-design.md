# Galeria de Imagens por Variante — Design Spec

**Data:** 2026-03-27

## Objetivo

Na página de detalhes do produto, ao clicar em uma opção de variante (ex: "Vermelho"), a galeria de imagens deve filtrar imediatamente para mostrar apenas as fotos associadas às variantes que contêm aquele valor — sem esperar que todos os grupos sejam selecionados.

## Contexto

A infraestrutura já existe:
- Tabela `produto_variante_imagens` (migrada)
- `ProdutoVariante::imagens()` — BelongsToMany
- `SiteController` eager-loads `variantesAtivas.imagens` e inclui `imagem_ids` no JSON
- `produto-detalhes.js` já tem lógica de filtro, mas só dispara após todos os grupos selecionados

## Escopo

Mudança em um único arquivo: `public/js/produto-detalhes.js`

Sem alterações em backend, migrations, modelos ou views.

## Lógica de Filtro

A cada clique em `.opcao-btn`:

1. Atualiza `selecao[grupoId] = valorId`
2. Coleta os valores selecionados até o momento: `Object.values(selecao)` como array de inteiros
3. Filtra `variantes` — mantém as que contêm **todos** os valores selecionados em seu array `valores`
4. Faz a união dos `imagem_ids` das variantes candidatas
5. Aplica à galeria:
   - Se a união tiver elementos: mostra só os thumbnails cujo `data-imagem-id` está na união, oculta os demais; a imagem principal passa a ser o primeiro thumbnail visível
   - Se a união for vazia (nenhuma foto associada): mostra todos os thumbnails (fallback)
6. Ao trocar seleção em qualquer grupo: restaura todos os thumbnails antes de reaplicar o filtro (comportamento já existente)

A lógica de atualização de preço, estoque e botão permanece inalterada — só roda quando todos os grupos estão preenchidos.

## Comportamento Esperado

| Seleção do usuário | Galeria exibe |
|---|---|
| Nenhuma opção | Todas as fotos do produto |
| Vermelho (1 de 2 grupos) | União das fotos de todas as variantes vermelhas |
| Vermelho + P (seleção completa) | Fotos da variante exata Vermelho+P |
| Variante sem fotos associadas | Todas as fotos (fallback) |

## Fora do Escopo

- Associação de imagens a valores individuais de opção (requereria nova tabela e UI no admin)
- Animações de transição na galeria
- Pré-carregamento de imagens
