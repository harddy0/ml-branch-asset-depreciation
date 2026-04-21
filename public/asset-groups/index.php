<?php
// Layout handled by init
require_once __DIR__ . '/../../src/includes/init.php';
?>

<div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-4">
    <div>
        <h1 class="text-1xl font-black text-slate-800 uppercase tracking-wide">Asset Group</h1>
    </div>

    <div class="flex items-center gap-4">
        <button type="button" onclick="openModal('asset-group-add-modal')"
            class="inline-flex items-center gap-2 bg-[#ce1126] hover:bg-red-700 active:bg-red-800
                   text-white text-xs font-black uppercase tracking-widest
                   px-2 py-2 rounded-xl shadow-md shadow-slate-200 hover:shadow-md transition-all">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/>
            </svg>
        </button>
    </div>
</div>

<div class="mb-4 mt-4"> 
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 items-end">
       <div class="relative w-full">
            <svg class="absolute left-3.5 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400 pointer-events-none"
                fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M21 21l-4.35-4.35M17 11A6 6 0 115 11a6 6 0 0112 0z"/>
            </svg>
            <input type="text" id="search-input"
                placeholder="Search by Group Name or GL Code"
                class="w-full pl-10 pr-4 py-1.5 border-2 border-slate-100 focus:border-slate-300 rounded-xl
                        placeholder:text-slate-300 text-sm font-mono text-slate-700 outline-none bg-white transition-all">
        </div>

         <div class="w-full">
            <label for="filter-category-type" class="text-xs font-mono text-slate-400 block mb-2 sm:mb-0 pl-5">Filter by Expense Type</label>
            <div class="relative">
                <input type="text" id="filter-category-type" autocomplete="off"
                    placeholder="Type (e.g. Building)"
                    class="w-full font-mono  placeholder:text-slate-300 placeholder:font-mono placeholder:text-sm pr-9 px-3 py-1.5 border-2 border-slate-100 focus:border-slate-300 rounded-xl text-sm text-slate-700 bg-white">
                <button type="button" id="filter-category-clear" aria-label="Clear filter"
                    class="hidden absolute right-2 top-1/2 -translate-y-1/2 w-5 h-5 inline-flex items-center justify-center text-slate-400 hover:text-slate-700 rounded-md">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 6l12 12M18 6L6 18" />
                    </svg>
                </button>
                <div id="filter-category-suggestions"
                    class="hidden font-mono absolute z-30 mt-1 w-full bg-white border border-slate-200 rounded-xl shadow-lg max-h-56 overflow-auto"></div>
            </div>
        </div>
    </div>
</div>

<div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
    <div class="overflow-x-auto">
        <table id="assetGroupsTable" class="w-full text-sm table-fixed">
            <colgroup>
                <col class="w-[20%]">
                <col class="w-[26%]">
                <col class="w-[12%]">
                <col class="w-[16%]">
                <col class="w-[16%]">
                <col class="w-[10%]">
            </colgroup>
            <thead>
                <tr class="bg-[#ce2216] border-b border-slate-200">
                    <th class="text-left text-xs font-black text-white tracking-widest px-6 py-2">Group Name</th>
                    <th class="text-left text-xs font-black text-white tracking-widest px-6 py-2">Expense Type</th>
                    <th class="text-center text-xs font-black text-white tracking-widest px-6 py-2 whitespace-nowrap">Actual Months</th>
                    <th class="text-center text-xs font-black text-white tracking-widest px-6 py-2">Asset GL Code</th>
                    <th class="text-center text-xs font-black text-white tracking-widest px-6 py-2">Expense GL Code</th>
                    <th class="text-center text-xs font-black text-white tracking-widest px-6 py-2">Actions</th>
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