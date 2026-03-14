#!/usr/bin/env python3
"""
scraper-produtos.py — Extrai dados de produtos de nissei.com/br e comprasparaguai.com.br,
gera planilha Excel para revisão e, após confirmação, gera produtos_prontos.json
compatível com o ProductImporterSeeder.

Dependências:
    python3 -m pip install playwright openpyxl requests --break-system-packages
    python3 -m playwright install chromium
"""

import asyncio
import json
import os
import random
import re
import sys
import time
import urllib.parse
from abc import ABC, abstractmethod
from pathlib import Path

import requests
try:
    import curl_cffi.requests as _cf_requests
    _CF_SESSION = _cf_requests.Session(impersonate="chrome131")
except ImportError:
    _CF_SESSION = None

try:
    from ddgs import DDGS
    _DDG_AVAILABLE = True
except ImportError:
    try:
        from duckduckgo_search import DDGS
        _DDG_AVAILABLE = True
    except ImportError:
        _DDG_AVAILABLE = False

SCRIPT_DIR = Path(__file__).parent.resolve()
IMAGENS_DIR = SCRIPT_DIR / "imagens_produtos"
EXCEL_PATH = SCRIPT_DIR / "produtos_extraidos.xlsx"
JSON_PATH = SCRIPT_DIR / "produtos_prontos.json"
CATALOGO_PATH = SCRIPT_DIR / "catalogo.txt"

# Ordem importa: mais específico antes do mais genérico
CATEGORY_MAP = [
    ("mouse-pad",       "Mousepads"),
    ("mouse",           "Mouses"),
    ("teclado",         "Teclados"),
    ("auricular",       "Fones de Ouvido"),   # antes de "monitor" (evita /monitores/ no path)
    ("fone-de-ouvido",  "Fones de Ouvido"),
    ("headset",         "Fones de Ouvido"),
    ("monitor",         "Monitores"),
]


def detect_category(url: str) -> str:
    # Usar o slug (último segmento) para evitar falsos positivos de path (/monitores/)
    from urllib.parse import urlparse
    slug = urlparse(url).path.rstrip("/").split("/")[-1].lower()
    for keyword, category in CATEGORY_MAP:
        if keyword in slug:
            return category
    # Fallback: testar URL inteira
    url_lower = url.lower()
    for keyword, category in CATEGORY_MAP:
        if keyword in url_lower:
            return category
    return "Outros"

CDN_HINTS = [
    "cdn", "images", "img", "static", "media", "product", "foto", "imagem",
    "nissei", "comprasparaguai", "mlstatic", "mercadolibre",
]

IMAGE_EXTS = {".jpg", ".jpeg", ".png", ".webp", ".gif"}


# ---------------------------------------------------------------------------
# Helpers
# ---------------------------------------------------------------------------


# Mapeamento de slugs de marca → forma canônica
BRAND_MAP = {
    "pulsar": "Pulsar",
    "wlmouse": "WLMouse",
    "steelseries": "SteelSeries",
    "logitech": "Logitech",
    "benq": "BenQ",
    "zowie": "ZOWIE",
    "lamzu": "Lamzu",
    "attack-shark": "Attack Shark",
    "attackshark": "Attack Shark",
    "moondrop": "Moondrop",
    "sennheiser": "Sennheiser",
    "razer": "Razer",
    "hyperx": "HyperX",
    "corsair": "Corsair",
    "roccat": "ROCCAT",
}

# Prefixos de categoria a remover do slug
CATEGORY_PREFIXES = [
    "mouse-pad-", "mouse-gamer-", "mouse-",
    "teclado-gamer-mecanico-", "teclado-mecanico-", "teclado-gamer-", "teclado-",
    "auricular-gamer-", "auricular-monitor-gamer-", "auricular-monitor-",
    "auricular-", "fone-de-ouvido-gamer-", "fone-de-ouvido-",
    "headset-gamer-", "headset-",
    "monitor-gamer-", "monitor-",
]

# Palavras que não devem ser capitalizadas no título (exceto no início)
LOWER_WORDS = {"de", "e", "do", "da", "dos", "das", "em", "por", "para", "com", "sem"}


