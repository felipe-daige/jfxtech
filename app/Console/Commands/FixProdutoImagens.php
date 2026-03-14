<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use App\Models\Produto;
use App\Models\ProdutoImagem;

class FixProdutoImagens extends Command
{
    protected $signature   = 'produtos:fix-imagens';
    protected $description = 'Corrige imagens dos produtos usando arquivos já baixados pelo scraper';

    public function handle(): int
    {
        $json        = json_decode(file_get_contents(base_path('imports/produtos_prontos.json')), true);
        $imgsBaseDir = base_path('imports/imagens_produtos');
        $extensions  = ['jpg', 'jpeg', 'png', 'webp', 'gif'];

        foreach ($json as $p) {
            $slug    = substr(Str::slug($p['nome']), 0, 80);
            $produto = Produto::where('slug', $slug)->first();

            if (!$produto) {
                $this->warn("Produto não encontrado no banco: {$slug}");
                continue;
            }

            $srcDir = "{$imgsBaseDir}/{$slug}";
            if (!is_dir($srcDir)) {
                $this->warn("Pasta local não encontrada: {$srcDir}");
                continue;
            }

            // Coletar arquivos locais ordenados por índice
            $files = [];
            foreach (glob("{$srcDir}/*") as $file) {
                $basename = pathinfo($file, PATHINFO_FILENAME); // "0", "1", "2" ...
                $ext      = strtolower(pathinfo($file, PATHINFO_EXTENSION));
                if (is_numeric($basename) && in_array($ext, $extensions)) {
                    $files[(int)$basename] = $file;
                }
            }
            ksort($files);

            if (empty($files)) {
                $this->warn("Nenhuma imagem local para: {$slug}");
                continue;
            }

            // Limpar registros antigos e arquivos em storage
            foreach ($produto->imagens as $img) {
                Storage::disk('public')->delete($img->caminho);
            }
            $produto->imagens()->delete();

            // Copiar e criar registros novos
            $ordem = 0;
            foreach ($files as $index => $srcPath) {
                $ext      = strtolower(pathinfo($srcPath, PATHINFO_EXTENSION));
                $caminho  = "produtos/{$slug}-{$index}.{$ext}";
                $destPath = storage_path("app/public/{$caminho}");

                @mkdir(dirname($destPath), 0755, true);
                if (!copy($srcPath, $destPath)) {
                    $this->warn("Falha ao copiar: {$srcPath}");
                    continue;
                }

                ProdutoImagem::create([
                    'produto_id' => $produto->id,
                    'caminho'    => $caminho,
                    'capa'       => ($ordem === 0),
                    'ordem'      => $ordem,
                ]);
                $ordem++;
            }

            $this->info("OK [{$ordem} imagens] {$slug}");
        }

        $this->info('Concluído.');
        return 0;
    }
}
