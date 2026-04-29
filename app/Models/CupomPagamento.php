<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CupomPagamento extends Model
{
    protected $table = 'cupom_pagamentos';

    protected $fillable = ['cupom_id', 'valor_pago', 'observacao', 'pago_em'];

    protected $casts = [
        'pago_em'    => 'date',
        'valor_pago' => 'decimal:2',
    ];

    public function cupom(): BelongsTo
    {
        return $this->belongsTo(Cupom::class);
    }

    public function usos(): HasMany
    {
        return $this->hasMany(CupomUso::class, 'cupom_pagamento_id');
    }
}