def slug_to_product_name(url: str) -> tuple[str, str]:
    """
    Converte slug da URL em (nome_produto, marca).
    Ex: 'mouse-gamer-pulsar-x3-crazylight-px3rcl101-dongle-8k-inalambrico'
    → ('Pulsar X3 Crazylight PX3RCL101 Dongle 8K', 'Pulsar')
    """
    # Extrair apenas o path final (último segmento não-vazio, sem query params)
    from urllib.parse import urlparse
    parsed = urlparse(url)
    path = parsed.path.rstrip("/")
    slug = path.split("/")[-1]

    # Remover __ID do ComprasParaguai
    slug = re.sub(r"__\d+$", "", slug)

    # Remover prefixo de categoria
    slug_lower = slug.lower()
    for prefix in CATEGORY_PREFIXES:
        if slug_lower.startswith(prefix):
            slug = slug[len(prefix):]
            break

    # Tokens do slug
    tokens = slug.split("-")

    # Identificar marca (buscar no mapa de marcas)
    brand = ""
    brand_end_idx = 0
    for i in range(1, min(4, len(tokens) + 1)):
        candidate = "-".join(tokens[:i]).lower()
        if candidate in BRAND_MAP:
            brand = BRAND_MAP[candidate]
            brand_end_idx = i
            break

    # Capitalizar tokens restantes
    name_tokens = tokens[brand_end_idx:]

    # Siglas sempre em maiúsculas
    ACRONYMS = {
        "xl", "xxl", "xs", "xsl", "dw", "rgb", "usb", "dpi", "ghz", "mhz", "hz",
        "fps", "ms", "mm", "cm", "kb", "mb", "gb", "tb", "bt", "tkl", "ips",
        "led", "lan", "wan", "usb-c", "he",
    }

    def capitalize_token(t: str) -> str:
        tl = t.lower()
        # Siglas conhecidas
        if tl in ACRONYMS:
            return t.upper()
        # Parece modelo alfanumérico (ex: X3, PX3RCL101, V3, FK2, 8K, 61661)
        if re.match(r"^[a-z0-9]+$", tl) and re.search(r"\d", tl):
            return t.upper()
        if tl in LOWER_WORDS:
            return t.lower()
        return t.capitalize()

    name_parts = [capitalize_token(t) for t in name_tokens]
    # Garantir que o primeiro token não fique em lowercase (exceto se já foi processado como sigla)
    if name_parts and name_parts[0].islower():
        name_parts[0] = name_parts[0].capitalize()

    product_name = " ".join(name_parts).strip()
    if brand:
        product_name = f"{brand} {product_name}".strip()

    return product_name or slug, brand


def parse_brl(raw: str) -> float:
    """'R$ 1.299,90' → 1299.90"""
    if not raw:
        return 0.0
    cleaned = re.sub(r"[^\d,]", "", raw)          # remove tudo exceto dígitos e vírgula
    cleaned = cleaned.replace(",", ".")            # vírgula → ponto decimal
    # Se houver múltiplos pontos, só o último é decimal
    parts = cleaned.split(".")
    if len(parts) > 2:
        cleaned = "".join(parts[:-1]) + "." + parts[-1]
    try:
        return float(cleaned)
    except ValueError:
        return 0.0


def slugify(text: str) -> str:
    text = text.lower().strip()
    text = re.sub(r"[^\w\s-]", "", text)
    text = re.sub(r"[\s_]+", "-", text)
    text = re.sub(r"-+", "-", text)
    return text[:80]


def url_stem(url: str) -> str:
    """Retorna o stem do path sem query params para deduplicação."""
    parsed = urllib.parse.urlparse(url)
    stem = Path(parsed.path).stem
    return stem


def is_product_image_url(url: str) -> bool:
    ext = Path(urllib.parse.urlparse(url).path).suffix.lower()
    if ext not in IMAGE_EXTS:
        return False
    url_lower = url.lower()
    return any(h in url_lower for h in CDN_HINTS)


def _image_quality_score(url: str) -> int:
    """Maior score = melhor qualidade provável."""
    u = url.lower()
    score = 0
    for bad in ("/cache/", "_thumbnail", "_small", "_mini", "_50x", "_100x",
                "_150x", "_200x", "_placeholder", "?w=", "?width=", "&w=",
                "thumb", "resize", "fit="):
        if bad in u:
            score -= 10
    for good in ("/original", "_original", "_full", "_large", "_zoom",
                 "/media/catalog/product/", "mlstatic.com", "D_NQ_NP"):
        if good in u:
            score += 5
    return score


def deduplicate_images(urls: list[str]) -> list[str]:
    """Agrupa por stem, mantém a URL com maior score de qualidade."""
    stem_map: dict[str, tuple[str, int]] = {}  # stem → (url, score)
    for url in urls:
        stem = url_stem(url)
        s = _image_quality_score(url)
        if stem not in stem_map or s > stem_map[stem][1]:
            stem_map[stem] = (url, s)
    return [url for url, _ in stem_map.values()]


def upgrade_mlstatic_url(url: str) -> str:
    """MLStatic CDN: troca sufixo de tamanho por _O (original)."""
    return re.sub(r"-[A-Z](\.[a-z]+)$", r"-O\1", url)


def _is_cf_blocked(nome: str, html_snippet: str = "") -> bool:
    """Verifica se o scraping foi bloqueado pelo CF (nome é domínio ou página de challenge)."""
    if not nome or nome == "Produto sem nome":
        return True
    if ".com" in nome and len(nome) < 30:
        return True
    if "_cf_chl_opt" in html_snippet:
        return True
    return False


def _quick_cf_check(url: str) -> bool:
    """
    Faz requisição HTTP rápida para verificar se o site está protegido por CF.
    Retorna True se CF está bloqueando (não vale tentar browser).
    """
    if _CF_SESSION is None:
        return False
    try:
        r = _CF_SESSION.get(url, timeout=8, allow_redirects=True)
        if r.status_code in (403, 503):
            text = r.text[:500]
            if "_cf_chl_opt" in text or "cf-turnstile" in text or "Just a moment" in text:
                return True
        return False
    except Exception:
        return False


