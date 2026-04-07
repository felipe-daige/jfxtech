# Blog — 5 Novos Artigos: Design Spec

**Data:** 2026-04-07  
**Objetivo:** Criar 5 artigos de blog completos, com imagens de capa, otimizados para SEO e coerentes com a identidade da JFXTech (hardware gamer premium, tom técnico e acessível).

---

## Estratégia SEO

**Abordagem:** Mix de guias de compra + comparativo de nicho + hub de setup (Opção C aprovada).

Cada artigo cobre uma lacuna de keyword diferente:
- Guias de compra → intenção transacional alta ("como escolher X")
- Comparativo mecânico vs membrana → keyword de altíssimo volume
- Hub setup completo → cauda longa ampla, linkável, âncora do blog

**Link interno:** O artigo de setup linka para os outros 4 artigos do blog + para os produtos do catálogo de cada categoria. Os guias de compra linkam para a categoria correspondente no catálogo.

---

## Padrões de Conteúdo

### Tom e Voz
- Técnico mas acessível: explica conceitos sem jargão desnecessário
- Direto, sem floreios — o leitor quer decidir uma compra
- Usa tabelas comparativas, listas e exemplos concretos (igual ao artigo existente)

### Estrutura de cada artigo
- 600–900 palavras (otimizado para SEO sem ser raso)
- 4–6 seções H2
- Pelo menos 1 tabela comparativa por artigo
- Conclusão com recomendação clara e CTA para o catálogo

### Front matter padrão
```yaml
---
title: "Título do Artigo"
description: "Meta description de 150–160 chars com keyword principal"
date: 2026-04-07
tags: [tag1, tag2, tag3]
image: images/blog/nome-da-imagem.jpg
---
```

---

## Artigos

### Artigo 1 — Como Escolher o Teclado Gamer Ideal

