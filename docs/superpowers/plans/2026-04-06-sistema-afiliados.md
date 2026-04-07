# Sistema de Afiliados — Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Implement a full affiliate marketing system with user-facing panel, admin CRUD, commission tracking via cookie, and SSE real-time metrics on the admin dashboard.

**Architecture:** `AffiliateService` encapsulates all business logic (cookie tracking, referral creation, commission calculation). Two thin controllers (`AffiliadoController` for users, `AdminAfiliadoController` for admin). A `TrackAffiliateReferral` middleware appended to the `web` group intercepts `?ref=` query params.

**Tech Stack:** Laravel 12, PostgreSQL, Tailwind CSS v4, vanilla JS, SSE (EventSource). All commands run inside `docker exec laravel-app`. Frontend assets built on host with `npm run build`.

---

## File Map

**New files:**
```
database/migrations/2026_04_06_000001_create_affiliates_table.php
database/migrations/2026_04_06_000002_create_affiliate_referrals_table.php
database/migrations/2026_04_06_000003_create_affiliate_commissions_table.php
database/migrations/2026_04_06_000004_create_affiliate_settings_table.php
database/factories/AffiliateFactory.php
app/Models/Affiliate.php
app/Models/AffiliateReferral.php
app/Models/AffiliateCommission.php
app/Models/AffiliateSetting.php
app/Services/AffiliateService.php
app/Http/Middleware/TrackAffiliateReferral.php
app/Http/Controllers/AffiliadoController.php
app/Http/Controllers/AdminAfiliadoController.php
resources/views/site/afiliados/painel.blade.php
resources/views/site/afiliados/solicitar.blade.php
resources/views/site/afiliados/indicacoes.blade.php
resources/views/site/afiliados/comissoes.blade.php
resources/views/admin/afiliados/index.blade.php
resources/views/admin/afiliados/comissoes.blade.php
resources/views/admin/afiliados/configuracoes.blade.php
public/js/afiliados.js
public/js/afiliados-admin.js
tests/Feature/AffiliateServiceTest.php
tests/Feature/AffiliateMiddlewareTest.php
tests/Feature/AffiliadoControllerTest.php
tests/Feature/AdminAfiliadoControllerTest.php
```

**Modified files:**
```
routes/web.php                                         — add /afiliados/* and /admin/afiliados/* routes
bootstrap/app.php                                      — append TrackAffiliateReferral to web middleware
app/Http/Controllers/SiteController.php               — inject AffiliateService; call recordReferralOnRegister after Auth::login
app/Http/Controllers/MercadoPagoCheckoutController.php — inject AffiliateService; call handleOrderPaid when status → 'pago'
resources/views/includes/header-admin.blade.php        — add Afiliados link to sidebar
resources/views/includes/header.blade.php              — add Painel Afiliado link to user dropdown
```

---

## Task 1: Migrations

**Files:**
- Create: `database/migrations/2026_04_06_000001_create_affiliates_table.php`
- Create: `database/migrations/2026_04_06_000002_create_affiliate_referrals_table.php`
- Create: `database/migrations/2026_04_06_000003_create_affiliate_commissions_table.php`
- Create: `database/migrations/2026_04_06_000004_create_affiliate_settings_table.php`

- [ ] **Step 1: Create affiliates migration**

```php
<?php
// database/migrations/2026_04_06_000001_create_affiliates_table.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('affiliates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('codigo', 8)->unique();
            $table->enum('commission_type', ['percent', 'fixed'])->default('percent');
            $table->decimal('commission_value', 8, 2)->nullable();
            $table->enum('status', ['pendente', 'ativo', 'inativo'])->default('pendente');
            $table->string('pix_key')->nullable();
            $table->text('bank_info')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('affiliates');
    }
};
```

- [ ] **Step 2: Create affiliate_referrals migration**

```php
<?php
// database/migrations/2026_04_06_000002_create_affiliate_referrals_table.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('affiliate_referrals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('affiliate_id')->constrained()->cascadeOnDelete();
            $table->foreignId('referred_user_id')->unique()->constrained('users')->cascadeOnDelete();
            $table->enum('status', ['pendente', 'convertido', 'cancelado'])->default('pendente');
            $table->timestamp('converted_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('affiliate_referrals');
    }
};
```

- [ ] **Step 3: Create affiliate_commissions migration**

```php
<?php
// database/migrations/2026_04_06_000003_create_affiliate_commissions_table.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('affiliate_commissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('affiliate_id')->constrained()->cascadeOnDelete();
            $table->foreignId('referral_id')->constrained('affiliate_referrals')->cascadeOnDelete();
            $table->foreignId('pedido_id')->constrained('pedidos')->cascadeOnDelete();
            $table->decimal('valor', 8, 2);
            $table->enum('status', ['pendente', 'aprovado', 'pago', 'rejeitado'])->default('pendente');
            $table->timestamp('eligible_at');
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('affiliate_commissions');
    }
};
```

- [ ] **Step 4: Create affiliate_settings migration with seed data**

```php
<?php
// database/migrations/2026_04_06_000004_create_affiliate_settings_table.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('affiliate_settings', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->text('value');
            $table->timestamps();
        });

        DB::table('affiliate_settings')->insert([
            ['key' => 'commission_percent_default', 'value' => '5.00', 'created_at' => now(), 'updated_at' => now()],
            ['key' => 'cookie_days',                'value' => '30',   'created_at' => now(), 'updated_at' => now()],
            ['key' => 'grace_period_days',          'value' => '30',   'created_at' => now(), 'updated_at' => now()],
            ['key' => 'commission_trigger',         'value' => 'first_paid_order', 'created_at' => now(), 'updated_at' => now()],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('affiliate_settings');
    }
};
```

- [ ] **Step 5: Run migrations**

```bash
docker exec laravel-app php artisan migrate --force
```

Expected: all 4 new tables created with no errors.

- [ ] **Step 6: Commit**

```bash
git add database/migrations/2026_04_06_000001_create_affiliates_table.php \
        database/migrations/2026_04_06_000002_create_affiliate_referrals_table.php \
        database/migrations/2026_04_06_000003_create_affiliate_commissions_table.php \
        database/migrations/2026_04_06_000004_create_affiliate_settings_table.php
git commit -m "feat: add affiliate system migrations"
```

---

## Task 2: Models + Factory

**Files:**
- Create: `app/Models/Affiliate.php`
- Create: `app/Models/AffiliateReferral.php`
- Create: `app/Models/AffiliateCommission.php`
- Create: `app/Models/AffiliateSetting.php`
- Create: `database/factories/AffiliateFactory.php`

- [ ] **Step 1: Create Affiliate model**

```php
<?php
// app/Models/Affiliate.php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Affiliate extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id', 'codigo', 'commission_type', 'commission_value',
        'status', 'pix_key', 'bank_info', 'approved_at',
    ];

    protected $casts = [
        'commission_value' => 'decimal:2',
        'approved_at'      => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function referrals(): HasMany
    {
        return $this->hasMany(AffiliateReferral::class);
    }

    public function commissions(): HasMany
    {
        return $this->hasMany(AffiliateCommission::class);
    }
}
```

- [ ] **Step 2: Create AffiliateReferral model**

```php
<?php
// app/Models/AffiliateReferral.php
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
```

- [ ] **Step 3: Create AffiliateCommission model**

```php
<?php
// app/Models/AffiliateCommission.php
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
```

- [ ] **Step 4: Create AffiliateSetting model**

```php
<?php
// app/Models/AffiliateSetting.php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AffiliateSetting extends Model
{
    protected $fillable = ['key', 'value'];
}
```

- [ ] **Step 5: Create AffiliateFactory**

```php
<?php
// database/factories/AffiliateFactory.php
namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class AffiliateFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id'          => User::factory(),
            'codigo'           => strtoupper(Str::random(8)),
            'commission_type'  => 'percent',
            'commission_value' => null,
            'status'           => 'ativo',
            'pix_key'          => null,
            'bank_info'        => null,
            'approved_at'      => now(),
        ];
    }
}
```

- [ ] **Step 6: Commit**

```bash
git add app/Models/Affiliate.php \
        app/Models/AffiliateReferral.php \
        app/Models/AffiliateCommission.php \
        app/Models/AffiliateSetting.php \
        database/factories/AffiliateFactory.php
git commit -m "feat: add affiliate models and factory"
```

---

## Task 3: AffiliateService — getSetting + generateUniqueCode

**Files:**
- Create: `app/Services/AffiliateService.php`
- Create: `tests/Feature/AffiliateServiceTest.php`

- [ ] **Step 1: Write failing tests**

```php
<?php
// tests/Feature/AffiliateServiceTest.php
namespace Tests\Feature;

use App\Models\Affiliate;
use App\Models\AffiliateSetting;
use App\Services\AffiliateService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AffiliateServiceTest extends TestCase
{
    use RefreshDatabase;

    private AffiliateService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new AffiliateService();
    }

    public function test_get_setting_returns_default_when_missing(): void
    {
        $value = $this->service->getSetting('commission_percent_default', '5.00');
        $this->assertEquals('5.00', $value);
    }

    public function test_get_setting_returns_db_value(): void
    {
        AffiliateSetting::create(['key' => 'cookie_days', 'value' => '45']);
        $value = $this->service->getSetting('cookie_days', '30');
        $this->assertEquals('45', $value);
    }

    public function test_generate_unique_code_is_8_uppercase_alphanumeric(): void
    {
        $code = $this->service->generateUniqueCode();
        $this->assertEquals(8, strlen($code));
        $this->assertMatchesRegularExpression('/^[A-Z0-9]{8}$/', $code);
    }

    public function test_generate_unique_code_does_not_collide_with_existing(): void
    {
        Affiliate::factory()->create(['codigo' => 'AAAAAAAA']);
        // Run multiple times to ensure uniqueness logic is exercised
        for ($i = 0; $i < 5; $i++) {
            $code = $this->service->generateUniqueCode();
            $this->assertNotEquals('AAAAAAAA', $code);
        }
    }
}
```

- [ ] **Step 2: Run to verify failure**

```bash
docker exec laravel-app php artisan test tests/Feature/AffiliateServiceTest.php
```

Expected: `Error: Class "App\Services\AffiliateService" not found`

- [ ] **Step 3: Create AffiliateService with getSetting + generateUniqueCode**

```php
<?php
// app/Services/AffiliateService.php
namespace App\Services;

use App\Models\Affiliate;
use App\Models\AffiliateCommission;
use App\Models\AffiliateReferral;
use App\Models\AffiliateSetting;
use App\Models\Pedido;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class AffiliateService
{
    public const COOKIE_NAME = 'affiliate_ref';

    public function getSetting(string $key, mixed $default = null): mixed
    {
        $setting = AffiliateSetting::where('key', $key)->first();
        return $setting?->value ?? $default;
    }

    public function generateUniqueCode(): string
    {
        do {
            $code = strtoupper(Str::random(8));
        } while (Affiliate::where('codigo', $code)->exists());

        return $code;
    }
}
```

- [ ] **Step 4: Run tests to verify pass**

```bash
docker exec laravel-app php artisan test tests/Feature/AffiliateServiceTest.php
```

Expected: 4 tests, 4 passed.

- [ ] **Step 5: Commit**

```bash
git add app/Services/AffiliateService.php tests/Feature/AffiliateServiceTest.php
git commit -m "feat: add AffiliateService with getSetting and generateUniqueCode"
```

