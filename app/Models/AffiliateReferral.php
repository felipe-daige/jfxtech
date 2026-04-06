<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class AffiliateReferral extends Model
{
    protected $fillable = [
        'affiliate_id', 'referred_user_id', 'status', 'converted_at',
    ];

    protected $casts = [
        'converted_at' => 'datetime',
    ];

    public function affiliate(): BelongsTo
    {
        return $this->belongsTo(Affiliate::class);
    }

    public function referredUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'referred_user_id');
    }

    public function commission(): HasOne
    {
        return $this->hasOne(AffiliateCommission::class, 'referral_id');
    }
}
