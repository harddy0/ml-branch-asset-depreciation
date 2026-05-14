// ============================================================
//  issuance-import.js
//  Depends on: main.js (openModal / closeModal)
// ============================================================

// ── Global state ─────────────────────────────────────────────
var issuancePreviewRows = [];
var issuanceSelectedRowNums = new Set();

var _issuanceCurrentRowIndex = -1;
var _issuanceIsEditMode = false;
var _issuanceSnapshot = null;

// Helper to get spinner SVG HTML
function getSpinnerHtml(sizeClass = 'h-4 w-4') {
    return '<svg class="animate-spin ' + sizeClass + ' text-white shrink-0" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">' +
        '<circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>' +
        '<path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>' +
        '</svg>';
}

// Helper to set button loading state
function setButtonLoading(button, isLoading, originalContent = null) {
    if (!button) return;
    
    if (isLoading) {
        // Store original content if not already stored
        if (!button.getAttribute('data-original-content')) {
            button.setAttribute('data-original-content', button.innerHTML);
        }
        button.disabled = true;
        button.innerHTML = getSpinnerHtml() + '<span> ' + (button.getAttribute('data-loading-text') || 'Loading...') + '</span>';
        button.classList.add('inline-flex', 'items-center', 'gap-2');
        button.style.minWidth = button.offsetWidth + 'px';
    } else {
        button.disabled = false;
        var original = button.getAttribute('data-original-content');
        if (original) {
            button.innerHTML = original;
            button.removeAttribute('data-original-content');
        }
        button.style.minWidth = '';
        button.classList.remove('inline-flex', 'items-center', 'gap-2');
    }
}

