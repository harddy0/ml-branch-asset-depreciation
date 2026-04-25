// ============================================================
//  asset-import.js
//  Depends on: main.js (openModal / closeModal)
// ============================================================

// ── Global state ─────────────────────────────────────────────
var reviewPreviewRows     = [];
var reviewSelectedRowNums = new Set();

var _deprCurrentRowIndex  = -1;
var _deprIsEditMode       = false;
var _deprSnapshot         = null;

// Groups fetched from backend, keyed by integer ID
var _availableGroups = {};
var _cascadeWired = false;

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
    
    // Map backend array to dict keyed by ID for O(1) lookups
    _availableGroups = {};
    if (data.groups) {
        data.groups.forEach(function(g) {
            _availableGroups[g.id] = g;
        });
    }

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
            badgeCell = '<td class="px-3 py-2.5 whitespace-nowrap"><span class="inline-flex items-center gap-1 bg-orange-100 text-orange-700 text-[10px] font-black px-2 py-0.5 rounded-full">DUP</span></td>';
        } else if (row.has_error) {
            badgeCell = '<td class="px-3 py-2.5 whitespace-nowrap"><span class="inline-flex items-center gap-1 bg-red-100 text-red-700 text-[10px] font-black px-2 py-0.5 rounded-full">ERR</span></td>';
        } else {
            badgeCell = '<td class="px-3 py-2.5 whitespace-nowrap"><span class="inline-flex items-center gap-1 bg-green-100 text-green-700 text-[10px] font-black px-2 py-0.5 rounded-full">OK</span></td>';
        }

        tr.innerHTML = checkCell + badgeCell
            + cell(row.serial_number || '—')
            + cell(row.description || '—')
            + cell(row.reference_no || '—')
            + cell(row.quantity || 1)
            + cell(row.property_type || 'PURCHASED')
            + cell(row.group_name || '—')
            + cell(formatMoney(row.acquisition_cost), 'text-right')
            + cell(row.date_received || '—')
            + cell(row.main_zone_code || '—')
            + cell(row.zone_code || '—')
            + cell(row.region_code || '—')
            + cell(row.cost_center_code || '—')
            + cell(row.branch_name || '—')
            + cell(row.item_code || '—')
            + cell(row.depreciation_start_date || '—');

        tr.addEventListener('click', function (e) {
            if (e.target.closest('.review-row-check')) return;
            openAssetDepreciationDetails(rowIndex);
        });

        tr.querySelector('.review-row-check')?.addEventListener('change', function (e) {
            e.stopPropagation();
            var rn = String(this.dataset.rowNum);
            if (this.checked) reviewSelectedRowNums.add(rn);
            else { reviewSelectedRowNums.delete(rn); if (selectAll) selectAll.checked = false; }
            btnConf.disabled = reviewSelectedRowNums.size === 0;
        });

        tbody.appendChild(tr);
    });

    if (selectAll) {
        selectAll.onchange = function () {
            var shouldCheck = this.checked;
            tbody.querySelectorAll('.review-row-check:not(:disabled)').forEach(function (cb) {
                cb.checked = shouldCheck;
                var rn = String(cb.dataset.rowNum);
                if (shouldCheck) reviewSelectedRowNums.add(rn);
                else reviewSelectedRowNums.delete(rn);
            });
            btnConf.disabled = reviewSelectedRowNums.size === 0;
        };
    }
}

// =============================================================
//  DETAIL / EDIT MODAL
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

