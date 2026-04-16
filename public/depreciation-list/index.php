<?php
$pageTitle   = 'Depreciation List';
$currentPage = 'depreciation-list';
require_once __DIR__ . '/../../src/includes/init.php';

require_once __DIR__ . '/../../src/classes/AssetClassificationService.php';

$classService  = new \App\AssetClassificationService($pdo);
$assetGroups   = $classService->getDropdownOptions();
// getDropdownOptions() returns: [['group_code' => '...', 'group_name' => '...'], ...]
?>

<!-- Page Header -->
<div class="flex flex-col gap-2 mb-1 min-w-0">
        <div class="flex items-center justify-between w-full min-w-0">
            <h1 class="text-1xl font-black text-slate-800 uppercase tracking-wide">ASSETS</h1>
            <div class="flex items-center gap-3 justify-end">
                <div class="flex items-center text-xs font-mono text-slate-600 shrink-0 -mt-4">
                    <div class="flex flex-col items-end">
                        <span class="text-xs mr-20">Filter by date added</span>
                        <div class="mt-1 flex items-center gap-2">
                            <input
                                type="date"
                                id="depr-date-from"
                                class="w-36 border border-slate-300 rounded-md px-3 py-1 font-mono text-sm text-slate-700"
                                title="Date from"
                            >

                            <input
                                type="date"
                                id="depr-date-to"
                                class="w-36 border border-slate-300 rounded-md px-3 py-1 font-mono text-sm text-slate-700"
                                title="Date to"
                            >
                        </div>
                    </div>
                </div>
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

        <div class="w-full min-w-0">
            <div class="flex flex-row items-center gap-2 w-full min-w-0 overflow-x-auto pt-6 -mt-5">
                <div class="flex-1 min-w-0">
                    <input
                        type="text"
                        id="depr-search"
                        placeholder="Search "
                        class="w-full border border-slate-300 rounded-md px-3 py-1 text-sm font-mono text-slate-700 min-w-0"
                    >
                </div>

                <div class="flex gap-2 flex-1 min-w-0">
                    <select
                        id="depr-group-filter"
                        class="flex-1 min-w-0 max-w-full truncate border border-slate-300 rounded-md px-3 py-1 text-sm font-mono text-slate-700"
                        style="min-width:0; max-width:100%; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;"
                    >
                        <option value="">All Group Codes</option>
                    </select>

                    <select
                        id="depr-branch-filter"
                        class="flex-1 min-w-0 max-w-full truncate border border-slate-300 rounded-md px-3 py-1 text-sm font-mono text-slate-700"
                        style="min-width:0; max-width:100%; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;"
                    >
                        <option value="">All Branches</option>
                    </select>
                </div>

                <div class="flex items-center gap-2 shrink-0">
                    <select
                        id="depr-status-filter"
                        class="border border-slate-300 rounded-md px-3 py-1 text-sm font-mono text-slate-700 "
                    >
                        <option value="">Status</option>
                        <option value="ACTIVE">Active</option>
                        <option value="DEPRECIATED">Depreciated</option>
                        <option value="SOLD">Sold</option>
                    </select>

                    <button
                        type="button"
                        id="depr-filter-reset"
                        class="inline-flex items-center justify-center px-3 py-1 text-sm font-mono font-bold uppercase tracking-wide border border-slate-300 rounded-md text-slate-700 hover:bg-slate-100"
                    >
                        Reset
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../src/includes/modals/add-asset.php'; ?>
<?php require_once __DIR__ . '/../../src/includes/modals/asset-ledger.php'; ?>

<script src="<?= ASSET_URL ?>js/main.js"></script>
<?php
    $deprJsPath = realpath(__DIR__ . '/../assets/js/depreciation-list.js');
    $deprJsVer = ($deprJsPath && file_exists($deprJsPath)) ? '?v=' . filemtime($deprJsPath) : '';