---

## Task 4: AffiliateService — calculateCommission

**Files:**
- Modify: `app/Services/AffiliateService.php` — add `calculateCommission`
- Modify: `tests/Feature/AffiliateServiceTest.php` — add 3 tests

- [ ] **Step 1: Add failing tests**

Append these test methods to `AffiliateServiceTest`:

```php
public function test_calculate_commission_percent_type(): void
{
    AffiliateSetting::create(['key' => 'commission_percent_default', 'value' => '5.00']);
    $user = \App\Models\User::factory()->create();
    $affiliate = Affiliate::factory()->create([
        'user_id' => $user->id,
        'commission_type'  => 'percent',
        'commission_value' => 10.00,
    ]);
    $pedido = Pedido::create([
        'user_id' => $user->id, 'status' => 'pago',
        'valor_total' => 200.00, 'frete_tipo' => 'pac', 'frete_valor' => 0,
    ]);

    $valor = $this->service->calculateCommission($affiliate, $pedido);

    $this->assertEquals(20.00, $valor); // 10% of 200
}

public function test_calculate_commission_fixed_type(): void
{
    $user = \App\Models\User::factory()->create();
    $affiliate = Affiliate::factory()->create([
        'user_id' => $user->id,
        'commission_type'  => 'fixed',
        'commission_value' => 15.00,
    ]);
    $pedido = Pedido::create([
        'user_id' => $user->id, 'status' => 'pago',
        'valor_total' => 200.00, 'frete_tipo' => 'pac', 'frete_valor' => 0,
    ]);

    $valor = $this->service->calculateCommission($affiliate, $pedido);

    $this->assertEquals(15.00, $valor); // flat R$ 15
}

public function test_calculate_commission_uses_global_when_value_null(): void
{
    AffiliateSetting::create(['key' => 'commission_percent_default', 'value' => '5.00']);
    $user = \App\Models\User::factory()->create();
    $affiliate = Affiliate::factory()->create([
        'user_id' => $user->id,
        'commission_type'  => 'percent',
        'commission_value' => null,
    ]);
    $pedido = Pedido::create([
        'user_id' => $user->id, 'status' => 'pago',
        'valor_total' => 100.00, 'frete_tipo' => 'pac', 'frete_valor' => 0,
    ]);

    $valor = $this->service->calculateCommission($affiliate, $pedido);

    $this->assertEquals(5.00, $valor); // 5% of 100
}
```

- [ ] **Step 2: Run to verify failure**

```bash
docker exec laravel-app php artisan test tests/Feature/AffiliateServiceTest.php
```

Expected: `Error: Call to undefined method App\Services\AffiliateService::calculateCommission()`

- [ ] **Step 3: Add calculateCommission to AffiliateService**

Append this method to `AffiliateService` (inside the class, before the closing `}`):

```php
    public function calculateCommission(Affiliate $affiliate, Pedido $pedido): float
    {
        $type  = $affiliate->commission_type ?? 'percent';
        $value = $affiliate->commission_value !== null
            ? (float) $affiliate->commission_value
            : (float) $this->getSetting('commission_percent_default', '5.00');

        return $type === 'fixed'
            ? $value
            : round((float) $pedido->valor_total * $value / 100, 2);
    }
```

- [ ] **Step 4: Run tests to verify pass**

```bash
docker exec laravel-app php artisan test tests/Feature/AffiliateServiceTest.php
```

Expected: 7 tests, 7 passed.

- [ ] **Step 5: Commit**

```bash
git add app/Services/AffiliateService.php tests/Feature/AffiliateServiceTest.php
git commit -m "feat: add calculateCommission to AffiliateService"
```

---

## Task 5: AffiliateService — recordReferralOnRegister

**Files:**
- Modify: `app/Services/AffiliateService.php` — add `recordReferralOnRegister`
- Modify: `tests/Feature/AffiliateServiceTest.php` — add 4 tests

- [ ] **Step 1: Add failing tests**

Append to `AffiliateServiceTest`:

```php
public function test_record_referral_creates_pending_referral(): void
{
    $affiliateUser = \App\Models\User::factory()->create();
    $affiliate = Affiliate::factory()->create(['user_id' => $affiliateUser->id, 'status' => 'ativo']);
    $newUser = \App\Models\User::factory()->create();

    $this->withCookies([AffiliateService::COOKIE_NAME => $affiliate->codigo])
         ->actingAs($newUser);

    // Simulate cookie being present by setting it on the request
    $request = \Illuminate\Http\Request::create('/');
    $request->cookies->set(AffiliateService::COOKIE_NAME, $affiliate->codigo);
    app()->instance('request', $request);

    $this->service->recordReferralOnRegister($newUser);

    $this->assertDatabaseHas('affiliate_referrals', [
        'affiliate_id'     => $affiliate->id,
        'referred_user_id' => $newUser->id,
        'status'           => 'pendente',
    ]);
}

public function test_record_referral_ignores_empty_cookie(): void
{
    $newUser = \App\Models\User::factory()->create();
    $request = \Illuminate\Http\Request::create('/');
    app()->instance('request', $request);

    $this->service->recordReferralOnRegister($newUser);

    $this->assertDatabaseCount('affiliate_referrals', 0);
}

public function test_record_referral_ignores_self_referral(): void
{
    $user = \App\Models\User::factory()->create();
    $affiliate = Affiliate::factory()->create(['user_id' => $user->id, 'status' => 'ativo']);

    $request = \Illuminate\Http\Request::create('/');
    $request->cookies->set(AffiliateService::COOKIE_NAME, $affiliate->codigo);
    app()->instance('request', $request);

    $this->service->recordReferralOnRegister($user);

    $this->assertDatabaseCount('affiliate_referrals', 0);
}

public function test_record_referral_ignores_invalid_code(): void
{
    $newUser = \App\Models\User::factory()->create();
    $request = \Illuminate\Http\Request::create('/');
    $request->cookies->set(AffiliateService::COOKIE_NAME, 'INVALID1');
    app()->instance('request', $request);

    $this->service->recordReferralOnRegister($newUser);

    $this->assertDatabaseCount('affiliate_referrals', 0);
}
```

- [ ] **Step 2: Run to verify failure**

```bash
docker exec laravel-app php artisan test tests/Feature/AffiliateServiceTest.php --filter=record_referral
```

Expected: `Error: Call to undefined method App\Services\AffiliateService::recordReferralOnRegister()`

- [ ] **Step 3: Add recordReferralOnRegister to AffiliateService**

Append to `AffiliateService`:

```php
    public function recordReferralOnRegister(User $user): void
    {
        $codigo = request()->cookie(self::COOKIE_NAME);
        if (!$codigo) {
            return;
        }

        $affiliate = Affiliate::where('codigo', $codigo)
            ->where('status', 'ativo')
            ->first();

        if (!$affiliate) {
            return;
        }

        // Anti-self-referral
        if ($affiliate->user_id === $user->id) {
            return;
        }

        // UNIQUE constraint guards duplicate; skip if already referred
        if (AffiliateReferral::where('referred_user_id', $user->id)->exists()) {
            return;
        }

        AffiliateReferral::create([
            'affiliate_id'     => $affiliate->id,
            'referred_user_id' => $user->id,
            'status'           => 'pendente',
        ]);
    }
```

- [ ] **Step 4: Run tests**

```bash
docker exec laravel-app php artisan test tests/Feature/AffiliateServiceTest.php
```

Expected: 11 tests, 11 passed.

- [ ] **Step 5: Commit**

```bash
git add app/Services/AffiliateService.php tests/Feature/AffiliateServiceTest.php
git commit -m "feat: add recordReferralOnRegister to AffiliateService"
```

---

## Task 6: AffiliateService — handleOrderPaid

**Files:**
- Modify: `app/Services/AffiliateService.php` — add `handleOrderPaid`
- Modify: `tests/Feature/AffiliateServiceTest.php` — add 4 tests

- [ ] **Step 1: Add failing tests**

Append to `AffiliateServiceTest`:

```php
private function makePaidOrder(\App\Models\User $user, float $total = 100.00): Pedido
{
    return Pedido::create([
        'user_id'     => $user->id,
        'status'      => 'pago',
        'valor_total' => $total,
        'frete_tipo'  => 'pac',
        'frete_valor' => 0,
    ]);
}

public function test_handle_order_paid_creates_commission(): void
{
    AffiliateSetting::create(['key' => 'commission_percent_default', 'value' => '5.00']);
    AffiliateSetting::create(['key' => 'grace_period_days', 'value' => '30']);

    $affiliateUser = \App\Models\User::factory()->create();
    $affiliate = Affiliate::factory()->create(['user_id' => $affiliateUser->id]);
    $buyer = \App\Models\User::factory()->create();
    AffiliateReferral::create([
        'affiliate_id' => $affiliate->id, 'referred_user_id' => $buyer->id, 'status' => 'pendente',
    ]);
    $pedido = $this->makePaidOrder($buyer, 200.00);

    $this->service->handleOrderPaid($pedido);

    $this->assertDatabaseHas('affiliate_commissions', [
        'affiliate_id' => $affiliate->id,
        'pedido_id'    => $pedido->id,
        'valor'        => 10.00, // 5% of 200
        'status'       => 'pendente',
    ]);
    $this->assertDatabaseHas('affiliate_referrals', [
        'id'     => AffiliateReferral::first()->id,
        'status' => 'convertido',
    ]);
}

public function test_handle_order_paid_skips_guest_orders(): void
{
    $pedido = Pedido::create([
        'user_id' => null, 'status' => 'pago',
        'valor_total' => 100.00, 'frete_tipo' => 'pac', 'frete_valor' => 0,
        'checkout_mode' => 'guest',
    ]);

    $this->service->handleOrderPaid($pedido);

    $this->assertDatabaseCount('affiliate_commissions', 0);
}

public function test_handle_order_paid_skips_non_first_purchase(): void
{
    AffiliateSetting::create(['key' => 'commission_percent_default', 'value' => '5.00']);
    AffiliateSetting::create(['key' => 'grace_period_days', 'value' => '30']);

    $affiliateUser = \App\Models\User::factory()->create();
    $affiliate = Affiliate::factory()->create(['user_id' => $affiliateUser->id]);
    $buyer = \App\Models\User::factory()->create();
    AffiliateReferral::create([
        'affiliate_id' => $affiliate->id, 'referred_user_id' => $buyer->id, 'status' => 'pendente',
    ]);

    // First paid order (not via service, simulate already existing)
    Pedido::create(['user_id' => $buyer->id, 'status' => 'pago', 'valor_total' => 50.00, 'frete_tipo' => 'pac', 'frete_valor' => 0]);

    // Second paid order
    $secondPedido = $this->makePaidOrder($buyer, 100.00);

    $this->service->handleOrderPaid($secondPedido);

    $this->assertDatabaseCount('affiliate_commissions', 0);
}

public function test_handle_order_paid_skips_user_without_referral(): void
{
    $buyer = \App\Models\User::factory()->create();
    $pedido = $this->makePaidOrder($buyer);

    $this->service->handleOrderPaid($pedido);

    $this->assertDatabaseCount('affiliate_commissions', 0);
}
```

- [ ] **Step 2: Run to verify failure**

```bash
docker exec laravel-app php artisan test tests/Feature/AffiliateServiceTest.php --filter=handle_order_paid
```

