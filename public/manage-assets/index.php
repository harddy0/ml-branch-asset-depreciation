<?php
$pageTitle   = 'Asset Overview';
$currentPage = 'manage-assets';
require_once __DIR__ . '/../../src/includes/init.php';
require_once __DIR__ . '/../../src/classes/AssetReportService.php';

$reportService = new \App\AssetReportService($pdo, $pdo2);

// Gather filter inputs with defaults for dates
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

<link href="https://cdn.jsdelivr.net/npm/tom-select@2.3.1/dist/css/tom-select.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
<style>
    /* Custom Overrides to match Tailwind Design */
    .ts-wrapper .ts-control {
        border: 1px solid #cbd5e1 !important; 
        border-radius: 0.25rem !important;    
        padding: 0.375rem 0.625rem !important; 
        font-size: 0.875rem !important;       
        font-weight: 500 !important;          
        color: #1e293b !important;            
        box-shadow: none !important;
        background-color: #ffffff !important;
        min-height: 34px !important;
    }
    .ts-wrapper.focus .ts-control {
        border-color: #3b82f6 !important;     
        box-shadow: 0 0 0 1px #3b82f6 !important;
    }
    .ts-dropdown {
        font-size: 0.875rem !important;
        border-radius: 0.25rem !important;
        border: 1px solid #cbd5e1 !important;
        box-shadow: 0 4px 6px -1px rgb(0 0 0 / 0.1) !important;
    }
</style>

<div class="mb-5 flex justify-between items-end">
    <div>
        <h1 class="text-2xl font-black text-slate-800 uppercase tracking-tight">Asset Overview</h1>
        <p class="text-sm text-slate-500 mt-0.5">Review and export current depreciation statuses across branches.</p>
    </div>
    
    <button type="button" onclick="exportExcel()" class="bg-red-50 border border-red-200 text-red-800 hover:bg-red-100 px-4 py-2 rounded-md text-sm font-bold flex items-center gap-2 transition-colors shadow-sm">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
        Export
    </button>
</div>

