/**
 * charts.js
 * ------------------------------------------------------------------
 * Chart.js configuration and rendering for the Student Management
 * Dashboard (Laboratory Exercise 4, Part 4).
 *
 * Three charts, each reading from dataManager:
 *   1. categoryValueChart -> Quality Points by Program   (bar)
 *   2. stockStatusChart   -> Academic Standing Distribution (doughnut)
 *   3. topProductsChart   -> Top 5 Students by GPA        (horizontal bar)
 *
 * Chart instances are kept in module state so they can be destroyed
 * and rebuilt whenever filters, search, or the real-time simulation
 * change the underlying data (Chart.js instances can't just have
 * their canvas re-drawn — the old instance must be destroyed first
 * or it leaks and stacks tooltips).
 * ------------------------------------------------------------------
 */

const dashboardCharts = (function () {
    'use strict';

    let categoryChart = null;
    let statusChart = null;
    let topChart = null;

    // Matches css/style.css theme variables
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
     * Step 2: Inventory (Quality Points) by Category/Program chart.
     * Bar chart: programs on the x-axis, total quality points on the y-axis.
     */
    function renderCategoryValueChart(categorySummary) {
        const canvas = document.getElementById('categoryValueChart');
        if (!canvas) return;

        const labels = categorySummary.map(c => c.category);
        const values = categorySummary.map(c => c.totalValue);

        if (categoryChart) categoryChart.destroy();
        categoryChart = new Chart(canvas, {
            type: 'bar',
            data: {
                labels,
                datasets: [{
                    label: 'Quality Points (Units × GPA)',
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
                            label: (ctx) => `${ctx.parsed.y.toFixed(2)} quality points`
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
     * Step 3: Stock Status / Academic Standing Distribution chart.
     * Doughnut chart with semantic colors (green/yellow/red).
     */
    function renderStockStatusChart(stats) {
        const canvas = document.getElementById('stockStatusChart');
        if (!canvas) return;

        if (statusChart) statusChart.destroy();
        statusChart = new Chart(canvas, {
            type: 'doughnut',
            data: {
                labels: ['Good Standing', 'At Risk', 'Probation'],
                datasets: [{
                    data: [stats.inStock, stats.lowStock, stats.outOfStock],
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
     * Additional chart (Step 5 / Part 4): Top 5 Students by GPA.
     * Horizontal bar chart — good for longer name labels.
     */
    function renderTopProductsChart(students) {
        const canvas = document.getElementById('topProductsChart');
        if (!canvas) return;

        const top5 = [...students]
            .sort((a, b) => b.unitPrice - a.unitPrice)
            .slice(0, 5);

        if (topChart) topChart.destroy();
        topChart = new Chart(canvas, {
            type: 'bar',
            data: {
                labels: top5.map(s => s.name),
                datasets: [{
                    label: 'GPA',
                    data: top5.map(s => s.unitPrice),
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
     * Re-renders all three charts. Pass the currently filtered student
     * list (from dataManager.applyFilters()) so the charts reflect
     * whatever the user has filtered/searched for; omit it to chart the
     * full roster. Called after initial load, after every filter/search
     * change, and after each real-time simulation tick.
     */
    function renderAll(list) {
        const source = list || dataManager.getProducts();
        renderCategoryValueChart(dataManager.getCategorySummary(source));
        renderStockStatusChart(dataManager.getStockStatistics(source));
        renderTopProductsChart(source);
    }

    return {
        renderAll,
        renderCategoryValueChart,
        renderStockStatusChart,
        renderTopProductsChart
    };
})();
