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

    const glGroupSelect       = document.getElementById('gl_group_select');
    const glGroupCodeDisplay  = document.getElementById('gl_group_code_display');
    const glGroupCodeValue    = document.getElementById('gl_group_code_value');   // hidden input → name="group_code"
    const glAssetCodeDisplay  = document.getElementById('gl_asset_code_display'); // name="asset_code"
    const glAssetNameDisplay  = document.getElementById('gl_asset_name_display');
    const glDepCodeDisplay    = document.getElementById('gl_dep_code_display');   // name="depreciation_code"
    const glDepNameDisplay    = document.getElementById('gl_dep_name_display');

    /**
     * Clears all auto-fill GL fields back to empty/placeholder state.
     */
    function clearGlFields() {
        if (glGroupCodeDisplay) glGroupCodeDisplay.value = '';
        if (glGroupCodeValue)   glGroupCodeValue.value   = '';
        if (glAssetCodeDisplay) glAssetCodeDisplay.value = '';
        if (glAssetNameDisplay) glAssetNameDisplay.value = '';
        if (glDepCodeDisplay)   glDepCodeDisplay.value   = '';
        if (glDepNameDisplay)   glDepNameDisplay.value   = '';
    }

    /**
     * Fetches all asset_groups from the DB and populates the Group select.
     * Uses the existing get_group_details endpoint at the list level via
     * AssetClassificationService::getDropdownOptions() — but we need a
     * separate lightweight list endpoint.
     *
     * NOTE: The get_group_details.php endpoint returns one group at a time.
     * We load the group list from a new get_asset_groups.php endpoint (see below),
     * OR we can inline it via PHP in the modal.
     *
     * For now we use the PHP-inlined approach: the modal PHP file populates
     * a JS array window.__assetGroups that this script reads.
     */
    function initGroupDropdown() {
        if (!glGroupSelect) return;

        const groups = window.__assetGroups || [];

        glGroupSelect.innerHTML = '<option value="" disabled selected>Select Group...</option>';

        if (groups.length === 0) {
            const opt = document.createElement('option');
            opt.disabled    = true;
            opt.textContent = 'No groups available';
            glGroupSelect.appendChild(opt);
            return;
        }

        groups.forEach(g => {
            const opt = document.createElement('option');
            opt.value       = g.group_code;
            opt.textContent = g.group_name;
            glGroupSelect.appendChild(opt);
        });
    }

    initGroupDropdown();

    /**
     * On group change: fetch full details and auto-fill the read-only GL boxes.
     */
    if (glGroupSelect) {
        glGroupSelect.addEventListener('change', function () {
            const selectedCode = this.value;

            if (!selectedCode) {
                clearGlFields();
                return;
            }

            // Show the code in the left display box immediately
            if (glGroupCodeDisplay) glGroupCodeDisplay.value = selectedCode;
            if (glGroupCodeValue)   glGroupCodeValue.value   = selectedCode;

            // Fetch the full chain from the API
            fetch(`${groupDetailsApiUrl}?group_code=${encodeURIComponent(selectedCode)}`)
                .then(r => {
                    if (!r.ok) throw new Error("HTTP " + r.status);
                    return r.json();
                })
                .then(res => {
                    if (!res.success || !res.data) {
                        console.error('GL auto-fill failed:', res.error);
                        clearGlFields();
                        return;
                    }

                    const d = res.data;

                    // Group
                    if (glGroupCodeDisplay) glGroupCodeDisplay.value = d.group_code;
                    if (glGroupCodeValue)   glGroupCodeValue.value   = d.group_code;

                    // Asset (credit GL)
                    if (glAssetCodeDisplay) glAssetCodeDisplay.value = d.asset_code;
                    if (glAssetNameDisplay) glAssetNameDisplay.value = d.asset_name;

                    // Depreciation P&L (debit GL)
                    if (glDepCodeDisplay) glDepCodeDisplay.value = d.depreciation_code;
                    if (glDepNameDisplay) glDepNameDisplay.value = d.depreciation_description;
                })
                .catch(err => {
                    console.error('GL fetch error:', err);
                    clearGlFields();
                });
        });
    }

    // ==========================================
    // 4. FORM SUBMISSION INTERCEPT
    // ==========================================
    const form = document.getElementById('addAssetForm');
    if (form) {
        form.addEventListener('submit', function (e) {
            e.preventDefault();

            // Extra guard: ensure a group was actually selected
            if (!glGroupCodeValue || !glGroupCodeValue.value) {
                alert('Please select an Asset Group before saving.');
                glGroupSelect && glGroupSelect.focus();
                return;
            }

            if (!form.checkValidity()) {
                form.reportValidity();
                return;
            }

            console.log("Form validated successfully. Ready for server submit.");

            // TODO: replace this with actual fetch/POST or form.submit()
            // when the asset_store.php action is wired up.
            if (typeof window.closeModal === 'function') {
                window.closeModal('modal-add-asset');
            } else {
                document.getElementById('modal-add-asset').classList.add('hidden');
            }
            alert('Form submitted successfully!');
        });
    }

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
                    // Re-init group dropdown in case it was wiped by reset()
                    if (glGroupSelect) {
                        glGroupSelect.value = '';
                    }
                }
            });
        });
        observer.observe(modalEl, { attributes: true });
    }
});