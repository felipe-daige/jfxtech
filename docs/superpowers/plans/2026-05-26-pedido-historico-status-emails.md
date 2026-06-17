# Pedido Histórico: Status e E-mails Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Registrar em banco cada mudança de status e cada e-mail disparado de um pedido, e exibir esse histórico cronológico na página de detalhes do pedido no admin.

**Architecture:** Nova tabela `pedido_historico` armazena eventos do tipo `status_alterado` e `email_enviado`. O `PedidoObserver` já existente grava as mudanças de status; o `OrderEmailNotificationService` grava os e-mails ao enfileirá-los. O `AdminController::detalhesPedido` carrega o relacionamento e a view exibe uma timeline no final do partial `pedido-detalhes`.

**Tech Stack:** Laravel 12, PHP 8.3, PostgreSQL, Blade + Tailwind CSS 4, PHPUnit Feature Tests

---

## File Map

| Ação | Arquivo | Responsabilidade |
|---|---|---|
| Criar | `database/migrations/2026_05_26_000001_create_pedido_historico_table.php` | Schema da tabela |
| Criar | `app/Models/PedidoHistorico.php` | Model + fillable + relate |
| Modificar | `app/Models/Pedido.php` | Adicionar `hasMany(PedidoHistorico::class)` |
| Modificar | `app/Observers/PedidoObserver.php` | Gravar evento `status_alterado` |
| Modificar | `app/Services/OrderEmailNotificationService.php` | Gravar evento `email_enviado` |
| Modificar | `app/Http/Controllers/AdminController.php` | Carregar `historico` no `detalhesPedido` |
| Modificar | `resources/views/admin/includes/pedido-detalhes.blade.php` | Exibir timeline de histórico |
| Criar | `tests/Feature/PedidoHistoricoTest.php` | Testes de integração |

---

### Task 1: Migration — tabela `pedido_historico`

**Files:**
- Create: `database/migrations/2026_05_26_000001_create_pedido_historico_table.php`

- [ ] **Step 1: Escrever o teste que falha**

```php
// tests/Feature/PedidoHistoricoTest.php
<?php

namespace Tests\Feature;

use App\Enums\PedidoStatus;
use App\Models\Pedido;
use App\Models\PedidoHistorico;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PedidoHistoricoTest extends TestCase
{
    use RefreshDatabase;

    public function test_pedido_historico_table_exists(): void
    {
        $this->assertTrue(
            \Schema::hasTable('pedido_historico'),
            'A tabela pedido_historico deve existir'
        );
    }
}
```

- [ ] **Step 2: Rodar o teste para confirmar que falha**

```bash
docker exec laravel-app php artisan test --filter=PedidoHistoricoTest::test_pedido_historico_table_exists
```
Expected: FAIL — `A tabela pedido_historico deve existir`

