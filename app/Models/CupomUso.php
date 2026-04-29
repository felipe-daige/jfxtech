<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CupomUso extends Model
{
    protected $table = 'cupom_usos';

    public $timestamps = false;

    protected $fillable = ['cupom_id', 'user_id', 'pedido_id', 'cupom_pagamento_id'];

    protected $casts = [
        'created_at' => 'datetime',
    ];

    public function cupom(): BelongsTo
    {
        return $this->belongsTo(Cupom::class);
    }

    public function pedido(): BelongsTo
    {
        return $this->belongsTo(Pedido::class);
    }

    public function pagamento(): BelongsTo
    {
        return $this->belongsTo(CupomPagamento::class, 'cupom_pagamento_id');
    }
}
