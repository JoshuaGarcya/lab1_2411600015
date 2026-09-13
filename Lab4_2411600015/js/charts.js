/**
 * charts.js
 * ------------------------------------------------------------------
 * Chart.js configuration and rendering for the GSCSDA Student Portal
 * Dashboard (Laboratory Exercise 4, Part 4) — "My Courses" section.
 *
 * Three charts, each reading from dataManager and scoped to the
 * logged-in student's own enrolled courses:
 *   1. categoryChart -> Units by Course Category   (bar)
 *   2. statusChart    -> Course Status Distribution  (doughnut)
 *   3. topCoursesChart -> Top Courses by Grade        (horizontal bar)
 *
 * Chart instances are kept in module state so they can be destroyed
 * and rebuilt whenever filters, search, or the real-time simulation
 * change the underlying data.
 * ------------------------------------------------------------------
 */

const dashboardCharts = (function () {
    'use strict';

    let categoryChart = null;
    let statusChart = null;
    let topCoursesChart = null;

    // Matches css/style.css theme variables (blue theme)
    const COLORS = {
        primary: '#072AC8',
        secondary: '#1E96FC',
        accent: '#6B818C',
        success: '#28a745',
        warning: '#f3a712',
        danger: '#dc3545'
    };

    const baseOptions = {
        responsive: true,
        maintainAspectRatio: false,
        animation: { duration: 500 }
    };

    /**
     * Units by Course Category chart.
     * Bar chart: category (Major/Minor/Gen Ed/Elective) on the x-axis,
     * total enrolled units on the y-axis.
     */
    function renderCategoryChart(categorySummary) {
        const canvas = document.getElementById('categoryChart');
        if (!canvas) return;

        const labels = categorySummary.map(c => c.category);
        const values = categorySummary.map(c => c.totalUnits);

        if (categoryChart) categoryChart.destroy();
        categoryChart = new Chart(canvas, {
            type: 'bar',
            data: {
                labels,
                datasets: [{
                    label: 'Units',
                    data: values,
                    backgroundColor: COLORS.primary,
                    hoverBackgroundColor: COLORS.secondary,
                    borderRadius: 6,
                    maxBarThickness: 48
                }]
            },
            options: {
                ...baseOptions,
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        callbacks: {
                            label: (ctx) => `${ctx.parsed.y} unit(s)`
                        }
                    }
                },
                scales: {
                    y: { beginAtZero: true, ticks: { precision: 0 } },
                    x: { ticks: { autoSkip: false, maxRotation: 30, minRotation: 0 } }
                }
            }
        });
    }

    /**
     * Course Status Distribution chart.
     * Doughnut chart with semantic colors (green/yellow/red).
     */
    function renderStatusChart(stats) {
        const canvas = document.getElementById('statusChart');
        if (!canvas) return;

        if (statusChart) statusChart.destroy();
        statusChart = new Chart(canvas, {
            type: 'radar',
            data: {
                labels: ['Passing', 'At Risk', 'Failing'],
                datasets: [{
                    data: [stats.passing, stats.atRisk, stats.failing],
                    backgroundColor: [COLORS.success, COLORS.warning, COLORS.danger],
                    hoverOffset: 8
                }]
            },
            options: {
                ...baseOptions,
                plugins: {
                    legend: { position: 'bottom' }
                },
                cutout: '60%'
            }
        });
    }

    /**
     * Top Courses by Grade.
     * Horizontal bar chart — good for longer course-name labels.
     */
    function renderTopCoursesChart(courses) {
        const canvas = document.getElementById('topCoursesChart');
        if (!canvas) return;

        const top5 = [...courses]
            .sort((a, b) => b.grade - a.grade)
            .slice(0, 5);

        if (topCoursesChart) topCoursesChart.destroy();
        topCoursesChart = new Chart(canvas, {
            type: 'bar',
            data: {
                labels: top5.map(c => c.courseName),
                datasets: [{
                    label: 'Grade',
                    data: top5.map(c => c.grade),
                    backgroundColor: COLORS.secondary,
                    hoverBackgroundColor: COLORS.primary,
                    borderRadius: 6
                }]
            },
            options: {
                ...baseOptions,
                indexAxis: 'y',
                plugins: { legend: { display: false } },
                scales: {
                    x: { beginAtZero: true, max: 4.0 }
                }
            }
        });
    }

    /**
     * Re-renders all three charts. Pass the currently filtered course
     * list (from dataManager.applyFilters()) so the charts update in
     * sync with the table and search; omit it to chart the full load.
     */
    function renderAll(list) {
        const source = list || dataManager.getCourses();
        renderCategoryChart(dataManager.getCategorySummary(source));
        renderStatusChart(dataManager.getGradeStatistics(source));
        renderTopCoursesChart(source);
    }

    return {
        renderAll,
        renderCategoryChart,
        renderStatusChart,
        renderTopCoursesChart
    };
})();
