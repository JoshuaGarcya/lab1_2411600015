/**
 * dataManager.js
 * ------------------------------------------------------------------
 * Central data management module for the GSCSDA Student Portal
 * Dashboard (Laboratory Exercise 4) — "My Courses" section.
 *
 * IMPORTANT DESIGN NOTE:
 * The first draft of this dashboard let the logged-in user browse a
 * table of *other* students (their programs, GPAs, standings). That
 * broke the Student Portal theme — a portal is supposed to show the
 * logged-in student their own information, not a roster of everyone
 * else's. This version replaces that roster with the logged-in
 * student's own enrolled courses for the current term: their grade,
 * units, category, and standing *per course*, not per classmate.
 *
 * Data model per course record:
 *   courseCode, courseName, category, units, grade, instructor, attendanceRate
 * Computed on load:
 *   status         ("Passing" / "At Risk" / "Failing")
 *   gradeThreshold  (the grade that separates Passing from At Risk)
 *   qualityPoints    (units * grade)
 *
 * Module pattern (IIFE) keeps all state private and exposes a single
 * global `dataManager` object.
 * ------------------------------------------------------------------
 */

const dataManager = (function () {
    'use strict';

    // ---------------------------------------------------------------
    // Private state
    // ---------------------------------------------------------------
    let courses = [];

    let activeFilters = {
        category: 'all',
        status: 'all',
        gradeMin: null,
        gradeMax: null,
        searchQuery: ''
    };

    // Standing thresholds (grade on a 4.0 scale, higher = better —
    // matches the GPA stat card already on the dashboard)
    const GRADE_PASSING = 3.0; // >= this -> Passing
    const GRADE_AT_RISK = 2.0; // >= this and < PASSING -> At Risk
    // below GRADE_AT_RISK -> Failing

    // ---------------------------------------------------------------
    // Sample fallback data (this term's enrolled courses), used only
    // if api.php is unreachable.
    // ---------------------------------------------------------------
    const SAMPLE_COURSES = [
        { id: 1, courseCode: 'CS201', courseName: 'Data Structures and Algorithms', category: 'Major', units: 3, grade: 3.75, instructor: 'Prof. Santos', attendanceRate: 96 },
        { id: 2, courseCode: 'CS210', courseName: 'Database Management Systems', category: 'Major', units: 3, grade: 1.85, instructor: 'Prof. Reyes', attendanceRate: 78 },
        { id: 3, courseCode: 'CS220', courseName: 'Object-Oriented Programming', category: 'Major', units: 3, grade: 3.20, instructor: 'Prof. Cruz', attendanceRate: 91 },
        { id: 4, courseCode: 'MATH101', courseName: 'Calculus I', category: 'Minor', units: 3, grade: 2.40, instructor: 'Prof. Bautista', attendanceRate: 85 },
        { id: 5, courseCode: 'ENG101', courseName: 'Communication Arts', category: 'Gen Ed', units: 3, grade: 1.60, instructor: 'Prof. Fernandez', attendanceRate: 70 },
        { id: 6, courseCode: 'PE101', courseName: 'Physical Education', category: 'Gen Ed', units: 2, grade: 3.90, instructor: 'Prof. Torres', attendanceRate: 98 },
        { id: 7, courseCode: 'HIST101', courseName: 'Philippine History', category: 'Gen Ed', units: 3, grade: 2.95, instructor: 'Prof. Ramos', attendanceRate: 88 },
        { id: 8, courseCode: 'CS230', courseName: 'Web Development', category: 'Elective', units: 3, grade: 3.55, instructor: 'Prof. Mendoza', attendanceRate: 94 },
        { id: 9, courseCode: 'STAT101', courseName: 'Statistics', category: 'Minor', units: 3, grade: 3.10, instructor: 'Prof. Aquino', attendanceRate: 90 },
        { id: 10, courseCode: 'CS240', courseName: 'Software Engineering', category: 'Major', units: 3, grade: 2.65, instructor: 'Prof. Garcia', attendanceRate: 82 },
    ];

    // ---------------------------------------------------------------
    // Helpers
    // ---------------------------------------------------------------

    function deriveStatus(grade) {
        if (grade >= GRADE_PASSING) return 'Passing';
        if (grade >= GRADE_AT_RISK) return 'At Risk';
        return 'Failing';
    }

    // Attaches computed fields (status, gradeThreshold, qualityPoints)
    function withComputed(course) {
        return {
            ...course,
            status: deriveStatus(course.grade),
            gradeThreshold: GRADE_PASSING,
            qualityPoints: +(course.units * course.grade).toFixed(2)
        };
    }

    // ---------------------------------------------------------------
    // Part 7: backend endpoint. If api.php isn't deployed/running
    // (e.g. opened via file://, or XAMPP's Apache isn't started),
    // initializeData() falls back to SAMPLE_COURSES so the dashboard
    // still works standalone.
    // ---------------------------------------------------------------
    const API_ENDPOINT = 'api.php?action=list';
    const API_TIMEOUT_MS = 2000;

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
     * Sets up the initial dataset. Tries api.php first; falls back to
     * bundled sample data after a short simulated delay if the API is
     * unreachable. Always returns a Promise resolving to the course list.
     */
    function initializeData() {
        return fetchFromApi()
            .then(apiCourses => {
                courses = apiCourses.map(withComputed);
                return getCourses();
            })
            .catch(() => {
                console.warn('dataManager: api.php unreachable — using bundled sample data.');
                return new Promise((resolve) => {
                    setTimeout(() => {
                        courses = SAMPLE_COURSES.map(withComputed);
                        resolve(getCourses());
                    }, 300);
                });
            });
    }

    // ---------------------------------------------------------------
    // Getters
    // ---------------------------------------------------------------

    /** Returns a shallow copy of the full list of enrolled courses. */
    function getCourses() {
        return [...courses];
    }

    /** Returns a single course by numeric id or course code string. */
    function getCourseById(id) {
        return courses.find(c => c.id === id || c.courseCode === id) || null;
    }

    /** Returns courses belonging to a given category. */
    function getCoursesByCategory(category) {
        return courses.filter(c => c.category === category);
    }

    /** Returns courses that are At Risk or Failing. */
    function getAtRiskCourses() {
        return courses.filter(c => c.status !== 'Passing');
    }

    /**
     * Returns aggregate grade statistics. Pass a pre-filtered list
     * (e.g. from applyFilters()) to scope stats to the current view;
     * omit it to summarize the full course load.
     */
    function getGradeStatistics(list) {
        const source = list || courses;
        const total = source.length;
        const totalQualityPoints = source.reduce((sum, c) => sum + c.qualityPoints, 0);
        const passing = source.filter(c => c.status === 'Passing').length;
        const atRisk = source.filter(c => c.status === 'At Risk').length;
        const failing = source.filter(c => c.status === 'Failing').length;
        const totalUnits = source.reduce((sum, c) => sum + c.units, 0);
        const averageGrade = totalUnits
            ? +(totalQualityPoints / totalUnits).toFixed(2)
            : 0;

        return {
            totalCourses: total,
            totalUnits,
            totalQualityPoints: +totalQualityPoints.toFixed(2),
            passing,
            atRisk,
            failing,
            averageGrade
        };
    }

    /**
     * Returns per-category totals (units, quality points, course count).
     * Pass a pre-filtered list to scope the summary to the current view.
     */
    function getCategorySummary(list) {
        const source = list || courses;
        const map = {};
        source.forEach(c => {
            if (!map[c.category]) {
                map[c.category] = {
                    category: c.category,
                    totalUnits: 0,
                    totalQualityPoints: 0,
                    count: 0
                };
            }
            map[c.category].totalUnits += c.units;
            map[c.category].totalQualityPoints += c.qualityPoints;
            map[c.category].count += 1;
        });
        return Object.values(map).map(cat => ({ ...cat, totalQualityPoints: +cat.totalQualityPoints.toFixed(2) }));
    }

    /** Returns the distinct list of categories present in the data. */
    function getCategories() {
        return [...new Set(courses.map(c => c.category))];
    }

    // ---------------------------------------------------------------
    // Filtering & search
    // ---------------------------------------------------------------

    function filterByCategory(category) {
        activeFilters.category = category;
        return applyFilters();
    }

    function filterByStatus(status) {
        activeFilters.status = status;
        return applyFilters();
    }

    function filterByGradeRange(min, max) {
        activeFilters.gradeMin = (min === '' || min == null) ? null : Number(min);
        activeFilters.gradeMax = (max === '' || max == null) ? null : Number(max);
        return applyFilters();
    }

    /** Applies every active filter + the search query together. */
    function applyFilters() {
        return courses.filter(c => {
            const matchCategory = activeFilters.category === 'all' || c.category === activeFilters.category;
            const matchStatus = activeFilters.status === 'all' || c.status === activeFilters.status;
            const matchMin = activeFilters.gradeMin == null || c.grade >= activeFilters.gradeMin;
            const matchMax = activeFilters.gradeMax == null || c.grade <= activeFilters.gradeMax;
            const q = activeFilters.searchQuery.trim().toLowerCase();
            const matchSearch = !q ||
                c.courseName.toLowerCase().includes(q) ||
                c.courseCode.toLowerCase().includes(q);
            return matchCategory && matchStatus && matchMin && matchMax && matchSearch;
        });
    }

    function resetFilters() {
        activeFilters = { category: 'all', status: 'all', gradeMin: null, gradeMax: null, searchQuery: '' };
        return getCourses();
    }

    function searchCourses(query) {
        activeFilters.searchQuery = query || '';
        return applyFilters();
    }

    /** Convenience alias for the search input handler. */
    function updateSearchResults(query) {
        return searchCourses(query);
    }

    function getActiveFilters() {
        return { ...activeFilters };
    }

    // ---------------------------------------------------------------
    // CSV export
    // ---------------------------------------------------------------

    /** Converts a list of course records into a CSV string. */
    function exportToCSV(data) {
        const rows = (data && data.length) ? data : courses;
        const headers = ['Course Code', 'Course Name', 'Category', 'Units', 'Instructor', 'Grade', 'Attendance %', 'Status'];

        const escape = (val) => `"${String(val).replace(/"/g, '""')}"`;

        const lines = rows.map(c => [
            c.courseCode,
            escape(c.courseName),
            escape(c.category),
            c.units,
            escape(c.instructor),
            c.grade.toFixed(2),
            c.attendanceRate,
            c.status
        ].join(','));

        return [headers.join(','), ...lines].join('\r\n');
    }

    /** Triggers a browser download of the given CSV content. */
    function downloadCSV(csvContent, filename) {
        const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
        const url = URL.createObjectURL(blob);
        const link = document.createElement('a');
        link.setAttribute('href', url);
        link.setAttribute('download', filename || 'my_grades.csv');
        link.style.visibility = 'hidden';
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
        URL.revokeObjectURL(url);
    }

    // ---------------------------------------------------------------
    // Real-time simulation hook (wired up by app.js in Part 5)
    // ---------------------------------------------------------------

    /** Nudges one random course's grade to mimic a live gradebook update. */
    function simulateUpdate() {
        if (!courses.length) return null;
        const idx = Math.floor(Math.random() * courses.length);
        const delta = +(Math.random() * 0.4 - 0.2).toFixed(2);
        let newGrade = +(courses[idx].grade + delta).toFixed(2);
        newGrade = Math.min(4.0, Math.max(0.5, newGrade));
        courses[idx] = withComputed({ ...courses[idx], grade: newGrade });
        return courses[idx];
    }

    // ---------------------------------------------------------------
    // Public API
    // ---------------------------------------------------------------
    return {
        initializeData,
        getCourses,
        getCourseById,
        getCoursesByCategory,
        getAtRiskCourses,
        getGradeStatistics,
        getCategorySummary,
        getCategories,
        filterByCategory,
        filterByStatus,
        filterByGradeRange,
        applyFilters,
        resetFilters,
        searchCourses,
        updateSearchResults,
        getActiveFilters,
        exportToCSV,
        downloadCSV,
        simulateUpdate
    };
})();