| Campo | Valor |
|-------|-------|
| **Slug** | `como-escolher-teclado-gamer` |
| **Keyword** | "teclado gamer como escolher" |
| **Tags** | teclado, guia, hardware |
| **Imagem** | `images/blog/teclado-gamer-guia.jpg` (Pexels #2115257, John Petalcurin) |
| **Description** | "Guia completo para escolher o teclado gamer ideal: switches, layout, conectividade e iluminação RGB. Encontre o teclado perfeito para seu setup." |

**Outline:**
1. Por que o teclado importa para o desempenho?
2. Tipos de switch: linear, tátil e clicky
3. Layout: full-size, TKL, 75%, 60%
4. Conectividade: USB, wireless, Bluetooth
5. Iluminação e extras (RGB, macro, palmrest)
6. Tabela resumo: qual teclado para qual perfil
7. Conclusão + CTA → catálogo de teclados

---

### Artigo 2 — Teclado Mecânico vs Membrana: Qual Comprar?

| Campo | Valor |
|-------|-------|
| **Slug** | `teclado-mecanico-vs-membrana` |
| **Keyword** | "teclado mecânico vs membrana" |
| **Tags** | teclado, comparativo, mecânico |
| **Imagem** | `images/blog/teclado-mecanico-vs-membrana.jpg` (Pexels #671629, Marcellino Andrian) |
| **Description** | "Teclado mecânico ou de membrana? Comparamos durabilidade, tactilidade, preço e desempenho para ajudar você a escolher o melhor para jogos." |

**Outline:**
1. A grande dúvida do mercado
2. Como funciona um teclado de membrana
3. Como funciona um teclado mecânico
4. Tabela comparativa: mecânico vs membrana (durabilidade, feel, preço, ruído, customização)
5. Para quem vale o mecânico?
6. Para quem o membrana ainda serve?
7. Conclusão + CTA → catálogo de teclados

---

### Artigo 3 — Como Escolher o Mouse Gamer: DPI, Sensor e Formato

| Campo | Valor |
|-------|-------|
| **Slug** | `como-escolher-mouse-gamer` |
| **Keyword** | "mouse gamer como escolher" |
| **Tags** | mouse, guia, hardware |
| **Imagem** | `images/blog/mouse-gamer-guia.jpg` (Pexels #1486294, FOX) |
| **Description** | "Saiba como escolher o mouse gamer ideal: DPI, tipo de sensor, formato ergonômico e peso. Guia completo para jogadores de todos os estilos." |

**Outline:**
1. O mouse certo muda o jogo
2. DPI: o que é e qual usar (tabela: FPS, MOBA, casual)
3. Sensor óptico vs laser: qual é melhor?
4. Formatos: palm, claw, fingertip grip
5. Peso: leve vs pesado — para cada estilo de jogo
6. Tabela de perfis: qual mouse para qual jogo
7. Conclusão + CTA → catálogo de mouses

---

### Artigo 4 — Headset Gamer: Surround 7.1 vs Estéreo Vale a Pena?

| Campo | Valor |
|-------|-------|
| **Slug** | `headset-gamer-surround-vs-estereo` |
| **Keyword** | "headset gamer 7.1 vs estéreo" |
| **Tags** | fone, headset, comparativo |
| **Imagem** | `images/blog/headset-gamer-surround.jpg` (Pexels #9072279) |
| **Description** | "Headset gamer com surround 7.1 ou estéreo? Explicamos a diferença real, quando vale a pena e o que considerar antes de comprar seu próximo fone." |

**Outline:**
1. Áudio faz diferença nos jogos?
2. O que é áudio estéreo (e por que funciona bem)
3. O que é surround 7.1 virtual (e quando ajuda de verdade)
4. Tabela comparativa: 7.1 vs estéreo
5. Open-back vs closed-back: o que ninguém conta
6. Microfone: cardioide vs omnidirecional
7. Conclusão + CTA → catálogo de fones

---

### Artigo 5 — Como Montar Setup Gamer Completo em 2026

| Campo | Valor |
|-------|-------|
| **Slug** | `como-montar-setup-gamer-completo-2026` |
| **Keyword** | "montar setup gamer" |
| **Tags** | setup, guia, hardware, monitor, teclado, mouse |
| **Imagem** | `images/blog/setup-gamer-completo.jpg` (Pexels #30469972) |
| **Description** | "Guia completo para montar o setup gamer em 2026: do monitor ao headset, orçamentos por nível e dicas de ergonomia para jogar mais e cansar menos." |

**Outline:**
1. Por onde começar: prioridades por orçamento
2. Monitor: o item mais impactante (link → artigo monitor)
3. Teclado: mecânico é o padrão mínimo (link → artigo teclado guia)
4. Mouse: sensor e DPI certos para o seu jogo (link → artigo mouse)
5. Headset: imersão e comunicação (link → artigo headset)
6. Extras: mousepad, cadeira, iluminação
7. Tabela de orçamentos: setup básico / intermediário / pro
8. Conclusão + CTA → catálogo completo JFXTech

---

## Imagens — Referências e Créditos

| Artigo | Arquivo | Fonte | Dimensões |
|--------|---------|-------|-----------|
| Teclado guia | `teclado-gamer-guia.jpg` | Pexels #2115257 (John Petalcurin) | 1104×750 |
| Teclado mecânico vs membrana | `teclado-mecanico-vs-membrana.jpg` | Pexels #671629 (Marcellino Andrian) | 1125×750 |
| Mouse gamer | `mouse-gamer-guia.jpg` | Pexels #1486294 (FOX) | 1260×726 |
| Headset gamer | `headset-gamer-surround.jpg` | Pexels #9072279 | 1125×750 |
| Setup completo | `setup-gamer-completo.jpg` | Pexels #30469972 | 1125×750 |

Todas as imagens são da Pexels com licença gratuita para uso comercial, sem necessidade de atribuição. Arquivos já baixados em `public/images/blog/`.

---

## Implementação

- Criar 5 arquivos `.md` em `resources/content/blog/`
- Cada artigo segue o front matter padrão acima
- Imagens referenciadas como `images/blog/{nome}.jpg` (servidas via `public/`)
- Não requer alteração de código — apenas conteúdo
- Após criação, limpar cache de views

---

## Fora do Escopo

- Não criar novas páginas ou rotas (o sistema de blog já existe)
- Não criar componente de "artigos relacionados" (melhoria futura)
- Não traduzir para outros idiomas