// =============================================================
//  UPLOAD & PREVIEW (Phase 1)
// =============================================================
document.addEventListener('DOMContentLoaded', function () {
    var dropZone    = document.getElementById('drop-zone');
    var fileInput   = document.getElementById('file-upload');
    var fileDisplay = document.getElementById('file-display');
    var fileNameTxt = document.getElementById('file-name');
    var btnCancel   = document.getElementById('btn-cancel');
    var btnProcess  = document.getElementById('btn-process');

    if (!dropZone || !fileInput || !fileDisplay || !fileNameTxt || !btnCancel || !btnProcess) return;

    // Set loading text attribute for the upload button
    btnProcess.setAttribute('data-loading-text', 'Uploading...');

    dropZone.addEventListener('click', function (e) {
        if (e.target.closest('#btn-process') || e.target.closest('#btn-cancel')) return;
        fileInput.click();
    });

    ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(function (evt) {
        dropZone.addEventListener(evt, function (e) { e.preventDefault(); e.stopPropagation(); }, false);
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
    fileInput.addEventListener('change', function () { handleFiles(this.files); });

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

    btnProcess.addEventListener('click', function (e) {
        e.preventDefault();
        e.stopPropagation();

        if (!fileInput.files.length) { alert('Please select a file first.'); return; }

        // Show spinner on upload button
        setButtonLoading(btnProcess, true);

        var formData = new FormData();
        formData.append('action',      'preview');
        formData.append('import_file', fileInput.files[0]);

        fetch(BASE_URL + '/public/actions/issuance_import_process.php', { method: 'POST', body: formData })
            .then(function (res) {
                if (!res.ok) throw new Error('Server error ' + res.status);
                return res.text();
            })
            .then(function (text) {
                return _parseJsonSafe(text);
            })
            .then(function (data) {
                // Restore button
                setButtonLoading(btnProcess, false);

                if (!data.success) { alert('Error: ' + data.error); return; }

                fileDisplay.classList.add('hidden');
                fileDisplay.classList.remove('flex');
                buildIssuanceReviewModal(data);
                openModal('modal-issuance-import-review');
            })
            .catch(function (err) {
                setButtonLoading(btnProcess, false);
                alert('Failed to parse file: ' + err.message);
            });
    });
});

// =============================================================
//  BUILD REVIEW MODAL TABLE
// =============================================================
function buildIssuanceReviewModal(data) {
    var tbody    = document.getElementById('issuance-review-tbody');
    var summOk   = document.getElementById('issuance-review-summary-ok');
    var summErr  = document.getElementById('issuance-review-summary-err');
    var errNote  = document.getElementById('issuance-review-error-note');
    var errTxt   = document.getElementById('issuance-review-error-note-text');
    var btnConf  = document.getElementById('btn-issuance-confirm-import');
    var selectAll = document.getElementById('issuance-review-select-all');

    if (!tbody || !btnConf) return;
    tbody.innerHTML = '';

    var preview = data.preview || [];
    var okRows  = preview.filter(function (r) { return !r.has_error && !r.is_duplicate; });
    var dupRows = preview.filter(function (r) { return !!r.is_duplicate; });
    var errRows = preview.filter(function (r) { return !!r.has_error && !r.is_duplicate; });

    issuancePreviewRows = preview;
    issuanceSelectedRowNums = new Set();

    summOk.textContent = okRows.length + ' row(s) ready';

    var errParts = [];
    if (dupRows.length) errParts.push(dupRows.length + ' duplicate(s)');
    if (errRows.length) errParts.push(errRows.length + ' error(s)');
    summErr.textContent = errParts.length ? '· ' + errParts.join(', ') + ' will be skipped' : '';

    if (dupRows.length + errRows.length) {
        errNote.classList.remove('hidden');
        var parts = [];
        if (errRows.length) parts.push(errRows.length + ' validation errors');
        if (dupRows.length) parts.push(dupRows.length + ' duplicates');
        errTxt.textContent = parts.join(' · ') + '. These will be skipped. Click a row to fix errors.';
    } else {
        errNote.classList.add('hidden');
    }

    btnConf.disabled = true;
    // Set loading text for save button
    btnConf.setAttribute('data-loading-text', 'Importing...');
    
    if (selectAll) selectAll.checked = false;

    preview.forEach(function (row, rowIndex) {
        var tr = document.createElement('tr');
        tr.className = (rowIndex % 2 === 1 ? 'bg-slate-50' : 'bg-white')
            + ' border-b border-slate-100 hover:bg-slate-200 transition-colors cursor-pointer';
        tr.setAttribute('data-row-index', String(rowIndex));
        if (row.errors && row.errors.length) tr.setAttribute('title', row.errors.join(' | '));

        function cell(val, extra) {
            return '<td class="px-3 py-1 text-slate-900 text-sm font-mono whitespace-nowrap ' + (extra || '') + '">'
                + escHtml(String(val ?? '—')) + '</td>';
        }

        var canSelect = !row.has_error && !row.is_duplicate;

        var checkCell = '<td class="px-3 py-1 text-center whitespace-nowrap">'
            + '<input type="checkbox" class="issuance-row-check w-3.5 h-3.5 rounded border-slate-300 text-[#ce1126] focus:ring-red-200" '
            + 'data-row-num="' + escHtml(String(row.row_num)) + '" '
            + (canSelect ? '' : 'disabled title="Only valid rows can be selected"')
            + '></td>';

        var badgeCell;
        var titleAttr = '';
        if (row.is_duplicate) {
            var dupReason = '';
            if (row.errors && row.errors.length) {
                dupReason = row.errors[0];
            } else {
                dupReason = 'Duplicate transaction detected';
            }
            badgeCell = '<td class="px-3 py-1 whitespace-nowrap"><span class="inline-flex items-center gap-1 bg-orange-100 text-orange-700 text-[10px] font-black px-2 py-0.5 rounded-full cursor-help" title="' + escHtml(dupReason) + '">DUP</span></td>';
        } else if (row.has_error) {
            badgeCell = '<td class="px-3 py-1 whitespace-nowrap"><span class="inline-flex items-center gap-1 bg-red-100 text-red-700 text-[10px] font-black px-2 py-0.5 rounded-full">ERR</span></td>';
        } else {
            badgeCell = '<td class="px-3 py-1 whitespace-nowrap"><span class="inline-flex items-center gap-1 bg-green-100 text-green-700 text-[10px] font-black px-2 py-0.5 rounded-full">OK</span></td>';
        }

        tr.innerHTML = checkCell + badgeCell
            + cell(row.date_issued || '—')
            + cell(row.issuance_number || '—')
            + cell(row.item_code || '—')
            + cell(row.item_description || '—')
            + cell(row.quantity || '—')
            + cell(row.uom || '—')
            + cell(row.cost_center_raw || '—')
            + cell(formatMoney(row.unit_cost), 'text-right')
            + cell(formatMoney(row.total_amount), 'text-right')
            + cell(row.description_remarks || '—')
            + cell(row.product_category || '—')
            + cell(row.zone || '—')
            + cell(row.region || '—')
            + cell(row.branch_name || '—')
            + cell(row.source_status || '—');

        tr.addEventListener('click', function (e) {
            if (e.target.closest('.issuance-row-check')) return;
            openIssuanceDetails(rowIndex);
        });

        tr.querySelector('.issuance-row-check')?.addEventListener('change', function (e) {
            e.stopPropagation();
            var rn = String(this.dataset.rowNum);
            if (this.checked) issuanceSelectedRowNums.add(rn);
            else { issuanceSelectedRowNums.delete(rn); if (selectAll) selectAll.checked = false; }
            btnConf.disabled = issuanceSelectedRowNums.size === 0;
        });

        tbody.appendChild(tr);
    });

    if (selectAll) {
        selectAll.onchange = function () {
            var shouldCheck = this.checked;
            tbody.querySelectorAll('.issuance-row-check:not(:disabled)').forEach(function (cb) {
                cb.checked = shouldCheck;
                var rn = String(cb.dataset.rowNum);
                if (shouldCheck) issuanceSelectedRowNums.add(rn);
                else issuanceSelectedRowNums.delete(rn);
            });
            btnConf.disabled = issuanceSelectedRowNums.size === 0;
        };
    }
}

// =============================================================
//  DETAIL / EDIT MODAL
// =============================================================
function openIssuanceDetails(rowIndex) {
    _issuanceCurrentRowIndex = rowIndex;
    _issuanceIsEditMode      = false;
    _issuanceSnapshot        = null;

    var row = issuancePreviewRows[rowIndex];
    if (!row) return;

    _renderIssuanceView(row);
    _setIssuanceEditMode(false);
    _clearIssuanceModalErrors();
    openModal('modal-issuance-details');
}

function _renderIssuanceView(row) {
    var container = document.getElementById('issuance-view-content');
    if (!container) return;

    var errHtml = '';
    if (row.errors && row.errors.length) {
        errHtml = '<div class="mb-5 px-6 py-2 bg-red-50 border border-red-200 rounded-lg">'
            + '<ul class="list-disc list-inside space-y-0.5">'
            + row.errors.map(function (e) { return '<li class="text-xs text-red-600">' + escHtml(e) + '</li>'; }).join('')
            + '</ul></div>';
    }

    function val(v) {
        return escHtml(String(v === null || v === undefined || v === '' ? '—' : v));
    }

    var titleCls = 'finish-title text-left text-[0.78rem] font-black uppercase tracking-[0.08em] text-red-700 bg-slate-50 border border-slate-200 px-2.5 py-1';
    var labelCls = 'finish-label text-slate-700 text-[0.8rem] font-bold font-mono bg-slate-50 border border-slate-300 px-2.5 py-1 whitespace-nowrap';
    var valueCls = 'finish-value text-slate-900 text-[0.84rem] font-bold font-mono bg-white border border-slate-200 px-2.5 py-1';
    var gapCls   = 'finish-value bg-white border border-slate-200 px-2.5 py-1';

    container.innerHTML = errHtml
        + '<div class="finish-card finish-card--table finish-card--single rounded-xl border border-slate-200 overflow-hidden">'
        + '<table class="finish-table finish-table--single w-full">'
        + '<colgroup>'
        + '<col class="finish-col-label-sm"><col class="finish-col-value-lg">'
        + '<col class="finish-col-label-sm"><col class="finish-col-value-lg">'
        + '</colgroup>'
        + '<tbody>'

        + '<tr><th colspan="4" class="' + titleCls + '">Issuance</th></tr>'
        + '<tr>'
        + '<td class="' + labelCls + '">Date Issued</td><td class="' + valueCls + '">' + val(row.date_issued) + '</td>'
        + '<td class="' + labelCls + '">Issuance Number</td><td class="' + valueCls + '">' + val(row.issuance_number) + '</td>'
        + '</tr>'
        + '<tr><td class="' + labelCls + '">Status</td><td class="' + valueCls + '" colspan="3">' + val(row.source_status || 'done') + '</td></tr>'
        + '<tr><td class="' + gapCls + '" colspan="4">&nbsp;</td></tr>'

        + '<tr><th colspan="4" class="' + titleCls + '">Item</th></tr>'
        + '<tr>'
        + '<td class="' + labelCls + '">Item Code</td><td class="' + valueCls + '">' + val(row.item_code) + '</td>'
        + '<td class="' + labelCls + '">Quantity</td><td class="' + valueCls + '">' + val(row.quantity) + '</td>'
        + '</tr>'
        + '<tr>'
        + '<td class="' + labelCls + '">UoM</td><td class="' + valueCls + '">' + val(row.uom) + '</td>'
        + '<td class="' + labelCls + '">Description</td><td class="' + valueCls + '">' + val(row.item_description) + '</td>'
        + '</tr>'
        + '<tr><td class="' + labelCls + '">Remarks</td><td class="' + valueCls + '" colspan="3">' + val(row.description_remarks) + '</td></tr>'
        + '<tr><td class="' + gapCls + '" colspan="4">&nbsp;</td></tr>'

        + '<tr><th colspan="4" class="' + titleCls + '">Cost</th></tr>'
        + '<tr>'
        + '<td class="' + labelCls + '">Unit Cost</td><td class="' + valueCls + '">' + val(formatMoney(row.unit_cost)) + '</td>'
        + '<td class="' + labelCls + '">Total Amount</td><td class="' + valueCls + '">' + val(formatMoney(row.total_amount)) + '</td>'
        + '</tr>'
        + '<tr><td class="' + labelCls + '">Cost Center</td><td class="' + valueCls + '" colspan="3">' + val(row.cost_center_raw) + '</td></tr>'
        + '<tr><td class="' + gapCls + '" colspan="4">&nbsp;</td></tr>'

        + '<tr><th colspan="4" class="' + titleCls + '">Location</th></tr>'
        + '<tr>'
        + '<td class="' + labelCls + '">Product Category</td><td class="' + valueCls + '">' + val(row.product_category) + '</td>'
        + '<td class="' + labelCls + '">Zone</td><td class="' + valueCls + '">' + val(row.zone) + '</td>'
        + '</tr>'
        + '<tr>'
        + '<td class="' + labelCls + '">Region</td><td class="' + valueCls + '">' + val(row.region) + '</td>'
        + '<td class="' + labelCls + '">Branch Name</td><td class="' + valueCls + '">' + val(row.branch_name) + '</td>'
        + '</tr>'

        + '</tbody></table></div>';
}

function enableIssuanceEdit() {
    var row = issuancePreviewRows[_issuanceCurrentRowIndex];
    if (!row) return;

    _issuanceSnapshot = JSON.parse(JSON.stringify(row));
    _issuanceIsEditMode = true;
    _setIssuanceEditMode(true);
    _populateIssuanceEditForm(row);
}

function cancelIssuanceEdit() {
    _issuanceIsEditMode = false;
    _issuanceSnapshot   = null;
    _setIssuanceEditMode(false);
    _clearIssuanceModalErrors();
    var row = issuancePreviewRows[_issuanceCurrentRowIndex];
    if (row) _renderIssuanceView(row);
}

function _setIssuanceEditMode(editMode) {
    var viewDiv   = document.getElementById('issuance-view-content');
    var editForm  = document.getElementById('issuance-edit-form');
    var badge     = document.getElementById('issuance-edit-badge');
    var hint      = document.getElementById('issuance-unsaved-hint');
    var btnEdit   = document.getElementById('issuance-btn-edit');
    var btnClose  = document.getElementById('issuance-btn-close');
    var btnCancel = document.getElementById('issuance-btn-cancel-edit');
    var btnSave   = document.getElementById('issuance-btn-save');

    if (editMode) {
        if (viewDiv)  viewDiv.classList.add('hidden');
        if (editForm) editForm.classList.remove('hidden');
        if (badge)    badge.classList.remove('hidden');
        if (hint)     hint.classList.remove('hidden');
        if (btnEdit)  btnEdit.classList.add('hidden');
        if (btnClose) btnClose.classList.add('hidden');
        if (btnCancel) btnCancel.classList.remove('hidden');
        if (btnSave)   btnSave.classList.remove('hidden');
    } else {
        if (viewDiv)  viewDiv.classList.remove('hidden');
        if (editForm) editForm.classList.add('hidden');
        if (badge)    badge.classList.add('hidden');
        if (hint)     hint.classList.add('hidden');
        if (btnEdit)  btnEdit.classList.remove('hidden');
        if (btnClose) btnClose.classList.remove('hidden');
        if (btnCancel) btnCancel.classList.add('hidden');
        if (btnSave)   btnSave.classList.add('hidden');
    }
}

function _populateIssuanceEditForm(row) {
    _setVal('iss-f-date', row.date_issued);
    _setVal('iss-f-number', row.issuance_number);
    _setVal('iss-f-item-code', row.item_code);
    _setVal('iss-f-item-desc', row.item_description);
    _setVal('iss-f-qty', row.quantity || 1);
    _setVal('iss-f-uom', row.uom);
    _setVal('iss-f-remarks', row.description_remarks);
    _setVal('iss-f-unit-cost', row.unit_cost);
    _setVal('iss-f-total-amount', row.total_amount);
    _setVal('iss-f-cost-center', row.cost_center_raw);
    _setVal('iss-f-category', row.product_category);
    _setVal('iss-f-zone', row.zone);
    _setVal('iss-f-region', row.region);
    _setVal('iss-f-branch', row.branch_name);
    _setVal('iss-f-status', row.source_status || 'done');
}

// =============================================================
//  SAVE / COMMIT (with spinner on Save button)
// =============================================================
function saveIssuanceEdit() {
    _clearIssuanceModalErrors();
    var row = issuancePreviewRows[_issuanceCurrentRowIndex];
    if (!row) return;

    var dateIssued      = (document.getElementById('iss-f-date')?.value || '').trim();
    var issuanceNumber  = (document.getElementById('iss-f-number')?.value || '').trim();
    var itemCode        = (document.getElementById('iss-f-item-code')?.value || '').trim();
    var itemDesc        = (document.getElementById('iss-f-item-desc')?.value || '').trim();
    var quantity        = parseInt(document.getElementById('iss-f-qty')?.value || 0, 10);
    var uom             = (document.getElementById('iss-f-uom')?.value || '').trim();
    var remarks         = (document.getElementById('iss-f-remarks')?.value || '').trim();
    var unitCost        = parseFloat(document.getElementById('iss-f-unit-cost')?.value || 0);
    var totalAmount     = parseFloat(document.getElementById('iss-f-total-amount')?.value || 0);
    var costCenter      = (document.getElementById('iss-f-cost-center')?.value || '').trim();
    var productCategory = (document.getElementById('iss-f-category')?.value || '').trim();
    var zone            = (document.getElementById('iss-f-zone')?.value || '').trim();
    var region          = (document.getElementById('iss-f-region')?.value || '').trim();
    var branchName      = (document.getElementById('iss-f-branch')?.value || '').trim();
    var sourceStatus    = (document.getElementById('iss-f-status')?.value || '').trim();

    if (!sourceStatus) sourceStatus = 'done';

    if (!Number.isFinite(unitCost)) unitCost = 0;
    if (!Number.isFinite(totalAmount)) totalAmount = 0;

    var errs = [];
    if (!dateIssued) errs.push('Date Issued is required.');
    if (!issuanceNumber) errs.push('Issuance Number is required.');
    if (!itemDesc) errs.push('Item Description is required.');
    if (!costCenter) errs.push('Cost Center is required.');
    if (!productCategory) errs.push('Product Category is required.');
    if (!quantity || quantity <= 0) errs.push('Quantity must be greater than 0.');
    if (unitCost < 0) errs.push('Unit Cost must be 0 or greater.');
    if (totalAmount < 0) errs.push('Total Amount must be 0 or greater.');
    if (unitCost <= 0 && totalAmount <= 0) errs.push('Unit Cost or Total Amount is required.');

    if (errs.length) { _showIssuanceModalErrors(errs); return; }

    if (totalAmount <= 0 && unitCost > 0 && quantity > 0) {
        totalAmount = parseFloat((unitCost * quantity).toFixed(2));
    }
    if (unitCost <= 0 && totalAmount > 0 && quantity > 0) {
        unitCost = parseFloat((totalAmount / quantity).toFixed(2));
    }

    row.date_issued         = dateIssued;
    row.issuance_number     = issuanceNumber;
    row.item_code           = itemCode || null;
    row.item_description    = itemDesc;
    row.quantity            = quantity;
    row.uom                 = uom || null;
    row.cost_center_raw     = costCenter;
    row.unit_cost           = unitCost;
    row.total_amount        = totalAmount;
    row.description_remarks = remarks || null;
    row.product_category    = productCategory;
    row.zone                = zone || null;
    row.region              = region || null;
    row.branch_name         = branchName || null;
    row.source_status       = sourceStatus;
    row.has_error           = false;
    row.is_duplicate        = false;
    row.errors              = [];
    row._edited             = true;

    issuancePreviewRows[_issuanceCurrentRowIndex] = row;
    _refreshIssuanceRow(_issuanceCurrentRowIndex, row);

    _issuanceSnapshot   = null;
    _issuanceIsEditMode = false;
    _setIssuanceEditMode(false);
    _renderIssuanceView(row);
    _clearIssuanceModalErrors();
}

function _refreshIssuanceRow(rowIndex, row) {
    var keepSelected = new Set(issuanceSelectedRowNums);
    keepSelected.add(String(row.row_num));

    buildIssuanceReviewModal({ preview: issuancePreviewRows });

    issuanceSelectedRowNums = new Set();
    var tbody = document.getElementById('issuance-review-tbody');
    var selectAll = document.getElementById('issuance-review-select-all');

    if (tbody) {
        var selectable = 0;
        var selected = 0;
        tbody.querySelectorAll('.issuance-row-check').forEach(function (cb) {
            if (cb.disabled) return;
            selectable++;
            var rn = String(cb.dataset.rowNum || '');
            if (keepSelected.has(rn)) {
                cb.checked = true;
                issuanceSelectedRowNums.add(rn);
                selected++;
            }
        });

        if (selectAll) selectAll.checked = selectable > 0 && selected === selectable;

        var tr = tbody.querySelector('tr[data-row-index="' + rowIndex + '"]');
        if (tr) {
            tr.classList.add('bg-green-50');
            setTimeout(function () { tr.classList.remove('bg-green-50'); }, 1200);
        }
    }

    var btnConf = document.getElementById('btn-issuance-confirm-import');
    if (btnConf) btnConf.disabled = issuanceSelectedRowNums.size === 0;
}

// =============================================================
//  CONFIRM IMPORT (with spinner on Save button in review modal)
// =============================================================
function confirmIssuanceImport() {
    var btnConf = document.getElementById('btn-issuance-confirm-import');
    
    // Show spinner on save button
    if (btnConf) { 
        setButtonLoading(btnConf, true);
    }

    var selectedNums  = Array.from(issuanceSelectedRowNums);
    if (selectedNums.length === 0) {
        selectedNums = (issuancePreviewRows || [])
            .filter(function (r) { return !r.has_error && !r.is_duplicate; })
            .map(function (r) { return String(r.row_num); });
    }

    if (selectedNums.length === 0) {
        if (btnConf) { 
            setButtonLoading(btnConf, false);
        }
        alert('No valid rows are available for import. Please fix row errors first.');
        return;
    }

    var editedRows = issuancePreviewRows.filter(function (r) { return r._edited; });

    var formData = new FormData();
    formData.append('action',        'commit');
    formData.append('selected_rows', JSON.stringify(selectedNums));
    formData.append('edited_rows',   JSON.stringify(editedRows));

    fetch(BASE_URL + '/public/actions/issuance_import_process.php', {
        method: 'POST',
        headers: { 'X-Requested-With': 'XMLHttpRequest' },
        body: formData
    })
        .then(function (res) {
            return res.text().then(function (text) { return { ok: res.ok, status: res.status, text: text }; });
        })
        .then(function (resp) {
            var data;
            try { data = _parseJsonSafe(resp.text); }
            catch (e) {
                if (!resp.ok) throw new Error('Server error ' + resp.status);
                throw e;
            }
            if (!resp.ok) throw new Error((data && data.error) ? data.error : ('Server error ' + resp.status));
            return data;
        })
        .then(function (data) {
            if (data.success) {
                closeIssuanceImportReview();
                window.location.reload();
            } else {
                if (btnConf) { 
                    setButtonLoading(btnConf, false);
                }
                alert('Import failed: ' + (data.error || 'Unknown error'));
            }
        })
        .catch(function (err) {
            if (btnConf) { 
                setButtonLoading(btnConf, false);
            }
            alert('Request failed: ' + err.message);
        });
}

function closeIssuanceDetails() {
    _issuanceIsEditMode      = false;
    _issuanceCurrentRowIndex = -1;
    _issuanceSnapshot        = null;
    _clearIssuanceModalErrors();
    var form = document.getElementById('issuance-edit-form');
    if (form) form.reset();
    closeModal('modal-issuance-details');
}

function closeIssuanceImportReview() {
    issuancePreviewRows = [];
    issuanceSelectedRowNums = new Set();

    var tbody = document.getElementById('issuance-review-tbody');
    var summOk = document.getElementById('issuance-review-summary-ok');
    var summErr = document.getElementById('issuance-review-summary-err');
    var errNote = document.getElementById('issuance-review-error-note');
    var errTxt = document.getElementById('issuance-review-error-note-text');
    var selectAll = document.getElementById('issuance-review-select-all');
    var btnConf = document.getElementById('btn-issuance-confirm-import');

    if (tbody) tbody.innerHTML = '';
    if (summOk) summOk.textContent = '0 row(s) ready';
    if (summErr) summErr.textContent = '';
    if (errTxt) errTxt.textContent = '';
    if (errNote) errNote.classList.add('hidden');
    if (selectAll) selectAll.checked = false;
    if (btnConf) { 
        setButtonLoading(btnConf, false);
        btnConf.disabled = true;
    }

    var fileInput = document.getElementById('file-upload');
    var fileDisplay = document.getElementById('file-display');
    var fileNameTxt = document.getElementById('file-name');
    var btnProcess = document.getElementById('btn-process');

    if (fileInput) fileInput.value = '';
    if (fileNameTxt) fileNameTxt.textContent = '';
    if (fileDisplay) { fileDisplay.classList.add('hidden'); fileDisplay.classList.remove('flex'); }
    if (btnProcess) { 
        setButtonLoading(btnProcess, false);
    }

    closeModal('modal-issuance-details');
    closeModal('modal-issuance-import-review');
}

// =============================================================
//  UTILITY HELPERS
// =============================================================
function _setVal(id, val) {
    var el = document.getElementById(id);
    if (el) el.value = (val === null || val === undefined) ? '' : val;
}

function _clearIssuanceModalErrors() {
    var el = document.getElementById('issuance-modal-errors');
    if (el) { el.textContent = ''; el.classList.add('hidden'); }
}

function _showIssuanceModalErrors(msgs) {
    var el = document.getElementById('issuance-modal-errors');
    if (!el) return;
    el.innerHTML = msgs.map(function (m) { return '<p>' + escHtml(m) + '</p>'; }).join('');
    el.classList.remove('hidden');
}

function _parseJsonSafe(text) {
    var cleaned = String(text || '').replace(/^\uFEFF+/, '').trim();
    if (!cleaned) throw new Error('Empty response from server.');
    try { return JSON.parse(cleaned); }
    catch (e) { throw new Error('Unexpected server response: ' + cleaned.substring(0, 120)); }
}

function formatMoney(val) {
    if (val === null || val === undefined || val === '') return '0.00';
    var num = parseFloat(String(val).replace(/,/g, ''));
    if (Number.isNaN(num)) return '0.00';
    return num.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
}

function escHtml(str) {
    return String(str)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
}