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
        <h1 class="text-2xl font-black text-slate-800 uppercase tracking-tight">Import Assets</h1>
        <p class="text-sm text-slate-500 mt-1">Upload a formatted Excel file to batch-import branch assets.</p>
    </div>

    <!-- Format Guide Button -->
    <button onclick="openModal('modal-format-guide')"
            class="inline-flex items-center gap-2 bg-[#ce1126] hover:bg-red-700
                   text-white font-black text-xs uppercase tracking-widest
                   px-5 py-2.5 rounded-xl shadow-lg shadow-red-200
                   hover:shadow-xl hover:-translate-y-0.5 transition-all group">
        <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                  d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
        </svg>
        Column Format
        <svg class="w-3.5 h-3.5 shrink-0 opacity-70 group-hover:translate-x-0.5 transition-transform"
             fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7"/>
        </svg>
    </button>
</div>

<!-- Two-column layout -->
<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

    <!-- Drop Zone (2/3) -->
    <div class="lg:col-span-2">
        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6 h-full flex flex-col">
            <form id="import-form"
                  action="<?= BASE_URL ?>/public/actions/asset_import_process.php"
                  method="POST"
                  enctype="multipart/form-data"
                  class="flex-1 flex flex-col">

                <div id="drop-zone"
                     class="flex-1 min-h-[280px] border-2 border-dashed border-red-200 rounded-xl
                            flex flex-col items-center justify-center text-center
                            hover:border-[#ce1126] hover:bg-red-50/40 transition-all cursor-pointer group relative">

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
                            Drag &amp; drop your file here
                        </p>
                        <p class="text-xs text-slate-400 mb-5">or click anywhere to browse</p>
                        <span class="inline-flex items-center gap-1.5 bg-red-50 border border-red-100
                                     text-red-400 text-[10px] font-black uppercase tracking-widest
                                     px-3 py-1.5 rounded-full">
                            .xlsx &nbsp;·&nbsp; .xls &nbsp;·&nbsp; .csv
                        </span>
                    </div>

                    <!-- File selected overlay -->
                    <div id="file-display"
                         class="hidden absolute inset-0 bg-white/95 backdrop-blur-sm rounded-xl flex-col
                                items-center justify-center border-2 border-[#ce1126] z-10">
                        <div class="w-14 h-14 bg-red-50 rounded-2xl flex items-center justify-center mb-3 border border-red-100">
                            <svg class="w-7 h-7 text-[#ce1126]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                      d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                            </svg>
                        </div>
                        <p id="file-name" class="text-sm font-black text-slate-800 mb-1 max-w-xs truncate px-4"></p>
                        <p class="text-[11px] text-slate-400 mb-5">Ready to process</p>
                        <div class="flex gap-3">
                            <button type="button" id="btn-cancel"
                                    class="px-5 py-2 bg-white hover:bg-red-50 border border-red-200
                                           text-slate-500 hover:text-[#ce1126] text-xs font-black
                                           uppercase tracking-widest rounded-lg transition-colors">
                                Cancel
                            </button>
                            <button type="button" id="btn-process"
                                    class="px-6 py-2 bg-[#ce1126] hover:bg-red-700 text-white text-xs
                                           font-black uppercase tracking-widest rounded-lg shadow-lg
                                           shadow-red-200 hover:shadow-xl hover:-translate-y-0.5 transition-all">
                                Process File
                            </button>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Right sidebar (1/3) -->
    <div class="flex flex-col gap-4">

        <!-- How it works -->
        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-5">
            <h3 class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-4">How it works</h3>
            <ol class="space-y-4">
                <?php
                $steps = [
                    ['1', 'Prepare your Excel file using the required 9-column format.'],
                    ['2', 'Upload — the system parses and validates every row instantly.'],
                    ['3', 'Review the preview. System-computed values are highlighted.'],
                    ['4', 'Confirm to commit valid rows. Duplicates and errors are skipped.'],
                ];
                foreach ($steps as $i => [$num, $txt]): ?>
                <li class="flex items-start gap-3">
                    <span class="w-6 h-6 rounded-full bg-[#ce1126] text-white text-[11px] font-black
                                 flex items-center justify-center shrink-0 mt-0.5 shadow-sm shadow-red-200">
                        <?= $num ?>
                    </span>
                    <p class="text-xs text-slate-500 leading-relaxed"><?= $txt ?></p>
                </li>
                <?php endforeach; ?>
            </ol>
        </div>

        <!-- Quick column reference -->
        <div class="bg-red-50 border border-red-100 rounded-2xl p-5">
            <div class="flex items-center justify-between mb-3">
                <h3 class="text-[10px] font-black text-[#ce1126] uppercase tracking-widest">9 Required Columns</h3>
                <button onclick="openModal('modal-format-guide')"
                        class="text-[10px] font-black text-[#ce1126] hover:text-red-800
                               uppercase tracking-widest underline underline-offset-2 transition-colors">
                    Full Guide →
                </button>
            </div>
            <div class="space-y-1.5">
                <?php
                $quick = [
                    'A · Zone', 'B · Region', 'C · Cost Center', 'D · Branch',
                    'E · Reference No.', 'F · Asset Category',
                    'G · Date Received', 'H · Acquisition Cost', 'I · Description',
                ];
                foreach ($quick as $q): ?>
                <div class="flex items-center gap-2 text-[11px] text-red-700 font-medium">
                    <span class="w-1.5 h-1.5 rounded-full bg-[#ce1126] shrink-0"></span>
                    <?= $q ?>
                </div>
                <?php endforeach; ?>
            </div>
        </div>

    </div>
</div>

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

<script src="<?= ASSET_URL ?>js/main.js"></script>
<script src="<?= ASSET_URL ?>js/asset-import.js"></script>