def download_image(url: str, dest_path: Path, referer: str = "") -> bool:
    try:
        headers = {"User-Agent": "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/131.0.0.0 Safari/537.36"}
        if referer:
            headers["Referer"] = referer
        resp = requests.get(url, timeout=15, headers=headers)
        if resp.status_code == 200 and len(resp.content) > 2048:  # >2KB = não é placeholder
            dest_path.parent.mkdir(parents=True, exist_ok=True)
            dest_path.write_bytes(resp.content)
            return True
    except Exception as e:
        print(f"    [WARN] Falha ao baixar {url}: {e}")
    return False


async def _extract_json_ld_images(page) -> list[str]:
    """Extrai imagens do JSON-LD <script type='application/ld+json'>."""
    urls = []
    try:
        scripts = await page.query_selector_all('script[type="application/ld+json"]')
        for script in scripts:
            try:
                data = json.loads(await script.inner_text())
                items = data if isinstance(data, list) else [data]
                for item in items:
                    images = item.get("image", [])
                    if isinstance(images, str):
                        images = [images]
                    elif isinstance(images, dict):
                        images = [images.get("url", "")]
                    for img in images:
                        if img and img.startswith("http"):
                            urls.append(img)
            except Exception:
                pass
    except Exception:
        pass
    return urls


def _collect_urls_from_obj(obj, out: list):
    """Varre recursivamente um dict/list buscando valores que são URLs de imagem."""
    if isinstance(obj, str):
        if obj.startswith("http") and is_product_image_url(obj):
            out.append(obj)
    elif isinstance(obj, dict):
        for v in obj.values():
            _collect_urls_from_obj(v, out)
    elif isinstance(obj, list):
        for item in obj:
            _collect_urls_from_obj(item, out)


async def _extract_magento_gallery_images(page) -> list[str]:
    """Extrai imagens da galeria Magento 2 (x-magento-init JSON). Retorna URLs full-res."""
    urls = []
    try:
        scripts = await page.query_selector_all('script[type="text/x-magento-init"]')
        for script in scripts:
            try:
                raw = await script.inner_text()
                data = json.loads(raw)
                _collect_urls_from_obj(data, urls)
            except Exception:
                pass
    except Exception:
        pass

    try:
        els = await page.query_selector_all("[data-zoom-image]")
        for el in els:
            src = await el.get_attribute("data-zoom-image")
            if src and src.startswith("http"):
                urls.append(src)
    except Exception:
        pass

    # Remover path de cache Magento: /cache/<hash>/ → /
    cleaned = []
    for url in urls:
        cleaned.append(re.sub(r"/cache/[a-f0-9]+/", "/", url))

    return cleaned


# ---------------------------------------------------------------------------
# Base Scraper
# ---------------------------------------------------------------------------

class ProductData:
    def __init__(self):
        self.url: str = ""
        self.nome: str = "Produto sem nome"
        self.marca: str = ""
        self.categoria: str = "Outros"
        self.preco: float = 0.0
        self.descricao: str = ""
        self.descricao_curta: str = ""
        self.specs: dict = {}
        self.image_urls: list[str] = []
        self.local_images: list[Path] = []


def _is_valid_image_url(url: str) -> bool:
    """Verifica se URL é imagem válida (tem extensão de imagem, não é thumbnail minúsculo)."""
    ext = Path(urllib.parse.urlparse(url).path).suffix.lower()
    if ext not in IMAGE_EXTS and not any(e in url.lower() for e in [".jpg", ".jpeg", ".png", ".webp"]):
        return False
    bad = ("icon", "favicon", "logo-sm", "sprite", "placeholder", "1x1", "pixel")
    return not any(b in url.lower() for b in bad)


_DDG_LAST_CALL: float = 0.0
_DDG_MIN_INTERVAL: float = 4.0  # segundos entre chamadas


def fetch_images_duckduckgo(product_name: str, marca: str = "", max_results: int = 5) -> list[str]:
    """Busca imagens do produto via DuckDuckGo Images (sem API key). Retry com backoff."""
    global _DDG_LAST_CALL
    if not _DDG_AVAILABLE:
        return []

    query = f"{product_name} product"
    retries = [0, 8, 15]  # delays antes de cada tentativa (0=imediato, depois backoff)

    # Queries progressivamente simplificadas como fallback
    queries = [query]
    if marca:
        # Query mais simples: marca + primeiros 2 tokens do nome sem a marca
        name_tokens = product_name.replace(marca, "").strip().split()[:2]
        simple = f"{marca} {' '.join(name_tokens)}".strip()
        if simple != product_name:
            queries.append(simple)
    queries.append(product_name.split()[0] + " " + product_name.split()[1] if len(product_name.split()) >= 2 else product_name)

    for attempt, extra_wait in enumerate(retries):
        # Rate limiting: esperar intervalo mínimo entre chamadas
        elapsed = time.time() - _DDG_LAST_CALL
        needed = max(_DDG_MIN_INTERVAL - elapsed, 0) + extra_wait
        if needed > 0:
            if extra_wait > 0:
                print(f"    [DDG] Rate limited, aguardando {extra_wait}s antes de retry {attempt + 1}...")
            time.sleep(needed)

        q = queries[min(attempt, len(queries) - 1)]
        urls = []
        try:
            _DDG_LAST_CALL = time.time()
            with DDGS() as ddgs:
                results = list(ddgs.images(q, max_results=max_results * 3, type_image="photo"))
                for r in results:
                    img_url = r.get("image", "")
                    if img_url and _is_valid_image_url(img_url):
                        urls.append(img_url)
                    if len(urls) >= max_results:
                        break
            if urls:
                return urls
            # Sem resultados pode ser rate limit disfarçado — tentar com query mais simples
            if attempt < len(retries) - 1:
                print(f"    [DDG] Sem resultados, tentando novamente com query simplificada...")
                continue
        except Exception as e:
            err_str = str(e)
            # "No results found" pode ser rate limit disfarçado — sempre retry
            is_retriable = (
                "no results" in err_str.lower()
                or "ratelimit" in err_str.lower()
                or "403" in err_str
                or "202" in err_str
                or "timeout" in err_str.lower()
            )
            if attempt < len(retries) - 1 and is_retriable:
                print(f"    [DDG] Falha ({err_str[:50]}), tentando novamente...")
                continue
            print(f"    [DDG] Erro ao buscar imagens: {e}")
        break

    return urls


