<?php
$pageTitle   = 'Depreciation List';
$currentPage = 'depreciation-list';
require_once __DIR__ . '/../../src/includes/init.php';

// Simple placeholder content — the main layout (header + sidebar) is provided by init.php/layout
?>
<!-- Page Header -->
<div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-4">
    <div>
        <h1 class="text-1xl font-black text-slate-800 uppercase tracking-wide">ASSETS</h1>
    </div>
    <div class="flex items-center">
        <button id="depr-add-btn" title="Add Asset" onclick="openModal('modal-add-asset')"
            class="inline-flex items-center gap-2 bg-[#ce1126] hover:bg-red-700 active:bg-red-800
                    text-white text-xs font-black uppercase tracking-widest
                    px-1.5 py-1.5 rounded-xl shadow-sm">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
            </svg>
        </button>
    </div>
</div>

<?php require_once __DIR__ . '/../../src/includes/modals/add-asset.php'; ?>

<script src="<?= ASSET_URL ?>js/main.js"></script>
<script src="<?= ASSET_URL ?>js/depreciation-list.js"></script>

<!-- Asset Table -->
<div class="bg-white rounded-lg border border-slate-200 shadow-sm overflow-hidden">
    <div class="overflow-x-auto">
        <table id="depr-table" class="w-full text-sm">
            <colgroup id="depr-colgroup">
                <col style="width:11%" />
                <col style="width:20%" />
                <col style="width:10%" />
                <col style="width:12%" />
                <col style="width:12%" />
                <col style="width:10%" />
                <col style="width:10%" />
                <col style="width:8%" />
            </colgroup>
            <thead>
                <tr class="bg-[#ce2216] border-b border-slate-200">
                    <th class="text-center text-xs font-black text-white tracking-widest px-6 py-2 whitespace-nowrap">Serial number</th>
                    <th class="text-left text-xs font-black text-white tracking-widest px-6 py-2 whitespace-nowrap">Description</th>
                    <th class="text-center text-xs font-black text-white uppercase tracking-widest px-6 py-2 whitespace-nowrap">Item</th>
                    <th class="text-center text-xs font-black text-white uppercase tracking-widest px-6 py-2 whitespace-nowrap">Group code</th>
                    <th class="text-center text-xs font-black text-white uppercase tracking-widest px-6 py-2 whitespace-nowrap">Resource</th>
                    <th class="text-center text-xs font-black text-white uppercase tracking-widest px-6 py-2 whitespace-nowrap">Amount</th>
                    <th class="text-center text-xs font-black text-white uppercase tracking-widest px-6 py-2 whitespace-nowrap">Status</th>
                    <th class="text-center text-xs font-black text-white uppercase tracking-widest px-6 py-2 whitespace-nowrap">End date</th>
                </tr>
            </thead>
            <tbody>
                <!-- Rows will be populated here (server-side or via JS) -->
            </tbody>
        </table>
    </div>
</div>



