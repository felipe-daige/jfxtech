<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Notifications\ResetPasswordNotification;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'phone',
        'cpf',
        'password',
        'admin',
        'coupon_portal_enabled',
        'must_change_password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'admin' => 'boolean',
            'coupon_portal_enabled' => 'boolean',
            'must_change_password' => 'boolean',
        ];
    }

    /**
     * Relacionamento: Um usuário pode ter vários pedidos
     */
    public function pedidos(): HasMany
    {
        return $this->hasMany(Pedido::class);
    }

    /**
     * Relacionamento: Um usuário pode ter vários favoritos
     */
    public function favoritos(): HasMany
    {
        return $this->hasMany(Favorito::class);
    }

    public function cupons(): HasMany
    {
        return $this->hasMany(Cupom::class);
    }

    public function sorteioParticipantes(): HasMany
    {
        return $this->hasMany(SorteioParticipante::class);
    }

    /**
     * Relacionamento: Um usuário pode favoritar vários produtos
     */
    public function produtosFavoritos(): BelongsToMany
    {
        return $this->belongsToMany(Produto::class, 'favoritos')
            ->withTimestamps();
    }

    public function sendPasswordResetNotification($token): void
    {
        $this->notify(new ResetPasswordNotification($token));
    }
}
