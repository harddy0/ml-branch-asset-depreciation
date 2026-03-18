<?php
$pageTitle   = 'Import Assets';
$currentPage = 'asset-import';
require_once __DIR__ . '/../../src/includes/init.php';

$success = $_SESSION['flash_success'] ?? null;
$error   = $_SESSION['flash_error']   ?? null;
$importErrors = $_SESSION['import_errors'] ?? []; // Grab the errors array

// Clean up sessions
unset($_SESSION['flash_success'], $_SESSION['flash_error'], $_SESSION['import_errors']);
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

<div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
    <div>
        <h1 class="text-2xl font-black text-slate-800 uppercase tracking-tight">Import Assets</h1>
        <p class="text-sm text-slate-500 mt-1">
            Upload new branch assets. Data will be validated against Master Data automatically.
        </p>
    </div>
</div>

<div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-8">
    <form id="import-form" action="<?= BASE_URL ?>/public/actions/asset_import_process.php" method="POST" enctype="multipart/form-data">
        <div id="drop-zone" class="w-full border-2 border-dashed border-slate-300 rounded-2xl p-12 text-center hover:border-red-500 hover:bg-red-50 transition-all cursor-pointer group relative">
            <input type="file" id="file-upload" name="import_file" class="hidden" accept=".csv, .xlsx, .xls" required>
            
            <div class="w-20 h-20 bg-slate-50 group-hover:bg-red-100 rounded-full flex items-center justify-center mx-auto mb-5 transition-colors shadow-sm">
                <svg class="w-10 h-10 text-slate-400 group-hover:text-red-600 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/>
                </svg>
            </div>
            
            <h3 class="text-lg font-black text-slate-700 mb-1 group-hover:text-red-700 transition-colors">Drag and drop your file here</h3>
            <p class="text-sm text-slate-500 mb-6">or click to browse from your computer</p>
            
            <div id="file-display" class="hidden absolute inset-0 bg-white/90 backdrop-blur-sm rounded-2xl flex-col items-center justify-center border-2 border-green-500 z-10">
                <p id="file-name" class="text-sm font-bold text-slate-800 mb-4"></p>
                <div class="flex gap-3">
                    <button type="button" id="btn-cancel" class="px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-600 text-xs font-black uppercase tracking-widest rounded-lg transition-colors">Cancel</button>
                    <button type="submit" id="btn-process" class="px-4 py-2 bg-[#ce1126] hover:bg-red-700 text-white text-xs font-black uppercase tracking-widest rounded-lg shadow-lg shadow-red-200 transition-colors">Process File</button>
                </div>
            </div>
        </div>
    </form>
</div>

<?php if (!empty($importErrors)): ?>
    <?php require_once __DIR__ . '/../../src/includes/modals/import-errors.php'; ?>
    <script>
        // Trigger modal to open automatically if errors exist from the PHP Session
        document.addEventListener('DOMContentLoaded', () => {
            setTimeout(() => { openModal('modal-import-errors'); }, 100);
        });
    </script>
<?php endif; ?>

<script src="<?= ASSET_URL ?>js/main.js"></script>
<script src="<?= ASSET_URL ?>js/asset-import.js"></script>