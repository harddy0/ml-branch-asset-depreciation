// ============================================================
//  asset-import.js - Asset Import page scripts
//  Depends on: main.js (openModal / closeModal)
// ============================================================

var reviewPreviewRows = [];
var reviewSelectedRowNums = new Set();

document.addEventListener('DOMContentLoaded', function () {
    var dropZone = document.getElementById('drop-zone');
    var fileInput = document.getElementById('file-upload');
    var fileDisplay = document.getElementById('file-display');
    var fileNameTxt = document.getElementById('file-name');
    var btnCancel = document.getElementById('btn-cancel');
    var btnProcess = document.getElementById('btn-process');

    if (!dropZone || !fileInput || !fileDisplay || !fileNameTxt || !btnCancel || !btnProcess) return;

    // 1) Click to browse
    dropZone.addEventListener('click', function (e) {
        if (e.target.closest('#btn-process') || e.target.closest('#btn-cancel')) return;
        fileInput.click();
    });

    // 2) Drag and drop
    ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(function (evt) {
        dropZone.addEventListener(evt, function (e) {
            e.preventDefault();
            e.stopPropagation();
        }, false);
    });

    ['dragenter', 'dragover'].forEach(function (evt) {
        dropZone.addEventListener(evt, function () {
            dropZone.classList.add('border-red-500', 'bg-red-50');
            dropZone.classList.remove('border-red-200');
        }, false);
    });

    ['dragleave', 'drop'].forEach(function (evt) {
        dropZone.addEventListener(evt, function () {
            dropZone.classList.remove('border-red-500', 'bg-red-50');
            dropZone.classList.add('border-red-200');
        }, false);
    });

    dropZone.addEventListener('drop', function (e) {
        fileInput.files = e.dataTransfer.files;
        handleFiles(fileInput.files);
    });

    fileInput.addEventListener('change', function () {
        handleFiles(this.files);
    });

    function handleFiles(files) {
        if (!files || !files.length) return;

        var file = files[0];
        var ext = file.name.split('.').pop().toLowerCase();
        if (!['csv', 'xlsx', 'xls'].includes(ext)) {
            alert('Invalid file type. Please upload a .csv, .xlsx, or .xls file.');
            fileInput.value = '';
            return;
        }

        fileNameTxt.textContent = file.name;
        fileDisplay.classList.remove('hidden');
        fileDisplay.classList.add('flex');
    }

    btnCancel.addEventListener('click', function (e) {
        e.stopPropagation();
        fileInput.value = '';
        fileDisplay.classList.add('hidden');
        fileDisplay.classList.remove('flex');
    });

    // 3) Process file -> preview
    btnProcess.addEventListener('click', function (e) {
        e.preventDefault();
        e.stopPropagation();

        if (!fileInput.files.length) {
            alert('Please select a file first.');
            return;
        }

        btnProcess.disabled = true;
        btnProcess.textContent = 'Uploading...';

        var formData = new FormData();
        formData.append('action', 'preview');
        formData.append('import_file', fileInput.files[0]);

        fetch(BASE_URL + '/public/actions/asset_import_process.php', {
            method: 'POST',
            body: formData,
        })
            .then(function (res) {
                if (!res.ok) throw new Error('Server error ' + res.status);
                return res.json();
            })
            .then(function (data) {
                btnProcess.disabled = false;
                btnProcess.textContent = 'Upload';

                if (!data.success) {
                    alert('Error: ' + data.error);
                    return;
                }

                fileDisplay.classList.add('hidden');
                fileDisplay.classList.remove('flex');
                buildReviewModal(data);
                openModal('modal-import-review');
            })
            .catch(function (err) {
                btnProcess.disabled = false;
                btnProcess.textContent = 'Upload';
                alert('Failed to parse file: ' + err.message);
            });
    });
});

