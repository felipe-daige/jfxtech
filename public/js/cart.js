// Cart JavaScript - JFXTECH

function escapeHtml(str) {
    return String(str)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;');
}

document.addEventListener('DOMContentLoaded', function() {

    // Cart elements
    var cartDropdownTrigger = document.querySelector('.cart-dropdown-trigger');
    var cartSidebar = document.getElementById('cart-sidebar');
    var cartOverlay = document.getElementById('cart-overlay');
    var closeCartBtn = document.getElementById('close-cart');
    var cartContent = document.querySelector('.cart-scroll');
    var cartCounter = document.querySelector('.cart-dropdown-trigger span');

    // Open cart
    function openCart() {
        if (!cartSidebar) return;
        closeAllDropdowns();
        cartSidebar.classList.remove('translate-x-full');
        cartOverlay.classList.remove('opacity-0', 'invisible');
        document.body.classList.add('overflow-hidden');

        if (cartContent) cartContent.classList.remove('has-scroll');

        setTimeout(function() {
            if (cartContent) {
                var hasOverflow = cartContent.scrollHeight > (cartContent.clientHeight + 10);
                if (hasOverflow) cartContent.classList.add('has-scroll');
            }
        }, 300);
    }

    function pulseCartCounter() {
        if (!cartCounter) return;

        cartCounter.classList.remove('scale-125', 'ring-4', 'ring-black/10');
        void cartCounter.offsetWidth;
        cartCounter.classList.add('scale-125', 'ring-4', 'ring-black/10', 'transition-all', 'duration-300');

        setTimeout(function() {
            cartCounter.classList.remove('scale-125', 'ring-4', 'ring-black/10');
        }, 320);
    }

    function animateAddToCartFeedback(sourceElement) {
        if (!sourceElement || !cartDropdownTrigger) return;

        var sourceRect = sourceElement.getBoundingClientRect();
        var targetRect = cartDropdownTrigger.getBoundingClientRect();
        var ghost = document.createElement('div');

        ghost.className = 'fixed z-[70] w-10 h-10 rounded-full bg-black text-white flex items-center justify-center shadow-2xl pointer-events-none';
        ghost.innerHTML = '<svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="8" cy="21" r="1"/><circle cx="19" cy="21" r="1"/><path d="M2.05 2.05h2l2.66 12.42a2 2 0 0 0 2 1.58h9.78a2 2 0 0 0 1.95-1.57l1.65-7.43H5.12"/></svg>';
        ghost.style.left = sourceRect.left + (sourceRect.width / 2) - 20 + 'px';
        ghost.style.top = sourceRect.top + (sourceRect.height / 2) - 20 + 'px';
        ghost.style.transition = 'transform 520ms cubic-bezier(0.22, 1, 0.36, 1), opacity 520ms ease';
        ghost.style.transform = 'translate3d(0, 0, 0) scale(1)';
        ghost.style.opacity = '0.96';

        document.body.appendChild(ghost);

        requestAnimationFrame(function() {
            var deltaX = (targetRect.left + (targetRect.width / 2)) - (sourceRect.left + (sourceRect.width / 2));
            var deltaY = (targetRect.top + (targetRect.height / 2)) - (sourceRect.top + (sourceRect.height / 2));
            ghost.style.transform = 'translate3d(' + deltaX + 'px, ' + deltaY + 'px, 0) scale(0.35)';
            ghost.style.opacity = '0.2';
        });

        setTimeout(function() {
            ghost.remove();
            pulseCartCounter();
        }, 560);
    }

    function restoreCardButton(button, originalContent) {
        if (!button) return;

        button.innerHTML = originalContent;
        button.disabled = false;
        button.classList.remove('scale-110', 'bg-emerald-600', 'hover:bg-emerald-700');
        button.classList.add('bg-black', 'hover:bg-gray-800');
    }

    function showCardButtonSuccess(button) {
        if (!button) return;

        button.innerHTML = '<svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg>';
        button.classList.add('scale-110', 'bg-emerald-600', 'hover:bg-emerald-700');
        button.classList.remove('bg-black', 'hover:bg-gray-800');
    }

    function handleProductCardAddToCart(event, button) {
        if (event) {
            event.preventDefault();
            event.stopPropagation();
        }

        if (!button || button.disabled) return false;

        var produtoId = button.getAttribute('data-produto-id');
        if (!produtoId) return false;

        var csrfMeta = document.querySelector('meta[name="csrf-token"]');
        if (!csrfMeta) return false;

        button.disabled = true;
        var originalContent = button.innerHTML;
        button.innerHTML = '<svg class="w-4 h-4 loading-spinner" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 12a9 9 0 1 1-6.219-8.56"/></svg>';

        fetch('/carrinho/adicionar', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfMeta.getAttribute('content'),
                'Accept': 'application/json'
            },
            body: JSON.stringify({ produto_id: produtoId, quantidade: 1 })
        })
        .then(function(response) { return response.json(); })
        .then(function(data) {
            if (data.success) {
                showCardButtonSuccess(button);
                animateAddToCartFeedback(button);
                showNotification('Produto adicionado ao carrinho!', 'success');
                updateCartCounter();
                loadCartItems();

                setTimeout(function() {
                    restoreCardButton(button, originalContent);
                }, 1800);

                return;
            }

            if (data.message && data.message.includes('logado')) {
                window.location.href = '/login';
                return;
            }

            showNotification(data.message || 'Erro ao adicionar', 'error');
            restoreCardButton(button, originalContent);
        })
        .catch(function() {
            showNotification('Erro ao adicionar produto', 'error');
            restoreCardButton(button, originalContent);
        });

        return false;
    }

    // Close cart
    function closeCart() {
        if (!cartSidebar) return;
        cartSidebar.classList.add('translate-x-full');
        cartOverlay.classList.add('opacity-0', 'invisible');
        document.body.classList.remove('overflow-hidden');
        if (cartContent) cartContent.classList.remove('has-scroll');
    }

    // Close all dropdowns
    function closeAllDropdowns() {
        var userDropdownMenu = document.querySelector('.user-dropdown-menu');
        if (userDropdownMenu) userDropdownMenu.classList.add('opacity-0', 'invisible');
        var mobileMenu = document.getElementById('mobile-menu');
        if (mobileMenu) mobileMenu.classList.add('opacity-0', 'invisible');
    }

    // Event listeners
    if (cartDropdownTrigger) {
        cartDropdownTrigger.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            loadCartItems();
            openCart();
        });
    }

    if (closeCartBtn) closeCartBtn.addEventListener('click', closeCart);
    if (cartOverlay) cartOverlay.addEventListener('click', closeCart);

    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape' && cartSidebar && !cartSidebar.classList.contains('translate-x-full')) {
            closeCart();
        }
    });

    if (cartSidebar) {
        cartSidebar.addEventListener('click', function(e) { e.stopPropagation(); });
    }

    // Update cart counter
    function updateCartCounter() {
        if (cartCounter) {
            fetch('/carrinho/contador', {
                method: 'GET',
                headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' }
            })
            .then(function(response) { return response.json(); })
            .then(function(data) {
                if (data.success) {
                    cartCounter.textContent = data.total_itens || 0;
                    updateCheckoutButton(data.total_itens || 0);
                }
            })
            .catch(function() {
                var itemCountElement = document.querySelector('.cart-item-count');
                if (itemCountElement) itemCountElement.textContent = '0 ITENS';
                updateCheckoutButton(0);
            });
        }
    }

    // Update checkout button
    function updateCheckoutButton(totalItens) {
        var checkoutBtn = document.getElementById('checkout-btn');
        if (checkoutBtn) {
            if (totalItens > 0) {
                checkoutBtn.classList.remove('opacity-50', 'pointer-events-none');
            } else {
                checkoutBtn.classList.add('opacity-50', 'pointer-events-none');
            }
        }
    }

    // Load cart items
    function loadCartItems() {
        if (!cartContent) return;

        cartContent.innerHTML = '<div class="p-5"><div class="text-center py-12"><div class="loading-spinner w-6 h-6 border-2 border-black border-t-transparent rounded-full mx-auto mb-4"></div><p class="text-gray-500 text-sm font-mono">CARREGANDO...</p></div></div>';

        fetch('/carrinho/itens', {
            method: 'GET',
            headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' }
        })
        .then(function(response) { return response.json(); })
        .then(function(data) {
            if (data.success && data.carrinho) {
                updateCartDisplay(data.carrinho);
            } else {
                showEmptyCart();
            }
        })
        .catch(function() { showEmptyCart(); });
    }

    // Update cart display
    function updateCartDisplay(carrinho) {
        if (!cartContent) return;

        if (!carrinho || !carrinho.itens || carrinho.itens.length === 0) {
            showEmptyCart();
            return;
        }

        var itemsHTML = '';
        carrinho.itens.forEach(function(item) {
            var preco = (parseFloat(item.preco) || 0).toFixed(2).replace('.', ',');
            var opcoesHtml = '';
            if (item.opcoes_snapshot && Object.keys(item.opcoes_snapshot).length > 0) {
                var partes = Object.entries(item.opcoes_snapshot).map(function(entry) {
                    return escapeHtml(entry[0]) + ': ' + escapeHtml(entry[1]);
                });
                opcoesHtml = '<span class="block text-xs font-mono text-gray-400 mt-1">' +
                             partes.join(' · ') + '</span>';
            }
            var varianteId = item.produto_variante_id != null ? item.produto_variante_id : null;
            var varianteAttr = varianteId !== null ? varianteId : '';
            itemsHTML += '<div class="bg-white border border-[var(--color-lab-border)] p-4 mb-3 cart-item" data-produto-id="' + item.produto.id + '" data-variante-id="' + varianteAttr + '">' +
                '<div class="flex items-center gap-3">' +
                    '<div class="w-14 h-14 bg-[var(--color-lab-bg)] border border-[var(--color-lab-border)] flex items-center justify-center flex-shrink-0 overflow-hidden">' +
                        (item.produto.primeira_imagem ?
                            '<img src="/storage/' + item.produto.primeira_imagem + '" alt="' + escapeHtml(item.produto.nome) + '" class="w-full h-full object-cover">' :
                            '<svg class="w-6 h-6 text-gray-300" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1"><rect width="18" height="18" x="3" y="3" rx="2" ry="2"/><circle cx="9" cy="9" r="2"/><path d="m21 15-3.086-3.086a2 2 0 0 0-2.828 0L6 21"/></svg>'
                        ) +
                    '</div>' +
                    '<div class="flex-1 min-w-0">' +
                        '<h4 class="font-bold text-sm truncate uppercase">' + escapeHtml(item.produto.nome) + '</h4>' +
                        opcoesHtml +
                        '<p class="font-mono text-sm font-bold">R$ ' + preco + '</p>' +
                    '</div>' +
                    '<div class="flex items-center gap-2">' +
                        '<div class="flex items-center border border-[var(--color-lab-border)]">' +
                            '<button class="quantity-decrease-btn w-7 h-7 flex items-center justify-center hover:bg-gray-100 transition-colors text-xs" data-produto-id="' + item.produto.id + '" data-variante-id="' + varianteAttr + '">−</button>' +
                            '<span class="quantity-display w-7 h-7 flex items-center justify-center font-mono text-xs font-bold border-x border-[var(--color-lab-border)]">' + item.quantidade + '</span>' +
                            '<button class="quantity-increase-btn w-7 h-7 flex items-center justify-center hover:bg-gray-100 transition-colors text-xs" data-produto-id="' + item.produto.id + '" data-variante-id="' + varianteAttr + '">+</button>' +
                        '</div>' +
                        '<button class="remove-item-btn w-7 h-7 flex items-center justify-center text-gray-400 hover:text-red-600 transition-colors" data-produto-id="' + item.produto.id + '" data-variante-id="' + varianteAttr + '" title="Remover">' +
                            '<svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 6h18"/><path d="M19 6v14c0 1-1 2-2 2H7c-1 0-2-1-2-2V6"/><path d="M8 6V4c0-1 1-2 2-2h4c1 0 2 1 2 2v2"/></svg>' +
                        '</button>' +
                    '</div>' +
                '</div>' +
            '</div>';
        });

        cartContent.innerHTML = '<div class="p-5">' + itemsHTML + '</div>';
        updateCartInfo(carrinho);
        addRemoveItemListeners();
    }

    // Show empty cart
    function showEmptyCart() {
        if (!cartContent) return;

        cartContent.innerHTML = '<div class="p-5">' +
            '<div class="text-center py-16">' +
                '<div class="w-20 h-20 border-2 border-dashed border-gray-300 flex items-center justify-center mx-auto mb-6">' +
                    '<svg class="w-8 h-8 text-gray-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="8" cy="21" r="1"/><circle cx="19" cy="21" r="1"/><path d="M2.05 2.05h2l2.66 12.42a2 2 0 0 0 2 1.58h9.78a2 2 0 0 0 1.95-1.57l1.65-7.43H5.12"/></svg>' +
                '</div>' +
                '<h4 class="font-bold text-lg mb-2">Carrinho Vazio</h4>' +
                '<p class="text-gray-500 text-sm mb-8 max-w-xs mx-auto font-mono">NENHUM ITEM ADICIONADO AINDA.</p>' +
                '<div class="space-y-3 max-w-xs mx-auto">' +
                    '<a href="/produtos" class="block w-full bg-black text-white py-3 px-6 text-xs font-bold tracking-widest uppercase hover:bg-gray-800 transition-colors text-center cart-button">EXPLORAR PRODUTOS</a>' +
                '</div>' +
            '</div>' +
        '</div>';

        updateCartInfo(null);
    }

    // Update cart info (subtotal, total, item count)
    function updateCartInfo(carrinho) {
        var subtotalElement = document.querySelector('.cart-subtotal');
        var totalElement = document.querySelector('.cart-total');
        var itemCountElement = document.querySelector('.cart-item-count');

        if (carrinho && carrinho.itens && carrinho.itens.length > 0) {
            var subtotal = parseFloat(carrinho.valor_total) || 0;
            var total = subtotal;

            if (subtotalElement) subtotalElement.textContent = 'R$ ' + subtotal.toFixed(2).replace('.', ',');
            if (totalElement) totalElement.textContent = 'R$ ' + total.toFixed(2).replace('.', ',');
            if (itemCountElement) itemCountElement.textContent = carrinho.itens.length + ' ITENS';
        } else {
            if (subtotalElement) subtotalElement.textContent = 'R$ 0,00';
            if (totalElement) totalElement.textContent = 'R$ 0,00';
            if (itemCountElement) itemCountElement.textContent = '0 ITENS';
        }
    }

    // Add event listeners for quantity controls and remove buttons
    function addRemoveItemListeners() {
        document.querySelectorAll('.remove-item-btn').forEach(function(button) {
            button.addEventListener('click', function(e) {
                e.preventDefault();
                removeFromCart(this.getAttribute('data-produto-id'), this.getAttribute('data-variante-id'));
            });
        });

        document.querySelectorAll('.quantity-decrease-btn').forEach(function(button) {
            button.addEventListener('click', function(e) {
                e.preventDefault();
                var produtoId = this.getAttribute('data-produto-id');
                var varianteId = this.getAttribute('data-variante-id');
                var quantityDisplay = this.parentElement.querySelector('.quantity-display');
                var currentQuantity = parseInt(quantityDisplay.textContent, 10);
                if (currentQuantity > 1) {
                    updateQuantity(produtoId, varianteId, currentQuantity - 1);
                } else {
                    removeFromCart(produtoId, varianteId);
                }
            });
        });

        document.querySelectorAll('.quantity-increase-btn').forEach(function(button) {
            button.addEventListener('click', function(e) {
                e.preventDefault();
                var produtoId = this.getAttribute('data-produto-id');
                var varianteId = this.getAttribute('data-variante-id');
                var quantityDisplay = this.parentElement.querySelector('.quantity-display');
                var currentQuantity = parseInt(quantityDisplay.textContent, 10);
                updateQuantity(produtoId, varianteId, currentQuantity + 1);
            });
        });
    }

    // Update quantity
    function updateQuantity(produtoId, varianteId, newQuantity) {
        var csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

        fetch('/carrinho/atualizar', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' },
            body: JSON.stringify({
                produto_id: produtoId,
                produto_variante_id: (varianteId !== null && varianteId !== '') ? parseInt(varianteId, 10) : null,
                quantidade: newQuantity
            })
        })
        .then(function(response) { return response.json(); })
        .then(function(data) {
            if (data.success) { loadCartItems(); updateCartCounter(); }
        })
        .catch(function() { loadCartItems(); updateCartCounter(); });
    }

    // Remove from cart
    function removeFromCart(produtoId, varianteId) {
        var csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

        fetch('/carrinho/remover', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' },
            body: JSON.stringify({
                produto_id: produtoId,
                produto_variante_id: varianteId !== null ? parseInt(varianteId, 10) : null
            })
        })
        .then(function(response) { return response.json(); })
        .then(function(data) {
            if (data.success) { loadCartItems(); updateCartCounter(); }
        })
        .catch(function() { loadCartItems(); updateCartCounter(); });
    }

    // Show notification
    function showNotification(message, type) {
        type = type || 'info';
        var notification = document.createElement('div');
        notification.className = 'fixed top-4 right-4 z-[60] pointer-events-none px-6 py-3 shadow-lg text-white text-sm font-mono font-bold uppercase tracking-wider transition-all duration-300 transform translate-x-full';

        var colors = { success: 'bg-black', error: 'bg-red-600', info: 'bg-gray-800', warning: 'bg-yellow-600' };
        notification.classList.add(colors[type] || colors.info);
        notification.textContent = message;
        document.body.appendChild(notification);

        setTimeout(function() { notification.classList.remove('translate-x-full'); }, 100);
        setTimeout(function() {
            notification.classList.add('translate-x-full');
            setTimeout(function() { if (notification.parentNode) notification.remove(); }, 300);
        }, 3000);
    }

    // MutationObserver for cart scroll
    if (cartContent) {
        var observer = new MutationObserver(function() {
            setTimeout(function() {
                cartContent.classList.remove('has-scroll');
                var hasOverflow = cartContent.scrollHeight > (cartContent.clientHeight + 5);
                if (hasOverflow) cartContent.classList.add('has-scroll');
            }, 100);
        });
        observer.observe(cartContent, { childList: true, subtree: true, attributes: true });
    }

    // Expose functions globally
    window.updateCartCounter = updateCartCounter;
    window.loadCartItems = loadCartItems;
    window.showNotification = showNotification;
    window.openCart = openCart;
    window.animateAddToCartFeedback = animateAddToCartFeedback;
    window.pulseCartCounter = pulseCartCounter;
    window.handleProductCardAddToCart = handleProductCardAddToCart;

    // Initialize
    updateCartCounter();
});