def _slug_fallback(url: str) -> "ProductData":
    """Cria ProductData a partir do slug + busca imagens via DDG."""
    data = ProductData()
    data.url = url
    data.categoria = detect_category(url)
    nome, marca = slug_to_product_name(url)
    data.nome = nome
    data.marca = marca
    print(f"    [DDG] Buscando imagens para: {nome[:50]}...")
    data.image_urls = fetch_images_duckduckgo(nome, marca, max_results=5)
    print(f"    [DDG] {len(data.image_urls)} imagens encontradas.")
    return data


class BaseScraper(ABC):
    def __init__(self, browser):
        self.browser = browser
        self._cookie_store: dict[str, list] = {}  # domain → cookies

    async def scrape(self, url: str) -> ProductData | None:
        try:
            return await self._scrape_one(url)
        except Exception as e:
            print(f"  [ERROR] {url}: {e}")
            return None

    @abstractmethod
    async def _scrape_one(self, url: str) -> ProductData:
        pass

    async def _save_cookies(self, context, domain: str):
        self._cookie_store[domain] = await context.cookies()

    async def _load_cookies(self, context, domain: str):
        if domain in self._cookie_store:
            await context.add_cookies(self._cookie_store[domain])

    async def _handle_cloudflare(self, page) -> bool:
        """Retorna True se havia CF challenge e ele foi resolvido."""
        CF_TITLE_KW = (
            "just a moment", "checking your browser", "attention required",
            "cloudflare", "um momento", "aguardando", "verificação de segurança",
            "security check", "please wait", "aguarde",
        )

        def _is_cf_title(title: str) -> bool:
            t = title.lower()
            return any(kw in t for kw in CF_TITLE_KW)

        def _is_cf_page(html: str) -> bool:
            return "_cf_chl_opt" in html or "cf-turnstile" in html or "challenges.cloudflare.com" in html

        try:
            title = await page.title()
        except Exception:
            return False

        if not _is_cf_title(title):
            # Verificar também pelo conteúdo da página
            try:
                html = await page.content()
                if not _is_cf_page(html):
                    return False
            except Exception:
                return False

        print("    [CF] Challenge detectado, aguardando resolução automática...")

        # Aguardar o Turnstile auto-resolver (timeout curto — CF managed raramente resolve em headless)
        try:
            await page.wait_for_function(
                """() => {
                    const title = document.title.toLowerCase();
                    const cfKw = ['just a moment','checking your browser','um momento','aguardando','verificação de segurança','security check','please wait'];
                    const stillCF = cfKw.some(s => title.includes(s));
                    const hasCFScript = !!document.querySelector('script') && document.body.innerHTML.includes('_cf_chl_opt');
                    return !stillCF && !hasCFScript;
                }""",
                timeout=8000,
                polling=1000,
            )
            await page.wait_for_load_state("domcontentloaded", timeout=8000)
            await page.wait_for_timeout(1500)
            print("    [CF] Challenge resolvido.")
            return True
        except Exception:
            print("    [CF] Challenge não resolveu — usando dados do slug.")
            return False

    async def _collect_images_from_page(self, page, max_thumbnails=10) -> list[str]:
        """Coleta imagens via listener de rede + interação com galeria."""
        collected: list[str] = []

        def on_response(response):
            if response.request.resource_type == "image":
                url = response.url
                if is_product_image_url(url):
                    collected.append(url)

        page.on("response", on_response)

        # Clica em thumbnails para acionar carregamento de imagens em alta res
        try:
            thumbs = await page.query_selector_all("img[data-thumb], .thumb, .thumbnail, [class*='thumb'], [class*='gallery'] img")
            for i, thumb in enumerate(thumbs[:max_thumbnails]):
                try:
                    await thumb.click()
                    await page.wait_for_timeout(350)
                except Exception:
                    pass
        except Exception:
            pass

        await page.wait_for_timeout(500)
        page.remove_listener("response", on_response)
        return collected


# ---------------------------------------------------------------------------
# Nissei Scraper
# ---------------------------------------------------------------------------

