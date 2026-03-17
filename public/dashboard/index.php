<?php
$pageTitle   = 'Dashboard';
$currentPage = 'dashboard';
require_once __DIR__ . '/../../src/includes/init.php';
?>
<div class="mb-6">
    <h1 class="text-2xl font-black text-slate-800 uppercase tracking-tight">Dashboard</h1>
    <p class="text-sm text-slate-500 mt-1">
        Welcome back, <strong><?= htmlspecialchars($_SESSION['full_name'] ?? '') ?></strong>
    </p>
</div>
<div class="grid grid-cols-1 md:grid-cols-3 gap-6">
    <div class="bg-white border border-slate-200 border-t-4 border-t-red-600 rounded-xl p-6 shadow-sm hover:shadow-md transition-shadow">
        <p class="text-xs font-bold text-slate-500 uppercase tracking-widest mb-2">Ready</p>
        <p class="text-sm text-slate-600">Your application is set up. Start adding your features here.</p>
    </div>
</div>
<script src="<?= ASSET_URL ?>js/main.js"></script>
