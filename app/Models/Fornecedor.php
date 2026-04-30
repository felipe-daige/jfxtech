<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Fornecedor extends Model
{
    protected $table = 'fornecedores';

    protected $fillable = [
        'nome',
        'perfil_url',
        'site_url',
        'email',
        'telefone',
        'whatsapp',
        'contato_nome',
        'pais',
        'observacoes',
        'ativo',
    ];

    protected $casts = [
        'ativo' => 'boolean',
    ];

    public function ofertas(): HasMany
    {
        return $this->hasMany(ProdutoFornecedorOferta::class);
    }
}
