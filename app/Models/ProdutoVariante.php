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

    protected $fillable = ['produto_id', 'valores', 'preco', 'estoque', 'ativo'];

    protected $casts = [
        'valores' => 'array',
        'preco'   => 'decimal:2',
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
}
