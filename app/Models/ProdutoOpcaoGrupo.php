<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProdutoOpcaoGrupo extends Model
{
    use HasFactory;

    protected $table = 'produto_opcao_grupos';

    protected $fillable = ['produto_id', 'nome', 'ordem'];

    public function produto(): BelongsTo
    {
        return $this->belongsTo(Produto::class);
    }

    public function valores(): HasMany
    {
        return $this->hasMany(ProdutoOpcaoValor::class, 'grupo_id')->orderBy('ordem')->orderBy('id');
    }
}
