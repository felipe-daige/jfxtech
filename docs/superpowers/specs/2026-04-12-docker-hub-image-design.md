# Docker Hub Image — Design Spec
**Data:** 2026-04-12  
**Status:** Aprovado

## Contexto

O projeto JFX Tech roda em Docker (PHP-FPM + Nginx + queue worker). Atualmente o `Dockerfile` só instala extensões PHP sem código de app, e não existe `.dockerignore`. O objetivo é publicar imagens de infraestrutura base no Docker Hub (`felipedaige/jfxtech`) para que possam ser usadas em outras máquinas via `docker pull`, sem expor o código-fonte da aplicação nem dados locais sensíveis.

## Objetivo

Publicar duas imagens de infraestrutura no repositório `felipedaige/jfxtech`:
- `:app` — PHP 8.3-FPM com extensões e configuração de produção
- `:webserver` — Nginx Alpine com a config da aplicação

Nenhuma das imagens deve conter: código PHP da aplicação, `.env`, `vendor/`, `node_modules/`, `.git/`, ou qualquer dado local.

## Arquivos

| Arquivo | Ação |
|---|---|
| `Dockerfile` | Atualizar — adicionar `COPY` de configs PHP, sem código de app |
| `Dockerfile.webserver` | Criar — Nginx Alpine copiando `nginx/` |
| `.dockerignore` | Criar — exclui código, segredos e artefatos locais |
| `docker/php/opcache.ini` | Criar — configuração opcache de produção |
| `docker/php/php.ini` | Criar — ajustes php.ini de produção |
| `docker/build-push.sh` | Criar — script de build/push com tag opcional |

## Dockerfile (app — PHP-FPM)

Base: `php:8.3-fpm`

Inclui:
- Dependências de sistema (git, curl, libpng, libzip, libpq, etc.)
- Extensões PHP: `pdo_pgsql`, `pgsql`, `mbstring`, `exif`, `pcntl`, `bcmath`, `gd`, `zip`, `opcache`
- Composer (copiado da imagem oficial)
- `docker/php/opcache.ini` → `/usr/local/etc/php/conf.d/opcache.ini`
- `docker/php/php.ini` → `/usr/local/etc/php/conf.d/custom.ini`
- `WORKDIR /var/www/html` (vazio — código chega via volume/bind mount em runtime)
- `USER www-data`

**Não inclui:** nenhum `COPY` de código PHP da aplicação.

## Dockerfile.webserver (Nginx)

Base: `nginx:alpine`

Inclui:
- `COPY nginx/ /etc/nginx/conf.d/`
- `EXPOSE 80`

## .dockerignore

Bloqueia tudo que não é configuração de infraestrutura:

```
.env
.env.*
.git/
.gitignore
node_modules/
vendor/
storage/
public/build/
public/storage/
public/hot/
.claude/
.superpowers/
.worktrees/
app/
bootstrap/
config/
database/
resources/
routes/
tests/
docs/
imports/
*.md
composer.json
composer.lock
package*.json
vite.config.js
phpunit.xml
artisan
README.md
```

## Configurações PHP (docker/php/)

**opcache.ini** — produção:
- `opcache.enable=1`
- `opcache.memory_consumption=128`
- `opcache.max_accelerated_files=10000`
- `opcache.validate_timestamps=0`

**php.ini** — ajustes:
- `upload_max_filesize=20M`
- `post_max_size=20M`
- `memory_limit=256M`
- `max_execution_time=60`

## Script de Build/Push (docker/build-push.sh)

```bash
#!/bin/bash
set -e
VERSION=$1

if [ -z "$VERSION" ]; then
  APP_TAG="app"
  WEB_TAG="webserver"
else
  APP_TAG="app-$VERSION"
  WEB_TAG="webserver-$VERSION"
fi

docker login

docker build -t felipedaige/jfxtech:$APP_TAG .
docker push felipedaige/jfxtech:$APP_TAG

docker build -f Dockerfile.webserver -t felipedaige/jfxtech:$WEB_TAG .
docker push felipedaige/jfxtech:$WEB_TAG
```

Uso:
- `bash docker/build-push.sh` → tags `:app` e `:webserver`
- `bash docker/build-push.sh 1.0` → tags `:app-1.0` e `:webserver-1.0`

## Uso na Outra Máquina

```yaml
services:
  app:
    image: felipedaige/jfxtech:app
    volumes:
      - .:/var/www/html
  webserver:
    image: felipedaige/jfxtech:webserver
    ports:
      - "80:80"
```

## Verificação

```bash
# Após o build, verificar que /var/www/html está vazio (sem código de app)
docker run --rm felipedaige/jfxtech:app ls /var/www/html

# Verificar extensões PHP presentes
docker run --rm felipedaige/jfxtech:app php -m | grep -E "pdo_pgsql|gd|zip|opcache"

# Verificar que .env não existe na imagem
docker run --rm felipedaige/jfxtech:app test ! -f .env && echo "OK: sem .env"

# Verificar Nginx
docker run --rm felipedaige/jfxtech:webserver nginx -t
```
