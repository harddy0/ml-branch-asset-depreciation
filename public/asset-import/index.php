<?php
$pageTitle   = 'Import Assets';
$currentPage = 'asset-import';
require_once __DIR__ . '/../../src/includes/init.php';

$success      = $_SESSION['flash_success'] ?? null;
$error        = $_SESSION['flash_error']   ?? null;
$importErrors = $_SESSION['import_errors'] ?? [];

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

<!-- Page Header -->
<div class="flex items-center justify-between mb-8">
    <div>
        <h1 class="text-1xl font-black text-slate-800 uppercase tracking-wide">Import Assets</h1>
    </div>

    <!-- Format Guide Button (compact info icon) -->
    <button onclick="openModal('modal-format-guide')" aria-label="Column format guide"
            class="inline-flex items-center justify-center bg-slate-100 text-slate-700 w-9 h-9 rounded-full hover:bg-slate-200 transition-colors"
            title="Column Format Guide">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8h.01M11 12h1v4h1" />
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 2a10 10 0 110 20 10 10 0 010-20z" />
        </svg>
    </button>
</div>

<!-- Upload -->
<div class="p-1">
    <form id="import-form"
          action="<?= BASE_URL ?>/public/actions/asset_import_process.php"
          method="POST"
          enctype="multipart/form-data">

        <div id="drop-zone"
             class="min-h-[450px] border-2 border-dashed border-red-200 rounded-xl
                    flex flex-col items-center justify-center text-center
                    hover:border-[#ce1126] transition-all cursor-pointer group relative">

            <input type="file" id="file-upload" name="import_file"
                   class="hidden" accept=".csv,.xlsx,.xls">

            <!-- Idle state -->
            <div class="pointer-events-none select-none px-8">
                <div class="w-16 h-16 bg-red-50 group-hover:bg-red-100 rounded-2xl flex items-center
                            justify-center mx-auto mb-4 transition-colors border border-red-100">
                    <svg class="w-8 h-8 text-red-300 group-hover:text-[#ce1126] transition-colors"
                         fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                              d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/>
                    </svg>
                </div>
                <p class="text-base font-black text-slate-600 group-hover:text-[#ce1126] transition-colors mb-1">
                    Drag &amp; drop file here
                </p>
                <p class="text-xs text-slate-400 mb-5">or click anywhere to choose file</p>
                <span class="inline-flex items-center gap-1.5 bg-red-50 border border-red-100
                             text-red-400 text-[10px] font-black uppercase tracking-widest
                             px-3 py-1.5 rounded-full">
                    .xlsx &nbsp;·&nbsp; .xls &nbsp;·&nbsp; .csv
                </span>
            </div>

            <!-- file-display moved to modal (import-file-display.php) -->
        </div>
    </form>
</div>

<?php require_once __DIR__ . '/../../src/includes/modals/import-file-display.php'; ?>
<?php require_once __DIR__ . '/../../src/includes/modals/import-format-guide.php'; ?>

<?php if (!empty($importErrors)): ?>
    <?php require_once __DIR__ . '/../../src/includes/modals/import-errors.php'; ?>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            setTimeout(function () { openModal('modal-import-errors'); }, 100);
        });
    </script>
<?php endif; ?>

<?php require_once __DIR__ . '/../../src/includes/modals/import-review.php'; ?>
<?php require_once __DIR__ . '/../../src/includes/modals/asset-depreciation-details.php'; ?>

<script src="<?= ASSET_URL ?>js/main.js"></script>
<script src="<?= ASSET_URL ?>js/asset-import.js"></script>