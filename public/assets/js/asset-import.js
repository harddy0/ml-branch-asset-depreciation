// ============================================================
//  asset-import.js - Asset Import page scripts
//  Depends on: main.js (openModal / closeModal)
// ============================================================

var reviewPreviewRows     = [];
var reviewSelectedRowNums = new Set();

// Track which row index is currently open in the details modal
var _deprCurrentRowIndex = -1;
var _deprIsEditMode      = false;
var _deprSnapshot        = null; // deep-copy of row before edits

// ─── Available categories fetched alongside preview data ─────
// Structure: { [category_name_lower]: { code, life } }
var _availableCategories = {};

document.addEventListener('DOMContentLoaded', function () {
    var dropZone   = document.getElementById('drop-zone');
    var fileInput  = document.getElementById('file-upload');
    var fileDisplay = document.getElementById('file-display');
    var fileNameTxt = document.getElementById('file-name');
    var btnCancel  = document.getElementById('btn-cancel');
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
        var ext  = file.name.split('.').pop().toLowerCase();
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

    // 3) Process file → preview
    btnProcess.addEventListener('click', function (e) {
        e.preventDefault();
        e.stopPropagation();

        if (!fileInput.files.length) {
            alert('Please select a file first.');
            return;
        }

        btnProcess.disabled    = true;
        btnProcess.textContent = 'Uploading...';

        var formData = new FormData();
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
                btnProcess.disabled    = false;
                btnProcess.textContent = 'Upload';
                alert('Failed to parse file: ' + err.message);
            });
    });
});

// ============================================================
//  BUILD REVIEW MODAL TABLE
// ============================================================
function buildReviewModal(data) {
    var tbody    = document.getElementById('review-tbody');
    var summOk   = document.getElementById('review-summary-ok');
    var summErr  = document.getElementById('review-summary-err');
    var errNote  = document.getElementById('review-error-note');
    var errTxt   = document.getElementById('review-error-note-text');
    var btnConf  = document.getElementById('btn-confirm-import');
    var selectAll = document.getElementById('review-select-all');

    if (!tbody || !btnConf) return;

    tbody.innerHTML = '';

    var preview  = data.preview || [];
    var okRows   = preview.filter(function (r) { return !r.has_error && !r.is_duplicate; });
    var dupRows  = preview.filter(function (r) { return !!r.is_duplicate; });
    var errRows  = preview.filter(function (r) { return !!r.has_error && !r.is_duplicate; });

    reviewPreviewRows     = preview;
    reviewSelectedRowNums = new Set();

    // Cache categories from data if returned; fallback empty
    _availableCategories = data.categories || {};

    summOk.textContent  = okRows.length + ' row(s) ready';

    var errParts = [];
    if (dupRows.length) errParts.push(dupRows.length + ' duplicate(s)');
    if (errRows.length) errParts.push(errRows.length + ' error(s)');
    summErr.textContent = errParts.length ? '· ' + errParts.join(', ') + ' will be skipped' : '';

    if (dupRows.length + errRows.length) {
        errNote.classList.remove('hidden');
        var parts = [];
        if (errRows.length)  parts.push(errRows.length  + ' row(s) have validation errors');
        if (dupRows.length)  parts.push(dupRows.length  + ' row(s) are duplicates already in the system');
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
            return '<td class="px-3 py-2.5 text-slate-700 font-medium whitespace-nowrap ' + (extraClass || '') + '">'
                + escHtml(String(val ?? '—')) + '</td>';
        }

        var canSelect = !row.has_error && !row.is_duplicate;

        var checkCell = '<td class="px-3 py-2.5 text-center whitespace-nowrap">'
            + '<input type="checkbox" class="review-row-check w-3.5 h-3.5 rounded border-slate-300 text-[#ce1126] focus:ring-red-200" '
            + 'data-row-num="' + escHtml(String(row.row_num)) + '" '
            + (canSelect ? '' : 'disabled title="Only valid rows can be selected"')
            + '></td>';

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

    // Row click → open detail modal
    tbody.querySelectorAll('tr').forEach(function (tr) {
        tr.addEventListener('click', function (e) {
            if (e.target.closest('input[type="checkbox"]')) return;
            var rowIndex = parseInt(tr.getAttribute('data-row-index') || '-1', 10);
            if (Number.isNaN(rowIndex) || rowIndex < 0 || !reviewPreviewRows[rowIndex]) return;
            openAssetDepreciationDetails(rowIndex);
        });
    });

    // ── Checkbox sync ─────────────────────────────────────────
    function syncConfirmState() {
        btnConf.disabled = (reviewSelectedRowNums.size === 0);
        if (!selectAll) return;
        var enabledChecks = Array.from(tbody.querySelectorAll('.review-row-check:not(:disabled)'));
        if (!enabledChecks.length) { selectAll.checked = false; return; }
        selectAll.checked = enabledChecks.every(function (cb) { return cb.checked; });
    }

    tbody.querySelectorAll('.review-row-check').forEach(function (cb) {
        cb.addEventListener('change', function () {
            var rowNum = cb.getAttribute('data-row-num');
            if (!rowNum) return;
            if (cb.checked) reviewSelectedRowNums.add(rowNum);
            else            reviewSelectedRowNums.delete(rowNum);
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
                else            reviewSelectedRowNums.delete(rowNum);
            });
            syncConfirmState();
        });
    }
}

