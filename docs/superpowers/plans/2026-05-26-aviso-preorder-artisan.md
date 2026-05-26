# Aviso de Pré-encomenda Artisan — Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Exibir um aviso de pré-encomenda nos produtos Artisan (importados do Japão, prazo de até 15 dias) na página de detalhe do produto e no checkout.

**Architecture:** Migration de dados corrige `marca = 'Artisan'` nos 5 produtos existentes. Um partial Blade reutilizável é incluído condicionalmente via `@if($produto->marca === 'Artisan')` na página de produto e via verificação de itens no checkout. Nenhuma nova coluna, nenhum serviço novo.

**Tech Stack:** Laravel 12 + Blade + Tailwind CSS 4 + PostgreSQL (prod) / SQLite (testes)

---

## File Map

| Ação | Arquivo | Responsabilidade |
|---|---|---|
| Create | `database/migrations/2026_05_26_XXXXXX_set_artisan_marca_on_produtos.php` | Preenche `marca = 'Artisan'` nos 5 produtos existentes |
| Create | `resources/views/includes/aviso-preorder-artisan.blade.php` | HTML do aviso (reutilizável, deletar quando não precisar mais) |
| Modify | `resources/views/site/produto-detalhes.blade.php` (~linha 172) | Inclui o partial para produtos Artisan |
| Modify | `resources/views/site/checkout.blade.php` (~linha 164) | Inclui o partial quando há item Artisan no carrinho |
| Create | `tests/Feature/ArtisanPreorderNoticeTest.php` | 4 testes de feature: presença/ausência do aviso nos dois contextos |

---

## Task 1: Migration — preencher campo `marca`

**Files:**
- Create: `database/migrations/2026_05_26_XXXXXX_set_artisan_marca_on_produtos.php`

- [ ] **Step 1: Gerar o arquivo de migration**

```bash
docker exec laravel-app php artisan make:migration set_artisan_marca_on_produtos
```

Isso cria o arquivo em `database/migrations/` com timestamp real. Anotar o nome gerado.

- [ ] **Step 2: Preencher a migration**

Abrir o arquivo gerado e substituir os métodos `up()` e `down()`:

```php
public function up(): void
{
    DB::table('produtos')
        ->whereRaw("LOWER(nome) LIKE ?", ['%artisan%'])
        ->update(['marca' => 'Artisan']);
}

public function down(): void
{
    DB::table('produtos')
        ->whereRaw("LOWER(nome) LIKE ?", ['%artisan%'])
        ->where('marca', 'Artisan')
        ->update(['marca' => null]);
}
```

Adicionar `use Illuminate\Support\Facades\DB;` no topo do arquivo se não estiver presente (está presente por padrão nas migrations).

- [ ] **Step 3: Executar a migration**

```bash
docker exec laravel-app php artisan migrate --force
```

Saída esperada: `Migrating: 2026_05_26_XXXXXX_set_artisan_marca_on_produtos` / `Migrated`

- [ ] **Step 4: Verificar os dados no banco**

```bash
docker exec postgres psql -U postgres -d jfxtech -c "SELECT id, nome, marca FROM produtos WHERE marca = 'Artisan';"
```

Saída esperada: 5 linhas (FX Hien, FX Key-83, FX Type-99, NINJA FX Zero, FX Raiden).

- [ ] **Step 5: Commit**

```bash
git add database/migrations/
git commit -m "feat: migration set marca=Artisan para produtos Artisan"
```

---

## Task 2: Criar o partial do aviso

**Files:**
- Create: `resources/views/includes/aviso-preorder-artisan.blade.php`

- [ ] **Step 1: Criar o arquivo partial**

Criar `resources/views/includes/aviso-preorder-artisan.blade.php` com o conteúdo:

```blade
{{-- Aviso de Pré-encomenda — produtos Artisan importados do Japão --}}
{{-- Para remover: apagar este arquivo e os @include em produto-detalhes e checkout --}}

<div class="border-l-4 border-black bg-zinc-50 p-4 mb-6">
    <div class="flex items-start gap-3">
        <svg class="w-5 h-5 text-black flex-shrink-0 mt-0.5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <circle cx="12" cy="12" r="10"/>
            <polyline points="12 6 12 12 16 14"/>
        </svg>
        <div>
            <span class="inline-block bg-black text-white text-[10px] font-mono font-bold px-2 py-0.5 uppercase tracking-widest mb-2">PRÉ-ENCOMENDA</span>
            <p class="text-sm font-mono text-gray-700 leading-relaxed m-0">
                Este produto é importado direto do Japão e precisa ser encomendado à loja antes do envio.<br>
                <strong class="text-black">Prazo estimado: até 15 dias para chegada.</strong>
            </p>
        </div>
    </div>
</div>
```

