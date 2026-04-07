# Blog + SEO Estrutural — Design Spec

**Data:** 2026-04-07  
**Objetivo principal:** Criar sistema de blog em Markdown e melhorar a estrutura SEO do site JFXTech (meta descriptions, sitemap dinâmico, robots.txt, JSON-LD).

---

## 1. Blog (Markdown)

### Armazenamento

Artigos ficam em `resources/content/blog/{slug}.md`. O slug do arquivo é a URL do artigo (`/blog/{slug}`). Os arquivos são versionados no git — commitar = publicar.

**Front matter YAML obrigatório:**
```yaml
---
title: "Como escolher o melhor monitor gamer"
description: "Guia completo para escolher monitor 144hz, 1ms de resposta..."
date: 2026-04-10
tags: [monitor, guia]
image: storage/images/blog/monitor-guia.jpg
---
```

**Front matter opcional:**
```yaml
featured_product_slug: monitor-lg-27gp850  # exibe card do produto no final do artigo
```

### Parsing

- Biblioteca: `league/commonmark` (já presente no Laravel como dependência indireta — confirmar via `composer show`)
- Front matter: parse manual via `symfony/yaml` (já no Laravel) lendo o bloco `---` antes do conteúdo Markdown
- Se `league/commonmark` não estiver disponível diretamente, instalar via `composer require league/commonmark`

### Rotas

```php
Route::get('/blog', [BlogController::class, 'index'])->name('site.blog.index');
Route::get('/blog/{slug}', [BlogController::class, 'show'])->name('site.blog.show');
```

### Controller: `BlogController`

**`index()`:**
1. Lê todos os arquivos `.md` de `resources/content/blog/`
2. Extrai front matter de cada um (título, descrição, data, imagem, tags)
3. Ordena por `date` desc
4. Pagina 12 por página
5. Retorna `site.blog.index` com lista de artigos

**`show($slug)`:**
1. Lê `resources/content/blog/{slug}.md` — 404 se não existir
2. Extrai front matter e corpo Markdown
3. Converte corpo para HTML com `league/commonmark`
4. Se `featured_product_slug` presente, busca `Produto::where('slug', ...)->where('ativo', true)->first()`
5. Retorna `site.blog.show` com artigo e produto opcional

### Views

`resources/views/site/blog/index.blade.php`:
- Grid de cards de artigos (imagem, título, descrição, data, tags)
- Paginação
- Mesmo header/footer do site

`resources/views/site/blog/show.blade.php`:
- Título, imagem de capa, data, tags
- Conteúdo HTML renderizado do Markdown
- Card opcional do produto relacionado (reusa estilo visual existente)
- Meta tags SEO completas (title, description, OG, JSON-LD Article)

### Tratamento de erros

- Arquivo `.md` sem front matter obrigatório: lança exceção com mensagem clara no log, artigo ignorado na listagem
- `{slug}` inexistente: `abort(404)`
- `featured_product_slug` inválido ou produto inativo: ignora silenciosamente (não exibe card)

---

## 2. Meta Descriptions

Todas as páginas públicas recebem `<meta name="description" content="...">`.

| Página | Conteúdo |
|---|---|
| `site/index.blade.php` | Estática: "Hardware gamer premium — monitores, teclados e mouses. Produtos originais com garantia e envio rápido. JFXTech." |
| `site/produtos.blade.php` | Dinâmica: passa `$metaDescription` do controller com categoria ativa, ex: "Monitores gamer — catálogo completo com os melhores produtos. JFXTech." |
| `site/produto-detalhes.blade.php` | `$produto->descricao_curta ?? Str::limit(strip_tags($produto->descricao), 155)` (já usado no OG, reutilizar) |
| `site/blog/index.blade.php` | Estática: "Guias, reviews e dicas de hardware gamer. Aprenda a montar seu PC, comparar produtos e escolher o melhor setup. JFXTech." |
| `site/blog/show.blade.php` | Front matter `description` do artigo |
| `site/contato.blade.php` | Estática: "Entre em contato com a JFXTech. Atendimento rápido para dúvidas, trocas e suporte técnico." |

Páginas privadas (checkout, perfil, pedidos, favoritos) recebem `<meta name="robots" content="noindex, nofollow">` para evitar indexação.

---

## 3. Sitemap.xml Dinâmico

### Rota

