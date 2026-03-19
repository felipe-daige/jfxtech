<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProdutoOpcaoValor extends Model
{
    use HasFactory;

    protected $table = 'produto_opcao_valores';

    protected $fillable = ['grupo_id', 'valor', 'ordem'];

    public function grupo(): BelongsTo
    {
        return $this->belongsTo(ProdutoOpcaoGrupo::class, 'grupo_id');
    }
}