function _renderViewContent(row) {
    var container = document.getElementById('depr-view-content');
    if (!container) return;

    var errHtml = '';
    if (row.errors && row.errors.length) {
        errHtml = '<div class="mb-5 p-3 bg-red-50 border border-red-200 rounded-lg">'
            + '<p class="text-xs font-black text-red-700 uppercase tracking-wide mb-1">Validation Errors</p>'
            + '<ul class="list-disc list-inside space-y-0.5">'
            + row.errors.map(function (e) { return '<li class="text-xs text-red-600">' + escHtml(e) + '</li>'; }).join('')
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
        + fieldRow('GL Group',    row.group_name)
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
        + '<div class="grid grid-cols-2 md:grid-cols-2 gap-4">'
        + fieldRow('System Code',     row.system_asset_code)
        + fieldRow('Status',          row.status || 'ACTIVE')
        + '</div>';
}

function enableDeprEdit() {
    var row = reviewPreviewRows[_deprCurrentRowIndex];
    if (!row) return;

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

function _populateEditForm(row) {
    _wireFormEvents();
    _populateGroupDropdown();

    _setVal('depr-f-description',  row.description);
    _setVal('depr-f-refno',        row.reference_no);
    _setVal('depr-f-serial',       row.serial_number);
    _setVal('depr-f-itemcode',     row.item_code);
    _setVal('depr-f-date-received', row.date_received);
    _setVal('depr-f-depr-start',   row.depreciation_start_date);
    _setVal('depr-f-acq-cost',     row.acquisition_cost);
    _setVal('depr-f-monthly-dep',  row.monthly_depreciation);
    _setVal('depr-f-quantity',     row.quantity || 1);
    _setVal('depr-f-branchcode',   row.branch_code || '');
    _setVal('depr-f-bos-code',     row.bos_branch_code || '');
    _setVal('depr-f-kpx-id',       row.kpx_branch_id || '');
    _setVal('depr-f-corp-name',    row.corporate_name || '');
    _setVal('depr-f-system-code',  row.system_asset_code || '');
    _selectVal('depr-f-property-type', row.property_type || 'PURCHASED');

    if (row.asset_group_id) {
        _selectVal('depr-f-group', String(row.asset_group_id));
        _recalculateMonthlyDep();
    }

    _loadCascadedLocation(row);
}

// =============================================================
//  LOCATIONS & CASCADES
// =============================================================
async function _loadCascadedLocation(row) {
    var elMZ = document.getElementById('depr-f-mainzone');
    var elZ  = document.getElementById('depr-f-zone');
    var elR  = document.getElementById('depr-f-region');
    var elB  = document.getElementById('depr-f-branch');
    var elCC = document.getElementById('depr-f-costcenter');
    var elBC = document.getElementById('depr-f-branchcode');

    var mzList = await _fetchLocation('main_zones', '');
    _fillDropdown(elMZ, mzList, '— Select Main Zone —');
    _trySelect(elMZ, row.main_zone_code);

    var zList = await _fetchLocation('zones', row.main_zone_code || '');
    _fillDropdown(elZ, zList, '— Select Sub-Zone —');
    _trySelect(elZ, row.zone_code);

    var rList = await _fetchLocation('regions', row.zone_code || '');
    _fillDropdown(elR, rList, '— Select Region —');
    _trySelect(elR, row.region_code);

    var bList = await _fetchLocation('branches', row.region_code || '');
    _fillBranchDropdown(elB, bList, '— Select Branch —');
    _trySelect(elB, row.branch_name);

    if (elCC) elCC.value = row.cost_center_code || '';
    if (elB) {
        var selected = elB.options[elB.selectedIndex];
        if (elBC) elBC.value = selected ? (selected.dataset.branchcode || row.branch_code || '') : (row.branch_code || '');
        if (elCC && !elCC.value && selected) elCC.value = selected.dataset.costcenter || '';
    }
}

async function _fetchLocation(level, filter) {
    try {
        var url = BASE_URL + '/public/api/get_locations.php?level=' + encodeURIComponent(level)
                + '&filter=' + encodeURIComponent(filter || '');
        var res  = await fetch(url);
        var data = await res.json();
        return data.success ? data.data : [];
    } catch (e) { return []; }
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
        opt.value = item.value;
        opt.textContent = item.label;
        opt.dataset.costcenter  = item.cost_center_code || '';
        opt.dataset.branchcode  = item.branch_code      || item.cost_center_code || '';
        opt.dataset.zonecode    = item.zone_code        || '';
        opt.dataset.mainzone    = item.main_zone_code   || '';
        opt.dataset.regioncode  = item.region_code      || '';
        opt.dataset.boscode     = item.branch_code || item.zone_code || '';
        opt.dataset.kpxid       = item.branch_id || '';
        opt.dataset.corpname    = item.corporate_name || '';
        el.appendChild(opt);
    });
}