function buildReviewModal(data) {
    var tbody = document.getElementById('review-tbody');
    var summOk = document.getElementById('review-summary-ok');
    var summErr = document.getElementById('review-summary-err');
    var errNote = document.getElementById('review-error-note');
    var errTxt = document.getElementById('review-error-note-text');
    var btnConf = document.getElementById('btn-confirm-import');
    var selectAll = document.getElementById('review-select-all');

    if (!tbody || !btnConf) return;

    tbody.innerHTML = '';

    var preview = data.preview || [];
    var okRows = preview.filter(function (r) { return !r.has_error && !r.is_duplicate; });
    var dupRows = preview.filter(function (r) { return !!r.is_duplicate; });
    var errRows = preview.filter(function (r) { return !!r.has_error && !r.is_duplicate; });

    reviewPreviewRows = preview;
    reviewSelectedRowNums = new Set();

    summOk.textContent = okRows.length + ' row(s) ready';

    var errParts = [];
    if (dupRows.length) errParts.push(dupRows.length + ' duplicate(s)');
    if (errRows.length) errParts.push(errRows.length + ' error(s)');
    summErr.textContent = errParts.length ? '· ' + errParts.join(', ') + ' will be skipped' : '';

    if (dupRows.length + errRows.length) {
        errNote.classList.remove('hidden');
        var parts = [];
        if (errRows.length) parts.push(errRows.length + ' row(s) have validation errors');
        if (dupRows.length) parts.push(dupRows.length + ' row(s) are duplicates already in the system');
        errTxt.textContent = parts.join(' · ') + '. These will be skipped.';
    } else {
        errNote.classList.add('hidden');
    }

    btnConf.disabled = true;
    if (selectAll) selectAll.checked = false;

    preview.forEach(function (row, rowIndex) {
        var tr = document.createElement('tr');
        var stripedBg = (rowIndex % 2 === 1) ? 'bg-slate-50' : 'bg-white';
        tr.className = stripedBg + ' border-b border-slate-100 hover:bg-slate-200 transition-colors cursor-pointer';

        tr.setAttribute('data-row-index', String(rowIndex));
        if (row.errors && row.errors.length) tr.setAttribute('title', row.errors.join(' | '));

        function cell(val, extraClass) {
            return '<td class="px-3 py-2.5 text-slate-700 font-medium whitespace-nowrap ' + (extraClass || '') + '">' + escHtml(String(val ?? '—')) + '</td>';
        }

        function sysCell(val, extraClass) {
            return '<td class="px-3 py-2.5 bg-blue-50/60 text-blue-700 font-bold whitespace-nowrap ' + (extraClass || '') + '">' + escHtml(String(val ?? '—')) + '</td>';
        }

        var canSelect = !row.has_error && !row.is_duplicate;
        var checkCell = '<td class="px-3 py-2.5 text-center whitespace-nowrap">'
            + '<input type="checkbox" class="review-row-check w-3.5 h-3.5 rounded border-slate-300 text-[#ce1126] focus:ring-red-200" '
            + 'data-row-num="' + escHtml(String(row.row_num)) + '" '
            + (canSelect ? '' : 'disabled title="Only valid rows can be selected"')
            + '>'
            + '</td>';

        var rowNumCell;
        if (row.is_duplicate) {
            rowNumCell = '<td class="px-3 py-2.5 whitespace-nowrap"><span class="inline-flex items-center gap-1 bg-orange-100 text-orange-700 text-[10px] font-black px-2 py-0.5 rounded-full">'
                + '<svg class="w-3 h-3 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg>'
                + escHtml(String(row.row_num)) + '</span></td>';
        } else if (row.has_error) {
            rowNumCell = '<td class="px-3 py-2.5 whitespace-nowrap"><span class="inline-flex items-center gap-1 bg-red-100 text-red-700 text-[10px] font-black px-2 py-0.5 rounded-full">'
                + '<svg class="w-3 h-3 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"/></svg>'
                + escHtml(String(row.row_num)) + '</span></td>';
        } else {
            rowNumCell = '<td class="px-3 py-2.5 whitespace-nowrap"><span class="inline-flex items-center gap-1 bg-green-100 text-green-700 text-[10px] font-black px-2 py-0.5 rounded-full">'
                + '<svg class="w-3 h-3 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>'
                + escHtml(String(row.row_num)) + '</span></td>';
        }

        tr.innerHTML =
            checkCell +
            rowNumCell +
            cell(row.zone) +
            cell(row.region) +
            cell(row.cost_center) +
            cell(row.branch_name) +
            cell(row.reference_no) +
            cell(row.category_name);

        tbody.appendChild(tr);
    });

    tbody.querySelectorAll('tr').forEach(function (tr) {
        tr.addEventListener('click', function (e) {
            if (e.target.closest('input[type="checkbox"]')) return;
            var rowIndex = parseInt(tr.getAttribute('data-row-index') || '-1', 10);
            if (Number.isNaN(rowIndex) || rowIndex < 0 || !reviewPreviewRows[rowIndex]) return;
            openAssetDepreciationDetails(reviewPreviewRows[rowIndex]);
        });
    });

    function syncConfirmState() {
        btnConf.disabled = (reviewSelectedRowNums.size === 0);
        if (!selectAll) return;

        var enabledChecks = Array.from(tbody.querySelectorAll('.review-row-check:not(:disabled)'));
        if (!enabledChecks.length) {
            selectAll.checked = false;
            return;
        }
        selectAll.checked = enabledChecks.every(function (cb) { return cb.checked; });
    }

    tbody.querySelectorAll('.review-row-check').forEach(function (cb) {
        cb.addEventListener('change', function () {
            var rowNum = cb.getAttribute('data-row-num');
            if (!rowNum) return;
            if (cb.checked) reviewSelectedRowNums.add(rowNum);
            else reviewSelectedRowNums.delete(rowNum);
            syncConfirmState();
        });
    });

    if (selectAll) {
        selectAll.addEventListener('change', function () {
            var enabledChecks = tbody.querySelectorAll('.review-row-check:not(:disabled)');
            enabledChecks.forEach(function (cb) {
                cb.checked = selectAll.checked;
                var rowNum = cb.getAttribute('data-row-num');
                if (!rowNum) return;
                if (cb.checked) reviewSelectedRowNums.add(rowNum);
                else reviewSelectedRowNums.delete(rowNum);
            });
            syncConfirmState();
        });
    }
}

