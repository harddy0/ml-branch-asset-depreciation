<?php
$pageTitle   = 'Issuance Report';
$currentPage = 'issuance-report';
require_once __DIR__ . '/../../src/includes/init.php';

// Placeholder variables. Option lists will be loaded via API calls in JS.
$zones = $regions = $mainZones = $branches = $costCenters = $categories = [];
$data = [];
$filters = [];
$rawFilters = [];
$hasFiltersApplied = false;
?>

<link href="https://cdn.jsdelivr.net/npm/tom-select@2.3.1/dist/css/tom-select.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
<style>
    /* Reuse Assets page select and table styles */
    .ts-wrapper .ts-control { border: 1px solid #cbd5e1 !important; border-radius: 0.25rem !important; padding: 0.375rem 0.625rem !important; font-size: 0.875rem !important; font-weight: 500 !important; color: #1e293b !important; box-shadow: none !important; background-color: #ffffff !important; height: 34px !important; min-height: 34px !important; max-height: 34px !important; display: flex !important; align-items: center !important; flex-wrap: nowrap !important; overflow: hidden !important; position: relative !important; }
    .ts-wrapper.focus .ts-control { border-color: #ce2216 !important; box-shadow: 0 0 0 1px #ce2216 !important; }
    .ts-dropdown { font-size: 0.875rem !important; border-radius: 0.25rem !important; border: 1px solid #cbd5e1 !important; box-shadow: 0 4px 6px -1px rgb(0 0 0 / 0.1) !important; z-index: 9999 !important; }
    .ts-wrapper { width: 100% !important; }
    /* date input small monospace */
    .date-formatter { font-size: 0.75rem !important; font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, 'Roboto Mono', 'Courier New', monospace !important; font-weight: 600 !important; }
    /* table row styles */
    #tableWrapper table tbody tr:nth-child(even) { background-color: #f8fafc; }
    #tableWrapper table tbody tr:hover { background-color: #f1f5f9; transition: background-color 0.15s ease; }
    #tableWrapper { transition: opacity 0.2s ease-in-out; }
</style>

<div class="mb-2 flex justify-between items-end">
    <div>
        <h1 class="text-1xl font-black text-slate-800 uppercase tracking-wide">Issuance Report</h1>
    </div>
    <div class="flex justify-end items-end gap-2">
        <div class="flex items-center text-xs font-mono text-slate-600 shrink-0 -mt-5">
            <div class="flex flex-col items-end">
                <span class="text-xs">Filter by date issued</span>
                <div class="mt-1 flex items-center gap-2">
                    <input
                        type="date"
                        id="issuance-date-from"
                        class="w-36 border border-slate-300 rounded-md px-2.5 py-1 font-mono text-sm text-slate-700"
                        title="Date from"
                    >

                    <input
                        type="date"
                        id="issuance-date-to"
                        class="w-36 border border-slate-300 rounded-md px-2.5 py-1 font-mono text-sm text-slate-700"
                        title="Date to"
                    >
                </div>
            </div>
        </div>
        <button id="exportToggleBtn" type="button" aria-expanded="false" aria-haspopup="true" class="border border-slate-200 text-slate-800 hover:bg-[#ce2216] hover:text-white px-4 py-1 rounded-md text-sm font-mono flex items-center gap-2 transition-colors shadow-sm">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v2a2 2 0 002 2h12a2 2 0 002-2v-2M7 10l5 5m0 0l5-5m-5 5V4"/></svg>
            Export
        </button>

        <div id="exportMenu" class="origin-top-right absolute right-0 mt-2 w-35 rounded-md shadow-lg bg-white ring-1 ring-black ring-opacity-5 hidden z-50">
            <div class="py-1">
                <button id="exportExcelBtn" type="button" class="w-full text-left px-6 py-2 text-sm text-slate-700 hover:bg-slate-100 flex items-center gap-2">
                    <svg class="w-4 h-4 text-green-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M3 7v10a2 2 0 0 0 2 2h14V5H5a2 2 0 0 0-2 2z" stroke-linecap="round" stroke-linejoin="round"/></svg>
                    Excel
                </button>
                <button id="exportPrintBtn" type="button" class="w-full text-left px-6 py-2 text-sm text-slate-700 hover:bg-slate-100 flex items-center gap-2">
                    <svg class="w-4 h-4 text-slate-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M6 9V2h12v7" stroke-linecap="round" stroke-linejoin="round"/></svg>
                    Print
                </button>
            </div>
        </div>
    </div>
</div>

<div class="bg-white rounded-lg shadow-sm border border-slate-200 overflow-hidden">
    <div class="bg-slate-50 border-b border-slate-200 px-3 py-2">
       <form id="filterForm" 
            data-api-url="<?= BASE_URL ?>/public/api/get_issuance_report.php" 
            data-export-url="<?= BASE_URL ?>/public/actions/export_issuance_report.php"
            class="m-0 flex flex-row items-center gap-4 w-full">

            <div class="flex flex-1 items-center gap-2">
                <select name="zone" id="zoneSelect" class="flex-1 min-w-0 outline-none text-sm">
                    <option value="">Zone</option>
                    <option value="__ALL__">All Zones</option>
                </select>

                <div class="h-4 w-px bg-slate-200"></div>
                <select name="region" id="regionSelect" class="flex-1 min-w-0 outline-none text-sm">
                    <option value="">Region</option>
                    <option value="__ALL__">All Regions</option>
                </select>

                <div class="h-4 w-px bg-slate-200"></div>
                <select name="branch_name" id="branchSelect" class="flex-[2] min-w-0 outline-none text-sm font-semibold">
                    <option value="">Branch Name</option>
                    <option value="__ALL__">All Branches</option>
                </select>

                <div class="h-4 w-px bg-slate-200"></div>
                <select name="product_category" id="categorySelect" class="flex-1 min-w-0 outline-none text-sm">
                    <option value="">Product Category</option>
                    <option value="__ALL__">All Categories</option>
                </select>
            </div>

            <div class="flex items-center">
                <button id="clearFiltersBtn" type="button" title="Clear filters" class="ml-0 px-3 py-1.5 text-xs border border-slate-300 rounded bg-white hover:bg-slate-100 text-slate-600 font-mono">
                    Clear
                </button>
            </div>
        </form>
    </div>

    <div class="overflow-x-auto">
        <div id="initialStateWrapper" class="<?= !$hasFiltersApplied ? '' : 'hidden' ?> flex flex-col items-center justify-center py-20 bg-white">
            <svg class="w-12 h-12 text-slate-300 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"/></svg>
            <p class="text-slate-500 font-bold text-base">Select filters to load Issuance Report</p>
        </div>

        <div id="noDataWrapper" class="<?= ($hasFiltersApplied && empty($data)) ? '' : 'hidden' ?> text-center py-16 text-slate-500 font-bold text-sm bg-white">
            No issuance records found. Please try different filters.
        </div>
        
        <div id="tableWrapper" class="<?= empty($data) ? 'hidden' : '' ?>">
            <table class="w-full text-sm text-left whitespace-nowrap">
                <thead>
                    <tr class="border-b-2 border-slate-200 bg-[#ce2216]">
                        <th class="py-2.5 pl-5 pr-3 font-bold text-white uppercase tracking-wider text-xs">Date Issued</th>
                        <th class="py-2.5 px-3 font-bold text-white uppercase tracking-wider text-xs">Issuance #</th>
                        <th class="py-2.5 px-3 font-bold text-white uppercase tracking-wider text-xs">Item Code</th>
                        <th class="py-2.5 px-3 font-bold text-white uppercase tracking-wider text-xs w-full">Item Description</th>
                        <th class="py-2.5 px-3 font-bold text-white uppercase tracking-wider text-xs text-right">Quantity</th>
                        <th class="py-2.5 px-3 font-bold text-white uppercase tracking-wider text-xs">UoM</th>
                        <th class="py-2.5 px-3 font-bold text-white uppercase tracking-wider text-xs">Cost Center</th>
                        <th class="py-2.5 px-3 font-bold text-white uppercase tracking-wider text-xs text-right">Unit Cost</th>
                        <th class="py-2.5 px-3 font-bold text-white uppercase tracking-wider text-xs text-right">Total Amount</th>
                        <th class="py-2.5 px-3 font-bold text-white uppercase tracking-wider text-xs">Product Category</th>
                        <th class="py-2.5 px-3 font-bold text-white uppercase tracking-wider text-xs">Zone</th>
                        <th class="py-2.5 px-3 font-bold text-white uppercase tracking-wider text-xs">Region</th>
                        <th class="py-2.5 pl-3 pr-5 font-bold text-white uppercase tracking-wider text-xs">Branch Name</th>
                    </tr>
                </thead>
                <tbody id="tableBody" class="divide-y divide-slate-100 font-medium text-slate-700">
                    <!-- rows will be injected by client JS -->
                </tbody>
            </table>
        </div>
    </div>
</div>

<div id="exportHeaderTemplate" class="hidden">
    <?php include_once __DIR__ . '/../../src/includes/export-header.php'; ?>
</div>

<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
<script src="https://cdn.jsdelivr.net/npm/tom-select@2.3.1/dist/js/tom-select.complete.min.js"></script>

<!-- load existing site JS helpers if available -->
<?php
    $assetBase = __DIR__ . '/../assets/js/';
    $assetFiles = [ 'issuance-report.js', 'main.js' ];
    foreach ($assetFiles as $f) {
        $path = realpath($assetBase . $f);
        $ver  = ($path && file_exists($path)) ? '?v=' . filemtime($path) : '';
        echo "<script src=\"" . ASSET_URL . "js/" . $f . "$ver\"></script>\n";
    }
?>
<?php
// End of file
?>