<div class="bg-white rounded-lg shadow-sm border border-slate-200 overflow-hidden">
    <div class="bg-slate-50 border-b border-slate-200 px-5 py-3">
        <form method="GET" action="" id="filterForm" class="flex flex-wrap items-center gap-3">
            
            <select name="zone" class="search-select min-w-[110px]" placeholder="-- All Zones --">
                <option value="">-- All Zones --</option>
                <?php foreach($zones as $z): ?>
                    <option value="<?= htmlspecialchars($z) ?>" <?= $filters['zone'] === $z ? 'selected' : '' ?>><?= htmlspecialchars($z) ?></option>
                <?php endforeach; ?>
            </select>

            <select name="region" class="search-select min-w-[150px]" placeholder="-- All Regions --">
                <option value="">-- All Regions --</option>
                <?php foreach($regions as $r): ?>
                    <option value="<?= htmlspecialchars($r) ?>" <?= $filters['region'] === $r ? 'selected' : '' ?>><?= htmlspecialchars($r) ?></option>
                <?php endforeach; ?>
            </select>

            <select name="branch_name" class="search-select min-w-[200px]" placeholder="-- All Branches --">
                <option value="">-- All Branches --</option>
                <?php foreach($branches as $b): ?>
                    <option value="<?= htmlspecialchars($b) ?>" <?= $filters['branch_name'] === $b ? 'selected' : '' ?>><?= htmlspecialchars($b) ?></option>
                <?php endforeach; ?>
            </select>

            <div class="flex items-center gap-2 border border-slate-300 rounded px-2 py-1 bg-white ml-auto">
                <span class="text-xs font-bold text-slate-500 uppercase tracking-wider">Period:</span>
                <input type="text" name="date_from" value="<?= htmlspecialchars($filters['date_from']) ?>" required class="date-formatter text-sm text-slate-800 font-medium outline-none cursor-pointer min-w-[130px] bg-transparent text-center" placeholder="Start Date">
                <span class="text-slate-300 font-bold">-</span>
                <input type="text" name="date_to" value="<?= htmlspecialchars($filters['date_to']) ?>" required class="date-formatter text-sm text-slate-800 font-medium outline-none cursor-pointer min-w-[130px] bg-transparent text-center" placeholder="End Date">
            </div>
        </form>
    </div>

    <div class="overflow-x-auto">
        <?php if (empty($data)): ?>
            <div class="text-center py-12 text-slate-500 font-bold text-sm bg-white">
                No active assets found for the selected filters and date range.
            </div>
        <?php else: ?>
            <table class="w-full text-sm text-left whitespace-nowrap">
                <thead>
                    <tr class="border-b-2 border-slate-200 bg-white">
                        <th class="py-2.5 pl-5 pr-3 font-bold text-slate-500 uppercase tracking-wider text-xs">Codes</th>
                        <th class="py-2.5 px-3 font-bold text-slate-500 uppercase tracking-wider text-xs">Branches</th>
                        <th class="py-2.5 px-3 font-bold text-slate-500 uppercase tracking-wider text-xs">Category</th>
                        <th class="py-2.5 px-3 font-bold text-slate-500 uppercase tracking-wider text-xs w-full">Description</th>
                        <th class="py-2.5 px-3 font-bold text-slate-500 uppercase tracking-wider text-xs text-right">Cost</th>
                        <th class="py-2.5 px-3 font-bold text-slate-500 uppercase tracking-wider text-xs text-right">Depreciation</th>
                        <th class="py-2.5 px-3 font-bold text-slate-500 uppercase tracking-wider text-xs text-right">Accu. Dep.</th>
                        <th class="py-2.5 px-3 font-bold text-slate-500 uppercase tracking-wider text-xs text-center">Life</th>
                        <th class="py-2.5 px-3 font-bold text-slate-500 uppercase tracking-wider text-xs text-right">Book Value</th>
                        <th class="py-2.5 pl-3 pr-5 font-bold text-slate-500 uppercase tracking-wider text-xs text-center">Date Generated</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 font-medium text-slate-700">
                    <?php foreach ($data as $row): ?>
                        <tr class="hover:bg-blue-50/50 transition-colors">
                            <td class="py-2 pl-5 pr-3 font-semibold text-slate-900"><?= htmlspecialchars($row['system_asset_code']) ?></td>
                            <td class="py-2 px-3"><?= htmlspecialchars($row['branch_name']) ?></td>
                            <td class="py-2 px-3 text-xs"><?= htmlspecialchars($row['category_name']) ?></td>
                            <td class="py-2 px-3 truncate max-w-[200px]" title="<?= htmlspecialchars($row['description']) ?>"><?= htmlspecialchars($row['description']) ?></td>
                            <td class="py-2 px-3 text-right font-mono"><?= number_format($row['acquisition_cost'], 2) ?></td>
                            <td class="py-2 px-3 text-right font-mono text-red-600"><?= number_format($row['period_depreciation_expense'], 2) ?></td>
                            <td class="py-2 px-3 text-right font-mono"><?= number_format($row['accumulated_depreciation'], 2) ?></td>
                            <td class="py-2 px-3 text-center font-bold"><?= $row['remaining_life'] ?></td>
                            <td class="py-2 px-3 text-right font-mono font-bold text-slate-900"><?= number_format($row['book_value'], 2) ?></td>
                            <td class="py-2 pl-3 pr-5 text-center text-slate-500 text-xs"><?= date('M j, Y', strtotime($row['period_date'])) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            
            <div class="border-t border-slate-200 bg-slate-50 px-5 py-3 flex items-center justify-between">
                <span class="text-xs font-bold text-slate-500 uppercase tracking-wider">Grand Totals</span>
                <div class="flex gap-8 text-sm font-black text-slate-800">
                    <span class="flex items-center gap-2">
                        <span class="text-xs text-slate-400 font-bold uppercase">Cost</span> 
                        <span class="font-mono"><?= number_format($totals['cost'], 2) ?></span>
                    </span>
                    <span class="flex items-center gap-2">
                        <span class="text-xs text-slate-400 font-bold uppercase">DE</span> 
                        <span class="font-mono text-red-600"><?= number_format($totals['de'], 2) ?></span>
                    </span>
                    <span class="flex items-center gap-2">
                        <span class="text-xs text-slate-400 font-bold uppercase">AD</span> 
                        <span class="font-mono"><?= number_format($totals['ad'], 2) ?></span>
                    </span>
                    <span class="flex items-center gap-2">
                        <span class="text-xs text-slate-400 font-bold uppercase">BV</span> 
                        <span class="font-mono"><?= number_format($totals['bv'], 2) ?></span>
                    </span>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
<script src="https://cdn.jsdelivr.net/npm/tom-select@2.3.1/dist/js/tom-select.complete.min.js"></script>

<script>
    // Initialize Searchable Dropdowns
    document.querySelectorAll('.search-select').forEach((el) => {
        new TomSelect(el, {
            create: false,
            maxOptions: null, 
            onChange: function() {
                document.getElementById('filterForm').submit();
            }
        });
    });

    // Initialize English date pickers
    flatpickr(".date-formatter", {
        altInput: true,
        altFormat: "M j, Y",
        dateFormat: "Y-m-d",
        onChange: function(selectedDates, dateStr, instance) {
            document.getElementById('filterForm').submit();
        }
    });

    function exportExcel() {
        const form = document.getElementById('filterForm');
        const params = new URLSearchParams(new FormData(form)).toString();
        window.location.href = '<?= BASE_URL ?>/public/actions/export_assets.php?' + params;
    }
</script>
<script src="<?= ASSET_URL ?>js/main.js"></script>