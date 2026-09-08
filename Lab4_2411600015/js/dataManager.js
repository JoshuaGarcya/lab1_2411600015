/**
 * dataManager.js
 * ------------------------------------------------------------------
 * Central data management module for the GSCSDA Student Portal
 * Dashboard (Laboratory Exercise 4).
 *
 * NAMING NOTE:
 * The lab specification is written around a generic "inventory"
 * dashboard (products, SKUs, stock status, price). This module keeps
 * those exact function names so the code satisfies the spec's required
 * function list, while the *data itself* is a Student Management theme.
 * Read the vocabulary as:
 *
 *   "product"      -> a student record
 *   category        -> academic program (e.g. "BS Computer Science")
 *   unitPrice        -> GPA (0.00 - 4.00 scale)
 *   quantity          -> enrolled units/credits
 *   totalValue         -> "quality points" = units * GPA (same formula
 *                         shape as quantity * unitPrice)
 *   stockStatus         -> academic standing: "Good Standing" / "At Risk" / "Probation"
 *   reorderLevel          -> the GPA threshold that triggers an at-risk flag
 *
 * Exposes a single global `dataManager` object (module pattern / IIFE)
 * so state lives in one place instead of scattered globals.
 * ------------------------------------------------------------------
 */

const dataManager = (function () {
    'use strict';

    // ---------------------------------------------------------------
    // Private state
    // ---------------------------------------------------------------
    let students = [];

    let activeFilters = {
        category: 'all',
        stockStatus: 'all',
        priceMin: null,
        priceMax: null,
        searchQuery: ''
    };

    // Academic standing thresholds (GPA on a 4.0 scale, higher = better)
    const GPA_GOOD_STANDING = 3.0; // >= this -> Good Standing
    const GPA_AT_RISK = 2.0;       // >= this and < GOOD_STANDING -> At Risk
    // below GPA_AT_RISK -> Probation

    // ---------------------------------------------------------------
    // Sample "backend" data.
    // In Part 7 this can be swapped for a fetch() call to api.php
    // without changing any function signature below.
    // ---------------------------------------------------------------
    const SAMPLE_STUDENTS = [
        { id: 1, studentId: '2411600015', name: 'Josh Ian Pacalang', category: 'BS Information Technology', yearLevel: 3, quantity: 21, unitPrice: 3.75, attendanceRate: 96 },

    ];

    // ---------------------------------------------------------------
    // Helpers
    // ---------------------------------------------------------------

    function deriveStatus(unitPrice) {
        if (unitPrice >= GPA_GOOD_STANDING) return 'Good Standing';
        if (unitPrice >= GPA_AT_RISK) return 'At Risk';
        return 'Probation';
    }

    // Attaches computed fields (stockStatus, reorderLevel, totalValue)
    // to a raw student record.
    function withComputed(student) {
        return {
            ...student,
            stockStatus: deriveStatus(student.unitPrice),
            reorderLevel: GPA_GOOD_STANDING,
            totalValue: +(student.quantity * student.unitPrice).toFixed(2)
        };
    }

    // ---------------------------------------------------------------
    // Step 1: Initialization & basic getters
    // ---------------------------------------------------------------

    // Part 7: backend endpoint. If api.php isn't deployed/running (e.g.
    // the page was opened directly via file://, or XAMPP's Apache isn't
    // started), initializeData() falls back to the bundled sample data
    // below so the dashboard still works standalone.
    const API_ENDPOINT = 'api.php?action=list';
    const API_TIMEOUT_MS = 2000;

    /** Attempts to fetch the roster from api.php, with a short timeout. */
    function fetchFromApi() {
        const controller = new AbortController();
        const timeoutId = setTimeout(() => controller.abort(), API_TIMEOUT_MS);

        return fetch(API_ENDPOINT, { signal: controller.signal })
            .then(res => {
                clearTimeout(timeoutId);
                if (!res.ok) throw new Error(`API responded with status ${res.status}`);
                return res.json();
            })
            .then(data => {
                if (!Array.isArray(data)) throw new Error('Unexpected API response shape');
                return data;
            });
    }

    /**
     * Sets up the initial dataset. Tries api.php first (Part 7); if that
     * fails for any reason, falls back to the bundled sample data after
     * a short simulated delay, so the dashboard is never blocked on a
     * live backend. Either way returns a Promise resolving to the roster.
     */
    function initializeData() {
        return fetchFromApi()
            .then(apiStudents => {
                students = apiStudents.map(withComputed);
                return getProducts();
            })
            .catch(() => {
                console.warn('dataManager: api.php unreachable — using bundled sample data.');
                return new Promise((resolve) => {
                    setTimeout(() => {
                        students = SAMPLE_STUDENTS.map(withComputed);
                        resolve(getProducts());
                    }, 300);
                });
            });
    }

    /** Returns a shallow copy of the full current student list. */
    function getProducts() {
        return [...students];
    }

    /** Returns a single student by numeric id or studentId string. */
    function getProductById(id) {
        return students.find(s => s.id === id || s.studentId === id) || null;
    }

    /** Returns students belonging to a given program. */
    function getProductsByCategory(category) {
        return students.filter(s => s.category === category);
    }

    /** Returns students who are At Risk or on Probation. */
    function getLowStockProducts() {
        return students.filter(s => s.stockStatus !== 'Good Standing');
    }

    /**
     * Returns aggregate statistics. Pass a pre-filtered list (e.g. from
     * applyFilters()) to get stats scoped to the current view; omit it
     * to summarize the entire roster.
     */
    function getStockStatistics(list) {
        const source = list || students;
        const total = source.length;
        const totalValue = source.reduce((sum, s) => sum + s.totalValue, 0);
        const goodStanding = source.filter(s => s.stockStatus === 'Good Standing').length;
        const atRisk = source.filter(s => s.stockStatus === 'At Risk').length;
        const probation = source.filter(s => s.stockStatus === 'Probation').length;
        const avgGpa = total
            ? +(source.reduce((sum, s) => sum + s.unitPrice, 0) / total).toFixed(2)
            : 0;

        return {
            totalProducts: total,
            totalValue: +totalValue.toFixed(2),
            inStock: goodStanding,
            lowStock: atRisk,
            outOfStock: probation,
            averagePrice: avgGpa
        };
    }

    /**
     * Returns per-program totals (quantity, quality points, headcount).
     * Pass a pre-filtered list to scope the summary to the current view.
     */
    function getCategorySummary(list) {
        const source = list || students;
        const map = {};
        source.forEach(s => {
            if (!map[s.category]) {
                map[s.category] = {
                    category: s.category,
                    totalQuantity: 0,
                    totalValue: 0,
                    count: 0
                };
            }
            map[s.category].totalQuantity += s.quantity;
            map[s.category].totalValue += s.totalValue;
            map[s.category].count += 1;
        });
        return Object.values(map).map(c => ({ ...c, totalValue: +c.totalValue.toFixed(2) }));
    }

    /** Returns the distinct list of programs present in the data. */
    function getCategories() {
        return [...new Set(students.map(s => s.category))];
    }

    // ---------------------------------------------------------------
    // Step 2: Filtering & search
    // ---------------------------------------------------------------

    function filterByCategory(category) {
        activeFilters.category = category;
        return applyFilters();
    }

    function filterByStockStatus(status) {
        activeFilters.stockStatus = status;
        return applyFilters();
    }

    function filterByPriceRange(min, max) {
        activeFilters.priceMin = (min === '' || min == null) ? null : Number(min);
        activeFilters.priceMax = (max === '' || max == null) ? null : Number(max);
        return applyFilters();
    }

    /** Applies every active filter + the search query together. */
    function applyFilters() {
        return students.filter(s => {
            const matchCategory = activeFilters.category === 'all' || s.category === activeFilters.category;
            const matchStatus = activeFilters.stockStatus === 'all' || s.stockStatus === activeFilters.stockStatus;
            const matchMin = activeFilters.priceMin == null || s.unitPrice >= activeFilters.priceMin;
            const matchMax = activeFilters.priceMax == null || s.unitPrice <= activeFilters.priceMax;
            const q = activeFilters.searchQuery.trim().toLowerCase();
            const matchSearch = !q ||
                s.name.toLowerCase().includes(q) ||
                s.studentId.toLowerCase().includes(q);
            return matchCategory && matchStatus && matchMin && matchMax && matchSearch;
        });
    }

    function resetFilters() {
        activeFilters = { category: 'all', stockStatus: 'all', priceMin: null, priceMax: null, searchQuery: '' };
        return getProducts();
    }

    function searchProducts(query) {
        activeFilters.searchQuery = query || '';
        return applyFilters();
    }

    /** Convenience alias matching the spec's naming for the search handler. */
    function updateSearchResults(query) {
        return searchProducts(query);
    }

    function getActiveFilters() {
        return { ...activeFilters };
    }

    // ---------------------------------------------------------------
    // Step 3: CSV export
    // ---------------------------------------------------------------

    /** Converts a list of student records into a CSV string. */
    function exportToCSV(data) {
        const rows = (data && data.length) ? data : students;
        const headers = ['Student ID', 'Name', 'Program', 'Year Level', 'Units', 'GPA', 'Attendance %', 'Status'];

        const escape = (val) => `"${String(val).replace(/"/g, '""')}"`;

        const lines = rows.map(s => [
            s.studentId,
            escape(s.name),
            escape(s.category),
            s.yearLevel,
            s.quantity,
            s.unitPrice.toFixed(2),
            s.attendanceRate,
            s.stockStatus
        ].join(','));

        return [headers.join(','), ...lines].join('\r\n');
    }

    /** Triggers a browser download of the given CSV content. */
    function downloadCSV(csvContent, filename) {
        const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
        const url = URL.createObjectURL(blob);
        const link = document.createElement('a');
        link.setAttribute('href', url);
        link.setAttribute('download', filename || 'student_roster.csv');
        link.style.visibility = 'hidden';
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
        URL.revokeObjectURL(url);
    }

    // ---------------------------------------------------------------
    // Real-time simulation hook (wired up by app.js in Part 5)
    // ---------------------------------------------------------------

    /** Nudges one random student's GPA to mimic a live data feed. */
    function simulateUpdate() {
        if (!students.length) return null;
        const idx = Math.floor(Math.random() * students.length);
        const delta = +(Math.random() * 0.4 - 0.2).toFixed(2);
        let newGpa = +(students[idx].unitPrice + delta).toFixed(2);
        newGpa = Math.min(4.0, Math.max(0.5, newGpa));
        students[idx] = withComputed({ ...students[idx], unitPrice: newGpa });
        return students[idx];
    }

    // ---------------------------------------------------------------
    // Public API
    // ---------------------------------------------------------------
    return {
        initializeData,
        getProducts,
        getProductById,
        getProductsByCategory,
        getLowStockProducts,
        getStockStatistics,
        getCategorySummary,
        getCategories,
        filterByCategory,
        filterByStockStatus,
        filterByPriceRange,
        applyFilters,
        resetFilters,
        searchProducts,
        updateSearchResults,
        getActiveFilters,
        exportToCSV,
        downloadCSV,
        simulateUpdate
    };
})();
