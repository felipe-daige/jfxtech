# Variant Option Deselect Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Allow users to deselect a previously selected variant option by clicking it again on the product detail page.

**Architecture:** The fix is entirely in the front-end click handler for `.opcao-btn` inside `produto-detalhes.js`. When the clicked button is already active (i.e. `selecao[grupoId] === valorId`), the handler should deselect it — clearing the visual state, removing the entry from `selecao`, resetting the add-to-cart button, and restoring the thumbnail gallery.

**Tech Stack:** Vanilla JS (`public/js/produto-detalhes.js`), no build step needed.

---

### Task 1: Add toggle (deselect) behaviour to the variant option click handler

**Files:**
- Modify: `public/js/produto-detalhes.js:158-263`

- [ ] **Step 1: Identify the existing click handler block**

Open `public/js/produto-detalhes.js`. The handler starts at line 158:

```js
opcaoBtns.forEach(function(btn) {
    btn.addEventListener('click', function() {
        var grupoId = this.getAttribute('data-grupo');
        var valorId = parseInt(this.getAttribute('data-valor'));
        // ...
    });
});
```

- [ ] **Step 2: Add deselect branch at the top of the handler**

Replace the handler body so that clicking an already-selected button deselects it. The full updated handler (replacing lines 158-263) is:

```js
opcaoBtns.forEach(function(btn) {
    btn.addEventListener('click', function() {
        var grupoId = this.getAttribute('data-grupo');
        var valorId = parseInt(this.getAttribute('data-valor'));

        // --- DESELECT if already selected ---
        if (selecao[grupoId] === valorId) {
            // Clear visual state for this button
            this.classList.remove('border-black', 'bg-black', 'text-white');
            this.classList.add('border-[var(--color-lab-border)]');

            // Remove from selection map
            delete selecao[grupoId];

            // Reset add-to-cart button
            if (addToCartBtn) {
                addToCartBtn.removeAttribute('data-variante-id');
                addToCartBtn.disabled = true;
                addToCartBtn.classList.add('opacity-50', 'cursor-not-allowed');
                addToCartBtn.textContent = 'SELECIONE AS OPÇÕES';
            }

            // Restore all thumbnails
            thumbnailBtns.forEach(function(b) { b.style.display = ''; });

            return;
        }

        // --- SELECT ---

        // Restore all thumbnails before applying new filter
        thumbnailBtns.forEach(function(b) { b.style.display = ''; });

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
            return;
        }

        // Update price display
        var priceEl = document.querySelector('.text-3xl.font-mono.font-bold');
        if (priceEl) {
            priceEl.textContent = 'R$ ' + varianteEncontrada.preco_efetivo.toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        }

        // Update stock display
        var stockEl = document.querySelector('.text-green-700, .text-red-600');
        if (stockEl && varianteEncontrada.estoque_efetivo > 0) {
            var unitsEl = document.getElementById('stock-units');
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

        // Filter gallery by variant images
        var imagemIds = varianteEncontrada.imagem_ids || [];
        if (imagemIds.length > 0) {
            var primeiroBtn = null;
            thumbnailBtns.forEach(function(b) {
                var imgId = parseInt(b.getAttribute('data-imagem-id'));
                if (imagemIds.indexOf(imgId) !== -1) {
                    b.style.display = '';
                    if (!primeiroBtn) primeiroBtn = b;
                } else {
                    b.style.display = 'none';
                }
            });
            if (primeiroBtn && mainImage) {
                mainImage.src = primeiroBtn.getAttribute('data-image');
                thumbnailBtns.forEach(function(b) {
                    b.classList.remove('border-black');
                    b.classList.add('border-[var(--color-lab-border)]');
                });
                primeiroBtn.classList.add('border-black');
                primeiroBtn.classList.remove('border-[var(--color-lab-border)]');
            }
        } else {
            // No images associated: show all
            thumbnailBtns.forEach(function(b) { b.style.display = ''; });
        }
    });
});
```

- [ ] **Step 3: Verify manually in the browser**

1. Open a product page with variants.
2. Click one option → it highlights (black background).
3. Click the same option again → it unhighlights, add-to-cart resets to "SELECIONE AS OPÇÕES" (disabled).
4. Click a different option → it highlights normally, deselect still works.
5. Select all groups → add-to-cart enables as before.

- [ ] **Step 4: Commit**

```bash
git add public/js/produto-detalhes.js
git commit -m "fix: allow deselecting variant option by clicking it again"
```