- [ ] **Step 3: Criar a migration**

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pedido_historico', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pedido_id')->constrained('pedidos')->cascadeOnDelete();
            $table->string('tipo'); // 'status_alterado' | 'email_enviado'
            $table->string('status_anterior')->nullable();
            $table->string('status_novo')->nullable();
            $table->string('email_destinatario')->nullable();
            $table->string('email_evento')->nullable();
            $table->string('criado_por')->default('sistema'); // 'sistema' | 'admin'
            $table->timestamp('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pedido_historico');
    }
};
```

- [ ] **Step 4: Rodar a migration**

```bash
docker exec laravel-app php artisan migrate --force
```
Expected: `Migrating: 2026_05_26_000001_create_pedido_historico_table` → `Migrated`

- [ ] **Step 5: Rodar o teste para confirmar que passa**

```bash
docker exec laravel-app php artisan test --filter=PedidoHistoricoTest::test_pedido_historico_table_exists
```
Expected: PASS

- [ ] **Step 6: Commit**

```bash
git add database/migrations/2026_05_26_000001_create_pedido_historico_table.php tests/Feature/PedidoHistoricoTest.php
git commit -m "feat: migration para tabela pedido_historico"
```

---

### Task 2: Model `PedidoHistorico` + relacionamento em `Pedido`

**Files:**
- Create: `app/Models/PedidoHistorico.php`
- Modify: `app/Models/Pedido.php`

- [ ] **Step 1: Escrever o teste que falha**

Adicionar no `tests/Feature/PedidoHistoricoTest.php`:

```php
public function test_pode_criar_registro_de_historico(): void
{
    $pedido = Pedido::factory()->create(['status' => PedidoStatus::PENDENTE]);

    $historico = PedidoHistorico::create([
        'pedido_id'       => $pedido->id,
        'tipo'            => 'status_alterado',
        'status_anterior' => PedidoStatus::PENDENTE,
        'status_novo'     => PedidoStatus::PROCESSANDO,
        'criado_por'      => 'sistema',
    ]);

    $this->assertDatabaseHas('pedido_historico', [
        'pedido_id'       => $pedido->id,
        'tipo'            => 'status_alterado',
        'status_anterior' => PedidoStatus::PENDENTE,
        'status_novo'     => PedidoStatus::PROCESSANDO,
    ]);
    $this->assertCount(1, $pedido->fresh()->historico);
}
```

- [ ] **Step 2: Rodar o teste para confirmar que falha**

```bash
docker exec laravel-app php artisan test --filter=PedidoHistoricoTest::test_pode_criar_registro_de_historico
```
Expected: FAIL — `Class "App\Models\PedidoHistorico" not found`

- [ ] **Step 3: Criar o Model**

```php
<?php
// app/Models/PedidoHistorico.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PedidoHistorico extends Model
{
    public $timestamps = false;

    const CREATED_AT = 'created_at';

    protected $fillable = [
        'pedido_id',
        'tipo',
        'status_anterior',
        'status_novo',
        'email_destinatario',
        'email_evento',
        'criado_por',
    ];

    public function pedido(): BelongsTo
    {
        return $this->belongsTo(Pedido::class);
    }
}
```

- [ ] **Step 4: Adicionar relacionamento no Pedido**

Em `app/Models/Pedido.php`, adicionar o import e o método após o método `pagamentos()`:

```php
use App\Models\PedidoHistorico; // adicionar no topo com os outros use
```

```php
public function historico(): HasMany
{
    return $this->hasMany(PedidoHistorico::class)->orderBy('created_at', 'asc');
}
```

- [ ] **Step 5: Rodar o teste para confirmar que passa**

```bash
docker exec laravel-app php artisan test --filter=PedidoHistoricoTest::test_pode_criar_registro_de_historico
```
Expected: PASS

- [ ] **Step 6: Commit**

```bash
git add app/Models/PedidoHistorico.php app/Models/Pedido.php tests/Feature/PedidoHistoricoTest.php
git commit -m "feat: model PedidoHistorico e relacionamento em Pedido"
```

---

### Task 3: PedidoObserver — gravar mudança de status

**Files:**
- Modify: `app/Observers/PedidoObserver.php`

- [ ] **Step 1: Escrever o teste que falha**

Adicionar no `tests/Feature/PedidoHistoricoTest.php`:

```php
public function test_mudanca_de_status_cria_historico(): void
{
    $pedido = Pedido::factory()->create(['status' => PedidoStatus::PENDENTE]);

    $pedido->update(['status' => PedidoStatus::PAGO]);

    $this->assertDatabaseHas('pedido_historico', [
        'pedido_id'       => $pedido->id,
        'tipo'            => 'status_alterado',
        'status_anterior' => PedidoStatus::PENDENTE,
        'status_novo'     => PedidoStatus::PAGO,
        'criado_por'      => 'sistema',
    ]);
}
```

- [ ] **Step 2: Rodar o teste para confirmar que falha**

```bash
docker exec laravel-app php artisan test --filter=PedidoHistoricoTest::test_mudanca_de_status_cria_historico
```
Expected: FAIL — nenhum registro em `pedido_historico`

- [ ] **Step 3: Atualizar o PedidoObserver**

Substituir o conteúdo de `app/Observers/PedidoObserver.php`:

```php
<?php

namespace App\Observers;

use App\Enums\PedidoStatus;
use App\Jobs\CartAbandonedNotificationJob;
use App\Models\Pedido;
use App\Models\PedidoHistorico;
use App\Services\OrderEmailNotificationService;
use App\Services\OrderStatusNotificationService;
use Illuminate\Support\Facades\Auth;

