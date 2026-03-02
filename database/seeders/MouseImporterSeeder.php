<?php

namespace Database\Seeders;

use App\Models\Categoria;
use App\Models\Produto;
use App\Models\ProdutoImagem;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class MouseImporterSeeder extends Seeder
{
    public function run(): void
    {
        $jsonPath = base_path('imports/produtos_final_v2.json');

        if (!file_exists($jsonPath)) {
            $this->command->error("Arquivo não encontrado: {$jsonPath}");
            $this->command->info("Execute primeiro: SERPER_API_KEY=xxx node imports/enricher_v2.js");
            return;
        }

        $produtos = json_decode(file_get_contents($jsonPath), true);

        if (!$produtos) {
            $this->command->error("Falha ao parsear JSON ou arquivo vazio.");
            return;
        }

        // Garantir que o diretório de imagens existe
        Storage::disk('public')->makeDirectory('produtos');

        $total = count($produtos);
        $this->command->info("Importando {$total} produtos...\n");

        foreach ($produtos as $i => $p) {
            $n = $i + 1;

            // a. Categoria
            $categoria = Categoria::firstOrCreate(
                ['nome' => $p['categoria']],
                [
                    'slug'  => Str::slug($p['categoria']),
                    'ativo' => true,
                ]
            );

            // b. Peso
            $peso = $this->parsePeso($p['specs']['peso'] ?? '0');

            // c. Upsert do produto
            $slug = Str::slug($p['nome']);
            $produto = Produto::updateOrCreate(
                ['slug' => $slug],
                [
                    'nome'            => $p['nome'],
                    'marca'           => $p['marca'] ?? null,
                    'descricao'       => $p['descricao'] ?? null,
                    'descricao_curta' => $p['descricao_curta'] ?? null,
                    'specs'           => $p['specs'] ?? null,
                    'preco'           => $p['preco'] ?: 0,
                    'estoque'         => $p['estoque'] ?: 0,
                    'peso'            => $peso,
                    'ativo'           => $p['ativo'] ?? true,
                    'categoria_id'    => $categoria->id,
                ]
            );

            // d. Limpar imagens antigas
            $produto->imagens()->delete();

            // e. Baixar e criar imagens
            $imgCount = 0;
            foreach ($p['imagens'] as $idx => $img) {
                $url = $this->fixProtocol($img['url'] ?? '');
                if (!$url) continue;

                $caminho = $this->downloadImagem($url, $slug, $idx);
                if ($caminho) {
                    ProdutoImagem::create([
                        'produto_id' => $produto->id,
                        'caminho'    => $caminho,
                        'capa'       => $img['capa'] ?? ($idx === 0),
                    ]);
                    $imgCount++;
                }
            }

            // f. Log
            $this->command->line("[{$n}/{$total}] {$p['nome']} — {$imgCount} imagem(ns)");
        }

        $this->command->newLine();
        $this->command->info('Importação concluída!');
        $this->command->info('Produtos: ' . Produto::count());
        $this->command->info('Imagens:  ' . ProdutoImagem::count());
    }

    private function parsePeso(string $peso): float
    {
        if (preg_match('/^([\d.]+)\s*g$/i', $peso, $m)) {
            return (float) $m[1] / 1000;
        }
        if (preg_match('/^([\d.]+)\s*kg$/i', $peso, $m)) {
            return (float) $m[1];
        }
        return 0;
    }

    private function fixProtocol(string $url): string
    {
        if (str_starts_with($url, '//')) {
            return 'https:' . $url;
        }
        return $url;
    }

    private function downloadImagem(string $url, string $slug, int $index): ?string
    {
        // Placeholder já está em storage — retornar como está
        if (str_starts_with($url, 'storage/')) {
            return $url;
        }

        $parsedPath = parse_url($url, PHP_URL_PATH) ?? '';
        $ext = pathinfo($parsedPath, PATHINFO_EXTENSION) ?: 'jpg';
        // Normalizar extensões compostas (ex: .webp?auto=format → webp)
        $ext = strtolower(preg_replace('/[^a-zA-Z0-9].*$/', '', $ext));
        if (!in_array($ext, ['jpg', 'jpeg', 'png', 'webp', 'gif'])) {
            $ext = 'jpg';
        }

        $filename = "produtos/{$slug}-{$index}.{$ext}";
        $destino  = storage_path("app/public/{$filename}");

        try {
            $response = Http::timeout(15)->get($url);
            if ($response->successful()) {
                file_put_contents($destino, $response->body());
                return $filename;
            }
        } catch (\Exception $e) {
            $this->command->warn("    Falha ao baixar imagem [{$url}]: " . $e->getMessage());
        }

        return null;
    }
}
