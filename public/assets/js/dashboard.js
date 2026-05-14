document.addEventListener('DOMContentLoaded', function () {
    const container = document.getElementById('dashboardContainer');
    if (!container) return;

    const apiUrl = container.getAttribute('data-api-url');
    const assetsApiUrl = apiUrl.replace('get_dashboard.php', 'get_assets.php');
    const depreciationApiUrl = apiUrl.replace('get_dashboard.php', 'get_depreciation_list.php');
    const issuanceApiUrl = apiUrl.replace('get_dashboard.php', 'get_issuance_report.php');

    const countFmt = new Intl.NumberFormat('en-US');

    const currencyFmt = new Intl.NumberFormat('en-PH', { style: 'currency', currency: 'PHP' });
    const themeColors = ['#dc2626', '#1e293b', '#ef4444', '#64748b', '#991b1b', '#94a3b8'];

    let chartZone     = null;
    let chartCategory = null;
    let chartBranch   = null;

    function fetchDepreciationList(params = {}) {
        const query = new URLSearchParams({ per_page: 100, ...params }).toString();
        return fetch(`${depreciationApiUrl}?${query}`).then(r => r.json());
    }

    function fetchIssuanceCount() {
        const query = new URLSearchParams({ page: 1, per_page: 1 }).toString();
        return fetch(`${issuanceApiUrl}?${query}`).then(r => r.json());
    }

    function fetchDashboardData() {
        Promise.all([
            fetchDepreciationList(),
            fetchDepreciationList({ status: 'ACTIVE' }),
            fetchDepreciationList({ status: 'DEPRECIATED' }),
            fetchDepreciationList({ status: 'SOLD' }),
            fetchIssuanceCount()
        ])
            .then(([allRes, activeRes, depreciatedRes, soldRes, issuanceRes]) => {
                if (!allRes.success) return;

                const allData = allRes.data || [];
                const activeData = activeRes.success ? (activeRes.data || []) : [];
                const depreciatedData = depreciatedRes.success ? (depreciatedRes.data || []) : [];

                const totalAssets = allRes.pagination?.total ?? allData.length;
                const activeAssets = activeRes.pagination?.total ?? activeData.length;
                const depreciatedAssets = depreciatedRes.pagination?.total ?? depreciatedData.length;
                const soldAssets = soldRes.success ? (soldRes.pagination?.total ?? (soldRes.data || []).length) : 0;
                const issuanceTotal = issuanceRes && issuanceRes.success
                    ? (issuanceRes.pagination?.total ?? 0)
                    : 0;

                // ── KPI Cards (Counts) ────────────────────────────────
                document.getElementById('overviewTotalCost').textContent    = countFmt.format(totalAssets);
                document.getElementById('overviewDepreciation').textContent = countFmt.format(activeAssets);
                document.getElementById('overviewAccumulated').textContent  = countFmt.format(depreciatedAssets);
                document.getElementById('overviewBookValue').textContent    = countFmt.format(soldAssets);

                // ── Active Assets ─────────────────────────────────────
                document.getElementById('ongoingCount').textContent = countFmt.format(activeAssets);
                document.getElementById('ongoingCost').textContent  = 'Total Count: ' + countFmt.format(activeAssets);

                const ongoingList = document.getElementById('ongoingList');
                if (activeData.length === 0) {
                    ongoingList.innerHTML = '<div class="text-xs text-slate-400 italic">No active assets</div>';
                } else {
                    const byBranch = {};
                    activeData.forEach(r => {
                        const branch = r.branch_name || 'Unknown';
                        byBranch[branch] = (byBranch[branch] || 0) + 1;
                    });
                    const sorted = Object.entries(byBranch).sort((a, b) => b[1] - a[1]);
                    ongoingList.innerHTML = sorted.map(([branch, count]) => `
                        <div class="flex justify-between items-center text-xs py-1 border-b border-slate-100">
                            <span class="text-slate-600 truncate max-w-[70%]">${branch}</span>
                            <span class="font-mono font-bold text-green-600">${count}</span>
                        </div>`).join('');
                }

                // ── Depreciated Assets ────────────────────────────────
                document.getElementById('closedCount').textContent = countFmt.format(depreciatedAssets);
                document.getElementById('closedCost').textContent  = 'Total Count: ' + countFmt.format(depreciatedAssets);

                const closedList = document.getElementById('closedList');
                if (depreciatedData.length === 0) {
                    closedList.innerHTML = '<div class="text-xs text-slate-400 italic">No depreciated assets</div>';
                } else {
                    const byBranch = {};
                    depreciatedData.forEach(r => {
                        const branch = r.branch_name || 'Unknown';
                        byBranch[branch] = (byBranch[branch] || 0) + 1;
                    });
                    const sorted = Object.entries(byBranch).sort((a, b) => b[1] - a[1]);
                    closedList.innerHTML = sorted.map(([branch, count]) => `
                        <div class="flex justify-between items-center text-xs py-1 border-b border-slate-100">
                            <span class="text-slate-600 truncate max-w-[70%]">${branch}</span>
                            <span class="font-mono font-bold text-red-500">${count}</span>
                        </div>`).join('');
                }

                // ── Category Breakdown (Counts) ───────────────────────
                document.getElementById('categoryCount').textContent = countFmt.format(issuanceTotal);
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