function _trySelect(el, value) {
    if (!el || !value) return;
    for (var i = 0; i < el.options.length; i++) {
        if (el.options[i].value === value) { el.selectedIndex = i; return; }
    }
}

function _wireFormEvents() {
    if (_cascadeWired) return;
    _cascadeWired = true;

    var elMZ = document.getElementById('depr-f-mainzone');
    var elZ  = document.getElementById('depr-f-zone');
    var elR  = document.getElementById('depr-f-region');
    var elB  = document.getElementById('depr-f-branch');
    var elCC = document.getElementById('depr-f-costcenter');
    var elBC = document.getElementById('depr-f-branchcode');

    // Filter Top-Down
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

    if (elZ) {
        elZ.addEventListener('change', async function () {
            var regions = await _fetchLocation('regions', this.value);
            _fillDropdown(elR, regions, '— Select Region —');
            if (elB) _fillBranchDropdown(elB, [], '— Select Branch —');
            if (elCC) elCC.value = '';
            if (elBC) elBC.value = '';
        });
    }

    if (elR) {
        elR.addEventListener('change', async function () {
            var branches = await _fetchLocation('branches', this.value);
            _fillBranchDropdown(elB, branches, '— Select Branch —');
            if (elCC) elCC.value = '';
            if (elBC) elBC.value = '';
        });
    }

    // Auto-Populate Bottom-Up (If user picks a branch, auto-fill parents)
    if (elB) {
        elB.addEventListener('change', async function () {
            var opt = this.options[this.selectedIndex];
            if (elCC) elCC.value = opt ? (opt.dataset.costcenter || '') : '';
            if (elBC) elBC.value = opt ? (opt.dataset.branchcode  || '') : '';

            _setVal('depr-f-bos-code',  opt ? (opt.dataset.boscode || '') : '');
            _setVal('depr-f-kpx-id',    opt ? (opt.dataset.kpxid || '') : '');
            _setVal('depr-f-corp-name', opt ? (opt.dataset.corpname || '') : '');

            if (opt && opt.value) {
                var mz = opt.dataset.mainzone || '';
                var z  = opt.dataset.zonecode || '';
                var r  = opt.dataset.regioncode || '';

                // Only trigger backfill if the parent region isn't already selected
                var currentR = elR ? elR.value : '';
                if (r && currentR !== r) {
                    await _backfillParents(mz, z, r);
                }
            }
        });
    }

    var elGroup = document.getElementById('depr-f-group');
    if (elGroup) elGroup.addEventListener('change', _recalculateMonthlyDep);

    var elAcq = document.getElementById('depr-f-acq-cost');
    if (elAcq) elAcq.addEventListener('input', _recalculateMonthlyDep);

    var elDR = document.getElementById('depr-f-date-received');
    if (elDR) {
        elDR.addEventListener('change', function () {
            var start = document.getElementById('depr-f-depr-start');
            if (start && this.value) start.value = _lastDayOfMonth(this.value);
        });
    }
}

// ── NEW HELPER: Auto-populate upper hierarchies ──────────────────────
async function _backfillParents(mz, z, r) {
    var elMZ = document.getElementById('depr-f-mainzone');
    var elZ  = document.getElementById('depr-f-zone');
    var elR  = document.getElementById('depr-f-region');

    // 1. Fetch & Select Main Zone
    if (elMZ && mz) {
        if (elMZ.options.length <= 1) {
            var mzList = await _fetchLocation('main_zones', '');
            _fillDropdown(elMZ, mzList, '— Select Main Zone —');
        }
        _trySelect(elMZ, mz);
        
        // 2. Fetch & Select Sub-Zone based on that Main Zone
        if (elZ) {
            var zList = await _fetchLocation('zones', mz);
            _fillDropdown(elZ, zList, '— Select Sub-Zone —');
            _trySelect(elZ, z);
        }
    }
    
    // 3. Fetch & Select Region based on that Sub-Zone
    if (elZ && z && elR) {
        var rList = await _fetchLocation('regions', z);
        _fillDropdown(elR, rList, '— Select Region —');
        _trySelect(elR, r);
    }
}

