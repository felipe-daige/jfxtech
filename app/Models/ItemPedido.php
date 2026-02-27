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
        'quantidade',
        'preco'
    ];

    protected $casts = [
        'quantidade' => 'integer',
        'preco' => 'decimal:2'
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
     * Accessor para calcular o subtotal do item
     */
    public function getSubtotalAttribute(): float
    {
        return $this->quantidade * $this->preco;
    }
}
