/*
 * Live dashboard: polls the JSON endpoints and updates the page in place.
 * Module pattern: everything is private except start().
 */
const dashboardLive = (function () {

    const config = document.getElementById('live-config');
    let timer = null;
    let failures = 0;

    /* ---------- helpers ---------- */

    function el(id) {
        return document.getElementById(id);
    }

    // Escape text before it goes into innerHTML-built rows
    function esc(value) {
        const div = document.createElement('div');
        div.textContent = value == null ? '' : String(value);
        return div.innerHTML;
    }

    function fetchJson(url) {
        return fetch(url, {
            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            credentials: 'same-origin'
        }).then(function (response) {
            if (!response.ok) {
                throw new Error('HTTP ' + response.status);
            }

            return response.json();
        });
    }

    // Counts from the current number to the new one and flashes the card
    function animateNumber(id, target, suffix) {
        const node = el(id);

        if (!node) return;

        suffix = suffix || '';

        const current = parseFloat(node.textContent.replace(/[^0-9.\-]/g, '')) || 0;

        if (current === target) return;

        const decimals = String(target).indexOf('.') >= 0 ? 2 : 0;
        const duration = 600;
        const startTime = performance.now();

        node.classList.remove('stat-updated');
        void node.offsetWidth; // restart the CSS animation
        node.classList.add('stat-updated');

        function step(now) {
            const progress = Math.min((now - startTime) / duration, 1);
            const value = current + (target - current) * progress;

            node.textContent = value.toFixed(decimals) + suffix;

            if (progress < 1) {
                requestAnimationFrame(step);
            } else {
                node.textContent = target.toFixed(decimals) + suffix;
            }
        }

        requestAnimationFrame(step);
    }

    function setStatus(ok) {
        const status = el('live-status');

        if (!status) return;

        status.textContent = ok ? '● Live' : '● Connection problem, retrying…';
        status.className = ok ? 'text-success' : 'text-danger';
    }

    /* ---------- renderers ---------- */

    function renderStats(data) {
        animateNumber('stat1-value', data.gpa);
        animateNumber('stat2-value', data.total_courses);
        animateNumber('stat3-value', data.at_risk);
        animateNumber('stat4-value', data.avg_attendance, '%');

        // Notification bell
        const bell = el('bell-count');

        if (bell) {
            bell.textContent = data.unread_notifications;
            bell.classList.toggle('d-none', data.unread_notifications === 0);
        }

        // Keep the attendance chart in step with the ledger
        if (window.dashboardCharts && data.attendance_by_course) {
            dashboardCharts.renderAttendanceChart(data.attendance_by_course);
        }

        const stamp = el('last-updated');

        if (stamp) {
            stamp.textContent = new Date(data.updated_at).toLocaleTimeString();
        }
    }

    function renderLowAllowance(courses) {
        const body = el('low-allowance-body');
        const empty = el('low-allowance-empty');

        if (!body) return;

        empty.classList.toggle('d-none', courses.length > 0);

        body.innerHTML = courses.map(function (c) {
            const badge = c.status_color === 'warning' ? 'bg-warning text-dark' : 'bg-' + c.status_color;

            return '<tr>' +
                '<td><a href="' + esc(c.url) + '">' + esc(c.course_code) + '</a>' +
                '<div class="small text-muted">' + esc(c.course_name) + '</div></td>' +
                '<td class="text-end">' + esc(c.absences_remaining) + ' / ' + esc(c.absences_allowed) + '</td>' +
                '<td><span class="badge ' + badge + '">' + esc(c.allowance_status) + '</span></td>' +
                '</tr>';
        }).join('');
    }

    function renderRecent(transactions) {
        const body = el('recent-body');
        const empty = el('recent-empty');

        if (!body) return;

        empty.classList.toggle('d-none', transactions.length > 0);

        body.innerHTML = transactions.map(function (t) {
            const badge = t.badge === 'info' ? 'bg-info text-dark' : 'bg-' + t.badge;
            const qtyClass = t.quantity > 0 ? 'text-success' : 'text-danger';

            return '<tr>' +
                '<td class="text-muted small">' + esc(t.created_at_human) + '</td>' +
                '<td><a href="' + esc(t.course.url) + '">' + esc(t.course.code) + '</a></td>' +
                '<td><a href="' + esc(t.url) + '"><span class="badge ' + badge + '">' + esc(t.type_label) + '</span></a></td>' +
                '<td class="text-end fw-bold ' + qtyClass + '">' + esc(t.signed_quantity) + '</td>' +
                '<td>' + esc(t.user) + '</td>' +
                '</tr>';
        }).join('');
    }

    /* ---------- polling ---------- */

    function refresh() {
        // Don't poll a tab nobody is looking at
        if (document.hidden) return Promise.resolve();

        return Promise.all([
            fetchJson(config.dataset.statsUrl),
            fetchJson(config.dataset.lowUrl),
            fetchJson(config.dataset.recentUrl)
        ]).then(function (results) {
            failures = 0;
            renderStats(results[0].data);
            renderLowAllowance(results[1].data);
            renderRecent(results[2].data);
            setStatus(true);
        }).catch(function (error) {
            failures++;
            setStatus(false);
            console.warn('Dashboard refresh failed:', error);

            // A 401/419 means the session expired: stop hammering the server
            if (failures >= 5) {
                clearInterval(timer);
                setStatus(false);
            }
        });
    }

    function start(intervalMs) {
        if (!config) return;

        refresh();
        timer = setInterval(refresh, intervalMs);
    }

    return { start: start };
})();