class NisseiScraper(BaseScraper):

    async def _scrape_one(self, url: str) -> ProductData:
        # Pré-check: CF bloqueando? → usar slug imediatamente sem abrir browser
        if _quick_cf_check(url):
            print(f"    [CF-BLOCK] Site bloqueado por CF, usando dados do slug.")
            return _slug_fallback(url)

        data = ProductData()
        data.url = url
        data.categoria = detect_category(url)

        collected_images: list[str] = []

        domain = urllib.parse.urlparse(url).netloc
        context = await self.browser.new_context(
            user_agent="Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/131.0.0.0 Safari/537.36",
            viewport={"width": 1280, "height": 800},
            locale="pt-BR",
            timezone_id="America/Sao_Paulo",
            extra_http_headers={
                "Accept-Language": "pt-BR,pt;q=0.9,en-US;q=0.8,en;q=0.7",
            },
        )
        await self._load_cookies(context, domain)
        page = await context.new_page()

        from playwright_stealth import Stealth
        stealth = Stealth(
            webgl_vendor_override="Intel Inc.",
            webgl_renderer_override="Intel Iris OpenGL Engine",
            navigator_languages_override=("pt-BR", "pt", "en-US", "en"),
        )
        await stealth.apply_stealth_async(page)

        def on_response(response):
            if response.request.resource_type == "image":
                img_url = response.url
                if is_product_image_url(img_url):
                    collected_images.append(img_url)

        page.on("response", on_response)

        try:
            await page.goto(url, wait_until="domcontentloaded", timeout=60000)
            cf_resolved = await self._handle_cloudflare(page)
            await self._save_cookies(context, domain)

            # CF pode redirecionar para home após resolver — re-navegar para o produto
            current_url = page.url
            if cf_resolved and url != current_url and url not in current_url:
                print(f"    [CF] Redirecionado para {current_url[:60]}, re-navegando para o produto...")
                await page.goto(url, wait_until="domcontentloaded", timeout=30000)
                await self._handle_cloudflare(page)
                await page.wait_for_timeout(2000)

            # Esperar carregar conteúdo real da página do produto
            try:
                await page.wait_for_load_state("networkidle", timeout=5000)
            except Exception:
                pass
            await page.wait_for_timeout(500)

            try:
                await page.wait_for_selector("h1", timeout=10000)
            except Exception:
                pass

            # Nome — tentar seletores específicos de produto antes do h1 genérico
            try:
                nome_el = None
                for sel in [
                    ".page-title h1", ".page-title-wrapper h1", "[itemprop='name']",
                    ".product-name h1", ".product-title", "h1.product_title",
                    "h1",
                ]:
                    nome_el = await page.query_selector(sel)
                    if nome_el:
                        text = (await nome_el.inner_text()).strip()
                        # Ignorar h1 que parecem domínios (CF challenge)
                        if text and ".com" not in text and len(text) > 5:
                            data.nome = text
                            break
            except Exception:
                pass

            # Fallback: se nome ainda parece de página CF, usar slug
            if _is_cf_blocked(data.nome):
                slug_nome, slug_marca = slug_to_product_name(url)
                data.nome = slug_nome
                data.marca = slug_marca
                print(f"    [SLUG] Nome extraído do slug: {data.nome}")
            else:
                # Marca (meta ou breadcrumb)
                try:
                    brand_el = await page.query_selector("[itemprop='brand'], .brand, [class*='brand']")
                    if brand_el:
                        data.marca = (await brand_el.inner_text()).strip()
                    else:
                        _, slug_marca = slug_to_product_name(url)
                        data.marca = slug_marca
                except Exception:
                    pass

            # Preço
            try:
                price_selectors = [
                    "[class*='price'] [class*='current']",
                    "[class*='price']:not([class*='old']):not([class*='before'])",
                    "[itemprop='price']",
                    ".price",
                    "[class*='Price']",
                ]
                for sel in price_selectors:
                    els = await page.query_selector_all(sel)
                    for el in els:
                        raw = (await el.inner_text()).strip()
                        price = parse_brl(raw)
                        if price > 0:
                            data.preco = price
                            break
                    if data.preco > 0:
                        break
            except Exception:
                pass

            # Specs — tabela ou dl
            try:
                specs: dict = {}
                # Tabela de especificações
                rows = await page.query_selector_all("table tr, dl dt")
                for row in rows:
                    try:
                        cells = await row.query_selector_all("td, dd")
                        if len(cells) >= 2:
                            key = (await cells[0].inner_text()).strip().rstrip(":")
                            val = (await cells[1].inner_text()).strip()
                            if key and val:
                                specs[key] = val
                        elif row.tag_name if hasattr(row, "tag_name") else "" == "dt":
                            # dt/dd pair
                            key = (await row.inner_text()).strip().rstrip(":")
                            dd = await row.evaluate_handle("el => el.nextElementSibling")
                            if dd:
                                val = await page.evaluate("el => el ? el.innerText : ''", dd)
                                if key and val:
                                    specs[key] = val.strip()
                    except Exception:
                        pass

                # Fallback: lista de especificações
                if not specs:
                    lis = await page.query_selector_all("[class*='spec'] li, [class*='feature'] li, [class*='detail'] li")
                    for li in lis:
                        text = (await li.inner_text()).strip()
                        if ":" in text:
                            k, _, v = text.partition(":")
                            specs[k.strip()] = v.strip()

                data.specs = specs
            except Exception:
                pass

            # Descrição
            try:
                desc_selectors = [
                    "[class*='description']",
                    "[itemprop='description']",
                    ".desc",
                    "[class*='detail'] p",
                ]
                for sel in desc_selectors:
                    el = await page.query_selector(sel)
                    if el:
                        text = (await el.inner_text()).strip()
                        if len(text) > 20:
                            data.descricao = text[:2000]
                            data.descricao_curta = text[:200]
                            break
            except Exception:
                pass

            # Galeria — clicar thumbnails
            try:
                thumb_selectors = [
                    "[class*='thumbnail'] img",
                    "[class*='thumb'] img",
                    "[class*='gallery'] img",
                    "[class*='carousel'] img",
                    ".slick-slide img",
                ]
                for sel in thumb_selectors:
                    thumbs = await page.query_selector_all(sel)
                    for thumb in thumbs[:10]:
                        try:
                            await thumb.click()
                            await page.wait_for_timeout(350)
                        except Exception:
                            pass
            except Exception:
                pass

            await page.wait_for_timeout(500)

            # Coletar também imagens do DOM (src + data-src + zoom attrs)
            try:
                dom_imgs = await page.evaluate("""() => {
                    const imgs = [];
                    document.querySelectorAll('img, [data-zoom-image], [data-full], [data-large]').forEach(el => {
                        const candidates = [
                            el.getAttribute('data-zoom-image'),
                            el.getAttribute('data-full'),
                            el.getAttribute('data-large'),
                            el.getAttribute('data-original'),
                            el.getAttribute('data-src'),
                            el.getAttribute('data-lazy'),
                            el.src || '',
                        ];
                        for (const src of candidates) {
                            if (src && src.startsWith('http')) { imgs.push(src); break; }
                        }
                    });
                    return imgs;
                }""")
                for img_url in dom_imgs:
                    if is_product_image_url(img_url):
                        collected_images.append(img_url)
            except Exception:
                pass

            # Extrair imagens de alta-res do Magento e JSON-LD
            magento_imgs = await _extract_magento_gallery_images(page)
            jsonld_imgs = await _extract_json_ld_images(page)
            for img in magento_imgs + jsonld_imgs:
                if is_product_image_url(img):
                    collected_images.append(img)

        finally:
            await page.close()
            await context.close()

        data.image_urls = deduplicate_images(collected_images)
        # Fallback DDG se nenhuma imagem foi coletada
        if not data.image_urls:
            print(f"    [DDG] Sem imagens do site, buscando via DuckDuckGo...")
            data.image_urls = fetch_images_duckduckgo(data.nome, data.marca, max_results=5)
            print(f"    [DDG] {len(data.image_urls)} imagens encontradas.")
        return data


