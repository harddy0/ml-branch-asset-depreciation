// ============================================================
//  asset-import.js
//  Depends on: main.js (openModal / closeModal)
// ============================================================

// ── Global state ─────────────────────────────────────────────
var reviewPreviewRows     = [];
var reviewSelectedRowNums = new Set();

var _deprCurrentRowIndex  = -1;
var _deprIsEditMode       = false;
var _deprSnapshot         = null;     // deep-copy of row before edits

// Groups fetched from backend with preview, keyed by group_code
// { [group_code]: { group_code, group_name, actual_months, asset_code, depreciation_code, ... } }
var _availableGroups = {};

// ── Cascade wiring flag (prevents re-attaching listeners) ────
var _cascadeWired = false;

// =============================================================
//  UPLOAD & PREVIEW (file selection → Phase 1 AJAX)
// =============================================================
document.addEventListener('DOMContentLoaded', function () {
    var dropZone    = document.getElementById('drop-zone');
    var fileInput   = document.getElementById('file-upload');
    var fileDisplay = document.getElementById('file-display');
    var fileNameTxt = document.getElementById('file-name');
    var btnCancel   = document.getElementById('btn-cancel');
    var btnProcess  = document.getElementById('btn-process');

    if (!dropZone || !fileInput || !fileDisplay || !fileNameTxt || !btnCancel || !btnProcess) return;

    // Click to browse
    dropZone.addEventListener('click', function (e) {
        if (e.target.closest('#btn-process') || e.target.closest('#btn-cancel')) return;
        fileInput.click();
    });

    // Drag-and-drop
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

    // ── Upload → preview ──────────────────────────────────────
    btnProcess.addEventListener('click', function (e) {
        e.preventDefault();
        e.stopPropagation();

        if (!fileInput.files.length) { alert('Please select a file first.'); return; }

        btnProcess.disabled    = true;
        btnProcess.textContent = 'Uploading…';

        var formData = new FormData();
        formData.append('action',      'preview');
        formData.append('import_file', fileInput.files[0]);

        fetch(BASE_URL + '/public/actions/asset_import_process.php', { method: 'POST', body: formData })
            .then(function (res) {
                if (!res.ok) throw new Error('Server error ' + res.status);
                return res.text();
            })
            .then(function (text) {
                return _parseJsonSafe(text);
            })
            .then(function (data) {
                btnProcess.disabled    = false;
                btnProcess.textContent = 'Upload';

                if (!data.success) { alert('Error: ' + data.error); return; }

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

// =============================================================
//  BUILD REVIEW MODAL TABLE
// =============================================================
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

    var preview = data.preview || [];
    var okRows  = preview.filter(function (r) { return !r.has_error && !r.is_duplicate; });
    var dupRows = preview.filter(function (r) { return !!r.is_duplicate; });
    var errRows = preview.filter(function (r) { return !!r.has_error && !r.is_duplicate; });

    reviewPreviewRows     = preview;
    reviewSelectedRowNums = new Set();
    _availableGroups      = data.groups || {};

    summOk.textContent = okRows.length + ' row(s) ready';

    var errParts = [];
    if (dupRows.length) errParts.push(dupRows.length + ' duplicate(s)');
    if (errRows.length) errParts.push(errRows.length + ' error(s)');
    summErr.textContent = errParts.length ? '· ' + errParts.join(', ') + ' will be skipped' : '';

    if (dupRows.length + errRows.length) {
        errNote.classList.remove('hidden');
        var parts = [];
        if (errRows.length) parts.push(errRows.length + ' row(s) have validation errors');
        if (dupRows.length) parts.push(dupRows.length + ' row(s) are duplicates');
        errTxt.textContent = parts.join(' · ') + '. These will be skipped. Click a row to fix errors.';
    } else {
        errNote.classList.add('hidden');
    }

    btnConf.disabled = true;
    if (selectAll) selectAll.checked = false;

    preview.forEach(function (row, rowIndex) {
        var tr = document.createElement('tr');
        tr.className = (rowIndex % 2 === 1 ? 'bg-slate-50' : 'bg-white')
            + ' border-b border-slate-100 hover:bg-slate-200 transition-colors cursor-pointer';
        tr.setAttribute('data-row-index', String(rowIndex));
        if (row.errors && row.errors.length) tr.setAttribute('title', row.errors.join(' | '));

        function cell(val, extra) {
            return '<td class="px-3 py-2.5 text-slate-700 font-medium whitespace-nowrap ' + (extra || '') + '">'
                + escHtml(String(val ?? '—')) + '</td>';
        }

        var canSelect = !row.has_error && !row.is_duplicate;

        var checkCell = '<td class="px-3 py-2.5 text-center whitespace-nowrap">'
            + '<input type="checkbox" class="review-row-check w-3.5 h-3.5 rounded border-slate-300 text-[#ce1126] focus:ring-red-200" '
            + 'data-row-num="' + escHtml(String(row.row_num)) + '" '
            + (canSelect ? '' : 'disabled title="Only valid rows can be selected"')
            + '></td>';

        var badgeCell;
        if (row.is_duplicate) {
            badgeCell = '<td class="px-3 py-2.5 whitespace-nowrap"><span class="inline-flex items-center gap-1 bg-orange-100 text-orange-700 text-[10px] font-black px-2 py-0.5 rounded-full">'
                + '<svg class="w-3 h-3 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg>'
                + 'DUP ' + escHtml(String(row.row_num)) + '</span></td>';
        } else if (row.has_error) {
            badgeCell = '<td class="px-3 py-2.5 whitespace-nowrap"><span class="inline-flex items-center gap-1 bg-red-100 text-red-700 text-[10px] font-black px-2 py-0.5 rounded-full">'
                + '<svg class="w-3 h-3 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"/></svg>'
                + 'ERR ' + escHtml(String(row.row_num)) + '</span></td>';
        } else {
            badgeCell = '<td class="px-3 py-2.5 whitespace-nowrap"><span class="inline-flex items-center gap-1 bg-green-100 text-green-700 text-[10px] font-black px-2 py-0.5 rounded-full">'
                + '<svg class="w-3 h-3 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>'
                + 'OK ' + escHtml(String(row.row_num)) + '</span></td>';
        }

        tr.innerHTML = checkCell + badgeCell
            + cell(row.serial_number || '—')
            + cell(row.description || '—')
            + cell(row.reference_no || '—')
            + cell(row.quantity || 1)
            + cell(row.property_type || 'PURCHASED')
            + cell(row.group_name || row.group_code || '—')
            + cell(formatMoney(row.acquisition_cost), 'text-right')
            + cell(row.date_received || '—')
            + cell(row.main_zone_code || '—')
            + cell(row.zone_code || '—')
            + cell(row.region_code || '—')
            + cell(row.cost_center_code || '—')
            + cell(row.branch_name || '—')
            + cell(row.item_code || '—')
            + cell(formatMoney(row.cost_unit), 'text-right')
            + cell(row.depreciation_start_date || '—')
            + cell(row.depreciation_on || 'LAST_DAY')
            + cell(row.depreciation_day || 1);

        // Click row → open detail modal
        tr.addEventListener('click', function (e) {
            if (e.target.closest('.review-row-check')) return;
            openAssetDepreciationDetails(rowIndex);
        });

        // Checkbox change
        tr.querySelector('.review-row-check')?.addEventListener('change', function (e) {
            e.stopPropagation();
            var rn = String(this.dataset.rowNum);
            if (this.checked) {
                reviewSelectedRowNums.add(rn);
            } else {
                reviewSelectedRowNums.delete(rn);
                if (selectAll) selectAll.checked = false;
            }
            btnConf.disabled = reviewSelectedRowNums.size === 0;
        });

        tbody.appendChild(tr);
    });

    // Select-all checkbox
    if (selectAll) {
        selectAll.onchange = function () {
            var shouldCheck = this.checked;
            tbody.querySelectorAll('.review-row-check:not(:disabled)').forEach(function (cb) {
                cb.checked = shouldCheck;
                var rn = String(cb.dataset.rowNum);
                if (shouldCheck) {
                    reviewSelectedRowNums.add(rn);
                } else {
                    reviewSelectedRowNums.delete(rn);
                }
            });
            btnConf.disabled = reviewSelectedRowNums.size === 0;
        };
    }
}

// =============================================================
//  OPEN DETAIL / EDIT MODAL  (Phase 3)
// =============================================================
function openAssetDepreciationDetails(rowIndex) {
    _deprCurrentRowIndex = rowIndex;
    _deprIsEditMode      = false;
    _deprSnapshot        = null;

    var row = reviewPreviewRows[rowIndex];
    if (!row) return;

    _renderViewContent(row);
    _setDeprEditMode(false);
    _clearModalErrors();
    openModal('modal-asset-depr-details');
}

// ── View-mode HTML ────────────────────────────────────────────
function _renderViewContent(row) {
    var container = document.getElementById('depr-view-content');
    if (!container) return;

    var errHtml = '';
    if (row.errors && row.errors.length) {
        errHtml = '<div class="mb-5 p-3 bg-red-50 border border-red-200 rounded-lg">'
            + '<p class="text-xs font-black text-red-700 uppercase tracking-wide mb-1">Validation Errors</p>'
            + '<ul class="list-disc list-inside space-y-0.5">'
            + row.errors.map(function (e) {
                return '<li class="text-xs text-red-600">' + escHtml(e) + '</li>';
            }).join('')
            + '</ul></div>';
    }

    function fieldRow(label, value) {
        return '<div><p class="text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-0.5">' + label + '</p>'
            + '<p class="text-sm font-semibold text-slate-700">' + escHtml(String(value ?? '—')) + '</p></div>';
    }

    container.innerHTML = errHtml
        + '<div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">'
        + fieldRow('Main Zone',   row.main_zone_code)
        + fieldRow('Sub-Zone',    row.zone_code)
        + fieldRow('Region',      row.region_code)
        + fieldRow('Cost Center', row.cost_center_code)
        + '</div>'
        + '<div class="grid grid-cols-2 md:grid-cols-3 gap-4 mb-6">'
        + fieldRow('Branch',      row.branch_name)
        + fieldRow('GL Group',    row.group_name || row.group_code)
        + fieldRow('Property',    row.property_type)
        + '</div>'
        + '<div class="grid grid-cols-2 md:grid-cols-3 gap-4 mb-6">'
        + fieldRow('Description', row.description)
        + fieldRow('Reference No', row.reference_no)
        + fieldRow('Serial No',    row.serial_number)
        + '</div>'
        + '<div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">'
        + fieldRow('Date Received',   row.date_received)
        + fieldRow('Depr. Start',     row.depreciation_start_date)
        + fieldRow('Acq. Cost',       '₱ ' + formatMoney(row.acquisition_cost))
        + fieldRow('Monthly Depr.',   '₱ ' + formatMoney(row.monthly_depreciation))
        + '</div>'
        + '<div class="grid grid-cols-2 md:grid-cols-4 gap-4">'
        + fieldRow('Asset Code (CR)', row.asset_code)
        + fieldRow('Depr. Code (DR)', row.depreciation_code)
        + fieldRow('Depreciate On',   row.depreciation_on)
        + fieldRow('System Code',     row.system_asset_code)
        + '</div>';
}

// =============================================================
//  ENABLE / DISABLE EDIT MODE
// =============================================================
function enableDeprEdit() {
    var row = reviewPreviewRows[_deprCurrentRowIndex];
    if (!row) return;

    // Snapshot before changes
    _deprSnapshot = JSON.parse(JSON.stringify(row));
    _deprIsEditMode = true;
    _setDeprEditMode(true);
    _populateEditForm(row);
}

function cancelDeprEdit() {
    _deprIsEditMode = false;
    _deprSnapshot   = null;
    _setDeprEditMode(false);
    _clearModalErrors();
    var row = reviewPreviewRows[_deprCurrentRowIndex];
    if (row) _renderViewContent(row);
}

function _setDeprEditMode(editMode) {
    var viewDiv   = document.getElementById('depr-view-content');
    var editForm  = document.getElementById('depr-edit-form');
    var badge     = document.getElementById('depr-edit-badge');
    var hint      = document.getElementById('depr-unsaved-hint');
    var btnEdit   = document.getElementById('depr-btn-edit');
    var btnClose  = document.getElementById('depr-btn-close');
    var btnCancel = document.getElementById('depr-btn-cancel-edit');
    var btnSave   = document.getElementById('depr-btn-save');

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

// =============================================================
//  POPULATE EDIT FORM  (fills all fields, loads cascaded dropdowns)
// =============================================================
function _populateEditForm(row) {
    _wireFormEvents();            // attach once
    _populateGroupDropdown();     // fill GL group options

    // Simple fields
    _setVal('depr-f-description',  row.description);
    _setVal('depr-f-refno',        row.reference_no);
    _setVal('depr-f-serial',       row.serial_number);
    _setVal('depr-f-itemcode',     row.item_code);
    _setVal('depr-f-date-received', row.date_received);
    _setVal('depr-f-depr-start',   row.depreciation_start_date);
    _setVal('depr-f-acq-cost',     row.acquisition_cost);
    _setVal('depr-f-cost-unit',    row.cost_unit || row.acquisition_cost);
    _setVal('depr-f-monthly-dep',  row.monthly_depreciation);
    _setVal('depr-f-quantity',     row.quantity || 1);
    _setVal('depr-f-branchcode',   row.branch_code || '');
    _setVal('depr-f-system-code',  row.system_asset_code || '');

    _selectVal('depr-f-property-type', row.property_type || 'PURCHASED');
    _selectVal('depr-f-depr-on',       row.depreciation_on || 'LAST_DAY');
    _setVal('depr-f-depr-day',         row.depreciation_day || 1);
    _updateDeprDayLock();
    _computeDepreciationStartDate();

    // GL group (after dropdown is populated)
    if (row.group_code) {
        _selectVal('depr-f-group', row.group_code);
        _applyGroupDetails(row.group_code);
    }

    // Cascaded location dropdowns — async chain
    _loadCascadedLocation(row);
}

// Async chain: main zone → zone → region → branch → cost center
async function _loadCascadedLocation(row) {
    var elMZ = document.getElementById('depr-f-mainzone');
    var elZ  = document.getElementById('depr-f-zone');
    var elR  = document.getElementById('depr-f-region');
    var elB  = document.getElementById('depr-f-branch');
    var elCC = document.getElementById('depr-f-costcenter');
    var elBC = document.getElementById('depr-f-branchcode');

    // 1. Main zones
    var mzList = await _fetchLocation('main_zones', '');
    _fillDropdown(elMZ, mzList, '— Select Main Zone —');
    _trySelect(elMZ, row.main_zone_code);

    // 2. Sub-zones
    var zList = await _fetchLocation('zones', row.main_zone_code || '');
    _fillDropdown(elZ, zList, '— Select Sub-Zone —');
    _trySelect(elZ, row.zone_code);

    // 3. Regions
    var rList = await _fetchLocation('regions', row.zone_code || '');
    _fillDropdown(elR, rList, '— Select Region —');
    _trySelect(elR, row.region_code);

    // 4. Branches
    var bList = await _fetchLocation('branches', row.region_code || '');
    _fillBranchDropdown(elB, bList, '— Select Branch —');
    _trySelect(elB, row.branch_name);

    // 5. Cost center
    if (elCC) elCC.value = row.cost_center_code || '';

    // Keep hidden branch code synced after pre-select.
    if (elB) {
        var selected = elB.options[elB.selectedIndex];
        if (elBC) elBC.value = selected ? (selected.dataset.branchcode || row.branch_code || '') : (row.branch_code || '');
        if (elCC && !elCC.value && selected) elCC.value = selected.dataset.costcenter || '';
    }
}

// =============================================================
//  LOCATION CASCADE HELPERS
// =============================================================
async function _fetchLocation(level, filter) {
    try {
        var url = BASE_URL + '/public/api/get_locations.php?level=' + encodeURIComponent(level)
                + '&filter=' + encodeURIComponent(filter || '');
        var res  = await fetch(url);
        var data = await res.json();
        return data.success ? data.data : [];
    } catch (e) {
        return [];
    }
}

function _fillDropdown(el, items, placeholder) {
    if (!el) return;
    el.innerHTML = '<option value="">' + escHtml(placeholder) + '</option>';
    (items || []).forEach(function (item) {
        var opt = document.createElement('option');
        opt.value       = item.value;
        opt.textContent = item.label;
        el.appendChild(opt);
    });
}

function _fillBranchDropdown(el, items, placeholder) {
    if (!el) return;
    el.innerHTML = '<option value="">' + escHtml(placeholder) + '</option>';
    (items || []).forEach(function (item) {
        var opt = document.createElement('option');
        opt.value = item.value;   // branch_name
        opt.textContent = item.label;
        opt.dataset.costcenter  = item.cost_center_code || '';
        opt.dataset.branchcode  = item.branch_code      || item.cost_center_code || '';
        opt.dataset.zonecode    = item.zone_code        || '';
        opt.dataset.mainzone    = item.main_zone_code   || '';
        opt.dataset.regioncode  = item.region_code      || '';
        el.appendChild(opt);
    });
}

function _trySelect(el, value) {
    if (!el || !value) return;
    for (var i = 0; i < el.options.length; i++) {
        if (el.options[i].value === value) {
            el.selectedIndex = i;
            return;
        }
    }
}

// =============================================================
//  WIRE FORM EVENTS (once per modal lifecycle)
// =============================================================
function _wireFormEvents() {
    if (_cascadeWired) return;
    _cascadeWired = true;

    var elMZ = document.getElementById('depr-f-mainzone');
    var elZ  = document.getElementById('depr-f-zone');
    var elR  = document.getElementById('depr-f-region');
    var elB  = document.getElementById('depr-f-branch');
    var elCC = document.getElementById('depr-f-costcenter');
    var elBC = document.getElementById('depr-f-branchcode');

    // Main zone → zones
    if (elMZ) {
        elMZ.addEventListener('change', async function () {
            var zones = await _fetchLocation('zones', this.value);
            _fillDropdown(elZ, zones, '— Select Sub-Zone —');
            if (elR) _fillDropdown(elR, [], '— Select Region —');
            if (elB) _fillBranchDropdown(elB, [], '— Select Branch —');
            if (elCC) elCC.value = '';
            if (elBC) elBC.value = '';
        });
    }

    // Zone → regions
    if (elZ) {
        elZ.addEventListener('change', async function () {
            var regions = await _fetchLocation('regions', this.value);
            _fillDropdown(elR, regions, '— Select Region —');
            if (elB) _fillBranchDropdown(elB, [], '— Select Branch —');
            if (elCC) elCC.value = '';
            if (elBC) elBC.value = '';
        });
    }

    // Region → branches
    if (elR) {
        elR.addEventListener('change', async function () {
            var branches = await _fetchLocation('branches', this.value);
            _fillBranchDropdown(elB, branches, '— Select Branch —');
            if (elCC) elCC.value = '';
            if (elBC) elBC.value = '';
        });
    }

    // Branch → auto-fill cost center + branch code
    if (elB) {
        elB.addEventListener('change', function () {
            var opt = this.options[this.selectedIndex];
            if (elCC) elCC.value = opt ? (opt.dataset.costcenter || '') : '';
            if (elBC) elBC.value = opt ? (opt.dataset.branchcode  || '') : '';
        });
    }

    // GL group → auto-fill GL boxes + recalculate monthly dep
    var elGroup = document.getElementById('depr-f-group');
    if (elGroup) {
        elGroup.addEventListener('change', function () {
            _applyGroupDetails(this.value);
        });
    }

    // Acquisition cost → mirror cost unit + recalculate monthly dep
    var elAcq = document.getElementById('depr-f-acq-cost');
    if (elAcq) {
        elAcq.addEventListener('input', function () {
            var cu = document.getElementById('depr-f-cost-unit');
            if (cu && (parseFloat(cu.value) === 0 || cu.value === '')) cu.value = this.value;
            _recalculateMonthlyDep();
        });
    }

    // Date received → recompute depreciation start
    var elDR = document.getElementById('depr-f-date-received');
    if (elDR) {
        elDR.addEventListener('change', function () {
            _computeDepreciationStartDate();
        });
    }

    // Depreciate On → lock/unlock specific day
    var elDO = document.getElementById('depr-f-depr-on');
    if (elDO) {
        elDO.addEventListener('change', function () {
            _updateDeprDayLock();
            _computeDepreciationStartDate();
        });
    }

    var elDay = document.getElementById('depr-f-depr-day');
    if (elDay) {
        elDay.addEventListener('input', _computeDepreciationStartDate);
        elDay.addEventListener('change', _computeDepreciationStartDate);
    }
}

// =============================================================
//  GL GROUP HELPERS
// =============================================================
function _populateGroupDropdown() {
    var el = document.getElementById('depr-f-group');
    if (!el) return;

    el.innerHTML = '<option value="">— Select GL Group —</option>';
    Object.values(_availableGroups).forEach(function (g) {
        var opt = document.createElement('option');
        opt.value       = g.group_code;
        opt.textContent = g.group_name;
        el.appendChild(opt);
    });
}

function _applyGroupDetails(groupCode) {
    var g = _availableGroups[groupCode];
    _setVal('gl-group-code-display', g ? g.group_code        : '');
    _setVal('gl-asset-code-display', g ? g.asset_code + ' — ' + (g.asset_name || '') : '');
    _setVal('gl-dep-code-display',   g ? g.depreciation_code + ' — ' + (g.depreciation_description || '') : '');
    _recalculateMonthlyDep();
}

function _recalculateMonthlyDep() {
    var groupCode = document.getElementById('depr-f-group')?.value || '';
    var g         = _availableGroups[groupCode];
    var acq       = parseFloat(document.getElementById('depr-f-acq-cost')?.value || 0);
    var monthly   = document.getElementById('depr-f-monthly-dep');
    if (monthly) {
        monthly.value = (g && g.actual_months > 0 && acq > 0)
            ? (acq / g.actual_months).toFixed(2)
            : '';
    }
}

// =============================================================
//  MISC FORM HELPERS
// =============================================================
function _updateDeprDayLock() {
    var elDO  = document.getElementById('depr-f-depr-on');
    var elDay = document.getElementById('depr-f-depr-day');
    if (!elDO || !elDay) return;
    var isSpecific = elDO.value === 'SPECIFIC_DATE';
    elDay.readOnly = !isSpecific;
    elDay.disabled = !isSpecific;
    elDay.classList.toggle('bg-white',      isSpecific);
    elDay.classList.toggle('text-slate-700', isSpecific);
    elDay.classList.toggle('bg-slate-50',   !isSpecific);
    elDay.classList.toggle('text-slate-400', !isSpecific);
    if (!isSpecific) elDay.value = 1;
}

function _computeDepreciationStartDate() {
    var elDate = document.getElementById('depr-f-date-received');
    var elOn   = document.getElementById('depr-f-depr-on');
    var elDay  = document.getElementById('depr-f-depr-day');
    var elOut  = document.getElementById('depr-f-depr-start');

    if (!elDate || !elOn || !elOut || !elDate.value) return;

    var parts = String(elDate.value).split('-');
    if (parts.length !== 3) return;

    var year  = parseInt(parts[0], 10);
    var month = parseInt(parts[1], 10);
    if (!year || !month) return;

    if (elOn.value === 'FIRST_DAY') {
        elOut.value = _firstDayOfMonth(elDate.value);
        return;
    }

    if (elOn.value === 'SPECIFIC_DATE') {
        var requested = parseInt(elDay?.value || '1', 10);
        if (!requested || requested < 1) requested = 1;

        var last = new Date(year, month, 0).getDate();
        var clamped = Math.min(requested, last);

        elOut.value = year
            + '-' + String(month).padStart(2, '0')
            + '-' + String(clamped).padStart(2, '0');
        return;
    }

    // Default LAST_DAY
    elOut.value = _lastDayOfMonth(elDate.value);
}

function _setVal(id, val) {
    var el = document.getElementById(id);
    if (el) el.value = (val === null || val === undefined) ? '' : val;
}

function _selectVal(id, val) {
    var el = document.getElementById(id);
    if (!el) return;
    for (var i = 0; i < el.options.length; i++) {
        if (el.options[i].value === val) { el.selectedIndex = i; return; }
    }
}

function _clearModalErrors() {
    var el = document.getElementById('depr-modal-errors');
    if (el) { el.textContent = ''; el.classList.add('hidden'); }
}

function _showModalErrors(msgs) {
    var el = document.getElementById('depr-modal-errors');
    if (!el) return;
    el.innerHTML = msgs.map(function (m) { return '<p>' + escHtml(m) + '</p>'; }).join('');
    el.classList.remove('hidden');
}

function _parseJsonSafe(text) {
    var cleaned = String(text || '').replace(/^\uFEFF+/, '').trim();
    if (!cleaned) {
        throw new Error('Empty response from server.');
    }

    try {
        return JSON.parse(cleaned);
    } catch (e) {
        throw new Error('Unexpected server response: ' + cleaned.substring(0, 120));
    }
}

// =============================================================
//  SAVE EDIT  (Phase 4 — state reconciliation)
// =============================================================
function saveDeprEdit() {
    _clearModalErrors();

    var row = reviewPreviewRows[_deprCurrentRowIndex];
    if (!row) return;

    // ── Read DOM ───────────────────────────────────────────────
    var mainZoneCode      = document.getElementById('depr-f-mainzone')?.value       || '';
    var zoneCode          = document.getElementById('depr-f-zone')?.value           || '';
    var regionCode        = document.getElementById('depr-f-region')?.value         || '';
    var branchName        = document.getElementById('depr-f-branch')?.value         || '';
    var costCenterCode    = document.getElementById('depr-f-costcenter')?.value     || '';
    var branchCode        = document.getElementById('depr-f-branchcode')?.value     || '';
    var groupCode         = document.getElementById('depr-f-group')?.value          || '';
    var propertyType      = document.getElementById('depr-f-property-type')?.value  || 'PURCHASED';
    var description       = (document.getElementById('depr-f-description')?.value   || '').trim();
    var refNo             = document.getElementById('depr-f-refno')?.value          || '';
    var serialNumber      = document.getElementById('depr-f-serial')?.value         || '';
    var itemCode          = document.getElementById('depr-f-itemcode')?.value       || '';
    var dateReceived      = document.getElementById('depr-f-date-received')?.value  || '';
    var deprStart         = document.getElementById('depr-f-depr-start')?.value     || '';
    var acqCost           = parseFloat(document.getElementById('depr-f-acq-cost')?.value  || 0);
    var costUnit          = parseFloat(document.getElementById('depr-f-cost-unit')?.value || 0);
    var monthlyDep        = parseFloat(document.getElementById('depr-f-monthly-dep')?.value || 0);
    var quantity          = parseInt(document.getElementById('depr-f-quantity')?.value || 1, 10);
    var deprOn            = document.getElementById('depr-f-depr-on')?.value        || 'LAST_DAY';
    var deprDay           = parseInt(document.getElementById('depr-f-depr-day')?.value || 1, 10);

    // Preserve existing values when async cascades have not populated selects yet.
    if (!mainZoneCode)   mainZoneCode = row.main_zone_code || '';
    if (!zoneCode)       zoneCode = row.zone_code || '';
    if (!regionCode)     regionCode = row.region_code || '';
    if (!branchName)     branchName = row.branch_name || '';
    if (!costCenterCode) costCenterCode = row.cost_center_code || '';
    if (!branchCode)     branchCode = row.branch_code || costCenterCode || '';
    if (!groupCode)      groupCode = row.group_code || '';

    // ── Local validation ───────────────────────────────────────
    var errs = [];
    if (!costCenterCode) errs.push('Cost Center is required — please select a Branch.');
    if (acqCost <= 0)    errs.push('Investment must be greater than zero.');
    if (!groupCode)      errs.push('GL Asset Group is required.');
    if (!description)    errs.push('Description is required.');
    if (!dateReceived)   errs.push('Date Received is required.');

    if (errs.length) { _showModalErrors(errs); return; }

    // ── Resolve group details ──────────────────────────────────
    var g           = _availableGroups[groupCode] || {};
    var actualMonths = g.actual_months || 0;
    var assetCode    = g.asset_code    || row.asset_code    || '';
    var depCode      = g.depreciation_code || row.depreciation_code || '';
    var groupName    = g.group_name    || row.group_name    || groupCode;

    if (monthlyDep <= 0 && actualMonths > 0 && acqCost > 0) {
        monthlyDep = parseFloat((acqCost / actualMonths).toFixed(2));
    }

    // ── Recompute depr start if changed ───────────────────────
    if (dateReceived && !deprStart) {
        deprStart = _lastDayOfMonth(dateReceived);
    }

    // ── Rebuild system_asset_code ──────────────────────────────
    var suffix = refNo.trim() !== ''
        ? refNo.trim()
        : (row.system_asset_code || '').split('-').pop() || Math.random().toString(36).substring(2, 7).toUpperCase();

    var newSystemCode = [assetCode, zoneCode, branchCode || costCenterCode, suffix].join('-');

    // ── Merge into state ───────────────────────────────────────
    row.main_zone_code          = mainZoneCode;
    row.zone_code               = zoneCode;
    row.region_code             = regionCode;
    row.branch_name             = branchName;
    row.cost_center_code        = costCenterCode;
    row.branch_code             = branchCode;
    row.group_code              = groupCode;
    row.group_name              = groupName;
    row.asset_code              = assetCode;
    row.depreciation_code       = depCode;
    row.actual_months           = actualMonths;
    row.property_type           = propertyType;
    row.description             = description;
    row.reference_no            = refNo.trim() || null;
    row.serial_number           = serialNumber.trim() || null;
    row.item_code               = itemCode.trim() || null;
    row.date_received           = dateReceived;
    row.depreciation_start_date = deprStart;
    row.acquisition_cost        = acqCost;
    row.cost_unit               = costUnit > 0 ? costUnit : acqCost;
    row.monthly_depreciation    = monthlyDep;
    row.quantity                = quantity;
    row.depreciation_on         = deprOn;
    row.depreciation_day        = deprDay;
    row.system_asset_code       = newSystemCode;
    row.has_error               = false;
    row.is_duplicate            = false;
    row.errors                  = [];
    row._edited                 = true;

    reviewPreviewRows[_deprCurrentRowIndex] = row;

    // ── Update the review table row ────────────────────────────
    _refreshTableRow(_deprCurrentRowIndex, row);

    // ── Snapshot no longer needed ──────────────────────────────
    _deprSnapshot   = null;
    _deprIsEditMode = false;
    _setDeprEditMode(false);
    _renderViewContent(row);
    _clearModalErrors();
}

// Re-render a single table row after save
function _refreshTableRow(rowIndex, row) {
    var keepSelected = new Set(reviewSelectedRowNums);
    keepSelected.add(String(row.row_num));

    buildReviewModal({ preview: reviewPreviewRows, groups: _availableGroups });

    reviewSelectedRowNums = new Set();
    var tbody = document.getElementById('review-tbody');
    var selectAll = document.getElementById('review-select-all');

    if (tbody) {
        var selectable = 0;
        var selected = 0;
        tbody.querySelectorAll('.review-row-check').forEach(function (cb) {
            if (cb.disabled) return;
            selectable++;
            var rn = String(cb.dataset.rowNum || '');
            if (keepSelected.has(rn)) {
                cb.checked = true;
                reviewSelectedRowNums.add(rn);
                selected++;
            }
        });

        if (selectAll) {
            selectAll.checked = selectable > 0 && selected === selectable;
        }

        var tr = tbody.querySelector('tr[data-row-index="' + rowIndex + '"]');
        if (tr) {
            tr.classList.add('bg-green-50');
            setTimeout(function () { tr.classList.remove('bg-green-50'); }, 1200);
        }
    }

    var btnConf = document.getElementById('btn-confirm-import');
    if (btnConf) btnConf.disabled = reviewSelectedRowNums.size === 0;
}

// =============================================================
//  CLOSE MODAL
// =============================================================
function closeAssetDepreciationDetails() {
    _deprIsEditMode      = false;
    _deprCurrentRowIndex = -1;
    _deprSnapshot        = null;
    _clearModalErrors();
    // Reset form so next open is clean
    var form = document.getElementById('depr-edit-form');
    if (form) form.reset();
    closeModal('modal-asset-depr-details');
}

function closeImportReview() {
    reviewPreviewRows = [];
    reviewSelectedRowNums = new Set();
    _availableGroups = {};

    var tbody = document.getElementById('review-tbody');
    var summOk = document.getElementById('review-summary-ok');
    var summErr = document.getElementById('review-summary-err');
    var errNote = document.getElementById('review-error-note');
    var errTxt = document.getElementById('review-error-note-text');
    var selectAll = document.getElementById('review-select-all');
    var btnConf = document.getElementById('btn-confirm-import');

    if (tbody) tbody.innerHTML = '';
    if (summOk) summOk.textContent = '0 row(s) ready';
    if (summErr) summErr.textContent = '';
    if (errTxt) errTxt.textContent = '';
    if (errNote) errNote.classList.add('hidden');
    if (selectAll) selectAll.checked = false;
    if (btnConf) {
        btnConf.disabled = true;
        btnConf.textContent = 'Confirm Import';
    }

    var fileInput = document.getElementById('file-upload');
    var fileDisplay = document.getElementById('file-display');
    var fileNameTxt = document.getElementById('file-name');
    var btnProcess = document.getElementById('btn-process');

    if (fileInput) fileInput.value = '';
    if (fileNameTxt) fileNameTxt.textContent = '';
    if (fileDisplay) {
        fileDisplay.classList.add('hidden');
        fileDisplay.classList.remove('flex');
    }
    if (btnProcess) {
        btnProcess.disabled = false;
        btnProcess.textContent = 'Upload';
    }

    closeModal('modal-asset-depr-details');
    closeModal('modal-import-review');
}

// =============================================================
//  CONFIRM IMPORT  (Phase 5 — bulk commit)
// =============================================================
function confirmImport() {
    var btnConf = document.getElementById('btn-confirm-import');
    if (btnConf) { btnConf.disabled = true; btnConf.textContent = 'Importing…'; }

    var selectedNums  = Array.from(reviewSelectedRowNums);
    if (selectedNums.length === 0) {
        selectedNums = (reviewPreviewRows || [])
            .filter(function (r) { return !r.has_error && !r.is_duplicate; })
            .map(function (r) { return String(r.row_num); });
    }

    if (selectedNums.length === 0) {
        if (btnConf) { btnConf.disabled = false; btnConf.textContent = 'Confirm Import'; }
        alert('No valid rows are available for import. Please fix row errors first.');
        return;
    }

    var editedRows    = reviewPreviewRows.filter(function (r) { return r._edited; });

    var formData = new FormData();
    formData.append('action',        'commit');
    formData.append('selected_rows', JSON.stringify(selectedNums));
    formData.append('edited_rows',   JSON.stringify(editedRows));

    fetch(BASE_URL + '/public/actions/asset_import_process.php', {
        method: 'POST',
        headers: { 'X-Requested-With': 'XMLHttpRequest' },
        body: formData
    })
        .then(function (res) {
            return res.text().then(function (text) {
                return { ok: res.ok, status: res.status, text: text };
            });
        })
        .then(function (resp) {
            var data;
            try {
                data = _parseJsonSafe(resp.text);
            } catch (e) {
                if (!resp.ok) {
                    throw new Error('Server error ' + resp.status);
                }
                throw e;
            }

            if (!resp.ok) {
                throw new Error((data && data.error) ? data.error : ('Server error ' + resp.status));
            }

            return data;
        })
        .then(function (data) {
            if (data.success) {
                closeImportReview();
                // Trigger a full page reload to show flash message from session
                window.location.reload();
            } else {
                if (btnConf) { btnConf.disabled = false; btnConf.textContent = 'Confirm Import'; }
                alert('Import failed: ' + (data.error || 'Unknown error'));
            }
        })
        .catch(function (err) {
            if (btnConf) { btnConf.disabled = false; btnConf.textContent = 'Confirm Import'; }
            alert('Request failed: ' + err.message);
        });
}

// =============================================================
//  UTILITY HELPERS (shared with other modules)
// =============================================================

// Last day of a YYYY-MM-DD month — avoids UTC timezone shift bugs
function _lastDayOfMonth(isoDate) {
    if (!isoDate) return '';
    var parts = String(isoDate).split('-');
    if (parts.length < 2) return '';
    var y    = parseInt(parts[0], 10);
    var m    = parseInt(parts[1], 10);
    var last = new Date(y, m, 0);
    return last.getFullYear()
        + '-' + String(last.getMonth() + 1).padStart(2, '0')
        + '-' + String(last.getDate()).padStart(2, '0');
}

function _firstDayOfMonth(isoDate) {
    if (!isoDate) return '';
    var parts = String(isoDate).split('-');
    if (parts.length < 2) return '';
    var y = parseInt(parts[0], 10);
    var m = parseInt(parts[1], 10);
    if (!y || !m) return '';
    return y + '-' + String(m).padStart(2, '0') + '-01';
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