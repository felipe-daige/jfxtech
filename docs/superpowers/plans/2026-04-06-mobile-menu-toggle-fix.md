# Mobile Menu Toggle Fix — Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** The mobile menu hamburger toggle correctly opens/closes on repeated clicks, and any click (inside or outside the menu) dismisses it when expanded.

**Architecture:** Pure JS fix in `public/js/dropdowns.js` — no backend, no build step. Two bugs: (1) state is captured after `closeAllDropdowns()` so toggle always opens; (2) `stopPropagation` on menu items blocks the document-level close handler.

**Tech Stack:** Vanilla JS, Tailwind CSS (`opacity-0`/`invisible` classes)

---

### Files

- Modify: `public/js/dropdowns.js:40-84`

---

### Task 1: Fix the toggle — capture state before closing

**Files:**
- Modify: `public/js/dropdowns.js:41-56`

- [ ] **Step 1: Understand the current broken flow**

In `dropdowns.js:41-56`:
```js
mobileMenuToggle.addEventListener('click', function(e) {
    e.preventDefault();
    e.stopPropagation();

    closeAllDropdowns();  // ← closes mobile-menu HERE

    const isVisible = !mobileMenu.classList.contains('opacity-0'); // ← always false now

    if (isVisible) {
        mobileMenu.classList.add('opacity-0', 'invisible'); // never runs
    } else {
        mobileMenu.classList.remove('opacity-0', 'invisible'); // always runs → always opens
    }
});
```

- [ ] **Step 2: Apply the fix — move `isVisible` capture before `closeAllDropdowns()`**

Replace the entire mobile menu listener block (lines 41-57) in `public/js/dropdowns.js`:

```js
    if (mobileMenuToggle && mobileMenu) {
        mobileMenuToggle.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();

            const isVisible = !mobileMenu.classList.contains('opacity-0');

            closeAllDropdowns();

            if (!isVisible) {
                mobileMenu.classList.remove('opacity-0', 'invisible');
            }
        });
    }
```

- [ ] **Step 3: Verify manually**

Open the site on mobile viewport (or DevTools responsive mode). Click hamburger → menu opens. Click hamburger again → menu closes. ✓

---

### Task 2: Fix "click anywhere" — allow document handler to close menu

**Files:**
- Modify: `public/js/dropdowns.js:59-84`

- [ ] **Step 1: Understand the current broken flow**

Lines 79-84 attach `stopPropagation` to both `.user-dropdown-menu` and `#mobile-menu`. This means clicking inside the mobile menu (even on whitespace, not links) blocks the document click handler at line 60 from firing — so the menu stays open.

```js
const dropdownMenus = document.querySelectorAll('.user-dropdown-menu, #mobile-menu');
dropdownMenus.forEach(menu => {
    menu.addEventListener('click', function(e) {
        e.stopPropagation(); // ← blocks document close for mobile-menu too
    });
});
```

- [ ] **Step 2: Apply the fix — only block propagation for the user dropdown, not mobile menu**

Replace lines 59-84 in `public/js/dropdowns.js` with:

```js
    // Fechar dropdowns ao clicar fora
    document.addEventListener('click', function(e) {
        const isInsideDropdown = e.target.closest('.user-dropdown') ||
                                e.target.closest('#mobile-menu-toggle');

        if (!isInsideDropdown) {
            closeAllDropdowns();
        }
    });

    // Fechar dropdowns ao pressionar ESC
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            closeAllDropdowns();
        }
    });

    // Prevenir fechamento ao clicar dentro do dropdown do usuário
    const userDropdownMenuEl = document.querySelector('.user-dropdown-menu');
    if (userDropdownMenuEl) {
        userDropdownMenuEl.addEventListener('click', function(e) {
            e.stopPropagation();
        });
    }
```

- [ ] **Step 3: Verify manually**

Open mobile menu → click a blank area inside → menu closes. Click hamburger → menu opens → click anywhere outside → menu closes. Links inside still navigate normally. ✓

---

### Task 3: Commit

- [ ] **Step 1: Stage and commit**

```bash
git add public/js/dropdowns.js
git commit -m "fix: mobile menu toggle now closes on re-click and any outside click"
```