// ============================================================
//  CONFIRM IMPORT (submit)
// ============================================================
function confirmImport() {
    var form              = document.getElementById('import-commit-form');
    var selectedRowsInput = document.getElementById('selected-rows');
    var editedRowsInput   = document.getElementById('edited-rows');

    if (!reviewSelectedRowNums.size) {
        alert('Please select at least one valid row to import.');
        return;
    }

    // Send which rows were selected
    if (selectedRowsInput) {
        selectedRowsInput.value = JSON.stringify(Array.from(reviewSelectedRowNums));
    }

    // Send any rows that were edited in the browser so the server can merge them
    if (editedRowsInput) {
        var selectedNums = Array.from(reviewSelectedRowNums).map(String);
        var editedRows = reviewPreviewRows.filter(function (r) {
            return r._edited && selectedNums.includes(String(r.row_num));
        });
        editedRowsInput.value = JSON.stringify(editedRows);
    }

    if (form) form.submit();
}

function closeImportReview() {
    closeModal('modal-import-review');
}

// ============================================================
//  OPEN ASSET DETAIL MODAL  (now receives rowIndex, not row)
// ============================================================
function openAssetDepreciationDetails(rowIndex) {
    _deprCurrentRowIndex = rowIndex;
    _deprIsEditMode      = false;
    _deprSnapshot        = null;

    renderDeprDetails(reviewPreviewRows[rowIndex], false);
    setDeprEditMode(false);
    openModal('modal-asset-depr-details');
}

