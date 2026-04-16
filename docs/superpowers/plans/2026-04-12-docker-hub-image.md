# Docker Hub Image Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Publicar duas imagens de infraestrutura base no Docker Hub (`felipedaige/jfxtech:app` e `felipedaige/jfxtech:webserver`) sem código PHP da aplicação nem dados locais sensíveis.

**Architecture:** Dois Dockerfiles separados — `Dockerfile` (PHP-FPM) e `Dockerfile.webserver` (Nginx). Um `.dockerignore` compartilhado bloqueia código, segredos e artefatos locais. Um script `docker/build-push.sh` automatiza o build e push com tag opcional.

**Tech Stack:** Docker, PHP 8.3-FPM, Nginx Alpine, Docker Hub

---

## Mapa de Arquivos

| Arquivo | Ação | Responsabilidade |
|---|---|---|
| `.dockerignore` | Criar | Excluir código PHP, .env, node_modules, vendor, .git do build context |
| `docker/php/opcache.ini` | Criar | Configuração opcache de produção |
| `docker/php/php.ini` | Criar | Ajustes php.ini de produção |
| `Dockerfile` | Atualizar | Adicionar COPY dos arquivos de config PHP |
| `Dockerfile.webserver` | Criar | Nginx Alpine com config da aplicação |
| `docker/build-push.sh` | Criar | Script de build/push com tag opcional |

---

### Task 1: Criar `.dockerignore`

**Files:**
- Create: `.dockerignore`

- [ ] **Step 1: Criar o arquivo `.dockerignore`**

```
# Segredos e ambiente local
.env
.env.*

# Controle de versão
.git/
.gitignore
.gitattributes

# Dependências (instaladas em runtime via volume/bind mount)
node_modules/
vendor/

# Storage e artefatos de build frontend
storage/
public/build/
public/storage/
public/hot/

# Ferramentas de desenvolvimento local
.claude/
.superpowers/
.worktrees/
.codex

# Código PHP da aplicação
app/
bootstrap/
config/
database/
resources/
routes/
tests/
docs/
imports/

# Arquivos de configuração da aplicação (não pertencem à imagem base)
composer.json
composer.lock
package.json
package-lock.json
vite.config.js
phpunit.xml
artisan

# Documentação
*.md
README.md
AGENTS.md
CLAUDE.md
GEMINI.md
```

- [ ] **Step 2: Verificar que o `.dockerignore` não bloqueia o que deve entrar na imagem**

Os arquivos que DEVEM entrar no build context (e que não estão listados no `.dockerignore`):
- `Dockerfile` e `Dockerfile.webserver` — ok, não bloqueados
- `docker/` (pasta com configs PHP) — ok, não bloqueada
- `nginx/` (config Nginx) — ok, não bloqueada

- [ ] **Step 3: Commit**

```bash
git add .dockerignore
git commit -m "feat: adicionar .dockerignore para imagens base Docker Hub"
```

---

### Task 2: Criar configs PHP de produção

**Files:**
- Create: `docker/php/opcache.ini`
- Create: `docker/php/php.ini`

- [ ] **Step 1: Criar diretório e `docker/php/opcache.ini`**

```ini
[opcache]
opcache.enable=1
opcache.memory_consumption=128
opcache.interned_strings_buffer=8
opcache.max_accelerated_files=10000
opcache.revalidate_freq=0
opcache.validate_timestamps=0
opcache.save_comments=1
```

- [ ] **Step 2: Criar `docker/php/php.ini`**

```ini
upload_max_filesize=20M
post_max_size=20M
memory_limit=256M
max_execution_time=60
```

- [ ] **Step 3: Commit**

```bash
git add docker/php/opcache.ini docker/php/php.ini
git commit -m "feat: adicionar configs PHP de produção para imagem Docker"
```

---

### Task 3: Atualizar `Dockerfile` (PHP-FPM)

**Files:**
- Modify: `Dockerfile`

O `Dockerfile` atual já está correto em estrutura — só falta copiar os arquivos de config PHP que acabamos de criar.

- [ ] **Step 1: Adicionar COPY das configs PHP no `Dockerfile`**

Substituir o conteúdo completo do `Dockerfile` por:

```dockerfile
FROM php:8.3-fpm

# Install system dependencies
RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    libzip-dev \
    libpq-dev \
    zip \
    unzip \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

# Install PHP extensions
RUN docker-php-ext-install \
    pdo_pgsql \
    pgsql \
    mbstring \
    exif \
    pcntl \
    bcmath \
    gd \
    zip \
    opcache

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# PHP production configuration
COPY docker/php/opcache.ini /usr/local/etc/php/conf.d/opcache.ini
COPY docker/php/php.ini /usr/local/etc/php/conf.d/custom.ini

WORKDIR /var/www/html

USER www-data
```

- [ ] **Step 2: Commit**

```bash
git add Dockerfile
git commit -m "feat: adicionar configs PHP de produção ao Dockerfile"
```

---

### Task 4: Criar `Dockerfile.webserver` (Nginx)

**Files:**
- Create: `Dockerfile.webserver`

