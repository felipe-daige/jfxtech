<?php

namespace App\Models;

use App\Enums\PedidoStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Pedido extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'endereco_id',
        'status',
        'valor_total',
        'frete_tipo',
        'frete_valor',
        'frete_custo_real',
        'cupom_codigo',
        'valor_desconto',
        'customer_name',
        'customer_email',
        'customer_phone',
        'guest_token',
        'checkout_mode',
        'codigo_rastreio',
        'nota_fiscal_imagem_path',
    ];

    protected $casts = [
        'valor_total' => 'decimal:2',
        'frete_valor' => 'decimal:2',
        'frete_custo_real' => 'decimal:2',
        'valor_desconto' => 'decimal:2',
    ];

    /**
     * Relacionamento: Um pedido pertence a um usuário
     */
    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Relacionamento: Um pedido pertence a um usuário (alias em inglês)
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Relacionamento: Um pedido pertence a um endereço
     */
    public function endereco(): BelongsTo
    {
        return $this->belongsTo(Endereco::class);
    }

    /**
     * Relacionamento: Um pedido pode ter vários itens
     */
    public function itens(): HasMany
    {
        return $this->hasMany(ItemPedido::class);
    }

    /**
     * Relacionamento: Um pedido pode ter vários pagamentos
     */
    public function pagamentos(): HasMany
    {
        return $this->hasMany(Pagamento::class);
    }

    /**
     * Relacionamento: Um pedido pode ter vários produtos através dos itens
     */
    public function produtos(): BelongsToMany
    {
        return $this->belongsToMany(Produto::class, 'itens_pedido')
            ->withPivot('quantidade', 'preco')
            ->withTimestamps();
    }

    /**
     * Accessor para verificar se o pedido está pago
     */
    public function getPagoAttribute(): bool
    {
        return $this->status === PedidoStatus::PAGO;
    }

    /**
     * Accessor para verificar se o pedido está pendente
     */
    public function getPendenteAttribute(): bool
    {
        return $this->status === PedidoStatus::PENDENTE;
    }

    /**
     * Accessor para o resultado de frete (frete cobrado - frete real pago)
     */
    public function getResultadoFreteAttribute(): ?float
    {
        if ($this->frete_custo_real === null) {
            return null;
        }

        return (float) $this->frete_valor - (float) $this->frete_custo_real;
    }
}
