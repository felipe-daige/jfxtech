# Migração JFXTech → jusdock

**Data:** 2026-06-16
**Contexto:** JFXTech encerrou as operações. O mesmo VPS (Hetzner) será reaproveitado para o jusdock — SaaS jurídico (gestão de processos + documentos para escritórios de advocacia).

---

## Stack jusdock

| Camada | Tecnologia |
|---|---|
| Backend | Laravel 11 + PHP 8.5 |
| Banco principal | PostgreSQL |
| Cache / sessão / filas | Redis |
| Jobs Laravel | Laravel Horizon |
| Frontend | Vue 3 + Inertia.js + Tailwind CSS |
| Build assets | Vite (`npm run build`) |
| Scraper | Node.js / TypeScript + BullMQ + Playwright |
| Docker | Laravel Sail (`compose.yaml`) |
| Exceção de página | `/processos/oab` ainda é Blade (em migração) |

**Comunicação scraper ↔ Laravel:**
```
Laravel ──(Redis/BullMQ)──> Scraper Node ──(HTTP callback)──> Laravel
```
O scraper consome a fila `scraping` no Redis e devolve resultados via `routes/internal.php` usando Bearer token próprio.

---

## Infraestrutura do VPS

- **Hetzner:** 4 vCPU / 8 GB RAM / 80 GB disco
- **Traefik** permanece intacto durante toda a migração (SSL + roteamento)
- Sem novo VPS — reaproveitamento completo
- **DNS** atualizado pelo operador manualmente durante o deploy

---

## Mudanças de arquitetura antes de criar a imagem Docker

### 1. Base image do scraper (crítico)
Usar `mcr.microsoft.com/playwright:v1.x-jammy` como base do container do scraper em vez de imagem Node genérica. A imagem oficial do Playwright inclui todas as dependências de sistema (libglib, libnss, libx11, etc.) necessárias para rodar browsers headless. Sem isso, Playwright falha na primeira execução.

```dockerfile
# scraper/Dockerfile
FROM mcr.microsoft.com/playwright:v1.x-jammy
WORKDIR /app
COPY package*.json ./
RUN npm ci
COPY . .
RUN npm run build
CMD ["node", "dist/worker.js"]
```

### 2. Limite de memória no scraper
Playwright abre browsers reais. Sem limite, um scrape concorrente pode consumir toda a RAM e derrubar os outros serviços.

```yaml
# compose.yaml — serviço scraper
scraper:
  mem_limit: 3g
  memswap_limit: 3g
```

### 3. Health checks para ordem de boot
Redis e PostgreSQL devem estar prontos antes do Laravel e do scraper iniciarem.

```yaml
# compose.yaml
pgsql:
  healthcheck:
    test: ["CMD-SHELL", "pg_isready -U ${DB_USERNAME}"]
    interval: 5s
    retries: 5

redis:
  healthcheck:
    test: ["CMD", "redis-cli", "ping"]
    interval: 5s
    retries: 5

laravel:
  depends_on:
    pgsql:
      condition: service_healthy
    redis:
      condition: service_healthy

scraper:
  depends_on:
    redis:
      condition: service_healthy
```

### 4. Token dedicado para rotas internas
O Bearer token que o scraper usa para chamar `routes/internal.php` deve ser uma variável própria, separada do `APP_KEY`.

```env
INTERNAL_API_TOKEN=<token-gerado-separadamente>
```

Rotacionar este token sem afetar o restante da aplicação.

---

## Integração Traefik com o compose.yaml do Sail

Apenas o serviço `laravel` entra na `proxy-network`. Os demais ficam na rede interna.

```yaml
# compose.yaml — serviço laravel
laravel:
  networks:
    - sail
    - proxy-network
  labels:
    - "traefik.enable=true"
    - "traefik.http.routers.jusdock.rule=Host(`jusdock.com.br`)"
    - "traefik.http.routers.jusdock.entrypoints=websecure"
    - "traefik.http.routers.jusdock.tls.certresolver=letsencrypt"
    - "traefik.http.services.jusdock.loadbalancer.server.port=80"

networks:
  sail:
    driver: bridge
  proxy-network:
    external: true
```

---

## Plano de migração

### Fase 1 — Backup JFXTech

```bash
mkdir -p /var/backups/jfxtech

# Dump do banco
docker exec laravel-app bash -c \
  "pg_dump -U \$DB_USERNAME \$DB_DATABASE" \
  > /var/backups/jfxtech/db.sql

# Imagens de produto
docker cp laravel-app:/var/www/html/storage/app/public \
  /var/backups/jfxtech/storage

# Variáveis de ambiente
cp /var/www/html/.env /var/backups/jfxtech/.env

# Download local (rodar na sua máquina)
# scp -r root@<vps-ip>:/var/backups/jfxtech ./jfxtech-backup
```

### Fase 2 — Limpeza (Traefik continua rodando)

```bash
cd /var/www/html
docker compose down -v
cd /
rm -rf /var/www/html
mkdir /var/www/html
```

### Fase 3 — Deploy jusdock

```bash
# Clonar repositório
git clone git@github.com:<org>/jusdock.git /var/www/html
cd /var/www/html

# Configurar ambiente
cp .env.example .env
# editar .env com valores de produção

# Build do scraper
cd scraper && npm ci && npm run build && cd ..

# Build dos assets frontend (no host — Node não está no container)
npm ci && npm run build

# Subir containers
docker compose up -d

# Inicializar aplicação
docker compose exec laravel php artisan key:generate
docker compose exec laravel php artisan migrate --force
docker compose exec laravel php artisan storage:link
```

### Fase 4 — Verificação

```bash
# Containers rodando
docker compose ps

# Logs de inicialização
docker compose logs laravel --tail=50
docker compose logs scraper --tail=50

# Horizon
docker compose exec laravel php artisan horizon:status

# Fila Redis
docker compose exec redis redis-cli ping
```

---

## Containers jusdock

| Container | Papel | Rede |
|---|---|---|
| `laravel` | PHP-FPM + app (Inertia/Vue) | sail + proxy-network |
| `pgsql` | PostgreSQL | sail (interno) |
| `redis` | Cache + sessões + filas BullMQ | sail (interno) |
| `horizon` | Worker Laravel Horizon | sail (interno) |
| `scraper` | Node.js + Playwright + BullMQ workers | sail (interno) |

---

## Estimativa de uso de memória

| Serviço | Baseline | Pico (Playwright ativo) |
|---|---|---|
| Laravel | ~300 MB | ~400 MB |
| PostgreSQL | ~300 MB | ~500 MB |
| Redis | ~80 MB | ~100 MB |
| Horizon | ~150 MB | ~200 MB |
| Scraper | ~200 MB | ~1.5 GB |
| **Total** | **~1 GB** | **~2.7 GB** |

8 GB RAM oferece margem confortável para múltiplas sessões Playwright concorrentes.
