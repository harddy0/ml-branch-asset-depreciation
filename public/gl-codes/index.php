<?php
$pageTitle   = 'GL Codes Management';
$currentPage = 'gl-codes';
require_once __DIR__ . '/../../src/includes/init.php';

// Prevent non-admins from accessing the master list
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

<div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-2">
    <div>
        <h1 class="text-1xl font-black text-slate-800 uppercase tracking-wide">GL Code Management</h1>
    </div>

    <div class="flex items-center gap-4">
        <button onclick="openModal('modal-add-gl-code')"
            class="inline-flex items-center gap-2 bg-[#ce1126] hover:bg-red-700 active:bg-red-800
                   text-white text-xs font-black uppercase tracking-widest
                   px-2 py-2 rounded-xl shadow-md shadow-slate-200 hover:shadow-md transition-all">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/>
            </svg>
        </button>
    </div>
</div>

<div class="mb-4">
    <div class="relative max-w-full">
        <svg class="absolute left-3.5 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400 pointer-events-none"
             fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                d="M21 21l-4.35-4.35M17 11A6 6 0 115 11a6 6 0 0112 0z"/>
        </svg>
        <input type="text" id="search-input"
               placeholder="Search by GL Code or Description"
               class="w-full pl-10 pr-4 py-1.5 border-2 border-slate-100 focus:border-slate-300 rounded-xl
                      placeholder:text-slate-300 text-sm font-mono text-slate-700 outline-none bg-white transition-all">
    </div>
</div>

<div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead>
                <tr class="bg-[#ce2216] border-b border-slate-200">
                    <th class="text-center text-sm font-black text-white tracking-widest px-6 py-1">GL Code</th>
                    <th class="text-left text-sm font-black text-white tracking-widest px-6 py-1">Description</th>
                    <th class="text-center text-sm font-black text-white tracking-widest px-6 py-1">Account Type</th>
                    <th class="text-center text-sm font-black text-white tracking-widest px-6 py-1">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100" id="gl-codes-tbody">
                <!-- GL codes will be loaded here dynamically -->
            </tbody>
        </table>
    </div>
</div>

<?php
// Include the Tailwind Add Modal
require_once __DIR__ . '/../../src/includes/modals/gl-code-add.php';
require_once __DIR__ . '/../../src/includes/modals/gl-code-edit.php';
require_once __DIR__ . '/../../src/includes/modals/gl-code-delete.php';
?>

<script src="<?= ASSET_URL ?>js/main.js"></script>
<script src="<?= ASSET_URL ?>js/gl-codes-list.js"></script>
<script src="<?= ASSET_URL ?>js/gl-codes-add-gl-code.js"></script>
<script src="<?= ASSET_URL ?>js/gl-codes-edit.js"></script>