@forelse($produtos as $produto)
    @include('admin.includes.produto-linha')
@empty
<div class="py-12 text-center border-b border-[var(--color-lab-border)]">
    <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor"
         stroke-width="1" stroke-linecap="round" stroke-linejoin="round"
         class="mx-auto text-gray-300 mb-4">
        <path d="m7.5 4.27 9 5.15"/><path d="M21 8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16Z"/><path d="m3.3 7 8.7 5 8.7-5"/><path d="M12 22V12"/>
    </svg>
    <p class="font-mono text-xs text-[var(--color-lab-muted)] uppercase tracking-widest">
        Nenhum produto encontrado
    </p>
</div>
@endforelse
@once
<script>
function toggleProdutoMenu(event, id) {
    event.stopPropagation();
    const menu = document.getElementById('pdrop-' + id);
    const isHidden = menu.classList.contains('hidden');

    document.querySelectorAll('[id^="pdrop-"]').forEach(m => m.classList.add('hidden'));

    if (isHidden) {
        const x = event.clientX;
        const y = event.clientY;
        menu.style.left = x + 'px';
        menu.style.top  = y + 'px';

        menu.classList.remove('hidden');
        const rect = menu.getBoundingClientRect();
        if (rect.right > window.innerWidth)  menu.style.left = (x - rect.width) + 'px';
        if (rect.bottom > window.innerHeight) menu.style.top = (y - rect.height) + 'px';
    }
}

function closeProdutoMenu(id) {
    document.getElementById('pdrop-' + id).classList.add('hidden');
}

document.addEventListener('click', function(e) {
    if (!e.target.closest('[id^="menu-container-"]')) {
        document.querySelectorAll('[id^="pdrop-"]').forEach(m => m.classList.add('hidden'));
    }
});
</script>
@endonce
