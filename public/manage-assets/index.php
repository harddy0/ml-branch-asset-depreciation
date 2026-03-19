<?php
$pageTitle   = 'Asset Overview';
$currentPage = 'manage-assets';
require_once __DIR__ . '/../../src/includes/init.php';
require_once __DIR__ . '/../../src/classes/AssetReportService.php';

$reportService = new \App\AssetReportService($pdo, $pdo2);

// Gather filter inputs for INITIAL PAGE LOAD only
$filters = [
    'zone'        => $_GET['zone'] ?? '',
    'region'      => $_GET['region'] ?? '',
    'branch_name' => $_GET['branch_name'] ?? '',
    'date_from'   => $_GET['date_from'] ?? date('Y-m-01'), 
    'date_to'     => $_GET['date_to'] ?? date('Y-m-t')     
];

$zones    = $reportService->getZones();
$regions  = $reportService->getRegions($filters['zone']);
$branches = $reportService->getBranches($filters['zone'], $filters['region']);

$reportData = $reportService->getFilteredAssets($filters);
$data   = $reportData['data'];
$totals = $reportData['totals'];
?>

<link href="https://cdn.jsdelivr.net/npm/tom-select@2.3.1/dist/css/tom-select.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
<style>
    /* Custom Overrides */
    .ts-wrapper .ts-control {
        border: 1px solid #cbd5e1 !important; border-radius: 0.25rem !important;    
        padding: 0.375rem 0.625rem !important; font-size: 0.875rem !important;       
        font-weight: 500 !important; color: #1e293b !important;            
        box-shadow: none !important; background-color: #ffffff !important;
        min-height: 34px !important;
    }
    .ts-wrapper.focus .ts-control { border-color: #3b82f6 !important; box-shadow: 0 0 0 1px #3b82f6 !important; }
    .ts-dropdown { font-size: 0.875rem !important; border-radius: 0.25rem !important; border: 1px solid #cbd5e1 !important; box-shadow: 0 4px 6px -1px rgb(0 0 0 / 0.1) !important; }
    #tableWrapper { transition: opacity 0.2s ease-in-out; }
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
        <form id="filterForm" class="flex flex-wrap items-center gap-3" onsubmit="event.preventDefault();">
            
            <select name="zone" id="zoneSelect" class="min-w-[110px]" placeholder="-- All Zones --">
                <option value="">-- All Zones --</option>
                <?php foreach($zones as $z): ?>
                    <option value="<?= htmlspecialchars($z) ?>" <?= $filters['zone'] === $z ? 'selected' : '' ?>><?= htmlspecialchars($z) ?></option>
                <?php endforeach; ?>
            </select>

            <select name="region" id="regionSelect" class="min-w-[150px]" placeholder="-- All Regions --">
                <option value="">-- All Regions --</option>
                <?php foreach($regions as $r): ?>
                    <option value="<?= htmlspecialchars($r) ?>" <?= $filters['region'] === $r ? 'selected' : '' ?>><?= htmlspecialchars($r) ?></option>
                <?php endforeach; ?>
            </select>

            <select name="branch_name" id="branchSelect" class="min-w-[200px]" placeholder="-- All Branches --">
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
        <div id="noDataWrapper" class="<?= empty($data) ? '' : 'hidden' ?> text-center py-12 text-slate-500 font-bold text-sm bg-white">
            No active assets found for the selected filters and date range.
        </div>
        
        <div id="tableWrapper" class="<?= empty($data) ? 'hidden' : '' ?>">
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
                <tbody id="tableBody" class="divide-y divide-slate-100 font-medium text-slate-700">
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
                        <span id="totCost" class="font-mono"><?= number_format($totals['cost'], 2) ?></span>
                    </span>
                    <span class="flex items-center gap-2">
                        <span class="text-xs text-slate-400 font-bold uppercase">DE</span> 
                        <span id="totDE" class="font-mono text-red-600"><?= number_format($totals['de'], 2) ?></span>
                    </span>
                    <span class="flex items-center gap-2">
                        <span class="text-xs text-slate-400 font-bold uppercase">AD</span> 
                        <span id="totAD" class="font-mono"><?= number_format($totals['ad'], 2) ?></span>
                    </span>
                    <span class="flex items-center gap-2">
                        <span class="text-xs text-slate-400 font-bold uppercase">BV</span> 
                        <span id="totBV" class="font-mono"><?= number_format($totals['bv'], 2) ?></span>
                    </span>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
<script src="https://cdn.jsdelivr.net/npm/tom-select@2.3.1/dist/js/tom-select.complete.min.js"></script>

<script>
    let tsZone, tsRegion, tsBranch;

    document.addEventListener("DOMContentLoaded", function() {
        
        // 1. Initialize TomSelects with dynamic API fetching
        tsZone = new TomSelect('#zoneSelect', {
            create: false, maxOptions: null, valueField: 'value', labelField: 'text', searchField: 'text',
            onChange: function(value) {
                tsRegion.clear(true); // Clear sub-menus silently
                tsBranch.clear(true);
                fetchData('zone');
            }
        });

        tsRegion = new TomSelect('#regionSelect', {
            create: false, maxOptions: null, valueField: 'value', labelField: 'text', searchField: 'text',
            onChange: function(value) {
                tsBranch.clear(true);
                fetchData('region');
            }
        });

        tsBranch = new TomSelect('#branchSelect', {
            create: false, maxOptions: null, valueField: 'value', labelField: 'text', searchField: 'text',
            onChange: function(value) {
                fetchData('branch');
            }
        });

        // 2. Initialize Date Picker
        flatpickr(".date-formatter", {
            altInput: true, altFormat: "M j, Y", dateFormat: "Y-m-d",
            onChange: function() {
                fetchData('date');
            }
        });
    });

    // Main AJAX Function
    function fetchData(source) {
        const form = document.getElementById('filterForm');
        const params = new URLSearchParams(new FormData(form)).toString();
        
        // Visual cue for loading
        document.getElementById('tableWrapper').style.opacity = '0.5';

        fetch('<?= BASE_URL ?>/public/api/get_assets.php?' + params)
            .then(response => response.json())
            .then(res => {
                if(res.success) {
                    // Update dropdown lists if parent changed
                    if (source === 'zone') {
                        updateTomSelect(tsRegion, res.regions, 'Regions');
                        updateTomSelect(tsBranch, res.branches, 'Branches');
                    } else if (source === 'region') {
                        updateTomSelect(tsBranch, res.branches, 'Branches');
                    }

                    // Render Data Table
                    renderTable(res.data, res.totals);
                } else {
                    console.error("Failed to fetch data:", res.error);
                }
            })
            .catch(err => console.error("Network Error:", err))
            .finally(() => {
                document.getElementById('tableWrapper').style.opacity = '1';
            });
    }

    // Helper: Repopulate TomSelect Options dynamically
    function updateTomSelect(instance, optionsData, labelPlural) {
        instance.clearOptions();
        instance.addOption({value: '', text: '-- All ' + labelPlural + ' --'});
        optionsData.forEach(item => {
            instance.addOption({value: item, text: item});
        });
        instance.refreshOptions(false);
    }

    // Helper: Convert array data to HTML Table rows
    function renderTable(data, totals) {
        const tbody = document.getElementById('tableBody');
        const wrapper = document.getElementById('tableWrapper');
        const noData = document.getElementById('noDataWrapper');

        if (data.length === 0) {
            wrapper.classList.add('hidden');
            noData.classList.remove('hidden');
        } else {
            wrapper.classList.remove('hidden');
            noData.classList.add('hidden');

            const currency = new Intl.NumberFormat('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
            const dateFmt = new Intl.DateTimeFormat('en-US', { month: 'short', day: 'numeric', year: 'numeric' });

            let html = '';
            data.forEach(r => {
                html += `<tr class="hover:bg-blue-50/50 transition-colors">
                    <td class="py-2 pl-5 pr-3 font-semibold text-slate-900">${r.system_asset_code}</td>
                    <td class="py-2 px-3">${r.branch_name}</td>
                    <td class="py-2 px-3 text-xs">${r.category_name}</td>
                    <td class="py-2 px-3 truncate max-w-[200px]" title="${r.description}">${r.description}</td>
                    <td class="py-2 px-3 text-right font-mono">${currency.format(r.acquisition_cost)}</td>
                    <td class="py-2 px-3 text-right font-mono text-red-600">${currency.format(r.period_depreciation_expense)}</td>
                    <td class="py-2 px-3 text-right font-mono">${currency.format(r.accumulated_depreciation)}</td>
                    <td class="py-2 px-3 text-center font-bold">${r.remaining_life}</td>
                    <td class="py-2 px-3 text-right font-mono font-bold text-slate-900">${currency.format(r.book_value)}</td>
                    <td class="py-2 pl-3 pr-5 text-center text-slate-500 text-xs">${dateFmt.format(new Date(r.period_date))}</td>
                </tr>`;
            });
            tbody.innerHTML = html;

            // Update Grand Totals
            document.getElementById('totCost').innerText = currency.format(totals.cost);
            document.getElementById('totDE').innerText = currency.format(totals.de);
            document.getElementById('totAD').innerText = currency.format(totals.ad);
            document.getElementById('totBV').innerText = currency.format(totals.bv);
        }
    }

    function exportExcel() {
        const form = document.getElementById('filterForm');
        const params = new URLSearchParams(new FormData(form)).toString();
        // Export relies on standard HTTP redirect, untouched by AJAX
        window.location.href = '<?= BASE_URL ?>/public/actions/export_assets.php?' + params;
    }
</script>
<script src="<?= ASSET_URL ?>js/main.js"></script>