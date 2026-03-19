<?php
$pageTitle   = 'Financial Dashboard';
$currentPage = 'dashboard';
require_once __DIR__ . '/../../src/includes/init.php';
?>

<div id="dashboardContainer" data-api-url="<?= BASE_URL ?>/public/api/get_dashboard.php">
    
    <div class="mb-6 flex justify-between items-end">
        <div>
            <h1 class="text-2xl font-black text-slate-800 uppercase tracking-tight">Financial Dashboard</h1>
            <p class="text-sm text-slate-500 mt-0.5">Visualized breakdown of asset valuations across the network.</p>
        </div>
        <button id="refreshDashboardBtn" class="bg-white border border-slate-200 text-slate-600 hover:text-red-600 hover:border-red-200 px-4 py-2 rounded-md text-sm font-bold flex items-center gap-2 transition-colors shadow-sm">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
            Sync Data
        </button>
    </div>

    <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6 relative mb-6">
        <h2 class="text-sm font-bold text-slate-500 uppercase tracking-wider mb-4 border-b border-slate-100 pb-2">Financial Health by Zone (Cost vs Current Value)</h2>
        <div id="loader-zone" class="absolute inset-0 bg-white/90 flex items-center justify-center z-10 hidden rounded-xl">
            <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-red-600"></div>
        </div>
        <div class="relative w-full" style="height: 350px;">
            <canvas id="zoneChart"></canvas>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6 relative">
            <h2 class="text-sm font-bold text-slate-500 uppercase tracking-wider mb-4 border-b border-slate-100 pb-2">Asset Value by Category</h2>
            <div id="loader-category" class="absolute inset-0 bg-white/90 flex items-center justify-center z-10 hidden rounded-xl">
                <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-red-600"></div>
            </div>
            <div class="relative h-72 w-full">
                <canvas id="categoryChart"></canvas>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6 relative">
            <h2 class="text-sm font-bold text-slate-500 uppercase tracking-wider mb-4 border-b border-slate-100 pb-2">Top 5 Branches by Asset Value</h2>
            <div id="loader-branch" class="absolute inset-0 bg-white/90 flex items-center justify-center z-10 hidden rounded-xl">
                <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-red-600"></div>
            </div>
            <div class="relative h-72 w-full">
                <canvas id="branchChart"></canvas>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="<?= ASSET_URL ?>js/dashboard.js"></script>
<script src="<?= ASSET_URL ?>js/main.js"></script>