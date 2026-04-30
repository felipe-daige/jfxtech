<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class ProdutoVariante extends Model
{
    use HasFactory;

    protected $table = 'produto_variantes';

    protected $fillable = ['produto_id', 'valores', 'preco', 'custo_compra', 'frete_compra', 'estoque', 'ativo', 'descricao', 'specs'];

    protected $casts = [
        'valores' => 'array',
        'specs'   => 'array',
        'preco'   => 'decimal:2',
        'custo_compra' => 'decimal:2',
        'frete_compra' => 'decimal:2',
        'estoque' => 'integer',
        'ativo'   => 'boolean',
    ];

    public function produto(): BelongsTo
    {
        return $this->belongsTo(Produto::class);
    }

    public function imagens(): BelongsToMany
    {
        return $this->belongsToMany(
            ProdutoImagem::class,
            'produto_variante_imagens',
            'variante_id',
            'imagem_id'
        );
    }

    /**
     * Preço efetivo: próprio ou preco_com_desconto do produto pai (arredondado para 2dp).
     */
    public function getPrecoEfetivoAttribute(): float
    {
        if ($this->preco !== null) {
            return (float) $this->preco;
        }
        return round($this->produto->preco_com_desconto, 2);
    }

    /**
     * Estoque efetivo: se compartilhado, usa produto pai. Caso contrário, usa próprio.
     * Retorna 0 se estoque_compartilhado=false e estoque da variante é null.
     */
    public function getEstoqueEfetivoAttribute(): int
    {
        if ($this->produto->estoque_compartilhado) {
            return $this->produto->estoque;
        }
        return $this->estoque ?? 0;
    }

    public function getCustoEfetivoAttribute(): ?float
    {
        $custoCompra = $this->custo_compra !== null
            ? (float) $this->custo_compra
            : ($this->produto?->custo_compra !== null ? (float) $this->produto->custo_compra : null);

        if ($custoCompra === null) {
            return null;
        }

        $freteCompra = $this->frete_compra !== null
            ? (float) $this->frete_compra
            : (float) ($this->produto?->frete_compra ?? 0);

        return round($custoCompra + $freteCompra, 2);
    }

    /**
     * Descrição efetiva: própria ou do produto pai se não definida.
     */
    public function getDescricaoEfetivaAttribute(): ?string
    {
        return $this->descricao ?? $this->produto?->descricao;
    }

    /**
     * Specs efetivos: próprios ou do produto pai se não definidos.
     */
    public function getSpecsEfetivosAttribute(): ?array
    {
        return $this->specs ?? $this->produto?->specs;
    }
}
