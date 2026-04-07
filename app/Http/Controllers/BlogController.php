<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use League\CommonMark\CommonMarkConverter;
use Symfony\Component\Yaml\Yaml;
use Illuminate\Support\Facades\Log;

class BlogController extends Controller
{
    private string $blogPath;

    public function __construct()
    {
        $this->blogPath = resource_path('content/blog');
    }

    public function index()
    {
        $articles = collect(glob($this->blogPath . '/*.md'))
            ->map(fn($file) => $this->parseFrontMatter($file))
            ->filter()
            ->sortByDesc('date')
            ->values();

        $perPage = 12;
        $page = max(1, (int) request('page', 1));
        $total = $articles->count();
        $articles = $articles->forPage($page, $perPage);

        return view('site.blog.index', compact('articles', 'total', 'page', 'perPage'));
    }

    public function show(string $slug)
    {
        // Previne path traversal
        $slug = basename($slug);
        $path = $this->blogPath . '/' . $slug . '.md';

        if (!file_exists($path)) {
            abort(404);
        }

        $frontMatter = $this->parseFrontMatter($path);

        if (!$frontMatter) {
            abort(404);
        }

        $rawContent = file_get_contents($path);
        $parts = explode('---', $rawContent, 3);
        $markdown = trim($parts[2] ?? '');

        $converter = new CommonMarkConverter(['html_input' => 'strip', 'allow_unsafe_links' => false]);
        $html = $converter->convert($markdown)->getContent();

        $article = array_merge($frontMatter, ['content' => $html]);

        $featuredProduct = null;
        if (!empty($article['featured_product_slug'])) {
            $featuredProduct = \App\Models\Produto::where('slug', $article['featured_product_slug'])
                ->where('ativo', true)
                ->first();
        }

        return view('site.blog.show', compact('article', 'featuredProduct'));
    }

    private function parseFrontMatter(string $path): ?array
    {
        $content = file_get_contents($path);

        if (!str_starts_with($content, '---')) {
            Log::warning("Blog: front matter ausente em {$path}");
            return null;
        }

        $parts = explode('---', $content, 3);

        if (count($parts) < 3) {
            Log::warning("Blog: front matter malformado em {$path}");
            return null;
        }

        try {
            $fm = Yaml::parse(trim($parts[1]));
        } catch (\Exception $e) {
            Log::warning("Blog: erro YAML em {$path} — {$e->getMessage()}");
            return null;
        }

        if (empty($fm['title']) || empty($fm['description']) || empty($fm['date'])) {
            Log::warning("Blog: campos obrigatórios ausentes em {$path}");
            return null;
        }

        return array_merge($fm, [
            'slug' => basename($path, '.md'),
            'date' => $this->normalizeDate($fm['date']),
            'tags' => $fm['tags'] ?? [],
            'image' => $fm['image'] ?? 'storage/images/jfxtech-link-preiew-opt.jpg',
        ]);
    }

    private function normalizeDate(mixed $date): string
    {
        if ($date instanceof \DateTimeInterface) {
            return $date->format('Y-m-d');
        }
        if (is_int($date)) {
            return date('Y-m-d', $date);
        }
        return (string) $date;
    }
}
