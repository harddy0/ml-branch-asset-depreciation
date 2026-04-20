<?php
// Layout handled by init
require_once __DIR__ . '/../../src/includes/init.php';
?>

<div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-4">
    <div>
        <h1 class="text-1xl font-black text-slate-800 uppercase tracking-wide">Asset Groups</h1>
        <p class="text-sm font-medium text-slate-500 mt-1">Manage asset grouping, policy linkage, and GL mapping</p>
    </div>

    <div class="flex items-center gap-4">
        <button type="button" onclick="openModal('asset-group-add-modal')"
            class="inline-flex items-center gap-2 bg-[#ce1126] hover:bg-red-700 active:bg-red-800
                   text-white text-xs font-black uppercase tracking-widest
                   px-4 py-2 rounded-xl shadow-md shadow-slate-200 hover:shadow-md transition-all">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/>
            </svg>
            Add Asset Group
        </button>
    </div>
</div>

<div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
    <div class="overflow-x-auto">
        <table id="assetGroupsTable" class="w-full text-sm">
            <thead>
                <tr class="bg-[#ce2216] border-b border-slate-200">
                    <th class="text-left text-xs font-black text-white uppercase tracking-widest px-6 py-2">Group Name</th>
                    <th class="text-left text-xs font-black text-white uppercase tracking-widest px-6 py-2">Linked Policy</th>
                    <th class="text-left text-xs font-black text-white uppercase tracking-widest px-6 py-2">Actual Months</th>
                    <th class="text-left text-xs font-black text-white uppercase tracking-widest px-6 py-2">Asset GL Code</th>
                    <th class="text-left text-xs font-black text-white uppercase tracking-widest px-6 py-2">Expense GL Code</th>
                    <th class="text-right text-xs font-black text-white uppercase tracking-widest px-6 py-2">Actions</th>
                </tr>
            </thead>
            <tbody id="assetGroupsTbody" class="divide-y divide-slate-100">
                <!-- Rows will be populated client-side via ../api/get_asset_groups.php -->
            </tbody>
        </table>
    </div>
</div>

<?php
// Include Modals
require_once __DIR__ . '/../../src/includes/modals/asset-group-add.php';
require_once __DIR__ . '/../../src/includes/modals/asset-group-edit.php';
require_once __DIR__ . '/../../src/includes/modals/asset-group-delete.php';
?>

<script src="../assets/js/asset-groups.js"></script>