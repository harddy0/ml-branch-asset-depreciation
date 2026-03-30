document.addEventListener('DOMContentLoaded', function () {
    const container = document.getElementById('dashboardContainer');
    if (!container) return;

    const apiUrl = container.getAttribute('data-api-url');
    const assetsApiUrl = apiUrl.replace('get_dashboard.php', 'get_assets.php');

    const fmt = (val) => new Intl.NumberFormat('en-US', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
    }).format(parseFloat(val) || 0);

    const currencyFmt = new Intl.NumberFormat('en-PH', { style: 'currency', currency: 'PHP' });
    const themeColors = ['#dc2626', '#1e293b', '#ef4444', '#64748b', '#991b1b', '#94a3b8'];

    let chartZone     = null;
    let chartCategory = null;
    let chartBranch   = null;

    function fetchDashboardData() {
        fetch(assetsApiUrl)
            .then(r => r.json())
            .then(res => {
                if (!res.success) return;
                const t = res.totals || {};
                const data = res.data || [];

                // ── KPI Cards ──────────────────────────────────────────
                document.getElementById('overviewTotalCost').textContent    = '₱' + fmt(t.cost);
                document.getElementById('overviewDepreciation').textContent = '₱' + fmt(t.de);
                document.getElementById('overviewAccumulated').textContent  = '₱' + fmt(t.ad);
                document.getElementById('overviewBookValue').textContent    = '₱' + fmt(t.bv);

                // ── Split ongoing vs closed by remaining_life ──────────
                const ongoing = data.filter(r => parseFloat(r.remaining_life) > 0);
                const closed  = data.filter(r => parseFloat(r.remaining_life) <= 0);

                // ── Ongoing ────────────────────────────────────────────
                const ongoingTotal = ongoing.reduce((s, r) => s + parseFloat(r.acquisition_cost || 0), 0);
                document.getElementById('ongoingCount').textContent = ongoing.length + ' assets';
                document.getElementById('ongoingCost').textContent  = 'Total Cost: ₱' + fmt(ongoingTotal);

                const ongoingList = document.getElementById('ongoingList');
                if (ongoing.length === 0) {
                    ongoingList.innerHTML = '<div class="text-xs text-slate-400 italic">No ongoing assets</div>';
                } else {
                    const byBranch = {};
                    ongoing.forEach(r => {
                        byBranch[r.branch_name] = (byBranch[r.branch_name] || 0) + 1;
                    });
                    const sorted = Object.entries(byBranch).sort((a, b) => b[1] - a[1]); // removed .slice(0, 6)
                    ongoingList.innerHTML = sorted.map(([branch, count]) => `
                        <div class="flex justify-between items-center text-xs py-1 border-b border-slate-100">
                            <span class="text-slate-600 truncate max-w-[70%]">${branch}</span>
                            <span class="font-mono font-bold text-green-600">${count}</span>
                        </div>`).join('');
                }

                // ── Closed ─────────────────────────────────────────────
                const closedTotal = closed.reduce((s, r) => s + parseFloat(r.acquisition_cost || 0), 0);
                document.getElementById('closedCount').textContent = closed.length + ' assets';
                document.getElementById('closedCost').textContent  = 'Total Cost: ₱' + fmt(closedTotal);

                const closedList = document.getElementById('closedList');
                if (closed.length === 0) {
                    closedList.innerHTML = '<div class="text-xs text-slate-400 italic">No closed assets</div>';
                } else {
                    const byBranch = {};
                    closed.forEach(r => {
                        byBranch[r.branch_name] = (byBranch[r.branch_name] || 0) + 1;
                    });
                    const sorted = Object.entries(byBranch).sort((a, b) => b[1] - a[1]); // removed .slice(0, 6)
                    closedList.innerHTML = sorted.map(([branch, count]) => `
                        <div class="flex justify-between items-center text-xs py-1 border-b border-slate-100">
                            <span class="text-slate-600 truncate max-w-[70%]">${branch}</span>
                            <span class="font-mono font-bold text-red-500">${count}</span>
                        </div>`).join('');
                }

                // ── Category Breakdown ─────────────────────────────────
                const allCategories = res.all_categories || [];

                const byCat = {};
                // Pre-populate ALL categories from DB with zero
                allCategories.forEach(cat => {
                    byCat[cat] = { count: 0, cost: 0 };
                });

                // Fill in actual data
                data.forEach(r => {
                    if (!byCat[r.category_name]) byCat[r.category_name] = { count: 0, cost: 0 };
                    byCat[r.category_name].count++;
                    byCat[r.category_name].cost += parseFloat(r.acquisition_cost || 0);
                });

                const cats = Object.entries(byCat).sort((a, b) => b[1].cost - a[1].cost);
                const totalCost = cats.reduce((s, [, v]) => s + v.cost, 0);

                document.getElementById('categoryCount').textContent = cats.length + ' categories';
                document.getElementById('categoryList').innerHTML = cats.map(([cat, v]) => {
                    const pct = totalCost > 0 ? ((v.cost / totalCost) * 100).toFixed(1) : 0;
                    return `
                        <div class="mb-2">
                            <div class="flex justify-between text-xs mb-1">
                                <span class="text-slate-600 font-medium truncate max-w-[60%]">${cat}</span>
                                <span class="text-slate-400 font-mono">${v.count} · ${pct}%</span>
                            </div>
                            <div class="w-full bg-slate-100 rounded-full h-1.5">
                                <div class="bg-red-600 h-1.5 rounded-full" style="width: ${pct}%"></div>
                            </div>
                        </div>`;
                }).join('');
            })
            .catch(err => console.error('Dashboard fetch error:', err));
    }

    function renderZoneChart(data) {
        const ctx = document.getElementById('zoneChart');
        if (!ctx) return;
        if (chartZone) chartZone.destroy();
        chartZone = new Chart(ctx.getContext('2d'), {
            type: 'bar',
            data: {
                labels: data.map(i => i.label),
                datasets: [
                    { label: 'Acquisition Cost',        data: data.map(i => parseFloat(i.total_cost)),       backgroundColor: '#cbd5e1', hoverBackgroundColor: '#94a3b8', borderRadius: 4, barPercentage: 0.8 },
                    { label: 'Net Book Value (Current)', data: data.map(i => parseFloat(i.total_book_value)), backgroundColor: '#dc2626', hoverBackgroundColor: '#b91c1c', borderRadius: 4, barPercentage: 0.8 }
                ]
            },
            options: {
                responsive: true, maintainAspectRatio: false,
                plugins: {
                    legend: { position: 'top', labels: { usePointStyle: true, boxWidth: 8 } },
                    tooltip: { callbacks: { label: c => ' ' + c.dataset.label + ': ' + currencyFmt.format(c.raw) } }
                },
                scales: {
                    y: { beginAtZero: true, grid: { color: '#f1f5f9' }, ticks: { callback: v => v >= 1e6 ? '₱'+(v/1e6).toFixed(1)+'M' : v >= 1000 ? '₱'+(v/1000).toFixed(0)+'k' : '₱'+v } },
                    x: { grid: { display: false } }
                }
            }
        });
    }

    function renderCategoryChart(data) {
        const ctx = document.getElementById('categoryChart');
        if (!ctx) return;
        if (chartCategory) chartCategory.destroy();
        chartCategory = new Chart(ctx.getContext('2d'), {
            type: 'doughnut',
            data: {
                labels: data.map(i => i.label),
                datasets: [{ data: data.map(i => parseFloat(i.value)), backgroundColor: themeColors, borderWidth: 2, borderColor: '#ffffff', hoverOffset: 4 }]
            },
            options: {
                responsive: true, maintainAspectRatio: false, cutout: '65%',
                plugins: {
                    legend: { position: 'right', labels: { usePointStyle: true, boxWidth: 8 } },
                    tooltip: { callbacks: { label: c => ' ' + c.label + ': ' + currencyFmt.format(c.raw) } }
                }
            }
        });
    }

    function renderBranchChart(data) {
        const ctx = document.getElementById('branchChart');
        if (!ctx) return;
        if (chartBranch) chartBranch.destroy();
        chartBranch = new Chart(ctx.getContext('2d'), {
            type: 'bar',
            data: {
                labels: data.map(i => i.label),
                datasets: [{ label: 'Total Asset Value', data: data.map(i => parseFloat(i.value)), backgroundColor: '#1e293b', hoverBackgroundColor: '#dc2626', borderRadius: 4, barPercentage: 0.6 }]
            },
            options: {
                responsive: true, maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    tooltip: { callbacks: { label: c => ' Value: ' + currencyFmt.format(c.raw) } }
                },
                scales: {
                    y: { beginAtZero: true, grid: { color: '#f1f5f9' }, ticks: { callback: v => v >= 1e6 ? '₱'+(v/1e6).toFixed(1)+'M' : v >= 1000 ? '₱'+(v/1000).toFixed(0)+'k' : '₱'+v } },
                    x: { grid: { display: false } }
                }
            }
        });
    }

    const refreshBtn = document.getElementById('refreshDashboardBtn');
    if (refreshBtn) refreshBtn.addEventListener('click', fetchDashboardData);

    fetchDashboardData();
});