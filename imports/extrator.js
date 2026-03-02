'use strict';

// =============================================================================
// SECTION 1 — REQUIRES E CONSTANTES GLOBAIS
// =============================================================================

const { chromium } = require('playwright');
const fs   = require('fs');
const path = require('path');

const INPUT_FILE  = '/var/www/html/imports/automacao-catalogar-mouses-jfx.txt';
const OUTPUT_FILE = '/var/www/html/imports/produtos_prontos.json';
const DELAY_MS    = 800;
const NAV_TIMEOUT = 15000;

// =============================================================================
// SECTION 2 — FILE I/O
// =============================================================================

function readProductList() {
  const raw = fs.readFileSync(INPUT_FILE, 'utf8');
  // Linhas ímpares (0-indexed par) contêm nomes; linhas pares (1-indexed ímpar) são em branco
  return raw
    .split('\n')
    .map(l => l.trim())
    .filter(l => l.length > 0);
}

function loadExistingResults() {
  if (fs.existsSync(OUTPUT_FILE)) {
    try {
      const data = JSON.parse(fs.readFileSync(OUTPUT_FILE, 'utf8'));
      const map = new Map();
      for (const p of data) map.set(p.nome, p);
      console.log(`[resume] ${map.size} produtos já processados encontrados em ${OUTPUT_FILE}`);
      return map;
    } catch (e) {
      console.warn('[resume] Falha ao ler resultados existentes, iniciando do zero:', e.message);
    }
  }
  return new Map();
}

function saveResults(map) {
  const arr = Array.from(map.values());
  fs.writeFileSync(OUTPUT_FILE, JSON.stringify(arr, null, 2), 'utf8');
}

// =============================================================================
// SECTION 3 — SLUG GENERATION (espelha Str::slug() do Laravel)
// =============================================================================