// ── Render the detail content ──────────────────────────────
function renderDeprDetails(row, editMode) {
    var content    = document.getElementById('asset-depr-detail-content');
    var subtitle   = document.getElementById('depr-details-subtitle');
    var hintEl     = document.getElementById('depr-unsaved-hint');
    if (!content) return;

    if (subtitle) subtitle.textContent = row.branch_name || '';
    if (hintEl)   hintEl.classList.add('hidden');

    // ── CSS helpers ──────────────────────────────────────────
    var inputBase  = 'w-full border rounded-md px-3 py-2 text-sm font-semibold outline-none transition-all';
    var inputEdit  = inputBase + ' border-slate-300 bg-white text-slate-800 focus:border-[#ce1126] focus:ring-1 focus:ring-red-100';
    var inputView  = inputBase + ' border-transparent bg-slate-100 text-slate-700 cursor-default';
    var inputSys   = inputBase + ' border-transparent bg-red-50 text-[#ce1126] cursor-default font-bold';
    var labelCls   = 'block text-xs font-black text-slate-500 uppercase tracking-widest mb-1';
    var sysLabelCls = 'block text-xs font-black text-[#ce1126] uppercase tracking-widest mb-1 flex items-center gap-1';

    function sysLabel(txt) {
        return '<label class="' + sysLabelCls + '">'
            + '<svg class="w-3 h-3 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">'
            + '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>'
            + '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>'
            + '</svg>' + escHtml(txt) + '</label>';
    }

    function field(id, labelTxt, val, type, isEditable, isSystem) {
        var lbl = isSystem
            ? sysLabel(labelTxt)
            : '<label class="' + labelCls + '" for="' + id + '">' + escHtml(labelTxt) + '</label>';

        var cls = isSystem ? inputSys : (editMode && isEditable ? inputEdit : inputView);
        var readonlyAttr = (editMode && isEditable && !isSystem) ? '' : ' readonly';
        var typeAttr = (type === 'date' && editMode && isEditable) ? ' type="date"' : ' type="text"';

        // In view mode: always show full English date for date fields
        // In edit mode for editable date fields: keep ISO for the date picker
        var displayVal = val;
        if (type === 'date') {
            if (editMode && isEditable) {
                displayVal = val; // ISO YYYY-MM-DD for <input type="date">
            } else {
                displayVal = formatDateFull(val); // full English for read-only display
            }
        }

        return '<div>'
            + lbl
            + '<input id="' + id + '" ' + typeAttr + readonlyAttr
            + ' class="' + cls + '" value="' + escHtml(String(displayVal ?? '')) + '">'
            + '</div>';
    }

    // ── Build category <select> for edit mode ────────────────
    function categoryField(currentName, currentCode) {
        var lbl = '<label class="' + labelCls + '" for="depr-f-category">Asset Category</label>';
        if (!editMode) {
            return '<div>' + lbl
                + '<input id="depr-f-category" type="text" readonly class="' + inputView + '" value="' + escHtml(currentName || '') + '">'
                + '</div>';
        }

        var opts = '<option value="">— Select Category —</option>';
        Object.keys(_availableCategories).forEach(function (key) {
            var cat = _availableCategories[key];
            var selected = (key === (currentName || '').toLowerCase()) ? ' selected' : '';
            opts += '<option value="' + escHtml(key) + '"' + selected + '>'
                + escHtml(cat.display_name || currentName) + '</option>';
        });

        // Fallback: if no categories in registry, just show an editable text field
        if (!Object.keys(_availableCategories).length) {
            return '<div>' + lbl
                + '<input id="depr-f-category" type="text" class="' + inputEdit + '" value="' + escHtml(currentName || '') + '">'
                + '</div>';
        }

        return '<div>' + lbl
            + '<select id="depr-f-category" class="' + inputEdit + ' appearance-none">'
            + opts
            + '</select>'
            + '</div>';
    }

    // ── Compute retirement date from depreciation_start + asset_life_months ──
    function computeRetirementDate(depStart, lifeMonths) {
        if (!depStart || !lifeMonths) return '';
        var parts = String(depStart).split('-');
        if (parts.length < 2) return '';
        var ry = parseInt(parts[0], 10);
        var rm = parseInt(parts[1], 10) - 1 + parseInt(lifeMonths, 10); // 0-based + life
        var retYear  = ry + Math.floor(rm / 12);
        var retMonth = (rm % 12) + 1; // back to 1-based
        return _lastDayOfMonth(retYear + '-' + String(retMonth).padStart(2, '0') + '-01');
    }

    // ── Compute depreciation start = last day of received month ─
    function computeDeprStart(dateReceived) {
        if (!dateReceived) return '';
        var last = _lastDayOfMonth(dateReceived);
        return last || '';
    }

    var deprStart      = row.depreciation_start || '';
    var retirementDate = computeRetirementDate(deprStart, row.asset_life_months);
    var monthlyDep     = row.monthly_depreciation
        ? parseFloat(row.monthly_depreciation).toFixed(2)
        : '0.00';

    // Pre-format dates for view mode display (full English: "March 31, 2026")
    var deprStartDisplay   = formatDateFull(deprStart);
    var retirementDisplay  = formatDateFull(retirementDate);
    var dateReceivedDisplay = formatDateFull(row.date_received);

    // ══════════════════════════════════════════════════════════
    //  SECTION 1 — Branch Details (all read-only: from masterdata)
    // ══════════════════════════════════════════════════════════
    var sec1 = '<section>'
        + '<h3 class="text-xs font-black text-slate-600 uppercase tracking-wider mb-3 flex items-center gap-2">'
        + '<span class="w-1 h-4 bg-slate-400 rounded-full inline-block"></span>Branch Details'
        + '</h3>'
        + '<div class="bg-white border border-slate-200 rounded-xl p-4 grid grid-cols-2 md:grid-cols-4 gap-4">'
        + field('depr-f-zone',        'Zone',        row.zone,        'text',  false, false)
        + field('depr-f-region',      'Region',      row.region,      'text',  false, false)
        + field('depr-f-costcenter',  'Cost Center', row.cost_center, 'text',  false, false)
        + field('depr-f-branch',      'Branch',      row.branch_name, 'text',  false, false)
        + '</div>'
        + '</section>';

    // ══════════════════════════════════════════════════════════
    //  SECTION 2 — Asset Details
    // ══════════════════════════════════════════════════════════
    var sec2 = '<section>'
        + '<h3 class="text-xs font-black text-slate-600 uppercase tracking-wider mb-3 flex items-center gap-2">'
        + '<span class="w-1 h-4 bg-[#ce1126] rounded-full inline-block"></span>Asset Details'
        + '</h3>'
        + '<div class="bg-white border border-slate-200 rounded-xl p-4 grid grid-cols-2 md:grid-cols-4 gap-4">'
        + field('depr-f-refno',       'Reference / Serial No', row.reference_no,     'text',  true,  false)
        + categoryField(row.category_name, row.category_code)
        + field('depr-f-code',        'Code',                  row.category_code,    'text',  false, true)
        + field('depr-f-desc',        'Description',           row.description,      'text',  true,  false)
        + field('depr-f-datereceived','Date Received',
                editMode ? row.date_received : dateReceivedDisplay,
                'date',  true,  false)
        + field('depr-f-acqcost',     'Acquisition Cost',      row.acquisition_cost, 'text',  true,  false)
        + field('depr-f-assetlife',   'Asset Life (months)',   row.asset_life_months,'text',  false, true)
        + field('depr-f-deprstart',   'Depreciation Date',     deprStartDisplay,     'text',  false, true)
        + field('depr-f-monthlydepr', 'Monthly Depreciation',  monthlyDep,           'text',  false, true)
        + field('depr-f-retirement',  'Retirement Date',       retirementDisplay,    'text',  false, true)
        + '</div>'
        + '</section>';

    // ══════════════════════════════════════════════════════════
    //  SECTION 3 — System Code
    // ══════════════════════════════════════════════════════════
    var sec3 = '<section>'
        + '<h3 class="text-xs font-black text-slate-600 uppercase tracking-wider mb-3 flex items-center gap-2">'
        + '<span class="w-1 h-4 bg-red-300 rounded-full inline-block"></span>System Info'
        + '<span class="text-[10px] text-red-300 font-semibold normal-case tracking-normal">&nbsp;— auto-generated, read-only</span>'
        + '</h3>'
        + '<div class="bg-white border border-slate-200 rounded-xl p-4 grid grid-cols-1 gap-4">'
        + field('depr-f-syscode', 'System Asset Code', row.system_asset_code, 'text', false, true)
        + '</div>'
        + '</section>';

    content.innerHTML = sec1 + sec2 + sec3;

    // ── Wire up live auto-compute when in edit mode ──────────
    if (editMode) {
        _wireDeprAutoCompute();
    }
}