- [ ] **Step 1: Criar `Dockerfile.webserver`**

```dockerfile
FROM nginx:alpine

COPY nginx/ /etc/nginx/conf.d/

EXPOSE 80
```

- [ ] **Step 2: Commit**

```bash
git add Dockerfile.webserver
git commit -m "feat: adicionar Dockerfile.webserver para imagem Nginx"
```

---

### Task 5: Criar script de build/push

**Files:**
- Create: `docker/build-push.sh`

- [ ] **Step 1: Criar `docker/build-push.sh`**

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

echo "Building felipedaige/jfxtech:$APP_TAG ..."
docker build -t felipedaige/jfxtech:$APP_TAG .

echo "Building felipedaige/jfxtech:$WEB_TAG ..."
docker build -f Dockerfile.webserver -t felipedaige/jfxtech:$WEB_TAG .

echo "Logging in to Docker Hub..."
docker login

echo "Pushing felipedaige/jfxtech:$APP_TAG ..."
docker push felipedaige/jfxtech:$APP_TAG

echo "Pushing felipedaige/jfxtech:$WEB_TAG ..."
docker push felipedaige/jfxtech:$WEB_TAG

echo "Done! Images published:"
echo "  docker pull felipedaige/jfxtech:$APP_TAG"
echo "  docker pull felipedaige/jfxtech:$WEB_TAG"
```

- [ ] **Step 2: Tornar o script executável**

```bash
chmod +x docker/build-push.sh
```

- [ ] **Step 3: Commit**

```bash
git add docker/build-push.sh
git commit -m "feat: adicionar script de build/push para Docker Hub"
```

---

### Task 6: Build e verificação local da imagem `:app`

**Files:** nenhum (verificação)

- [ ] **Step 1: Build da imagem `:app`**

```bash
docker build -t felipedaige/jfxtech:app .
```

Esperado: build concluído sem erros. Deve baixar `php:8.3-fpm` e `composer:latest` e instalar as extensões.

- [ ] **Step 2: Verificar que o workdir está vazio (sem código PHP)**

```bash
docker run --rm felipedaige/jfxtech:app ls /var/www/html
```

Esperado: saída vazia ou mensagem "total 0". Se aparecer `app/`, `config/`, `artisan`, etc., o `.dockerignore` não está funcionando — revisar Task 1.

- [ ] **Step 3: Verificar extensões PHP**

```bash
docker run --rm felipedaige/jfxtech:app php -m | grep -E "pdo_pgsql|gd|zip|opcache"
```

Esperado:
```
gd
opcache
pdo_pgsql
zip
```

- [ ] **Step 4: Verificar que `.env` não existe na imagem**

```bash
docker run --rm felipedaige/jfxtech:app sh -c 'test ! -f /var/www/html/.env && echo "OK: sem .env" || echo "ERRO: .env encontrado!"'
```

Esperado: `OK: sem .env`

- [ ] **Step 5: Verificar config opcache**

```bash
docker run --rm felipedaige/jfxtech:app php -i | grep opcache.enable
```

Esperado: `opcache.enable => On => On`

---

### Task 7: Build e verificação local da imagem `:webserver`

**Files:** nenhum (verificação)

- [ ] **Step 1: Build da imagem `:webserver`**

```bash
docker build -f Dockerfile.webserver -t felipedaige/jfxtech:webserver .
```

Esperado: build concluído sem erros.

- [ ] **Step 2: Verificar config Nginx**

```bash
docker run --rm felipedaige/jfxtech:webserver nginx -t
```

Esperado:
```
nginx: the configuration file /etc/nginx/nginx.conf syntax is ok
nginx: configuration file /etc/nginx/nginx.conf test is successful
```

- [ ] **Step 3: Verificar que a config da aplicação foi copiada**

```bash
docker run --rm felipedaige/jfxtech:webserver ls /etc/nginx/conf.d/
```

Esperado: `app.conf` listado.

---

### Task 8: Push para Docker Hub

**Files:** nenhum

- [ ] **Step 1: Rodar o script de build/push**

```bash
bash docker/build-push.sh
```

O script irá solicitar login no Docker Hub (usuário: `felipedaige`, senha/token). Use um Access Token gerado em https://hub.docker.com/settings/security em vez da senha.

Esperado ao final:
```
Done! Images published:
  docker pull felipedaige/jfxtech:app
  docker pull felipedaige/jfxtech:webserver
```

- [ ] **Step 2: Verificar no Docker Hub**

Acessar https://hub.docker.com/r/felipedaige/jfxtech/tags e confirmar que as tags `:app` e `:webserver` aparecem.

- [ ] **Step 3: Testar pull em outra sessão (opcional mas recomendado)**

```bash
# Remover imagens locais para forçar pull
docker rmi felipedaige/jfxtech:app felipedaige/jfxtech:webserver

# Fazer pull como faria em outra máquina
docker pull felipedaige/jfxtech:app
docker pull felipedaige/jfxtech:webserver
```

Esperado: download das imagens do Docker Hub sem erros.
