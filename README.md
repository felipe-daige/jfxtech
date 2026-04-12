# JFXTECH

Storefront em Laravel para catálogo e venda de hardware gamer, com vitrine pública, favoritos, carrinho autenticado, checkout e painel administrativo para gestão de produtos, categorias e pedidos.

**Docker Hub:** [`felipedaige/jfxtech`](https://hub.docker.com/r/felipedaige/jfxtech)

---

## Imagens Docker Hub

As imagens de infraestrutura base estão publicadas em `felipedaige/jfxtech`. Elas contêm o ambiente de runtime (PHP, Nginx, extensões, configurações), mas **não o código da aplicação** — o código é montado via volume em tempo de execução.

| Tag | Base | Conteúdo |
|---|---|---|
| `felipedaige/jfxtech:app` | `php:8.3-fpm` | PHP-FPM + extensões (pdo_pgsql, gd, zip, opcache, etc.) + configs de produção |
| `felipedaige/jfxtech:webserver` | `nginx:alpine` | Nginx com a configuração da aplicação |

### Pull

```bash
docker pull felipedaige/jfxtech:app
docker pull felipedaige/jfxtech:webserver
```

---

## Setup Completo (usando imagens do Docker Hub)

Siga esses passos para rodar o projeto em uma nova máquina sem precisar buildar as imagens localmente.

### 1. Pré-requisitos

- Docker e Docker Compose instalados
- Node.js 20+ instalado no host (para build dos assets Vite)
- PostgreSQL acessível (pode ser outro container ou instância remota)

### 2. Clone o repositório

```bash
git clone <url-do-repo> jfxtech
cd jfxtech
```

### 3. Configure o ambiente

```bash
cp .env.example .env
```

Edite o `.env` com as credenciais corretas:

```env
APP_KEY=          # gerar com: docker run --rm felipedaige/jfxtech:app php artisan key:generate --show
DB_HOST=          # host do PostgreSQL
DB_PORT=5432
DB_DATABASE=jfxtech
DB_USERNAME=...
DB_PASSWORD=...
```

### 4. Crie o `docker-compose.yml`

Crie um `docker-compose.yml` usando as imagens do Hub (sem build local):

```yaml
name: laravel

services:
  app:
    image: felipedaige/jfxtech:app
    container_name: laravel-app
    restart: unless-stopped
    volumes:
      - ./:/var/www/html
    networks:
      - laravel-network

  worker:
    image: felipedaige/jfxtech:app
    container_name: laravel-worker
    restart: unless-stopped
    command: php artisan queue:work --sleep=3 --tries=3 --max-time=3600
    volumes:
      - ./:/var/www/html
    networks:
      - laravel-network
    depends_on:
      - app

  webserver:
    image: felipedaige/jfxtech:webserver
    container_name: laravel-webserver
    restart: unless-stopped
    ports:
      - "80:80"
    volumes:
      - ./:/var/www/html
    networks:
      - laravel-network
    depends_on:
      - app

networks:
  laravel-network:
    driver: bridge
```

### 5. Suba os containers

```bash
docker compose up -d
```

### 6. Instale as dependências PHP

```bash
docker exec laravel-app composer install --no-dev --optimize-autoloader
```

### 7. Configure a aplicação

```bash
docker exec laravel-app php artisan key:generate
docker exec laravel-app php artisan migrate --force
docker exec laravel-app php artisan storage:link
docker exec laravel-app php artisan config:cache
docker exec laravel-app php artisan route:cache
docker exec laravel-app php artisan view:cache
```

### 8. Build dos assets frontend

```bash
npm install
npm run build
```

A aplicação estará disponível em `http://localhost`.

---

## Atualizar as Imagens (maintainer)

Para publicar novas versões no Docker Hub após alterações na infraestrutura (Dockerfile, configs PHP, config Nginx):

```bash
# Login no Docker Hub
docker login

# Build e push das duas imagens (tag :app e :webserver)
bash docker/build-push.sh

# Ou com versão específica
bash docker/build-push.sh 1.1
```

---

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