// =============================================================
//  GROUP & CALCULATIONS
// =============================================================
function _populateGroupDropdown() {
    var el = document.getElementById('depr-f-group');
    if (!el) return;

    el.innerHTML = '<option value="">— Select GL Group —</option>';
    Object.values(_availableGroups).forEach(function (g) {
        var opt = document.createElement('option');
        opt.value       = g.id;
        opt.textContent = g.group_name;
        el.appendChild(opt);
    });
}

function _recalculateMonthlyDep() {
    var groupId = document.getElementById('depr-f-group')?.value || '';
    var g       = _availableGroups[groupId];
    var acq     = parseFloat(document.getElementById('depr-f-acq-cost')?.value || 0);
    var monthly = document.getElementById('depr-f-monthly-dep');
    
    if (monthly) {
        monthly.value = (g && g.actual_months > 0 && acq > 0)
            ? (acq / g.actual_months).toFixed(2)
            : '';
    }
}

// =============================================================
//  SAVE / COMMIT
// =============================================================
function saveDeprEdit() {
    _clearModalErrors();
    var row = reviewPreviewRows[_deprCurrentRowIndex];
    if (!row) return;

    var mainZoneCode      = document.getElementById('depr-f-mainzone')?.value       || '';
    var zoneCode          = document.getElementById('depr-f-zone')?.value           || '';
    var regionCode        = document.getElementById('depr-f-region')?.value         || '';
    var branchName        = document.getElementById('depr-f-branch')?.value         || '';
    var costCenterCode    = document.getElementById('depr-f-costcenter')?.value     || '';
    var branchCode        = document.getElementById('depr-f-branchcode')?.value     || '';

    var bosCode           = document.getElementById('depr-f-bos-code')?.value       || '';
    var kpxId             = document.getElementById('depr-f-kpx-id')?.value         || '';
    var corpName          = document.getElementById('depr-f-corp-name')?.value      || '';
    
    // Core integer identifier for the group
    var groupId           = document.getElementById('depr-f-group')?.value          || '';
    
    var propertyType      = document.getElementById('depr-f-property-type')?.value  || 'PURCHASED';
    var description       = (document.getElementById('depr-f-description')?.value   || '').trim();
    var refNo             = document.getElementById('depr-f-refno')?.value          || '';
    var serialNumber      = document.getElementById('depr-f-serial')?.value         || '';
    var itemCode          = document.getElementById('depr-f-itemcode')?.value       || '';
    var dateReceived      = document.getElementById('depr-f-date-received')?.value  || '';
    var deprStart         = document.getElementById('depr-f-depr-start')?.value     || '';
    var acqCost           = parseFloat(document.getElementById('depr-f-acq-cost')?.value  || 0);
    var monthlyDep        = parseFloat(document.getElementById('depr-f-monthly-dep')?.value || 0);
    var quantity          = parseInt(document.getElementById('depr-f-quantity')?.value || 1, 10);

    if (!mainZoneCode)   mainZoneCode = row.main_zone_code || '';
    if (!zoneCode)       zoneCode = row.zone_code || '';
    if (!regionCode)     regionCode = row.region_code || '';
    if (!branchName)     branchName = row.branch_name || '';
    if (!costCenterCode) costCenterCode = row.cost_center_code || '';
    if (!branchCode)     branchCode = row.branch_code || costCenterCode || '';
    if (!groupId)        groupId = row.asset_group_id || '';

    var errs = [];
    if (!costCenterCode) errs.push('Cost Center is required — please select a Branch.');
    if (acqCost <= 0)    errs.push('Investment must be greater than zero.');
    if (!groupId)        errs.push('GL Asset Group is required.');
    if (!description)    errs.push('Description is required.');
    if (!dateReceived)   errs.push('Date Received is required.');

    if (errs.length) { _showModalErrors(errs); return; }

    var g            = _availableGroups[groupId] || {};
    var actualMonths = g.actual_months || 0;
    var groupName    = g.group_name || row.group_name;

    if (monthlyDep <= 0 && actualMonths > 0 && acqCost > 0) {
        monthlyDep = parseFloat((acqCost / actualMonths).toFixed(2));
    }

    if (dateReceived && !deprStart) deprStart = _lastDayOfMonth(dateReceived);

    var suffix = refNo.trim() !== ''
        ? refNo.trim()
        : (row.system_asset_code || '').split('-').pop() || Math.random().toString(36).substring(2, 7).toUpperCase();

    // Rebuild the system code strictly formatted as AST-CC-REF
    var ccPart = branchCode || costCenterCode || 'UNKN';
    var newSystemCode = 'AST-' + ccPart + '-' + suffix;

    row.main_zone_code          = mainZoneCode;
    row.zone_code               = zoneCode;
    row.region_code             = regionCode;
    row.branch_name             = branchName;
    row.cost_center_code        = costCenterCode;
    row.branch_code             = branchCode;
    row.bos_branch_code         = bosCode;
    row.kpx_branch_id           = kpxId;
    row.corporate_name          = corpName;
    row.asset_group_id          = parseInt(groupId, 10);
    row.group_name              = groupName;
    row.actual_months           = actualMonths;
    row.property_type           = propertyType;
    row.description             = description;
    row.reference_no            = refNo.trim() || null;
    row.serial_number           = serialNumber.trim() || null;
    row.item_code               = itemCode.trim() || null;
    row.date_received           = dateReceived;
    row.depreciation_start_date = deprStart;
    row.acquisition_cost        = acqCost;
    row.monthly_depreciation    = monthlyDep;
    row.quantity                = quantity;
    row.system_asset_code       = newSystemCode;
    row.has_error               = false;
    row.is_duplicate            = false;
    row.errors                  = [];
    row._edited                 = true;

    reviewPreviewRows[_deprCurrentRowIndex] = row;
    _refreshTableRow(_deprCurrentRowIndex, row);

    _deprSnapshot   = null;
    _deprIsEditMode = false;
    _setDeprEditMode(false);
    _renderViewContent(row);
    _clearModalErrors();
}

