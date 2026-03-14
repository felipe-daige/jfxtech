<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProdutoImagem extends Model
{
    protected $table = 'produto_imagens';
    
    protected $fillable = [
        'produto_id',
        'caminho',
        'capa',
        'ordem',
    ];

    /**
     * Relacionamento: Uma imagem pertence a um produto
     */
    public function produto(): BelongsTo
    {
        return $this->belongsTo(Produto::class);
    }
}
