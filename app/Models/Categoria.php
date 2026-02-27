<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Categoria extends Model
{
    protected $fillable = [
        'nome',
        'slug',
        'ativo'
    ];

    protected $casts = [
        'ativo' => 'boolean'
    ];

    /**
     * Boot method para gerar slug automaticamente
     */
    protected static function boot()
    {
        parent::boot();
        
        static::creating(function ($categoria) {
            if (empty($categoria->slug)) {
                $categoria->slug = Str::slug($categoria->nome);
            }
        });
        
        static::updating(function ($categoria) {
            if ($categoria->isDirty('nome') && empty($categoria->slug)) {
                $categoria->slug = Str::slug($categoria->nome);
            }
        });
    }

    /**
     * Relacionamento: Uma categoria pode ter vários produtos
     */
    public function produtos(): HasMany
    {
        return $this->hasMany(Produto::class);
    }
}
