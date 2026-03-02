# JFX Tech Catalog Specialist — Memória Persistente

## Infraestrutura de Scraping (VPS)

- Node 20.x disponível no host; Playwright ^1.58.2 instalado em `/var/www/html/imports/node_modules`
- Rodar scripts com: `node --max-old-space-size=256 extrator.js`
- Output padrão: `/var/www/html/imports/produtos_prontos.json`
- Input padrão: `/var/www/html/imports/automacao-catalogar-mouses-jfx.txt` (uma linha com nome, uma linha em branco)

## Padrões de URL por Marca

| Marca | Padrão de URL | Tipo de Loja |
|---|---|---|
| Finalmouse | `finalmouse.com/products/{slug}` | Shopify |
| Razer | `razer.com/gaming-mice/{slug}` | Custom (spec tables) |
| ATK | `atk.gg/products/{slug}` | Shopify |
| Zowie | `zowie.benq.com/en-us/mouse/{model}.html` | Custom (BenQ) |
| Pulsar | `pulsargg.com/products/{slug}` | Shopify |
| Glorious | `gloriousgaming.com/products/{slug}` | Shopify |
| Endgame Gear | `endgamegear.gg/products/{slug}` | Shopify |

## Marcas Knowledge-Only (sem scraping de rede)

Corsair, Arye, Arbiter, Cobra, Orbital Works, Teevolution — usar apenas knowledge base.

## Limpeza de URLs de Imagem Shopify

- Remover sufixo de tamanho: `_800x.jpg` → `.jpg`, `_1200x600.webp` → `.webp`
- Remover query strings: `?width=N`, `?v=N`, `&w=N`, `&h=N`
- Padrão regex: `/_\d+x(\d+)?\.(jpg|jpeg|png|webp|gif)/gi` → `.$2`

## Seletores Shopify Confiáveis

- Descrição: `.product__description`, `.product-description`, `.product-single__description`
- OG image: `meta[property="og:image"]` (mais confiável entre lojas)
- JSON-LD: `script[type="application/ld+json"]` com `@type: Product`

## Seletores Razer

- Specs: `.specs-table`, `.product-specs`, `.tech-specs`, `[class*="spec"]`

## Seletores Zowie

- Specs: `.product-spec`, `.spec-list`, `.specifications`

## Produtos que Requerem Deep Search Google

- Qualquer produto com `-DW` no nome (linha Zowie wireless)
- `Deathadder v4 PRO`
- Query pattern: `{nome} specs sensor weight` → google.com/search?q=...&hl=en
- Snippets: `div.BNeawe`, `div.VwiC3b`, `.IsZvec`, `.lEBKkf`
- Links credíveis a seguir: rtings.com, techpowerup, notebookcheck, benq.com

## Slug Generation (espelha Str::slug Laravel)

1. `normalize('NFD')` → strip diacritics `[\u0300-\u036f]`
2. Remover `[` e `]` (ex: `[LAB]` → `LAB`)
3. `/` → `-`
4. Remover non-alphanumeric (exceto `-`)
5. Spaces → `-`; collapse `-+` → `-`; trim bordas

Exemplos verificados:
- `"Glorious Model D 2 PRO 4K/8K"` → `"glorious-model-d-2-pro-4k-8k"`
- `"Pulsar [LAB] X2F"` → `"pulsar-lab-x2f"`
- `"Deathadder v4 PRO"` → `"deathadder-v4-pro"`

## Atletas Assinados (copy descricao)

- `Pulsar ZywOo Small` → ZywOo (Mathieu Herbaut), Team Vitality, AWPer CS2
- `Pulsar TenZ` → TenZ (Tyson Ngo), Valorant
- `Pulsar X2 CrazyLight 8K Bruce Lee` → Bruce Lee edition

## Faixas de Sensor por Produto (referência rápida)

- PAW3950: ATK A9 Ultimate, ATK F1 V2 Ultimate, ATK X1 V2 Ultimate, Pulsar X3, Pulsar [LAB] X2F, Teevolution Terra PRO 8K, Glorious D2/O2 PRO
- PAW3395: ATK Duckbill, ATK F1/X1 Extreme, Arye RCC-1, Arbiter Akitsu, Pulsar X2 series, Pulsar Xlite, Pulsar JV-X, Pulsar ZywOo, Pulsar TenZ, Endgame XM2w 4K v2
- Focus Pro 2.0: Razer Deathadder v4 PRO, Razer Viper V3 PRO
- Focus Pro 30K: Razer Deathadder v3 Pro
- Finalmouse AI: toda linha ULX
- Zowie Pixart 3395: toda linha EC/FK-DW

## Convenções do Catálogo JFX Tech

- `categoria`: sempre `"Mouse"` para esta lista
- `preco` e `estoque`: sempre `0.00` e `0` (preenchido manualmente depois)
- `ativo`: `true`
- `cabo`: `null` para sem fio; string descritiva para com fio
- `source`: `"scraped"` | `"knowledge"` | `"deep_search"` | `"unknown"`
- Imagens: array vazio `[]` se nenhuma URL obtida (nunca placeholder inventado)

## Script Principal

- `/var/www/html/imports/extrator.js` — script Playwright para os 44 mouses
- Resumível: carrega `produtos_prontos.json` existente e pula produtos já processados
- Browser/context/page: instância única reutilizada por todos os produtos
- Delay: 800ms entre produtos (1200ms após Google search)
