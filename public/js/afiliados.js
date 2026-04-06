// public/js/afiliados.js
function copiarLink() {
    const input = document.getElementById('linkAfiliado');
    if (!input) return;

    navigator.clipboard.writeText(input.value).then(() => {
        const btn = document.querySelector('[onclick="copiarLink()"]');
        if (btn) {
            const original = btn.textContent;
            btn.textContent = 'Copiado!';
            setTimeout(() => { btn.textContent = original; }, 2000);
        }
    }).catch(() => {
        input.select();
        document.execCommand('copy');
    });
}