// ── Live auto-compute wiring ───────────────────────────────
function _wireDeprAutoCompute() {
    var drEl   = document.getElementById('depr-f-datereceived');
    var catEl  = document.getElementById('depr-f-category');
    var costEl = document.getElementById('depr-f-acqcost');
    var refEl  = document.getElementById('depr-f-refno');

    function recompute() {
        var row        = reviewPreviewRows[_deprCurrentRowIndex];
        var dateRecv   = drEl   ? drEl.value   : (row.date_received || '');
        var acqCost    = costEl ? parseFloat(costEl.value) || 0 : parseFloat(row.acquisition_cost) || 0;

        // Resolve category life from select (or current row)
        var assetLife  = parseInt(row.asset_life_months, 10) || 0;
        if (catEl && catEl.tagName === 'SELECT') {
            var selectedKey = catEl.value;
            if (selectedKey && _availableCategories[selectedKey]) {
                assetLife = _availableCategories[selectedKey].life || assetLife;
                // Update code field
                var codeEl = document.getElementById('depr-f-code');
                if (codeEl) codeEl.value = _availableCategories[selectedKey].code || '';
                // Update asset life display
                var lifeEl = document.getElementById('depr-f-assetlife');
                if (lifeEl) lifeEl.value = assetLife;
            }
        }

        // Depreciation start = last day of received month
        var deprStart  = '';
        if (dateRecv) {
            deprStart = _lastDayOfMonth(dateRecv);
        }

        // Retirement date = deprStart + assetLife months (last day)
        var retDate = '';
        if (deprStart && assetLife > 0) {
            var dParts = deprStart.split('-');
            var ry = parseInt(dParts[0], 10);
            var rm = parseInt(dParts[1], 10) - 1 + assetLife; // 0-based month + life
            var retYear  = ry + Math.floor(rm / 12);
            var retMonth = (rm % 12) + 1; // back to 1-based
            retDate = _lastDayOfMonth(retYear + '-' + String(retMonth).padStart(2, '0') + '-01');
        }

        // Monthly depreciation = cost / life
        var monthlyDep = (assetLife > 0 && acqCost > 0) ? (acqCost / assetLife).toFixed(2) : '0.00';

        // Rebuild system_asset_code live so the user sees it update
        var row        = reviewPreviewRows[_deprCurrentRowIndex];
        var refEl2     = document.getElementById('depr-f-refno');
        var currentRef = refEl2 ? refEl2.value.trim() : (row.reference_no || '');
        var suffix     = currentRef !== ''
            ? currentRef
            : (row.system_asset_code || '').split('-').pop();
        var catCodeNow = (document.getElementById('depr-f-code') || {}).value || row.category_code || '';
        var newSysCode = [catCodeNow, row.zone, row.branch_code || '', suffix].join('-');
        var sysEl      = document.getElementById('depr-f-syscode');
        if (sysEl) sysEl.value = newSysCode;

        // Push computed values to display fields
        var dsEl  = document.getElementById('depr-f-deprstart');
        var retEl = document.getElementById('depr-f-retirement');
        var mdEl  = document.getElementById('depr-f-monthlydepr');

        if (dsEl)  dsEl.value  = deprStart  ? formatDateFull(deprStart)  : '';
        if (retEl) retEl.value = retDate     ? formatDateFull(retDate)    : '';
        if (mdEl)  mdEl.value  = monthlyDep;

        // Show unsaved hint
        var hintEl = document.getElementById('depr-unsaved-hint');
        if (hintEl) hintEl.classList.remove('hidden');
    }

    if (drEl)   drEl.addEventListener('change', recompute);
    if (catEl)  catEl.addEventListener('change', recompute);
    if (costEl) costEl.addEventListener('input',  recompute);
    if (refEl)  refEl.addEventListener('input',   recompute);
}

