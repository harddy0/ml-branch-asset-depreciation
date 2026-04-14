document.addEventListener('DOMContentLoaded', function () {
    console.log("System: Asset Management JS Initialized");
    
    // ==========================================
    // 1. DYNAMIC FORM UI LOGIC (Specific Date)
    // ==========================================
    const depreciationOnSelect = document.getElementById('depreciation_on');
    const specificDayInput = document.getElementById('depreciation_day');
    const specificDayLabel = document.getElementById('depreciation_day_label');

    if (depreciationOnSelect && specificDayInput) {
        depreciationOnSelect.addEventListener('change', function () {
            if (this.value === 'SPECIFIC_DATE') {
                specificDayInput.disabled = false;
                specificDayInput.classList.remove('bg-slate-100', 'text-slate-400', 'cursor-not-allowed', 'border-slate-200');
                specificDayInput.classList.add('border-slate-300', 'focus:ring-2', 'focus:ring-red-500');
                specificDayInput.setAttribute('required', 'required');
                if(specificDayLabel) specificDayLabel.innerHTML = 'Specific Day <span class="text-red-500">*</span>';
            } else {
                specificDayInput.disabled = true;
                specificDayInput.classList.add('bg-slate-100', 'text-slate-400', 'cursor-not-allowed', 'border-slate-200');
                specificDayInput.classList.remove('border-slate-300', 'focus:ring-2', 'focus:ring-red-500');
                specificDayInput.removeAttribute('required');
                specificDayInput.value = '';
                if(specificDayLabel) specificDayLabel.innerHTML = 'Specific Day';
            }
        });
    }

    // ==========================================
    // 2. LOCATIONS HIERARCHY FETCH & AUTO-FILL
    // ==========================================
    const mainZoneSelect = document.getElementById('main_zone_code');
    const zoneSelect = document.getElementById('zone_code');
    const regionSelect = document.getElementById('region_code');
    const branchSelect = document.getElementById('branch_name');
    const costCenterInput = document.getElementById('cost_center_code');

    let allBranches = []; 

    let baseUrlClean = '/';
    if (typeof BASE_URL !== 'undefined' && BASE_URL !== '') {
        baseUrlClean = BASE_URL.endsWith('/') ? BASE_URL : BASE_URL + '/';
    }
    
    const apiUrl = baseUrlClean + 'api/get_locations.php';

    fetch(apiUrl)
        .then(response => {
            if (!response.ok) throw new Error("HTTP error " + response.status);
            return response.text(); 
        })
        .then(text => {
            let cleanText = text.replace(/^\uFEFF/, '').trim();
            return JSON.parse(cleanText);
        })
        .then(data => {
            if (data.success && data.branches) {
                allBranches = data.branches;
                
                // Initialize ONLY Main Zone
                populateDropdown(mainZoneSelect, getUniqueValues(allBranches, 'main_zone_code'), 'Select Main Zone...');
                populateDropdown(zoneSelect, [], 'Waiting for Main Zone...');
                populateDropdown(regionSelect, [], 'Waiting for Sub-Zone...');
                populateBranchDropdown([]);
                
                if(mainZoneSelect) {
                    mainZoneSelect.disabled = false;
                    mainZoneSelect.classList.remove('disabled:bg-slate-100', 'disabled:text-slate-400');
                }
            }
        })
        .catch(err => console.error('System Fetch Error:', err));

    // --- Helper Functions ---
    
    // Gets simple unique values (used for Zones)
    function getUniqueValues(array, key) {
        return [...new Set(array.map(item => item[key]).filter(Boolean))].sort();
    }

    // NEW: Gets unique Regions and formats them with their Description
    function getUniqueRegions(array) {
        let unique = {};
        array.forEach(item => {
            if(item.region_code && !unique[item.region_code]) {
                // If description exists, combine them: "R03 - Central Luzon"
                unique[item.region_code] = item.region_description 
                    ? `${item.region_code} - ${item.region_description}` 
                    : item.region_code;
            }
        });
        // Convert to array of objects and sort alphabetically by code
        return Object.keys(unique).sort().map(k => ({ value: k, text: unique[k] }));
    }

    // UPDATED: Supports both simple arrays and arrays of objects (for Regions)
    function populateDropdown(selectEl, valuesArray, defaultText) {
        if (!selectEl) return;
        selectEl.innerHTML = `<option value="" disabled selected>${defaultText}</option>`;
        
        valuesArray.forEach(val => {
            let opt = document.createElement('option');
            if (typeof val === 'object') {
                opt.value = val.value; // The DB Code
                opt.textContent = val.text; // The UI Label (Code - Description)
            } else {
                opt.value = val;
                opt.textContent = val;
            }
            selectEl.appendChild(opt);
        });
        
        if(valuesArray.length === 0) {
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
        
        if(branchesArray.length === 0) {
            branchSelect.disabled = true;
            branchSelect.classList.add('disabled:bg-slate-100', 'disabled:text-slate-400');
            return;
        }

        branchSelect.disabled = false;
        branchSelect.classList.remove('disabled:bg-slate-100', 'disabled:text-slate-400');

        branchesArray.sort((a, b) => {
            let nameA = a.branch_name ? String(a.branch_name).trim() : '';
            let nameB = b.branch_name ? String(b.branch_name).trim() : '';
            return nameA.localeCompare(nameB);
        }).forEach(b => {
            if (!b.branch_name) return; 
            let opt = document.createElement('option');
            opt.value = b.branch_name; 
            opt.textContent = b.branch_name;
            branchSelect.appendChild(opt);
        });
    }

    // --- STRICT TOP-DOWN CASCADING LOGIC ---
    
    if (mainZoneSelect) {
        mainZoneSelect.addEventListener('change', function() {
            let filtered = allBranches.filter(b => b.main_zone_code === this.value);
            populateDropdown(zoneSelect, getUniqueValues(filtered, 'zone_code'), 'Select Sub-Zone...');
            
            populateDropdown(regionSelect, [], 'Waiting for Sub-Zone...');
            populateBranchDropdown([]);
            if(costCenterInput) costCenterInput.value = ''; 
        });
    }

    if (zoneSelect) {
        zoneSelect.addEventListener('change', function() {
            let filtered = allBranches.filter(b => b.main_zone_code === mainZoneSelect.value && b.zone_code === this.value);
            
            // USE NEW REGION FORMATTER HERE
            populateDropdown(regionSelect, getUniqueRegions(filtered), 'Select Region...');
            
            populateBranchDropdown([]);
            if(costCenterInput) costCenterInput.value = '';
        });
    }

    if (regionSelect) {
        regionSelect.addEventListener('change', function() {
            let filtered = allBranches.filter(b => 
                b.main_zone_code === mainZoneSelect.value && 
                b.zone_code === zoneSelect.value && 
                b.region_code === this.value
            );
            populateBranchDropdown(filtered);
            if(costCenterInput) costCenterInput.value = '';
        });
    }

    if (branchSelect) {
        branchSelect.addEventListener('change', function() {
            const selectedBranch = allBranches.find(b => b.branch_name === this.value);
            if (selectedBranch && costCenterInput) {
                costCenterInput.value = selectedBranch.cost_center_code;
            }
        });
    }

    // ==========================================
    // 3. FORM SUBMISSION INTERCEPT
    // ==========================================
    const form = document.getElementById('addAssetForm');
    if (form) {
        form.addEventListener('submit', function (e) {
            e.preventDefault();
            if (!form.checkValidity()) {
                form.reportValidity();
                return;
            }
            console.log("Form validated successfully.");
            
            if (typeof window.closeModal === 'function') {
                window.closeModal('modal-add-asset');
            } else {
                document.getElementById('modal-add-asset').classList.add('hidden');
            }
            alert('Form submitted successfully!');
        });
    }
});