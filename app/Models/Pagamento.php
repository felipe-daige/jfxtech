<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Pagamento extends Model
{
    protected $fillable = [
        'pedido_id',
        'metodo',
        'status',
        'valor',
        'data'
    ];

    protected $casts = [
        'valor' => 'decimal:2',
        'data' => 'datetime'
    ];

    /**
     * Relacionamento: Um pagamento pertence a um pedido
     */
    public function pedido(): BelongsTo
    {
        return $this->belongsTo(Pedido::class);
    }

    /**
     * Accessor para verificar se o pagamento foi aprovado
     */
    public function getAprovadoAttribute(): bool
    {
        return $this->status === 'pago';
    }

    /**
     * Accessor para verificar se o pagamento falhou
     */
    public function getFalhouAttribute(): bool
    {
        return $this->status === 'falhou';
    }

    /**
     * Accessor para verificar se o pagamento está pendente
     */
    public function getPendenteAttribute(): bool
    {
        return $this->status === 'pendente';
    }
}