# ---------------------------------------------------------------------------
# ComprasParaguai Scraper
# ---------------------------------------------------------------------------

class ComprasParaguaiScraper(BaseScraper):

    async def _scrape_one(self, url: str) -> ProductData:
        # Pré-check: CF bloqueando? → usar slug imediatamente sem abrir browser
        if _quick_cf_check(url):
            print(f"    [CF-BLOCK] Site bloqueado por CF, usando dados do slug.")
            return _slug_fallback(url)

        data = ProductData()
        data.url = url
        data.categoria = detect_category(url)

        collected_images: list[str] = []

        domain = urllib.parse.urlparse(url).netloc
        context = await self.browser.new_context(
            user_agent="Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/131.0.0.0 Safari/537.36",
            viewport={"width": 1280, "height": 800},
            locale="pt-BR",
            timezone_id="America/Sao_Paulo",
            extra_http_headers={
                "Accept-Language": "pt-BR,pt;q=0.9,en-US;q=0.8,en;q=0.7",
            },
        )
        await self._load_cookies(context, domain)
        page = await context.new_page()

        from playwright_stealth import Stealth
        stealth = Stealth(
            webgl_vendor_override="Intel Inc.",
            webgl_renderer_override="Intel Iris OpenGL Engine",
            navigator_languages_override=("pt-BR", "pt", "en-US", "en"),
        )
        await stealth.apply_stealth_async(page)

        def on_response(response):
            if response.request.resource_type == "image":
                img_url = response.url
                if is_product_image_url(img_url):
                    collected_images.append(img_url)

        page.on("response", on_response)

        try:
            await page.goto(url, wait_until="domcontentloaded", timeout=60000)
            cf_resolved = await self._handle_cloudflare(page)
            await self._save_cookies(context, domain)

            # CF pode redirecionar para home após resolver — re-navegar para o produto
            current_url = page.url
            if cf_resolved and url != current_url and url not in current_url:
                print(f"    [CF] Redirecionado para {current_url[:60]}, re-navegando para o produto...")
                await page.goto(url, wait_until="domcontentloaded", timeout=30000)
                await self._handle_cloudflare(page)

            await page.wait_for_timeout(2000)

            # Scroll lento para acionar lazy loading
            await page.evaluate("""async () => {
                for (let i = 0; i < 5; i++) {
                    window.scrollBy(0, 300);
                    await new Promise(r => setTimeout(r, 400));
                }
                window.scrollTo(0, 0);
            }""")

            try:
                await page.wait_for_load_state("networkidle", timeout=8000)
            except Exception:
                pass

            # Nome — tentar seletores específicos antes do h1 genérico
            try:
                nome_el = None
                for sel in [
                    ".page-title h1", "[itemprop='name']", ".product-name",
                    ".product_title", "h1",
                ]:
                    nome_el = await page.query_selector(sel)
                    if nome_el:
                        text = (await nome_el.inner_text()).strip()
                        if text and ".com" not in text and len(text) > 5:
                            data.nome = text
                            break
            except Exception:
                pass

            # Fallback: se nome parece de página CF, usar slug
            if _is_cf_blocked(data.nome):
                slug_nome, slug_marca = slug_to_product_name(url)
                data.nome = slug_nome
                data.marca = slug_marca
                print(f"    [SLUG] Nome extraído do slug: {data.nome}")
            else:
                # Marca
                try:
                    brand_el = await page.query_selector("[class*='brand'], [itemprop='brand'], .marca")
                    if brand_el:
                        data.marca = (await brand_el.inner_text()).strip()
                    else:
                        _, slug_marca = slug_to_product_name(url)
                        data.marca = slug_marca
                except Exception:
                    pass

            # Preço
            try:
                price_selectors = [
                    "[class*='price']:not([class*='old'])",
                    "[class*='Price']:not([class*='Old'])",
                    "[itemprop='price']",
                    ".preco",
                ]
                for sel in price_selectors:
                    els = await page.query_selector_all(sel)
                    for el in els:
                        raw = (await el.inner_text()).strip()
                        price = parse_brl(raw)
                        if price > 0:
                            data.preco = price
                            break
                    if data.preco > 0:
                        break
            except Exception:
                pass

            # Specs
            try:
                specs: dict = {}
                rows = await page.query_selector_all("table tr")
                for row in rows:
                    cells = await row.query_selector_all("td, th")
                    if len(cells) >= 2:
                        key = (await cells[0].inner_text()).strip().rstrip(":")
                        val = (await cells[1].inner_text()).strip()
                        if key and val:
                            specs[key] = val
                if not specs:
                    lis = await page.query_selector_all("[class*='spec'] li, [class*='attribute'] li, [class*='detail'] li")
                    for li in lis:
                        text = (await li.inner_text()).strip()
                        if ":" in text:
                            k, _, v = text.partition(":")
                            specs[k.strip()] = v.strip()
                data.specs = specs
            except Exception:
                pass

            # Descrição
            try:
                desc_selectors = [
                    "[class*='description']",
                    "[itemprop='description']",
                    ".descricao",
                    ".desc",
                ]
                for sel in desc_selectors:
                    el = await page.query_selector(sel)
                    if el:
                        text = (await el.inner_text()).strip()
                        if len(text) > 20:
                            data.descricao = text[:2000]
                            data.descricao_curta = text[:200]
                            break
            except Exception:
                pass

            # Clicar thumbnails da galeria
            try:
                thumbs = await page.query_selector_all("[class*='thumb'] img, [class*='gallery'] img, [class*='carousel'] img")
                for thumb in thumbs[:10]:
                    try:
                        await thumb.click()
                        await page.wait_for_timeout(350)
                    except Exception:
                        pass
            except Exception:
                pass

            await page.wait_for_timeout(500)

            # DOM images
            try:
                dom_imgs = await page.evaluate("""() => {
                    const imgs = [];
                    document.querySelectorAll('img').forEach(img => {
                        const src = img.src || img.getAttribute('data-src') || img.getAttribute('data-zoom-image') || '';
                        if (src && src.startsWith('http')) imgs.push(src);
                    });
                    return imgs;
                }""")
                for img_url in dom_imgs:
                    if is_product_image_url(img_url):
                        collected_images.append(img_url)
            except Exception:
                pass

            # JSON-LD pode conter imagens full-res
            jsonld_imgs = await _extract_json_ld_images(page)
            for img in jsonld_imgs:
                if is_product_image_url(img):
                    collected_images.append(img)

        finally:
            await page.close()
            await context.close()

        # Upgrade URLs do MLStatic CDN para resolução original
        collected_images = [
            upgrade_mlstatic_url(u) if "mlstatic.com" in u else u
            for u in collected_images
        ]
        data.image_urls = deduplicate_images(collected_images)
        # Fallback DDG se nenhuma imagem foi coletada
        if not data.image_urls:
            print(f"    [DDG] Sem imagens do site, buscando via DuckDuckGo...")
            data.image_urls = fetch_images_duckduckgo(data.nome, data.marca, max_results=5)
            print(f"    [DDG] {len(data.image_urls)} imagens encontradas.")
        return data


