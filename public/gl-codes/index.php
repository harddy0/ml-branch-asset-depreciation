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

<div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-4">
    <div>
        <h1 class="text-2xl font-black text-slate-800 uppercase tracking-wide">GL Codes Management</h1>
        <p class="text-sm font-medium text-slate-500 mt-1">Manage Chart of Accounts</p>
    </div>

    <div class="flex items-center gap-4">
        <button onclick="openModal('modal-add-gl-code')"
            class="inline-flex items-center gap-2 bg-[#ce1126] hover:bg-red-700 active:bg-red-800
                   text-white text-xs font-black uppercase tracking-widest
                   px-4 py-2 rounded-xl shadow-md shadow-slate-200 hover:shadow-md transition-all">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/>
            </svg>
            Add GL Code
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
                      placeholder:text-slate-300 text-sm font-medium text-slate-700 outline-none bg-white transition-all">
    </div>
</div>

<div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead>
                <tr class="bg-[#ce2216] border-b border-slate-200">
                    <th class="text-left text-xs font-black text-white uppercase tracking-widest px-6 py-3">GL Code</th>
                    <th class="text-left text-xs font-black text-white uppercase tracking-widest px-6 py-3">Description</th>
                    <th class="text-left text-xs font-black text-white uppercase tracking-widest px-6 py-3">Account Type</th>
                    <th class="text-center text-xs font-black text-white uppercase tracking-widest px-6 py-3">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100" id="gl-codes-tbody">
                
                <tr class="hover:bg-slate-50/70 transition-colors">
                    <td class="px-6 py-3 font-mono text-xs font-bold text-slate-600">
                        1231101
                    </td>
                    <td class="px-6 py-3">
                        <p class="font-bold uppercase text-slate-800">A/D - Office Equipment</p>
                    </td>
                    <td class="px-6 py-3">
                        <span class="inline-flex items-center gap-1.5 bg-red-100 text-red-700 text-[10px] font-black uppercase tracking-widest px-2.5 py-1 rounded-full">
                            CREDIT
                        </span>
                    </td>
                    <td class="px-6 py-3">
                        <div class="flex items-center justify-center gap-1">
                            <button class="p-2 text-slate-400 hover:text-red-600 hover:bg-red-50 rounded-lg transition-all" title="Edit">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                </svg>
                            </button>
                            <button class="p-2 text-slate-400 hover:text-red-600 hover:bg-red-50 rounded-lg transition-all" title="Delete">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                </svg>
                            </button>
                        </div>
                    </td>
                </tr>

                <tr class="hover:bg-slate-50/70 transition-colors">
                    <td class="px-6 py-3 font-mono text-xs font-bold text-slate-600">
                        5151005
                    </td>
                    <td class="px-6 py-3">
                        <p class="font-bold uppercase text-slate-800">D/E - Office Equipment</p>
                    </td>
                    <td class="px-6 py-3">
                        <span class="inline-flex items-center gap-1.5 bg-slate-100 text-slate-600 text-[10px] font-black uppercase tracking-widest px-2.5 py-1 rounded-full">
                            DEBIT
                        </span>
                    </td>
                    <td class="px-6 py-3">
                        <div class="flex items-center justify-center gap-1">
                            <button class="p-2 text-slate-400 hover:text-red-600 hover:bg-red-50 rounded-lg transition-all" title="Edit">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                </svg>
                            </button>
                            <button class="p-2 text-slate-400 hover:text-red-600 hover:bg-red-50 rounded-lg transition-all" title="Delete">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                </svg>
                            </button>
                        </div>
                    </td>
                </tr>

            </tbody>
        </table>
    </div>
</div>

<?php
// Include the Tailwind Add Modal
require_once __DIR__ . '/../../src/includes/modals/gl-code-add.php';
?>

<script src="<?= ASSET_URL ?>js/main.js"></script>
<script src="<?= ASSET_URL ?>js/gl-codes-add-gl-code.js"></script>