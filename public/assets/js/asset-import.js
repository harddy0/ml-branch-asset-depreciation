// ============================================================
//  asset-import.js — Asset Import page scripts
//  Depends on: main.js  (openModal / closeModal)
// ============================================================

document.addEventListener('DOMContentLoaded', function () {
    const dropZone    = document.getElementById('drop-zone');
    const fileInput   = document.getElementById('file-upload');
    const fileDisplay = document.getElementById('file-display');
    const fileNameTxt = document.getElementById('file-name');
    const btnCancel   = document.getElementById('btn-cancel');
    const form        = document.getElementById('import-form');

    if (!dropZone || !fileInput) return;

    // ── 1. Click to Browse ──────────────────────────────────────────
    // Only open file dialog when clicking the drop zone itself,
    // never when clicking buttons inside the overlay (#file-display)
    dropZone.addEventListener('click', function (e) {
        if (e.target.closest('#btn-process') || e.target.closest('#btn-cancel')) return;
        if (e.target.closest('#file-display')) return;
        fileInput.click();
    });

    // ── 2. Drag & Drop ──────────────────────────────────────────────
    ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(function (evt) {
        dropZone.addEventListener(evt, function (e) { e.preventDefault(); e.stopPropagation(); }, false);
    });

    ['dragenter', 'dragover'].forEach(function (evt) {
        dropZone.addEventListener(evt, function () {
            dropZone.classList.add('border-red-500', 'bg-red-50');
            dropZone.classList.remove('border-slate-300');
        }, false);
    });

    ['dragleave', 'drop'].forEach(function (evt) {
        dropZone.addEventListener(evt, function () {
            dropZone.classList.remove('border-red-500', 'bg-red-50');
            dropZone.classList.add('border-slate-300');
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
        if (!files.length) return;
        const file    = files[0];
        const ext     = file.name.split('.').pop().toLowerCase();
        if (!['csv', 'xlsx', 'xls'].includes(ext)) {
            alert('Invalid file type. Please upload a .csv or .xlsx file.');
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

    // ── 3. Process File → AJAX Preview ─────────────────────────────
    // Intercept the "Process File" button — send as AJAX preview first
    const btnProcess = document.getElementById('btn-process');
    if (btnProcess) {
        btnProcess.addEventListener('click', function (e) {
            e.preventDefault();
            e.stopPropagation();

            if (!fileInput.files.length) {
                alert('Please select a file first.');
                return;
            }

            // Show loading state
            btnProcess.disabled    = true;
            btnProcess.textContent = 'Parsing…';

            const formData = new FormData();
            formData.append('action',      'preview');
            formData.append('import_file', fileInput.files[0]);

            fetch(BASE_URL + '/public/actions/asset_import_process.php', {
                method: 'POST',
                body:   formData,
            })
            .then(function (res) {
                if (!res.ok) throw new Error('Server error ' + res.status);
                return res.json();
            })
            .then(function (data) {
                btnProcess.disabled    = false;
                btnProcess.textContent = 'Process File';

                if (!data.success) {
                    alert('Error: ' + data.error);
                    return;
                }

                buildReviewModal(data);
                openModal('modal-import-review');
            })
            .catch(function (err) {
                btnProcess.disabled    = false;
                btnProcess.textContent = 'Process File';
                alert('Failed to parse file: ' + err.message);
            });
        });
    }
});

// ── 4. Build Review Modal Table ──────────────────────────────────────
function buildReviewModal(data) {
    const tbody    = document.getElementById('review-tbody');
    const summOk   = document.getElementById('review-summary-ok');
    const summErr  = document.getElementById('review-summary-err');
    const errNote  = document.getElementById('review-error-note');
    const errTxt   = document.getElementById('review-error-note-text');
    const btnConf  = document.getElementById('btn-confirm-import');

    if (!tbody) return;
    tbody.innerHTML = '';

    const preview   = data.preview  || [];
    const okRows    = preview.filter(function (r) { return !r.has_error; });
    const dupRows   = preview.filter(function (r) { return r.is_duplicate; });
    const errRows   = preview.filter(function (r) { return r.has_error && !r.is_duplicate; });

    summOk.textContent  = okRows.length + ' row(s) ready';
    var errParts = [];
    if (dupRows.length)  errParts.push(dupRows.length  + ' duplicate(s)');
    if (errRows.length)  errParts.push(errRows.length  + ' error(s)');
    summErr.textContent = errParts.length ? '· ' + errParts.join(', ') + ' will be skipped' : '';

    var skipTotal = dupRows.length + errRows.length;
    if (skipTotal) {
        errNote.classList.remove('hidden');
        var parts = [];
        if (errRows.length) parts.push(errRows.length + ' row(s) have validation errors');
        if (dupRows.length) parts.push(dupRows.length + ' row(s) are duplicates already in the system');
        errTxt.textContent = parts.join(' · ') + '. These will be skipped.';
    } else {
        errNote.classList.add('hidden');
    }

    // Disable confirm if nothing valid to import
    btnConf.disabled = (okRows.length === 0);

    preview.forEach(function (row) {
        const tr = document.createElement('tr');
        tr.className = row.is_duplicate
            ? 'bg-orange-50 border-b border-orange-100'
            : row.has_error
                ? 'bg-red-50 border-b border-red-100'
                : 'bg-white hover:bg-green-50/40 border-b border-slate-100 transition-colors';

        // Helper: user-supplied cell
        function cell(val, extraClass) {
            return '<td class="px-3 py-2.5 text-slate-700 font-medium whitespace-nowrap ' + (extraClass || '') + '">'
                + escHtml(String(val ?? '—'))
                + '</td>';
        }

        // Helper: system-computed cell (blue tint)
        function sysCell(val, extraClass) {
            return '<td class="px-3 py-2.5 bg-blue-50/60 text-blue-700 font-bold whitespace-nowrap ' + (extraClass || '') + '">'
                + escHtml(String(val ?? '—'))
                + '</td>';
        }

        const costFmt = row.acquisition_cost
            ? parseFloat(row.acquisition_cost).toLocaleString('en-PH', { minimumFractionDigits: 2 })
            : '—';
        const depFmt  = row.monthly_depreciation
            ? parseFloat(row.monthly_depreciation).toLocaleString('en-PH', { minimumFractionDigits: 2 })
            : '—';

        // Row number badge — green (ok), orange (duplicate), red (error)
        let rowNumCell;
        if (row.is_duplicate) {
            rowNumCell = '<td class="px-3 py-2.5 whitespace-nowrap">'
                + '<span class="inline-flex items-center gap-1 bg-orange-100 text-orange-700 text-[10px] font-black px-2 py-0.5 rounded-full">'
                + '<svg class="w-3 h-3 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg>'
                + escHtml(String(row.row_num))
                + '</span></td>';
        } else if (row.has_error) {
            rowNumCell = '<td class="px-3 py-2.5 whitespace-nowrap">'
                + '<span class="inline-flex items-center gap-1 bg-red-100 text-red-700 text-[10px] font-black px-2 py-0.5 rounded-full">'
                + '<svg class="w-3 h-3 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"/></svg>'
                + escHtml(String(row.row_num))
                + '</span></td>';
        } else {
            rowNumCell = '<td class="px-3 py-2.5 whitespace-nowrap">'
                + '<span class="inline-flex items-center gap-1 bg-green-100 text-green-700 text-[10px] font-black px-2 py-0.5 rounded-full">'
                + '<svg class="w-3 h-3 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>'
                + escHtml(String(row.row_num))
                + '</span></td>';
        }

        // Error tooltip for rows with errors — append a title
        const errTitle = row.errors && row.errors.length
            ? ' title="' + row.errors.join(' | ').replace(/"/g, '&quot;') + '"'
            : '';

        tr.setAttribute('data-has-error', row.has_error ? '1' : '0');
        if (errTitle) tr.setAttribute('title', row.errors.join(' | '));

        tr.innerHTML =
            rowNumCell +
            cell(row.zone) +
            cell(row.region) +
            cell(row.cost_center) +
            sysCell(row.branch_name) +          // ⚙ system-fetched
            cell(row.reference_no) +
            cell(row.category_name) +
            sysCell(row.category_code) +         // ⚙ system-derived
            sysCell(row.asset_life_months) +     // ⚙ system-derived
            cell(formatDate(row.date_received)) +
            sysCell(formatDate(row.depreciation_start)) +    // ⚙ system-computed
            '<td class="px-3 py-2.5 text-right text-slate-700 font-medium whitespace-nowrap">' + costFmt + '</td>' +
            '<td class="px-3 py-2.5 bg-blue-50/60 text-right text-blue-700 font-bold whitespace-nowrap">' + depFmt + '</td>' + // ⚙
            cell(row.description, 'max-w-[220px] overflow-hidden text-ellipsis') +
            sysCell(row.system_asset_code || (row.has_error ? '—' : ''));  // ⚙

        tbody.appendChild(tr);
    });
}

// ── 5. Confirm Import ────────────────────────────────────────────────
function confirmImport() {
    const form = document.getElementById('import-commit-form');
    if (form) form.submit();
}

// ── 6. Close Review Modal ────────────────────────────────────────────
function closeImportReview() {
    closeModal('modal-import-review');
}

// ── Util ─────────────────────────────────────────────────────────────

// Format ISO date string (Y-m-d) → "Jan 31, 2026"
function formatDate(iso) {
    if (!iso) return '—';
    var parts = String(iso).split('-');
    if (parts.length !== 3) return iso;
    var months = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];
    var d = parseInt(parts[2], 10);
    var m = parseInt(parts[1], 10) - 1;
    var y = parts[0];
    return (months[m] || parts[1]) + ' ' + d + ', ' + y;
}

function escHtml(str) {
    return str
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
}