?>
<script src="<?= ASSET_URL ?>js/depreciation-list.js<?= $deprJsVer ?>"></script>
<script>
    // Injected by depreciation-list/index.php — consumed by depreciation-list.js
    window.__assetGroups = <?= json_encode($assetGroups, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
</script>

<script>
// Make group select show full "code - description" in dropdown, but display only the code when closed.
document.addEventListener('DOMContentLoaded', function () {
    const sel = document.getElementById('depr-group-filter');
    if (!sel) return;

    // store full text and code for each option
    Array.from(sel.options).forEach(opt => {
        const full = opt.textContent.trim();
        opt.dataset.full = full;
        // code assumed before ' - ' if present
        const parts = full.split(' - ');
        opt.dataset.code = parts[0] || full;
    });

    function restoreFull() {
        Array.from(sel.options).forEach(opt => opt.textContent = opt.dataset.full);
    }

    function showSelectedCode() {
        Array.from(sel.options).forEach(opt => {
            if (opt.selected) opt.textContent = opt.dataset.code;
            else opt.textContent = opt.dataset.full;
        });
    }

    // when opening/selecting, restore full list so user sees descriptions
    sel.addEventListener('focus', restoreFull);
    sel.addEventListener('mousedown', restoreFull);

    // when selection changes or loses focus, display code only for selected
    sel.addEventListener('change', showSelectedCode);
    sel.addEventListener('blur', showSelectedCode);

    // apply initial state
    showSelectedCode();
});
</script>

<!-- Asset Table -->
    <div class="bg-white rounded-lg border border-slate-200 shadow-sm overflow-hidden">
    <div id="depr-table-wrapper" class="overflow-x-auto">
        <table id="depr-table" class="w-full text-sm">
            <colgroup id="depr-colgroup">
                <col style="width:11%" />
                <col style="width:20%" />
                <col style="width:10%" />
                <col style="width:12%" />
                <col style="width:12%" />
                <col style="width:6%" />
                <col style="width:10%" />
                <col style="width:8%" />
                <col style="width:7%" />
                <col style="width:4%" />
                <col style="width:6%" />
            </colgroup>
            <thead>
                <tr class="bg-[#ce2216] border-b border-slate-200">
                    <th class="text-center text-xs font-black text-white tracking-widest px-6 py-2 whitespace-nowrap">
                        <button type="button" class="depr-sort inline-flex items-center gap-1" data-sort="serial_number">Serial number </button>
                    </th>
                    <th class="text-left text-xs font-black text-white tracking-widest px-6 py-2 whitespace-nowrap">
                        <button type="button" class="depr-sort inline-flex items-center gap-1" data-sort="description">Description </button>
                    </th>
                    <th class="text-center text-xs font-black text-white uppercase tracking-widest px-6 py-2 whitespace-nowrap">
                        <button type="button" class="depr-sort inline-flex items-center gap-1" data-sort="item_code">Item </button>
                    </th>
                    <th class="text-center text-xs font-black text-white uppercase tracking-widest px-6 py-2 whitespace-nowrap">
                        <button type="button" class="depr-sort inline-flex items-center gap-1" data-sort="group_code">Group code </button>
                    </th>
                    <th class="text-left text-xs font-black text-white uppercase tracking-widest px-6 py-2 whitespace-nowrap">
                        <button type="button" class="depr-sort inline-flex items-center gap-1" data-sort="branch_name">Branch </button>
                    </th>
                    <th class="text-center text-xs font-black text-white uppercase tracking-widest px-6 py-2 whitespace-nowrap">
                        <button type="button" class="depr-sort inline-flex items-center gap-1" data-sort="uploaded_by">Uploaded by</button>
                    </th>
                    <th class="text-right text-xs font-black text-white uppercase tracking-widest px-6 py-2 whitespace-nowrap">
                        <button type="button" class="depr-sort inline-flex items-center gap-1" data-sort="acquisition_cost">Amount</button>
                    </th>
                    <th class="text-right text-xs font-black text-white uppercase tracking-widest px-6 py-2 whitespace-nowrap">
                        <button type="button" class="depr-sort inline-flex items-center gap-1" data-sort="monthly_depreciation">Monthly</button>
                    </th>
                    <th class="text-center text-xs font-black text-white uppercase tracking-widest px-6 py-2 whitespace-nowrap">
                        <button type="button" class="depr-sort inline-flex items-center gap-1" data-sort="status">Status </button>
                    </th>
                    <th class="text-center text-xs font-black text-white uppercase tracking-widest px-6 py-2 whitespace-nowrap">
                        <button type="button" class="depr-sort inline-flex items-center gap-1" data-sort="depreciation_end_date">End date </button>
                    </th>
                    <th class="text-center text-xs font-black text-white uppercase tracking-widest px-6 py-2 whitespace-nowrap">
                        <button type="button" class="depr-sort inline-flex items-center gap-1" data-sort="created_at">Date Added </button>
                    </th>
                </tr>
            </thead>
            <tbody id="depr-table-body">
                <!-- Rows will be populated here (server-side or via JS) -->
            </tbody>
        </table>
    </div>
     <!-- 
    <div class="border-t border-slate-200 bg-slate-50 px-2 py-1 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
        <div id="depr-page-meta" class="text-xs font-mono text-slate-500">
            Page 1 of 1 • 0 records
        </div>

        <div class="flex items-center gap-2">
            <button
                type="button"
                id="depr-prev-page"
                class="px-3 py-1 text-xs font-mono tracking-wide border border-slate-300 rounded-md text-slate-700 hover:bg-slate-100 disabled:opacity-50 disabled:cursor-not-allowed"
            >
                Prev
            </button>

            <button
                type="button"
                id="depr-next-page"
                class="px-3 py-1 text-xs font-mono tracking-wide border border-slate-300 rounded-md text-slate-700 hover:bg-slate-100 disabled:opacity-50 disabled:cursor-not-allowed"
            >
                Next
            </button>
        </div>
    </div>
     -->
</div>

<div id="depr-list-config"
     data-api-url="<?= BASE_URL ?>/public/api/get_depreciation_list.php"
     data-per-page="50"
     class="hidden"></div>

<div id="depr-ledger-config"
    data-api-url="<?= BASE_URL ?>/public/api/get_asset_ledger.php"
    data-generated-by="<?= htmlspecialchars($_SESSION['full_name'] ?? 'User') ?>"
    class="hidden"></div>



