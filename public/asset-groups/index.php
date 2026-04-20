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
            <tbody class="divide-y divide-slate-100">
                <tr class="hover:bg-slate-50 transition-colors">
                    <td class="px-6 py-3 text-sm font-medium text-slate-700">Branch Laptops</td>
                    <td class="px-6 py-3 text-sm font-medium text-slate-700">IT Equipment</td>
                    <td class="px-6 py-3 text-sm font-medium text-slate-700">36</td>
                    <td class="px-6 py-3 text-sm font-medium text-slate-700">10010 - IT Hardware</td>
                    <td class="px-6 py-3 text-sm font-medium text-slate-700">50010 - Depr Exp IT</td>
                    <td class="px-6 py-3 text-right text-sm">
                        <button onclick="openModal('asset-group-edit-modal')" class="inline-flex items-center justify-center w-8 h-8 bg-white hover:bg-slate-50 border border-slate-200 text-slate-700 rounded-lg shadow-sm transition" title="Edit">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5M18.5 2.5a2.121 2.121 0 113 3L12 15l-4 1 1-4 9.5-9.5z"/>
                            </svg>
                        </button>
                        <button onclick="openModal('asset-group-delete-modal')" class="inline-flex items-center justify-center w-8 h-8 ml-1 bg-white hover:bg-red-50 border border-slate-200 text-red-600 rounded-lg shadow-sm transition" title="Delete">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6M9 7V4a1 1 0 011-1h4a1 1 0 011 1v3m-7 0h8"/>
                            </svg>
                        </button>
                    </td>
                </tr>
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