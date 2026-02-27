<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Favorito extends Model
{
    protected $fillable = [
        'user_id',
        'produto_id'
    ];

    /**
     * Relacionamento: Um favorito pertence a um usuário
     */
    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Relacionamento: Um favorito pertence a um produto
     */
    public function produto(): BelongsTo
    {
        return $this->belongsTo(Produto::class);
    }
}