# ---------------------------------------------------------------------------
# Image Downloader
# ---------------------------------------------------------------------------

def download_images_for_product(data: ProductData) -> None:
    if not data.image_urls:
        return
    slug = slugify(data.nome)
    dest_dir = IMAGENS_DIR / slug
    dest_dir.mkdir(parents=True, exist_ok=True)
    local: list[Path] = []
    for i, url in enumerate(data.image_urls):
        ext = Path(urllib.parse.urlparse(url).path).suffix.lower()
        if ext not in IMAGE_EXTS:
            ext = ".jpg"
        dest = dest_dir / f"{i}{ext}"
        if download_image(url, dest, referer=data.url):
            local.append(dest)
    data.local_images = local


# ---------------------------------------------------------------------------
# Excel Exporter
# ---------------------------------------------------------------------------

class ExcelExporter:
    def export(self, products: list[ProductData]) -> None:
        import pandas as pd
        rows = []
        for p in products:
            paths_str = "; ".join(str(lp) for lp in p.local_images) if p.local_images else "—"
            rows.append({
                "Categoria":       p.categoria,
                "Nome":            p.nome,
                "Preço":           p.preco,
                "Descricao":       p.descricao_curta or "—",
                "Qtd Imagens":     len(p.image_urls),
                "Caminho Imagens": paths_str,
            })
        df = pd.DataFrame(rows)
        df.to_excel(EXCEL_PATH, index=False)
        print(f"\n[OK] Excel salvo: {EXCEL_PATH}")


