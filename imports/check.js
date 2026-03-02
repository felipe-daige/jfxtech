const fs = require('fs');
const d = JSON.parse(fs.readFileSync('produtos_prontos.json','utf8'));
console.log('Total:', d.length);
const sources = {};
d.forEach(p => { sources[p.source] = (sources[p.source]||0)+1; });
console.log('Sources:', JSON.stringify(sources, null, 2));

const semImg = d.filter(p => !p.imagens || p.imagens.length === 0);
const comImg = d.filter(p => p.imagens && p.imagens.length > 0);
console.log('Sem imagens:', semImg.length);
console.log('Com imagens:', comImg.length);

// Verificar slugs
const slugBad = d.filter(p => !p.slug || !/^[a-z0-9-]+$/.test(p.slug));
console.log('Slugs inválidos:', slugBad.length, slugBad.map(p => p.slug));

// Mostrar produtos com imagens
console.log('\nProdutos COM imagens:');
comImg.forEach(p => {
  console.log(' -', p.nome, '|', p.source);
  p.imagens.forEach(img => console.log('    URL:', img.url));
});

// Primeiros 5 produtos completos
console.log('\n========== PRIMEIROS 5 PRODUTOS ==========\n');
d.slice(0, 5).forEach((p, i) => {
  const s = p.specs || {};
  console.log('[' + (i+1) + '] ' + p.nome);
  console.log('    slug:       ' + p.slug);
  console.log('    marca:      ' + p.marca);
  console.log('    source:     ' + p.source);
  console.log('    sensor:     ' + s.sensor);
  console.log('    dpi_max:    ' + s.dpi_maximo);
  console.log('    peso:       ' + s.peso);
  console.log('    polling:    ' + s.polling_rate);
  console.log('    conexao:    ' + s.conexao);
  console.log('    imagens:    ' + (p.imagens ? p.imagens.length : 0));
  if (p.imagens && p.imagens.length > 0) {
    p.imagens.slice(0, 2).forEach(img => console.log('      > ' + img.url));
  }
  console.log('    desc_curta: ' + p.descricao_curta);
  console.log('    desc(200):  ' + (p.descricao || '').substring(0, 200));
  console.log('');
});
