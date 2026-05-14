<?php
$pageTitle   = 'Financial Dashboard';
$currentPage = 'dashboard';
require_once __DIR__ . '/../../src/includes/init.php';
?>

<style>
    :root {
        --dash-gap: 16px;
        --dash-card-radius: 10px;
        --dash-border: #e2e8f0;
    }

    #dashboardContainer.dashboard-viewport {
        min-height: calc(100vh - 150px);
        display: flex;
        flex-direction: column;
        gap: var(--dash-gap);
    }

    #dashboardContainer .asset-overview-row {
        margin-bottom: 0;
        gap: var(--dash-gap);
    }

    #dashboardContainer .overview-item {
        border-radius: var(--dash-card-radius);
        padding: 12px;
        min-height: 76px;
    }

    #dashboardContainer .dashboard-panels {
        flex: 1;
        min-height: 0;
        gap: var(--dash-gap);
        margin-bottom: 0;
    }

    #dashboardContainer .dashboard-panel {
        height: 76px;
        min-height: 0;
        border-radius: var(--dash-card-radius);
        overflow: hidden;
        text-align: center;
    }

    #dashboardContainer .dashboard-panel .panel-list,
    #dashboardContainer .dashboard-panel .panel-subline {
        display: none;
    }

    #dashboardContainer .section-header-row {
        display: grid;
        grid-template-columns: repeat(3, minmax(0, 1fr));
        gap: var(--dash-gap);
    }

    @media (max-width: 1200px) {
        #dashboardContainer.dashboard-viewport {
            min-height: auto;
        }

        #dashboardContainer .dashboard-panels {
            flex-wrap: wrap;
        }

        #dashboardContainer .dashboard-panel {
            height: auto;
            min-height: 300px;
        }
    }
</style>

<div id="dashboardContainer" class="dashboard-viewport" data-api-url="<?= BASE_URL ?>/public/api/get_dashboard.php">
    
    <div class="mb-6 flex justify-between items-end">
        <div>
            <h1 class="text-1xl font-black text-slate-800 uppercase tracking-wide">Dashboard</h1>
        </div>
    </div>

    <div class="flex text-md font-black text-slate-800 uppercase tracking-wide mb-0">Assets</div>

    <!-- Row 1: KPI Cards -->
    <div class="asset-overview-row flex gap-6 mb-6">
        <div class="overview-item bg-white rounded shadow p-4 flex-1 text-center border-t-2 border-[#ce1126]">
            <div class="text-xs text-slate-500 uppercase mb-1">Total Assets</div>
            <div id="overviewTotalCost" class="text-xl font-bold text-slate-800">₱0.00</div>
        </div>
        <div class="overview-item bg-white rounded shadow p-4 flex-1 text-center border-t-2 border-[#ce1126]">
            <div class="text-xs text-slate-500 uppercase mb-1">Active Assets</div>
            <div id="overviewDepreciation" class="text-xl font-bold text-slate-800">₱0.00</div>
        </div>
        <div class="overview-item bg-white rounded shadow p-4 flex-1 text-center border-t-2 border-[#ce1126]">
            <div class="text-xs text-slate-500 uppercase mb-1">Depreciated Assets</div>
            <div id="overviewAccumulated" class="text-xl font-bold text-slate-800">₱0.00</div>
        </div>
        <div class="overview-item bg-white rounded shadow p-4 flex-1 text-center border-t-2 border-[#ce1126] ">
            <div class="text-xs text-slate-500 uppercase mb-1">Sold Assets</div>
            <div id="overviewBookValue" class="text-xl font-bold text-slate-800">₱0.00</div>
        </div>
    </div>

    <div class="section-header-row mb-0">
        <div class="text-md font-black text-slate-800 uppercase tracking-wide">Depreciation</div>
        <div></div>
        <div class="text-md font-black text-slate-800 uppercase tracking-wide">Issuance</div>
    </div>

    <!-- Row 2: Ongoing | Closed | Category -->
    <div class="dashboard-panels flex gap-6 mb-2">

        <!-- Running Depreciation -->
        <div class="dashboard-panel bg-white rounded shadow p-4 flex-1 min-w-0 flex flex-col border-t-2 border-[#ce1126]">
            <div class="text-xs text-slate-500 uppercase mb-1">Running Depreciation</div>
            <div id="ongoingCount" class="text-xl font-bold text-slate-800">—</div>
            <div id="ongoingCost" class="panel-subline text-xs text-slate-400 font-mono mb-3">—</div>
            <div id="ongoingList" class="panel-list space-y-1 overflow-y-auto flex-1 pr-1"></div>
        </div>

        <!-- Fully Depreciated -->
        <div class="dashboard-panel bg-white rounded shadow p-4 flex-1 min-w-0 flex flex-col border-t-2 border-[#ce1126]">
            <div class="text-xs text-slate-500 uppercase mb-1">Fully Depreciated</div>
            <div id="closedCount" class="text-xl font-bold text-slate-800">—</div>
            <div id="closedCost" class="panel-subline text-xs text-slate-400 font-mono mb-3">—</div>
            <div id="closedList" class="panel-list space-y-1 overflow-y-auto flex-1 pr-1"></div>
        </div>

        <!-- Total Issuance -->
        <div class="dashboard-panel bg-white rounded shadow p-4 flex-1 min-w-0 flex flex-col border-t-2 border-[#ce1126]">
            <div class="text-xs text-slate-500 uppercase mb-1">Total Issuance</div>
            <div id="categoryCount" class="text-xl font-bold text-slate-800">—</div>
            <div class="panel-subline text-xs text-slate-400 font-mono mb-0">&nbsp;</div>
            <div id="categoryList" class="panel-list overflow-y-auto flex-1 pr-1"></div>
        </div>

    </div>

</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="<?= ASSET_URL ?>js/dashboard.js"></script>
<script src="<?= ASSET_URL ?>js/main.js"></script>