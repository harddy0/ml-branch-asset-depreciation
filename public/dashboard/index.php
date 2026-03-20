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