```php
Route::get('/sitemap.xml', [SitemapController::class, 'index'])->name('sitemap');
```

### Controller: `SitemapController`

Gera XML com `response()->make($xml, 200, ['Content-Type' => 'application/xml'])`.

**URLs incluídas:**

| URL | Prioridade | Changefreq | Lastmod |
|---|---|---|---|
| `/` | 1.0 | weekly | hoje |
| `/produtos` | 0.8 | daily | hoje |
| `/produto/{slug}` (todos ativos) | 0.8 | weekly | `$produto->updated_at` |
| `/blog` | 0.7 | weekly | data do artigo mais recente |
| `/blog/{slug}` (todos os artigos) | 0.8 | monthly | `date` do front matter |
| `/contato` | 0.5 | monthly | — |

**Excluídas:** todas as rotas `/admin/*`, `/checkout`, `/carrinho`, `/perfil`, `/meus-pedidos`, `/favoritos`, `/login`, `/register`.

### Cache

Resultado do sitemap cacheado por 1 hora via `Cache::remember('sitemap', 3600, fn() => ...)`. Cache invalidado automaticamente quando um produto é salvo (via `PedidoObserver` existente ou novo observer de `Produto` se necessário).

---

## 4. robots.txt

Substituir o `public/robots.txt` atual:

```
User-agent: *
Disallow: /admin/
Disallow: /checkout
Disallow: /carrinho
Disallow: /perfil
Disallow: /meus-pedidos
Disallow: /favoritos
Disallow: /login
Disallow: /register
Disallow: /pedidos/

Sitemap: https://jfxtech.com.br/sitemap.xml
```

---

## 5. JSON-LD Estruturado

### Produto (`site/produto-detalhes.blade.php`)

```json
{
  "@context": "https://schema.org",
  "@type": "Product",
  "name": "{{ $produto->nome }}",
  "description": "{{ $produto->descricao_curta }}",
  "image": "{{ url('storage/' . $produto->imagemCapa->caminho) }}",
  "brand": { "@type": "Brand", "name": "{{ $produto->marca }}" },
  "offers": {
    "@type": "Offer",
    "priceCurrency": "BRL",
    "price": "{{ $produto->preco_com_desconto }}",
    "availability": "{{ $produto->em_estoque ? 'InStock' : 'OutOfStock' }}",
    "url": "{{ url()->current() }}"
  }
}
```

### Artigo (`site/blog/show.blade.php`)

```json
{
  "@context": "https://schema.org",
  "@type": "Article",
  "headline": "{{ $artigo['title'] }}",
  "description": "{{ $artigo['description'] }}",
  "image": "{{ url($artigo['image']) }}",
  "datePublished": "{{ $artigo['date'] }}",
  "author": { "@type": "Organization", "name": "JFXTech" },
  "publisher": { "@type": "Organization", "name": "JFXTech", "url": "{{ url('/') }}" }
}
```

### Organização (`site/index.blade.php`)

```json
{
  "@context": "https://schema.org",
  "@type": "Organization",
  "name": "JFXTech",
  "url": "{{ url('/') }}",
  "logo": "{{ url('storage/images/jfxtech-link-preiew-opt.jpg') }}",
  "description": "Hardware gamer premium — monitores, teclados e mouses."
}
```

---

## Arquivos a criar/modificar

### Novos
- `app/Http/Controllers/BlogController.php`
- `app/Http/Controllers/SitemapController.php`
- `resources/views/site/blog/index.blade.php`
- `resources/views/site/blog/show.blade.php`
- `resources/content/blog/` (diretório + artigo de exemplo)

### Modificados
- `routes/web.php` — novas rotas blog e sitemap
- `public/robots.txt` — atualizar
- `resources/views/site/index.blade.php` — meta description + JSON-LD Organization
- `resources/views/site/produtos.blade.php` — meta description dinâmica
- `resources/views/site/produto-detalhes.blade.php` — meta description + JSON-LD Product
- `resources/views/site/contato.blade.php` — meta description
- `app/Http/Controllers/SiteController.php` — passar `$metaDescription` para view de produtos

### Dependências
- Verificar se `league/commonmark` está disponível; instalar se necessário
- `symfony/yaml` já disponível no Laravel

---

## Fora do escopo

- Painel admin para gerenciar artigos (blog é gerenciado por git)
- Busca full-text de artigos
- Comentários
- RSS feed
- Analytics de artigos
