<?php
$pageTitle   = 'Active Assets Report';
$currentPage = 'manage-assets';
require_once __DIR__ . '/../../src/includes/init.php';
require_once __DIR__ . '/../../src/classes/AssetReportService.php';

$reportService = new \App\AssetReportService($pdo, $pdo2);

// Gather filter inputs with defaults for dates to prevent "no data" on first load
$filters = [
    'zone'        => $_GET['zone'] ?? '',
    'region'      => $_GET['region'] ?? '',
    'branch_name' => $_GET['branch_name'] ?? '',
    'date_from'   => $_GET['date_from'] ?? date('Y-m-01'), 
    'date_to'     => $_GET['date_to'] ?? date('Y-m-t')     
];

// Fetch dropdown data strictly from Masterdata DB2
$zones    = $reportService->getZones();
$regions  = $reportService->getRegions($filters['zone']);
$branches = $reportService->getBranches($filters['zone'], $filters['region']);

// Fetch report data
$reportData = $reportService->getFilteredAssets($filters);
$data   = $reportData['data'];
$totals = $reportData['totals'];
?>

<div class="mb-6">
    <h1 class="text-2xl font-black text-slate-800 uppercase tracking-tight">Active Assets Report</h1>
    <p class="text-sm text-slate-500 mt-1">Review and export current depreciation statuses across branches.</p>
</div>

