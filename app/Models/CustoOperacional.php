<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CustoOperacional extends Model
{
    protected $table = 'custos_operacionais';

    protected $fillable = [
        'created_by',
        'tipo',
        'nome',
        'valor',
        'data_referencia',
        'observacoes',
    ];

    protected $casts = [
        'valor' => 'decimal:2',
        'data_referencia' => 'date',
    ];

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