class PedidoObserver
{
    public function updated(Pedido $pedido): void
    {
        if (!$pedido->wasChanged('status')) {
            return;
        }

        $statusAnterior = $pedido->getOriginal('status');

        PedidoHistorico::create([
            'pedido_id'       => $pedido->id,
            'tipo'            => 'status_alterado',
            'status_anterior' => $statusAnterior,
            'status_novo'     => $pedido->status,
            'criado_por'      => (Auth::check() && Auth::user()?->is_admin) ? 'admin' : 'sistema',
        ]);

        if ($pedido->status === PedidoStatus::PENDENTE) {
            CartAbandonedNotificationJob::dispatch($pedido->id)
                ->delay(now()->addMinutes(30));
            return;
        }

        if (!in_array($pedido->status, PedidoStatus::notificationValues(), true)) {
            return;
        }

        if (in_array($pedido->status, [PedidoStatus::PAGO, PedidoStatus::PROCESSANDO, PedidoStatus::ENVIADO, PedidoStatus::ENTREGUE], true)) {
            app(\App\Services\GuestToUserConversionService::class)->convert($pedido);
        }

        app(OrderStatusNotificationService::class)->send($pedido);
        app(OrderEmailNotificationService::class)->send($pedido);
    }
}
```

- [ ] **Step 4: Rodar o teste para confirmar que passa**

```bash
docker exec laravel-app php artisan test --filter=PedidoHistoricoTest::test_mudanca_de_status_cria_historico
```
Expected: PASS

- [ ] **Step 5: Rodar todos os testes para garantir que nada quebrou**

```bash
docker exec laravel-app php artisan test
```
Expected: todos os testes passando

- [ ] **Step 6: Commit**

```bash
git add app/Observers/PedidoObserver.php tests/Feature/PedidoHistoricoTest.php
git commit -m "feat: observer grava histórico de mudança de status do pedido"
```

---

### Task 4: OrderEmailNotificationService — gravar e-mail disparado

**Files:**
- Modify: `app/Services/OrderEmailNotificationService.php`

- [ ] **Step 1: Escrever o teste que falha**

Adicionar no `tests/Feature/PedidoHistoricoTest.php`:

```php
public function test_envio_de_email_cria_historico(): void
{
    \Illuminate\Support\Facades\Mail::fake();

    $pedido = Pedido::factory()
        ->has(\App\Models\ItemPedido::factory()->count(1), 'itens')
        ->create([
            'status'         => PedidoStatus::PAGO,
            'customer_email' => 'cliente@teste.com',
        ]);

    app(\App\Services\OrderEmailNotificationService::class)->send($pedido);

    $this->assertDatabaseHas('pedido_historico', [
        'pedido_id'          => $pedido->id,
        'tipo'               => 'email_enviado',
        'email_destinatario' => 'cliente@teste.com',
        'email_evento'       => PedidoStatus::PAGO,
        'criado_por'         => 'sistema',
    ]);
}
```

- [ ] **Step 2: Rodar o teste para confirmar que falha**

```bash
docker exec laravel-app php artisan test --filter=PedidoHistoricoTest::test_envio_de_email_cria_historico
```
Expected: FAIL — nenhum registro `email_enviado` em `pedido_historico`

- [ ] **Step 3: Atualizar OrderEmailNotificationService**

Substituir o conteúdo de `app/Services/OrderEmailNotificationService.php`:

```php
<?php

namespace App\Services;

use App\Enums\PedidoStatus;
use App\Mail\OrderStatusMail;
use App\Models\Pedido;
use App\Models\PedidoHistorico;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

class OrderEmailNotificationService
{
    public function __construct(private CheckoutOrderService $checkoutOrderService) {}

    public function send(Pedido $pedido): void
    {
        if (!in_array($pedido->status, PedidoStatus::notificationValues(), true)) {
            return;
        }

        $this->sendEvent($pedido, $pedido->status);
    }

    public function sendOrderReceived(Pedido $pedido): void
    {
        $this->sendEvent($pedido, 'received');
    }

    private function sendEvent(Pedido $pedido, string $eventType): void
    {
        $pedido->loadMissing(['user', 'itens.produto']);

        $recipient = $this->recipientEmail($pedido);
        if (!$recipient) {
            return;
        }

        try {
            Mail::to($recipient)->queue(new OrderStatusMail(
                $pedido,
                $eventType,
                $this->checkoutOrderService->orderUrl($pedido),
            ));

            PedidoHistorico::create([
                'pedido_id'          => $pedido->id,
                'tipo'               => 'email_enviado',
                'email_destinatario' => $recipient,
                'email_evento'       => $eventType,
                'criado_por'         => 'sistema',
            ]);
        } catch (Throwable $exception) {
            Log::error('Order email notification failed', [
                'pedido_id'  => $pedido->id,
                'status'     => $pedido->status,
                'event_type' => $eventType,
                'error'      => $exception->getMessage(),
            ]);
        }
    }