<div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6">
    <form method="GET" action="" id="filterForm" class="flex flex-wrap items-center gap-3 mb-6 border-b border-slate-100 pb-6">
        
        <select name="zone" onchange="this.form.submit()" class="border border-slate-300 rounded-md px-3 py-2 text-sm text-slate-800 font-medium min-w-[120px] outline-none focus:border-blue-500 cursor-pointer">
            <option value="">-- All Zones --</option>
            <?php foreach($zones as $z): ?>
                <option value="<?= htmlspecialchars($z) ?>" <?= $filters['zone'] === $z ? 'selected' : '' ?>><?= htmlspecialchars($z) ?></option>
            <?php endforeach; ?>
        </select>

        <select name="region" onchange="this.form.submit()" class="border border-slate-300 rounded-md px-3 py-2 text-sm text-slate-800 font-medium min-w-[150px] outline-none focus:border-blue-500 cursor-pointer">
            <option value="">-- All Regions --</option>
            <?php foreach($regions as $r): ?>
                <option value="<?= htmlspecialchars($r) ?>" <?= $filters['region'] === $r ? 'selected' : '' ?>><?= htmlspecialchars($r) ?></option>
            <?php endforeach; ?>
        </select>

        <select name="branch_name" onchange="this.form.submit()" class="border border-slate-300 rounded-md px-3 py-2 text-sm text-slate-800 font-medium min-w-[200px] outline-none focus:border-blue-500 cursor-pointer">
            <option value="">-- All Branches --</option>
            <?php foreach($branches as $b): ?>
                <option value="<?= htmlspecialchars($b) ?>" <?= $filters['branch_name'] === $b ? 'selected' : '' ?>><?= htmlspecialchars($b) ?></option>
            <?php endforeach; ?>
        </select>

        <div class="flex items-center gap-2 border border-slate-300 rounded-md px-2 bg-white">
            <input type="date" name="date_from" value="<?= htmlspecialchars($filters['date_from']) ?>" onchange="this.form.submit()" required class="py-2 text-sm text-slate-800 font-medium outline-none cursor-pointer">
            <span class="text-slate-400 font-bold">to</span>
            <input type="date" name="date_to" value="<?= htmlspecialchars($filters['date_to']) ?>" onchange="this.form.submit()" required class="py-2 text-sm text-slate-800 font-medium outline-none cursor-pointer">
        </div>

        <button type="button" onclick="exportExcel()" class="bg-emerald-50 border border-emerald-200 text-emerald-800 hover:bg-emerald-100 px-5 py-2.5 rounded-md text-sm font-bold flex items-center gap-2 ml-auto transition-colors">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
            Export Excel
        </button>
    </form>

    <div class="overflow-x-auto">
        <?php if (empty($data)): ?>
            <div class="text-center py-12 text-slate-500 font-bold text-base bg-slate-50 rounded-xl border border-slate-200 shadow-inner">
                No active assets found for the selected filters and date range.
            </div>
        <?php else: ?>
            <table class="w-full text-sm text-left">
                <thead>
                    <tr class="border-b-2 border-slate-300 bg-slate-50/50">
                        <th class="py-4 pr-3 font-black text-slate-900 uppercase tracking-wide">Codes</th>
                        <th class="py-4 px-3 font-black text-slate-900 uppercase tracking-wide">Branches</th>
                        <th class="py-4 px-3 font-black text-slate-900 uppercase tracking-wide">Asset Category</th>
                        <th class="py-4 px-3 font-black text-slate-900 uppercase tracking-wide">Description</th>
                        <th class="py-4 px-3 font-black text-slate-900 uppercase tracking-wide text-right">Cost</th>
                        <th class="py-4 px-3 font-black text-slate-900 uppercase tracking-wide text-right">Depreciation</th>
                        <th class="py-4 px-3 font-black text-slate-900 uppercase tracking-wide text-right">Accu. Dep.</th>
                        <th class="py-4 px-3 font-black text-slate-900 uppercase tracking-wide text-center">Asset Lives</th>
                        <th class="py-4 px-3 font-black text-slate-900 uppercase tracking-wide text-right">Book Value</th>
                        <th class="py-4 pl-3 font-black text-slate-900 uppercase tracking-wide text-center">Date Gen.</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200 font-medium text-slate-800">
                    <?php foreach ($data as $row): ?>
                        <tr class="hover:bg-blue-50/50 transition-colors">
                            <td class="py-4 pr-3 whitespace-nowrap font-semibold"><?= htmlspecialchars($row['system_asset_code']) ?></td>
                            <td class="py-4 px-3 whitespace-nowrap"><?= htmlspecialchars($row['branch_name']) ?></td>
                            <td class="py-4 px-3"><?= htmlspecialchars($row['category_name']) ?></td>
                            <td class="py-4 px-3 max-w-[250px] truncate" title="<?= htmlspecialchars($row['description']) ?>"><?= htmlspecialchars($row['description']) ?></td>
                            <td class="py-4 px-3 text-right font-mono"><?= number_format($row['acquisition_cost'], 2) ?></td>
                            <td class="py-4 px-3 text-right font-mono text-red-600"><?= number_format($row['period_depreciation_expense'], 2) ?></td>
                            <td class="py-4 px-3 text-right font-mono"><?= number_format($row['accumulated_depreciation'], 2) ?></td>
                            <td class="py-4 px-3 text-center font-bold text-slate-900"><?= $row['remaining_life'] ?></td>
                            <td class="py-4 px-3 text-right font-mono font-bold text-slate-900"><?= number_format($row['book_value'], 2) ?></td>
                            
                            <td class="py-4 pl-3 text-center text-slate-500 text-xs"><?= htmlspecialchars($row['period_date']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            
            <div class="mt-6 pt-5 border-t-4 border-slate-800 flex items-center justify-between text-sm font-black text-slate-900 bg-slate-50 px-4 py-3 rounded-b-lg">
                <div class="flex flex-wrap gap-x-12 gap-y-2">
                    <span class="flex flex-col">
                        <span class="text-xs text-slate-500 uppercase tracking-widest mb-1">Total Cost</span>
                        <span class="text-base font-mono"><?= number_format($totals['cost'], 2) ?></span>
                    </span>
                    <span class="flex flex-col">
                        <span class="text-xs text-slate-500 uppercase tracking-widest mb-1">Total DE</span>
                        <span class="text-base font-mono text-red-600"><?= number_format($totals['de'], 2) ?></span>
                    </span>
                    <span class="flex flex-col">
                        <span class="text-xs text-slate-500 uppercase tracking-widest mb-1">Total AD</span>
                        <span class="text-base font-mono"><?= number_format($totals['ad'], 2) ?></span>
                    </span>
                    <span class="flex flex-col">
                        <span class="text-xs text-slate-500 uppercase tracking-widest mb-1">Total BV</span>
                        <span class="text-base font-mono"><?= number_format($totals['bv'], 2) ?></span>
                    </span>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
function exportExcel() {
    const form = document.getElementById('filterForm');
    const params = new URLSearchParams(new FormData(form)).toString();
    window.location.href = '<?= BASE_URL ?>/public/actions/export_assets.php?' + params;
}
</script>
<script src="<?= ASSET_URL ?>js/main.js"></script>