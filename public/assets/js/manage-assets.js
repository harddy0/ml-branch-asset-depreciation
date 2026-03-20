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
            if (_suppressChange) return;
            if (tsZone && tsZone.wrapper) tsZone.wrapper.classList.remove('ts-typing-mode');
            // Zone changed: wipe Region and Branch, then fetch
            _resetToPlaceholder(tsRegion);
            _resetToPlaceholder(tsBranch);
            fetchData('zone');
            setTomSelectTitleAndScroll(tsZone, 'Select Zone');
        }
    });

    tsRegion = new TomSelect('#regionSelect', {
        create: false, maxOptions: null, valueField: 'value', labelField: 'text', searchField: 'text', placeholder: 'Select Region', dropdownParent: 'body',
        onChange: function(value) {
            if (_suppressChange) return;
            if (tsRegion && tsRegion.wrapper) tsRegion.wrapper.classList.remove('ts-typing-mode');
            // Region changed: wipe Branch, then fetch
            _resetToPlaceholder(tsBranch);
            fetchData('region');
            setTomSelectTitleAndScroll(tsRegion, 'Select Region');
        }
    });

    tsBranch = new TomSelect('#branchSelect', {
        create: false, maxOptions: null, valueField: 'value', labelField: 'text', searchField: 'text', placeholder: 'Select Branch', dropdownParent: 'body',
        onChange: function() {
            if (_suppressChange) return;
            if (tsBranch && tsBranch.wrapper) tsBranch.wrapper.classList.remove('ts-typing-mode');
            // Branch: nothing to clear above, just fetch
            fetchData('branch');
            setTomSelectTitleAndScroll(tsBranch, 'Select Branch');
        }
    });

    // ─── Deferred-input behavior for TomSelect controls ──────────────────
    let _deferredTS = null;

    function setTypingMode(ts, isTyping) {
        if (!ts || !ts.wrapper) return;
        if (isTyping) {
            ts.wrapper.classList.add('ts-typing-mode');
        } else {
            ts.wrapper.classList.remove('ts-typing-mode');
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
        ts.settings.openOnFocus = false;

        const ctrl = ts.wrapper.querySelector('.ts-control');

        setTypingMode(ts, false);

        ctrl.addEventListener('click', function(e) {
            _deferredTS = ts;
            setTypingMode(ts, false);
            try {
                if (typeof ts.setTextboxValue === 'function') {
                    ts.setTextboxValue('');
                } else if (ts.input) {
                    ts.input.value = '';
                }
            } catch (err) {}
            ts.open();
            try { ctrl.scrollLeft = 0; } catch (err) {}
        });

        const origBlur = ts.onBlur || function() {};
        ts.onBlur = function() {
            setTypingMode(ts, false);
            if (typeof origBlur === 'function') origBlur.apply(this, arguments);
        };
    }

    attachDeferredInput(tsZone);
    attachDeferredInput(tsRegion);
    attachDeferredInput(tsBranch);

    document.addEventListener('keydown', function(e) {
        if (!_deferredTS) return;
        const key = e.key;
        const isPrintable = key.length === 1;
        if (!isPrintable) return;

        e.preventDefault();

        const ts = _deferredTS;
        const input = ts.input;
        if (!input) return;

        setTypingMode(ts, true);
        try { input.value = ''; } catch (err) {}
        input.focus();
        try {
            input.value = key;
            input.setSelectionRange(1, 1);
        } catch (err) {}

        input.dispatchEvent(new Event('input', { bubbles: true }));
        try { ts.open(); } catch (err) {}

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
                        // Repopulate both dependents with zone-filtered lists
                        _rebuildSelect(tsRegion, res.regions,  'Region');
                        _rebuildSelect(tsBranch, res.branches, 'Branch');
                    } else if (source === 'region') {
                        // Repopulate Branch with zone+region-filtered list
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

    // ─── 3. Dropdown Helpers ─────────────────────────────────────────────

    /**
     * Silently reset a TomSelect back to the empty placeholder.
     * Wrapped in _suppressChange so it never triggers onChange.
     */
    function _resetToPlaceholder(instance) {
        _suppressChange = true;
        try {
            instance.setValue('', true);
            setTomSelectTitleAndScroll(instance, instance.settings.placeholder || '');
        } finally {
            _suppressChange = false;
        }
    }

    /**
     * Rebuild a dependent TomSelect's option list after its parent changes.
     * Lands on __ALL__ so data loads immediately for the selected parent scope.
     */
    function _rebuildSelect(instance, optionsData, labelSingular) {
        instance.clearOptions();

        const allLabel = 'All ' + labelSingular + 's';
        instance.addOption({ value: '__ALL__', text: allLabel });

        (optionsData || []).forEach(item => {
            instance.addOption({ value: item, text: item });
        });
        instance.refreshOptions(false);

        instance.setValue('__ALL__', true);
        setTomSelectTitleAndScroll(instance, allLabel);
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
            if (ctrl) ctrl.scrollLeft = 0;
        }
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