<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ItemPedido extends Model
{
    public const STATUS_PREPARACAO_PENDENTE = 'pendente';

    public const STATUS_PREPARACAO_CONFIRMADO = 'confirmado';

    public const STATUS_PREPARACAO_CANCELADO = 'cancelado';

    protected $table = 'itens_pedido';

    protected $fillable = [
        'pedido_id',
        'produto_id',
        'produto_variante_id',
        'opcoes_snapshot',
        'quantidade',
        'preco',
        'custo_unitario_declarado',
        'custo_declarado_em',
        'status_preparacao',
        'status_preparacao_em',
    ];

    protected $casts = [
        'quantidade' => 'integer',
        'preco' => 'decimal:2',
        'custo_unitario_declarado' => 'decimal:2',
        'custo_declarado_em' => 'datetime',
        'status_preparacao_em' => 'datetime',
        'opcoes_snapshot' => 'array',
    ];

    public static function preparationStatuses(): array
    {
        return [
            self::STATUS_PREPARACAO_PENDENTE,
            self::STATUS_PREPARACAO_CONFIRMADO,
            self::STATUS_PREPARACAO_CANCELADO,
        ];
    }

    public static function preparationStatusLabels(): array
    {
        return [
            self::STATUS_PREPARACAO_PENDENTE => 'Pendente',
            self::STATUS_PREPARACAO_CONFIRMADO => 'Comprado / vai entregar',
            self::STATUS_PREPARACAO_CANCELADO => 'Cancelado / estorno',
        ];
    }

    public static function preparationStatusLabel(?string $status): string
    {
        return self::preparationStatusLabels()[$status ?: self::STATUS_PREPARACAO_PENDENTE] ?? ucfirst((string) $status);
    }

    public function getStatusPreparacaoEfetivoAttribute(): string
    {
        return $this->status_preparacao ?: self::STATUS_PREPARACAO_PENDENTE;
    }

    public function getCanceladoNaPreparacaoAttribute(): bool
    {
        return $this->status_preparacao_efetivo === self::STATUS_PREPARACAO_CANCELADO;
    }

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