// ── Toggle edit mode UI ────────────────────────────────────
function setDeprEditMode(on) {
    _deprIsEditMode = on;

    var badge        = document.getElementById('depr-edit-badge');
    var btnEdit      = document.getElementById('depr-btn-edit');
    var btnClose     = document.getElementById('depr-btn-close');
    var btnCancelEdit = document.getElementById('depr-btn-cancel-edit');
    var btnSave      = document.getElementById('depr-btn-save');
    var hintEl       = document.getElementById('depr-unsaved-hint');

    if (on) {
        badge        && badge.classList.remove('hidden');
        btnEdit      && btnEdit.classList.add('hidden');
        btnClose     && btnClose.classList.add('hidden');
        btnCancelEdit && btnCancelEdit.classList.remove('hidden');
        btnSave      && btnSave.classList.remove('hidden');
    } else {
        badge        && badge.classList.add('hidden');
        btnEdit      && btnEdit.classList.remove('hidden');
        btnClose     && btnClose.classList.remove('hidden');
        btnCancelEdit && btnCancelEdit.classList.add('hidden');
        btnSave      && btnSave.classList.add('hidden');
        hintEl       && hintEl.classList.add('hidden');
    }
}

function enableDeprEdit() {
    if (_deprCurrentRowIndex < 0) return;
    var row = reviewPreviewRows[_deprCurrentRowIndex];
    // Snapshot for discard
    _deprSnapshot = JSON.parse(JSON.stringify(row));
    // Re-render in edit mode
    renderDeprDetails(row, true);
    setDeprEditMode(true);
}

