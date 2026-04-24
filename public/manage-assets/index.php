<?php
$pageTitle   = 'Asset Overview';
$currentPage = 'manage-assets';
require_once __DIR__ . '/../../src/includes/init.php';
require_once __DIR__ . '/../actions/load_depreciation_list_page.php';

$data   = [];
$totals = ['cost' => 0, 'de' => 0, 'ad' => 0, 'bv' => 0];
?>

<link href="https://cdn.jsdelivr.net/npm/tom-select@2.3.1/dist/css/tom-select.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
<style>
    .ts-wrapper .ts-control { border: 1px solid #cbd5e1 !important; border-radius: 0.25rem !important; padding: 0.375rem 0.625rem !important; font-size: 0.875rem !important; font-weight: 500 !important; color: #1e293b !important; box-shadow: none !important; background-color: #ffffff !important; height: 34px !important; min-height: 34px !important; max-height: 34px !important; display: flex !important; align-items: center !important; flex-wrap: nowrap !important; overflow: hidden !important; position: relative !important; }
    .ts-wrapper.focus .ts-control { border-color: #ce2216 !important; box-shadow: 0 0 0 1px #ce2216 !important; }
    .ts-dropdown { font-size: 0.875rem !important; border-radius: 0.25rem !important; border: 1px solid #cbd5e1 !important; box-shadow: 0 4px 6px -1px rgb(0 0 0 / 0.1) !important; z-index: 9999 !important; }
    .ts-wrapper { width: 100% !important; }
    .ts-wrapper.single .ts-control > .item { white-space: nowrap !important; overflow: hidden !important; text-overflow: ellipsis !important; max-width: 100% !important; position: relative !important; z-index: 2 !important; }
    /* Ensure the internal Tom Select input shows full, non-faded text when focused */
    .ts-wrapper .ts-control input {
        color: #1e293b !important;
        background-color: transparent !important;
        opacity: 1 !important;
        caret-color: #1e293b !important;
        border: none !important;
        padding: 0 !important;
        margin: 0 !important;
        line-height: 1 !important;
        position: absolute !important;
        inset: 0.375rem 0.625rem !important;
        width: calc(100% - 1.25rem) !important;
        min-width: calc(100% - 1.25rem) !important;
        z-index: 1 !important;
        pointer-events: none !important;
    }

    /* In normal mode with selected value, show item and hide input text layer */
    .ts-wrapper.has-items:not(.ts-typing-mode) .ts-control input {
        opacity: 0 !important;
    }

    .ts-wrapper.ts-typing-mode .ts-control input {
        opacity: 1 !important;
        z-index: 3 !important;
        pointer-events: auto !important;
    }

    .ts-wrapper.ts-typing-mode .ts-control > .item {
        opacity: 0 !important;
    }

    /* Make selected label smaller and monospace for Zone, Region and Branch selects */
    select#zoneSelect + .ts-wrapper .ts-control .item,
    select#regionSelect + .ts-wrapper .ts-control .item,
    select#branchSelect + .ts-wrapper .ts-control .item {
        font-size: 0.75rem !important; /* xs */
        font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, 'Roboto Mono', 'Courier New', monospace !important;
        font-weight: 600 !important;
    }

    select#zoneSelect + .ts-wrapper input,
    select#regionSelect + .ts-wrapper input,
    select#branchSelect + .ts-wrapper input {
        font-size: 0.75rem !important; /* xs */
        font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, 'Roboto Mono', 'Courier New', monospace !important;
        font-weight: 600 !important;
    }

    /* Match date inputs typography with Region selection style */
    .date-formatter {
        font-size: 0.75rem !important; /* xs */
        font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, 'Roboto Mono', 'Courier New', monospace !important;
        font-weight: 600 !important;
    }
    /* Even table rows background */
    #tableWrapper table tbody tr:nth-child(even) { background-color: #f8fafc; }
    /* Hover row background (slate-100) */
    #tableWrapper table tbody tr:hover { background-color: #f1f5f9; transition: background-color 0.15s ease; }
    #tableWrapper { transition: opacity 0.2s ease-in-out; }
</style>

<div class="mb-2 flex justify-between items-end">
    <div>
        <h1 class="text-1xl font-black text-slate-800 uppercase tracking-wide">Running Depreciation Report</h1>
    </div>
    
    <div class="relative inline-block text-left">
        <button id="exportToggleBtn" type="button" aria-expanded="false" aria-haspopup="true" class="border border-slate-200 text-slate-800 hover:bg-[#ce2216] hover:text-white px-4 py-1 rounded-md text-sm font-mono flex items-center gap-2 transition-colors shadow-sm">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v2a2 2 0 002 2h12a2 2 0 002-2v-2M7 10l5 5m0 0l5-5m-5 5V4"/></svg>
            Export
        </button>

        <div id="exportMenu" class="origin-top-right absolute right-0 mt-2 w-35 rounded-md shadow-lg bg-white ring-1 ring-black ring-opacity-5 hidden z-50">
            <div class="py-1">
                <button id="exportExcelBtn" type="button" class="w-full text-left px-6 py-2 text-sm text-slate-700 hover:bg-slate-100 flex items-center gap-2">
                    <!-- Excel icon -->
                    <svg class="w-4 h-4 text-green-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M3 7v10a2 2 0 0 0 2 2h14V5H5a2 2 0 0 0-2 2z" stroke-linecap="round" stroke-linejoin="round"/><path d="M7 10h10M7 14h6" stroke-linecap="round" stroke-linejoin="round"/></svg>
                    Excel
                </button>
                <button id="exportPrintBtn" type="button" class="w-full text-left px-6 py-2 text-sm text-slate-700 hover:bg-slate-100 flex items-center gap-2">
                    <!-- Print icon -->
                    <svg class="w-4 h-4 text-slate-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M6 9V2h12v7" stroke-linecap="round" stroke-linejoin="round"/><path d="M6 18H4a2 2 0 0 1-2-2v-3h20v3a2 2 0 0 1-2 2h-2" stroke-linecap="round" stroke-linejoin="round"/><rect x="6" y="14" width="12" height="8" rx="2" ry="2"/></svg>
                    Print
                </button>
            </div>
        </div>
    </div>
</div>

<div class="mb-1 mr-6 text-right">
    <p class="text-[11px] font-mono text-slate-500">Filtered as of date</p>
</div>

<div class="bg-white rounded-lg shadow-sm border border-slate-200 overflow-hidden">
        <div class="bg-slate-50 border-b border-slate-200 px-3 py-2">
       <form id="filterForm" 
            data-api-url="<?= BASE_URL ?>/public/api/get_assets.php" 
            data-export-url="<?= BASE_URL ?>/public/actions/export_assets.php"
          data-generated-by="<?= htmlspecialchars($_SESSION['full_name'] ?? 'User') ?>"
            class="m-0 flex flex-row items-center gap-4 w-full">

            <div class="flex flex-1 items-center gap-2">
                
                <select name="zone" id="zoneSelect" class="flex-1 min-w-0 outline-none text-sm">
                    <option value="">Select Zone</option>
                    <option value="__ALL__" <?= ($rawFilters['zone'] ?? '') === '__ALL__' ? 'selected' : '' ?>>All Zones</option>
                    <?php foreach($zones as $z): ?>
                        <option value="<?= htmlspecialchars($z) ?>" <?= $filters['zone'] === $z ? 'selected' : '' ?>><?= htmlspecialchars($z) ?></option>
                    <?php endforeach; ?>
                </select>

                <div class="h-4 w-px bg-slate-200"></div> <select name="region" id="regionSelect" class="flex-1 min-w-0 outline-none text-sm">
                    <option value="">Select Region</option>
                    <option value="__ALL__" <?= ($rawFilters['region'] ?? '') === '__ALL__' ? 'selected' : '' ?>>All Regions</option>
                    <?php foreach($regions as $r): ?>
                        <option value="<?= htmlspecialchars($r) ?>" <?= $filters['region'] === $r ? 'selected' : '' ?>><?= htmlspecialchars($r) ?></option>
                    <?php endforeach; ?>
                </select>

                <div class="h-4 w-px bg-slate-200"></div> <select name="branch_name" id="branchSelect" class="flex-[2] min-w-0 outline-none text-sm font-semibold">
                    <option value="">Select Branch</option>
                    <option value="__ALL__" <?= ($rawFilters['branch_name'] ?? '') === '__ALL__' ? 'selected' : '' ?>>All Branches</option>
                    <?php foreach($branches as $b): ?>
                        <option value="<?= htmlspecialchars($b) ?>" <?= $filters['branch_name'] === $b ? 'selected' : '' ?>><?= htmlspecialchars($b) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="flex items-center gap-2 border border-slate-300 rounded px-2 py-1 bg-white focus-within:border-[#ce2216] focus-within:ring-1 focus-within:ring-[#ce2216] transition-all">
                <input type="text" name="as_of_date" value="<?= htmlspecialchars($filters['as_of_date'] ?? '') ?>" required class="date-formatter text-sm text-slate-800 font-medium outline-none cursor-pointer w-28 bg-slate-50 text-center" placeholder="As of">
            </div>
        </form>
    </div>

    <div class="overflow-x-auto">
        <div id="initialStateWrapper" class="<?= !$hasFiltersApplied ? '' : 'hidden' ?> flex flex-col items-center justify-center py-20 bg-white">
            <svg class="w-12 h-12 text-slate-300 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"/></svg>
            <p class="text-slate-500 font-bold text-base">Select from all required filters</p>
        </div>

        <div id="noDataWrapper" class="<?= ($hasFiltersApplied && empty($data)) ? '' : 'hidden' ?> text-center py-16 text-slate-500 font-bold text-sm bg-white">
            No asset records found. Please check the as of date.
        </div>
        
        <div id="tableWrapper" class="<?= empty($data) ? 'hidden' : '' ?>">
            <table class="w-full text-sm text-left whitespace-nowrap">
                <thead>
                    <tr class="border-b-2 border-slate-200 bg-[#ce2216]">
                        <th class="py-2.5 pl-5 pr-3 font-bold text-white uppercase tracking-wider text-xs">Codes</th>
                        <th class="py-2.5 px-3 font-bold text-white uppercase tracking-wider text-xs">Branches</th>
                        <th class="py-2.5 px-3 font-bold text-white uppercase tracking-wider text-xs">Asset Group</th>
                        <th class="py-2.5 px-3 font-bold text-white uppercase tracking-wider text-xs w-full">Description</th>
                        <th class="py-2.5 px-3 font-bold text-white uppercase tracking-wider text-xs text-right">Cost</th>
                        <th class="py-2.5 px-3 font-bold text-white uppercase tracking-wider text-xs text-right">Depreciation</th>
                        <th class="py-2.5 px-3 font-bold text-white uppercase tracking-wider text-xs text-right">Accu. Dep.</th>
                        <th class="py-2.5 px-3 font-bold text-white uppercase tracking-wider text-xs text-center">Life</th>
                        <th class="py-2.5 px-3 font-bold text-white uppercase tracking-wider text-xs text-right">Book Value</th>
                        <th class="py-2.5 px-3 font-bold text-white uppercase tracking-wider text-xs text-center">Start Date</th>
                        <th class="py-2.5 pl-3 pr-5 font-bold text-white uppercase tracking-wider text-xs text-center">End Date</th>
                    </tr>
                </thead>
                <tbody id="tableBody" class="divide-y divide-slate-100 font-medium text-slate-700">
                    <?php foreach ($data as $row): ?>
                        <?php $payload = htmlspecialchars(json_encode($row), ENT_QUOTES); ?>
                        <tr class="asset-row cursor-pointer" data-id="<?= (int)$row['asset_id'] ?>" data-asset='<?= $payload ?>'>
                            <td class="py-0 pl-5 pr-3 text-xs text-slate-900"><?= htmlspecialchars($row['system_asset_code']) ?></td>
                            <td class="py-0 px-3 text-xs"><?= htmlspecialchars($row['branch_name']) ?></td>
                            <td class="py-0 px-3 text-xs"><?= htmlspecialchars($row['group_name'] ?? '') ?></td>
                            <td class="py-0 px-3 truncate max-w-[200px] text-xs" title="<?= htmlspecialchars($row['description']) ?>"><?= htmlspecialchars($row['description']) ?></td>
                            <td class="py-0 px-3 text-right font-mono text-xs"><?= number_format($row['acquisition_cost'], 2) ?></td>
                            <td class="py-0 px-3 text-right font-mono text-slate-900"><?= number_format($row['period_depreciation_expense'], 2) ?></td>
                            <td class="py-0 px-3 text-right font-mono text-xs"><?= number_format($row['accumulated_depreciation'], 2) ?></td>
                            <td class="py-0 px-3 text-center font-bold text-xs"><?= $row['remaining_life'] ?></td>
                            <td class="py-0 px-3 text-right font-mono text-xs text-slate-900"><?= number_format($row['book_value'], 2) ?></td>
                            <td class="py-0 px-3 text-center text-slate-500 text-xs"><?= !empty($row['depreciation_start_date']) ? date('M j, Y', strtotime($row['depreciation_start_date'])) : '-' ?></td>
                            <td class="py-0 pl-3 pr-5 text-center text-slate-500 text-xs"><?= !empty($row['depreciation_end_date']) ? date('M j, Y', strtotime($row['depreciation_end_date'])) : '-' ?></td>
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


<?php include_once __DIR__ . '/../../src/includes/modals/view-asset-details.php'; ?>

<div id="exportHeaderTemplate" class="hidden">
    <?php include_once __DIR__ . '/../../src/includes/export-header.php'; ?>
</div>

<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
<script src="https://cdn.jsdelivr.net/npm/tom-select@2.3.1/dist/js/tom-select.complete.min.js"></script>

<!-- reuse import page's detail renderer (defines renderDeprDetails, setDeprEditMode, closeAssetDepreciationDetails) -->
<?php
    $assetBase = __DIR__ . '/../assets/js/';
    $assetFiles = [ 'asset-import.js', 'manage-assets.js', 'main.js' ];
    foreach ($assetFiles as $f) {
        $path = realpath($assetBase . $f);
        $ver  = ($path && file_exists($path)) ? '?v=' . filemtime($path) : '';
        echo "<script src=\"" . ASSET_URL . "js/" . $f . "$ver\"></script>\n";
    }
?>