<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ItemPedido extends Model
{
    protected $table = 'itens_pedido';
    
    protected $fillable = [
        'pedido_id',
        'produto_id',
        'produto_variante_id',
        'opcoes_snapshot',
        'quantidade',
        'preco'
    ];

    protected $casts = [
        'quantidade' => 'integer',
        'preco' => 'decimal:2',
        'opcoes_snapshot' => 'array',
    ];

    /**
     * Relacionamento: Um item pertence a um pedido
     */
    public function pedido(): BelongsTo
    {
        return $this->belongsTo(Pedido::class);
    }

    /**
     * Relacionamento: Um item pertence a um produto
     */
    public function produto(): BelongsTo
    {
        return $this->belongsTo(Produto::class);
    }

    /**
     * Relacionamento: Um item pode pertencer a uma variante de produto
     */
    public function produtoVariante(): BelongsTo
    {
        return $this->belongsTo(ProdutoVariante::class, 'produto_variante_id');
    }

    /**
     * Accessor para calcular o subtotal do item
     */
    public function getSubtotalAttribute(): float
    {
        return $this->quantidade * $this->preco;
    }
}
