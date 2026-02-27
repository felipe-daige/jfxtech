/**
 * Products Page JavaScript - JFXTECH
 */
document.addEventListener('DOMContentLoaded', function() {

    // Load cart counter
    if (window.updateCartCounter) window.updateCartCounter();

    // Add to cart click delegation
    document.addEventListener('click', function(e) {
        if (e.target.closest('.add-to-cart-btn')) {
            e.preventDefault();
            e.stopPropagation();
            var button = e.target.closest('.add-to-cart-btn');
            var produtoId = button.getAttribute('data-produto-id');
            if (!produtoId) return;

            button.disabled = true;
            var originalContent = button.innerHTML;
            button.innerHTML = '<svg class="w-4 h-4 loading-spinner" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 12a9 9 0 1 1-6.219-8.56"/></svg>';

            var csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

            fetch('/carrinho/adicionar', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' },
                body: JSON.stringify({ produto_id: produtoId, quantidade: 1 })
            })
            .then(function(response) { return response.json(); })
            .then(function(data) {
                if (data.success) {
                    button.innerHTML = '<svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg>';
                    if (window.showNotification) window.showNotification('Produto adicionado ao carrinho!', 'success');
                    if (window.updateCartCounter) window.updateCartCounter();
                    setTimeout(function() { button.innerHTML = originalContent; button.disabled = false; }, 2000);
                } else {
                    if (data.message && data.message.includes('logado')) {
                        window.location.href = '/login';
                    } else {
                        if (window.showNotification) window.showNotification(data.message || 'Erro ao adicionar', 'error');
                        button.innerHTML = originalContent;
                        button.disabled = false;
                    }
                }
            })
            .catch(function() {
                if (window.showNotification) window.showNotification('Erro ao adicionar produto', 'error');
                button.innerHTML = originalContent;
                button.disabled = false;
            });
        }
    });

    // Notification system (local fallback if not available from cart.js)
    if (!window.showNotification) {
        window.showNotification = function(message, type) {
            type = type || 'info';
            document.querySelectorAll('.notification').forEach(function(n) { n.remove(); });
            var notification = document.createElement('div');
            notification.className = 'notification fixed top-4 right-4 z-[60] px-6 py-3 shadow-lg text-white text-sm font-mono font-bold uppercase tracking-wider transition-all duration-300 transform translate-x-full';
            var colors = { success: 'bg-black', error: 'bg-red-600', info: 'bg-gray-800', warning: 'bg-yellow-600' };
            notification.classList.add(colors[type] || colors.info);
            notification.textContent = message;
            document.body.appendChild(notification);
            setTimeout(function() { notification.classList.remove('translate-x-full'); }, 100);
            setTimeout(function() {
                notification.classList.add('translate-x-full');
                setTimeout(function() { if (notification.parentNode) notification.remove(); }, 300);
            }, 3000);
        };
    }
});
