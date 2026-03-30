<?php
$pageTitle   = 'Financial Dashboard';
$currentPage = 'dashboard';
require_once __DIR__ . '/../../src/includes/init.php';
?>

<div id="dashboardContainer" data-api-url="<?= BASE_URL ?>/public/api/get_dashboard.php">
    
    <div class="mb-6 flex justify-between items-end">
        <div>
            <h1 class="text-1xl font-black text-slate-800 uppercase tracking-wide">Dashboard</h1>
        </div>
    </div>

    <!-- Row 1: KPI Cards -->
    <div class="asset-overview-row flex gap-6 mb-6">
        <div class="overview-item bg-white rounded shadow p-4 flex-1 text-center border-t-2 border-[#ce1126]">
            <div class="text-xs text-slate-500 uppercase mb-1">Total Cost</div>
            <div id="overviewTotalCost" class="text-xl font-bold text-slate-800">₱0.00</div>
        </div>
        <div class="overview-item bg-white rounded shadow p-4 flex-1 text-center border-t-2 border-[#ce1126]">
            <div class="text-xs text-slate-500 uppercase mb-1">Depreciation Per Month</div>
            <div id="overviewDepreciation" class="text-xl font-bold text-slate-800">₱0.00</div>
        </div>
        <div class="overview-item bg-white rounded shadow p-4 flex-1 text-center border-t-2 border-[#ce1126]">
            <div class="text-xs text-slate-500 uppercase mb-1">Accumulated Depreciation</div>
            <div id="overviewAccumulated" class="text-xl font-bold text-slate-800">₱0.00</div>
        </div>
        <div class="overview-item bg-white rounded shadow p-4 flex-1 text-center border-t-2 border-[#ce1126] ">
            <div class="text-xs text-slate-500 uppercase mb-1">Book Value</div>
            <div id="overviewBookValue" class="text-xl font-bold text-slate-800">₱0.00</div>
        </div>
    </div>

    <!-- Row 2: Ongoing | Closed | Category -->
    <div class="flex gap-6 mb-2">

        <!-- Ongoing Assets -->
        <div class="bg-white rounded shadow p-4 flex-1 min-w-0 flex flex-col border-t-2 border-[#ce1126]" style="height: 370px;">
            <div class="text-xs text-slate-500 uppercase mb-3 font-semibold tracking-wide">Ongoing Assets</div>
            <div id="ongoingCount" class="text-2xl font-black text-slate-800 mb-1">—</div>
            <div id="ongoingCost" class="text-xs text-slate-400 font-mono mb-3">—</div>
            <div id="ongoingList" class="space-y-1 overflow-y-auto flex-1 pr-1"></div>
        </div>

        <!-- Closed Assets -->
        <div class="bg-white rounded shadow p-4 flex-1 min-w-0 flex flex-col border-t-2 border-[#ce1126]" style="height: 370px;">
            <div class="text-xs text-slate-500 uppercase mb-3 font-semibold tracking-wide">Closed Assets</div>
            <div id="closedCount" class="text-2xl font-black text-slate-800 mb-1">—</div>
            <div id="closedCost" class="text-xs text-slate-400 font-mono mb-3">—</div>
            <div id="closedList" class="space-y-1 overflow-y-auto flex-1 pr-1"></div>
        </div>

        <!-- Category Breakdown -->
        <div class="bg-white rounded shadow p-4 flex-1 min-w-0 flex flex-col border-t-2 border-[#ce1126]" style="height: 370px;">
            <div class="text-xs text-slate-500 uppercase mb-3 font-semibold tracking-wide">Category</div>
            <div id="categoryCount" class="text-2xl font-black text-slate-800 mb-0">—</div>
            <div class="text-xs text-slate-400 font-mono mb-0">&nbsp;</div>
            <div id="categoryList" class="overflow-y-auto flex-1 pr-1"></div>
        </div>

    </div>

</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="<?= ASSET_URL ?>js/dashboard.js"></script>
<script src="<?= ASSET_URL ?>js/main.js"></script>