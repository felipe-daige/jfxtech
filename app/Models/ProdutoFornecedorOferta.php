<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProdutoFornecedorOferta extends Model
{
    protected $table = 'produto_fornecedor_ofertas';

    protected $fillable = [
        'produto_id',
        'fornecedor_id',
        'preco_compra',
        'frete_compra',
        'moeda',
        'quantidade_minima',
        'prazo_dias',
        'url_produto',
        'sku_fornecedor',
        'observacoes',
        'cotado_em',
        'ativo',
    ];

    protected $casts = [
        'preco_compra' => 'decimal:2',
        'frete_compra' => 'decimal:2',
        'quantidade_minima' => 'integer',
        'prazo_dias' => 'integer',
        'cotado_em' => 'date',
        'ativo' => 'boolean',
    ];

    public function produto(): BelongsTo
    {
        return $this->belongsTo(Produto::class);
    }

    public function fornecedor(): BelongsTo
    {
        return $this->belongsTo(Fornecedor::class);
    }
}