    private function recipientEmail(Pedido $pedido): ?string
    {
        return $pedido->customer_email ?: $pedido->user?->email;
    }
}
```

- [ ] **Step 4: Rodar o teste para confirmar que passa**

```bash
docker exec laravel-app php artisan test --filter=PedidoHistoricoTest::test_envio_de_email_cria_historico
```
Expected: PASS

- [ ] **Step 5: Rodar todos os testes**

```bash
docker exec laravel-app php artisan test
```
Expected: todos passando

- [ ] **Step 6: Commit**

```bash
git add app/Services/OrderEmailNotificationService.php tests/Feature/PedidoHistoricoTest.php
git commit -m "feat: service grava histórico de e-mail enviado do pedido"
```

---

### Task 5: AdminController — carregar histórico no detalhesPedido

**Files:**
- Modify: `app/Http/Controllers/AdminController.php`

- [ ] **Step 1: Localizar o método `detalhesPedido`** — linha ~886 de `AdminController.php`

- [ ] **Step 2: Atualizar o eager loading do método**

Encontrar:

```php
$pedido = Pedido::with([
    'user',
    'endereco',
    'itens.produto.imagens',
    'itens.produtoVariante',
    'pagamentos',
])->findOrFail($id);

$analytics = $this->buildOrderDetailAnalytics($pedido);

if (! $request->expectsJson() && ! $request->ajax()) {
    return view('admin.pedido-detalhes', compact('pedido', 'analytics'));
}

$html = view('admin.includes.pedido-detalhes', array_merge(
    compact('pedido', 'analytics'),
    ['isFullOrderPage' => false]
))->render();
```

Substituir por:

```php
$pedido = Pedido::with([
    'user',
    'endereco',
    'itens.produto.imagens',
    'itens.produtoVariante',
    'pagamentos',
    'historico',
])->findOrFail($id);

$analytics = $this->buildOrderDetailAnalytics($pedido);

if (! $request->expectsJson() && ! $request->ajax()) {
    return view('admin.pedido-detalhes', compact('pedido', 'analytics'));
}

$html = view('admin.includes.pedido-detalhes', array_merge(
    compact('pedido', 'analytics'),
    ['isFullOrderPage' => false]
))->render();
```

- [ ] **Step 3: Limpar cache de views e testar manualmente**

```bash
docker exec laravel-app php artisan view:clear
docker exec laravel-app php artisan cache:clear
```

- [ ] **Step 4: Commit**

```bash
git add app/Http/Controllers/AdminController.php
git commit -m "feat: carregar historico do pedido no admin"
```

---

### Task 6: View — timeline de histórico no admin pedido-detalhes

**Files:**
- Modify: `resources/views/admin/includes/pedido-detalhes.blade.php`

- [ ] **Step 1: Localizar o final do partial** — linha ~821 (após `</section>` do rastreamento)

Adicionar **antes** do `</div>` que fecha `<div class="space-y-6" data-status="{{ $pedido->status }}">` (última linha antes do fechamento do componente) a seguinte seção de timeline:

```blade
@if($pedido->historico->isNotEmpty())
<section class="border border-[var(--color-lab-border)] bg-white overflow-hidden">
    <div class="px-5 py-4 border-b border-[var(--color-lab-border)] bg-[var(--color-lab-surface)]">
        <p class="font-mono text-[10px] uppercase tracking-widest text-[var(--color-lab-muted)]">Histórico do pedido</p>
    </div>
    <div class="divide-y divide-[var(--color-lab-border)]">
        @foreach($pedido->historico as $evento)
            <div class="flex items-start gap-3 px-5 py-3">
                @if($evento->tipo === 'status_alterado')
                    <div class="mt-0.5 flex-shrink-0 w-5 h-5 flex items-center justify-center rounded-full bg-black text-white">
                        <svg xmlns="http://www.w3.org/2000/svg" width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
                    </div>
                @else
                    <div class="mt-0.5 flex-shrink-0 w-5 h-5 flex items-center justify-center rounded-full border border-[var(--color-lab-border)] bg-white text-[var(--color-lab-muted)]">
                        <svg xmlns="http://www.w3.org/2000/svg" width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="4" width="20" height="16" rx="2"/><path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7"/></svg>
                    </div>
                @endif
                <div class="min-w-0 flex-1">
                    @if($evento->tipo === 'status_alterado')
                        <p class="font-mono text-xs text-black">
                            Status alterado:
                            <span class="text-[var(--color-lab-muted)]">{{ \App\Enums\PedidoStatus::label($evento->status_anterior ?? '') }}</span>
                            →
                            <span class="font-semibold">{{ \App\Enums\PedidoStatus::label($evento->status_novo ?? '') }}</span>
                        </p>
                    @else
                        <p class="font-mono text-xs text-black">
                            E-mail enviado:
                            <span class="font-semibold">{{ $evento->email_evento }}</span>
                            <span class="text-[var(--color-lab-muted)]">→ {{ $evento->email_destinatario }}</span>
                        </p>
                    @endif
                    <p class="font-mono text-[10px] text-[var(--color-lab-muted)] mt-0.5">
                        {{ $evento->created_at->format('d/m/Y H:i:s') }}
                        @if($evento->criado_por === 'admin')
                            · <span class="text-black">admin</span>
                        @endif
                    </p>
                </div>
            </div>
        @endforeach
    </div>
