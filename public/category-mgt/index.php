<?php
$pageTitle   = 'Category Management';
$currentPage = 'category-mgt';
require_once __DIR__ . '/../../src/includes/init.php';

if (!$auth->isAdmin()) {
    header('Location: ' . BASE_URL . '/public/dashboard/');
    exit;
}

$success = $_SESSION['flash_success'] ?? null;
$error   = $_SESSION['flash_error']   ?? null;
unset($_SESSION['flash_success'], $_SESSION['flash_error']);
?>

<?php if ($success): ?>
<div data-flash class="mb-5 flex items-center gap-3 bg-green-50 border border-green-200 text-green-800 text-sm font-bold rounded-xl px-5 py-3.5 shadow-sm">
    <svg class="w-5 h-5 text-green-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
    <?= htmlspecialchars($success) ?>
</div>
<?php endif; ?>
<?php if ($error): ?>
<div data-flash class="mb-5 flex items-center gap-3 bg-red-50 border border-red-200 text-red-800 text-sm font-bold rounded-xl px-5 py-3.5 shadow-sm">
    <svg class="w-5 h-5 text-red-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
    <?= htmlspecialchars($error) ?>
</div>
<?php endif; ?>

<div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
    <div>
        <h1 class="text-2xl font-black text-slate-800 uppercase tracking-wide">Category Management</h1>
        <p class="text-sm font-medium text-slate-500 mt-1">Master Depreciation Policies</p>
    </div>
</div>

<div class="w-full bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden flex flex-col">
    <div class="px-4 py-3 border-b border-slate-200 flex items-center justify-between">
        <h2 class="text-xs font-black uppercase tracking-widest text-slate-700">Asset Categories</h2>
        <button id="btn-add-category" type="button" class="inline-flex items-center gap-1.5 bg-[#ce1126] hover:bg-red-700 text-white text-[11px] font-black uppercase tracking-widest px-3 py-1.5 rounded-lg transition-colors">
            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/>
            </svg>
            Add Category
        </button>
    </div>
    
    <div class="p-4">
        <table id="categories-table" class="w-full text-left border-collapse">
            <thead>
                <tr class="border-b border-slate-200 text-xs font-bold text-slate-500 uppercase tracking-wider">
                    <th class="py-3 px-4">Code</th>
                    <th class="py-3 px-4">Description</th>
                    <th class="py-3 px-4">Useful Life</th>
                    <th class="py-3 px-4">GL Account</th>
                    <th class="py-3 px-4 text-right">Actions</th>
                </tr>
            </thead>
            <tbody id="categories-tbody" class="text-sm text-slate-700">
                </tbody>
        </table>
    </div>
</div>

<?php
require_once __DIR__ . '/../../src/includes/modals/pl-rule-modal.php';
require_once __DIR__ . '/../../src/includes/modals/delete-confirmation.php';
?>

<script src="<?= ASSET_URL ?>js/main.js"></script>
<script src="<?= ASSET_URL ?>js/category-mgt.js"></script>