- [ ] **Step 2: Commit**

```bash
git add resources/views/includes/aviso-preorder-artisan.blade.php
git commit -m "feat: partial aviso pre-encomenda produtos Artisan"
```

---

## Task 3: TDD — integrar na página de detalhe do produto

**Files:**
- Create: `tests/Feature/ArtisanPreorderNoticeTest.php`
- Modify: `resources/views/site/produto-detalhes.blade.php` (~linha 172)

- [ ] **Step 1: Criar o arquivo de testes**

Criar `tests/Feature/ArtisanPreorderNoticeTest.php`:

```php
<?php

namespace Tests\Feature;

use App\Models\ItemPedido;
use App\Models\Pedido;
use App\Models\Produto;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ArtisanPreorderNoticeTest extends TestCase
{
    use RefreshDatabase;

    public function test_artisan_product_shows_preorder_notice_on_detail_page(): void
    {
        $produto = Produto::factory()->create([
            'nome'   => 'Artisan FX Hien',
            'marca'  => 'Artisan',
            'estoque' => 5,
        ]);

        $this->get(route('site.produto.detalhes', $produto->slug))
            ->assertOk()
            ->assertSee('PRÉ-ENCOMENDA');
    }

    public function test_non_artisan_product_does_not_show_preorder_notice_on_detail_page(): void
    {
        $produto = Produto::factory()->create([
            'nome'   => 'Razer Viper Mini',
            'marca'  => 'Razer',
            'estoque' => 5,
        ]);

        $this->get(route('site.produto.detalhes', $produto->slug))
            ->assertOk()
            ->assertDontSee('PRÉ-ENCOMENDA');
    }

    public function test_checkout_with_artisan_item_shows_preorder_notice(): void
    {
        $user = User::factory()->create();
        $this->makeCart($user, 'Artisan');

        $this->actingAs($user)
            ->get(route('site.checkout'))
            ->assertOk()
            ->assertSee('PRÉ-ENCOMENDA');
    }

    public function test_checkout_without_artisan_item_does_not_show_preorder_notice(): void
    {
        $user = User::factory()->create();
        $this->makeCart($user, 'Razer');

        $this->actingAs($user)
            ->get(route('site.checkout'))
            ->assertOk()
            ->assertDontSee('PRÉ-ENCOMENDA');
    }

    private function makeCart(User $user, string $marca): Pedido
    {
        $produto = Produto::factory()->create([
            'marca' => $marca,
            'preco' => 200.00,
            'peso'  => 0.5,
        ]);

        $pedido = Pedido::create([
            'user_id'     => $user->id,
            'status'      => 'carrinho',
            'valor_total' => 200.00,
        ]);

        ItemPedido::create([
            'pedido_id'  => $pedido->id,
            'produto_id' => $produto->id,
            'quantidade' => 1,
            'preco'      => 200.00,
        ]);

        return $pedido;
    }
}
```

- [ ] **Step 2: Rodar os testes de produto-detalhes para confirmar falha**

```bash
docker exec laravel-app php artisan test --filter=ArtisanPreorderNoticeTest::test_artisan_product_shows_preorder_notice_on_detail_page
docker exec laravel-app php artisan test --filter=ArtisanPreorderNoticeTest::test_non_artisan_product_does_not_show_preorder_notice_on_detail_page
```

Saída esperada: primeiro FAIL (texto não encontrado), segundo PASS (texto ausente, que já é o comportamento atual).

- [ ] **Step 3: Adicionar o @include na view produto-detalhes**

Em `resources/views/site/produto-detalhes.blade.php`, localizar o bloco de Stock Status (termina com `@endif` por volta da linha 171) e inserir o include logo após:

