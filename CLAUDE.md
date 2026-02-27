# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Production Environment

This app runs in Docker on a Linux server. All artisan/composer commands must be executed inside the `laravel-app` container:

```bash
docker exec laravel-app php artisan <command>
docker exec laravel-app composer <command>
```

Frontend assets are built on the host (Node.js available on host, not in container):

```bash
cd /var/www/html && npm run build
```

After editing Blade views, clear the view cache:

```bash
docker exec laravel-app php artisan view:clear
docker exec laravel-app php artisan cache:clear
```

Run migrations:

```bash
docker exec laravel-app php artisan migrate --force
```

Run tests:

```bash
docker exec laravel-app php artisan test
```

## Infrastructure (server-only, not in git)

- `docker-compose.yml` — defines three services: `laravel-app` (PHP-FPM), `laravel-webserver` (Nginx), `laravel-worker` (queue worker running `php artisan queue:work`)
- `Dockerfile` — PHP 8.3-FPM image with pgsql, gd, zip, opcache extensions
- `.env` — production secrets (DB, APP_KEY, etc.)
- `nginx/` — Nginx config
- Traefik handles SSL termination and proxying to `laravel-webserver`

## Architecture

**Stack:** Laravel 12 + PostgreSQL + Blade templates + Tailwind CSS 4 (via Vite) + vanilla JS

**No session/auth middleware on routes** — the `AdminAuth` middleware exists but is a passthrough. Admin access is enforced manually in each `AdminController` method via `if (!Auth::check())`. The cart and favorites use `session()` for guests and database for authenticated users.

### Controllers

| Controller | Responsibility |
|---|---|
| `SiteController` | Public pages: home, product listing, product detail, contact, auth (login/register/logout), checkout view, profile |
| `AdminController` | Full CRUD for products (with image upload), categories, order management and status updates |
| `CarrinhoController` | Cart CRUD via session; returns JSON for AJAX calls |
| `FavoritosController` | Favorites stored in `favoritos` table; returns JSON |
| `PedidoController` | Order creation (`store`) and display (`show`, `index`) |
| `PedidosController` | Alias for user order listing (`meus_pedidos`) |
| `CepController` | Proxies ViaCEP API for address lookup |
| `FreteController` | Shipping calculation |

### Models & Key Relationships

- `Produto` → `hasMany` `ProdutoImagem`, `hasOne` `ProdutoImagem` (where `capa=true`)
- `Produto` auto-generates unique slugs on create/update via model boot hooks
- `Produto` has accessors: `$produto->primeira_imagem`, `$produto->preco_com_desconto`, `$produto->em_estoque`
- `Pedido` → `hasMany` `ItemPedido` → `belongsTo` `Produto`
- `User` → `hasMany` `Favorito`, `hasMany` `Pedido`, `hasMany` `Endereco`

### Frontend JS Architecture

JavaScript is split between two locations:
- `resources/js/app.js` + `resources/css/app.css` — compiled by Vite, loaded via `@vite()` in every Blade view (Tailwind CSS + axios bootstrap)
- `public/js/*.js` — loaded directly via `asset('js/...')` per-page; these are **not** processed by Vite

Each Blade view has its own `<head>` (no shared layout file). Views include `@include('includes.header')` and `@include('includes.footer')` for nav/footer.

### Static Files & Storage

Product images are uploaded to `storage/app/public/produtos/` via `$image->store('produtos', 'public')` and served through the symlink `public/storage → storage/app/public`. The symlink must be created manually on the host (the container lacks permission):

```bash
ln -s /var/www/html/storage/app/public /var/www/html/public/storage
chown -R www-data:www-data /var/www/html/storage/app/public/
```

Image paths stored in `produto_imagens.caminho` are relative (e.g. `produtos/abc123.jpg`). Views reference them as `asset('storage/' . $imagem->caminho)`.

### Open Graph / Social Meta Tags

Configured in `resources/views/site/index.blade.php` (static) and `resources/views/site/produto-detalhes.blade.php` (dynamic, uses product image with fallback to `storage/images/jfxtech-link-preiew-opt.jpg`). The OG image must be under 300KB — use `convert` (ImageMagick) to compress before deploying large images.
