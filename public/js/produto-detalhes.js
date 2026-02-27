/**
 * Product Detail Page JavaScript - JFXTECH
 */
document.addEventListener('DOMContentLoaded', function() {

    if (window.updateCartCounter) window.updateCartCounter();

    var mainImage = document.getElementById('main-image');
    var thumbnailBtns = document.querySelectorAll('.thumbnail-btn');
    var quantityInput = document.getElementById('quantity');
    var decreaseBtn = document.getElementById('decrease-qty');
    var increaseBtn = document.getElementById('increase-qty');
    var toggleFavoriteBtn = document.getElementById('toggle-favorite');

    // Thumbnail image switching
    if (thumbnailBtns.length > 0) {
        thumbnailBtns.forEach(function(btn) {
            btn.addEventListener('click', function() {
                var imageSrc = this.getAttribute('data-image');
                if (mainImage) mainImage.src = imageSrc;
                thumbnailBtns.forEach(function(b) {
                    b.classList.remove('border-black');
                    b.classList.add('border-[var(--color-lab-border)]');
                });
                this.classList.remove('border-[var(--color-lab-border)]');
                this.classList.add('border-black');
            });
        });
    }

    // Quantity controls
    if (decreaseBtn && increaseBtn && quantityInput) {
        decreaseBtn.addEventListener('click', function() {
            var currentValue = parseInt(quantityInput.value);
            var minValue = parseInt(quantityInput.getAttribute('min'));
            if (currentValue > minValue) quantityInput.value = currentValue - 1;
        });

        increaseBtn.addEventListener('click', function() {
            var currentValue = parseInt(quantityInput.value);
            var maxValue = parseInt(quantityInput.getAttribute('max'));
            if (currentValue < maxValue) quantityInput.value = currentValue + 1;
        });

        quantityInput.addEventListener('input', function() {
            var value = parseInt(this.value);
            var min = parseInt(this.getAttribute('min'));
            var max = parseInt(this.getAttribute('max'));
            if (value < min) this.value = min;
            else if (value > max) this.value = max;
        });
    }

    // Add to cart
    var addToCartBtn = document.querySelector('.add-to-cart-btn');
    if (addToCartBtn) {
        addToCartBtn.addEventListener('click', function() {
            var produtoId = this.getAttribute('data-produto-id');
            if (!produtoId) return;

            var btn = this;
            btn.disabled = true;
            var originalContent = btn.innerHTML;
            btn.innerHTML = 'ADICIONANDO...';

            var csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
            var qty = quantityInput ? parseInt(quantityInput.value) : 1;

            fetch('/carrinho/adicionar', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' },
                body: JSON.stringify({ produto_id: produtoId, quantidade: qty })
            })
            .then(function(response) { return response.json(); })
            .then(function(data) {
                if (data.success) {
                    btn.innerHTML = 'ADICIONADO!';
                    if (window.showNotification) window.showNotification('Produto adicionado ao carrinho!', 'success');
                    if (window.updateCartCounter) window.updateCartCounter();
                    setTimeout(function() { btn.innerHTML = originalContent; btn.disabled = false; }, 2000);
                } else {
                    if (data.message && data.message.includes('logado')) {
                        window.location.href = '/login';
                    } else {
                        if (window.showNotification) window.showNotification(data.message || 'Erro ao adicionar', 'error');
                        btn.innerHTML = originalContent;
                        btn.disabled = false;
                    }
                }
            })
            .catch(function() {
                if (window.showNotification) window.showNotification('Erro ao adicionar produto', 'error');
                btn.innerHTML = originalContent;
                btn.disabled = false;
            });
        });
    }

    // Toggle favorite
    if (toggleFavoriteBtn) {
        toggleFavoriteBtn.addEventListener('click', function() {
            var produtoId = this.getAttribute('data-produto-id');
            if (!produtoId) return;

            var csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
            var isFavorited = this.classList.contains('bg-black');
            var action = isFavorited ? 'remover' : 'adicionar';
            var url = '/favoritos/' + action;
            var btn = this;

            fetch(url, {
                method: action === 'remover' ? 'DELETE' : 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' },
                body: JSON.stringify({ produto_id: produtoId })
            })
            .then(function(response) { return response.json(); })
            .then(function(data) {
                if (data.success) {
                    var heartSvg = btn.querySelector('svg');
                    if (isFavorited) {
                        btn.classList.remove('bg-black', 'text-white', 'border-black');
                        btn.classList.add('border-black');
                        if (heartSvg) heartSvg.setAttribute('fill', 'none');
                        if (window.showNotification) window.showNotification('Removido dos favoritos', 'info');
                    } else {
                        btn.classList.add('bg-black', 'text-white', 'border-black');
                        if (heartSvg) heartSvg.setAttribute('fill', 'currentColor');
                        if (window.showNotification) window.showNotification('Adicionado aos favoritos!', 'success');
                    }
                } else {
                    if (data.message && (data.message.includes('logado') || data.message.includes('autenticado'))) {
                        window.location.href = '/login';
                    } else {
                        if (window.showNotification) window.showNotification('Erro ao atualizar favoritos', 'error');
                    }
                }
            })
            .catch(function() {
                if (window.showNotification) window.showNotification('Erro ao atualizar favoritos', 'error');
            });
        });
    }

    // Image zoom
    if (mainImage) {
        mainImage.style.cursor = 'pointer';
        mainImage.addEventListener('click', function() {
            var modal = document.createElement('div');
            modal.className = 'fixed inset-0 bg-black/90 z-50 flex items-center justify-center p-4';
            modal.innerHTML = '<div class="relative max-w-4xl max-h-full">' +
                '<img src="' + this.src + '" alt="' + this.alt + '" class="max-w-full max-h-full object-contain">' +
                '<button class="absolute top-4 right-4 text-white w-10 h-10 flex items-center justify-center border border-white/30 hover:bg-white/10 transition-colors">' +
                '<svg class="w-5 h-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 6 6 18"/><path d="m6 6 12 12"/></svg>' +
                '</button></div>';
            document.body.appendChild(modal);
            modal.addEventListener('click', function(e) {
                if (e.target === modal || e.target.closest('button')) modal.remove();
            });
            document.addEventListener('keydown', function handler(e) {
                if (e.key === 'Escape') { modal.remove(); document.removeEventListener('keydown', handler); }
            });
        });
    }
});
