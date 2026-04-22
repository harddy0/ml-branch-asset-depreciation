document.addEventListener('DOMContentLoaded', function () {

    // ==========================================
    // 1. DYNAMIC FORM UI LOGIC (Specific Date)
    // ==========================================
    const depreciationOnSelect = document.getElementById('depreciation_on');
    const specificDayInput     = document.getElementById('depreciation_day');
    const specificDayLabel     = document.getElementById('depreciation_day_label');

    if (depreciationOnSelect && specificDayInput) {
        depreciationOnSelect.addEventListener('change', function () {
            if (this.value === 'SPECIFIC_DATE') {
                specificDayInput.disabled = false;
                specificDayInput.classList.remove('bg-slate-100', 'text-slate-400', 'cursor-not-allowed', 'border-slate-200');
                specificDayInput.classList.add('border-slate-300', 'focus:ring-2', 'focus:ring-red-500');
                specificDayInput.setAttribute('required', 'required');
                if (specificDayLabel) specificDayLabel.innerHTML = 'Specific Day <span class="text-red-500">*</span>';
            } else {
                specificDayInput.disabled = true;
                specificDayInput.classList.add('bg-slate-100', 'text-slate-400', 'cursor-not-allowed', 'border-slate-200');
                specificDayInput.classList.remove('border-slate-300', 'focus:ring-2', 'focus:ring-red-500');
                specificDayInput.removeAttribute('required');
                specificDayInput.value = '';
                if (specificDayLabel) specificDayLabel.innerHTML = 'Specific Day';
            }
        });
    }

    // ==========================================
    // 2. LOCATIONS HIERARCHY FETCH & AUTO-FILL
    // ==========================================
    const mainZoneSelect  = document.getElementById('main_zone_code');
    const zoneSelect      = document.getElementById('zone_code');
    const regionSelect    = document.getElementById('region_code');
    const branchSelect    = document.getElementById('branch_name');
    const costCenterInput = document.getElementById('cost_center_code');

    let allBranches = [];

    let appBase = '';
    if (typeof BASE_URL !== 'undefined' && BASE_URL !== '') {
        appBase = BASE_URL.replace(/\/+$/, '');
    }
    const publicBase = appBase === ''
        ? '/public'
        : (appBase.endsWith('/public') ? appBase : appBase + '/public');

    const locationsApiUrl    = publicBase + '/api/get_locations.php';
    const groupDetailsApiUrl = publicBase + '/api/get_group_details.php';

    fetch(locationsApiUrl)
        .then(r => { if (!r.ok) throw new Error("HTTP " + r.status); return r.text(); })
        .then(text => JSON.parse(text.replace(/^\uFEFF/, '').trim()))
        .then(data => {
            if (data.success && data.branches) {
                allBranches = data.branches;
                populateDropdown(mainZoneSelect,   [], 'Enter branch name or branch code...');
                populateDropdown(zoneSelect,     [], 'Enter branch name or branch code...');
                populateDropdown(regionSelect,   [], 'Enter branch name or branch code...');
                populateBranchDropdown(allBranches);
            }
        })
        .catch(err => console.error('Location fetch error:', err));

    function getUniqueValues(array, key) {
        return [...new Set(array.map(i => i[key]).filter(Boolean))].sort();
    }

    function getUniqueRegions(array) {
        let unique = {};
        array.forEach(item => {
            if (item.region_code && !unique[item.region_code]) {
                unique[item.region_code] = item.region_description
                    ? `${item.region_code} - ${item.region_description}`
                    : item.region_code;
            }
        });
        return Object.keys(unique).sort().map(k => ({ value: k, text: unique[k] }));
    }

    function populateDropdown(selectEl, valuesArray, defaultText) {
        if (!selectEl) return;
        selectEl.innerHTML = `<option value="" disabled selected>${defaultText}</option>`;
        valuesArray.forEach(val => {
            let opt = document.createElement('option');
            if (typeof val === 'object') {
                opt.value       = val.value;
                opt.textContent = val.text;
            } else {
                opt.value       = val;
                opt.textContent = val;
            }
            selectEl.appendChild(opt);
        });
        if (valuesArray.length === 0) {
            selectEl.disabled = true;
            selectEl.classList.add('disabled:bg-slate-100', 'disabled:text-slate-400');
        } else {
            selectEl.disabled = false;
            selectEl.classList.remove('disabled:bg-slate-100', 'disabled:text-slate-400');
        }
    }

    function populateBranchDropdown(branchesArray) {
        if (branchSelect && branchSelect.tagName === 'SELECT') {
            branchSelect.innerHTML = `<option value="" disabled selected>${branchesArray.length === 0 ? 'Waiting for Region...' : 'Select Branch...'}</option>`;
            if (branchesArray.length === 0) {
                branchSelect.disabled = true;
                branchSelect.classList.add('disabled:bg-slate-100', 'disabled:text-slate-400');
                return;
            }
            branchSelect.disabled = false;
            branchSelect.classList.remove('disabled:bg-slate-100', 'disabled:text-slate-400');
            branchesArray
                .sort((a, b) => (a.branch_name || '').localeCompare(b.branch_name || ''))
                .forEach(b => {
                    if (!b.branch_name) return;
                    let opt = document.createElement('option');
                    opt.value       = b.branch_name;
                    opt.textContent = b.branch_name;
                    branchSelect.appendChild(opt);
                });
            return;
        }

        const branchInput = document.getElementById('branch_name_input');
        const branchList = document.getElementById('branch_list');
        if (branchInput && branchList) {
                branchInput.placeholder = branchesArray.length === 0 ? 'Waiting for Region...' : 'Type to search branches...';
                branchInput.disabled = branchesArray.length === 0;
        }
    }

    if (mainZoneSelect) {
        mainZoneSelect.addEventListener('change', function () {
            let filtered = allBranches.filter(b => b.main_zone_code === this.value);
            populateDropdown(zoneSelect, getUniqueValues(filtered, 'zone_code'), 'Enter branch name or branch code...');
            populateDropdown(regionSelect, [], 'Enter branch name or branch code...');
            populateBranchDropdown([]);
            if (costCenterInput) costCenterInput.value = '';
        });
    }
    if (zoneSelect) {
        zoneSelect.addEventListener('change', function () {
            let filtered = allBranches.filter(b =>
                b.main_zone_code === mainZoneSelect.value && b.zone_code === this.value
            );
            populateDropdown(regionSelect, getUniqueRegions(filtered), 'Enter branch name or branch code...');
            populateBranchDropdown([]);
            if (costCenterInput) costCenterInput.value = '';
        });
    }
    if (regionSelect) {
        regionSelect.addEventListener('change', function () {
            let filtered = allBranches.filter(b =>
                b.main_zone_code === mainZoneSelect.value &&
                b.zone_code      === zoneSelect.value &&
                b.region_code    === this.value
            );
            populateBranchDropdown(filtered);
            if (costCenterInput) costCenterInput.value = '';
        });
    }
    if (branchSelect && branchSelect.tagName === 'SELECT') {
        branchSelect.addEventListener('change', function () {
            const found = allBranches.find(b => b.branch_name === this.value);
            if (!found) return;

            if (found && costCenterInput) costCenterInput.value = found.branch_code || found.cost_center_code || '';

            function setSingle(selectEl, val, displayText) {
                if (!selectEl) return;
                selectEl.innerHTML = `<option value="" disabled>${displayText || 'N/A'}</option>`;
                if (val) {
                    const opt = document.createElement('option');
                    opt.value = val;
                    opt.textContent = val;
                    selectEl.appendChild(opt);
                    selectEl.value = val;
                    selectEl.disabled = false;
                    selectEl.classList.remove('disabled:bg-slate-100', 'disabled:text-slate-400');
                } else {
                    selectEl.disabled = true;
                    selectEl.classList.add('disabled:bg-slate-100', 'disabled:text-slate-400');
                }
            }

            setSingle(mainZoneSelect, found.main_zone_code, found.main_zone_code || 'N/A');
            setSingle(zoneSelect,     found.zone_code,      found.zone_code || 'N/A');

            const regionCode = found.region_code || ''; 
            const regionLabel = found.region || found.region_code || ''; 

            setSingle(regionSelect, regionCode, regionLabel || 'N/A');
        });
    }

    const branchInputEl = document.getElementById('branch_name_input');
    const branchListEl = document.getElementById('branch_list');
    if (branchInputEl && branchListEl) {
        branchInputEl.addEventListener('input', function () {
            const val = String(branchInputEl.value || '').trim();
            const found = allBranches.find(b => b.branch_name === val);
            if (!found) {
                if (costCenterInput) costCenterInput.value = '';
                populateDropdown(mainZoneSelect, [], 'Enter branch name or branch code...');
                populateDropdown(zoneSelect, [], 'Enter branch name or branch code...');
                populateDropdown(regionSelect, [], 'Enter branch name or branch code...');
                return;
            }
            if (costCenterInput) costCenterInput.value = found.branch_code || found.cost_center_code || '';

            function setSingle(selectEl, val, displayText) {
                if (!selectEl) return;
                selectEl.innerHTML = `<option value="" disabled>${displayText || 'N/A'}</option>`;
                if (val) {
                    const opt = document.createElement('option');
                    opt.value = val;
                    opt.textContent = val;
                    selectEl.appendChild(opt);
                    selectEl.value = val;
                    selectEl.disabled = false;
                    selectEl.classList.remove('disabled:bg-slate-100', 'disabled:text-slate-400');
                } else {
                    selectEl.disabled = true;
                    selectEl.classList.add('disabled:bg-slate-100', 'disabled:text-slate-400');
                }
            }

            setSingle(mainZoneSelect, found.main_zone_code, found.main_zone_code || 'N/A');
            setSingle(zoneSelect,     found.zone_code,      found.zone_code || 'N/A');
            const regionCode = found.region || '';
            const regionLabel = regionCode; 
            setSingle(regionSelect,   regionCode,    regionLabel || 'N/A');
        });
    }

    // ==========================================
    // 3. GL GROUP SELECT — Load options + Auto-fill
    // ==========================================

    const glGroupSelect      = document.getElementById('gl_group_select');     
    const glGroupCodeDisplay = document.getElementById('gl_group_code_display'); 
    const glAssetCodeDisplay = document.getElementById('gl_asset_code_display'); 
    const glAssetNameDisplay = document.getElementById('gl_asset_name_display');
    const glDepCodeDisplay   = document.getElementById('gl_dep_code_display');   
    const glDepNameDisplay   = document.getElementById('gl_dep_name_display');

    function clearGlFields() {
        if (glGroupCodeDisplay) glGroupCodeDisplay.value = '';
        if (glAssetCodeDisplay) glAssetCodeDisplay.value = '';
        if (glAssetNameDisplay) glAssetNameDisplay.value = '';
        if (glDepCodeDisplay)   glDepCodeDisplay.value   = '';
        if (glDepNameDisplay)   glDepNameDisplay.value   = '';
    }

    function initGroupDropdown() {
        if (!glGroupSelect) return;

        const groups = window.__assetGroups || [];

        glGroupSelect.innerHTML = '<option value="" disabled selected>Select Group...</option>';

        if (groups.length === 0) {
            glGroupSelect.innerHTML = '<option value="" disabled selected>No groups configured</option>';
            return;
        }

        groups.forEach(function (g) {
            const opt       = document.createElement('option');
            // FIX: The database table 'asset_groups' uses 'id', not 'group_code'.
            opt.value       = g.id;
            opt.textContent = g.group_name;
            glGroupSelect.appendChild(opt);
        });
    }

    initGroupDropdown();

    if (glGroupSelect) {
        glGroupSelect.addEventListener('change', function () {
            const selectedCode = this.value;

            if (!selectedCode) {
                clearGlFields();
                return;
            }

            if (glGroupCodeDisplay) glGroupCodeDisplay.value = selectedCode;

            fetch(`${groupDetailsApiUrl}?group_code=${encodeURIComponent(selectedCode)}`)
                .then(function (r) {
                    if (!r.ok) throw new Error('HTTP ' + r.status);
                    return r.json();
                })
                .then(function (res) {
                    if (!res.success || !res.data) {
                        clearGlFields();
                        if (glGroupCodeDisplay) glGroupCodeDisplay.value = selectedCode;
                        return;
                    }

                    const d = res.data;

                    if (glGroupCodeDisplay) glGroupCodeDisplay.value = d.group_code || selectedCode;
                    if (glAssetCodeDisplay) glAssetCodeDisplay.value = d.asset_code;
                    if (glAssetNameDisplay) glAssetNameDisplay.value = d.asset_name;
                    if (glDepCodeDisplay) glDepCodeDisplay.value = d.depreciation_code;
                    if (glDepNameDisplay) glDepNameDisplay.value = d.depreciation_description;

                    if (typeof window.__setActualMonths === 'function') {
                        window.__setActualMonths(d.actual_months);
                    }
                })
                .catch(function (err) {
                    console.error('GL fetch error:', err);
                });
        });
    }

    // ==========================================
    // 4. END DATE AUTO-COMPUTE 
    // ==========================================

    const dateReceivedInput   = document.getElementById('date_received');
    const startDateInput      = document.getElementById('depreciation_start_date'); 
    const endDateInput        = document.getElementById('depreciation_end_date');
    const endDateAutoBadge    = document.getElementById('end_date_auto_badge');
    
    const depOnSelect         = document.getElementById('depreciation_on');
    const depDayInput         = document.getElementById('depreciation_day');

    let endDateManuallySet = false;
    let currentActualMonths = 0;

    function computeDates() {
        if (!dateReceivedInput || !dateReceivedInput.value) return;

        const recvDate = new Date(dateReceivedInput.value + 'T00:00:00');
        if (isNaN(recvDate)) return;

        const depOn = depOnSelect ? depOnSelect.value : 'LAST_DAY';
        let specificDay = (depDayInput && !depDayInput.disabled && depDayInput.value) ? parseInt(depDayInput.value) : 1;

        let startYear = recvDate.getFullYear();
        let startMonth = recvDate.getMonth();
        let startDateObj;

        if (depOn === 'LAST_DAY') {
            startDateObj = new Date(startYear, startMonth + 1, 0); 
        } else if (depOn === 'FIRST_DAY') {
            startDateObj = new Date(startYear, startMonth, 1); 
        } else {
            let lastDayOfMonth = new Date(startYear, startMonth + 1, 0).getDate();
            let clampedDay = Math.min(specificDay, lastDayOfMonth);
            startDateObj = new Date(startYear, startMonth, clampedDay);
        }

        if (startDateInput) {
            startDateInput.value = formatDate(startDateObj);
        }

        if (!endDateManuallySet && currentActualMonths > 0) {
            let endYear = startDateObj.getFullYear();
            let endMonth = startDateObj.getMonth() + currentActualMonths;
            let endDateObj;

            if (depOn === 'LAST_DAY') {
                endDateObj = new Date(endYear, endMonth + 1, 0);
            } else if (depOn === 'FIRST_DAY') {
                endDateObj = new Date(endYear, endMonth, 1);
            } else {
                let lastDayOfEndMonth = new Date(endYear, endMonth + 1, 0).getDate();
                let endClampedDay = Math.min(specificDay, lastDayOfEndMonth);
                endDateObj = new Date(endYear, endMonth, endClampedDay);
            }

            if (endDateInput) {
                endDateInput.value = formatDate(endDateObj);
                endDateInput.classList.add('bg-slate-50');
                endDateInput.classList.remove('bg-white');
                if (endDateAutoBadge) endDateAutoBadge.classList.remove('hidden');
            }
        }
    }

    function formatDate(dateObj) {
        const y = dateObj.getFullYear();
        const m = String(dateObj.getMonth() + 1).padStart(2, '0');
        const d = String(dateObj.getDate()).padStart(2, '0');
        return `${y}-${m}-${d}`;
    }

    if (endDateInput) {
        endDateInput.addEventListener('input', function () {
            endDateManuallySet = true;
            endDateInput.classList.remove('bg-slate-50');
            endDateInput.classList.add('bg-white');
            if (endDateAutoBadge) endDateAutoBadge.classList.add('hidden');
        });
    }

    if (dateReceivedInput) dateReceivedInput.addEventListener('change', computeDates);
    if (depOnSelect) depOnSelect.addEventListener('change', computeDates);
    if (depDayInput) depDayInput.addEventListener('input', computeDates);

    // ==========================================
    // 5. RESET FORM when modal is closed
    // ==========================================
    const form = document.getElementById('addAssetForm');
    const modalEl = document.getElementById('modal-add-asset');
    if (modalEl) {
        const observer = new MutationObserver(function (mutations) {
            mutations.forEach(m => {
                if (m.attributeName === 'class' && modalEl.classList.contains('hidden')) {
                    if (form) form.reset();
                    clearGlFields();
                    if (glGroupSelect) glGroupSelect.value = '';
                    endDateManuallySet = false;
                    currentActualMonths = 0;
                    if (endDateAutoBadge) endDateAutoBadge.classList.remove('hidden');
                }
            });
        });
        observer.observe(modalEl, { attributes: true });
    }

    // ==========================================
    // 6. MONTHLY DEPRECIATION AUTO-COMPUTE
    // ==========================================
    const acqCostInput = document.getElementById('asset_acquisition_cost');
    const monthlyDepInput = document.getElementById('monthly_depreciation');

    function computeMonthlyDepreciation() {
        if (!acqCostInput || !monthlyDepInput) return;
        const raw = String(acqCostInput.value || '');
        const normalized = raw.replace(/[^\d.\-]/g, '');
        const cost = parseFloat(normalized) || 0;

        if (currentActualMonths > 0 && cost > 0) {
            const monthly = (cost / currentActualMonths).toFixed(2);
            monthlyDepInput.value = monthly;
        } else {
            monthlyDepInput.value = '0.00';
        }
    }

    if (acqCostInput) acqCostInput.addEventListener('input', computeMonthlyDepreciation);

    window.__setActualMonths = function (months) {
        currentActualMonths = parseInt(months) || 0;
        endDateManuallySet  = false; 
        if (endDateAutoBadge) endDateAutoBadge.classList.remove('hidden');
        
        computeDates();
        computeMonthlyDepreciation();
    };

    // ==========================================
    // 7. FORM SUBMISSION (AJAX FETCH)
    // ==========================================
    if (form && !form.hasAttribute('data-submit-managed')) {
        form.addEventListener('submit', function (e) {
            e.preventDefault();

            if (!glGroupSelect || !glGroupSelect.value) {
                alert('Please select an Asset Group before saving.');
                glGroupSelect.focus();
                return;
            }

            if (!form.checkValidity()) {
                form.reportValidity();
                return;
            }

            const submitBtn =
                form.querySelector('button[type="submit"], input[type="submit"]') ||
                document.querySelector('button[type="submit"][form="' + form.id + '"], input[type="submit"][form="' + form.id + '"]');

            const isInputSubmit = submitBtn && submitBtn.tagName === 'INPUT';
            const originalText = submitBtn
                ? (isInputSubmit ? submitBtn.value : submitBtn.innerHTML)
                : '';

            if (submitBtn) {
                submitBtn.disabled = true;
                if (isInputSubmit) {
                    submitBtn.value = 'Saving...';
                } else {
                    submitBtn.innerHTML = 'Saving...';
                }
            }

            const formData = new FormData(form);

            fetch(publicBase + '/actions/asset_store.php', {
                method: 'POST',
                body: formData
            })
            .then(r => {
                if (!r.ok) throw new Error("HTTP " + r.status);
                return r.text();
            })
            .then(text => {
                const cleaned = text.replace(/^\uFEFF/, '').trim();
                if (!cleaned) {
                    throw new Error('Empty response from server.');
                }

                try {
                    return JSON.parse(cleaned);
                } catch (parseErr) {
                    throw parseErr;
                }
            })
            .then(res => {
                if (res.success) {
                    alert('Asset successfully added!');
                    
                    if (typeof window.closeModal === 'function') {
                        window.closeModal('modal-add-asset');
                    } else {
                        document.getElementById('modal-add-asset').classList.add('hidden');
                    }
                    
                    form.reset();
                    clearGlFields();
                    if (glGroupSelect) glGroupSelect.value = '';

                    window.location.reload(); 
                } else {
                    alert('Failed to save asset: ' + res.error);
                }
            })
            .catch(err => {
                alert('Failed to save asset: ' + (err.message || 'Unknown error'));
            })
            .finally(() => {
                if (submitBtn) {
                    submitBtn.disabled = false;
                    if (isInputSubmit) {
                        submitBtn.value = originalText;
                    } else {
                        submitBtn.innerHTML = originalText;
                    }
                }
            });
        });
    }

    // ==========================================
    // 8. DEPRECIATION LIST (ACTIVE / PAGINATED)
    // ==========================================
    const listConfigEl = document.getElementById('depr-list-config');
    const listApiUrl = (listConfigEl && listConfigEl.dataset.apiUrl)
        ? listConfigEl.dataset.apiUrl
        : (publicBase + '/api/get_depreciation_list.php');

    const listPerPage = (listConfigEl && parseInt(listConfigEl.dataset.perPage, 10))
        ? parseInt(listConfigEl.dataset.perPage, 10)
        : 50;

    const tableBody = document.getElementById('depr-table-body');
    const metaEl = document.getElementById('depr-page-meta');
    const prevBtn = document.getElementById('depr-prev-page');
    const nextBtn = document.getElementById('depr-next-page');
    const pageNumbersEl = document.getElementById('depr-page-numbers');
    const searchInput = document.getElementById('depr-search');
    const groupFilter = document.getElementById('depr-group-filter');
    const branchFilter = document.getElementById('depr-branch-filter');
    const dateFromInput = document.getElementById('depr-date-from');
    const dateToInput = document.getElementById('depr-date-to');
    const statusFilter = document.getElementById('depr-status-filter');
    const resetBtn = document.getElementById('depr-filter-reset');
    const sortButtons = Array.from(document.querySelectorAll('.depr-sort'));

    const listState = {
        page: 1,
        perPage: listPerPage,
        search: '',
        group_code: '',
        branch_name: '',
        date_from: '',
        date_to: '',
        status: '',
        sort_by: 'created_at',
        sort_dir: 'DESC',
    };

    const currency = new Intl.NumberFormat('en-US', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
    });

    function escapeHtml(value) {
        return String(value ?? '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    function formatDateDisplay(value) {
        if (!value) return '-';
        const s = String(value).trim();
        let dt = new Date(s);
        if (isNaN(dt)) {
            const replaced = s.replace(' ', 'T');
            dt = new Date(replaced);
        }
        if (isNaN(dt)) {
            const datePart = s.split(' ')[0].split('T')[0];
            if (!datePart) return '-';
            dt = new Date(datePart + 'T00:00:00');
        }
        if (isNaN(dt)) return '-';
        return new Intl.DateTimeFormat('en-US', {
            month: 'short',
            day: 'numeric',
            year: 'numeric'
        }).format(dt);
    }

    function renderListRows(rows) {
        if (!tableBody) return;

        if (!rows || rows.length === 0) {
            tableBody.innerHTML = `
                <tr>
                    <td colspan="11" class="px-6 py-8 text-center text-sm font-semibold text-slate-500">
                        No active assets found.
                    </td>
                </tr>
            `;
            return;
        }

        const html = rows.map(function (row, idx) {
            const rowBg = (idx % 2 === 0) ? 'bg-white' : 'bg-slate-200';
            const payload = encodeURIComponent(JSON.stringify({
                id: row.id,
                system_asset_code: row.system_asset_code || '',
                serial_number: row.serial_number || '',
                description: row.description || '',
                group_code: row.asset_group_id || '',
                branch_name: row.branch_name || '',
                uploaded_by: row.uploaded_by || '',
                created_at: row.created_at || ''
            }));

            const serialNo = row.serial_number || row.system_asset_code || '-';
            const description = row.description || '-';
            const itemCode = row.item_code || '-';
            const groupCode = row.asset_group_id || '-';
            const branch = row.branch_name || '-';
            const uploadedBy = row.uploaded_by || 'Unknown';
            const acquisitionCost = currency.format(parseFloat(row.acquisition_cost || 0));
            const monthlyDep = currency.format(parseFloat(row.monthly_depreciation || 0));
            const status = row.status || 'ACTIVE';
            const endDate = formatDateDisplay(row.depreciation_end_date);
            const dateAdded = formatDateDisplay(row.created_at);

            return `
                <tr class="depr-asset-row border-b border-slate-100 hover:bg-slate-50 cursor-pointer ${rowBg}" data-asset="${payload}">
                    <td class="px-6 py-1 text-center text-xs font-mono text-slate-700 whitespace-nowrap">${escapeHtml(serialNo)}</td>
                    <td class="px-6 py-1 text-left text-xs font-semibold text-slate-700 whitespace-nowrap">${escapeHtml(description)}</td>
                    <td class="px-6 py-1 text-center text-xs font-mono text-slate-700 whitespace-nowrap">${escapeHtml(itemCode)}</td>
                    <td class="px-6 py-1 text-center text-xs font-mono text-slate-700 whitespace-nowrap">${escapeHtml(groupCode)}</td>
                    <td class="px-6 py-1 text-left text-xs text-slate-700 whitespace-nowrap">
                        <div class="font-semibold">${escapeHtml(branch)}</div>
                    </td>
                    <td class="px-6 py-1 text-left text-xs text-slate-700 whitespace-nowrap">
                        <div class="text-[11px] text-slate-500">${escapeHtml(uploadedBy)}</div>
                    </td>
                    <td class="px-6 py-1 text-xs font-mono text-slate-700 whitespace-nowrap">
                        <div class="font-semibold currency-cell"><span class="currency-symbol">₱</span><span class="amount">${acquisitionCost}</span></div>
                    </td>
                    <td class="px-6 py-1 text-xs font-mono text-slate-700 whitespace-nowrap">
                        <div class="font-semibold currency-cell"><span class="currency-symbol">₱</span><span class="amount">${monthlyDep}</span></div>
                    </td>
                    <td class="px-6 py-1 text-center text-xs whitespace-nowrap">
                        <span class="inline-flex items-center px-2 py-1 rounded-full text-[10px] font-bold uppercase bg-emerald-100 text-emerald-700">
                            ${escapeHtml(status)}
                        </span>
                    </td>
                    <td class="px-6 py-1 text-center text-xs font-mono text-slate-700 whitespace-nowrap">${escapeHtml(endDate)}</td>
                    <td class="px-6 py-1 text-center text-xs font-mono text-slate-700 whitespace-nowrap">${escapeHtml(dateAdded)}</td>
                </tr>
            `;
        }).join('');

        tableBody.innerHTML = html;
    }

    function updateSortIndicators() {
        sortButtons.forEach(function (btn) {
            const indicator = btn.querySelector('.depr-sort-indicator');
            if (!indicator) return;

            if (btn.dataset.sort === listState.sort_by) {
                indicator.textContent = (listState.sort_dir === 'ASC') ? '↑' : '↓';
                indicator.classList.remove('opacity-70');
            } else {
                indicator.textContent = '↕';
                indicator.classList.add('opacity-70');
            }
        });
    }

    function buildVisiblePages(currentPage, totalPages) {
        const maxButtons = 7;
        const pages = [];

        if (totalPages <= maxButtons) {
            for (let i = 1; i <= totalPages; i++) pages.push(i);
            return pages;
        }

        let start = Math.max(1, currentPage - 3);
        let end = Math.min(totalPages, start + maxButtons - 1);

        if ((end - start + 1) < maxButtons) {
            start = Math.max(1, end - maxButtons + 1);
        }

        for (let i = start; i <= end; i++) pages.push(i);
        return pages;
    }

    function renderPageNumberButtons(currentPage, totalPages) {
        if (!pageNumbersEl) return;

        const pages = buildVisiblePages(currentPage, totalPages);
        let html = '';

        if (pages.length > 0 && pages[0] > 1) {
            html += `<button type="button" class="depr-page-btn px-2 py-1 text-xs font-semibold border border-slate-300 rounded text-slate-700 hover:bg-slate-100" data-page="1">1</button>`;
            if (pages[0] > 2) {
                html += `<span class="px-1 text-xs font-semibold text-slate-400">...</span>`;
            }
        }

        pages.forEach(function (pageNum) {
            const activeClass = pageNum === currentPage
                ? 'bg-[#ce2216] border-[#ce2216] text-white'
                : 'text-slate-700 hover:bg-slate-100 border-slate-300';

            html += `<button type="button" class="depr-page-btn px-2 py-1 text-xs font-semibold border rounded ${activeClass}" data-page="${pageNum}">${pageNum}</button>`;
        });

        if (pages.length > 0 && pages[pages.length - 1] < totalPages) {
            if (pages[pages.length - 1] < totalPages - 1) {
                html += `<span class="px-1 text-xs font-semibold text-slate-400">...</span>`;
            }
            html += `<button type="button" class="depr-page-btn px-2 py-1 text-xs font-semibold border border-slate-300 rounded text-slate-700 hover:bg-slate-100" data-page="${totalPages}">${totalPages}</button>`;
        }

        pageNumbersEl.innerHTML = html;
        const pageButtons = pageNumbersEl.querySelectorAll('.depr-page-btn');
        pageButtons.forEach(function (btn) {
            btn.addEventListener('click', function () {
                const nextPage = parseInt(btn.dataset.page, 10);
                if (!nextPage || nextPage === listState.page) return;
                listState.page = nextPage;
                fetchDepreciationList();
            });
        });
    }

    function updatePaginationUi(pagination) {
        if (!metaEl || !prevBtn || !nextBtn || !pagination) return;

        const total = parseInt(pagination.total || 0, 10);
        const page = parseInt(pagination.page || 1, 10);
        const totalPages = parseInt(pagination.total_pages || 1, 10);

        metaEl.textContent = `Page ${page} of ${totalPages} • ${total.toLocaleString()} records`;
        prevBtn.disabled = !pagination.has_prev;
        nextBtn.disabled = !pagination.has_next;

        renderPageNumberButtons(page, totalPages);
    }

    function setTableLoading() {
        if (!tableBody) return;
        tableBody.innerHTML = `
            <tr>
                <td colspan="11" class="px-6 py-8 text-center text-sm font-semibold text-slate-500">Loading assets...</td>
            </tr>
        `;
    }

    function buildListQuery() {
        const params = new URLSearchParams();
        params.set('page', String(listState.page));
        params.set('per_page', String(listState.perPage));
        params.set('sort_by', listState.sort_by);
        params.set('sort_dir', listState.sort_dir);

        if (listState.search) params.set('search', listState.search);
        if (listState.group_code) params.set('group_code', listState.group_code);
        if (listState.branch_name) params.set('branch_name', listState.branch_name);
        if (listState.date_from) params.set('date_from', listState.date_from);
        if (listState.date_to) params.set('date_to', listState.date_to);
        if (listState.status) params.set('status', listState.status);

        return params.toString();
    }

    function fetchDepreciationList() {
        if (!tableBody) return;

        setTableLoading();
        updateSortIndicators();

        fetch(`${listApiUrl}?${buildListQuery()}`)
            .then(function (r) {
                if (!r.ok) throw new Error('HTTP ' + r.status);
                return r.text();
            })
            .then(function (text) {
                return JSON.parse(text.replace(/^\uFEFF/, '').trim());
            })
            .then(function (res) {
                if (!res.success) {
                    throw new Error(res.error || 'Failed to fetch depreciation list.');
                }

                const rows = Array.isArray(res.data) ? res.data : [];
                const branches = Array.isArray(res.branches) ? res.branches : [];
                const pagination = res.pagination || null;

                if (res.sort && res.sort.sort_by) {
                    listState.sort_by = res.sort.sort_by;
                    listState.sort_dir = res.sort.sort_dir || listState.sort_dir;
                }
                if (pagination && pagination.page) {
                    listState.page = parseInt(pagination.page, 10) || listState.page;
                }

                populateBranchFilter(branches);
                if (dateFromInput) {
                    const v = (res.filters && res.filters.date_from) ? res.filters.date_from : (listState.date_from || '');
                    dateFromInput.value = v || '';
                    listState.date_from = v || '';
                }
                if (dateToInput) {
                    const v2 = (res.filters && res.filters.date_to) ? res.filters.date_to : (listState.date_to || '');
                    dateToInput.value = v2 || '';
                    listState.date_to = v2 || '';
                }
                if (statusFilter) {
                    const sv = (res.filters && res.filters.status) ? res.filters.status : (listState.status || '');
                    statusFilter.value = sv || '';
                    listState.status = sv || '';
                }
                renderListRows(rows);
                updatePaginationUi(pagination);
                updateSortIndicators();
            })
            .catch(function (err) {
                if (tableBody) {
                    tableBody.innerHTML = `
                        <tr>
                            <td colspan="11" class="px-6 py-8 text-center text-sm font-semibold text-red-600">
                                Unable to load depreciation list: ${escapeHtml(err.message || 'Unknown error')}
                            </td>
                        </tr>
                    `;
                }
            });
    }

    function populateListGroupFilter() {
        if (!groupFilter) return;

        // FIX: Ensure options are reset and we extract the exact ID from the backend object.
        groupFilter.innerHTML = '<option value="">All Group Codes</option>';

        const groups = Array.isArray(window.__assetGroups) ? window.__assetGroups : [];
        groups.forEach(function (group) {
            const opt = document.createElement('option');
            opt.value = group.id; 
            opt.textContent = `${group.group_name}`; 
            groupFilter.appendChild(opt);
        });
    }

    function populateBranchFilter(branches) {
        if (!branchFilter) return;

        const selectedValue = listState.branch_name;
        branchFilter.innerHTML = '<option value="">All Branches</option>';

        branches.forEach(function (branch) {
            const opt = document.createElement('option');
            opt.value = branch;
            opt.textContent = branch;
            branchFilter.appendChild(opt);
        });

        if (selectedValue && branches.includes(selectedValue)) {
            branchFilter.value = selectedValue;
        } else {
            branchFilter.value = '';
            if (selectedValue) {
                listState.branch_name = '';
            }
        }
    }

    // ==========================================
    // 9. LEDGER MODAL (LEDGER + FINANCIAL VIEW)
    // ==========================================
    const ledgerConfigEl = document.getElementById('depr-ledger-config');
    const ledgerApiUrl = (ledgerConfigEl && ledgerConfigEl.dataset.apiUrl)
        ? ledgerConfigEl.dataset.apiUrl
        : (publicBase + '/api/get_asset_ledger.php');

    const ledgerModalEl = document.getElementById('modal-asset-ledger');
    const ledgerSubtitleEl = document.getElementById('ledger-subtitle');
    const ledgerAssetMetaEl = document.getElementById('ledger-asset-meta');
    const ledgerLoadingEl = document.getElementById('ledger-loading');
    const ledgerErrorEl = document.getElementById('ledger-error');
    const ledgerTableWrapEl = document.getElementById('ledger-table-wrap');
    const ledgerTableBodyEl = document.getElementById('ledger-table-body');
    const fsTableWrapEl = document.getElementById('fs-table-wrap');
    const fsTableBodyEl = document.getElementById('fs-table-body');
    const ledgerFooterSummaryEl = document.getElementById('ledger-footer-summary');
    const ledgerTotalDebitEl = document.getElementById('ledger-total-debit');
    const ledgerTotalCreditEl = document.getElementById('ledger-total-credit');
    const ledgerLatestAccumEl = document.getElementById('ledger-latest-accum');
    const ledgerLatestBookEl = document.getElementById('ledger-latest-book');

    const ledgerPeriodYearEl = document.getElementById('ledger-period-year');
    const ledgerPeriodMonthEl = document.getElementById('ledger-period-month');
    const ledgerEntrySideEl = document.getElementById('ledger-entry-side');
    const ledgerResetFilterBtn = document.getElementById('ledger-reset-filter');
    const ledgerTabLedgerBtn = document.getElementById('ledger-tab-ledger');
    const ledgerTabFsBtn = document.getElementById('ledger-tab-fs');
    const ledgerPrintBtn = document.getElementById('ledger-print-btn');

    const ledgerState = {
        asset: null,
        filters: {
            period_year: '',
            period_month: '',
            entry_side: 'ALL'
        },
        activeTab: 'LEDGER',
    };

    function monthName(monthNum) {
        const map = ['January','February','March','April','May','June','July','August','September','October','November','December'];
        const idx = parseInt(monthNum, 10) - 1;
        return (idx >= 0 && idx < 12) ? map[idx] : String(monthNum || '');
    }

    function setLedgerTab(tab) {
        ledgerState.activeTab = tab;

        if (!ledgerTabLedgerBtn || !ledgerTabFsBtn || !ledgerTableWrapEl || !fsTableWrapEl) return;

        if (tab === 'LEDGER') {
            ledgerTabLedgerBtn.classList.add('bg-[#ce1126]', 'text-white');
            ledgerTabLedgerBtn.classList.remove('bg-white', 'text-slate-700');
            ledgerTabFsBtn.classList.add('bg-white', 'text-slate-700');
            ledgerTabFsBtn.classList.remove('bg-[#ce1126]', 'text-white');

            ledgerTableWrapEl.classList.remove('hidden');
            fsTableWrapEl.classList.add('hidden');
        } else {
            ledgerTabFsBtn.classList.add('bg-[#ce1126]', 'text-white');
            ledgerTabFsBtn.classList.remove('bg-white', 'text-slate-700');
            ledgerTabLedgerBtn.classList.add('bg-white', 'text-slate-700');
            ledgerTabLedgerBtn.classList.remove('bg-[#ce1126]', 'text-white');

            fsTableWrapEl.classList.remove('hidden');
            ledgerTableWrapEl.classList.add('hidden');
        }
    }

    function setLedgerLoading(on) {
        if (!ledgerLoadingEl || !ledgerErrorEl) return;
        ledgerLoadingEl.classList.toggle('hidden', !on);
        if (on) {
            ledgerErrorEl.classList.add('hidden');
        }
    }

    function setLedgerError(message) {
        if (!ledgerErrorEl) return;
        ledgerErrorEl.textContent = message;
        ledgerErrorEl.classList.remove('hidden');
    }

    function getLedgerLineRows(rows) {
        const lines = [];

        (rows || []).forEach(function (r) {
            const sourceId = parseInt(r.ledger_id || 0, 10) || 0;
            const shared = {
                source_id: sourceId,
                period_date: r.period_date || '',
                period_year: r.period_year || '',
                period_month: r.period_month || '',
                description: r.description || '',
                period_depreciation_expense: parseFloat(r.period_depreciation_expense || 0) || 0,
                accumulated_depreciation: parseFloat(r.accumulated_depreciation || 0) || 0,
                book_value: parseFloat(r.book_value || 0) || 0,
                created_at: r.created_at || ''
            };

            if (r.gl1_code) {
                const gl1Type = String(r.gl1_type || '').toUpperCase();
                const gl1Amount = parseFloat(r.gl1_amount || 0) || 0;
                lines.push(Object.assign({}, shared, {
                    line_type: gl1Type,
                    gl_code: String(r.gl1_code),
                    debit: gl1Type === 'DEBIT' ? gl1Amount : 0,
                    credit: gl1Type === 'CREDIT' ? gl1Amount : 0
                }));
            }

            if (r.gl2_code) {
                const gl2Type = String(r.gl2_type || '').toUpperCase();
                const gl2Amount = parseFloat(r.gl2_amount || 0) || 0;
                lines.push(Object.assign({}, shared, {
                    line_type: gl2Type,
                    gl_code: String(r.gl2_code),
                    debit: gl2Type === 'DEBIT' ? gl2Amount : 0,
                    credit: gl2Type === 'CREDIT' ? gl2Amount : 0
                }));
            }
        });

        const entryFilter = ledgerState.filters.entry_side || 'ALL';
        return lines.filter(line => {
            if (entryFilter === 'DEBIT' && line.line_type !== 'DEBIT') return false;
            if (entryFilter === 'CREDIT' && line.line_type !== 'CREDIT') return false;
            return true;
        });
    }

    function renderLedgerRows(rows) {
        if (!ledgerTableBodyEl) return;

        const lineRows = getLedgerLineRows(rows);

        if (!lineRows || lineRows.length === 0) {
            ledgerTableBodyEl.innerHTML = '<tr><td colspan="11" class="px-3 py-6 text-center text-sm font-semibold text-slate-500">No ledger rows found for selected filters.</td></tr>';
            return;
        }

        const html = lineRows.map(function (r) {
            const periodLabel = `${r.period_year || ''}-${String(r.period_month || '').padStart(2, '0')}`;
            const rowTone = (r.line_type === 'DEBIT') ? 'bg-emerald-50/40' : 'bg-amber-50/40';
            const debitValue = r.debit !== 0 ? (r.debit).toFixed(2) : '';
            const creditValue = r.credit !== 0 ? (r.credit).toFixed(2) : '';
            const expenseValue = (r.period_depreciation_expense || 0).toFixed(2);
            const accumValue = (r.accumulated_depreciation || 0).toFixed(2);
            const bookVal = (r.book_value || 0).toFixed(2);

            return `
                <tr class="border-b border-slate-100 ${rowTone}">
                    <td class="px-3 py-2 text-slate-700">${escapeHtml(formatDateDisplay(r.period_date))}</td>
                    <td class="px-3 py-2 font-mono text-slate-700 text-center">${escapeHtml(periodLabel)}</td>
                    <td class="px-3 py-2 font-mono text-slate-700">${escapeHtml(r.gl_code || '')}</td>
                    <td class="px-3 py-2 text-right font-mono text-sm text-slate-700">${debitValue}</td>
                    <td class="px-3 py-2 text-right font-mono text-sm text-slate-700">${creditValue}</td>
                    <td class="px-3 py-2 text-right font-mono text-sm text-slate-700">${expenseValue}</td>
                    <td class="px-3 py-2 text-right font-mono text-sm text-slate-700">${accumValue}</td>
                    <td class="px-3 py-2 text-right font-mono text-sm text-slate-700">${bookVal}</td>
                    <td class="px-3 py-2 text-center text-xs text-slate-600">${escapeHtml(r.line_type || '')}</td>
                </tr>
            `;
        }).join('');

        ledgerTableBodyEl.innerHTML = html;
    }

    function renderFsRows(fsRows) {
        if (!fsTableBodyEl) return;

        const entryFilter = ledgerState.filters.entry_side || 'ALL';
        const filteredFsRows = fsRows.filter(r => {
            const side = String(r.entry_side || '').toUpperCase();
            if (entryFilter === 'DEBIT' && side !== 'DEBIT') return false;
            if (entryFilter === 'CREDIT' && side !== 'CREDIT') return false;
            return true;
        });

        if (!filteredFsRows || filteredFsRows.length === 0) {
            fsTableBodyEl.innerHTML = '<tr><td colspan="9" class="px-3 py-6 text-center text-sm font-semibold text-slate-500">No financial statement rows found for selected filters.</td></tr>';
            return;
        }

        const html = filteredFsRows.map(function (r) {
            let periodLabel = '-';
            if (r.period_date) {
                const parts = String(r.period_date).split('-');
                if (parts.length >= 2) {
                    periodLabel = `${parts[0]}-${String(parts[1]).padStart(2, '0')}`;
                }
            }
            
            const entryType = String(r.entry_side || '').toUpperCase();
            const bgColor = (entryType === 'DEBIT') ? 'bg-emerald-50/40' : 'bg-amber-50/40';
            const badge = (entryType === 'DEBIT') 
                ? '<span class="inline-flex px-2 py-0.5 rounded text-[10px] font-bold uppercase bg-emerald-100 text-emerald-700">DEBIT</span>'
                : '<span class="inline-flex px-2 py-0.5 rounded text-[10px] font-bold uppercase bg-amber-100 text-amber-700">CREDIT</span>';
            
            const periodAmount = (parseFloat(r.period_depreciation_expense || 0)).toFixed(2);
            const accumulated = (parseFloat(r.accumulated_depreciation || 0)).toFixed(2);
            const bookValue = (parseFloat(r.book_value || 0)).toFixed(2);
            const debitValue = (parseFloat(r.debit_amount || 0)).toFixed(2);
            const creditValue = (parseFloat(r.credit_amount || 0)).toFixed(2);

            return `
                <tr class="border-b border-slate-100 ${bgColor}">
                    <td class="px-3 py-2 text-slate-700">${escapeHtml(formatDateDisplay(r.period_date))}</td>
                    <td class="px-3 py-2 font-mono text-slate-700 text-center">${escapeHtml(periodLabel)}</td>
                    <td class="px-3 py-2 text-center">${badge}</td>
                    <td class="px-3 py-2 font-mono text-slate-700">${escapeHtml(r.account_code || '')}</td>
                    <td class="px-3 py-2 text-right font-mono text-sm text-slate-700">${debitValue}</td>
                    <td class="px-3 py-2 text-right font-mono text-sm text-slate-700">${creditValue}</td>
                    <td class="px-3 py-2 text-right font-mono text-sm text-slate-700">${periodAmount}</td>
                    <td class="px-3 py-2 text-right font-mono text-sm text-slate-700">${accumulated}</td>
                    <td class="px-3 py-2 text-right font-mono text-sm text-slate-700">${bookValue}</td>
                </tr>
            `;
        }).join('');

        fsTableBodyEl.innerHTML = html;
    }

    function updateLedgerFooter(totals, ledgerRows) {
        if (!ledgerFooterSummaryEl || !ledgerTotalDebitEl || !ledgerTotalCreditEl || !ledgerLatestAccumEl || !ledgerLatestBookEl) return;
        const sourceRows = parseInt((totals && totals.row_count) || 0, 10);
        const lineRows = getLedgerLineRows(ledgerRows);
        
        let dynamicDebit = 0;
        let dynamicCredit = 0;
        lineRows.forEach(row => {
            dynamicDebit += row.debit;
            dynamicCredit += row.credit;
        });

        ledgerFooterSummaryEl.textContent = `Rows: ${lineRows.length} lines (${sourceRows} entries)`;
        ledgerTotalDebitEl.textContent = currency.format(dynamicDebit);
        ledgerTotalCreditEl.textContent = currency.format(dynamicCredit);

        const latestRow = (ledgerRows && ledgerRows.length > 0)
            ? ledgerRows[ledgerRows.length - 1]
            : null;

        ledgerLatestAccumEl.textContent = currency.format(parseFloat((latestRow && latestRow.accumulated_depreciation) || 0));
        ledgerLatestBookEl.textContent = currency.format(parseFloat((latestRow && latestRow.book_value) || 0));
    }

    function populatePeriodOptions(options, selectedYear, selectedMonth) {
        if (!ledgerPeriodYearEl || !ledgerPeriodMonthEl) return;

        const years = Array.isArray(options && options.years) ? options.years : [];

        ledgerPeriodYearEl.innerHTML = '<option value="">All Years</option>';
        years.forEach(function (y) {
            const opt = document.createElement('option');
            opt.value = String(y);
            opt.textContent = String(y);
            ledgerPeriodYearEl.appendChild(opt);
        });

        ledgerPeriodMonthEl.innerHTML = '<option value="">All Months</option>';
        for (let i = 1; i <= 12; i++) {
            const opt = document.createElement('option');
            opt.value = String(i);
            opt.textContent = monthName(i);
            ledgerPeriodMonthEl.appendChild(opt);
        }

        ledgerPeriodYearEl.value = selectedYear || '';
        const selMonth = (selectedMonth && !isNaN(parseInt(selectedMonth, 10))) ? String(parseInt(selectedMonth, 10)) : '';
        ledgerPeriodMonthEl.value = selMonth;
    }

    function updateLedgerAssetMeta(asset) {
        if (!ledgerSubtitleEl || !ledgerAssetMetaEl || !asset) return;
        const serial = asset.serial_number || asset.system_asset_code || '-';
        ledgerSubtitleEl.textContent = `${asset.system_asset_code || ''} • ${asset.description || ''}`;
        const groupDisplay = asset.asset_group_id ? `${asset.asset_group_id}` : (asset.group_name || '-');
        ledgerAssetMetaEl.textContent = `Serial: ${serial} | Group: ${groupDisplay} | Branch: ${asset.branch_name || '-'} | Uploaded by: ${asset.uploaded_by || 'Unknown'}`;
    }

    function buildLedgerQuery() {
        const params = new URLSearchParams();
        params.set('asset_id', String(ledgerState.asset.id));
        
        if (ledgerState.filters.date_from) params.set('date_from', ledgerState.filters.date_from);
        if (ledgerState.filters.date_to) params.set('date_to', ledgerState.filters.date_to);

        let m = parseInt(ledgerState.filters.period_month, 10);
        let y = parseInt(ledgerState.filters.period_year, 10);
        
        if (!isNaN(m) && m >= 1 && m <= 12 && isNaN(y)) {
            y = new Date().getFullYear();
            if (ledgerPeriodYearEl) ledgerPeriodYearEl.value = String(y);
            ledgerState.filters.period_year = String(y);
        }

        if (!isNaN(m) && m >= 1 && m <= 12) {
            params.set('period_month', m);
        }
        if (!isNaN(y) && y > 0) {
            params.set('period_year', y);
        }

        if (ledgerState.filters.entry_side) params.set('entry_side', ledgerState.filters.entry_side);
        
        return params.toString();
    }

    function syncLedgerFiltersFromInputs() {
        ledgerState.filters.entry_side = ledgerEntrySideEl ? ledgerEntrySideEl.value : 'ALL';
        ledgerState.filters.period_year = ledgerPeriodYearEl ? ledgerPeriodYearEl.value : '';
        ledgerState.filters.period_month = ledgerPeriodMonthEl ? ledgerPeriodMonthEl.value : '';
    }

    function fetchAssetLedgerReport() {
        if (!ledgerState.asset || !ledgerState.asset.id) return;

        setLedgerLoading(true);
        fetch(`${ledgerApiUrl}?${buildLedgerQuery()}`)
            .then(function (r) {
                if (!r.ok) throw new Error('HTTP ' + r.status);
                return r.text();
            })
            .then(function (text) {
                return JSON.parse(text.replace(/^\uFEFF/, '').trim());
            })
            .then(function (res) {
                if (!res.success) throw new Error(res.error || 'Failed to fetch ledger report.');

                const ledgerRows = res.ledger_rows || [];
                const fsRows = res.fs_rows || [];

                updateLedgerAssetMeta(res.asset);
                renderLedgerRows(ledgerRows);
                renderFsRows(fsRows);
                updateLedgerFooter(res.totals || {}, ledgerRows);
                
                populatePeriodOptions(
                    res.options || {},
                    ledgerState.filters.period_year || '',
                    ledgerState.filters.period_month || ''
                );

                if (ledgerEntrySideEl) ledgerEntrySideEl.value = ledgerState.filters.entry_side;
            })
            .catch(function (err) {
                setLedgerError(`Unable to load ledger report: ${err.message || 'Unknown error'}`);
            })
            .finally(function () {
                setLedgerLoading(false);
            });
    }

    function openAssetLedgerModal(assetPayload) {
        if (!assetPayload || !assetPayload.id || !ledgerModalEl) return;

        ledgerState.asset = assetPayload;
        ledgerState.filters = {
            entry_side: 'ALL',
            period_year: '',
            period_month: ''
        };

        if (ledgerEntrySideEl) ledgerEntrySideEl.value = 'ALL';
        if (ledgerPeriodYearEl) ledgerPeriodYearEl.value = '';
        if (ledgerPeriodMonthEl) ledgerPeriodMonthEl.value = '';

        setLedgerTab('LEDGER');
        openModal('modal-asset-ledger');
        fetchAssetLedgerReport();
    }

    function printActiveLedgerTable() {
        if (!ledgerState.asset) return;

        const activeWrap = (ledgerState.activeTab === 'LEDGER') ? ledgerTableWrapEl : fsTableWrapEl;
        if (!activeWrap) return;

        const tableEl = activeWrap.querySelector('table');
        if (!tableEl) return;

        const printWin = window.open('', '_blank');
        if (!printWin) return;

        const title = (ledgerState.activeTab === 'LEDGER') ? 'Asset Ledger' : 'Financial Statement';
        const generatedBy = (ledgerConfigEl && ledgerConfigEl.dataset.generatedBy)
            ? ledgerConfigEl.dataset.generatedBy
            : 'User';

        const generatedAt = new Intl.DateTimeFormat('en-US', {
            month: 'short',
            day: 'numeric',
            year: 'numeric',
            hour: 'numeric',
            minute: '2-digit'
        }).format(new Date());

        const filterLine = `Side: ${ledgerState.filters.entry_side || 'ALL'} | Year: ${ledgerState.filters.period_year || 'All'} | Month: ${ledgerState.filters.period_month || 'All'}`;
        const style = `
            <style>
                body { font-family: Arial, Helvetica, sans-serif; padding: 12px; color: #1e293b; }
                h1 { margin: 0 0 4px; font-size: 16px; }
                .meta { margin: 0 0 12px; font-size: 11px; color: #475569; }
                table { border-collapse: collapse; width: 100%; }
                th, td { border: 1px solid #ddd; padding: 6px 8px; font-size: 11px; }
                th { background: #ce2216; color: #fff; font-weight: 700; }
                .num { text-align: right; font-family: Consolas, monospace; }
            </style>
        `;

        printWin.document.open();
        printWin.document.write(`<!doctype html><html><head><meta charset="utf-8"><title>${title}</title>${style}</head><body><h1>${title}</h1><p class="meta">Asset: ${escapeHtml(ledgerState.asset.system_asset_code || '')} | Serial: ${escapeHtml(ledgerState.asset.serial_number || '')} | Group: ${escapeHtml(ledgerState.asset.group_code || '')} | Branch: ${escapeHtml(ledgerState.asset.branch_name || '')}<br>${escapeHtml(filterLine)}<br>Generated by: ${escapeHtml(generatedBy)} | Generated at: ${escapeHtml(generatedAt)}</p>${tableEl.outerHTML}</body></html>`);
        printWin.document.close();
        printWin.focus();
        setTimeout(function () {
            try { printWin.print(); } catch (e) { console.error(e); }
        }, 300);
    }

    if (tableBody) {
        tableBody.addEventListener('click', function (e) {
            const row = e.target.closest('tr.depr-asset-row');
            if (!row || !row.dataset.asset) return;

            try {
                const assetPayload = JSON.parse(decodeURIComponent(row.dataset.asset));
                openAssetLedgerModal(assetPayload);
            } catch (err) {
                console.error('Failed to parse selected asset payload:', err);
            }
        });

        populateListGroupFilter();

        let searchDebounce = null;
        if (searchInput) {
            searchInput.addEventListener('input', function () {
                clearTimeout(searchDebounce);
                searchDebounce = setTimeout(function () {
                    listState.search = searchInput.value.trim();
                    listState.page = 1;
                    fetchDepreciationList();
                }, 300);
            });
        }

        if (dateFromInput) {
            dateFromInput.addEventListener('change', function () {
                listState.date_from = dateFromInput.value || '';
                listState.page = 1;
                fetchDepreciationList();
            });
        }

        if (dateToInput) {
            dateToInput.addEventListener('change', function () {
                listState.date_to = dateToInput.value || '';
                listState.page = 1;
                fetchDepreciationList();
            });
        }

        if (statusFilter) {
            statusFilter.addEventListener('change', function () {
                listState.status = statusFilter.value || '';
                listState.page = 1;
                fetchDepreciationList();
            });
        }

        if (groupFilter) {
            groupFilter.addEventListener('change', function () {
                listState.group_code = groupFilter.value;
                listState.branch_name = '';
                if (branchFilter) branchFilter.value = '';
                listState.page = 1;
                fetchDepreciationList();
            });
        }

        if (branchFilter) {
            branchFilter.addEventListener('change', function () {
                listState.branch_name = branchFilter.value;
                listState.page = 1;
                fetchDepreciationList();
            });
        }

        if (resetBtn) {
            resetBtn.addEventListener('click', function () {
                if (searchInput) searchInput.value = '';
                if (groupFilter) groupFilter.value = '';
                if (branchFilter) branchFilter.value = '';
                if (dateFromInput) dateFromInput.value = '';
                if (dateToInput) dateToInput.value = '';
                if (statusFilter) statusFilter.value = '';

                listState.page = 1;
                listState.search = '';
                listState.group_code = '';
                listState.branch_name = '';
                listState.date_from = '';
                listState.date_to = '';
                listState.status = '';
                listState.sort_by = 'created_at';
                listState.sort_dir = 'DESC';

                fetchDepreciationList();
            });
        }

        sortButtons.forEach(function (btn) {
            btn.addEventListener('click', function () {
                const sortField = btn.dataset.sort;
                if (!sortField) return;

                if (listState.sort_by === sortField) {
                    listState.sort_dir = (listState.sort_dir === 'ASC') ? 'DESC' : 'ASC';
                } else {
                    listState.sort_by = sortField;
                    listState.sort_dir = 'ASC';
                }

                listState.page = 1;
                fetchDepreciationList();
            });
        });

        if (prevBtn) {
            prevBtn.addEventListener('click', function () {
                if (listState.page <= 1) return;
                listState.page -= 1;
                fetchDepreciationList();
            });
        }

        if (nextBtn) {
            nextBtn.addEventListener('click', function () {
                listState.page += 1;
                fetchDepreciationList();
            });
        }

        fetchDepreciationList();
    }

    [ledgerEntrySideEl, ledgerPeriodYearEl, ledgerPeriodMonthEl]
        .filter(Boolean)
        .forEach(function (el) {
            el.addEventListener('change', function () {
                if (!ledgerState.asset || !ledgerState.asset.id) {
                    return;
                }
                syncLedgerFiltersFromInputs();
                fetchAssetLedgerReport();
            });
        });

    if (ledgerResetFilterBtn) {
        ledgerResetFilterBtn.addEventListener('click', function () {
            if (ledgerEntrySideEl) ledgerEntrySideEl.value = 'ALL';
            if (ledgerPeriodYearEl) ledgerPeriodYearEl.value = '';
            if (ledgerPeriodMonthEl) ledgerPeriodMonthEl.value = '';

            ledgerState.filters = {
                entry_side: 'ALL',
                period_year: '',
                period_month: '',
            };
            fetchAssetLedgerReport();
        });
    }

    if (ledgerTabLedgerBtn) {
        ledgerTabLedgerBtn.addEventListener('click', function () {
            setLedgerTab('LEDGER');
        });
    }

    if (ledgerTabFsBtn) {
        ledgerTabFsBtn.addEventListener('click', function () {
            setLedgerTab('FS');
        });
    }

    if (ledgerPrintBtn) {
        ledgerPrintBtn.addEventListener('click', function () {
            printActiveLedgerTable();
        });
    }

});