function confirmImport() {
    var form = document.getElementById('import-commit-form');
    var selectedRowsInput = document.getElementById('selected-rows');

    if (!reviewSelectedRowNums.size) {
        alert('Please select at least one valid row to import.');
        return;
    }

    if (selectedRowsInput) {
        selectedRowsInput.value = JSON.stringify(Array.from(reviewSelectedRowNums));
    }

    if (form) form.submit();
}

function closeImportReview() {
    closeModal('modal-import-review');
}

function openAssetDepreciationDetails(row) {
    var branchEl = document.getElementById('depr-details-branch');
    var categoryEl = document.getElementById('depr-details-category');
    var content = document.getElementById('asset-depr-detail-content');
    if (!content) return;

    if (branchEl) branchEl.textContent = row.branch_name || '—';
    if (categoryEl) categoryEl.textContent = row.category_name || '—';

    var costFmt = row.acquisition_cost
        ? parseFloat(row.acquisition_cost).toLocaleString('en-PH', { minimumFractionDigits: 2 })
        : '—';
    var depFmt = row.monthly_depreciation
        ? parseFloat(row.monthly_depreciation).toLocaleString('en-PH', { minimumFractionDigits: 2 })
        : '—';

    function field(label, value) {
        return '<div>'
            + '<label class="block text-xs font-black text-slate-700 mb-1">' + escHtml(String(label)) + '</label>'
            + '<input type="text" class="w-full border border-slate-300 bg-white px-3 py-2 text-xs font-semibold text-slate-700 outline-none" value="' + escHtml(String(value ?? '')) + '">'
            + '</div>';
    }

    var sectionOne = '<section>'
        + '<h3 class="text-xs font-black text-slate-700 uppercase tracking-wider mb-2">Branch Details</h3>'
        + '<div class="border-t-2 border-slate-700 pt-3 grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-3">'
        + field('Zone', row.zone)
        + field('Region', row.region)
        + field('Cost Center', row.cost_center)
        + field('Branch', row.branch_name)
        + '</div>'
        + '</section>';

    var sectionTwo = '<section>'
        + '<h3 class="text-xs font-black text-slate-700 uppercase tracking-wider mb-2">Asset Details</h3>'
        + '<div class="border-t-2 border-slate-700 pt-3 grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-3">'
        + field('Reference Number', row.reference_no)
        + field('Category', row.category_name)
        + field('Code', row.category_code)
        + field('Asset Life', row.asset_life_months)
        + field('Date Received', formatDate(row.date_received))
        + field('Date Start', formatDate(row.depreciation_start))
        + '</div>'
        + '</section>';

    var sectionThree = '<section>'
        + '<h3 class="text-xs font-black text-slate-700 uppercase tracking-wider mb-2">Depreciation Summary</h3>'
        + '<div class="border-t-2 border-slate-700 pt-3 grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-3">'
        + field('Acquisition Cost', costFmt)
        + field('Monthly Depreciation', depFmt)
        + field('Description', row.description)
        + field('System Code', row.system_asset_code)
        + '</div>'
        + '</section>';

    content.innerHTML = sectionOne + sectionTwo + sectionThree;

    openModal('modal-asset-depr-details');
}

function closeAssetDepreciationDetails() {
    closeModal('modal-asset-depr-details');
}

function formatDate(iso) {
    if (!iso) return '—';
    var parts = String(iso).split('-');
    if (parts.length !== 3) return iso;
    var months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
    var d = parseInt(parts[2], 10);
    var m = parseInt(parts[1], 10) - 1;
    var y = parts[0];
    return (months[m] || parts[1]) + ' ' + d + ', ' + y;
}

function escHtml(str) {
    return String(str)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
}