function cancelDeprEdit() {
    if (_deprSnapshot && _deprCurrentRowIndex >= 0) {
        reviewPreviewRows[_deprCurrentRowIndex] = _deprSnapshot;
    }
    _deprSnapshot = null;
    renderDeprDetails(reviewPreviewRows[_deprCurrentRowIndex], false);
    setDeprEditMode(false);
}

function saveDeprEdit() {
    if (_deprCurrentRowIndex < 0) return;

    var row = reviewPreviewRows[_deprCurrentRowIndex];

    // ── Read editable fields back into the row object ────────
    var refEl    = document.getElementById('depr-f-refno');
    var catEl    = document.getElementById('depr-f-category');
    var descEl   = document.getElementById('depr-f-desc');
    var drEl     = document.getElementById('depr-f-datereceived');
    var costEl   = document.getElementById('depr-f-acqcost');
    var codeEl   = document.getElementById('depr-f-code');
    var lifeEl   = document.getElementById('depr-f-assetlife');

    if (refEl)  row.reference_no      = refEl.value.trim();
    if (descEl) row.description        = descEl.value.trim();
    if (drEl)   row.date_received      = drEl.value;
    if (costEl) row.acquisition_cost   = parseFloat(costEl.value) || 0;

    // Recompute system values from latest inputs
    var assetLife = parseInt(row.asset_life_months, 10) || 0;

    if (catEl && catEl.tagName === 'SELECT' && catEl.value) {
        var key = catEl.value;
        if (_availableCategories[key]) {
            row.category_name      = _availableCategories[key].display_name || row.category_name;
            row.category_code      = _availableCategories[key].code;
            row.asset_life_months  = _availableCategories[key].life;
            assetLife              = _availableCategories[key].life;
        }
    } else if (catEl && catEl.tagName === 'INPUT') {
        row.category_name = catEl.value.trim();
    }

    // Depreciation start = last day of received month
    var deprStart = '';
    if (row.date_received) {
        deprStart = _lastDayOfMonth(row.date_received);
    }
    row.depreciation_start = deprStart;

    // Monthly depreciation
    row.monthly_depreciation = (assetLife > 0 && row.acquisition_cost > 0)
        ? parseFloat((row.acquisition_cost / assetLife).toFixed(2))
        : 0;

    // ── Rebuild system_asset_code from updated parts ─────────
    // Format: {CATCODE}-{ZONE}-{BRANCHCODE}-{REFNO or random suffix}
    // zone and branch_code are immutable (from masterdata), never editable
    var suffix = (row.reference_no && String(row.reference_no).trim() !== '')
        ? String(row.reference_no).trim()
        : row.system_asset_code.split('-').pop(); // keep original suffix if no ref
    row.system_asset_code = [
        row.category_code,
        row.zone,
        row.branch_code || '',
        suffix
    ].join('-');

    // Mark as edited so confirmImport knows to send it to the server
    row._edited = true;

    // Commit back into the global array
    reviewPreviewRows[_deprCurrentRowIndex] = row;

    _deprSnapshot = null;

    // Switch back to view mode with updated data
    renderDeprDetails(row, false);
    setDeprEditMode(false);

    // Refresh the review table row so the updated values are immediately visible
    _refreshTableRow(_deprCurrentRowIndex);
}

