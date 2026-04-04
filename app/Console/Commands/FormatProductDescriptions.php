<?php

namespace App\Console\Commands;

use App\Models\Produto;
use App\Support\ProdutoDescricaoFormatter;
use Illuminate\Console\Command;

class FormatProductDescriptions extends Command
{
    protected $signature = 'products:format-descriptions {--dry-run : Exibe uma amostra sem salvar}';

    protected $description = 'Reescreve descrições de produtos no padrão HTML estruturado do storefront';

    public function handle(): int
    {
        $produtos = Produto::with('categoria')->orderBy('id')->get();

        if ($produtos->isEmpty()) {
            $this->warn('Nenhum produto encontrado.');

            return self::SUCCESS;
        }

        $updated = 0;
        $sampleShown = 0;

        foreach ($produtos as $produto) {
            $formatted = ProdutoDescricaoFormatter::formatProduto($produto);

            if ($formatted === '') {
                $this->warn("Produto {$produto->id} sem descrição utilizável: {$produto->nome}");
                continue;
            }

            if ($this->option('dry-run') && $sampleShown < 3) {
                $this->line('');
                $this->info("{$produto->id} | {$produto->nome}");
                $this->line($formatted);
                $sampleShown++;
            }

            if (!$this->option('dry-run') && $produto->descricao !== $formatted) {
                $produto->forceFill(['descricao' => $formatted])->save();
                $updated++;
            } elseif ($this->option('dry-run') && $produto->descricao !== $formatted) {
                $updated++;
            }
        }

        $message = $this->option('dry-run')
            ? "Dry run concluído. {$updated} produtos seriam atualizados."
            : "Formatação concluída. {$updated} produtos atualizados.";

        $this->info($message);

        return self::SUCCESS;
    }
}
