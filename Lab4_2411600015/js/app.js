/**
 * app.js
 * ------------------------------------------------------------------
 * DOM handlers for the GSCSDA Student Portal Dashboard (Lab 4, Part
 * 5) — "My Courses" section. Wires dataManager (state) and
 * dashboardCharts (visualization) to the page: filter controls, live
 * search, at-risk course alerts, CSV export, and a simulated
 * real-time gradebook feed.
 * ------------------------------------------------------------------
 */

document.addEventListener('DOMContentLoaded', () => {
    // Guard: only run on pages that actually have the courses section.
    const tableBody = document.getElementById('courseTableBody');
    if (!tableBody || typeof dataManager === 'undefined') return;

    const els = {
        categoryFilter: document.getElementById('categoryFilter'),
        statusFilter: document.getElementById('statusFilter'),
        gradeMin: document.getElementById('gradeMinInput'),
        gradeMax: document.getElementById('gradeMaxInput'),
        resetBtn: document.getElementById('resetFiltersBtn'),
        searchInput: document.getElementById('searchInput'),
        exportBtn: document.getElementById('exportCsvBtn'),
        tableBody,
        resultsCount: document.getElementById('resultsCount'),
        alertBox: document.getElementById('courseAlert'),
        alertText: document.getElementById('courseAlertText'),
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
        dataManager.getCategories().forEach(category => {
            const opt = document.createElement('option');
            opt.value = category;
            opt.textContent = category;
            els.categoryFilter.appendChild(opt);
        });
    }

    function attachListeners() {
        els.categoryFilter.addEventListener('change', (e) => {
            dataManager.filterByCategory(e.target.value);
            refreshView();
        });

        els.statusFilter.addEventListener('change', (e) => {
            dataManager.filterByStatus(e.target.value);
            refreshView();
        });

        let gradeDebounce;
        function handleGradeChange() {
            clearTimeout(gradeDebounce);
            gradeDebounce = setTimeout(() => {
                dataManager.filterByGradeRange(els.gradeMin.value, els.gradeMax.value);
                refreshView();
            }, 250);
        }
        els.gradeMin.addEventListener('input', handleGradeChange);
        els.gradeMax.addEventListener('input', handleGradeChange);

        els.resetBtn.addEventListener('click', () => {
            dataManager.resetFilters();
            currentQuery = '';
            els.categoryFilter.value = 'all';
            els.statusFilter.value = 'all';
            els.gradeMin.value = '';
            els.gradeMax.value = '';
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
                showToast('Nothing to export', 'No courses match the current filters.', 'warning');
                return;
            }
            const csv = dataManager.exportToCSV(filtered);
            const stamp = new Date().toISOString().slice(0, 10);
            dataManager.downloadCSV(csv, `my_grades_${stamp}.csv`);
            showToast('Export complete', `${filtered.length} course record(s) exported to CSV.`, 'success');
        });
    }

    // ---- Rendering --------------------------------------------------

    function renderLoadingRow() {
        els.tableBody.innerHTML = `
            <tr><td colspan="8" class="text-center text-muted py-4">Loading your courses...</td></tr>
        `;
    }

    function statusBadgeClass(status) {
        if (status === 'Passing') return 'bg-success';
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
     * Rebuilds the course table body from scratch using DOM methods:
     * innerHTML reset to clear, then createElement + appendChild per
     * row, so individual rows can carry data attributes and per-row
     * classes for the status highlight and the real-time "flash"
     * animation.
     */
    function renderTable(list, query) {
        els.tableBody.innerHTML = '';

        if (!list.length) {
            const emptyRow = document.createElement('tr');
            const cell = document.createElement('td');
            cell.colSpan = 8;
            cell.className = 'text-center text-muted py-4';
            cell.textContent = 'No courses match the current filters.';
            emptyRow.appendChild(cell);
            els.tableBody.appendChild(emptyRow);
        } else {
            list.forEach(c => {
                const row = document.createElement('tr');
                if (c.status === 'At Risk') row.classList.add('row-at-risk');
                if (c.status === 'Failing') row.classList.add('row-failing');
                row.dataset.courseCode = c.courseCode;

                row.innerHTML = `
                    <td>${highlight(c.courseCode, query)}</td>
                    <td>${highlight(c.courseName, query)}</td>
                    <td>${escapeHtml(c.category)}</td>
                    <td>${c.units}</td>
                    <td>${escapeHtml(c.instructor)}</td>
                    <td>${c.grade.toFixed(2)}</td>
                    <td>${c.attendanceRate}%</td>
                    <td><span class="badge status-badge ${statusBadgeClass(c.status)}">${c.status}</span></td>
                `;
                els.tableBody.appendChild(row);
            });
        }

        els.resultsCount.textContent = `Showing ${list.length} of ${dataManager.getCourses().length} courses`;
    }

    /** At-risk banner always reflects your entire course load, not just the current filter. */
    function updateCourseAlert() {
        const atRisk = dataManager.getAtRiskCourses();
        if (atRisk.length === 0) {
            els.alertBox.classList.add('d-none');
            return;
        }
        const names = atRisk.slice(0, 3).map(c => c.courseName).join(', ');
        const extra = atRisk.length > 3 ? ` and ${atRisk.length - 3} more` : '';
        els.alertText.textContent = `You're At Risk or Failing in ${atRisk.length} course(s): ${names}${extra}.`;
        els.alertBox.classList.remove('d-none');
    }

    /** Re-pulls the filtered list from dataManager and repaints table + alert + charts. */
    function refreshView() {
        const filtered = dataManager.applyFilters();
        renderTable(filtered, currentQuery);
        updateCourseAlert();
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

    // ---- Part 5 Step 5: simulated real-time gradebook updates ---------------------

    function startRealtimeSimulation() {
        setInterval(() => {
            const updated = dataManager.simulateUpdate();
            if (!updated) return;

            refreshView();

            const row = els.tableBody.querySelector(`tr[data-course-code="${CSS.escape(updated.courseCode)}"]`);
            if (row) {
                row.classList.add('row-flash');
                setTimeout(() => row.classList.remove('row-flash'), 1200);
            }

            showToast(
                'Grade update',
                `Your grade in ${updated.courseName} was just updated to ${updated.grade.toFixed(2)} (${updated.status}).`,
                'info'
            );
        }, 8000); // every 8 seconds
    }
});
