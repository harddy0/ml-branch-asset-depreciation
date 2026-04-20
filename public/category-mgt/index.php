<?php
$pageTitle   = 'Manage Expense Types';
$currentPage = 'category-mgt';
require_once __DIR__ . '/../../src/includes/init.php';

// Flash messages can go here if needed, matching your system
?>

<div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-2">
    <div>
        <h1 class="text-1xl font-black text-slate-800 uppercase tracking-wide">Expense Types (Policies)</h1>
    </div>

    <div class="flex items-center gap-4">
        <button onclick="openAddModal()"
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
    <div class="relative max-w-full">
        <svg class="absolute left-3.5 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400 pointer-events-none"
             fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                d="M21 21l-4.35-4.35M17 11A6 6 0 115 11a6 6 0 0112 0z"/>
        </svg>
        <input type="text" id="searchInput" onkeyup="handleSearch()"
               placeholder="Search by expense name or category"
               class="w-full pl-10 pr-4 py-1.5 border-2 border-slate-100 focus:border-slate-300 rounded-xl
                      placeholder:text-slate-300 text-sm font-mono text-slate-700 outline-none bg-white transition-all">
    </div>
</div>

<div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead>
                <tr class="bg-[#ce2216] border-b border-slate-200">
                    <th class="text-center text-xs font-black text-white tracking-widest px-6 py-2">ID</th>
                    <th class="text-left text-xs font-black text-white tracking-widest px-6 py-2">Expense Name</th>
                    <th class="text-left text-xs font-black text-white tracking-widest px-6 py-2 pl-8">Category Type</th>
                    <th class="text-center text-xs font-black text-white tracking-widest px-6 py-2">Amortization</th>
                    <th class="text-center text-xs font-black text-white tracking-widest px-6 py-2">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100" id="expenseTypeTableBody">
                </tbody>
        </table>
    </div>
</div>

<div id="paginationControls" class="flex justify-end gap-2 mt-4"></div>

<?php
require_once __DIR__ . '/../../src/includes/modals/expense-type-add.php';
require_once __DIR__ . '/../../src/includes/modals/expense-type-edit.php';
require_once __DIR__ . '/../../src/includes/modals/expense-type-delete.php';
?>

<script src="<?= ASSET_URL ?>js/expense-types.js"></script>