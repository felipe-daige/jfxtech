/**
 * Product Detail Page JavaScript - JFXTECH
 */
document.addEventListener('DOMContentLoaded', function() {

    if (window.updateCartCounter) window.updateCartCounter();

    var mainImage = document.getElementById('main-image');
    var mainImageFrame = document.getElementById('main-image-frame');
    var thumbnailBtns = document.querySelectorAll('.thumbnail-btn');
    var quantityInput = document.getElementById('quantity');
    var decreaseBtn = document.getElementById('decrease-qty');
    var increaseBtn = document.getElementById('increase-qty');
    var toggleFavoriteBtn = document.getElementById('toggle-favorite');
    var canHoverZoom = !window.matchMedia || window.matchMedia('(hover: hover) and (pointer: fine)').matches;
    var defaultMainImageSrc = mainImage ? mainImage.src : null;

    function resetMainImageZoom() {
        if (!mainImage || !mainImageFrame) return;
        mainImageFrame.style.setProperty('--product-zoom-scale', '1');
        mainImageFrame.style.setProperty('--product-zoom-x', '50%');
        mainImageFrame.style.setProperty('--product-zoom-y', '50%');
        mainImageFrame.classList.remove('is-zoomed');
    }

    function updateMainImage(imageSrc) {
        if (!mainImage || !imageSrc) return;
        mainImage.src = imageSrc;
        resetMainImageZoom();
    }

    document.querySelectorAll('.js-product-description').forEach(function(section) {
        var toggle = section.querySelector('[data-description-toggle]');
        var fullContent = section.querySelector('[data-description-full]');
        var summary = section.querySelector('[data-description-summary]');

        if (!toggle || !fullContent || !summary) return;

        var label = toggle.querySelector('[data-expand-label]');
        var icon = toggle.querySelector('[data-expand-icon]');

        toggle.addEventListener('click', function() {
            var expanded = toggle.getAttribute('aria-expanded') === 'true';
            toggle.setAttribute('aria-expanded', expanded ? 'false' : 'true');
            fullContent.classList.toggle('hidden', expanded);
            summary.classList.toggle('hidden', !expanded);

            if (label) {
                label.textContent = expanded ? 'Ler descrição completa' : 'Mostrar menos';
            }

            if (icon) {
                icon.classList.toggle('rotate-180', !expanded);
            }
        });
    });

    // Thumbnail image switching
    if (thumbnailBtns.length > 0) {
        thumbnailBtns.forEach(function(btn) {
            btn.addEventListener('click', function() {
                var imageSrc = this.getAttribute('data-image');
                updateMainImage(imageSrc);
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

            var varianteId = this.getAttribute('data-variante-id');
            fetch('/carrinho/adicionar', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' },
                body: JSON.stringify({ produto_id: produtoId, quantidade: qty, produto_variante_id: varianteId ? parseInt(varianteId) : null })
            })
            .then(function(response) { return response.json(); })
            .then(function(data) {
                if (data.success) {
                    btn.innerHTML = 'ADICIONADO!';
                    if (typeof window.animateAddToCartFeedback === 'function') {
                        window.animateAddToCartFeedback(btn);
                    }
                    if (window.showNotification) window.showNotification('Produto adicionado ao carrinho!', 'success');
                    if (window.updateCartCounter) window.updateCartCounter();
                    if (window.loadCartItems) window.loadCartItems();
                    setTimeout(function() {
                        if (window.openCart) window.openCart();
                    }, 180);
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

    // Variant selector
    var variantesDataEl = document.getElementById('variantes-data');
    if (variantesDataEl) {
        var variantes = JSON.parse(variantesDataEl.textContent);
        var selecao = {}; // { grupoId: valorId }
        var opcaoBtns = document.querySelectorAll('.opcao-btn');
        var priceEl = document.querySelector('.text-3xl.font-mono.font-bold');
        var precoOriginalTexto = priceEl ? priceEl.textContent.trim() : null;
        var unitsEl = document.getElementById('stock-units');
        var stockUnitsOriginalTexto = unitsEl ? unitsEl.textContent.trim() : null;
        var descricaoSectionInit = document.getElementById('descricao-section');
        var initialDescricaoHtml = descricaoSectionInit ? descricaoSectionInit.innerHTML : null;
        var specsGridContainerInit = document.getElementById('specs-grid-container');
        var initialSpecsHtml = specsGridContainerInit ? specsGridContainerInit.innerHTML : null;
        var gruposIds = [];

        opcaoBtns.forEach(function(btn) {
            var grupoId = btn.getAttribute('data-grupo');
            if (gruposIds.indexOf(grupoId) === -1) gruposIds.push(grupoId);
        });

        opcaoBtns.forEach(function(btn) {
            btn.addEventListener('click', function() {
                var grupoId = this.getAttribute('data-grupo');
                var valorId = parseInt(this.getAttribute('data-valor'));

                // --- DESELECT if already selected ---
                if (selecao[grupoId] === valorId) {
                    this.classList.remove('border-black', 'bg-black', 'text-white');
                    this.classList.add('border-[var(--color-lab-border)]');
                    delete selecao[grupoId];
                    if (addToCartBtn) {
                        addToCartBtn.removeAttribute('data-variante-id');
                        addToCartBtn.disabled = true;
                        addToCartBtn.classList.add('opacity-50', 'cursor-not-allowed');
                        addToCartBtn.textContent = 'SELECIONE AS OPÇÕES';
                    }
                    thumbnailBtns.forEach(function(b) { b.style.display = ''; });
                    updateMainImage(defaultMainImageSrc);
                    if (quantityInput) {
                        quantityInput.setAttribute('max', 0);
                        quantityInput.value = 1;
                    }
                    if (priceEl && precoOriginalTexto) priceEl.textContent = precoOriginalTexto;
                    if (unitsEl && stockUnitsOriginalTexto) unitsEl.textContent = stockUnitsOriginalTexto;
                    var descSection = document.getElementById('descricao-section');
                    if (descSection && initialDescricaoHtml !== null) { descSection.innerHTML = initialDescricaoHtml; descSection.style.display = ''; }
                    var specsCont = document.getElementById('specs-grid-container');
                    if (specsCont && initialSpecsHtml !== null) { specsCont.innerHTML = initialSpecsHtml; }
                    var specsSection = document.getElementById('specs-section');
                    if (specsSection) specsSection.style.display = '';
                    return;
                }

                // --- SELECT ---

                // Restaurar todos os thumbnails visíveis ao trocar seleção
                thumbnailBtns.forEach(function(btn) { btn.style.display = ''; });

                // Deselect others in same group
                opcaoBtns.forEach(function(b) {
                    if (b.getAttribute('data-grupo') === grupoId) {
                        b.classList.remove('border-black', 'bg-black', 'text-white');
                        b.classList.add('border-[var(--color-lab-border)]');
                    }
                });
                this.classList.add('border-black', 'bg-black', 'text-white');
                this.classList.remove('border-[var(--color-lab-border)]');

                selecao[grupoId] = valorId;

                // Check if all groups are selected
                var todosGrupos = Object.keys(selecao).length === gruposIds.length;
                if (!todosGrupos) {
                    if (addToCartBtn) {
                        addToCartBtn.disabled = true;
                        addToCartBtn.classList.add('opacity-50', 'cursor-not-allowed');
                        addToCartBtn.textContent = 'SELECIONE AS OPÇÕES';
                    }
                    return;
                }

                // Find matching variant (sorted comparison)
                var selectedIds = gruposIds.map(function(g) { return parseInt(selecao[g]); }).sort(function(a, b) { return a - b; });
                var varianteEncontrada = null;
                for (var i = 0; i < variantes.length; i++) {
                    var vIds = variantes[i].valores.map(function(id) { return parseInt(id); }).slice().sort(function(a, b) { return a - b; });
                    if (JSON.stringify(vIds) === JSON.stringify(selectedIds)) {
                        varianteEncontrada = variantes[i];
                        break;
                    }
                }

                if (!varianteEncontrada || !varianteEncontrada.ativo || varianteEncontrada.estoque_efetivo <= 0) {
                    if (addToCartBtn) {
                        addToCartBtn.disabled = true;
                        addToCartBtn.classList.add('opacity-50', 'cursor-not-allowed');
                        addToCartBtn.textContent = 'INDISPONÍVEL';
                    }
                    if (quantityInput) quantityInput.setAttribute('max', 0);
                    if (priceEl && precoOriginalTexto) priceEl.textContent = precoOriginalTexto;
                    if (unitsEl && stockUnitsOriginalTexto) unitsEl.textContent = stockUnitsOriginalTexto;
                    return;
                }

                // Update price display
                priceEl = document.querySelector('.text-3xl.font-mono.font-bold');
                if (priceEl) {
                    priceEl.textContent = 'R$ ' + varianteEncontrada.preco_efetivo.toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
                }

                // Update stock display
                var stockEl = document.querySelector('.text-green-700, .text-red-600');
                if (stockEl && varianteEncontrada.estoque_efetivo > 0) {
                    unitsEl = document.getElementById('stock-units');
                    if (unitsEl) {
                        unitsEl.textContent = '(' + varianteEncontrada.estoque_efetivo + ' un.)';
                    }
                }

                // Update quantity max
                if (quantityInput) quantityInput.setAttribute('max', varianteEncontrada.estoque_efetivo);

                // Enable button with variante-id
                if (addToCartBtn) {
                    addToCartBtn.setAttribute('data-variante-id', varianteEncontrada.id);
                    addToCartBtn.disabled = false;
                    addToCartBtn.classList.remove('opacity-50', 'cursor-not-allowed');
                    addToCartBtn.textContent = 'ADICIONAR AO CARRINHO';
                }

                // Filtrar galeria pelas fotos da variante
                var imagemIds = varianteEncontrada.imagem_ids || [];
                if (imagemIds.length > 0) {
                    var primeiroBtn = null;
                    thumbnailBtns.forEach(function(btn) {
                        var imgId = parseInt(btn.getAttribute('data-imagem-id'));
                        if (imagemIds.indexOf(imgId) !== -1) {
                            btn.style.display = '';
                            if (!primeiroBtn) primeiroBtn = btn;
                        } else {
                            btn.style.display = 'none';
                        }
                    });
                    if (primeiroBtn && mainImage) {
                        updateMainImage(primeiroBtn.getAttribute('data-image'));
                        thumbnailBtns.forEach(function(b) {
                            b.classList.remove('border-black');
                            b.classList.add('border-[var(--color-lab-border)]');
                        });
                        primeiroBtn.classList.add('border-black');
                        primeiroBtn.classList.remove('border-[var(--color-lab-border)]');
                    }
                } else {
                    // Sem fotos associadas: mostra todas
                    thumbnailBtns.forEach(function(btn) { btn.style.display = ''; });
                }

                // Update description section
                var descricaoSection = document.getElementById('descricao-section');
                if (descricaoSection) {
                    var descricaoEfetiva = varianteEncontrada.descricao_efetiva;
                    if (descricaoEfetiva) {
                        var descFull = descricaoSection.querySelector('[data-description-full]');
                        var descSummary = descricaoSection.querySelector('[data-description-summary] p:last-child');
                        if (descFull) {
                            descFull.innerHTML = descricaoEfetiva;
                            // Reset expand/collapse toggle to collapsed state
                            descFull.classList.add('hidden');
                            var toggleBtn = descricaoSection.querySelector('[data-description-toggle]');
                            if (toggleBtn) {
                                toggleBtn.setAttribute('aria-expanded', 'false');
                                var expandIcon = toggleBtn.querySelector('[data-expand-icon]');
                                if (expandIcon) expandIcon.classList.remove('rotate-180');
                                var expandLabel = toggleBtn.querySelector('[data-expand-label]');
                                if (expandLabel) expandLabel.textContent = 'Ler descrição completa';
                            }
                            var summaryBlock = descricaoSection.querySelector('[data-description-summary]');
                            if (summaryBlock) summaryBlock.classList.remove('hidden');
                        }
                        if (descSummary) {
                            var textoLimpo = descricaoEfetiva.replace(/<[^>]*>/g, ' ').replace(/\s+/g, ' ').trim();
                            descSummary.textContent = textoLimpo.length > 420 ? textoLimpo.substring(0, 420) + '…' : textoLimpo;
                        }
                        descricaoSection.style.display = '';
                    } else {
                        descricaoSection.style.display = 'none';
                    }
                }

                // Update specs section
                var specsSection = document.getElementById('specs-section');
                var specsGridContainer = document.getElementById('specs-grid-container');
                if (specsGridContainer) {
                    var specsEfetivos = varianteEncontrada.specs_efetivos;
                    var specsValidas = specsEfetivos ? Object.keys(specsEfetivos).filter(function(k) { return specsEfetivos[k] !== null && specsEfetivos[k] !== ''; }) : [];
                    if (specsValidas.length > 0) {
                        var specLabels = {
                            'sensor': 'Sensor', 'dpi_maximo': 'DPI Máximo',
                            'switches': 'Switches', 'peso': 'Peso',
                            'conexao': 'Conexão', 'polling_rate': 'Polling Rate',
                            'dimensoes': 'Dimensões', 'cabo': 'Cabo',
                            'iluminacao': 'Iluminação', 'garantia': 'Garantia',
                            'layout': 'Layout', 'superficie': 'Superfície',
                            'base': 'Base', 'drivers': 'Drivers',
                            'frequencia': 'Frequência', 'microfone': 'Microfone',
                            'bateria': 'Bateria'
                        };
                        function escHtml(s) { var d = document.createElement('div'); d.appendChild(document.createTextNode(String(s))); return d.innerHTML; }
                        var gridHtml = '<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 border-t border-l border-white/10">';
                        specsValidas.forEach(function(key) {
                            var val = specsEfetivos[key];
                            var label = specLabels[key] || key.replace(/_/g, ' ');
                            gridHtml += '<div class="p-6 flex flex-col border-b border-r border-white/10 hover:bg-white/5 transition-colors">' +
                                '<p class="text-[10px] font-mono text-gray-500 uppercase tracking-widest mb-2">' + escHtml(label) + '</p>' +
                                '<span class="font-bold text-white text-lg leading-tight">' + escHtml(val) + '</span>' +
                                '</div>';
                        });
                        gridHtml += '</div>';
                        specsGridContainer.innerHTML = gridHtml;
                        if (specsSection) specsSection.style.display = '';
                    } else {
                        specsGridContainer.innerHTML = '';
                        if (specsSection) specsSection.style.display = 'none';
                    }
                }
            });
        });
    }

    // Image zoom
    if (mainImage) {
        if (canHoverZoom && mainImageFrame) {
            function applyHoverZoom(event) {
                var rect = mainImageFrame.getBoundingClientRect();
                var x = (event.clientX - rect.left) / rect.width;
                var y = (event.clientY - rect.top) / rect.height;
                var clampedX = Math.max(0, Math.min(1, x));
                var clampedY = Math.max(0, Math.min(1, y));

                mainImageFrame.classList.add('is-zoomed');
                mainImageFrame.style.setProperty('--product-zoom-scale', '2.6');
                mainImageFrame.style.setProperty('--product-zoom-x', (clampedX * 100) + '%');
                mainImageFrame.style.setProperty('--product-zoom-y', (clampedY * 100) + '%');
            }

            mainImageFrame.addEventListener('mouseenter', function(event) {
                applyHoverZoom(event);
            });

            mainImageFrame.addEventListener('mousemove', function(event) {
                applyHoverZoom(event);
            });

            mainImageFrame.addEventListener('mouseleave', function() {
                resetMainImageZoom();
            });
        }

        mainImage.addEventListener('click', function() {
            if (canHoverZoom) return;

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
