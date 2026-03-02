#!/usr/bin/env node
/**
 * enricher_v2.js — Enriquece produtos_prontos.json com imagens via Serper API
 *
 * Uso:
 *   SERPER_API_KEY=sua_chave node enricher_v2.js
 *
 * Output: produtos_final_v2.json
 */

const fs = require('fs');
const path = require('path');

const SERPER_API_KEY = process.env.SERPER_API_KEY || '';
const INPUT_FILE    = path.join(__dirname, 'produtos_prontos.json');
const OUTPUT_FILE   = path.join(__dirname, 'produtos_final_v2.json');
const PLACEHOLDER   = 'storage/products/placeholder-jfx.jpg';

// --- helpers ---

function fixProtocol(url) {
    if (typeof url === 'string' && url.startsWith('//')) {
        return 'https:' + url;
    }
    return url;
}

function isValidImageUrl(url) {
    if (!url || typeof url !== 'string') return false;
    if (!url.startsWith('https://')) return false;
    if (url.endsWith('.svg')) return false;
    return true;
}

function preferredFirst(urls) {
    const preferred = urls.filter(u => /\.(webp|jpg|jpeg|png)/i.test(u));
    const rest      = urls.filter(u => !/\.(webp|jpg|jpeg|png)/i.test(u));
    return [...preferred, ...rest];
}

async function searchImages(query) {
    const response = await fetch('https://google.serper.dev/images', {
        method: 'POST',
        headers: {
            'X-API-KEY': SERPER_API_KEY,
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({ q: query, gl: 'br', hl: 'pt', num: 5 }),
    });

    if (!response.ok) {
        const text = await response.text();
        throw new Error(`Serper API error ${response.status}: ${text}`);
    }

    const data = await response.json();
    const images = (data.images || [])
        .map(img => img.imageUrl)
        .filter(isValidImageUrl);

    return preferredFirst(images).slice(0, 2);
}

// --- main ---

async function main() {
    if (!fs.existsSync(INPUT_FILE)) {
        console.error(`Arquivo de entrada não encontrado: ${INPUT_FILE}`);
        process.exit(1);
    }

    const produtos = JSON.parse(fs.readFileSync(INPUT_FILE, 'utf8'));
    console.log(`\nTotal de produtos: ${produtos.length}`);

    if (!SERPER_API_KEY) {
        console.warn('\n[AVISO] SERPER_API_KEY não definida. Produtos sem imagem receberão placeholder.\n');
    }

    let comImagem = 0;
    let semImagem = 0;
    let usouPlaceholder = 0;
    let buscouSerper   = 0;

    for (let i = 0; i < produtos.length; i++) {
        const p = produtos[i];
        const label = `[${i + 1}/${produtos.length}] ${p.nome}`;

        // 1. Corrigir protocolos relativos nas imagens já existentes
        if (Array.isArray(p.imagens)) {
            p.imagens = p.imagens.map(img => ({
                ...img,
                url: fixProtocol(img.url),
            }));
        } else {
            p.imagens = [];
        }

        // 2. Se o produto ainda não tem imagens válidas, buscar via Serper
        const temImagem = p.imagens.some(img => img.url && !img.url.startsWith('storage/'));

        if (!temImagem) {
            if (SERPER_API_KEY) {
                const query = `${p.marca} ${p.nome} mouse gaming`;
                try {
                    const urls = await searchImages(query);
                    if (urls.length > 0) {
                        p.imagens = urls.map((url, idx) => ({
                            url,
                            capa: idx === 0,
                            alt: p.nome,
                        }));
                        console.log(`  ${label} — ${urls.length} imagem(ns) encontrada(s)`);
                        buscouSerper++;
                        comImagem++;
                    } else {
                        p.imagens = [{ url: PLACEHOLDER, capa: true, alt: p.nome }];
                        console.log(`  ${label} — sem resultado Serper → placeholder`);
                        usouPlaceholder++;
                        semImagem++;
                    }
                } catch (err) {
                    console.error(`  ${label} — ERRO Serper: ${err.message} → placeholder`);
                    p.imagens = [{ url: PLACEHOLDER, capa: true, alt: p.nome }];
                    usouPlaceholder++;
                    semImagem++;
                }

                // Pequeno delay para não sobrecarregar a API
                await new Promise(r => setTimeout(r, 300));
            } else {
                p.imagens = [{ url: PLACEHOLDER, capa: true, alt: p.nome }];
                usouPlaceholder++;
                semImagem++;
            }
        } else {
            console.log(`  ${label} — já tinha ${p.imagens.length} imagem(ns)`);
            comImagem++;
        }
    }

    fs.writeFileSync(OUTPUT_FILE, JSON.stringify(produtos, null, 2), 'utf8');

    console.log('\n--- Resumo ---');
    console.log(`Total:            ${produtos.length}`);
    console.log(`Com imagem real:  ${comImagem}`);
    console.log(`Sem imagem:       ${semImagem}`);
    console.log(`Via Serper:       ${buscouSerper}`);
    console.log(`Placeholder:      ${usouPlaceholder}`);
    console.log(`\nOutput salvo em: ${OUTPUT_FILE}`);
}

main().catch(err => {
    console.error('Erro fatal:', err);
    process.exit(1);
});