Expected: `Error: Call to undefined method App\Services\AffiliateService::handleOrderPaid()`

- [ ] **Step 3: Add handleOrderPaid to AffiliateService**

Append to `AffiliateService`:

```php
    public function handleOrderPaid(Pedido $pedido): void
    {
        if ($pedido->user_id === null) {
            return;
        }

        $referral = AffiliateReferral::where('referred_user_id', $pedido->user_id)
            ->where('status', 'pendente')
            ->first();

        if (!$referral) {
            return;
        }

        // Only commission on the very first paid order
        $paidCount = Pedido::where('user_id', $pedido->user_id)
            ->where('status', 'pago')
            ->count();

        if ($paidCount !== 1) {
            return;
        }

        $affiliate = $referral->affiliate;
        $valor = $this->calculateCommission($affiliate, $pedido);
        $graceDays = (int) $this->getSetting('grace_period_days', '30');

        AffiliateCommission::create([
            'affiliate_id' => $affiliate->id,
            'referral_id'  => $referral->id,
            'pedido_id'    => $pedido->id,
            'valor'        => $valor,
            'status'       => 'pendente',
            'eligible_at'  => now()->addDays($graceDays),
        ]);

        $referral->update([
            'status'       => 'convertido',
            'converted_at' => now(),
        ]);
    }
```

- [ ] **Step 4: Run all service tests**

```bash
docker exec laravel-app php artisan test tests/Feature/AffiliateServiceTest.php
```

Expected: 15 tests, 15 passed.

- [ ] **Step 5: Commit**

```bash
git add app/Services/AffiliateService.php tests/Feature/AffiliateServiceTest.php
git commit -m "feat: add handleOrderPaid to AffiliateService"
```

---

## Task 7: Middleware — TrackAffiliateReferral

**Files:**
- Create: `app/Http/Middleware/TrackAffiliateReferral.php`
- Create: `tests/Feature/AffiliateMiddlewareTest.php`

- [ ] **Step 1: Write failing tests**

```php
<?php
// tests/Feature/AffiliateMiddlewareTest.php
namespace Tests\Feature;

use App\Models\Affiliate;
use App\Models\AffiliateSetting;
use App\Services\AffiliateService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AffiliateMiddlewareTest extends TestCase
{
    use RefreshDatabase;

    public function test_middleware_sets_cookie_when_ref_is_valid_active_affiliate(): void
    {
        AffiliateSetting::create(['key' => 'cookie_days', 'value' => '30']);
        $affiliate = Affiliate::factory()->create(['status' => 'ativo', 'codigo' => 'TESTCODE']);

        $response = $this->get('/?ref=TESTCODE');

        $response->assertCookie(AffiliateService::COOKIE_NAME, 'TESTCODE');
    }

    public function test_middleware_ignores_invalid_code(): void
    {
        $response = $this->get('/?ref=BADCODE1');

        $response->assertCookieMissing(AffiliateService::COOKIE_NAME);
    }

    public function test_middleware_ignores_inactive_affiliate(): void
    {
        Affiliate::factory()->create(['status' => 'inativo', 'codigo' => 'INACTIVE']);

        $response = $this->get('/?ref=INACTIVE');

        $response->assertCookieMissing(AffiliateService::COOKIE_NAME);
    }

    public function test_middleware_ignores_request_without_ref(): void
    {
        $response = $this->get('/');

        $response->assertCookieMissing(AffiliateService::COOKIE_NAME);
    }
}
```

- [ ] **Step 2: Run to verify failure**

```bash
docker exec laravel-app php artisan test tests/Feature/AffiliateMiddlewareTest.php
```