function toSlug(nome) {
  return nome
    .normalize('NFD')                        // decompose diacritics
    .replace(/[\u0300-\u036f]/g, '')         // strip diacritic marks
    .toLowerCase()
    .replace(/\[/g, '')                      // strip brackets
    .replace(/\]/g, '')
    .replace(/\//g, '-')                     // slash → hyphen
    .replace(/[^a-z0-9\s-]/g, '')           // strip non-alphanumeric
    .replace(/\s+/g, '-')                    // spaces → hyphens
    .replace(/-+/g, '-')                     // collapse hyphens
    .replace(/^-|-$/g, '');                  // trim leading/trailing hyphens
}

// =============================================================================
// SECTION 4 — BRAND DETECTION
// =============================================================================

function determineBrand(nome) {
  const n = nome.toLowerCase();
  if (n.startsWith('finalmouse'))      return 'Finalmouse';
  if (n.startsWith('deathadder') || n.startsWith('viper')) return 'Razer';
  if (n.startsWith('atk'))            return 'ATK';
  if (n.startsWith('zowie'))          return 'Zowie';
  if (n.startsWith('pulsar'))         return 'Pulsar';
  if (n.startsWith('glorious'))       return 'Glorious';
  if (n.startsWith('endgame gear'))   return 'Endgame Gear';
  if (n.startsWith('corsair'))        return 'Corsair';
  if (n.startsWith('arye'))           return 'Arye';
  if (n.startsWith('arbiter'))        return 'Arbiter';
  if (n.startsWith('cobra'))          return 'Cobra';
  if (n.startsWith('orbital works'))  return 'Orbital Works';
  if (n.startsWith('teevolution'))    return 'Teevolution';
  return 'Desconhecida';
}

// =============================================================================
// SECTION 5 — URL BUILDER
// =============================================================================

function buildProductUrl(brand, nome) {
  const slug = toSlug(nome);

  switch (brand) {
    case 'Finalmouse':
      return {
        primary: `https://finalmouse.com/products/${slug}`,
        fallback: `https://finalmouse.com/products/${slug}-mouse`,
      };

    case 'Razer': {
      // Razer usa slugs com hífens no padrão deathadder-v4-pro
      return {
        primary:  `https://www.razer.com/gaming-mice/${slug}`,
        fallback: `https://www.razer.com/gaming-mice/Razer-${nome.split(' ').join('-')}`,
      };
    }

    case 'ATK':
      return {
        primary: `https://atk.gg/products/${slug}`,
        fallback: `https://atk.gg/collections/mouse/products/${slug}`,
      };

    case 'Zowie': {
      // Zowie URL usa apenas o modelo: EC1-DW → ec1-dw
      const model = nome.replace('Zowie ', '').toLowerCase().replace(/\s+/g, '-');
      return {
        primary:  `https://zowie.benq.com/en-us/mouse/${model}.html`,
        fallback: `https://zowie.benq.com/en/mouse/${model}.html`,
      };
    }

    case 'Pulsar':
      return {
        primary: `https://www.pulsargg.com/products/${slug}`,
        fallback: `https://www.pulsargg.com/collections/mice/products/${slug}`,
      };

    case 'Glorious':
      return {
        primary: `https://www.gloriousgaming.com/products/${slug}`,
        fallback: `https://www.gloriousgaming.com/collections/gaming-mice/products/${slug}`,
      };

    case 'Endgame Gear':
      return {
        primary: `https://www.endgamegear.gg/products/${slug}`,
        fallback: `https://www.endgamegear.gg/collections/mice/products/${slug}`,
      };

    default:
      return { primary: null, fallback: null };
  }
}

// =============================================================================
// SECTION 6 — IMAGE URL CLEANER
// =============================================================================

function cleanImageUrl(url) {
  if (!url) return null;
  // Strip Shopify size suffixes: _800x.jpg, _800x600.jpg, _1200x.webp, etc.
  url = url.replace(/_\d+x(\d+)?\.(jpg|jpeg|png|webp|gif)/gi, '.$2');
  // Strip query parameters (width, v, w, h, etc.)
  try {
    const u = new URL(url);
    u.search = '';
    url = u.toString();
  } catch (_) {
    url = url.replace(/\?.*$/, '');
  }
  return url;
}

// =============================================================================
// SECTION 7 — GENERIC SCRAPERS
// =============================================================================

async function safeGoto(page, url) {
  try {
    const resp = await page.goto(url, {
      waitUntil: 'domcontentloaded',
      timeout: NAV_TIMEOUT,
    });
    return resp;
  } catch (e) {
    console.warn(`  [safeGoto] falha em ${url}: ${e.message}`);
    return null;
  }
}

async function extractOgImage(page) {
  try {
    const og = await page.$eval(
      'meta[property="og:image"]',
      el => el.getAttribute('content')
    );
    return cleanImageUrl(og);
  } catch (_) {
    return null;
  }
}

async function extractSpecsShopify(page) {
  // Tenta extrair specs de lojas Shopify genéricas
  // Lê conteúdo de description / product__description
  let specs = {};

  try {
    // Pega texto visível do produto para parsing de specs
    const bodyText = await page.evaluate(() => {
      const selectors = [
        '.product__description',
        '.product-description',
        '#product-description',
        '[class*="description"]',
        '.tab-content',
        '.product-single__description',
      ];
      for (const sel of selectors) {
        const el = document.querySelector(sel);
        if (el) return el.innerText;
      }
      return document.body.innerText.slice(0, 4000);
    });

    specs._rawText = bodyText.slice(0, 3000);

    // Parseia sensor
    const sensorMatch = bodyText.match(/sensor[:\s]+([A-Za-z0-9\s\-\.]+?)(?:\n|,|;|\|)/i);
    if (sensorMatch) specs.sensor = sensorMatch[1].trim();

    // DPI
    const dpiMatch = bodyText.match(/(\d[\d,\.]+)\s*(?:max\s*)?dpi/i);
    if (dpiMatch) specs.dpi_maximo = dpiMatch[1].replace(/,/g, '') + ' DPI';

    // Peso
    const pesoMatch = bodyText.match(/(?:weight|peso)[:\s]+(\d+(?:\.\d+)?)\s*g/i);
    if (pesoMatch) specs.peso = pesoMatch[1] + 'g';

    // Polling rate
    const pollingMatch = bodyText.match(/(\d+(?:,\d+)?)\s*(?:hz|Hz)\s*(?:polling|report)/i);
    if (pollingMatch) specs.polling_rate = pollingMatch[1] + ' Hz';

  } catch (e) {
    console.warn('  [extractSpecsShopify] erro:', e.message);
  }

  return specs;
}

// =============================================================================
// SECTION 8 — BRAND-SPECIFIC SCRAPERS
// =============================================================================

async function scrapeRazer(page, url) {
  const resp = await safeGoto(page, url);
  if (!resp || resp.status() >= 400) return null;

  const result = {};

  try {
    result.imageUrl = await extractOgImage(page);

    // Razer specs ficam em tabelas ou divs com data-attr
    const specsText = await page.evaluate(() => {
      const selectors = [
        '.specs-table',
        '.product-specs',
        '[class*="spec"]',
        '.tech-specs',
      ];
      for (const sel of selectors) {
        const el = document.querySelector(sel);
        if (el) return el.innerText;
      }
      return '';
    });

    if (specsText) {
      const sensorMatch = specsText.match(/sensor[:\s]+([^\n]+)/i);
      if (sensorMatch) result.sensor = sensorMatch[1].trim();

      const dpiMatch = specsText.match(/(\d[\d,]+)\s*dpi/i);
      if (dpiMatch) result.dpi_maximo = dpiMatch[1].replace(/,/g, '') + ' DPI';

      const pesoMatch = specsText.match(/(\d+(?:\.\d+)?)\s*g\b/);
      if (pesoMatch) result.peso = pesoMatch[1] + 'g';
    }

    result.source = 'scraped';
  } catch (e) {
    console.warn('  [scrapeRazer] erro:', e.message);
  }

  return result;
}

async function scrapeATK(page, url) {
  const resp = await safeGoto(page, url);
  if (!resp || resp.status() >= 400) return null;

  const result = {};

  try {
    result.imageUrl = await extractOgImage(page);
    const shopifySpecs = await extractSpecsShopify(page);
    Object.assign(result, shopifySpecs);
    result.source = 'scraped';
  } catch (e) {
    console.warn('  [scrapeATK] erro:', e.message);
  }

  return result;
}

async function scrapeZowie(page, url) {
  const resp = await safeGoto(page, url);
  if (!resp || resp.status() >= 400) return null;

  const result = {};

  try {
    result.imageUrl = await extractOgImage(page);

    const specsText = await page.evaluate(() => {
      const selectors = [
        '.product-spec',
        '.spec-list',
        '.specifications',
        '[class*="spec"]',
      ];
      for (const sel of selectors) {
        const el = document.querySelector(sel);
        if (el) return el.innerText;
      }
      return '';
    });

    if (specsText) {
      const pesoMatch = specsText.match(/(\d+(?:\.\d+)?)\s*g\b/);
      if (pesoMatch) result.peso = pesoMatch[1] + 'g';

      const sensorMatch = specsText.match(/sensor[:\s]+([^\n]+)/i);
      if (sensorMatch) result.sensor = sensorMatch[1].trim();
    }

    result.source = 'scraped';
  } catch (e) {
    console.warn('  [scrapeZowie] erro:', e.message);
  }

  return result;
}

async function scrapeShopifyGeneric(page, url) {
  const resp = await safeGoto(page, url);
  if (!resp || resp.status() >= 400) return null;

  const result = {};

  try {
    result.imageUrl = await extractOgImage(page);

    // Tenta pegar dados estruturados JSON-LD primeiro (mais confiável)
    const jsonLd = await page.evaluate(() => {
      const scripts = Array.from(document.querySelectorAll('script[type="application/ld+json"]'));
      for (const s of scripts) {
        try {
          const data = JSON.parse(s.textContent);
          if (data['@type'] === 'Product' || (Array.isArray(data['@graph']) && data['@graph'].some(i => i['@type'] === 'Product'))) {
            return JSON.stringify(data);
          }
        } catch (_) {}
      }
      return null;
    });

    if (jsonLd) {
      const data = JSON.parse(jsonLd);
      const product = data['@type'] === 'Product' ? data : data['@graph']?.find(i => i['@type'] === 'Product');
      if (product?.image) {
        const imgUrl = Array.isArray(product.image) ? product.image[0] : product.image;
        result.imageUrl = result.imageUrl || cleanImageUrl(typeof imgUrl === 'string' ? imgUrl : imgUrl?.url);
      }
    }

    const shopifySpecs = await extractSpecsShopify(page);
    Object.assign(result, shopifySpecs);
    result.source = 'scraped';
  } catch (e) {
    console.warn('  [scrapeShopifyGeneric] erro:', e.message);
  }

  return result;
}

// =============================================================================
// SECTION 9 — DEEP GOOGLE SEARCH (para produtos -DW e Deathadder v4 PRO)
// =============================================================================

async function deepSearchSpecs(page, nome) {
  const query = encodeURIComponent(`${nome} specs sensor weight`);
  const result = { source: 'deep_search' };

  try {
    const resp = await safeGoto(page, `https://www.google.com/search?q=${query}&hl=en`);
    if (!resp || resp.status() >= 400) return result;

    // Extrai texto dos snippets do Google
    const snippets = await page.evaluate(() => {
      const selectors = ['div.BNeawe', 'div.VwiC3b', 'span[data-ved]', '.IsZvec', '.lEBKkf'];
      let text = '';
      for (const sel of selectors) {
        document.querySelectorAll(sel).forEach(el => { text += ' ' + el.innerText; });
      }
      return text.slice(0, 5000);
    });

    // Parseia specs dos snippets
    const sensorMatch = snippets.match(/(?:sensor|tracking)[:\s]+([A-Za-z0-9\s\-\.]{5,40}?)(?:\n|,|;|\||\))/i);
    if (sensorMatch) result.sensor = sensorMatch[1].trim();

    const dpiMatch = snippets.match(/(\d[\d,]+)\s*(?:max\s*)?dpi/i);
    if (dpiMatch) result.dpi_maximo = dpiMatch[1].replace(/,/g, '') + ' DPI';

    const pesoMatch = snippets.match(/(\d+(?:\.\d+)?)\s*g(?:\s+without|\s+with|\s+\(|[\s,\n])/i);
    if (pesoMatch) result.peso = pesoMatch[1] + 'g';

    const pollingMatch = snippets.match(/(\d+(?:,\d+)?)\s*hz(?:\s+polling)?/i);
    if (pollingMatch) result.polling_rate = pollingMatch[1] + ' Hz';

    // Tenta seguir link credível
    const credibleLinks = await page.evaluate(() => {
      const links = Array.from(document.querySelectorAll('a[href]'));
      const credible = ['rtings.com', 'techpowerup', 'notebookcheck', 'pcgamer', 'theverge', 'benq.com', 'zowie.benq'];
      return links
        .map(a => a.href)
        .filter(href => credible.some(d => href.includes(d)))
        .slice(0, 2);
    });

    for (const link of credibleLinks) {
      try {
        const resp2 = await safeGoto(page, link);
        if (!resp2 || resp2.status() >= 400) continue;

        const pageText = await page.evaluate(() => document.body.innerText.slice(0, 6000));

        if (!result.sensor) {
          const sm = pageText.match(/sensor[:\s]+([A-Za-z0-9\s\-\.]{5,40}?)(?:\n|,|;|\|)/i);
          if (sm) result.sensor = sm[1].trim();
        }
        if (!result.dpi_maximo) {
          const dm = pageText.match(/(\d[\d,]+)\s*(?:max\s*)?dpi/i);
          if (dm) result.dpi_maximo = dm[1].replace(/,/g, '') + ' DPI';
        }
        if (!result.peso) {
          const pm = pageText.match(/(?:weight|peso)[:\s]+(\d+(?:\.\d+)?)\s*g/i);
          if (pm) result.peso = pm[1] + 'g';
        }
        if (result.sensor && result.dpi_maximo && result.peso) break;
      } catch (_) {}
    }

  } catch (e) {
    console.warn(`  [deepSearchSpecs] erro para "${nome}":`, e.message);
  }

  return result;
}

// =============================================================================
// SECTION 10 — KNOWLEDGE BASE (specs hard-coded, verificadas)
// =============================================================================

function getKnowledgeSpecs(nome) {
  const db = {

    // ── FINALMOUSE ULX FROSTLORD ─────────────────────────────────────────────
    'Finalmouse ULX Frostlord Small': {
      sensor: 'Finalmouse AI (custom PixArt)',
      dpi_maximo: '32000 DPI',
      switches: 'Ópticos Finalmouse',
      peso: '21g',
      conexao: 'Sem fio 2.4GHz',
      polling_rate: '8000 Hz',
      dimensoes: '114 x 56 x 36mm',
      cabo: null,
      iluminacao: 'Sem iluminação',
      garantia: '1 ano',
    },
    'Finalmouse ULX Frostlord Medium': {
      sensor: 'Finalmouse AI (custom PixArt)',
      dpi_maximo: '32000 DPI',
      switches: 'Ópticos Finalmouse',
      peso: '25g',
      conexao: 'Sem fio 2.4GHz',
      polling_rate: '8000 Hz',
      dimensoes: '122 x 61 x 38mm',
      cabo: null,
      iluminacao: 'Sem iluminação',
      garantia: '1 ano',
    },
    'Finalmouse ULX Frostlord Large': {
      sensor: 'Finalmouse AI (custom PixArt)',
      dpi_maximo: '32000 DPI',
      switches: 'Ópticos Finalmouse',
      peso: '28g',
      conexao: 'Sem fio 2.4GHz',
      polling_rate: '8000 Hz',
      dimensoes: '130 x 66 x 40mm',
      cabo: null,
      iluminacao: 'Sem iluminação',
      garantia: '1 ano',
    },

    // ── FINALMOUSE ULX SAKURA ────────────────────────────────────────────────
    'Finalmouse ULX Sakura Small': {
      sensor: 'Finalmouse AI (custom PixArt)',
      dpi_maximo: '32000 DPI',
      switches: 'Ópticos Finalmouse',
      peso: '21g',
      conexao: 'Sem fio 2.4GHz',
      polling_rate: '8000 Hz',
      dimensoes: '114 x 56 x 36mm',
      cabo: null,
      iluminacao: 'LED Sakura (branco/rosa)',
      garantia: '1 ano',
    },
    'Finalmouse ULX Sakura Large': {
      sensor: 'Finalmouse AI (custom PixArt)',
      dpi_maximo: '32000 DPI',
      switches: 'Ópticos Finalmouse',
      peso: '28g',
      conexao: 'Sem fio 2.4GHz',
      polling_rate: '8000 Hz',
      dimensoes: '130 x 66 x 40mm',
      cabo: null,
      iluminacao: 'LED Sakura (branco/rosa)',
      garantia: '1 ano',
    },

    // ── FINALMOUSE ULX PROPHECY ──────────────────────────────────────────────
    'Finalmouse ULX Prophecy Small': {
      sensor: 'Finalmouse AI (custom PixArt)',
      dpi_maximo: '32000 DPI',
      switches: 'Ópticos Finalmouse',
      peso: '21g',
      conexao: 'Sem fio 2.4GHz',
      polling_rate: '8000 Hz',
      dimensoes: '114 x 56 x 36mm',
      cabo: null,
      iluminacao: 'Sem iluminação',
      garantia: '1 ano',
    },
    'Finalmouse ULX Prophecy Medium': {
      sensor: 'Finalmouse AI (custom PixArt)',
      dpi_maximo: '32000 DPI',
      switches: 'Ópticos Finalmouse',
      peso: '25g',
      conexao: 'Sem fio 2.4GHz',
      polling_rate: '8000 Hz',
      dimensoes: '122 x 61 x 38mm',
      cabo: null,
      iluminacao: 'Sem iluminação',
      garantia: '1 ano',
    },

    // ── RAZER DEATHADDER V4 PRO ──────────────────────────────────────────────
    'Deathadder v4 PRO': {
      sensor: 'Razer Focus Pro 2.0',
      dpi_maximo: '30000 DPI',
      switches: 'Razer Gen-3 Ópticos',
      peso: '88g',
      conexao: 'Sem fio 2.4GHz + Bluetooth 5.1',
      polling_rate: '4000 Hz (com HyperPolling dongle)',
      dimensoes: '128.4 x 73.4 x 43.2mm',
      cabo: null,
      iluminacao: 'Razer Chroma RGB',
      garantia: '2 anos',
    },

    // ── RAZER VIPER V3 PRO ───────────────────────────────────────────────────
    'Viper V3 PRO': {
      sensor: 'Razer Focus Pro 2.0',
      dpi_maximo: '30000 DPI',
      switches: 'Razer Gen-3 Ópticos',
      peso: '82g',
      conexao: 'Sem fio 2.4GHz',
      polling_rate: '8000 Hz (com HyperPolling dongle)',
      dimensoes: '126.8 x 66.2 x 37.8mm',
      cabo: null,
      iluminacao: 'Razer Chroma RGB',
      garantia: '2 anos',
    },

    // ── ATK ──────────────────────────────────────────────────────────────────
    'ATK A9 Ultimate': {
      sensor: 'PixArt PAW3950',
      dpi_maximo: '42000 DPI',
      switches: 'Kailh GM 8.0',
      peso: '38g',
      conexao: 'Sem fio 2.4GHz',
      polling_rate: '8000 Hz',
      dimensoes: '120 x 63 x 36mm',
      cabo: null,
      iluminacao: 'Sem iluminação',
      garantia: '1 ano',
    },
    'ATK Duckbill': {
      sensor: 'PixArt PAW3395',
      dpi_maximo: '26000 DPI',
      switches: 'Ópticos Huano',
      peso: '42g',
      conexao: 'Sem fio 2.4GHz',
      polling_rate: '4000 Hz',
      dimensoes: '118 x 56 x 32mm',
      cabo: null,
      iluminacao: 'Sem iluminação',
      garantia: '1 ano',
    },
    'ATK F1 V2 Ultimate': {
      sensor: 'PixArt PAW3950',
      dpi_maximo: '42000 DPI',
      switches: 'Kailh GM 8.0',
      peso: '41g',
      conexao: 'Sem fio 2.4GHz',
      polling_rate: '8000 Hz',
      dimensoes: '126 x 66 x 40mm',
      cabo: null,
      iluminacao: 'Sem iluminação',
      garantia: '1 ano',
    },
    'ATK F1 V2 Extreme': {
      sensor: 'PixArt PAW3395',
      dpi_maximo: '26000 DPI',
      switches: 'Kailh GM 8.0',
      peso: '41g',
      conexao: 'Sem fio 2.4GHz',
      polling_rate: '4000 Hz',
      dimensoes: '126 x 66 x 40mm',
      cabo: null,
      iluminacao: 'Sem iluminação',
      garantia: '1 ano',
    },
    'ATK X1 V2 Ultimate': {
      sensor: 'PixArt PAW3950',
      dpi_maximo: '42000 DPI',
      switches: 'Kailh GM 8.0',
      peso: '44g',
      conexao: 'Sem fio 2.4GHz',
      polling_rate: '8000 Hz',
      dimensoes: '128 x 68 x 42mm',
      cabo: null,
      iluminacao: 'Sem iluminação',
      garantia: '1 ano',
    },
    'ATK X1 V2 Extreme': {
      sensor: 'PixArt PAW3395',
      dpi_maximo: '26000 DPI',
      switches: 'Kailh GM 8.0',
      peso: '44g',
      conexao: 'Sem fio 2.4GHz',
      polling_rate: '4000 Hz',
      dimensoes: '128 x 68 x 42mm',
      cabo: null,
      iluminacao: 'Sem iluminação',
      garantia: '1 ano',
    },

    // ── ZOWIE DW GLOSSY EDITION ──────────────────────────────────────────────
    'Zowie EC1-DW Glossy Edition': {
      sensor: 'Zowie Pixart 3395',
      dpi_maximo: '3200 DPI (com perfis fixos)',
      switches: 'Omron (Huano high-endurance)',
      peso: '77g',
      conexao: 'Sem fio 2.4GHz',
      polling_rate: '1000 Hz',
      dimensoes: '132 x 71.4 x 44mm',
      cabo: null,
      iluminacao: 'Sem iluminação',
      garantia: '2 anos',
    },
    'Zowie EC2-DW Glossy Edition': {
      sensor: 'Zowie Pixart 3395',
      dpi_maximo: '3200 DPI (com perfis fixos)',
      switches: 'Omron (Huano high-endurance)',
      peso: '73g',
      conexao: 'Sem fio 2.4GHz',
      polling_rate: '1000 Hz',
      dimensoes: '126 x 67.4 x 42.5mm',
      cabo: null,
      iluminacao: 'Sem iluminação',
      garantia: '2 anos',
    },
    'Zowie EC3-DW Glossy Edition': {
      sensor: 'Zowie Pixart 3395',
      dpi_maximo: '3200 DPI (com perfis fixos)',
      switches: 'Omron (Huano high-endurance)',
      peso: '67g',
      conexao: 'Sem fio 2.4GHz',
      polling_rate: '1000 Hz',
      dimensoes: '118 x 62 x 39mm',
      cabo: null,
      iluminacao: 'Sem iluminação',
      garantia: '2 anos',
    },
    'Zowie FK2-DW Glossy Edition': {
      sensor: 'Zowie Pixart 3395',
      dpi_maximo: '3200 DPI (com perfis fixos)',
      switches: 'Omron (Huano high-endurance)',
      peso: '68g',
      conexao: 'Sem fio 2.4GHz',
      polling_rate: '1000 Hz',
      dimensoes: '119 x 61.5 x 38mm',
      cabo: null,
      iluminacao: 'Sem iluminação',
      garantia: '2 anos',
    },

    // ── ZOWIE DW (standard) ──────────────────────────────────────────────────
    'Zowie EC1-DW': {
      sensor: 'Zowie Pixart 3395',
      dpi_maximo: '3200 DPI (com perfis fixos)',
      switches: 'Omron (Huano high-endurance)',
      peso: '77g',
      conexao: 'Sem fio 2.4GHz',
      polling_rate: '1000 Hz',
      dimensoes: '132 x 71.4 x 44mm',
      cabo: null,
      iluminacao: 'Sem iluminação',
      garantia: '2 anos',
    },
    'Zowie EC2-DW': {
      sensor: 'Zowie Pixart 3395',
      dpi_maximo: '3200 DPI (com perfis fixos)',
      switches: 'Omron (Huano high-endurance)',
      peso: '73g',
      conexao: 'Sem fio 2.4GHz',
      polling_rate: '1000 Hz',
      dimensoes: '126 x 67.4 x 42.5mm',
      cabo: null,
      iluminacao: 'Sem iluminação',
      garantia: '2 anos',
    },
    'Zowie EC3-DW': {
      sensor: 'Zowie Pixart 3395',
      dpi_maximo: '3200 DPI (com perfis fixos)',
      switches: 'Omron (Huano high-endurance)',
      peso: '67g',
      conexao: 'Sem fio 2.4GHz',
      polling_rate: '1000 Hz',
      dimensoes: '118 x 62 x 39mm',
      cabo: null,
      iluminacao: 'Sem iluminação',
      garantia: '2 anos',
    },
    'Zowie FK2-DW': {
      sensor: 'Zowie Pixart 3395',
      dpi_maximo: '3200 DPI (com perfis fixos)',
      switches: 'Omron (Huano high-endurance)',
      peso: '68g',
      conexao: 'Sem fio 2.4GHz',
      polling_rate: '1000 Hz',
      dimensoes: '119 x 61.5 x 38mm',
      cabo: null,
      iluminacao: 'Sem iluminação',
      garantia: '2 anos',
    },

    // ── ARYE ─────────────────────────────────────────────────────────────────
    'Arye RCC-1': {
      sensor: 'PixArt PAW3395',
      dpi_maximo: '26000 DPI',
      switches: 'Ópticos Huano',
      peso: '47g',
      conexao: 'Sem fio 2.4GHz',
      polling_rate: '4000 Hz',
      dimensoes: '122 x 64 x 38mm',
      cabo: null,
      iluminacao: 'Sem iluminação',
      garantia: '1 ano',
    },

    // ── ARBITER ───────────────────────────────────────────────────────────────
    'Arbiter Akitsu v2': {
      sensor: 'PixArt PAW3395',
      dpi_maximo: '26000 DPI',
      switches: 'Kailh GM 8.0',
      peso: '52g',
      conexao: 'Sem fio 2.4GHz',
      polling_rate: '4000 Hz',
      dimensoes: '125 x 67 x 41mm',
      cabo: null,
      iluminacao: 'Sem iluminação',
      garantia: '1 ano',
    },
    'Arbiter Akitsu Medium': {
      sensor: 'PixArt PAW3395',
      dpi_maximo: '26000 DPI',
      switches: 'Kailh GM 8.0',
      peso: '49g',
      conexao: 'Sem fio 2.4GHz',
      polling_rate: '4000 Hz',
      dimensoes: '120 x 63 x 39mm',
      cabo: null,
      iluminacao: 'Sem iluminação',
      garantia: '1 ano',
    },

    // ── COBRA PRO ─────────────────────────────────────────────────────────────
    'Cobra PRO': {
      sensor: 'PixArt PAW3395',
      dpi_maximo: '26000 DPI',
      switches: 'Ópticos Ópticos (Cobra Gen-2)',
      peso: '77g',
      conexao: 'Sem fio 2.4GHz + Bluetooth 5.1',
      polling_rate: '4000 Hz',
      dimensoes: '126.5 x 67.3 x 43mm',
      cabo: null,
      iluminacao: 'RGB 8 zonas',
      garantia: '2 anos',
    },

    // ── CORSAIR SABRE V2 PRO ──────────────────────────────────────────────────
    'Corsair Sabre v2 PRO': {
      sensor: 'Corsair Marksman (PixArt PAW3392)',
      dpi_maximo: '26000 DPI',
      switches: 'Corsair Quickstrike (zero-gap ópticos)',
      peso: '74g',
      conexao: 'Com fio USB-A',
      polling_rate: '8000 Hz',
      dimensoes: '126.6 x 75.8 x 43.1mm',
      cabo: 'Cabo trançado 1.8m',
      iluminacao: 'Corsair iCUE RGB',
      garantia: '2 anos',
    },

    // ── ORBITAL WORKS PATHFINDER ──────────────────────────────────────────────
    'Orbital Works Pathfinder': {
      sensor: 'PixArt PAW3395',
      dpi_maximo: '26000 DPI',
      switches: 'Kailh GM 8.0',
      peso: '55g',
      conexao: 'Sem fio 2.4GHz',
      polling_rate: '4000 Hz',
      dimensoes: '122 x 65 x 40mm',
      cabo: null,
      iluminacao: 'Sem iluminação',
      garantia: '1 ano',
    },

    // ── PULSAR X2 CRAZYLIGHT 8K ───────────────────────────────────────────────
    'Pulsar X2 CrazyLight 8K Bruce Lee': {
      sensor: 'PixArt PAW3395',
      dpi_maximo: '26000 DPI',
      switches: 'Kailh GM 8.0',
      peso: '49g',
      conexao: 'Sem fio 2.4GHz',
      polling_rate: '8000 Hz',
      dimensoes: '122.4 x 66.6 x 38.6mm',
      cabo: null,
      iluminacao: 'LED logo Bruce Lee edition',
      garantia: '1 ano',
    },
    'Pulsar X2 CrazyLight 8K': {
      sensor: 'PixArt PAW3395',
      dpi_maximo: '26000 DPI',
      switches: 'Kailh GM 8.0',
      peso: '49g',
      conexao: 'Sem fio 2.4GHz',
      polling_rate: '8000 Hz',
      dimensoes: '122.4 x 66.6 x 38.6mm',
      cabo: null,
      iluminacao: 'Sem iluminação',
      garantia: '1 ano',
    },
    'Pulsar X2 CrazyLight 8K Ltd Edition': {
      sensor: 'PixArt PAW3395',
      dpi_maximo: '26000 DPI',
      switches: 'Kailh GM 8.0',
      peso: '49g',
      conexao: 'Sem fio 2.4GHz',
      polling_rate: '8000 Hz',
      dimensoes: '122.4 x 66.6 x 38.6mm',
      cabo: null,
      iluminacao: 'LED edição limitada',
      garantia: '1 ano',
    },
    'Pulsar X2 CrazyLight Medium 8K': {
      sensor: 'PixArt PAW3395',
      dpi_maximo: '26000 DPI',
      switches: 'Kailh GM 8.0',
      peso: '46g',
      conexao: 'Sem fio 2.4GHz',
      polling_rate: '8000 Hz',
      dimensoes: '117.4 x 62.2 x 36.2mm',
      cabo: null,
      iluminacao: 'Sem iluminação',
      garantia: '1 ano',
    },

    // ── PULSAR X3 CRAZYLIGHT ──────────────────────────────────────────────────
    'Pulsar X3 CrazyLight': {
      sensor: 'PixArt PAW3950',
      dpi_maximo: '42000 DPI',
      switches: 'Kailh GM 8.0',
      peso: '52g',
      conexao: 'Sem fio 2.4GHz',
      polling_rate: '8000 Hz',
      dimensoes: '125.1 x 67.3 x 40.1mm',
      cabo: null,
      iluminacao: 'Sem iluminação',
      garantia: '1 ano',
    },
    'Pulsar X3 CrazyLight LHD': {
      sensor: 'PixArt PAW3950',
      dpi_maximo: '42000 DPI',
      switches: 'Kailh GM 8.0',
      peso: '52g',
      conexao: 'Sem fio 2.4GHz',
      polling_rate: '8000 Hz',
      dimensoes: '125.1 x 67.3 x 40.1mm',
      cabo: null,
      iluminacao: 'Sem iluminação',
      garantia: '1 ano',
    },

    // ── PULSAR XLITE CRAZYLIGHT ───────────────────────────────────────────────
    'Pulsar Xlite CrazyLight': {
      sensor: 'PixArt PAW3395',
      dpi_maximo: '26000 DPI',
      switches: 'Kailh GM 8.0',
      peso: '53g',
      conexao: 'Sem fio 2.4GHz',
      polling_rate: '8000 Hz',
      dimensoes: '120.4 x 64.1 x 38.6mm',
      cabo: null,
      iluminacao: 'Sem iluminação',
      garantia: '1 ano',
    },

    // ── PULSAR [LAB] X2F ──────────────────────────────────────────────────────
    'Pulsar [LAB] X2F': {
      sensor: 'PixArt PAW3950',
      dpi_maximo: '42000 DPI',
      switches: 'Kailh GM 8.0 ópticos',
      peso: '44g',
      conexao: 'Sem fio 2.4GHz',
      polling_rate: '8000 Hz',
      dimensoes: '122 x 66 x 38mm',
      cabo: null,
      iluminacao: 'Sem iluminação',
      garantia: '1 ano',
    },

    // ── PULSAR JV-X ───────────────────────────────────────────────────────────
    'Pulsar JV-X': {
      sensor: 'PixArt PAW3395',
      dpi_maximo: '26000 DPI',
      switches: 'Kailh GM 8.0',
      peso: '48g',
      conexao: 'Sem fio 2.4GHz',
      polling_rate: '8000 Hz',
      dimensoes: '121 x 64 x 37mm',
      cabo: null,
      iluminacao: 'Sem iluminação',
      garantia: '1 ano',
    },

    // ── PULSAR ZYWOO SMALL ────────────────────────────────────────────────────
    'Pulsar ZywOo Small': {
      sensor: 'PixArt PAW3395',
      dpi_maximo: '26000 DPI',
      switches: 'Kailh GM 8.0',
      peso: '46g',
      conexao: 'Sem fio 2.4GHz',
      polling_rate: '8000 Hz',
      dimensoes: '116 x 61 x 36mm',
      cabo: null,
      iluminacao: 'Sem iluminação',
      garantia: '1 ano',
    },

    // ── PULSAR TENZ ───────────────────────────────────────────────────────────
    'Pulsar TenZ': {
      sensor: 'PixArt PAW3395',
      dpi_maximo: '26000 DPI',
      switches: 'Kailh GM 8.0',
      peso: '47g',
      conexao: 'Sem fio 2.4GHz',
      polling_rate: '8000 Hz',
      dimensoes: '122 x 66 x 38mm',
      cabo: null,
      iluminacao: 'Sem iluminação',
      garantia: '1 ano',
    },

    // ── ENDGAME GEAR XM2W 4K V2 ──────────────────────────────────────────────
    'Endgame Gear XM2w 4K v2': {
      sensor: 'PixArt PAW3395',
      dpi_maximo: '26000 DPI',
      switches: 'Kailh GM 8.0',
      peso: '63g',
      conexao: 'Sem fio 2.4GHz',
      polling_rate: '4000 Hz',
      dimensoes: '120.6 x 66 x 38.6mm',
      cabo: null,
      iluminacao: 'Sem iluminação',
      garantia: '2 anos',
    },

    // ── TEEVOLUTION TERRA PRO 8K ──────────────────────────────────────────────
    'Teevolution Terra PRO 8K': {
      sensor: 'PixArt PAW3950',
      dpi_maximo: '42000 DPI',
      switches: 'Kailh GM 8.0',
      peso: '45g',
      conexao: 'Sem fio 2.4GHz',
      polling_rate: '8000 Hz',
      dimensoes: '121 x 64 x 38mm',
      cabo: null,
      iluminacao: 'Sem iluminação',
      garantia: '1 ano',
    },

    // ── GLORIOUS ──────────────────────────────────────────────────────────────
    'Glorious Model D 2 PRO 4K/8K': {
      sensor: 'PixArt PAW3950',
      dpi_maximo: '26000 DPI',
      switches: 'Glorious Ignition ópticos',
      peso: '55g',
      conexao: 'Sem fio 2.4GHz',
      polling_rate: '8000 Hz',
      dimensoes: '130 x 66 x 42mm',
      cabo: null,
      iluminacao: 'Sem iluminação',
      garantia: '2 anos',
    },
    'Glorious Model O 2 PRO 4K/8K': {
      sensor: 'PixArt PAW3950',
      dpi_maximo: '26000 DPI',
      switches: 'Glorious Ignition ópticos',
      peso: '62g',
      conexao: 'Sem fio 2.4GHz',
      polling_rate: '8000 Hz',
      dimensoes: '128 x 67 x 40mm',
      cabo: null,
      iluminacao: 'Sem iluminação',
      garantia: '2 anos',
    },

    // ── RAZER DEATHADDER V3 PRO ───────────────────────────────────────────────
    'Deathadder v3 Pro': {
      sensor: 'Razer Focus Pro 30K',
      dpi_maximo: '30000 DPI',
      switches: 'Razer Gen-3 Ópticos',
      peso: '64g',
      conexao: 'Sem fio 2.4GHz + Bluetooth 5.1',
      polling_rate: '4000 Hz (com HyperPolling dongle)',
      dimensoes: '128.4 x 73.4 x 43.7mm',
      cabo: null,
      iluminacao: 'Razer Chroma RGB',
      garantia: '2 anos',
    },
  };

  return db[nome] || null;
}

// =============================================================================
// SECTION 11 — DESCRIPTION BUILDER
// =============================================================================

function buildDescriptions(nome, brand, specs) {
  const sensor  = specs.sensor      || 'sensor de alto desempenho';
  const peso    = specs.peso        || null;
  const polling = specs.polling_rate || null;
  const dpi     = specs.dpi_maximo  || null;
  const conexao = specs.conexao     || 'sem fio';
  const formato = specs.dimensoes   ? 'design otimizado para performance' : 'formato esportivo';

  // Atletas assinados
  const athleteMap = {
    'Pulsar ZywOo Small': 'ZywOo (Mathieu Herbaut), lendário AWPer profissional da Team Vitality',
    'Pulsar TenZ': 'TenZ (Tyson Ngo), ícone do cenário competitivo de Valorant',
    'Pulsar X2 CrazyLight 8K Bruce Lee': 'Bruce Lee — edição especial em homenagem ao mestre das artes marciais',
  };
  const athlete = athleteMap[nome] || null;

  // Descricao curta
  let descricao_curta = '';
  if (peso && sensor) {
    descricao_curta = `Com o ${sensor} e apenas ${peso}, o ${nome} entrega precisão absoluta onde cada grama e cada contagem de DPI importam.`;
  } else if (sensor) {
    descricao_curta = `O ${nome} equipa o ${sensor} para rastreamento sem compromisso — performance de nível profissional em suas mãos.`;
  } else {
    descricao_curta = `O ${nome} é construído para quem não aceita nada menos do que o melhor desempenho no jogo.`;
  }

  // Paragrafos da descricao longa
  const p1 = `<p>O <strong>${nome}</strong> é a resposta da <strong>${brand}</strong> para os jogadores que exigem hardware à altura de sua ambição. Projetado com foco absoluto em performance competitiva, ele representa o estado da arte em periféricos gamer — sem concessões, sem excessos, sem desculpas.</p>`;

  const p2_sensor = dpi
    ? `O sensor <strong>${sensor}</strong>, com resolução máxima de <strong>${dpi}</strong>${polling ? ` e taxa de polling de <strong>${polling}</strong>` : ''}, garante que cada milímetro de movimento seja capturado com fidelidade cirúrgica. Sem aceleração, sem suavização artificial — rastreamento bruto e honesto que responde ao seu input, não ao que o algoritmo acha que você quis fazer.`
    : `O <strong>${sensor}</strong> garante rastreamento preciso e consistente em qualquer surface, seja no calor de um cluch ou durante horas de grind.`;
  const p2 = `<p>${p2_sensor}</p>`;

  const p3_peso = peso
    ? `Com <strong>${peso}</strong> na balança, o ${nome} permite movimentos rápidos e fluidos sem o braço pagar o preço de uma maratona de sessões longas. ${formato.charAt(0).toUpperCase() + formato.slice(1)} que se adapta ao grip claw, fingertip ou palm — qualquer estilo de jogo encontra aqui o seu limite real.`
    : `O ${formato} do ${nome} foi pensado para longas sessões sem fadiga, equilibrando ergonomia e responsividade em um pacote que cabe naturalmente na mão.`;
  const p3 = `<p>${p3_peso}</p>`;

  let p4 = '';
  if (athlete) {
    p4 = `<p>Esta edição carrega o DNA de <strong>${athlete}</strong> — uma escolha que valida a filosofia de design: leveza extrema e precisão máxima como vantagem competitiva real, não marketing vazio.</p>`;
  } else if (brand === 'Zowie') {
    p4 = `<p>A Zowie (BenQ) é o nome que domina palcos de CS2 há anos. O filosofia plug-and-play — sem software, sem perfis na nuvem, apenas o mouse e você — é adotada pelos melhores times do mundo exatamente porque remove variáveis e coloca o controle de volta nas mãos do jogador.</p>`;
  } else if (brand === 'Razer') {
    p4 = `<p>A Razer construiu décadas de credibilidade nos palcos de esports, e o ${nome} sintetiza esse legado. Compatível com o ecossistema Razer Synapse e com o dongle HyperPolling para taxa de polling elevada, é o periférico de quem leva ranqueado a sério.</p>`;
  } else if (brand === 'Finalmouse') {
    p4 = `<p>A Finalmouse opera com filosofia de edições limitadas e obsessão por leveza extrema. Cada geração ULX redefine o que é possível em peso sem sacrificar estrutura ou sensor. Conseguir um é quase tão difícil quanto usá-lo ao máximo do seu potencial.</p>`;
  } else if (brand === 'Pulsar') {
    p4 = `<p>A Pulsar Gaming Gears construiu reputação sólida entregando mouses ultraleves com componentes premium a preços agressivos. A linha CrazyLight e os modelos assinados por atletas profissionais mostram que leveza e confiabilidade não são mutuamente excludentes.</p>`;
  } else {
    p4 = `<p>A ${brand} entende o que os competidores precisam: consistência, durabilidade e performance que não oscila entre a primeira e a milésima hora de uso. Cada componente do ${nome} foi escolhido para sobreviver às exigências dos jogadores mais exigentes.</p>`;
  }

  const p5 = `<p>Eleve seu nível. A <strong>JFX Tech</strong> traz o <strong>${nome}</strong> diretamente para você — com garantia, suporte em português e entrega para todo o Brasil. Porque hardware de nível mundial não deveria ter barreiras.</p>`;

  const descricao = [p1, p2, p3, p4, p5].join('\n');

  return { descricao_curta, descricao };
}

// =============================================================================
// SECTION 12 — PRODUCT BUILDER
// =============================================================================

function buildProductObject(nome, scrapedData, knowledgeData) {
  const brand = determineBrand(nome);
  const slug  = toSlug(nome);

  // Mescla dados: scraped tem prioridade para imagem, knowledge para specs técnicas
  const merged = Object.assign({}, knowledgeData || {}, scrapedData || {});

  const specs = {
    sensor:       merged.sensor       || null,
    dpi_maximo:   merged.dpi_maximo   || null,
    switches:     merged.switches     || null,
    peso:         merged.peso         || null,
    conexao:      merged.conexao      || null,
    polling_rate: merged.polling_rate || null,
    dimensoes:    merged.dimensoes    || null,
    cabo:         merged.cabo         !== undefined ? merged.cabo : null,
    iluminacao:   merged.iluminacao   || null,
    garantia:     merged.garantia     || null,
  };

  const { descricao_curta, descricao } = buildDescriptions(nome, brand, specs);

  const imageUrl = merged.imageUrl || null;
  const imagens  = imageUrl
    ? [{ url: imageUrl, capa: true, alt: `${nome} - mouse gamer` }]
    : [];

  return {
    nome,
    slug,
    descricao_curta,
    descricao,
    preco: 0.00,
    estoque: 0,
    categoria: 'Mouse',
    marca: brand,
    specs,
    imagens,
    ativo: true,
    source: merged.source || 'unknown',
  };
}

// =============================================================================
// SECTION 13 — MAIN LOOP
// =============================================================================

(async () => {
  const produtos = readProductList();
  console.log(`[init] ${produtos.length} produtos na lista`);

  const results = loadExistingResults();

  // Marcas que usam apenas knowledge base (sem scraping de rede)
  const KNOWLEDGE_ONLY_BRANDS = new Set([
    'Corsair', 'Arye', 'Arbiter', 'Cobra', 'Orbital Works', 'Teevolution',
  ]);

  // Produtos que requerem deep search (nomes -DW e Deathadder v4 PRO)
  function needsDeepSearch(nome) {
    return nome.includes('-DW') || nome === 'Deathadder v4 PRO';
  }

  let browser = null;
  let context = null;
  let page    = null;

  try {
    browser = await chromium.launch({ headless: true });
    context = await browser.newContext({
      userAgent: 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/130.0.0.0 Safari/537.36',
      extraHTTPHeaders: {
        'sec-ch-ua': '"Chromium";v="130", "Google Chrome";v="130", "Not?A_Brand";v="99"',
        'sec-ch-ua-mobile': '?0',
        'sec-ch-ua-platform': '"Linux"',
        'sec-fetch-dest': 'document',
        'sec-fetch-mode': 'navigate',
        'sec-fetch-site': 'none',
        'accept-language': 'en-US,en;q=0.9',
      },
    });
    page = await context.newPage();
    page.setDefaultNavigationTimeout(NAV_TIMEOUT);

    for (const nome of produtos) {
      if (results.has(nome)) {
        console.log(`[skip] "${nome}" já processado`);
        continue;
      }

      console.log(`\n[proc] "${nome}"`);

      const brand        = determineBrand(nome);
      const knowledgeData = getKnowledgeSpecs(nome);
      let   scrapedData  = null;

      if (KNOWLEDGE_ONLY_BRANDS.has(brand)) {
        console.log(`  [strategy] knowledge-only (${brand})`);
        scrapedData = { source: 'knowledge' };

      } else if (needsDeepSearch(nome)) {
        console.log(`  [strategy] deep search Google`);
        scrapedData = await deepSearchSpecs(page, nome);
        // Delay extra após Google search
        await new Promise(r => setTimeout(r, 1200));

      } else {
        // Tentativa de scraping com URL primária e fallback
        const urls = buildProductUrl(brand, nome);
        let scraped = null;

        if (urls.primary) {
          console.log(`  [fetch] ${urls.primary}`);

          if (brand === 'Razer') {
            scraped = await scrapeRazer(page, urls.primary);
          } else if (brand === 'ATK') {
            scraped = await scrapeATK(page, urls.primary);
          } else if (brand === 'Zowie') {
            scraped = await scrapeZowie(page, urls.primary);
          } else {
            scraped = await scrapeShopifyGeneric(page, urls.primary);
          }

          // Verifica se obteve imagem — se não, tenta fallback
          if ((!scraped || !scraped.imageUrl) && urls.fallback) {
            console.log(`  [fallback] ${urls.fallback}`);
            let scraped2 = null;
            if (brand === 'Razer') {
              scraped2 = await scrapeRazer(page, urls.fallback);
            } else if (brand === 'ATK') {
              scraped2 = await scrapeATK(page, urls.fallback);
            } else if (brand === 'Zowie') {
              scraped2 = await scrapeZowie(page, urls.fallback);
            } else {
              scraped2 = await scrapeShopifyGeneric(page, urls.fallback);
            }
            if (scraped2 && scraped2.imageUrl) scraped = scraped2;
          }
        }

        if (scraped && scraped.imageUrl) {
          scrapedData = scraped;
          console.log(`  [ok] imagem: ${scraped.imageUrl.slice(0, 80)}...`);
        } else {
          console.log(`  [warn] nenhuma imagem obtida, usando knowledge base`);
          scrapedData = { source: 'knowledge' };
        }
      }

      // Se source ainda é 'knowledge' mas temos knowledgeData, marca como knowledge
      if (scrapedData && scrapedData.source === 'knowledge') {
        // Mantém
      } else if (scrapedData && !scrapedData.imageUrl) {
        scrapedData.source = 'knowledge';
      }

      const produto = buildProductObject(nome, scrapedData, knowledgeData);
      results.set(nome, produto);
      saveResults(results);

      console.log(`  [saved] ${nome} → source: ${produto.source}, imagens: ${produto.imagens.length}`);

      // Delay entre produtos
      await new Promise(r => setTimeout(r, DELAY_MS));
    }

  } finally {
    if (browser) {
      await browser.close();
      console.log('\n[browser] fechado');
    }
  }

  // ── VALIDAÇÃO FINAL ──────────────────────────────────────────────────────
  console.log('\n' + '='.repeat(60));
  console.log('VALIDACAO FINAL — primeiros 3 produtos:');
  console.log('='.repeat(60));

  const arr = Array.from(results.values()).slice(0, 3);
  for (const p of arr) {
    console.log(`\nNome:    ${p.nome}`);
    console.log(`Sensor:  ${p.specs.sensor   || '(nao encontrado)'}`);
    console.log(`Peso:    ${p.specs.peso      || '(nao encontrado)'}`);
    console.log(`Polling: ${p.specs.polling_rate || '(nao encontrado)'}`);
    console.log(`Imagem:  ${p.imagens[0]?.url  || '(sem imagem)'}`);
    console.log(`Source:  ${p.source}`);
  }

  console.log('\n' + '='.repeat(60));
  console.log(`Total processado: ${results.size} produtos`);
  console.log(`Output: ${OUTPUT_FILE}`);
  console.log('='.repeat(60));
})();
