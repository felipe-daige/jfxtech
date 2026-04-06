<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

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
        'customer_name',
        'customer_email',
        'customer_phone',
        'guest_token',
        'checkout_mode',
        'codigo_rastreio',
    ];

    protected $casts = [
        'valor_total' => 'decimal:2',
        'frete_valor' => 'decimal:2',
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
        return $this->status === 'pago';
    }

    /**
     * Accessor para verificar se o pedido está pendente
     */
    public function getPendenteAttribute(): bool
    {
        return $this->status === 'pendente';
    }
}