# ---------------------------------------------------------------------------
# Catalog Integrator
# ---------------------------------------------------------------------------

class CatalogIntegrator:
    def generate_json(self, products: list[ProductData]) -> None:
        output = []
        for p in products:
            imagens = []
            for i, url in enumerate(p.image_urls):
                imagens.append({"url": url, "capa": i == 0})

            output.append({
                "nome":            p.nome,
                "marca":           p.marca,
                "descricao":       p.descricao or p.nome,
                "descricao_curta": p.descricao_curta or p.nome[:200],
                "categoria":       p.categoria,
                "preco":           p.preco,
                "estoque":         10,
                "specs":           p.specs,
                "ativo":           True,
                "imagens":         imagens,
            })

        JSON_PATH.write_text(json.dumps(output, ensure_ascii=False, indent=2), encoding="utf-8")
        print(f"[OK] JSON salvo: {JSON_PATH}")
        print(f"     {len(output)} produto(s) exportados.")


def enviar_para_catalogo(products: list[ProductData]) -> None:
    """
    Mock/placeholder — seria responsável por fazer POST no banco de dados
    com os textos e imagens de cada produto.

    Exemplo de integração real:
        for p in products:
            payload = {"nome": p.nome, "preco": p.preco, ...}
            requests.post("https://api.jfxtech.com.br/produtos", json=payload)
    """
    print("[MOCK] enviar_para_catalogo() chamada — integração não ativada.")


# ---------------------------------------------------------------------------
# Main
# ---------------------------------------------------------------------------

async def main():
    # 1. Ler catálogo
    if not CATALOGO_PATH.exists():
        print(f"[ERROR] Arquivo não encontrado: {CATALOGO_PATH}")
        sys.exit(1)

    urls = [line.strip() for line in CATALOGO_PATH.read_text().splitlines() if line.strip().startswith("http")]
    nissei_urls = [u for u in urls if "nissei.com" in u]
    cp_urls = [u for u in urls if "comprasparaguai.com" in u]

    print(f"Catálogo carregado: {len(nissei_urls)} Nissei + {len(cp_urls)} ComprasParaguai = {len(urls)} total\n")

    all_products: list[ProductData] = []

    from playwright.async_api import async_playwright

    async with async_playwright() as pw:
        browser = await pw.chromium.launch(
            headless=True,
            args=[
                "--no-sandbox",
                "--disable-blink-features=AutomationControlled",
                "--disable-dev-shm-usage",
                "--disable-gpu",
                "--window-size=1280,800",
            ],
        )

        # 2a. Nissei — concorrência 1 (anti-Cloudflare)
        print("=== Raspando Nissei ===")
        sem = asyncio.Semaphore(1)
        scraper_nissei = NisseiScraper(browser)

        async def scrape_nissei(url: str):
            async with sem:
                print(f"  → {url}")
                result = await scraper_nissei.scrape(url)
                await asyncio.sleep(1.5)
                return result

        nissei_results = await asyncio.gather(*[scrape_nissei(u) for u in nissei_urls])
        for r in nissei_results:
            if r:
                all_products.append(r)
                print(f"  [OK] {r.nome[:60]} | R$ {r.preco:.2f} | {len(r.image_urls)} img")

        # 2b. ComprasParaguai — sequencial com delay
        print("\n=== Raspando ComprasParaguai ===")
        scraper_cp = ComprasParaguaiScraper(browser)
        for url in cp_urls:
            print(f"  → {url}")
            result = await scraper_cp.scrape(url)
            if result:
                all_products.append(result)
                print(f"  [OK] {result.nome[:60]} | R$ {result.preco:.2f} | {len(result.image_urls)} img")
            await asyncio.sleep(1)

        await browser.close()

    # 3. Download de imagens
    print(f"\n=== Baixando imagens ({len(all_products)} produtos) ===")
    for p in all_products:
        print(f"  {p.nome[:50]}: {len(p.image_urls)} URLs")
        download_images_for_product(p)
        print(f"    → {len(p.local_images)} imagens baixadas")

    # 4. Exportar Excel
    exporter = ExcelExporter()
    exporter.export(all_products)

    # 5. Confirmação
    print(f"\nTotal: {len(all_products)} produtos extraídos.")
    print(f"Revise o arquivo: {EXCEL_PATH}")
    print("Verifique nomes, preços, quantidades de imagens e categorias.\n")

    input("\nPlanilha gerada! Pressione Enter para simular o envio ao catálogo...")
    enviar_para_catalogo(all_products)

    # 6. Gerar JSON
    integrator = CatalogIntegrator()
    integrator.generate_json(all_products)

    print("\nPróximo passo:")
    print("  docker exec laravel-app php artisan db:seed --class=ProductImporterSeeder")


if __name__ == "__main__":
    asyncio.run(main())