function _refreshTableRow(rowIndex, row) {
    var keepSelected = new Set(reviewSelectedRowNums);
    keepSelected.add(String(row.row_num));

    // Re-render
    buildReviewModal({ preview: reviewPreviewRows, groups: Object.values(_availableGroups) });

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

        if (selectAll) selectAll.checked = selectable > 0 && selected === selectable;

        var tr = tbody.querySelector('tr[data-row-index="' + rowIndex + '"]');
        if (tr) {
            tr.classList.add('bg-green-50');
            setTimeout(function () { tr.classList.remove('bg-green-50'); }, 1200);
        }
    }

    var btnConf = document.getElementById('btn-confirm-import');
    if (btnConf) btnConf.disabled = reviewSelectedRowNums.size === 0;
}

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

    var editedRows = reviewPreviewRows.filter(function (r) { return r._edited; });

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
                closeImportReview();
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

function closeAssetDepreciationDetails() {
    _deprIsEditMode      = false;
    _deprCurrentRowIndex = -1;
    _deprSnapshot        = null;
    _clearModalErrors();
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
    if (btnConf) { btnConf.disabled = true; btnConf.textContent = 'Confirm Import'; }

    var fileInput = document.getElementById('file-upload');
    var fileDisplay = document.getElementById('file-display');
    var fileNameTxt = document.getElementById('file-name');
    var btnProcess = document.getElementById('btn-process');

    if (fileInput) fileInput.value = '';
    if (fileNameTxt) fileNameTxt.textContent = '';
    if (fileDisplay) { fileDisplay.classList.add('hidden'); fileDisplay.classList.remove('flex'); }
    if (btnProcess) { btnProcess.disabled = false; btnProcess.textContent = 'Upload'; }

    closeModal('modal-asset-depr-details');
    closeModal('modal-import-review');
}

// =============================================================
//  UTILITY HELPERS
// =============================================================
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