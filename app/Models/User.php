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

    public const ADMIN_PERMISSION_CATALOG = 'catalog.manage';

    public const ADMIN_PERMISSION_LABELS = [
        self::ADMIN_PERMISSION_CATALOG => 'Produtos e estoque',
    ];

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
        'admin_permissions',
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
            'admin_permissions' => 'array',
            'coupon_portal_enabled' => 'boolean',
            'must_change_password' => 'boolean',
        ];
    }

    public function isSuperAdmin(): bool
    {
        return (bool) $this->admin;
    }

    public function adminPermissions(): array
    {
        $permissions = $this->admin_permissions ?? [];

        if (!is_array($permissions)) {
            return [];
        }

        return array_values(array_filter($permissions, 'is_string'));
    }

    public function hasAdminPermission(string $permission): bool
    {
        return $this->isSuperAdmin()
            || in_array($permission, $this->adminPermissions(), true);
    }

    public function hasAnyAdminPermission(array $permissions = []): bool
    {
        if ($this->isSuperAdmin()) {
            return true;
        }

        $userPermissions = $this->adminPermissions();

        if ($permissions === []) {
            return $userPermissions !== [];
        }

        foreach ($permissions as $permission) {
            if (in_array($permission, $userPermissions, true)) {
                return true;
            }
        }

        return false;
    }

    public function hasAdminAccess(): bool
    {
        return $this->isSuperAdmin() || $this->hasAnyAdminPermission();
    }

    public function canAccessAdminArea(array $permissions = []): bool
    {
        if ($this->isSuperAdmin()) {
            return true;
        }

        if ($permissions === []) {
            return false;
        }

        return $this->hasAnyAdminPermission($permissions);
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
