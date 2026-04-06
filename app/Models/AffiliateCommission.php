<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AffiliateCommission extends Model
{
    protected $fillable = [
        'affiliate_id', 'referral_id', 'pedido_id',
        'valor', 'status', 'eligible_at', 'paid_at',
    ];

    protected $casts = [
        'valor'       => 'decimal:2',
        'eligible_at' => 'datetime',
        'paid_at'     => 'datetime',
    ];

    public function affiliate(): BelongsTo
    {
        return $this->belongsTo(Affiliate::class);
    }

    public function referral(): BelongsTo
    {
        return $this->belongsTo(AffiliateReferral::class);
    }

    public function pedido(): BelongsTo
    {
        return $this->belongsTo(Pedido::class);
    }
}
