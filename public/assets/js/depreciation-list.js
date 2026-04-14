document.addEventListener('DOMContentLoaded', function () {
    console.log("System: Asset Management JS Initialized");

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

    let baseUrlClean = '/';
    if (typeof BASE_URL !== 'undefined' && BASE_URL !== '') {
        baseUrlClean = BASE_URL.endsWith('/') ? BASE_URL : BASE_URL + '/';
    }

    const locationsApiUrl   = baseUrlClean + 'api/get_locations.php';
    const groupDetailsApiUrl = baseUrlClean + 'api/get_group_details.php';

    fetch(locationsApiUrl)
        .then(r => { if (!r.ok) throw new Error("HTTP " + r.status); return r.text(); })
        .then(text => JSON.parse(text.replace(/^\uFEFF/, '').trim()))
        .then(data => {
            if (data.success && data.branches) {
                allBranches = data.branches;
                populateDropdown(mainZoneSelect, getUniqueValues(allBranches, 'main_zone_code'), 'Select Main Zone...');
                populateDropdown(zoneSelect,     [], 'Waiting for Main Zone...');
                populateDropdown(regionSelect,   [], 'Waiting for Sub-Zone...');
                populateBranchDropdown([]);
                if (mainZoneSelect) {
                    mainZoneSelect.disabled = false;
                    mainZoneSelect.classList.remove('disabled:bg-slate-100', 'disabled:text-slate-400');
                }
            }
        })
        .catch(err => console.error('Location fetch error:', err));

    // --- Location Helper Functions ---

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
        if (!branchSelect) return;
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
    }

    // --- Strict top-down cascade ---
    if (mainZoneSelect) {
        mainZoneSelect.addEventListener('change', function () {
            let filtered = allBranches.filter(b => b.main_zone_code === this.value);
            populateDropdown(zoneSelect, getUniqueValues(filtered, 'zone_code'), 'Select Sub-Zone...');
            populateDropdown(regionSelect, [], 'Waiting for Sub-Zone...');
            populateBranchDropdown([]);
            if (costCenterInput) costCenterInput.value = '';
        });
    }
    if (zoneSelect) {
        zoneSelect.addEventListener('change', function () {
            let filtered = allBranches.filter(b =>
                b.main_zone_code === mainZoneSelect.value && b.zone_code === this.value
            );
            populateDropdown(regionSelect, getUniqueRegions(filtered), 'Select Region...');
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
    if (branchSelect) {
        branchSelect.addEventListener('change', function () {
            const found = allBranches.find(b => b.branch_name === this.value);
            if (found && costCenterInput) costCenterInput.value = found.cost_center_code;
        });
    }

    // ==========================================
    // 3. GL GROUP SELECT — Load options + Auto-fill
    // ==========================================

    const glGroupSelect      = document.getElementById('gl_group_select');     // <select name="group_code">
    const glGroupCodeDisplay = document.getElementById('gl_group_code_display'); // read-only code box on the RIGHT
    const glAssetCodeDisplay = document.getElementById('gl_asset_code_display'); // name="asset_code"
    const glAssetNameDisplay = document.getElementById('gl_asset_name_display');
    const glDepCodeDisplay   = document.getElementById('gl_dep_code_display');   // name="depreciation_code"
    const glDepNameDisplay   = document.getElementById('gl_dep_name_display');

    /** Clears all auto-fill GL fields back to empty/placeholder state. */
    function clearGlFields() {
        if (glGroupCodeDisplay) glGroupCodeDisplay.value = '';
        if (glAssetCodeDisplay) glAssetCodeDisplay.value = '';
        if (glAssetNameDisplay) glAssetNameDisplay.value = '';
        if (glDepCodeDisplay)   glDepCodeDisplay.value   = '';
        if (glDepNameDisplay)   glDepNameDisplay.value   = '';
    }

    /**
     * Populate the Group <select> from window.__assetGroups injected by PHP.
     * Format: [{ group_code: 'OE24MOS', group_name: 'Office Equipment (24mos)' }, ...]
     */
    function initGroupDropdown() {
        if (!glGroupSelect) return;

        const groups = window.__assetGroups || [];

        // Always reset first
        glGroupSelect.innerHTML = '<option value="" disabled selected>Select Group...</option>';

        if (groups.length === 0) {
            glGroupSelect.innerHTML = '<option value="" disabled selected>No groups configured</option>';
            console.warn('GL groups: window.__assetGroups is empty. Check inject_asset_groups_snippet.php is loaded before depreciation-list.js.');
            return;
        }

        groups.forEach(function (g) {
            const opt       = document.createElement('option');
            opt.value       = g.group_code;
            opt.textContent = g.group_name;
            glGroupSelect.appendChild(opt);
        });
    }

    // Run immediately — window.__assetGroups must be set before this script loads
    initGroupDropdown();

    /** On group change: show code on right, fetch chain, fill Asset + Dep rows. */
    if (glGroupSelect) {
        glGroupSelect.addEventListener('change', function () {
            const selectedCode = this.value;

            if (!selectedCode) {
                clearGlFields();
                return;
            }

            // Show selected group_code in the right-side read-only box immediately
            if (glGroupCodeDisplay) glGroupCodeDisplay.value = selectedCode;

            // Fetch the full classification chain from the API
            fetch(`${groupDetailsApiUrl}?group_code=${encodeURIComponent(selectedCode)}`)
                .then(function (r) {
                    if (!r.ok) throw new Error('HTTP ' + r.status);
                    return r.json();
                })
                .then(function (res) {
                    if (!res.success || !res.data) {
                        console.error('GL auto-fill failed:', res.error);
                        clearGlFields();
                        // Still keep the group code display since user did pick something
                        if (glGroupCodeDisplay) glGroupCodeDisplay.value = selectedCode;
                        return;
                    }

                    const d = res.data;

                    // Right-side code box (confirms what was selected)
                    if (glGroupCodeDisplay) glGroupCodeDisplay.value = d.group_code;

                    // Asset row (credit GL account)
                    if (glAssetCodeDisplay) glAssetCodeDisplay.value = d.asset_code;
                    if (glAssetNameDisplay) glAssetNameDisplay.value = d.asset_name;

                    // Depreciation P&L row (debit GL account)
                    if (glDepCodeDisplay) glDepCodeDisplay.value = d.depreciation_code;
                    if (glDepNameDisplay) glDepNameDisplay.value = d.depreciation_description;

                    // Feed actual_months into the end date auto-compute
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
    // 4. END DATE AUTO-COMPUTE (UPDATED LOGIC)
    // ==========================================

    const dateReceivedInput   = document.getElementById('date_received');
    const startDateInput      = document.getElementById('depreciation_start_date'); // Now a hidden field
    const endDateInput        = document.getElementById('depreciation_end_date');
    const endDateAutoBadge    = document.getElementById('end_date_auto_badge');
    
    // Schedule Setting Selectors
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

        // 1. Determine exact Start Date dynamically based on rules
        if (depOn === 'LAST_DAY') {
            startDateObj = new Date(startYear, startMonth + 1, 0); 
        } else if (depOn === 'FIRST_DAY') {
            startDateObj = new Date(startYear, startMonth, 1); 
        } else {
            // SPECIFIC_DATE: clamp to the end of the month if they pick 31st on a 30-day month
            let lastDayOfMonth = new Date(startYear, startMonth + 1, 0).getDate();
            let clampedDay = Math.min(specificDay, lastDayOfMonth);
            startDateObj = new Date(startYear, startMonth, clampedDay);
        }

        // Set the hidden field so it reaches the database properly
        if (startDateInput) {
            startDateInput.value = formatDate(startDateObj);
        }

        // 2. Compute End Date if group months are loaded
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

    // Stop auto-computing if user manually overrides end date
    if (endDateInput) {
        endDateInput.addEventListener('input', function () {
            endDateManuallySet = true;
            endDateInput.classList.remove('bg-slate-50');
            endDateInput.classList.add('bg-white');
            if (endDateAutoBadge) endDateAutoBadge.classList.add('hidden');
        });
    }

    // Trigger dates calculation on any schedule input change
    if (dateReceivedInput) dateReceivedInput.addEventListener('change', computeDates);
    if (depOnSelect) depOnSelect.addEventListener('change', computeDates);
    if (depDayInput) depDayInput.addEventListener('input', computeDates);

    // Recompute when Asset Group is selected (injects months lifespan)
    window.__setActualMonths = function (months) {
        currentActualMonths = parseInt(months) || 0;
        endDateManuallySet  = false; // reset override allowance when group changes
        if (endDateAutoBadge) endDateAutoBadge.classList.remove('hidden');
        computeDates();
    };

    // ==========================================
    // 5. RESET FORM when modal is closed
    // ==========================================
    const modalEl = document.getElementById('modal-add-asset');
    if (modalEl) {
        // Watch for when the modal becomes hidden (close button / cancel)
        const observer = new MutationObserver(function (mutations) {
            mutations.forEach(m => {
                if (m.attributeName === 'class' && modalEl.classList.contains('hidden')) {
                    if (form) form.reset();
                    clearGlFields();
                    // Restore placeholder after reset() clears the select
                    if (glGroupSelect) glGroupSelect.value = '';
                    // Reset end date auto-compute state
                    endDateManuallySet = false;
                    currentActualMonths = 0;
                    if (endDateAutoBadge) endDateAutoBadge.classList.remove('hidden');
                }
            });
        });
        observer.observe(modalEl, { attributes: true });
    }
});