const dashboardCharts = (function () {
    'use strict';

    let categoryChart = null;
    let statusChart = null;
    let topCoursesChart = null;

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
        animation: {
            duration: 500
        }
    };

    function renderCategoryChart(categorySummary) {
        const canvas = document.getElementById('categoryChart');

        if (!canvas) return;

        const labels = categorySummary.map(c => c.category);
        const values = categorySummary.map(c => Number(c.total_units));

        if (categoryChart) {
            categoryChart.destroy();
        }

        categoryChart = new Chart(canvas, {
            type: 'bar',

            data: {
                labels: labels,

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
                    legend: {
                        display: false
                    },

                    tooltip: {
                        callbacks: {
                            label: (ctx) =>
                                `${ctx.parsed.y} unit(s)`
                        }
                    }
                },

                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            precision: 0
                        }
                    },

                    x: {
                        ticks: {
                            autoSkip: false,
                            maxRotation: 30,
                            minRotation: 0
                        }
                    }
                }
            }
        });
    }

    function renderStatusChart(stats) {
        const canvas = document.getElementById('statusChart');

        if (!canvas) return;

        if (statusChart) {
            statusChart.destroy();
        }

        statusChart = new Chart(canvas, {
            type: 'doughnut',

            data: {
                labels: [
                    'Passing',
                    'At Risk',
                    'Failing'
                ],

                datasets: [{
                    data: [
                        stats.passing,
                        stats.atRisk,
                        stats.failing
                    ],

                    backgroundColor: [
                        COLORS.success,
                        COLORS.warning,
                        COLORS.danger
                    ],

                    hoverOffset: 8
                }]
            },

            options: {
                ...baseOptions,

                plugins: {
                    legend: {
                        position: 'bottom'
                    }
                },

                cutout: '60%'
            }
        });
    }

    function renderTopCoursesChart(courses) {
        const canvas = document.getElementById('topCoursesChart');

        if (!canvas) return;

        const top5 = [...courses]
            .sort((a, b) => b.grade - a.grade)
            .slice(0, 5);

        if (topCoursesChart) {
            topCoursesChart.destroy();
        }

        topCoursesChart = new Chart(canvas, {
            type: 'bar',

            data: {
                labels: top5.map(c => c.course_name),

                datasets: [{
                    label: 'Grade',

                    data: top5.map(c => Number(c.grade)),

                    backgroundColor: COLORS.secondary,
                    hoverBackgroundColor: COLORS.primary,
                    borderRadius: 6
                }]
            },

            options: {
                ...baseOptions,

                indexAxis: 'y',

                plugins: {
                    legend: {
                        display: false
                    }
                },

                scales: {
                    x: {
                        beginAtZero: true,
                        max: 4.0
                    }
                }
            }
        });
    }

    function renderAll(categorySummary, statusStats, courses) {
        renderCategoryChart(categorySummary);
        renderStatusChart(statusStats);
        renderTopCoursesChart(courses);
    }

    return {
        renderAll,
        renderCategoryChart,
        renderStatusChart,
        renderTopCoursesChart
    };

})();