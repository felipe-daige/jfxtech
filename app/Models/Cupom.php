<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Cupom extends Model
{
    protected $table = 'cupons';

    protected $fillable = [
        'codigo',
        'user_id',
        'restricted_user_id',
        'tipo',
        'valor',
        'valor_minimo_pedido',
        'limite_usos',
        'usos_realizados',
        'valido_ate',
        'ativo',
        'comissao_percentual',
    ];

    protected $casts = [
        'ativo' => 'boolean',
        'restricted_user_id' => 'integer',
        'valido_ate' => 'date',
        'valor' => 'decimal:2',
        'valor_minimo_pedido' => 'decimal:2',
        'comissao_percentual' => 'decimal:2',
    ];

    public function setCodigoAttribute(string $value): void
    {
        $this->attributes['codigo'] = strtoupper(trim($value));
    }

    public function usos(): HasMany
    {
        return $this->hasMany(CupomUso::class);
    }

    public function pagamentos(): HasMany
    {
        return $this->hasMany(CupomPagamento::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function restrictedUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'restricted_user_id');
    }

    public function isRestrictedToDifferentUser(?int $userId): bool
    {
        if ($this->restricted_user_id === null) {
            return false;
        }

        return $userId === null || (int) $this->restricted_user_id !== $userId;
    }

    /**
     * Scope para cupons válidos: ativo + não expirado.
     */
    public function scopeValido($query)
    {
        return $query->where('ativo', true)
            ->where(function ($q) {
                $q->whereNull('valido_ate')->orWhere('valido_ate', '>=', now()->toDateString());
            });
    }

    /**
     * Calcula o valor do desconto para um dado subtotal.
     * Nunca retorna mais que o subtotal.
     */
    public function calcularDesconto(float $subtotal): float
    {
        if ($this->tipo === 'percentual') {
            return round($subtotal * ((float) $this->valor / 100), 2);
        }

        return min((float) $this->valor, $subtotal);
    }
}
