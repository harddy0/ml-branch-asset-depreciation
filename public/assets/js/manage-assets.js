document.addEventListener("DOMContentLoaded", function() {

    const form = document.getElementById('filterForm');
    if (!form) return;

    const apiUrl    = form.getAttribute('data-api-url');
    const exportUrl = form.getAttribute('data-export-url');

    let tsZone, tsRegion, tsBranch;

    // Prevents programmatic setValue / clearOptions from firing onChange cascade
    let _suppressChange = false;

    form.addEventListener('submit', (e) => e.preventDefault());

    // ─── 1. Initialize TomSelect ─────────────────────────────────────────
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
        onChange: function() { fetchData('date'); }
    });

    // ─── 2. Fetch ────────────────────────────────────────────────────────
    function fetchData(source) {
        const rawParams   = new URLSearchParams(new FormData(form));
        const cleanParams = new URLSearchParams();
        rawParams.forEach((val, key) => {
            // __ALL__ sentinel → '' (no filter applied for this field)
            // disabled placeholder '' → also '' (same meaning)
            cleanParams.set(key, (val === '__ALL__') ? '' : val);
        });

        const tableWrapper = document.getElementById('tableWrapper');
        if (tableWrapper) tableWrapper.style.opacity = '0.5';

        fetch(`${apiUrl}?${cleanParams.toString()}`)
            .then(r => r.json())
            .then(res => {
                if (!res.success) {
                    console.error('API error:', res.error);
                    return;
                }

                _suppressChange = true;
                try {
                    if (source === 'zone') {
                        // Repopulate Region and Branch with zone-filtered options
                        // Both were already reset to placeholder before the fetch
                        _rebuildSelect(tsRegion, res.regions,  'Region');
                        _rebuildSelect(tsBranch, res.branches, 'Branch');

                    } else if (source === 'region') {
                        // Repopulate Branch with zone+region-filtered options
                        // Branch was already reset to placeholder before the fetch
                        _rebuildSelect(tsBranch, res.branches, 'Branch');

                    }
                    // 'branch' and 'date': no dropdown rebuilds needed
                } finally {
                    _suppressChange = false;
                }

                renderTable(res.data, res.totals);
            })
            .catch(err => console.error('Network Error:', err))
            .finally(() => {
                if (tableWrapper) tableWrapper.style.opacity = '1';
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

    /**
     * Rebuild a dependent TomSelect's option list after a parent changes.
     * Always lands on __ALL__ after rebuild (user must drill down further if wanted).
     */
    function _rebuildSelect(instance, optionsData, labelSingular) {
        // clearOptions + addOption does not fire onChange while _suppressChange is true
        instance.clearOptions();

        const allLabel = '-- All ' + labelSingular + 's --';
        instance.addOption({ value: '__ALL__', text: allLabel });

        (optionsData || []).forEach(item => {
            instance.addOption({ value: item, text: item });
        });
        instance.refreshOptions(false);

        // Land on __ALL__ — shows all options under the selected parent
        instance.setValue('__ALL__', true);
        _updateTitle(instance, allLabel);
    }

    /** Refresh the visible label on a TomSelect control */
    function _updateTitle(instance, fallbackLabel) {
        if (!instance || !instance.wrapper) return;
        const val = instance.getValue();
        let label = fallbackLabel || '';
        if (val && val !== '__ALL__' && val !== '') {
            const opt = instance.options && instance.options[val];
            label = (opt && opt.text) ? opt.text : val;
        } else if (val === '__ALL__') {
            const allOpt = instance.options && instance.options['__ALL__'];
            label = (allOpt && allOpt.text) ? allOpt.text : fallbackLabel || '';
        }
        instance.wrapper.title = label;
        const ctrl = instance.wrapper.querySelector('.ts-control');
        if (ctrl) ctrl.scrollLeft = ctrl.scrollWidth;
    }

    // ─── 4. Table Renderer ───────────────────────────────────────────────
    function renderTable(data, totals) {
        const tbody        = document.getElementById('tableBody');
        const wrapper      = document.getElementById('tableWrapper');
        const noData       = document.getElementById('noDataWrapper');
        const initialState = document.getElementById('initialStateWrapper');

        if (initialState) initialState.classList.add('hidden');

        if (!data || data.length === 0) {
            wrapper.classList.add('hidden');
            noData.classList.remove('hidden');
        } else {
            wrapper.classList.remove('hidden');
            noData.classList.add('hidden');

            const currency = new Intl.NumberFormat('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
            const dateFmt  = new Intl.DateTimeFormat('en-US', { month: 'short', day: 'numeric', year: 'numeric' });

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
                    <td class="py-2 px-3 text-right font-mono text-xs">${currency.format(r.accumulated_depreciation)}</td>
                    <td class="py-2 px-3 text-center font-mono text-xs">${r.remaining_life}</td>
                    <td class="py-2 px-3 text-right font-mono text-xs text-slate-900">${currency.format(r.book_value)}</td>
                    <td class="py-2 pl-3 pr-5 text-center text-slate-500 font-mono text-xs">${dateFmt.format(new Date(r.period_date))}</td>
                </tr>`;
            });
            tbody.innerHTML = html;
            tbody.onclick = assetRowClickHandler;

            document.getElementById('totCost').innerText = currency.format(totals.cost);
            document.getElementById('totDE').innerText   = currency.format(totals.de);
            document.getElementById('totAD').innerText   = currency.format(totals.ad);
            document.getElementById('totBV').innerText   = currency.format(totals.bv);
        }
    }

    // ─── 5. Row Click → Detail Modal ────────────────────────────────────
    function assetRowClickHandler(e) {
        const tr = e.target.closest && e.target.closest('tr.asset-row');
        if (!tr) return;

        let raw = tr.dataset && tr.dataset.asset ? tr.dataset.asset : tr.getAttribute('data-asset');
        if (!raw) return;

        try {
            const decoded = raw
                .replace(/&quot;/g, '"')
                .replace(/&#039;/g, "'")
                .replace(/&amp;/g, '&')
                .replace(/&lt;/g, '<')
                .replace(/&gt;/g, '>');
            let parsed = JSON.parse(decoded);

            parsed.depreciation_start   = parsed.depreciation_start_date || parsed.depreciation_start;
            parsed.monthly_depreciation = parsed.period_depreciation_expense || parsed.monthly_depreciation;

            if (!parsed.retirement_date || String(parsed.retirement_date).startsWith('0000-00-00')) {
                if (parsed.depreciation_start && parsed.asset_life_months) {
                    const safeDepr = String(parsed.depreciation_start).replace(/-/g, '/');
                    const dDate    = new Date(safeDepr);
                    if (!isNaN(dDate.getTime())) {
                        const targetMonth = dDate.getMonth() + parseInt(parsed.asset_life_months);
                        const calcRet     = new Date(dDate.getFullYear(), targetMonth + 1, 0);
                        parsed.retirement_date = `${calcRet.getFullYear()}-${String(calcRet.getMonth()+1).padStart(2,'0')}-${String(calcRet.getDate()).padStart(2,'0')}`;
                    }
                }
            }

            if (typeof renderDeprDetails === 'function') {
                renderDeprDetails(parsed, false);
                if (typeof setDeprEditMode === 'function') setDeprEditMode(false);
                const editBtn = document.getElementById('depr-btn-edit');
                if (editBtn) editBtn.style.display = 'none';
                openModal('modal-asset-depr-details');
            }
        } catch (err) {
            console.error('Failed to parse asset row:', err);
        }
    }

    // ─── 6. Export ───────────────────────────────────────────────────────
    const exportBtn = document.getElementById('exportExcelBtn');
    if (exportBtn) {
        exportBtn.addEventListener('click', function() {
            const rawParams   = new URLSearchParams(new FormData(form));
            const cleanParams = new URLSearchParams();
            rawParams.forEach((val, key) => {
                cleanParams.set(key, (val === '__ALL__') ? '' : val);
            });
            window.location.href = `${exportUrl}?${cleanParams.toString()}`;
        });
    }

    const initialTbody = document.getElementById('tableBody');
    if (initialTbody) initialTbody.addEventListener('click', assetRowClickHandler);

});