```blade
                    {{-- Stock Status --}}
                    <div class="flex items-center gap-2 mb-6 font-mono text-sm">
                        @if($produto->em_estoque)
                            <div class="w-2 h-2 bg-green-500 rounded-full"></div>
                            <span class="text-green-700">EM ESTOQUE</span>
                            <span id="stock-units" class="text-gray-400">({{ $produto->estoque }} un.)</span>
                        @else
                            <div class="w-2 h-2 bg-red-500 rounded-full"></div>
                            <span class="text-red-600">ESGOTADO</span>
                        @endif
                    </div>

                    @if($produto->marca === 'Artisan')
                        @include('includes.aviso-preorder-artisan')
                    @endif

                    {{-- Specs Grid --}}
```

- [ ] **Step 4: Rodar os dois testes de produto-detalhes para confirmar que passam**

```bash
docker exec laravel-app php artisan test --filter=ArtisanPreorderNoticeTest::test_artisan_product_shows_preorder_notice_on_detail_page
docker exec laravel-app php artisan test --filter=ArtisanPreorderNoticeTest::test_non_artisan_product_does_not_show_preorder_notice_on_detail_page
```

Saída esperada: ambos PASS.

- [ ] **Step 5: Commit**

```bash
git add tests/Feature/ArtisanPreorderNoticeTest.php resources/views/site/produto-detalhes.blade.php
git commit -m "feat: aviso pre-encomenda na pagina de produto Artisan"
```

---

## Task 4: TDD — integrar no checkout

**Files:**
- Modify: `resources/views/site/checkout.blade.php` (~linha 164)

Os testes já foram criados na Task 3. Agora rodar os de checkout para confirmar a falha e depois fazer o fix.

- [ ] **Step 1: Rodar os testes de checkout para confirmar falha**

```bash
docker exec laravel-app php artisan test --filter=ArtisanPreorderNoticeTest::test_checkout_with_artisan_item_shows_preorder_notice
docker exec laravel-app php artisan test --filter=ArtisanPreorderNoticeTest::test_checkout_without_artisan_item_does_not_show_preorder_notice
```

Saída esperada: primeiro FAIL (texto não encontrado), segundo PASS.

- [ ] **Step 2: Adicionar o @include na view checkout**

Em `resources/views/site/checkout.blade.php`, localizar o título do Resumo do Pedido (por volta da linha 163):

```blade
                        <h3 class="text-lg sm:text-xl font-bold tracking-tight mb-4 sm:mb-6">Resumo do Pedido</h3>
```

Inserir o aviso logo após o `<h3>`, antes do `<!-- Order Items -->`:

```blade
                        <h3 class="text-lg sm:text-xl font-bold tracking-tight mb-4 sm:mb-6">Resumo do Pedido</h3>

                        @if($carrinho->itens->contains(fn($i) => $i->produto->marca === 'Artisan'))
                            @include('includes.aviso-preorder-artisan')
                        @endif

                        <!-- Order Items -->
```

- [ ] **Step 3: Rodar os testes de checkout para confirmar que passam**

```bash
docker exec laravel-app php artisan test --filter=ArtisanPreorderNoticeTest::test_checkout_with_artisan_item_shows_preorder_notice
docker exec laravel-app php artisan test --filter=ArtisanPreorderNoticeTest::test_checkout_without_artisan_item_does_not_show_preorder_notice
```

Saída esperada: ambos PASS.

- [ ] **Step 4: Rodar a suite completa para garantir que não há regressões**

```bash
docker exec laravel-app php artisan test
```

Saída esperada: todos os testes passam (32 tests ao total após adicionar os 4 novos).

- [ ] **Step 5: Limpar cache de views**

```bash
docker exec laravel-app php artisan view:clear
docker exec laravel-app php artisan cache:clear
```

- [ ] **Step 6: Commit final**

```bash
git add resources/views/site/checkout.blade.php
git commit -m "feat: aviso pre-encomenda no checkout quando ha item Artisan"
```

---

## Remoção futura

Quando os produtos Artisan estiverem disponíveis localmente:

```bash
# 1. Apagar o partial
rm resources/views/includes/aviso-preorder-artisan.blade.php

# 2. Remover o @if/@include de produto-detalhes.blade.php (bloco de 3 linhas)
# 3. Remover o @if/@include de checkout.blade.php (bloco de 3 linhas)
# 4. Apagar os 4 testes em ArtisanPreorderNoticeTest.php (ou o arquivo inteiro)

docker exec laravel-app php artisan view:clear
git commit -m "remove: aviso pre-encomenda Artisan (produtos em estoque local)"
```

O campo `marca = 'Artisan'` no banco permanece — é dado correto e não causa efeito colateral.
