<?php

namespace App\Http\Controllers;

use App\Models\Produto;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\Yaml\Yaml;

class SitemapController extends Controller
{
    public function index()
    {
        $xml = Cache::remember('sitemap.xml', 3600, function () {
            $produtos = Produto::where('ativo', true)
                ->select('slug', 'updated_at')
                ->get();

            $articles = $this->loadBlogArticles();

            return view('sitemap', compact('produtos', 'articles'))->render();
        });

        return response($xml, 200, ['Content-Type' => 'application/xml; charset=utf-8']);
    }

    private function loadBlogArticles(): array
    {
        $blogPath = resource_path('content/blog');
        $files = glob($blogPath . '/*.md') ?: [];

        return collect($files)->map(function ($file) {
            $content = file_get_contents($file);
            if (!str_starts_with($content, '---')) return null;
            $parts = explode('---', $content, 3);
            if (count($parts) < 3) return null;
            try {
                $fm = Yaml::parse(trim($parts[1]));
                $date = $fm['date'] ?? null;
                if ($date instanceof \DateTimeInterface) {
                    $date = $date->format('Y-m-d');
                } elseif (is_int($date)) {
                    $date = date('Y-m-d', $date);
                } else {
                    $date = $date ? (string) $date : now()->toDateString();
                }
                return [
                    'slug' => basename($file, '.md'),
                    'date' => $date,
                ];
            } catch (\Exception $e) {
                return null;
            }
        })->filter()->values()->toArray();
    }
}
