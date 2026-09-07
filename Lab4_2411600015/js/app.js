/**
 * app.js
 * ------------------------------------------------------------------
 * DOM handlers for the Student Management Dashboard (Lab 4, Part 5).
 * Wires dataManager (state) and dashboardCharts (visualization) to
 * the actual page: filter controls, live search, low-stock alerts,
 * CSV export, and a simulated real-time feed.
 * ------------------------------------------------------------------
 */

document.addEventListener('DOMContentLoaded', () => {
    // Guard: only run on pages that actually have the roster section.
    const tableBody = document.getElementById('studentTableBody');
    if (!tableBody || typeof dataManager === 'undefined') return;

    const els = {
        categoryFilter: document.getElementById('categoryFilter'),
        statusFilter: document.getElementById('statusFilter'),
        priceMin: document.getElementById('priceMinInput'),
        priceMax: document.getElementById('priceMaxInput'),
        resetBtn: document.getElementById('resetFiltersBtn'),
        searchInput: document.getElementById('searchInput'),
        exportBtn: document.getElementById('exportCsvBtn'),
        tableBody,
        resultsCount: document.getElementById('resultsCount'),
        alertBox: document.getElementById('lowStockAlert'),
        alertText: document.getElementById('lowStockAlertText'),
        toastContainer: document.getElementById('toastContainer')
    };

    let currentQuery = '';

    // ---- Boot -----------------------------------------------------
    renderLoadingRow();

    dataManager.initializeData().then(() => {
        populateCategoryDropdown();
        refreshView();
        attachListeners();
        startRealtimeSimulation();
    });

    // ---- Setup ------------------------------------------------------

    function populateCategoryDropdown() {
        dataManager.getCategories().forEach(cat => {
            const opt = document.createElement('option');
            opt.value = cat;
            opt.textContent = cat;
            els.categoryFilter.appendChild(opt);
        });
    }

    function attachListeners() {
        els.categoryFilter.addEventListener('change', (e) => {
            dataManager.filterByCategory(e.target.value);
            refreshView();
        });

        els.statusFilter.addEventListener('change', (e) => {
            dataManager.filterByStockStatus(e.target.value);
            refreshView();
        });

        let priceDebounce;
        function handlePriceChange() {
            clearTimeout(priceDebounce);
            priceDebounce = setTimeout(() => {
                dataManager.filterByPriceRange(els.priceMin.value, els.priceMax.value);
                refreshView();
            }, 250);
        }
        els.priceMin.addEventListener('input', handlePriceChange);
        els.priceMax.addEventListener('input', handlePriceChange);

        els.resetBtn.addEventListener('click', () => {
            dataManager.resetFilters();
            currentQuery = '';
            els.categoryFilter.value = 'all';
            els.statusFilter.value = 'all';
            els.priceMin.value = '';
            els.priceMax.value = '';
            els.searchInput.value = '';
            refreshView();
        });

        let searchDebounce;
        els.searchInput.addEventListener('input', (e) => {
            currentQuery = e.target.value;
            clearTimeout(searchDebounce);
            searchDebounce = setTimeout(() => {
                dataManager.updateSearchResults(currentQuery);
                refreshView();
            }, 150);
        });

        els.exportBtn.addEventListener('click', () => {
            const filtered = dataManager.applyFilters();
            if (!filtered.length) {
                showToast('Nothing to export', 'No students match the current filters.', 'warning');
                return;
            }
            const csv = dataManager.exportToCSV(filtered);
            const stamp = new Date().toISOString().slice(0, 10);
            dataManager.downloadCSV(csv, `student_roster_${stamp}.csv`);
            showToast('Export complete', `${filtered.length} student record(s) exported to CSV.`, 'success');
        });
    }

    // ---- Rendering --------------------------------------------------

    function renderLoadingRow() {
        els.tableBody.innerHTML = `
            <tr><td colspan="8" class="text-center text-muted py-4">Loading student roster...</td></tr>
        `;
    }

    function statusBadgeClass(status) {
        if (status === 'Good Standing') return 'bg-success';
        if (status === 'At Risk') return 'bg-warning text-dark';
        return 'bg-danger';
    }

    function escapeHtml(str) {
        const div = document.createElement('div');
        div.textContent = String(str);
        return div.innerHTML;
    }

    function escapeRegExp(str) {
        return str.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
    }

    /** Wraps the matching substring of `text` in <mark> for live search highlighting. */
    function highlight(text, query) {
        const safe = escapeHtml(text);
        if (!query) return safe;
        const re = new RegExp(`(${escapeRegExp(query)})`, 'ig');
        return safe.replace(re, '<mark>$1</mark>');
    }

    /**
     * Rebuilds the student table body from scratch using DOM methods:
     * innerHTML reset (removeChild-equivalent clear), then createElement
     * + appendChild per row. Kept as explicit DOM construction (rather
     * than one big innerHTML string) so individual rows can carry
     * data attributes and per-row classes for the low-stock highlight
     * and the real-time "flash" animation.
     */
    function renderTable(list, query) {
        els.tableBody.innerHTML = '';

        if (!list.length) {
            const emptyRow = document.createElement('tr');
            const cell = document.createElement('td');
            cell.colSpan = 8;
            cell.className = 'text-center text-muted py-4';
            cell.textContent = 'No students match the current filters.';
            emptyRow.appendChild(cell);
            els.tableBody.appendChild(emptyRow);
        } else {
            list.forEach(s => {
                const row = document.createElement('tr');
                if (s.stockStatus === 'At Risk') row.classList.add('row-at-risk');
                if (s.stockStatus === 'Probation') row.classList.add('row-probation');
                row.dataset.studentId = s.studentId;

                row.innerHTML = `
                    <td>${highlight(s.studentId, query)}</td>
                    <td>${highlight(s.name, query)}</td>
                    <td>${escapeHtml(s.category)}</td>
                    <td>${s.yearLevel}</td>
                    <td>${s.quantity}</td>
                    <td>${s.unitPrice.toFixed(2)}</td>
                    <td>${s.attendanceRate}%</td>
                    <td><span class="badge status-badge ${statusBadgeClass(s.stockStatus)}">${s.stockStatus}</span></td>
                `;
                els.tableBody.appendChild(row);
            });
        }

        els.resultsCount.textContent = `Showing ${list.length} of ${dataManager.getProducts().length} students`;
    }

    /** Low-stock (at-risk) banner always reflects the whole roster, not just the current filter. */
    function updateLowStockAlert() {
        const atRisk = dataManager.getLowStockProducts();
        if (atRisk.length === 0) {
            els.alertBox.classList.add('d-none');
            return;
        }
        const names = atRisk.slice(0, 3).map(s => s.name).join(', ');
        const extra = atRisk.length > 3 ? ` and ${atRisk.length - 3} more` : '';
        els.alertText.textContent = `${atRisk.length} student(s) are At Risk or on Probation: ${names}${extra}.`;
        els.alertBox.classList.remove('d-none');
    }

    /** Re-pulls the filtered list from dataManager and repaints table + alert + charts. */
    function refreshView() {
        const filtered = dataManager.applyFilters();
        renderTable(filtered, currentQuery);
        updateLowStockAlert();
        if (typeof dashboardCharts !== 'undefined') {
            dashboardCharts.renderAll(filtered);
        }
    }

    // ---- Notifications ------------------------------------------------

    function showToast(title, message, variant = 'primary') {
        if (!els.toastContainer || typeof bootstrap === 'undefined') return;

        const toastEl = document.createElement('div');
        toastEl.className = `toast align-items-center text-bg-${variant} border-0`;
        toastEl.setAttribute('role', 'alert');
        toastEl.setAttribute('aria-live', 'assertive');
        toastEl.setAttribute('aria-atomic', 'true');
        toastEl.innerHTML = `
            <div class="d-flex">
                <div class="toast-body"><strong>${escapeHtml(title)}:</strong> ${escapeHtml(message)}</div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
            </div>
        `;
        els.toastContainer.appendChild(toastEl);
        const toast = new bootstrap.Toast(toastEl, { delay: 4000 });
        toast.show();
        toastEl.addEventListener('hidden.bs.toast', () => toastEl.remove());
    }

    // ---- Part 5 Step 5: simulated real-time updates ---------------------

    function startRealtimeSimulation() {
        setInterval(() => {
            const updated = dataManager.simulateUpdate();
            if (!updated) return;

            refreshView();

            const row = els.tableBody.querySelector(`tr[data-student-id="${CSS.escape(updated.studentId)}"]`);
            if (row) {
                row.classList.add('row-flash');
                setTimeout(() => row.classList.remove('row-flash'), 1200);
            }

            showToast(
                'Live update',
                `${updated.name}'s GPA changed to ${updated.unitPrice.toFixed(2)} (${updated.stockStatus}).`,
                'info'
            );
        }, 8000); // every 8 seconds
    }
});
