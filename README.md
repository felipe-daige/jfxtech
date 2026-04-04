# JFXTECH

Storefront em Laravel para catálogo e venda de hardware gamer, com vitrine pública, favoritos, carrinho autenticado, checkout e painel administrativo para gestão de produtos, categorias e pedidos.

## Stack

- Laravel 12
- PHP 8.3
- PostgreSQL
- Blade
- Tailwind CSS 4 via Vite
- JavaScript direto em `public/js` para interações específicas de página

## Execução

O projeto roda com Docker no backend e Node.js no host para os assets.

### Backend

Todos os comandos Laravel e Composer devem ser executados dentro do container `laravel-app`:

```bash
docker exec laravel-app composer install
docker exec laravel-app php artisan migrate --force
docker exec laravel-app php artisan test
```

### Frontend

Os assets Vite são gerados no host:

```bash
npm install
npm run dev
npm run build
```

## Estrutura Do Projeto

- `app/`:
  lógica da aplicação, controllers, models, exports e commands
- `routes/`:
  rotas web e comandos artisan
- `resources/views/`:
  Blade templates do site e admin
- `resources/js` e `resources/css`:
  assets compilados pelo Vite
- `public/js`:
  scripts carregados diretamente no navegador por página
- `database/`:
  migrations, seeders e factories

## Funcionalidades Principais

- Catálogo de produtos com filtros e páginas de detalhe
- Carrinho baseado em `Pedido` com status `carrinho`
- Favoritos por usuário autenticado
- Checkout e fluxo de pedidos
- Painel admin para CRUD de produtos, imagens, categorias e pedidos
- Gestão de variantes, specs e destaques

## Operação E Manutenção

Após alterações em views Blade, limpe os caches:

```bash
docker exec laravel-app php artisan view:clear
docker exec laravel-app php artisan cache:clear
```

As imagens dos produtos são armazenadas no disco `public` do Laravel e servidas por `public/storage`. Evite caminhos absolutos hardcoded nas views.

## Testes

Rodar a suíte principal:

```bash
docker exec laravel-app php artisan test
```

Se algum teste de exemplo falhar por ambiente local, valide ao menos os testes de domínio/fluxos alterados antes de publicar mudanças sensíveis.

## Observações

- Node roda no host, não dentro do container PHP.
- O projeto mistura assets via Vite e scripts estáticos em `public/js`, então mudanças nesses arquivos devem considerar versionamento de cache quando necessário.
