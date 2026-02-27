<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Endereco extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'cep',
        'rua',
        'numero',
        'complemento',
        'bairro',
        'cidade',
        'estado',
        'pais'
    ];

    /**
     * Relacionamento com usuário
     */
    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Relacionamento com pedidos
     */
    public function pedidos(): HasMany
    {
        return $this->hasMany(Pedido::class);
    }

    /**
     * Formatar endereço completo
     */
    public function getEnderecoCompletoAttribute(): string
    {
        $endereco = "{$this->rua}, {$this->numero}";
        
        if ($this->complemento) {
            $endereco .= ", {$this->complemento}";
        }
        
        $endereco .= " - {$this->bairro}, {$this->cidade}/{$this->estado}";
        $endereco .= " - CEP: {$this->cep}";
        
        return $endereco;
    }

    /**
     * Formatar CEP
     */
    public function getCepFormatadoAttribute(): string
    {
        return preg_replace('/(\d{5})(\d{3})/', '$1-$2', $this->cep);
    }
}