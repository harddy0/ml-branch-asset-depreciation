document.addEventListener("DOMContentLoaded", function() {
    
    const form = document.getElementById('filterForm');
    if (!form) return;

    // Retrieve backend routing URLs from the DOM
    const apiUrl = form.getAttribute('data-api-url');
    const exportUrl = form.getAttribute('data-export-url');

    let tsZone, tsRegion, tsBranch;

    // Prevent enter key from reloading the page
    form.addEventListener('submit', (e) => e.preventDefault());

    // ─── 1. Initialize Plugins ───────────────────────────────────────────
    tsZone = new TomSelect('#zoneSelect', {
        create: false, maxOptions: null, valueField: 'value', labelField: 'text', searchField: 'text', placeholder: 'Select Zone', dropdownParent: 'body',
        onChange: function(value) {
            if (tsZone && tsZone.wrapper) tsZone.wrapper.classList.remove('ts-typing-mode');
            fetchData('zone');
            setTomSelectTitleAndScroll(tsZone, 'Select Zone');
        }
    });

    tsRegion = new TomSelect('#regionSelect', {
        create: false, maxOptions: null, valueField: 'value', labelField: 'text', searchField: 'text', placeholder: 'Select Region', dropdownParent: 'body',
        onChange: function(value) {
            if (tsRegion && tsRegion.wrapper) tsRegion.wrapper.classList.remove('ts-typing-mode');
            fetchData('region');
            setTomSelectTitleAndScroll(tsRegion, 'Select Region');
        }
    });

    tsBranch = new TomSelect('#branchSelect', {
        create: false, maxOptions: null, valueField: 'value', labelField: 'text', searchField: 'text', placeholder: 'Select Branch', dropdownParent: 'body',
        onChange: function() {
            if (tsBranch && tsBranch.wrapper) tsBranch.wrapper.classList.remove('ts-typing-mode');
            fetchData('branch');
            setTomSelectTitleAndScroll(tsBranch, 'Select Branch');
        }
    });

    // ─── Deferred-input behavior for TomSelect controls ───────────────
    // Click: show full selected label (keep input hidden). Typing after click
    // will enable the input, clear it, insert the first typed character and
    // open the dropdown to search.
    let _deferredTS = null;

    function setTypingMode(ts, isTyping) {
        if (!ts || !ts.wrapper) return;
        if (isTyping) {
            ts.wrapper.classList.add('ts-typing-mode');
        } else {
            ts.wrapper.classList.remove('ts-typing-mode');
            // Clear any hidden search term so opening the dropdown shows all options
            try {
                if (typeof ts.setTextboxValue === 'function') {
                    ts.setTextboxValue('');
                } else if (ts.input) {
                    ts.input.value = '';
                }
                ts.refreshOptions(false);
            } catch (err) {}
        }
    }

    function attachDeferredInput(ts) {
        if (!ts || !ts.wrapper) return;
        // prevent TomSelect from auto-opening when input receives focus
        ts.settings.openOnFocus = false;

        const ctrl = ts.wrapper.querySelector('.ts-control');
        const input = ts.input;

        setTypingMode(ts, false);

        ctrl.addEventListener('click', function(e) {
            // open dropdown but keep internal input hidden so user sees full label
            _deferredTS = ts;
            setTypingMode(ts, false);
            // Ensure option list is not pre-filtered by any stale textbox value
            try {
                if (typeof ts.setTextboxValue === 'function') {
                    ts.setTextboxValue('');
                } else if (ts.input) {
                    ts.input.value = '';
                }
            } catch (err) {}
            ts.open();
            // keep label anchored from the start to avoid clipped values
            try { ctrl.scrollLeft = 0; } catch (err) {}
        });

        // when TomSelect loses focus, reset input visibility
        if (typeof ts.onBlur !== 'function') ts.onBlur = function() {};
        const origBlur = ts.onBlur;
        ts.onBlur = function() {
            setTypingMode(ts, false);
            if (typeof origBlur === 'function') origBlur.apply(this, arguments);
        };
    }

    // Attach deferred behavior to our selects
    attachDeferredInput(tsZone);
    attachDeferredInput(tsRegion);
    attachDeferredInput(tsBranch);

    // Global key handler: if a TomSelect was clicked (deferred), enable input
    document.addEventListener('keydown', function(e) {
        if (!_deferredTS) return;
        const key = e.key;
        const isPrintable = key.length === 1;
        // Only handle printable characters; allow Backspace to be ignored until input is active
        if (!isPrintable) return;

        e.preventDefault();

        const ts = _deferredTS;
        const input = ts.input;
        if (!input) return;

        // show and focus input, clear it, insert the first typed char
        setTypingMode(ts, true);
        try { input.value = ''; } catch (err) {}
        input.focus();
        try {
            input.value = key;
            input.setSelectionRange(1,1);
        } catch (err) {}

        // trigger TomSelect's input handling and open dropdown
        input.dispatchEvent(new Event('input', { bubbles: true }));
        try { ts.open(); } catch (err) {}

        // clear deferred marker so subsequent keys go straight to the input
        _deferredTS = null;
    });

    flatpickr(".date-formatter", {
        altInput: true, altFormat: "M j, Y", dateFormat: "Y-m-d",
        onChange: function() {
            fetchData('date');
        }
    });

    // ─── 2. Fetch API Logic ──────────────────────────────────────────────
    function fetchData(source) {
        const params = new URLSearchParams(new FormData(form)).toString();
        document.getElementById('tableWrapper').style.opacity = '0.5';

        fetch(`${apiUrl}?${params}`)
            .then(response => response.json())
            .then(res => {
                if(res.success) {
                    if (source === 'zone') {
                        updateTomSelect(tsRegion, res.regions, 'Regions');
                        updateTomSelect(tsBranch, res.branches, 'Branches');
                    } else if (source === 'region') {
                        updateTomSelect(tsBranch, res.branches, 'Branches');
                    }
                    renderTable(res.data, res.totals);
                } else {
                    console.error("Failed to fetch data:", res.error);
                }
            })
            .catch(err => console.error("Network Error:", err))
            .finally(() => {
                document.getElementById('tableWrapper').style.opacity = '1';
            });
    }

    // ─── 3. DOM Manipulation Helpers ─────────────────────────────────────
    function updateTomSelect(instance, optionsData, labelPlural) {
        const current = instance.getValue();
        instance.clearOptions();
        const singular = labelPlural === 'Zones' ? 'Zone' : (labelPlural === 'Regions' ? 'Region' : (labelPlural === 'Branches' ? 'Branch' : labelPlural));
        const defaultLabel = 'Select ' + singular;
        instance.addOption({value: '', text: defaultLabel});
        instance.addOption({value: '__ALL__', text: 'All ' + labelPlural});
        optionsData.forEach(item => {
            instance.addOption({value: item, text: item});
        });
        instance.refreshOptions(false);

        // restore previous selection when still available, otherwise reset to default
        if (current === '__ALL__' || (current && optionsData.includes(current))) {
            instance.setValue(current, true);
        } else {
            instance.setValue('', true);
        }

        setTomSelectTitleAndScroll(instance, defaultLabel);
    }

    function setTomSelectTitleAndScroll(instance, defaultLabel) {
        if (!instance) return;
        const val = instance.getValue();
        let label = defaultLabel;
        if (val) {
            const opt = instance.options && instance.options[val];
            if (opt && opt.text) label = opt.text;
        }
        if (instance.wrapper) {
            instance.wrapper.title = label;
            const ctrl = instance.wrapper.querySelector('.ts-control');
            if (ctrl) {
                // Keep text anchored from the start to avoid clipped/cut labels
                ctrl.scrollLeft = 0;
            }
        }
    }

    // Click handler for table rows — parses embedded row data and opens modal instantly
    function assetRowClickHandler(e) {
        const tr = e.target.closest && e.target.closest('tr.asset-row');
        if (!tr) return;

        let raw = tr.dataset && tr.dataset.asset ? tr.dataset.asset : tr.getAttribute('data-asset');
        if (!raw) return;
        
        try {
            // Safely decode HTML entities and parse the complete JSON row data
            const decoded = raw.replace(/&quot;/g, '"').replace(/&#039;/g, "'").replace(/&amp;/g, '&').replace(/&lt;/g, '<').replace(/&gt;/g, '>');
            let parsed = JSON.parse(decoded);
            
            // 1. Normalize field names so the global render function finds them
            parsed.depreciation_start = parsed.depreciation_start_date || parsed.depreciation_start;
            parsed.monthly_depreciation = parsed.period_depreciation_expense || parsed.monthly_depreciation;
            
            // 2. Dynamically Calculate Retirement Date if it's missing
            let retDate = parsed.retirement_date;
            if (!retDate || String(retDate).startsWith('0000-00-00')) {
                if (parsed.depreciation_start && parsed.asset_life_months) {
                    const safeDepr = String(parsed.depreciation_start).includes(' ') ? parsed.depreciation_start : String(parsed.depreciation_start).replace(/-/g, '/');
                    const dDate = new Date(safeDepr);
                    if (!isNaN(dDate.getTime())) {
                        const targetYear = dDate.getFullYear();
                        const targetMonth = dDate.getMonth() + parseInt(parsed.asset_life_months);
                        const calcRetDate = new Date(targetYear, targetMonth + 1, 0);
                        parsed.retirement_date = `${calcRetDate.getFullYear()}-${String(calcRetDate.getMonth() + 1).padStart(2, '0')}-${String(calcRetDate.getDate()).padStart(2, '0')}`;
                    }
                }
            }

            // 3. Hand off the enriched data to your REAL design function (renderDeprDetails)
            if (typeof renderDeprDetails === 'function') {
                renderDeprDetails(parsed, false); // false = view-only mode
                if (typeof setDeprEditMode === 'function') setDeprEditMode(false);
                
                // === FORCE HIDE THE EDIT BUTTON FOR THE MANAGE ASSETS VIEW ===
                const editBtn = document.getElementById('depr-btn-edit');
                if (editBtn) editBtn.style.display = 'none';
                // ==============================================================

                openModal('modal-asset-depr-details');
            } else {
                // Fallback only if the global function isn't loaded
                openAssetDepreciationDetails(parsed);
            }

        } catch (err) {
            console.error('Failed to parse asset row payload:', err, 'raw:', raw);
        }
    }

    // Populate and open the asset-depreciation-details modal (view-only)
    function openAssetDepreciationDetails(row) {
        if (!row) return;
        const container = document.getElementById('asset-depr-detail-content');
        const subtitle = document.getElementById('depr-details-subtitle');
        if (!container || !subtitle) return;

        // Simple formatters local to this module
        const currency = new Intl.NumberFormat('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        const dateFmt = new Intl.DateTimeFormat('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
        
        // ROBUST DATE FORMATTER
        const formatDate = (val) => {
            if (!val || String(val).startsWith('0000-00-00')) return '—';
            const safeVal = String(val).includes(' ') ? val : String(val).replace(/-/g, '/');
            const d = new Date(safeVal);
            return isNaN(d.getTime()) ? '—' : dateFmt.format(d);
        };

        // 1. Extract Depreciation Date safely
        let deprDateStr = row.depreciation_start_date || row.depreciation_start || null;

        // 2. Dynamically Calculate Retirement Date if missing
        let retirementDateStr = row.retirement_date;
        if (!retirementDateStr || String(retirementDateStr).startsWith('0000-00-00')) {
            if (deprDateStr && row.asset_life_months) {
                const safeDepr = String(deprDateStr).includes(' ') ? deprDateStr : String(deprDateStr).replace(/-/g, '/');
                const dDate = new Date(safeDepr);
                
                if (!isNaN(dDate.getTime())) {
                    // Add asset life months to the start date
                    const targetYear = dDate.getFullYear();
                    const targetMonth = dDate.getMonth() + parseInt(row.asset_life_months);
                    
                    // The "0" day of the next month gives us the exact LAST day of the target month
                    const calcRetDate = new Date(targetYear, targetMonth + 1, 0);
                    
                    // Format back to YYYY-MM-DD for the formatter
                    retirementDateStr = `${calcRetDate.getFullYear()}-${String(calcRetDate.getMonth() + 1).padStart(2, '0')}-${String(calcRetDate.getDate()).padStart(2, '0')}`;
                }
            }
        }

        subtitle.textContent = (row.branch_name ? row.branch_name + ' — ' : '') + (row.category_name || '');

        const html = `
            <div class="grid grid-cols-2 gap-6">
                <div class="space-y-2">
                    <div class="text-xs text-slate-500">Code</div>
                    <div class="text-sm font-black text-slate-800">${row.system_asset_code || '—'}</div>
                </div>
                <div class="space-y-2">
                    <div class="text-xs text-slate-500">Reference / Serial No</div>
                    <div class="text-sm text-slate-800">${row.reference_no || '—'}</div>
                </div>
                <div class="space-y-2">
                    <div class="text-xs text-slate-500">Zone</div>
                    <div class="text-sm text-slate-800">${row.zone || '—'}</div>
                </div>
                <div class="space-y-2">
                    <div class="text-xs text-slate-500">Region</div>
                    <div class="text-sm text-slate-800">${row.region || '—'}</div>
                </div>
                <div class="space-y-2">
                    <div class="text-xs text-slate-500">Cost Center</div>
                    <div class="text-sm text-slate-800">${row.cost_center || row.cost_center_code || '—'}</div>
                </div>
                <div class="space-y-2">
                    <div class="text-xs text-slate-500">Branch</div>
                    <div class="text-sm text-slate-800">${row.branch_name || '—'}</div>
                </div>
                <div class="space-y-2">
                    <div class="text-xs text-slate-500">Category</div>
                    <div class="text-sm text-slate-800">${row.category_name || '—'}</div>
                </div>
                <div class="space-y-2">
                    <div class="text-xs text-slate-500">Date Generated</div>
                    <div class="text-sm font-black text-slate-800">${formatDate(row.period_date)}</div>
                </div>
                <div class="col-span-2">
                    <div class="text-xs text-slate-500">Description</div>
                    <div class="text-sm text-slate-800">${row.description || '—'}</div>
                </div>
                <div class="space-y-2">
                    <div class="text-xs text-slate-500">Date Received</div>
                    <div class="text-sm text-slate-800">${formatDate(row.date_received)}</div>
                </div>
                <div class="space-y-2">
                    <div class="text-xs text-slate-500">Depreciation Date</div>
                    <div class="text-sm text-slate-800">${formatDate(deprDateStr)}</div>
                </div>
                <div class="space-y-2">
                    <div class="text-xs text-slate-500">Retirement Date</div>
                    <div class="text-sm text-slate-800">${formatDate(retirementDateStr)}</div>
                </div>
                <div class="space-y-2">
                    <div class="text-xs text-slate-500">Remaining Life (months)</div>
                    <div class="text-sm font-black text-slate-800">${row.remaining_life != null ? row.remaining_life : '—'}</div>
                </div>
                <div class="space-y-2">
                    <div class="text-xs text-slate-500">Acquisition Cost</div>
                    <div class="text-sm font-mono text-slate-800">${currency.format(row.acquisition_cost || 0)}</div>
                </div>
                <div class="space-y-2">
                    <div class="text-xs text-slate-500">Monthly Depreciation</div>
                    <div class="text-sm font-mono text-slate-800">${currency.format(row.monthly_depreciation || row.period_depreciation_expense || 0)}</div>
                </div>
                <div class="space-y-2">
                    <div class="text-xs text-slate-500">Accumulated Depreciation</div>
                    <div class="text-sm font-mono text-slate-800">${currency.format(row.accumulated_depreciation || 0)}</div>
                </div>
                <div class="space-y-2">
                    <div class="text-xs text-slate-500">Book Value</div>
                    <div class="text-sm font-mono text-slate-800">${currency.format(row.book_value || 0)}</div>
                </div>
            </div>
        `;

        container.innerHTML = html;
        openModal('modal-asset-depr-details');
    }
    function renderTable(data, totals) {
        const tbody = document.getElementById('tableBody');
        const wrapper = document.getElementById('tableWrapper');
        const noData = document.getElementById('noDataWrapper');
        const initialState = document.getElementById('initialStateWrapper');

        if (initialState) initialState.classList.add('hidden');

        if (data.length === 0) {
            wrapper.classList.add('hidden');
            noData.classList.remove('hidden');
        } else {
            wrapper.classList.remove('hidden');
            noData.classList.add('hidden');

            const currency = new Intl.NumberFormat('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
            const dateFmt = new Intl.DateTimeFormat('en-US', { month: 'short', day: 'numeric', year: 'numeric' });

            let html = '';
            data.forEach(r => {
                const payload = JSON.stringify(r).replace(/'/g, "&#039;");
                html += `<tr class="asset-row cursor-pointer hover:bg-red-50/40 transition-colors" data-asset='${payload}'>
                    <td class="py-2 pl-5 pr-3 font-mono text-xs text-slate-900">${r.system_asset_code}</td>
                    <td class="py-2 px-3 font-mono text-xs">${r.branch_name}</td>
                    <td class="py-2 px-3 font-mono text-xs">${r.category_name}</td>
                    <td class="py-2 px-3 truncate font-mono text-xs max-w-[200px]" title="${r.description}">${r.description}</td>
                    <td class="py-2 px-3 text-right font-mono text-xs">${currency.format(r.acquisition_cost)}</td>
                    <td class="py-2 px-3 text-right font-mono text-xs text-slate-900">${currency.format(r.period_depreciation_expense)}</td>
                    <td class="py-2 px-3 text-rightfont-mono text-xs">${currency.format(r.accumulated_depreciation)}</td>
                    <td class="py-2 px-3 text-center font-mono text-xs">${r.remaining_life}</td>
                    <td class="py-2 px-3 text-right font-mono text-xs text-slate-900">${currency.format(r.book_value)}</td>
                    <td class="py-2 pl-3 pr-5 text-center text-slate-500 font-mono text-xs">${dateFmt.format(new Date(r.period_date))}</td>
                </tr>`;
            });
            tbody.innerHTML = html;
            // replace previous handler to avoid duplicates
            tbody.onclick = assetRowClickHandler;

            document.getElementById('totCost').innerText = currency.format(totals.cost);
            document.getElementById('totDE').innerText = currency.format(totals.de);
            document.getElementById('totAD').innerText = currency.format(totals.ad);
            document.getElementById('totBV').innerText = currency.format(totals.bv);
        }
    }

    // ─── 4. Export Event Listener ────────────────────────────────────────
    const exportBtn = document.getElementById('exportExcelBtn');
    if (exportBtn) {
        exportBtn.addEventListener('click', function() {
            const params = new URLSearchParams(new FormData(form)).toString();
            window.location.href = `${exportUrl}?${params}`;
        });
    }

    // Attach click handler for server-rendered rows (if any)
    const initialTbody = document.getElementById('tableBody');
    if (initialTbody) {
        initialTbody.addEventListener('click', assetRowClickHandler);
    }

});