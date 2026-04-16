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

<!-- Asset Table -->
<div class="bg-white rounded-lg border border-slate-200 shadow-sm overflow-hidden">
    <div class="border-b border-slate-200 bg-slate-50 px-4 py-3">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-3">
            <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-2 w-full md:w-auto">
                <input
                    type="text"
                    id="depr-search"
                    placeholder="Search serial, description, item, group, branch, uploader..."
                    class="w-full sm:w-80 border border-slate-300 rounded-md px-3 py-2 text-sm text-slate-700 focus:outline-none focus:ring-2 focus:ring-red-500"
                >

                <select
                    id="depr-group-filter"
                    class="w-full sm:w-56 border border-slate-300 rounded-md px-3 py-2 text-sm text-slate-700 focus:outline-none focus:ring-2 focus:ring-red-500"
                >
                    <option value="">All Group Codes</option>
                </select>

                <select
                    id="depr-branch-filter"
                    class="w-full sm:w-64 border border-slate-300 rounded-md px-3 py-2 text-sm text-slate-700 focus:outline-none focus:ring-2 focus:ring-red-500"
                >
                    <option value="">All Branches</option>
                </select>

                <button
                    type="button"
                    id="depr-filter-reset"
                    class="inline-flex items-center justify-center px-3 py-2 text-xs font-bold uppercase tracking-wide border border-slate-300 rounded-md text-slate-700 hover:bg-slate-100"
                >
                    Reset
                </button>
            </div>

            <div class="text-xs font-semibold text-slate-500">
                Showing ACTIVE assets only • 50 per page
            </div>
        </div>
    </div>

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
                    <th class="text-center text-xs font-black text-white tracking-widest px-6 py-2 whitespace-nowrap">
                        <button type="button" class="depr-sort inline-flex items-center gap-1" data-sort="serial_number">Serial number <span class="depr-sort-indicator opacity-70">↕</span></button>
                    </th>
                    <th class="text-left text-xs font-black text-white tracking-widest px-6 py-2 whitespace-nowrap">
                        <button type="button" class="depr-sort inline-flex items-center gap-1" data-sort="description">Description <span class="depr-sort-indicator opacity-70">↕</span></button>
                    </th>
                    <th class="text-center text-xs font-black text-white uppercase tracking-widest px-6 py-2 whitespace-nowrap">
                        <button type="button" class="depr-sort inline-flex items-center gap-1" data-sort="item_code">Item <span class="depr-sort-indicator opacity-70">↕</span></button>
                    </th>
                    <th class="text-center text-xs font-black text-white uppercase tracking-widest px-6 py-2 whitespace-nowrap">
                        <button type="button" class="depr-sort inline-flex items-center gap-1" data-sort="group_code">Group code <span class="depr-sort-indicator opacity-70">↕</span></button>
                    </th>
                    <th class="text-center text-xs font-black text-white uppercase tracking-widest px-6 py-2 whitespace-nowrap">
                        <button type="button" class="depr-sort inline-flex items-center gap-1" data-sort="branch_name">Resource <span class="depr-sort-indicator opacity-70">↕</span></button>
                    </th>
                    <th class="text-center text-xs font-black text-white uppercase tracking-widest px-6 py-2 whitespace-nowrap">
                        <button type="button" class="depr-sort inline-flex items-center gap-1" data-sort="acquisition_cost">Amount <span class="depr-sort-indicator opacity-70">↕</span></button>
                    </th>
                    <th class="text-center text-xs font-black text-white uppercase tracking-widest px-6 py-2 whitespace-nowrap">
                        <button type="button" class="depr-sort inline-flex items-center gap-1" data-sort="status">Status <span class="depr-sort-indicator opacity-70">↕</span></button>
                    </th>
                    <th class="text-center text-xs font-black text-white uppercase tracking-widest px-6 py-2 whitespace-nowrap">
                        <button type="button" class="depr-sort inline-flex items-center gap-1" data-sort="depreciation_end_date">End date <span class="depr-sort-indicator opacity-70">↕</span></button>
                    </th>
                </tr>
            </thead>
            <tbody id="depr-table-body">
                <!-- Rows will be populated here (server-side or via JS) -->
            </tbody>
        </table>
    </div>

    <div class="border-t border-slate-200 bg-slate-50 px-4 py-3 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
        <div id="depr-page-meta" class="text-xs font-semibold text-slate-500">
            Page 1 of 1 • 0 records
        </div>

        <div class="flex items-center gap-2">
            <button
                type="button"
                id="depr-prev-page"
                class="px-3 py-1.5 text-xs font-bold uppercase tracking-wide border border-slate-300 rounded-md text-slate-700 hover:bg-slate-100 disabled:opacity-50 disabled:cursor-not-allowed"
            >
                Prev
            </button>

            <div id="depr-page-numbers" class="flex items-center gap-1"></div>

            <button
                type="button"
                id="depr-next-page"
                class="px-3 py-1.5 text-xs font-bold uppercase tracking-wide border border-slate-300 rounded-md text-slate-700 hover:bg-slate-100 disabled:opacity-50 disabled:cursor-not-allowed"
            >
                Next
            </button>
        </div>
    </div>
</div>

<div id="depr-list-config"
     data-api-url="<?= BASE_URL ?>/public/api/get_depreciation_list.php"
     data-per-page="50"
     class="hidden"></div>

<div id="depr-ledger-config"
    data-api-url="<?= BASE_URL ?>/public/api/get_asset_ledger.php"
    data-generated-by="<?= htmlspecialchars($_SESSION['full_name'] ?? 'User') ?>"
    class="hidden"></div>



