document.addEventListener('DOMContentLoaded', function() {
    const container = document.getElementById('dashboardContainer');
    if (!container) return;

    const apiUrl = container.getAttribute('data-api-url');
    const currencyFmt = new Intl.NumberFormat('en-US', { style: 'currency', currency: 'PHP' });
    
    let chartZone = null;
    let chartCategory = null;
    let chartBranch = null;

    // Premium Corporate Red & Slate Palette
    const themeColors = [
        '#dc2626', // Brand Red
        '#1e293b', // Deep Slate
        '#ef4444', // Bright Red
        '#64748b', // Slate Gray
        '#991b1b', // Dark Crimson
        '#94a3b8', // Light Slate
        '#7f1d1d'  // Very Dark Red
    ];

    function fetchDashboardData() {
        ['loader-zone', 'loader-category', 'loader-branch'].forEach(id => 
            document.getElementById(id).classList.remove('hidden')
        );

        fetch(apiUrl)
            .then(res => res.json())
            .then(data => {
                if(data.success) {
                    renderZoneChart(data.zones);
                    renderCategoryChart(data.categories);
                    renderBranchChart(data.branches);
                } else {
                    console.error("Dashboard Error:", data.error);
                }
            })
            .catch(err => console.error("Network Error:", err))
            .finally(() => {
                ['loader-zone', 'loader-category', 'loader-branch'].forEach(id => 
                    document.getElementById(id).classList.add('hidden')
                );
            });
    }

    function renderZoneChart(data) {
        const ctx = document.getElementById('zoneChart').getContext('2d');
        if (chartZone) chartZone.destroy();

        const labels = data.map(item => item.label);
        const costValues = data.map(item => parseFloat(item.total_cost));
        const bookValues = data.map(item => parseFloat(item.total_book_value));

        chartZone = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: labels,
                datasets: [
                    {
                        label: 'Acquisition Cost',
                        data: costValues,
                        backgroundColor: '#cbd5e1', // Neutral Slate for original cost
                        hoverBackgroundColor: '#94a3b8',
                        borderRadius: 4,
                        barPercentage: 0.8
                    },
                    {
                        label: 'Net Book Value (Current)',
                        data: bookValues,
                        backgroundColor: '#dc2626', // Bold Brand Red for Current Value
                        hoverBackgroundColor: '#b91c1c', // Darker Red on hover
                        borderRadius: 4,
                        barPercentage: 0.8
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { position: 'top', labels: { usePointStyle: true, boxWidth: 8, font: { family: "'Inter', sans-serif", weight: '600' } } },
                    tooltip: { callbacks: { label: (ctx) => ' ' + ctx.dataset.label + ': ' + currencyFmt.format(ctx.raw) } }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        grid: { color: '#f1f5f9', drawBorder: false },
                        ticks: {
                            font: { family: "'Inter', sans-serif", size: 11 },
                            callback: function(value) {
                                if (value >= 1000000) return '₱' + (value / 1000000).toFixed(1) + 'M';
                                if (value >= 1000) return '₱' + (value / 1000).toFixed(0) + 'k';
                                return '₱' + value;
                            }
                        }
                    },
                    x: {
                        grid: { display: false, drawBorder: false },
                        ticks: { font: { family: "'Inter', sans-serif", weight: '600' } }
                    }
                }
            }
        });
    }

    function renderCategoryChart(data) {
        const ctx = document.getElementById('categoryChart').getContext('2d');
        if (chartCategory) chartCategory.destroy();

        chartCategory = new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: data.map(item => item.label),
                datasets: [{
                    data: data.map(item => parseFloat(item.value)),
                    backgroundColor: themeColors,
                    borderWidth: 2, borderColor: '#ffffff', hoverOffset: 4
                }]
            },
            options: {
                responsive: true, maintainAspectRatio: false, cutout: '65%',
                plugins: {
                    legend: { position: 'right', labels: { usePointStyle: true, boxWidth: 8, font: { size: 11, family: "'Inter', sans-serif" } } },
                    tooltip: { callbacks: { label: (ctx) => ' ' + ctx.label + ': ' + currencyFmt.format(ctx.raw) } }
                }
            }
        });
    }

    function renderBranchChart(data) {
        const ctx = document.getElementById('branchChart').getContext('2d');
        if (chartBranch) chartBranch.destroy();

        chartBranch = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: data.map(item => item.label),
                datasets: [{
                    label: 'Total Asset Value',
                    data: data.map(item => parseFloat(item.value)),
                    backgroundColor: '#1e293b', // Deep Slate for branches
                    hoverBackgroundColor: '#dc2626', // Pops Red on hover
                    borderRadius: 4, barPercentage: 0.6
                }]
            },
            options: {
                responsive: true, maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    tooltip: { callbacks: { label: (ctx) => ' Value: ' + currencyFmt.format(ctx.raw) } }
                },
                scales: {
                    y: {
                        beginAtZero: true, grid: { color: '#f1f5f9', drawBorder: false },
                        ticks: {
                            font: { size: 11, family: "'Inter', sans-serif" },
                            callback: function(value) {
                                if (value >= 1000000) return '₱' + (value / 1000000).toFixed(1) + 'M';
                                if (value >= 1000) return '₱' + (value / 1000).toFixed(0) + 'k';
                                return '₱' + value;
                            }
                        }
                    },
                    x: {
                        grid: { display: false, drawBorder: false },
                        ticks: { font: { size: 11, family: "'Inter', sans-serif" } }
                    }
                }
            }
        });
    }

    fetchDashboardData();
    document.getElementById('refreshDashboardBtn').addEventListener('click', fetchDashboardData);
});