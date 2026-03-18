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

$stmt       = $pdo->query("SELECT * FROM asset_categories ORDER BY category_code ASC");
$categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
$total = count($categories);
?>

<!-- Flash Messages -->
<?php if ($success): ?>
<div data-flash class="mb-5 flex items-center gap-3 bg-green-50 border border-green-200 text-green-800 text-sm font-bold rounded-xl px-5 py-3.5 shadow-sm">
    <svg class="w-5 h-5 text-green-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
    </svg>
    <?= htmlspecialchars($success) ?>
</div>
<?php endif; ?>
<?php if ($error): ?>
<div data-flash class="mb-5 flex items-center gap-3 bg-red-50 border border-red-200 text-red-800 text-sm font-bold rounded-xl px-5 py-3.5 shadow-sm">
    <svg class="w-5 h-5 text-red-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
    </svg>
    <?= htmlspecialchars($error) ?>
</div>
<?php endif; ?>

<!-- Page Header -->
<div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
    <div>
        <h1 class="text-2xl font-black text-slate-800 uppercase tracking-tight">Category Management</h1>
        <p class="text-sm text-slate-500 mt-1">
            Manage asset depreciation categories —
            <span class="font-bold text-slate-700"><?= $total ?></span>
            categor<?= $total !== 1 ? 'ies' : 'y' ?> defined
        </p>
    </div>
    <button onclick="openModal('modal-add-category')"
        class="inline-flex items-center gap-2 bg-[#ce1126] hover:bg-red-700 active:bg-red-800
               text-white text-xs font-black uppercase tracking-widest
               px-5 py-3 rounded-xl shadow-lg shadow-red-200 hover:shadow-xl hover:-translate-y-0.5 transition-all">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/>
        </svg>
        Add Category
    </button>
</div>

<!-- Search Bar -->
<div class="mb-4">
    <div class="relative max-w-sm">
        <svg class="absolute left-3.5 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400 pointer-events-none"
             fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                d="M21 21l-4.35-4.35M17 11A6 6 0 115 11a6 6 0 0112 0z"/>
        </svg>
        <input type="text" id="search-input"
               placeholder="Search by code or category name..."
               class="w-full pl-10 pr-4 py-2.5 border-2 border-slate-200 focus:border-red-500 rounded-xl
                      text-sm font-medium text-slate-700 outline-none bg-white transition-all">
    </div>
</div>

<!-- Categories Table -->
<div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead>
                <tr class="bg-slate-50 border-b border-slate-200">
                    <th class="text-left text-xs font-black text-slate-500 uppercase tracking-widest px-6 py-4">Code</th>
                    <th class="text-left text-xs font-black text-slate-500 uppercase tracking-widest px-6 py-4">Category Name</th>
                    <th class="text-left text-xs font-black text-slate-500 uppercase tracking-widest px-6 py-4">Asset Life</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100" id="categories-tbody">
                <?php if (empty($categories)): ?>
                <tr>
                    <td colspan="4" class="text-center py-20">
                        <div class="flex flex-col items-center gap-2">
                            <div class="w-12 h-12 rounded-xl bg-slate-100 flex items-center justify-center">
                                <svg class="w-6 h-6 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                        d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/>
                                </svg>
                            </div>
                            <p class="text-sm font-bold text-slate-400">No categories found</p>
                            <p class="text-xs text-slate-300">Add your first category to get started</p>
                        </div>
                    </td>
                </tr>
                <?php else: ?>
                <?php foreach ($categories as $cat):
                    $lifeMonths = (int)$cat['asset_life_months'];
                ?>
                <tr class="hover:bg-slate-50/70 transition-colors category-row group">

                    <!-- Code -->
                    <td class="px-6 py-4">
                        <span class="inline-block font-mono text-xs font-black text-[#ce1126] bg-red-50 border border-red-100 px-2.5 py-1 rounded-lg tracking-wider cat-code">
                            <?= htmlspecialchars($cat['category_code']) ?>
                        </span>
                    </td>

                    <!-- Category Name -->
                    <td class="px-6 py-4 font-semibold text-slate-800 cat-name">
                        <?= htmlspecialchars($cat['category_name']) ?>
                    </td>

                    <!-- Asset Life -->
                    <td class="px-6 py-4">
                        <span class="font-bold text-slate-800"><?= $lifeMonths ?></span>
                        <span class="text-xs text-slate-400 font-medium ml-1">month<?= $lifeMonths !== 1 ? 's' : '' ?></span>
                    </td>

                    <td class="px-6 py-4 text-right">
                        <button
                            onclick="openEditModal(<?= htmlspecialchars(json_encode([
                                'id'                => $cat['id'],
                                'category_code'     => $cat['category_code'],
                                'category_name'     => $cat['category_name'],
                                'asset_life_months' => $cat['asset_life_months'],
                            ]), ENT_QUOTES) ?>)"
                            class="p-2 text-slate-400 hover:text-blue-600 hover:bg-blue-50 rounded-lg transition-all
                                   opacity-0 group-hover:opacity-100"
                            title="Edit Category">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                            </svg>
                        </button>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>


</div>

<?php
require_once __DIR__ . '/../../src/includes/modals/category-add.php';
require_once __DIR__ . '/../../src/includes/modals/category-edit.php';
?>

<script src="<?= ASSET_URL ?>js/main.js"></script>
<script src="<?= ASSET_URL ?>js/category-mgt.js"></script>