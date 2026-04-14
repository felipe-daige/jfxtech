// public/js/cupom-dashboard.js
(function () {
    'use strict';

    /* ─── Animated Counters ───────────────────────────────────────────── */
    function animateCounter(el) {
        const raw = el.dataset.value;
        const isMonetary = el.dataset.type === 'monetary';
        const target = parseFloat(raw);
        if (isNaN(target)) return;

        const duration = 700;
        const start = performance.now();

        function formatBR(n) {
            return n.toLocaleString('pt-BR', {
                minimumFractionDigits: isMonetary ? 2 : 0,
                maximumFractionDigits: isMonetary ? 2 : 0,
            });
        }

        function tick(now) {
            const elapsed = now - start;
            const progress = Math.min(elapsed / duration, 1);
            const eased = 1 - Math.pow(1 - progress, 3);
            const current = target * eased;
            el.textContent = (isMonetary ? 'R$ ' : '') + formatBR(current);
            if (progress < 1) requestAnimationFrame(tick);
        }

        requestAnimationFrame(tick);
    }

    document.querySelectorAll('[data-animate-counter]').forEach(animateCounter);

    /* ─── Table Sort ──────────────────────────────────────────────────── */
    function initSort(tableId) {
        const table = document.getElementById(tableId);
        if (!table) return;

        const tbody = table.querySelector('tbody');
        const headers = table.querySelectorAll('thead th[data-sort]');
        let currentCol = null;
        let ascending = true;

        headers.forEach(function (th) {
            th.style.cursor = 'pointer';
            th.setAttribute('tabindex', '0');
            th.addEventListener('keydown', function (e) {
                if (e.key === 'Enter' || e.key === ' ') {
                    e.preventDefault();
                    th.click();
                }
            });
            th.addEventListener('click', function () {
                const col = th.dataset.sort;
                const isNumeric = th.dataset.numeric === 'true';

                if (currentCol === col) {
                    ascending = !ascending;
                } else {
                    currentCol = col;
                    ascending = true;
                }

                headers.forEach(function (h) {
                    const icon = h.querySelector('.sort-icon');
                    if (icon) icon.textContent = '↕';
                });
                const icon = th.querySelector('.sort-icon');
                if (icon) icon.textContent = ascending ? '↑' : '↓';

                const rows = Array.from(tbody.querySelectorAll('tr.sale-row'));
                rows.sort(function (a, b) {
                    const av = a.dataset[col];
                    const bv = b.dataset[col];
                    if (isNumeric) {
                        return ascending
                            ? parseFloat(av) - parseFloat(bv)
                            : parseFloat(bv) - parseFloat(av);
                    }
                    return ascending
                        ? av.localeCompare(bv, 'pt-BR')
                        : bv.localeCompare(av, 'pt-BR');
                });

                rows.forEach(function (row) { tbody.appendChild(row); });
                paginationState[tableId].currentPage = 1;
                updatePagination(tableId);
            });
        });
    }

    /* ─── Pagination ──────────────────────────────────────────────────── */
    const paginationState = {};

    function updatePagination(tableId) {
        const table = document.getElementById(tableId);
        if (!table) return;

        const state = paginationState[tableId];
        const rows = Array.from(table.querySelectorAll('tbody tr.sale-row'));
        const pageSize = state.pageSize;
        const total = rows.length;
        const totalPages = Math.max(1, Math.ceil(total / pageSize));

        state.currentPage = Math.min(state.currentPage, totalPages);
        const start = (state.currentPage - 1) * pageSize;
        const end = start + pageSize;

        rows.forEach(function (row, i) {
            row.style.display = (i >= start && i < end) ? '' : 'none';
        });

        const info = document.getElementById(tableId + '-page-info');
        const btnPrev = document.getElementById(tableId + '-prev');
        const btnNext = document.getElementById(tableId + '-next');

        if (info) info.textContent = 'Página ' + state.currentPage + ' de ' + totalPages;
        if (btnPrev) btnPrev.disabled = state.currentPage <= 1;
        if (btnNext) btnNext.disabled = state.currentPage >= totalPages;
    }

    function initPagination(tableId, pageSize) {
        paginationState[tableId] = { currentPage: 1, pageSize: pageSize };

        const btnPrev = document.getElementById(tableId + '-prev');
        const btnNext = document.getElementById(tableId + '-next');

        if (btnPrev) {
            btnPrev.addEventListener('click', function () {
                paginationState[tableId].currentPage--;
                updatePagination(tableId);
            });
        }
        if (btnNext) {
            btnNext.addEventListener('click', function () {
                paginationState[tableId].currentPage++;
                updatePagination(tableId);
            });
        }

        updatePagination(tableId);
    }

    /* ─── Init ────────────────────────────────────────────────────────── */
    initSort('sales-table');
    initPagination('sales-table', 20);
})();