Expected: tests pass trivially (cookie missing tests pass, cookie presence test fails because middleware doesn't exist yet). All 4 fail or 3 pass/1 fail is acceptable — the middleware doesn't exist so `assertCookieMissing` tests will pass by default. The important one (`sets_cookie_when_ref_is_valid`) will fail.

- [ ] **Step 3: Create the middleware**

```php
<?php
// app/Http/Middleware/TrackAffiliateReferral.php
namespace App\Http\Middleware;

use App\Models\Affiliate;
use App\Models\AffiliateSetting;
use App\Services\AffiliateService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class TrackAffiliateReferral
{
    public function handle(Request $request, Closure $next): Response
    {
        $ref = $request->query('ref');

        if ($ref && !$request->hasCookie(AffiliateService::COOKIE_NAME)) {
            $affiliate = Affiliate::where('codigo', $ref)
                ->where('status', 'ativo')
                ->first();

            if ($affiliate) {
                $days = (int) (AffiliateSetting::where('key', 'cookie_days')->first()?->value ?? 30);
                $response = $next($request);
                $response->withCookie(cookie(AffiliateService::COOKIE_NAME, $ref, $days * 24 * 60));
                return $response;
            }
        }

        return $next($request);
    }
}
```

- [ ] **Step 4: Register middleware in bootstrap/app.php**

Edit `bootstrap/app.php`. Replace:

```php
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->trustProxies(at: '*');
    })
```

With:

```php
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->trustProxies(at: '*');
        $middleware->web(append: [
            \App\Http\Middleware\TrackAffiliateReferral::class,
        ]);
    })
```

- [ ] **Step 5: Run middleware tests**

```bash
docker exec laravel-app php artisan test tests/Feature/AffiliateMiddlewareTest.php
```

Expected: 4 tests, 4 passed.

- [ ] **Step 6: Commit**

```bash
git add app/Http/Middleware/TrackAffiliateReferral.php \
        bootstrap/app.php \
        tests/Feature/AffiliateMiddlewareTest.php
git commit -m "feat: add TrackAffiliateReferral middleware"
```

---

## Task 8: Wire SiteController + MercadoPagoCheckoutController

**Files:**
- Modify: `app/Http/Controllers/SiteController.php`
- Modify: `app/Http/Controllers/MercadoPagoCheckoutController.php`

- [ ] **Step 1: Inject AffiliateService into SiteController and call after registration**

In `app/Http/Controllers/SiteController.php`:

1. Add `use App\Services\AffiliateService;` to the imports (after the existing `use` block).

2. Change the constructor at lines 20-23 from:
```php
    public function __construct(
        protected CheckoutOrderService $checkoutOrderService
    ) {
    }
```
To:
```php
    public function __construct(
        protected CheckoutOrderService $checkoutOrderService,
        protected AffiliateService $affiliateService,
    ) {
    }
```

3. In the `register()` method, after `Auth::login($user);` (currently line 311) and before the `return redirect()`, add:
```php
        $this->affiliateService->recordReferralOnRegister($user);
```

The end of `register()` should look like:
```php
        Auth::login($user);

        $this->affiliateService->recordReferralOnRegister($user);

        return redirect()->route('site.index')
            ->with('success', 'Conta criada com sucesso! Bem-vindo à MX Racing!');
    }
```

- [ ] **Step 2: Inject AffiliateService into MercadoPagoCheckoutController and call on paid**

In `app/Http/Controllers/MercadoPagoCheckoutController.php`:

1. Add `use App\Services\AffiliateService;` to imports.

2. Change the constructor at lines 21-25 from:
```php
    public function __construct(
        protected MercadoPagoService $mercadoPagoService,
        protected CheckoutOrderService $checkoutOrderService
    ) {
    }
```
To:
```php
    public function __construct(
        protected MercadoPagoService $mercadoPagoService,
        protected CheckoutOrderService $checkoutOrderService,
        protected AffiliateService $affiliateService,
    ) {
    }
```

3. Locate the block at lines 374-376:
```php
        $pedido->update([
            'status' => $this->mapOrderStatus($gatewayResponse['status'] ?? null),
        ]);
```

Replace with:
```php
        $newStatus = $this->mapOrderStatus($gatewayResponse['status'] ?? null);
        $pedido->update(['status' => $newStatus]);

        if ($newStatus === 'pago') {
            $this->affiliateService->handleOrderPaid($pedido);
        }
```

- [ ] **Step 3: Run existing tests to verify no regressions**

```bash
docker exec laravel-app php artisan test tests/Feature/MercadoPagoCheckoutTest.php
```

Expected: all existing tests pass.

- [ ] **Step 4: Commit**

```bash
git add app/Http/Controllers/SiteController.php \
        app/Http/Controllers/MercadoPagoCheckoutController.php
git commit -m "feat: wire AffiliateService into register and order paid flows"
```

---

## Task 9: Routes

**Files:**
- Modify: `routes/web.php`

- [ ] **Step 1: Add all affiliate routes to routes/web.php**

After the existing profile routes (after `Route::delete('/enderecos/{endereco}', ...)`) in `routes/web.php`, add:

```php
use App\Http\Controllers\AffiliadoController;
use App\Http\Controllers\AdminAfiliadoController;

// ─── Painel do Afiliado (requires auth) ───────────────────────────────────────
Route::middleware('auth')->group(function () {
    Route::get('/afiliados', [AffiliadoController::class, 'painel'])->name('afiliados.painel');
    Route::get('/afiliados/solicitar', [AffiliadoController::class, 'solicitar'])->name('afiliados.solicitar');
    Route::post('/afiliados/solicitar', [AffiliadoController::class, 'registrar'])->name('afiliados.registrar');
    Route::get('/afiliados/indicacoes', [AffiliadoController::class, 'indicacoes'])->name('afiliados.indicacoes');
    Route::get('/afiliados/comissoes', [AffiliadoController::class, 'comissoes'])->name('afiliados.comissoes');
});

// ─── Admin: Afiliados ─────────────────────────────────────────────────────────
Route::prefix('admin/afiliados')->name('admin.afiliados.')->group(function () {
    Route::get('/',                        [AdminAfiliadoController::class, 'index'])->name('index');
    Route::get('/stream',                  [AdminAfiliadoController::class, 'stream'])->name('stream');
    Route::get('/comissoes',               [AdminAfiliadoController::class, 'comissoes'])->name('comissoes');
    Route::post('/comissoes/bulk',         [AdminAfiliadoController::class, 'bulkComissoes'])->name('comissoes.bulk');
    Route::get('/configuracoes',           [AdminAfiliadoController::class, 'configuracoes'])->name('configuracoes');
    Route::post('/configuracoes',          [AdminAfiliadoController::class, 'salvarConfiguracoes'])->name('configuracoes.salvar');
    Route::get('/{id}',                    [AdminAfiliadoController::class, 'show'])->name('show');
    Route::post('/{id}/aprovar',           [AdminAfiliadoController::class, 'aprovar'])->name('aprovar');
    Route::post('/{id}/suspender',         [AdminAfiliadoController::class, 'suspender'])->name('suspender');
    Route::post('/{id}/comissao',          [AdminAfiliadoController::class, 'editarComissao'])->name('comissao');
});
```

> **Important:** The static routes (`/comissoes`, `/configuracoes`, `/stream`) are declared BEFORE `/{id}` to avoid route parameter collision.

- [ ] **Step 2: Verify routes are registered**

```bash
docker exec laravel-app php artisan route:list --path=afiliados
```

Expected: all 15 affiliate routes listed.

- [ ] **Step 3: Commit**

```bash
git add routes/web.php
git commit -m "feat: register affiliate routes"
```

---

## Task 10: AffiliadoController

**Files:**
- Create: `app/Http/Controllers/AffiliadoController.php`
- Create: `tests/Feature/AffiliadoControllerTest.php`

- [ ] **Step 1: Write failing tests**

```php
<?php
// tests/Feature/AffiliadoControllerTest.php
namespace Tests\Feature;

use App\Models\Affiliate;
use App\Models\AffiliateSetting;
use App\Models\AffiliateReferral;
use App\Models\AffiliateCommission;
use App\Models\User;
use App\Models\Pedido;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AffiliadoControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_painel_redirects_unauthenticated_to_login(): void
    {
        $this->get(route('afiliados.painel'))
             ->assertRedirect(route('site.login'));
    }

    public function test_painel_redirects_to_solicitar_when_no_affiliate(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user)
             ->get(route('afiliados.painel'))
             ->assertRedirect(route('afiliados.solicitar'));
    }

    public function test_painel_shows_pending_message_when_status_pendente(): void
    {
        $user = User::factory()->create();
        Affiliate::factory()->create(['user_id' => $user->id, 'status' => 'pendente']);

        $this->actingAs($user)
             ->get(route('afiliados.painel'))
             ->assertOk()
             ->assertSee('análise');
    }

    public function test_painel_shows_dashboard_when_ativo(): void
    {
        $user = User::factory()->create();
        Affiliate::factory()->create(['user_id' => $user->id, 'status' => 'ativo']);

        $this->actingAs($user)
             ->get(route('afiliados.painel'))
             ->assertOk()
             ->assertSee('Link de Indicação');
    }

    public function test_solicitar_form_shows_for_non_affiliate(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user)
             ->get(route('afiliados.solicitar'))
             ->assertOk();
    }

    public function test_registrar_creates_affiliate_and_redirects(): void
    {
        AffiliateSetting::create(['key' => 'commission_percent_default', 'value' => '5.00']);
        $user = User::factory()->create();

        $this->actingAs($user)
             ->post(route('afiliados.registrar'), ['pix_key' => 'meu@pix.com'])
             ->assertRedirect(route('afiliados.painel'));

        $this->assertDatabaseHas('affiliates', [
            'user_id' => $user->id,
            'status'  => 'pendente',
        ]);
    }

    public function test_registrar_prevents_duplicate_affiliate(): void
    {
        $user = User::factory()->create();
        Affiliate::factory()->create(['user_id' => $user->id]);

        $this->actingAs($user)
             ->post(route('afiliados.registrar'), [])
             ->assertRedirect(route('afiliados.painel'));

        $this->assertDatabaseCount('affiliates', 1);
    }

    public function test_indicacoes_returns_ok_for_active_affiliate(): void
    {
        $user = User::factory()->create();
        Affiliate::factory()->create(['user_id' => $user->id, 'status' => 'ativo']);

        $this->actingAs($user)
             ->get(route('afiliados.indicacoes'))
             ->assertOk();
    }

    public function test_comissoes_returns_ok_for_active_affiliate(): void
    {
        $user = User::factory()->create();
        Affiliate::factory()->create(['user_id' => $user->id, 'status' => 'ativo']);

        $this->actingAs($user)
             ->get(route('afiliados.comissoes'))
             ->assertOk();
    }
}
```

- [ ] **Step 2: Run to verify failure**

```bash
docker exec laravel-app php artisan test tests/Feature/AffiliadoControllerTest.php
```

Expected: `Error: Target class [App\Http\Controllers\AffiliadoController] does not exist`

- [ ] **Step 3: Create AffiliadoController**

```php
<?php
// app/Http/Controllers/AffiliadoController.php
namespace App\Http\Controllers;

use App\Models\Affiliate;
use App\Models\AffiliateCommission;
use App\Models\AffiliateReferral;
use App\Services\AffiliateService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AffiliadoController extends Controller
{
    public function __construct(protected AffiliateService $affiliateService) {}

    public function painel()
    {
        $affiliate = Affiliate::where('user_id', Auth::id())->first();

        if (!$affiliate) {
            return redirect()->route('afiliados.solicitar');
        }

        if ($affiliate->status === 'pendente') {
            return view('site.afiliados.painel', compact('affiliate'));
        }

        $stats = [
            'total_indicacoes'     => $affiliate->referrals()->count(),
            'convertidas'          => $affiliate->referrals()->where('status', 'convertido')->count(),
            'comissoes_pendentes'  => $affiliate->commissions()->where('status', 'pendente')->sum('valor'),
            'comissoes_pagas'      => $affiliate->commissions()->where('status', 'pago')->sum('valor'),
        ];

        $ultimasIndicacoes = $affiliate->referrals()
            ->with('referredUser')
            ->latest()
            ->limit(5)
            ->get();

        $ultimasComissoes = $affiliate->commissions()
            ->with('pedido')
            ->latest()
            ->limit(5)
            ->get();

        $linkIndicacao = url('/') . '/?ref=' . $affiliate->codigo;

        return view('site.afiliados.painel', compact('affiliate', 'stats', 'ultimasIndicacoes', 'ultimasComissoes', 'linkIndicacao'));
    }

    public function solicitar()
    {
        if (Affiliate::where('user_id', Auth::id())->exists()) {
            return redirect()->route('afiliados.painel');
        }

        return view('site.afiliados.solicitar');
    }

    public function registrar(Request $request)
    {
        if (Affiliate::where('user_id', Auth::id())->exists()) {
            return redirect()->route('afiliados.painel');
        }

        $request->validate([
            'pix_key'   => 'nullable|string|max:255',
            'bank_info' => 'nullable|string|max:1000',
        ]);

        Affiliate::create([
            'user_id'          => Auth::id(),
            'codigo'           => $this->affiliateService->generateUniqueCode(),
            'commission_type'  => 'percent',
            'commission_value' => null,
            'status'           => 'pendente',
            'pix_key'          => $request->pix_key,
            'bank_info'        => $request->bank_info,
        ]);

        return redirect()->route('afiliados.painel')
            ->with('success', 'Solicitação enviada! Aguarde aprovação do administrador.');
    }

    public function indicacoes()
    {
        $affiliate = Affiliate::where('user_id', Auth::id())->firstOrFail();

        $indicacoes = $affiliate->referrals()
            ->with('referredUser')
            ->latest()
            ->paginate(20);

        return view('site.afiliados.indicacoes', compact('affiliate', 'indicacoes'));
    }

    public function comissoes()
    {
        $affiliate = Affiliate::where('user_id', Auth::id())->firstOrFail();

        $comissoes = $affiliate->commissions()
            ->with('pedido')
            ->latest()
            ->paginate(20);

        $totais = [
            'pendente' => $affiliate->commissions()->where('status', 'pendente')->sum('valor'),
            'aprovado' => $affiliate->commissions()->where('status', 'aprovado')->sum('valor'),
            'pago'     => $affiliate->commissions()->where('status', 'pago')->sum('valor'),
        ];

        return view('site.afiliados.comissoes', compact('affiliate', 'comissoes', 'totais'));
    }
}
```

- [ ] **Step 4: Run tests**

```bash
docker exec laravel-app php artisan test tests/Feature/AffiliadoControllerTest.php
```

Expected: 9 tests, 9 passed.

- [ ] **Step 5: Commit**

```bash
git add app/Http/Controllers/AffiliadoController.php tests/Feature/AffiliadoControllerTest.php
git commit -m "feat: add AffiliadoController with affiliate panel routes"
```

---

## Task 11: User Panel Views

**Files:**
- Create: `resources/views/site/afiliados/painel.blade.php`
- Create: `resources/views/site/afiliados/solicitar.blade.php`
- Create: `resources/views/site/afiliados/indicacoes.blade.php`
- Create: `resources/views/site/afiliados/comissoes.blade.php`
- Create: `public/js/afiliados.js`

- [ ] **Step 1: Create painel.blade.php**

```blade
{{-- resources/views/site/afiliados/painel.blade.php --}}
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Painel Afiliado — JFXTECH</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <link rel="stylesheet" href="{{ asset('css/site-styles.css') }}">
</head>
<body class="bg-white text-black font-mono">
@include('includes.header')

<main class="max-w-4xl mx-auto px-4 py-10">
    <h1 class="text-2xl font-bold uppercase tracking-widest mb-8">Painel Afiliado</h1>

    @if(session('success'))
        <div class="border border-black bg-white p-4 mb-6 text-sm">{{ session('success') }}</div>
    @endif

    @if($affiliate->status === 'pendente')
        <div class="border border-black p-8 text-center">
            <p class="text-sm uppercase tracking-widest text-gray-500 mb-2">Status</p>
            <p class="text-lg font-bold uppercase">Solicitação em análise</p>
            <p class="text-sm text-gray-500 mt-2">O administrador avaliará sua solicitação em breve.</p>
        </div>
    @else
        {{-- Referral link --}}
        <div class="border border-black p-6 mb-8">
            <p class="text-xs uppercase tracking-widest text-gray-500 mb-2">Seu link de indicação</p>
            <div class="flex items-center gap-3">
                <input id="linkAfiliado" type="text" readonly
                    value="{{ $linkIndicacao }}"
                    class="flex-1 border border-gray-300 px-3 py-2 text-sm font-mono bg-gray-50 focus:outline-none">
                <button onclick="copiarLink()" class="border border-black px-4 py-2 text-xs uppercase tracking-widest hover:bg-black hover:text-white transition-colors">
                    Copiar
                </button>
            </div>
            <p class="text-xs text-gray-400 mt-2">Código: <strong>{{ $affiliate->codigo }}</strong></p>
        </div>

        {{-- Stats cards --}}
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-8">
            @foreach([
                ['Total indicações', $stats['total_indicacoes']],
                ['Convertidas', $stats['convertidas']],
                ['Comissões pendentes', 'R$ ' . number_format($stats['comissoes_pendentes'], 2, ',', '.')],
                ['Comissões pagas', 'R$ ' . number_format($stats['comissoes_pagas'], 2, ',', '.')],
            ] as [$label, $val])
            <div class="border border-black p-4 text-center">
                <p class="text-xs uppercase tracking-widest text-gray-500 mb-1">{{ $label }}</p>
                <p class="text-xl font-bold">{{ $val }}</p>
            </div>
            @endforeach
        </div>

        {{-- Recent referrals --}}
        <div class="grid md:grid-cols-2 gap-6">
            <div>
                <div class="flex items-center justify-between mb-3">
                    <h2 class="text-xs uppercase tracking-widest font-bold">Últimas indicações</h2>
                    <a href="{{ route('afiliados.indicacoes') }}" class="text-xs underline">Ver todas</a>
                </div>
                @forelse($ultimasIndicacoes as $ref)
                <div class="border-b border-gray-100 py-2 flex justify-between items-center text-sm">
                    <span class="text-gray-700">{{ substr($ref->referredUser->name, 0, 3) }}***</span>
                    <span class="text-xs uppercase tracking-widest {{ $ref->status === 'convertido' ? 'text-black' : 'text-gray-400' }}">
                        {{ $ref->status }}
                    </span>
                </div>
                @empty
                <p class="text-sm text-gray-400">Nenhuma indicação ainda.</p>
                @endforelse
            </div>

            <div>
                <div class="flex items-center justify-between mb-3">
                    <h2 class="text-xs uppercase tracking-widest font-bold">Últimas comissões</h2>
                    <a href="{{ route('afiliados.comissoes') }}" class="text-xs underline">Ver todas</a>
                </div>
                @forelse($ultimasComissoes as $com)
                <div class="border-b border-gray-100 py-2 flex justify-between items-center text-sm">
                    <span>R$ {{ number_format($com->valor, 2, ',', '.') }}</span>
                    <span class="text-xs uppercase tracking-widest {{ $com->status === 'pago' ? 'text-black' : 'text-gray-400' }}">
                        {{ $com->status }}
                    </span>
                </div>
                @empty
                <p class="text-sm text-gray-400">Nenhuma comissão ainda.</p>
                @endforelse
            </div>
        </div>
    @endif
</main>

@include('includes.footer')
<script src="{{ asset('js/afiliados.js') }}"></script>
</body>
</html>
```

- [ ] **Step 2: Create solicitar.blade.php**

```blade
{{-- resources/views/site/afiliados/solicitar.blade.php --}}
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tornar-se Afiliado — JFXTECH</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <link rel="stylesheet" href="{{ asset('css/site-styles.css') }}">
</head>
<body class="bg-white text-black font-mono">
@include('includes.header')

<main class="max-w-xl mx-auto px-4 py-10">
    <h1 class="text-2xl font-bold uppercase tracking-widest mb-2">Tornar-se Afiliado</h1>
    <p class="text-sm text-gray-500 mb-8">Indique clientes e ganhe comissão nas vendas geradas.</p>

    @if($errors->any())
        <div class="border border-black p-4 mb-6 text-sm">
            @foreach($errors->all() as $error)
                <p>{{ $error }}</p>
            @endforeach
        </div>
    @endif

    <form method="POST" action="{{ route('afiliados.registrar') }}" class="space-y-5">
        @csrf
        <div>
            <label class="block text-xs uppercase tracking-widest mb-1">Chave PIX (opcional)</label>
            <input type="text" name="pix_key" value="{{ old('pix_key') }}"
                placeholder="CPF, e-mail, telefone ou chave aleatória"
                class="w-full border border-black px-3 py-2 text-sm focus:outline-none focus:ring-1 focus:ring-black">
        </div>
        <div>
            <label class="block text-xs uppercase tracking-widest mb-1">Dados bancários (opcional)</label>
            <textarea name="bank_info" rows="3"
                placeholder="Banco, agência, conta..."
                class="w-full border border-black px-3 py-2 text-sm focus:outline-none focus:ring-1 focus:ring-black">{{ old('bank_info') }}</textarea>
        </div>
        <button type="submit"
            class="w-full border border-black bg-black text-white py-3 text-xs uppercase tracking-widest hover:bg-white hover:text-black transition-colors">
            Enviar Solicitação
        </button>
    </form>
</main>

@include('includes.footer')
</body>
</html>
```

- [ ] **Step 3: Create indicacoes.blade.php**

```blade
{{-- resources/views/site/afiliados/indicacoes.blade.php --}}
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Minhas Indicações — JFXTECH</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <link rel="stylesheet" href="{{ asset('css/site-styles.css') }}">
</head>
<body class="bg-white text-black font-mono">
@include('includes.header')

<main class="max-w-4xl mx-auto px-4 py-10">
    <div class="flex items-center justify-between mb-8">
        <h1 class="text-2xl font-bold uppercase tracking-widest">Minhas Indicações</h1>
        <a href="{{ route('afiliados.painel') }}" class="text-xs underline">← Painel</a>
    </div>

    @if($indicacoes->isEmpty())
        <p class="text-sm text-gray-400">Nenhuma indicação registrada ainda.</p>
    @else
        <table class="w-full text-sm border-collapse">
            <thead>
                <tr class="border-b border-black">
                    <th class="text-left py-2 text-xs uppercase tracking-widest">Usuário</th>
                    <th class="text-left py-2 text-xs uppercase tracking-widest">Data</th>
                    <th class="text-left py-2 text-xs uppercase tracking-widest">Status</th>
                    <th class="text-left py-2 text-xs uppercase tracking-widest">Convertido em</th>
                </tr>
            </thead>
            <tbody>
                @foreach($indicacoes as $ref)
                <tr class="border-b border-gray-100 hover:bg-gray-50">
                    <td class="py-2">{{ substr($ref->referredUser->name, 0, 3) }}***</td>
                    <td class="py-2">{{ $ref->created_at->format('d/m/Y') }}</td>
                    <td class="py-2 uppercase text-xs tracking-widest">{{ $ref->status }}</td>
                    <td class="py-2">{{ $ref->converted_at?->format('d/m/Y') ?? '—' }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
        <div class="mt-4">{{ $indicacoes->links() }}</div>
    @endif
</main>

@include('includes.footer')
</body>
</html>
```

- [ ] **Step 4: Create comissoes.blade.php (user)**

```blade
{{-- resources/views/site/afiliados/comissoes.blade.php --}}
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Minhas Comissões — JFXTECH</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <link rel="stylesheet" href="{{ asset('css/site-styles.css') }}">
</head>
<body class="bg-white text-black font-mono">
@include('includes.header')

<main class="max-w-4xl mx-auto px-4 py-10">
    <div class="flex items-center justify-between mb-6">
        <h1 class="text-2xl font-bold uppercase tracking-widest">Minhas Comissões</h1>
        <a href="{{ route('afiliados.painel') }}" class="text-xs underline">← Painel</a>
    </div>

    <div class="grid grid-cols-3 gap-4 mb-8">
        @foreach([
            ['Pendente', $totais['pendente']],
            ['Aprovado', $totais['aprovado']],
            ['Pago', $totais['pago']],
        ] as [$label, $val])
        <div class="border border-black p-4 text-center">
            <p class="text-xs uppercase tracking-widest text-gray-500 mb-1">{{ $label }}</p>
            <p class="text-lg font-bold">R$ {{ number_format($val, 2, ',', '.') }}</p>
        </div>
        @endforeach
    </div>

    @if($comissoes->isEmpty())
        <p class="text-sm text-gray-400">Nenhuma comissão registrada ainda.</p>
    @else
        <table class="w-full text-sm border-collapse">
            <thead>
                <tr class="border-b border-black">
                    <th class="text-left py-2 text-xs uppercase tracking-widest">Data</th>
                    <th class="text-left py-2 text-xs uppercase tracking-widest">Pedido</th>
                    <th class="text-left py-2 text-xs uppercase tracking-widest">Valor</th>
                    <th class="text-left py-2 text-xs uppercase tracking-widest">Status</th>
                    <th class="text-left py-2 text-xs uppercase tracking-widest">Elegível em</th>
                </tr>
            </thead>
            <tbody>
                @foreach($comissoes as $com)
                <tr class="border-b border-gray-100 hover:bg-gray-50">
                    <td class="py-2">{{ $com->created_at->format('d/m/Y') }}</td>
                    <td class="py-2">#{{ $com->pedido_id }}</td>
                    <td class="py-2 font-bold">R$ {{ number_format($com->valor, 2, ',', '.') }}</td>
                    <td class="py-2 uppercase text-xs tracking-widest">{{ $com->status }}</td>
                    <td class="py-2">{{ $com->eligible_at->format('d/m/Y') }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
        <div class="mt-4">{{ $comissoes->links() }}</div>
    @endif
</main>

@include('includes.footer')
</body>
</html>
```

- [ ] **Step 5: Create public/js/afiliados.js**

```javascript
// public/js/afiliados.js
function copiarLink() {
    const input = document.getElementById('linkAfiliado');
    if (!input) return;

    navigator.clipboard.writeText(input.value).then(() => {
        const btn = document.querySelector('[onclick="copiarLink()"]');
        if (btn) {
            const original = btn.textContent;
            btn.textContent = 'Copiado!';
            setTimeout(() => { btn.textContent = original; }, 2000);
        }
    }).catch(() => {
        input.select();
        document.execCommand('copy');
    });
}
```

- [ ] **Step 6: Build assets**

```bash
cd /var/www/html && npm run build
docker exec laravel-app php artisan view:clear
```

- [ ] **Step 7: Commit**

```bash
git add resources/views/site/afiliados/ public/js/afiliados.js
git commit -m "feat: add affiliate user panel views"
```

---

## Task 12: AdminAfiliadoController — CRUD

**Files:**
- Create: `app/Http/Controllers/AdminAfiliadoController.php`
- Create: `tests/Feature/AdminAfiliadoControllerTest.php`

- [ ] **Step 1: Write failing tests**

```php
<?php
// tests/Feature/AdminAfiliadoControllerTest.php
namespace Tests\Feature;

use App\Models\Affiliate;
use App\Models\AffiliateCommission;
use App\Models\AffiliateReferral;
use App\Models\AffiliateSetting;
use App\Models\Pedido;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminAfiliadoControllerTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->create(['admin' => true]);
    }

    public function test_index_requires_auth(): void
    {
        $this->get(route('admin.afiliados.index'))
             ->assertRedirect(route('site.login'));
    }

    public function test_index_returns_ok_for_authenticated_user(): void
    {
        $this->actingAs($this->admin)
             ->get(route('admin.afiliados.index'))
             ->assertOk();
    }

    public function test_show_returns_json_for_affiliate(): void
    {
        $affiliate = Affiliate::factory()->create();

        $this->actingAs($this->admin)
             ->getJson(route('admin.afiliados.show', $affiliate->id))
             ->assertOk()
             ->assertJsonPath('id', $affiliate->id);
    }

    public function test_aprovar_sets_status_to_ativo(): void
    {
        $affiliate = Affiliate::factory()->create(['status' => 'pendente', 'approved_at' => null]);

        $this->actingAs($this->admin)
             ->post(route('admin.afiliados.aprovar', $affiliate->id))
             ->assertRedirect();

        $this->assertDatabaseHas('affiliates', [
            'id'     => $affiliate->id,
            'status' => 'ativo',
        ]);
        $this->assertNotNull($affiliate->fresh()->approved_at);
    }

    public function test_suspender_sets_status_to_inativo(): void
    {
        $affiliate = Affiliate::factory()->create(['status' => 'ativo']);

        $this->actingAs($this->admin)
             ->post(route('admin.afiliados.suspender', $affiliate->id))
             ->assertRedirect();

        $this->assertDatabaseHas('affiliates', [
            'id'     => $affiliate->id,
            'status' => 'inativo',
        ]);
    }

    public function test_editar_comissao_updates_commission(): void
    {
        $affiliate = Affiliate::factory()->create();

        $this->actingAs($this->admin)
             ->post(route('admin.afiliados.comissao', $affiliate->id), [
                 'commission_type'  => 'percent',
                 'commission_value' => '8.50',
             ])
             ->assertRedirect();

        $this->assertDatabaseHas('affiliates', [
            'id'               => $affiliate->id,
            'commission_type'  => 'percent',
            'commission_value' => 8.50,
        ]);
    }

    public function test_comissoes_index_returns_ok(): void
    {
        $this->actingAs($this->admin)
             ->get(route('admin.afiliados.comissoes'))
             ->assertOk();
    }

    public function test_bulk_comissoes_aprovar_changes_status(): void
    {
        $affiliateUser = User::factory()->create();
        $affiliate = Affiliate::factory()->create(['user_id' => $affiliateUser->id]);
        $buyer = User::factory()->create();
        $referral = AffiliateReferral::create([
            'affiliate_id' => $affiliate->id, 'referred_user_id' => $buyer->id, 'status' => 'convertido',
        ]);
        $pedido = Pedido::create([
            'user_id' => $buyer->id, 'status' => 'pago', 'valor_total' => 100.00,
            'frete_tipo' => 'pac', 'frete_valor' => 0,
        ]);
        $commission = AffiliateCommission::create([
            'affiliate_id' => $affiliate->id,
            'referral_id'  => $referral->id,
            'pedido_id'    => $pedido->id,
            'valor'        => 5.00,
            'status'       => 'pendente',
            'eligible_at'  => now()->subDay(),
        ]);

        $this->actingAs($this->admin)
             ->post(route('admin.afiliados.comissoes.bulk'), [
                 'ids'    => [$commission->id],
                 'action' => 'aprovar',
             ])
             ->assertRedirect();

        $this->assertDatabaseHas('affiliate_commissions', [
            'id'     => $commission->id,
            'status' => 'aprovado',
        ]);
    }

    public function test_bulk_comissoes_pago_changes_status(): void
    {
        $affiliateUser = User::factory()->create();
        $affiliate = Affiliate::factory()->create(['user_id' => $affiliateUser->id]);
        $buyer = User::factory()->create();
        $referral = AffiliateReferral::create([
            'affiliate_id' => $affiliate->id, 'referred_user_id' => $buyer->id, 'status' => 'convertido',
        ]);
        $pedido = Pedido::create([
            'user_id' => $buyer->id, 'status' => 'pago', 'valor_total' => 100.00,
            'frete_tipo' => 'pac', 'frete_valor' => 0,
        ]);
        $commission = AffiliateCommission::create([
            'affiliate_id' => $affiliate->id,
            'referral_id'  => $referral->id,
            'pedido_id'    => $pedido->id,
            'valor'        => 5.00,
            'status'       => 'aprovado',
            'eligible_at'  => now()->subDay(),
        ]);

        $this->actingAs($this->admin)
             ->post(route('admin.afiliados.comissoes.bulk'), [
                 'ids'    => [$commission->id],
                 'action' => 'marcar_pago',
             ])
             ->assertRedirect();

        $this->assertDatabaseHas('affiliate_commissions', [
            'id'     => $commission->id,
            'status' => 'pago',
        ]);
        $this->assertNotNull($commission->fresh()->paid_at);
    }

    public function test_configuracoes_returns_ok(): void
    {
        AffiliateSetting::create(['key' => 'commission_percent_default', 'value' => '5.00']);
        AffiliateSetting::create(['key' => 'cookie_days', 'value' => '30']);
        AffiliateSetting::create(['key' => 'grace_period_days', 'value' => '30']);

        $this->actingAs($this->admin)
             ->get(route('admin.afiliados.configuracoes'))
             ->assertOk();
    }

    public function test_salvar_configuracoes_updates_settings(): void
    {
        AffiliateSetting::create(['key' => 'commission_percent_default', 'value' => '5.00']);
        AffiliateSetting::create(['key' => 'cookie_days', 'value' => '30']);
        AffiliateSetting::create(['key' => 'grace_period_days', 'value' => '30']);

        $this->actingAs($this->admin)
             ->post(route('admin.afiliados.configuracoes.salvar'), [
                 'commission_percent_default' => '8.00',
                 'cookie_days'               => '60',
                 'grace_period_days'         => '15',
             ])
             ->assertRedirect();

        $this->assertDatabaseHas('affiliate_settings', ['key' => 'commission_percent_default', 'value' => '8.00']);
        $this->assertDatabaseHas('affiliate_settings', ['key' => 'cookie_days', 'value' => '60']);
        $this->assertDatabaseHas('affiliate_settings', ['key' => 'grace_period_days', 'value' => '15']);
    }
}
```

- [ ] **Step 2: Run to verify failure**

```bash
docker exec laravel-app php artisan test tests/Feature/AdminAfiliadoControllerTest.php
```

Expected: `Error: Target class [App\Http\Controllers\AdminAfiliadoController] does not exist`

- [ ] **Step 3: Create AdminAfiliadoController**

```php
<?php
// app/Http/Controllers/AdminAfiliadoController.php
namespace App\Http\Controllers;

use App\Models\Affiliate;
use App\Models\AffiliateCommission;
use App\Models\AffiliateSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AdminAfiliadoController extends Controller
{
    private function checkAuth(): ?object
    {
        if (!Auth::check()) {
            return redirect()->route('site.login');
        }
        return null;
    }

    public function index()
    {
        if ($r = $this->checkAuth()) return $r;

        $afiliados = Affiliate::with('user')
            ->withCount('referrals')
            ->withCount(['referrals as convertidas_count' => fn($q) => $q->where('status', 'convertido')])
            ->orderByDesc('created_at')
            ->paginate(30);

        $metrics = [
            'ativos'               => Affiliate::where('status', 'ativo')->count(),
            'pendentes'            => Affiliate::where('status', 'pendente')->count(),
            'indicacoes_hoje'      => \App\Models\AffiliateReferral::whereDate('created_at', today())->count(),
            'comissoes_pendentes'  => AffiliateCommission::where('status', 'pendente')->sum('valor'),
            'comissoes_pagas'      => AffiliateCommission::where('status', 'pago')->sum('valor'),
        ];

        return view('admin.afiliados.index', compact('afiliados', 'metrics'));
    }

    public function stream()
    {
        if (!Auth::check()) {
            return response()->json(['error' => 'Não autorizado'], 401);
        }

        return response()->stream(function () {
            set_time_limit(0);
            while (true) {
                $data = [
                    'afiliados_ativos'          => Affiliate::where('status', 'ativo')->count(),
                    'indicacoes_hoje'            => \App\Models\AffiliateReferral::whereDate('created_at', today())->count(),
                    'comissoes_pendentes_valor'  => (float) AffiliateCommission::where('status', 'pendente')->sum('valor'),
                    'comissoes_pagas_valor'      => (float) AffiliateCommission::where('status', 'pago')->sum('valor'),
                ];
                echo 'data: ' . json_encode($data) . "\n\n";
                ob_flush();
                flush();
                if (connection_aborted()) break;
                sleep(30);
            }
        }, 200, [
            'Content-Type'       => 'text/event-stream',
            'Cache-Control'      => 'no-cache',
            'X-Accel-Buffering'  => 'no',
        ]);
    }

    public function show(int $id)
    {
        if (!Auth::check()) {
            return response()->json(['error' => 'Não autorizado'], 401);
        }

        $affiliate = Affiliate::with('user')->findOrFail($id);

        return response()->json([
            'id'               => $affiliate->id,
            'nome'             => $affiliate->user->name,
            'email'            => $affiliate->user->email,
            'codigo'           => $affiliate->codigo,
            'commission_type'  => $affiliate->commission_type,
            'commission_value' => $affiliate->commission_value,
            'status'           => $affiliate->status,
            'pix_key'          => $affiliate->pix_key,
            'bank_info'        => $affiliate->bank_info,
            'approved_at'      => $affiliate->approved_at?->format('d/m/Y'),
        ]);
    }

    public function aprovar(int $id)
    {
        if ($r = $this->checkAuth()) return $r;

        $affiliate = Affiliate::findOrFail($id);
        $affiliate->update(['status' => 'ativo', 'approved_at' => now()]);

        return redirect()->route('admin.afiliados.index')
            ->with('success', "Afiliado {$affiliate->user->name} aprovado.");
    }

    public function suspender(int $id)
    {
        if ($r = $this->checkAuth()) return $r;

        $affiliate = Affiliate::findOrFail($id);
        $affiliate->update(['status' => 'inativo']);

        return redirect()->route('admin.afiliados.index')
            ->with('success', "Afiliado {$affiliate->user->name} suspenso.");
    }

    public function editarComissao(Request $request, int $id)
    {
        if ($r = $this->checkAuth()) return $r;

        $affiliate = Affiliate::findOrFail($id);

        $request->validate([
            'commission_type'  => 'required|in:percent,fixed',
            'commission_value' => 'nullable|numeric|min:0|max:99999',
        ]);

        $affiliate->update([
            'commission_type'  => $request->commission_type,
            'commission_value' => $request->commission_value ?: null,
        ]);

        return redirect()->route('admin.afiliados.index')
            ->with('success', 'Comissão atualizada.');
    }

    public function comissoes(Request $request)
    {
        if ($r = $this->checkAuth()) return $r;

        $query = AffiliateCommission::with(['affiliate.user', 'pedido'])
            ->orderByDesc('created_at');

        if ($request->status) {
            $query->where('status', $request->status);
        }

        $comissoes = $query->paginate(30);

        $totais = [
            'pendente' => AffiliateCommission::where('status', 'pendente')->sum('valor'),
            'aprovado' => AffiliateCommission::where('status', 'aprovado')->sum('valor'),
            'pago'     => AffiliateCommission::where('status', 'pago')->sum('valor'),
        ];

        return view('admin.afiliados.comissoes', compact('comissoes', 'totais'));
    }

    public function bulkComissoes(Request $request)
    {
        if ($r = $this->checkAuth()) return $r;

        $request->validate([
            'ids'    => 'required|array',
            'ids.*'  => 'integer',
            'action' => 'required|in:aprovar,rejeitar,marcar_pago',
        ]);

        $commissions = AffiliateCommission::whereIn('id', $request->ids)->get();

        foreach ($commissions as $commission) {
            match ($request->action) {
                'aprovar'     => $commission->update(['status' => 'aprovado']),
                'rejeitar'    => $commission->update(['status' => 'rejeitado']),
                'marcar_pago' => $commission->update(['status' => 'pago', 'paid_at' => now()]),
            };
        }

        return redirect()->route('admin.afiliados.comissoes')
            ->with('success', count($commissions) . ' comissão(ões) atualizada(s).');
    }

    public function configuracoes()
    {
        if ($r = $this->checkAuth()) return $r;

        $settings = AffiliateSetting::whereIn('key', [
            'commission_percent_default',
            'cookie_days',
            'grace_period_days',
        ])->pluck('value', 'key');

        return view('admin.afiliados.configuracoes', compact('settings'));
    }

    public function salvarConfiguracoes(Request $request)
    {
        if ($r = $this->checkAuth()) return $r;

        $request->validate([
            'commission_percent_default' => 'required|numeric|min:0|max:100',
            'cookie_days'               => 'required|integer|min:1|max:365',
            'grace_period_days'         => 'required|integer|min:0|max:365',
        ]);

        foreach (['commission_percent_default', 'cookie_days', 'grace_period_days'] as $key) {
            AffiliateSetting::where('key', $key)
                ->update(['value' => $request->$key]);
        }

        return redirect()->route('admin.afiliados.configuracoes')
            ->with('success', 'Configurações salvas.');
    }
}
```

- [ ] **Step 4: Run tests**

```bash
docker exec laravel-app php artisan test tests/Feature/AdminAfiliadoControllerTest.php
```

Expected: 13 tests, 13 passed.

- [ ] **Step 5: Commit**

```bash
git add app/Http/Controllers/AdminAfiliadoController.php tests/Feature/AdminAfiliadoControllerTest.php
git commit -m "feat: add AdminAfiliadoController with CRUD and bulk commission management"
```

---

## Task 13: Admin Views + JS

**Files:**
- Create: `resources/views/admin/afiliados/index.blade.php`
- Create: `resources/views/admin/afiliados/comissoes.blade.php`
- Create: `resources/views/admin/afiliados/configuracoes.blade.php`
- Create: `public/js/afiliados-admin.js`

- [ ] **Step 1: Create admin/afiliados/index.blade.php**

```blade
{{-- resources/views/admin/afiliados/index.blade.php --}}
@extends('includes.header-admin')
@section('title', 'Afiliados')
@section('content')
<div class="p-4 md:p-6 space-y-6">

    {{-- Header --}}
    <div class="flex items-center justify-between">
        <h1 class="font-mono text-lg font-bold uppercase tracking-widest">Afiliados</h1>
        <a href="{{ route('admin.afiliados.configuracoes') }}"
           class="border border-black px-3 py-1.5 font-mono text-xs uppercase tracking-widest hover:bg-black hover:text-white transition-colors">
            Configurações
        </a>
    </div>

    @if(session('success'))
        <div class="border border-black bg-white p-4 font-mono text-sm">{{ session('success') }}</div>
    @endif

    {{-- SSE Metrics Cards --}}
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
        <div class="border border-black p-4 text-center">
            <p class="font-mono text-[10px] uppercase tracking-widest text-gray-500 mb-1">Afiliados ativos</p>
            <p id="sse-ativos" class="font-mono text-2xl font-bold">{{ $metrics['ativos'] }}</p>
        </div>
        <div class="border border-black p-4 text-center">
            <p class="font-mono text-[10px] uppercase tracking-widest text-gray-500 mb-1">Indicações hoje</p>
            <p id="sse-indicacoes-hoje" class="font-mono text-2xl font-bold">{{ $metrics['indicacoes_hoje'] }}</p>
        </div>
        <div class="border border-black p-4 text-center">
            <p class="font-mono text-[10px] uppercase tracking-widest text-gray-500 mb-1">Comissões pendentes</p>
            <p id="sse-pendentes" class="font-mono text-2xl font-bold">R$ {{ number_format($metrics['comissoes_pendentes'], 2, ',', '.') }}</p>
        </div>
        <div class="border border-black p-4 text-center">
            <p class="font-mono text-[10px] uppercase tracking-widest text-gray-500 mb-1">Comissões pagas</p>
            <p id="sse-pagas" class="font-mono text-2xl font-bold">R$ {{ number_format($metrics['comissoes_pagas'], 2, ',', '.') }}</p>
        </div>
    </div>

    {{-- Pending affiliates section --}}
    @if($metrics['pendentes'] > 0)
    <div class="border border-black p-4 bg-gray-50 font-mono text-sm">
        <strong>{{ $metrics['pendentes'] }}</strong> afiliado(s) aguardando aprovação.
    </div>
    @endif

    {{-- Affiliates table --}}
    <div class="border border-black overflow-x-auto">
        <table class="w-full text-xs font-mono border-collapse">
            <thead>
                <tr class="border-b border-black bg-gray-50">
                    <th class="text-left px-4 py-2 uppercase tracking-widest">Nome</th>
                    <th class="text-left px-4 py-2 uppercase tracking-widest">Código</th>
                    <th class="text-left px-4 py-2 uppercase tracking-widest">Indicações</th>
                    <th class="text-left px-4 py-2 uppercase tracking-widest">Comissão</th>
                    <th class="text-left px-4 py-2 uppercase tracking-widest">Status</th>
                    <th class="text-left px-4 py-2 uppercase tracking-widest">Ações</th>
                </tr>
            </thead>
            <tbody>
                @foreach($afiliados as $aff)
                <tr class="border-b border-gray-100 hover:bg-gray-50">
                    <td class="px-4 py-2">
                        {{ $aff->user->name }}<br>
                        <span class="text-gray-400">{{ $aff->user->email }}</span>
                    </td>
                    <td class="px-4 py-2 font-bold">{{ $aff->codigo }}</td>
                    <td class="px-4 py-2">{{ $aff->referrals_count }} ({{ $aff->convertidas_count }} conv.)</td>
                    <td class="px-4 py-2">
                        @if($aff->commission_value)
                            {{ $aff->commission_type === 'percent' ? $aff->commission_value . '%' : 'R$ ' . number_format($aff->commission_value, 2, ',', '.') }}
                        @else
                            <span class="text-gray-400">Global</span>
                        @endif
                    </td>
                    <td class="px-4 py-2">
                        <span class="uppercase tracking-widest {{ $aff->status === 'ativo' ? 'text-black' : 'text-gray-400' }}">
                            {{ $aff->status }}
                        </span>
                    </td>
                    <td class="px-4 py-2 flex gap-2 flex-wrap">
                        @if($aff->status === 'pendente')
                        <form method="POST" action="{{ route('admin.afiliados.aprovar', $aff->id) }}">
                            @csrf
                            <button class="border border-black px-2 py-1 text-[10px] uppercase tracking-widest hover:bg-black hover:text-white transition-colors">Aprovar</button>
                        </form>
                        @endif
                        @if($aff->status === 'ativo')
                        <form method="POST" action="{{ route('admin.afiliados.suspender', $aff->id) }}">
                            @csrf
                            <button class="border border-black px-2 py-1 text-[10px] uppercase tracking-widest hover:bg-black hover:text-white transition-colors">Suspender</button>
                        </form>
                        @endif
                        <button onclick="abrirModalComissao({{ $aff->id }}, '{{ $aff->commission_type }}', '{{ $aff->commission_value ?? '' }}')"
                            class="border border-black px-2 py-1 text-[10px] uppercase tracking-widest hover:bg-black hover:text-white transition-colors">
                            Comissão
                        </button>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <div class="font-mono">{{ $afiliados->links() }}</div>

    <a href="{{ route('admin.afiliados.comissoes') }}"
       class="inline-block border border-black px-4 py-2 font-mono text-xs uppercase tracking-widest hover:bg-black hover:text-white transition-colors">
        Gerenciar Comissões →
    </a>
</div>

{{-- Modal: Edit commission --}}
<div id="modalComissao" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-black/50">
    <div class="bg-white border border-black p-6 w-full max-w-sm font-mono">
        <h2 class="text-xs uppercase tracking-widest font-bold mb-4">Editar Comissão</h2>
        <form id="formComissao" method="POST">
            @csrf
            <div class="mb-3">
                <label class="block text-[10px] uppercase tracking-widest mb-1">Tipo</label>
                <select id="mc-type" name="commission_type" class="w-full border border-black px-2 py-1 text-xs">
                    <option value="percent">Percentual (%)</option>
                    <option value="fixed">Fixo (R$)</option>
                </select>
            </div>
            <div class="mb-4">
                <label class="block text-[10px] uppercase tracking-widest mb-1">Valor (vazio = global)</label>
                <input id="mc-value" type="number" step="0.01" name="commission_value"
                    class="w-full border border-black px-2 py-1 text-xs">
            </div>
            <div class="flex gap-2">
                <button type="submit" class="flex-1 border border-black bg-black text-white py-2 text-[10px] uppercase tracking-widest hover:bg-white hover:text-black transition-colors">Salvar</button>
                <button type="button" onclick="fecharModalComissao()" class="flex-1 border border-black py-2 text-[10px] uppercase tracking-widest hover:bg-black hover:text-white transition-colors">Cancelar</button>
            </div>
        </form>
    </div>
</div>

@endsection
@push('scripts')
<script src="{{ asset('js/afiliados-admin.js') }}?v={{ time() }}"></script>
<script>
    window.routes = window.routes || {};
    window.routes.adminAfiliadosComissao = '{{ route("admin.afiliados.comissao", ":id") }}';
    window.routes.adminAfiliadosStream   = '{{ route("admin.afiliados.stream") }}';
</script>
@endpush
```

> **Note:** The `@push('scripts')` stack must be yielded in `header-admin.blade.php`. Check if `@stack('scripts')` already exists; if not, add it before `</body>`. If the layout doesn't support stacks, add the `<script>` tags directly at the bottom of this view instead.

- [ ] **Step 2: Check if header-admin.blade.php has @stack('scripts')**

```bash
grep -n "stack\|yield" /var/www/html/resources/views/includes/header-admin.blade.php
```

If `@stack('scripts')` is NOT present, replace `@push('scripts')...<script>...@endpush` at the bottom of the view with direct `<script>` tags:

```blade
<script src="{{ asset('js/afiliados-admin.js') }}?v={{ time() }}"></script>
<script>
    window.routes = window.routes || {};
    window.routes.adminAfiliadosComissao = '{{ route("admin.afiliados.comissao", ":id") }}';
    window.routes.adminAfiliadosStream   = '{{ route("admin.afiliados.stream") }}';
</script>
```

If it IS present, keep `@push('scripts')`.

- [ ] **Step 3: Create admin/afiliados/comissoes.blade.php**

```blade
{{-- resources/views/admin/afiliados/comissoes.blade.php --}}
@extends('includes.header-admin')
@section('title', 'Comissões — Afiliados')
@section('content')
<div class="p-4 md:p-6 space-y-6">

    <div class="flex items-center justify-between">
        <h1 class="font-mono text-lg font-bold uppercase tracking-widest">Comissões</h1>
        <a href="{{ route('admin.afiliados.index') }}" class="font-mono text-xs underline">← Afiliados</a>
    </div>

    @if(session('success'))
        <div class="border border-black bg-white p-4 font-mono text-sm">{{ session('success') }}</div>
    @endif

    {{-- Totals --}}
    <div class="grid grid-cols-3 gap-4">
        @foreach([
            ['Pendente', $totais['pendente']],
            ['Aprovado', $totais['aprovado']],
            ['Pago', $totais['pago']],
        ] as [$label, $val])
        <div class="border border-black p-4 text-center font-mono">
            <p class="text-[10px] uppercase tracking-widest text-gray-500 mb-1">{{ $label }}</p>
            <p class="text-xl font-bold">R$ {{ number_format($val, 2, ',', '.') }}</p>
        </div>
        @endforeach
    </div>

    {{-- Filter --}}
    <form method="GET" action="{{ route('admin.afiliados.comissoes') }}" class="flex gap-2">
        <select name="status" class="border border-black px-3 py-1.5 font-mono text-xs">
            <option value="">Todos os status</option>
            <option value="pendente" {{ request('status') === 'pendente' ? 'selected' : '' }}>Pendente</option>
            <option value="aprovado" {{ request('status') === 'aprovado' ? 'selected' : '' }}>Aprovado</option>
            <option value="pago"     {{ request('status') === 'pago' ? 'selected' : '' }}>Pago</option>
            <option value="rejeitado" {{ request('status') === 'rejeitado' ? 'selected' : '' }}>Rejeitado</option>
        </select>
        <button class="border border-black px-3 py-1.5 font-mono text-xs uppercase tracking-widest hover:bg-black hover:text-white transition-colors">Filtrar</button>
    </form>

    {{-- Bulk form --}}
    <form id="bulkForm" method="POST" action="{{ route('admin.afiliados.comissoes.bulk') }}">
        @csrf
        <input type="hidden" name="action" id="bulkAction" value="">
        <div class="border border-black overflow-x-auto">
            <table class="w-full text-xs font-mono border-collapse">
                <thead>
                    <tr class="border-b border-black bg-gray-50">
                        <th class="px-4 py-2"><input type="checkbox" id="selectAll" onclick="toggleAll()"></th>
                        <th class="text-left px-4 py-2 uppercase tracking-widest">Afiliado</th>
                        <th class="text-left px-4 py-2 uppercase tracking-widest">Pedido</th>
                        <th class="text-left px-4 py-2 uppercase tracking-widest">Valor</th>
                        <th class="text-left px-4 py-2 uppercase tracking-widest">Status</th>
                        <th class="text-left px-4 py-2 uppercase tracking-widest">Elegível em</th>
                        <th class="text-left px-4 py-2 uppercase tracking-widest">Pago em</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($comissoes as $com)
                    <tr class="border-b border-gray-100 hover:bg-gray-50">
                        <td class="px-4 py-2"><input type="checkbox" name="ids[]" value="{{ $com->id }}"></td>
                        <td class="px-4 py-2">{{ $com->affiliate->user->name }}</td>
                        <td class="px-4 py-2">#{{ $com->pedido_id }}</td>
                        <td class="px-4 py-2 font-bold">R$ {{ number_format($com->valor, 2, ',', '.') }}</td>
                        <td class="px-4 py-2 uppercase tracking-widest">{{ $com->status }}</td>
                        <td class="px-4 py-2">{{ $com->eligible_at->format('d/m/Y') }}</td>
                        <td class="px-4 py-2">{{ $com->paid_at?->format('d/m/Y') ?? '—' }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="flex items-center gap-2 mt-3">
            <button type="button" onclick="submitBulk('aprovar')"
                class="border border-black px-3 py-1.5 text-[10px] font-mono uppercase tracking-widest hover:bg-black hover:text-white transition-colors">
                Aprovar selecionadas
            </button>
            <button type="button" onclick="submitBulk('rejeitar')"
                class="border border-black px-3 py-1.5 text-[10px] font-mono uppercase tracking-widest hover:bg-black hover:text-white transition-colors">
                Rejeitar selecionadas
            </button>
            <button type="button" onclick="submitBulk('marcar_pago')"
                class="border border-black px-3 py-1.5 text-[10px] font-mono uppercase tracking-widest hover:bg-black hover:text-white transition-colors">
                Marcar como pagas
            </button>
        </div>
    </form>

    <div class="font-mono">{{ $comissoes->links() }}</div>
</div>

<script>
function toggleAll() {
    const all = document.getElementById('selectAll').checked;
    document.querySelectorAll('input[name="ids[]"]').forEach(cb => cb.checked = all);
}
function submitBulk(action) {
    document.getElementById('bulkAction').value = action;
    document.getElementById('bulkForm').submit();
}
</script>
@endsection
```

- [ ] **Step 4: Create admin/afiliados/configuracoes.blade.php**

```blade
{{-- resources/views/admin/afiliados/configuracoes.blade.php --}}
@extends('includes.header-admin')
@section('title', 'Configurações — Afiliados')
@section('content')
<div class="p-4 md:p-6 max-w-lg space-y-6">

    <div class="flex items-center justify-between">
        <h1 class="font-mono text-lg font-bold uppercase tracking-widest">Configurações de Afiliados</h1>
        <a href="{{ route('admin.afiliados.index') }}" class="font-mono text-xs underline">← Afiliados</a>
    </div>

    @if(session('success'))
        <div class="border border-black bg-white p-4 font-mono text-sm">{{ session('success') }}</div>
    @endif

    @if($errors->any())
        <div class="border border-black p-4 font-mono text-sm">
            @foreach($errors->all() as $err)<p>{{ $err }}</p>@endforeach
        </div>
    @endif

    <form method="POST" action="{{ route('admin.afiliados.configuracoes.salvar') }}" class="space-y-5">
        @csrf
        <div>
            <label class="block font-mono text-[10px] uppercase tracking-widest mb-1">Comissão padrão (%)</label>
            <input type="number" step="0.01" name="commission_percent_default"
                value="{{ $settings['commission_percent_default'] ?? '5.00' }}"
                class="w-full border border-black px-3 py-2 font-mono text-sm focus:outline-none">
            <p class="text-[10px] text-gray-400 mt-1">Aplicado a afiliados sem override individual.</p>
        </div>
        <div>
            <label class="block font-mono text-[10px] uppercase tracking-widest mb-1">Validade do cookie (dias)</label>
            <input type="number" name="cookie_days"
                value="{{ $settings['cookie_days'] ?? '30' }}"
                class="w-full border border-black px-3 py-2 font-mono text-sm focus:outline-none">
        </div>
        <div>
            <label class="block font-mono text-[10px] uppercase tracking-widest mb-1">Período de carência (dias)</label>
            <input type="number" name="grace_period_days"
                value="{{ $settings['grace_period_days'] ?? '30' }}"
                class="w-full border border-black px-3 py-2 font-mono text-sm focus:outline-none">
            <p class="text-[10px] text-gray-400 mt-1">Dias após conversão antes de a comissão poder ser aprovada.</p>
        </div>
        <button type="submit"
            class="w-full border border-black bg-black text-white py-3 font-mono text-xs uppercase tracking-widest hover:bg-white hover:text-black transition-colors">
            Salvar Configurações
        </button>
    </form>
</div>
@endsection
```

- [ ] **Step 5: Create public/js/afiliados-admin.js**

```javascript
// public/js/afiliados-admin.js

// SSE real-time metrics
(function () {
    if (!window.routes || !window.routes.adminAfiliadosStream) return;

    const source = new EventSource(window.routes.adminAfiliadosStream);

    source.onmessage = function (e) {
        try {
            const data = JSON.parse(e.data);

            const ativos = document.getElementById('sse-ativos');
            const hoje   = document.getElementById('sse-indicacoes-hoje');
            const pend   = document.getElementById('sse-pendentes');
            const pagas  = document.getElementById('sse-pagas');

            if (ativos) ativos.textContent = data.afiliados_ativos;
            if (hoje)   hoje.textContent   = data.indicacoes_hoje;
            if (pend)   pend.textContent   = 'R$ ' + formatMoney(data.comissoes_pendentes_valor);
            if (pagas)  pagas.textContent  = 'R$ ' + formatMoney(data.comissoes_pagas_valor);
        } catch (err) {
            // ignore parse errors
        }
    };

    source.onerror = function () {
        source.close();
    };

    function formatMoney(value) {
        return Number(value).toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    }
})();

// Commission modal
function abrirModalComissao(id, type, value) {
    document.getElementById('mc-type').value  = type || 'percent';
    document.getElementById('mc-value').value = value || '';
    document.getElementById('formComissao').action =
        (window.routes.adminAfiliadosComissao || '').replace(':id', id);
    document.getElementById('modalComissao').classList.remove('hidden');
}

function fecharModalComissao() {
    document.getElementById('modalComissao').classList.add('hidden');
}

document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape') fecharModalComissao();
});
```

- [ ] **Step 6: Build assets and clear cache**

```bash
cd /var/www/html && npm run build
docker exec laravel-app php artisan view:clear && docker exec laravel-app php artisan cache:clear
```

- [ ] **Step 7: Commit**

```bash
git add resources/views/admin/afiliados/ public/js/afiliados-admin.js
git commit -m "feat: add admin affiliate views and JS"
```

---

## Task 14: Nav Links

**Files:**
- Modify: `resources/views/includes/header-admin.blade.php`
- Modify: `resources/views/includes/header.blade.php`

- [ ] **Step 1: Add Afiliados link to admin sidebar**

In `resources/views/includes/header-admin.blade.php`, locate the Categorias `<li>` block (around line 128):

```blade
                    <li>
                        <a href="{{ route('admin.categorias') }}" class="flex items-center space-x-3 px-3 py-2.5 font-mono text-xs uppercase tracking-widest transition-colors {{ request()->routeIs('admin.categorias*') ? 'bg-black text-white' : 'text-[var(--color-lab-muted)] hover:bg-gray-100 hover:text-black' }}">
                            ...
                            <span>Categorias</span>
                        </a>
                    </li>
```

After that `</li>`, add:

```blade
                    <li>
                        <a href="{{ route('admin.afiliados.index') }}" class="flex items-center space-x-3 px-3 py-2.5 font-mono text-xs uppercase tracking-widest transition-colors {{ request()->routeIs('admin.afiliados*') ? 'bg-black text-white' : 'text-[var(--color-lab-muted)] hover:bg-gray-100 hover:text-black' }}">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                            <span>Afiliados</span>
                        </a>
                    </li>
```

- [ ] **Step 2: Add Painel Afiliado link to user dropdown**

In `resources/views/includes/header.blade.php`, locate the "Meu Perfil" link (around line 96):

```blade
<a href="{{ route('site.perfil') }}" class="flex items-center gap-2 px-4 py-2.5 text-sm ...">
    ...Meu Perfil
```

After the `</a>` for "Meu Perfil", add:

```blade
                                <a href="{{ route('afiliados.painel') }}" class="flex items-center gap-2 px-4 py-2.5 text-sm text-gray-700 hover:bg-gray-50 hover:text-black transition-colors">
                                    <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                                    Painel Afiliado
                                </a>
```

- [ ] **Step 3: Clear view cache**

```bash
docker exec laravel-app php artisan view:clear
```

- [ ] **Step 4: Commit**

```bash
git add resources/views/includes/header-admin.blade.php \
        resources/views/includes/header.blade.php
git commit -m "feat: add affiliate nav links to admin sidebar and user dropdown"
```

---

## Task 15: Full Test Suite + Smoke Test

- [ ] **Step 1: Run all tests**

```bash
docker exec laravel-app php artisan test
```

Expected: all tests pass, including:
- `AffiliateServiceTest` (15 tests)
- `AffiliateMiddlewareTest` (4 tests)
- `AffiliadoControllerTest` (9 tests)
- `AdminAfiliadoControllerTest` (13 tests)
- All pre-existing tests

- [ ] **Step 2: Run migrations on production DB**

```bash
docker exec laravel-app php artisan migrate --force
```

- [ ] **Step 3: Smoke test the full flow**

1. Visit `http://localhost/?ref=TESTCODE` (use an actual affiliate code from DB)
2. Inspect cookies — `affiliate_ref` should be set
3. Register a new user
4. Check DB: `SELECT * FROM affiliate_referrals;`
5. Simulate a paid order update:
   ```bash
   docker exec laravel-app php artisan tinker
   # In tinker:
   $p = App\Models\Pedido::where('status', 'pago')->first();
   app(App\Services\AffiliateService::class)->handleOrderPaid($p);
   App\Models\AffiliateCommission::all();
   ```
6. Visit `/admin/afiliados` — confirm SSE cards update
7. Visit `/admin/afiliados/comissoes` — approve a commission in bulk
8. Visit `/afiliados` as the referred user — confirm dashboard shows data

- [ ] **Step 4: Final commit if any fixes**

```bash
git add -p  # review changes before staging
git commit -m "fix: post-integration fixes for affiliate system"
```

---

## Self-Review Notes

- ✅ All 4 business rules (anti-self-referral, unique referral, first purchase, grace period) have tests
- ✅ Guest checkout is skipped in `handleOrderPaid` via `$pedido->user_id === null` check
- ✅ SSE route `stream` is defined before `{id}` to avoid route conflict
- ✅ `set_time_limit(0)` and `connection_aborted()` guard the SSE loop
- ✅ Commission calculation handles both percent and fixed types with null fallback to global setting
- ✅ `getSetting` reads from DB, falls back to parameter default (works with RefreshDatabase in tests)
- ✅ Route names are consistent throughout (e.g., `afiliados.painel`, `admin.afiliados.index`)