</section>
@endif
```

- [ ] **Step 2: Verificar onde inserir no arquivo**

Abrir `resources/views/admin/includes/pedido-detalhes.blade.php` e localizar a última linha antes do `</div>` final de `<div class="space-y-6"`. O trecho acima deve ser inserido logo antes desse `</div>`.

- [ ] **Step 3: Limpar cache e compilar assets**

```bash
docker exec laravel-app php artisan view:clear
npm run build
```

- [ ] **Step 4: Testar manualmente**

Abrir um pedido no admin que tenha passado por pelo menos uma mudança de status. Confirmar que a seção "Histórico do pedido" aparece no final da página com os eventos listados em ordem cronológica.

- [ ] **Step 5: Commit**

```bash
git add resources/views/admin/includes/pedido-detalhes.blade.php
git commit -m "feat: exibir timeline de histórico na página de detalhes do pedido admin"
```

---

### Task 7: Teste de integração — admin view exibe histórico

**Files:**
- Modify: `tests/Feature/PedidoHistoricoTest.php`

- [ ] **Step 1: Adicionar teste de view**

```php
public function test_admin_pode_ver_historico_de_status_e_email(): void
{
    \Illuminate\Support\Facades\Mail::fake();

    $admin = \App\Models\User::factory()->create(['is_admin' => true]);
    $this->actingAs($admin);

    $pedido = Pedido::factory()
        ->has(\App\Models\ItemPedido::factory()->count(1), 'itens')
        ->create([
            'status'         => PedidoStatus::PAGO,
            'customer_email' => 'cliente@teste.com',
        ]);

    \App\Models\PedidoHistorico::create([
        'pedido_id'       => $pedido->id,
        'tipo'            => 'status_alterado',
        'status_anterior' => PedidoStatus::PENDENTE,
        'status_novo'     => PedidoStatus::PAGO,
        'criado_por'      => 'sistema',
    ]);

    \App\Models\PedidoHistorico::create([
        'pedido_id'          => $pedido->id,
        'tipo'               => 'email_enviado',
        'email_destinatario' => 'cliente@teste.com',
        'email_evento'       => PedidoStatus::PAGO,
        'criado_por'         => 'sistema',
    ]);

    $response = $this->get(route('admin.pedidos.detalhes', $pedido->id));

    $response->assertStatus(200);
    $response->assertSee('Histórico do pedido');
    $response->assertSee('Status alterado');
    $response->assertSee('E-mail enviado');
    $response->assertSee('cliente@teste.com');
}
```

- [ ] **Step 2: Rodar o teste**

```bash
docker exec laravel-app php artisan test --filter=PedidoHistoricoTest::test_admin_pode_ver_historico_de_status_e_email
```
Expected: PASS

- [ ] **Step 3: Rodar toda a suite de testes**

```bash
docker exec laravel-app php artisan test
```
Expected: todos passando

- [ ] **Step 4: Commit final**

```bash
git add tests/Feature/PedidoHistoricoTest.php
git commit -m "test: cobertura completa do PedidoHistorico"
```

---

## Self-Review

### Spec coverage
- ✅ E-mails automáticos ao mudar status — já funcionava; plano confirma via testes
- ✅ Histórico de status do pedido — Task 3 (Observer) + Task 6 (view)
- ✅ Histórico de e-mails disparados — Task 4 (Service) + Task 6 (view)
- ✅ Exibir no admin — Task 5 (Controller) + Task 6 (view)

### Placeholder scan
- Nenhum TBD ou TODO encontrado — todos os steps têm código completo

### Type consistency
- `PedidoHistorico::create()` — fillable definido em Task 2, usado identicamente em Tasks 3, 4, 7
- `$pedido->historico` — relacionamento definido em Task 2, carregado em Task 5, renderizado em Task 6
- `PedidoStatus::label()` — método já existente no enum, usado corretamente na view
