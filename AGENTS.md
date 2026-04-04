# Repository Guidelines

## Project Structure & Module Organization
This is a Laravel 12 storefront application. Core backend code lives in `app/`, with Eloquent models in `app/Models` and exports in `app/Exports`. Routes are defined in `routes/web.php` and `routes/console.php`. Database migrations, factories, and seeders are under `database/`. Blade views and Vite-managed assets live in `resources/views`, `resources/js`, and `resources/css`. Page-specific JavaScript that is served directly, not bundled by Vite, is in `public/js`. Public images and static assets are in `public/`.

## Build, Test, and Development Commands
This project runs in Docker. Execute Laravel and Composer commands inside the `laravel-app` container:

- `docker exec laravel-app php artisan test` runs the test suite.
- `docker exec laravel-app php artisan migrate --force` applies migrations.
- `docker exec laravel-app php artisan view:clear && docker exec laravel-app php artisan cache:clear` clears compiled Blade and app cache after view changes.
- `docker exec laravel-app composer install` installs PHP dependencies in the container.
- `npm run dev` starts Vite on the host for local asset development.
- `npm run build` builds production frontend assets on the host.

## Coding Style & Naming Conventions
Follow `.editorconfig`: UTF-8, LF line endings, spaces, and 4-space indentation (2 spaces for YAML). Format PHP with `vendor/bin/pint`. Use PSR-4 class naming under `App\\...`; keep models singular (`Produto`, `Pedido`) and tests suffixed with `Test`. Preserve the current split between Vite entry files in `resources/` and direct browser scripts in `public/js/`.

## Testing Guidelines
Tests use PHPUnit through Laravel’s test runner. Put request, controller, and integration coverage in `tests/Feature`; pure domain logic belongs in `tests/Unit`. Name files after the behavior under test, for example `ProdutoVarianteTest.php`. Run tests with `docker exec laravel-app php artisan test` before opening a PR.

## Commit & Pull Request Guidelines
Recent history uses concise conventional prefixes such as `feat:`, `fix:`, and `docs:`. Keep commit subjects imperative and specific, for example `fix: reset stock display when deselecting variant`. PRs should include a short summary, affected areas, migration or cache-clear steps if applicable, linked issues, and screenshots for Blade/UI changes.

## Environment & Deployment Notes
Node runs on the host, not in the PHP container. Product images are stored via Laravel’s `public` disk and served from `public/storage`, so avoid hardcoding absolute storage paths in views or scripts.