// ── Refresh the review table row with updated data after save ──
function _refreshTableRow(rowIndex) {
    var tbody = document.getElementById('review-tbody');
    if (!tbody) return;
    var tr = tbody.querySelector('tr[data-row-index="' + rowIndex + '"]');
    if (!tr) return;

    var row = reviewPreviewRows[rowIndex];
    if (!row) return;

    // Columns (in order): checkbox | row_num | zone | region | cost_center | branch_name | reference_no | category_name
    // We only update the data cells — leave checkbox (td[0]) and row_num badge (td[1]) alone
    var tds = tr.querySelectorAll('td');
    if (tds.length < 8) return;

    tds[2].textContent = row.zone        || '—';
    tds[3].textContent = row.region      || '—';
    tds[4].textContent = row.cost_center || '—';
    tds[5].textContent = row.branch_name || '—';
    tds[6].textContent = row.reference_no  != null ? row.reference_no  : '—';
    tds[7].textContent = row.category_name || '—';

    // Brief highlight to confirm the update visually
    tr.classList.add('bg-red-50');
    setTimeout(function () { tr.classList.remove('bg-red-50'); }, 1200);
}

// ============================================================
//  CLOSE DETAIL MODAL
// ============================================================
function closeAssetDepreciationDetails() {
    _deprIsEditMode      = false;
    _deprCurrentRowIndex = -1;
    _deprSnapshot        = null;
    closeModal('modal-asset-depr-details');
}

// ============================================================
//  UTILITY HELPERS
// ============================================================

// ── Last day of the month — parses YYYY-MM-DD parts directly ──
// Avoids UTC vs local timezone offset issues that cause a "last day - 1" bug
// when using new Date('YYYY-MM-DD') (parsed as UTC midnight, shifted back in PH time).
function _lastDayOfMonth(isoDate) {
    if (!isoDate) return '';
    var parts = String(isoDate).split('-');
    if (parts.length < 2) return '';
    var y = parseInt(parts[0], 10);
    var m = parseInt(parts[1], 10); // 1-based month
    // new Date(y, m, 0): month arg is 0-based, so passing m (1-based) targets next month,
    // and day 0 rolls back to the last day of the current month — all local arithmetic.
    var last = new Date(y, m, 0);
    var yy   = last.getFullYear();
    var mm   = String(last.getMonth() + 1).padStart(2, '0');
    var dd   = String(last.getDate()).padStart(2, '0');
    return yy + '-' + mm + '-' + dd;
}

// Full English date: "March 31, 2026"
function formatDateFull(iso) {
    if (!iso) return '—';
    var parts = String(iso).split('-');
    if (parts.length !== 3) return iso;
    var months = [
        'January','February','March','April','May','June',
        'July','August','September','October','November','December'
    ];
    var d = parseInt(parts[2], 10);
    var m = parseInt(parts[1], 10) - 1;
    var y = parts[0];
    return (months[m] || parts[1]) + ' ' + d + ', ' + y;
}

// Short English date: "Mar 31, 2026"
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
    return String(str)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
}