<?php echo '<?xml version="1.0" encoding="UTF-8"?>'; ?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">

    {{-- Homepage --}}
    <url>
        <loc>{{ url('/') }}</loc>
        <lastmod>{{ now()->toDateString() }}</lastmod>
        <priority>1.0</priority>
        <changefreq>weekly</changefreq>
    </url>

    {{-- Catálogo --}}
    <url>
        <loc>{{ url('/produtos') }}</loc>
        <lastmod>{{ $latestProductDate }}</lastmod>
        <priority>0.8</priority>
        <changefreq>daily</changefreq>
    </url>

    {{-- Blog index --}}
    <url>
        <loc>{{ url('/blog') }}</loc>
        <lastmod>{{ $latestArticleDate }}</lastmod>
        <priority>0.7</priority>
        <changefreq>weekly</changefreq>
    </url>

    {{-- Contato --}}
    <url>
        <loc>{{ url('/contato') }}</loc>
        <lastmod>2026-01-01</lastmod>
        <priority>0.5</priority>
        <changefreq>monthly</changefreq>
    </url>

    {{-- Produtos ativos --}}
    @foreach($produtos as $produto)
    <url>
        <loc>{{ url('/produto/' . $produto->slug) }}</loc>
        <lastmod>{{ $produto->updated_at->toDateString() }}</lastmod>
        <priority>0.8</priority>
        <changefreq>weekly</changefreq>
    </url>
    @endforeach

    {{-- Artigos do blog --}}
    @foreach($articles as $article)
    <url>
        <loc>{{ url('/blog/' . $article['slug']) }}</loc>
        <lastmod>{{ $article['date'] }}</lastmod>
        <priority>0.8</priority>
        <changefreq>monthly</changefreq>
    </url>
    @endforeach

</urlset>
