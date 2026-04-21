document.addEventListener("DOMContentLoaded", function() {

    const form = document.getElementById('filterForm');
    if (!form) return;

    const apiUrl    = form.getAttribute('data-api-url');
    const exportUrl = form.getAttribute('data-export-url');
    const generatedBy = (form.getAttribute('data-generated-by') || 'User').toUpperCase();

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

    const asOfInput = form.querySelector('input[name="as_of_date"]');
    if (asOfInput && asOfInput.value) {
        fetchData('init');
    }

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

        function _pluralize(label) {
            if (/ch$/i.test(label)) return label + 'es';
            if (/y$/i.test(label)) return label.replace(/y$/i, 'ies');
            if (/s$/i.test(label)) return label + 'es';
            return label + 's';
        }

        const allLabel = 'All ' + _pluralize(labelSingular);
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
            setExportAvailability(false);
        } else {
            wrapper.classList.remove('hidden');
            noData.classList.add('hidden');
            setExportAvailability(true);

            const currency = new Intl.NumberFormat('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
            const dateFmt  = new Intl.DateTimeFormat('en-US', { month: 'short', day: 'numeric', year: 'numeric' });

            let html = '';
            data.forEach(r => {
                const payload = JSON.stringify(r).replace(/'/g, "&#039;");
                html += `<tr class="asset-row cursor-pointer hover:bg-red-50/40 transition-colors" data-asset='${payload}'>
                    <td class="py-2 pl-5 pr-3 font-mono text-xs text-slate-900">${r.system_asset_code}</td>
                    <td class="py-2 px-3 font-mono text-xs">${r.branch_name}</td>
                    <td class="py-2 px-3 font-mono text-xs">${r.group_code || ''}</td>
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

            parsed.category_name = parsed.category_name || parsed.group_code || '';
            parsed.category_code = parsed.category_code || parsed.group_code || '';
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

    // ─── 6. Export / Print ──────────────────────────────────────────────
    function setExportAvailability(hasData) {
        const exportToggleBtn = document.getElementById('exportToggleBtn');
        const excelBtn = document.getElementById('exportExcelBtn');
        const printBtn = document.getElementById('exportPrintBtn');
        const exportMenu = document.getElementById('exportMenu');
        const exportDisabledTooltip = document.getElementById('exportDisabledTooltip');

        if (!exportToggleBtn || !excelBtn || !printBtn) return;

        exportToggleBtn.disabled = !hasData;
        excelBtn.disabled = !hasData;
        printBtn.disabled = !hasData;

        exportToggleBtn.classList.toggle('opacity-50', !hasData);
        exportToggleBtn.classList.toggle('cursor-not-allowed', !hasData);
        excelBtn.classList.toggle('opacity-50', !hasData);
        excelBtn.classList.toggle('cursor-not-allowed', !hasData);
        printBtn.classList.toggle('opacity-50', !hasData);
        printBtn.classList.toggle('cursor-not-allowed', !hasData);

        if (!hasData && exportMenu) {
            exportMenu.classList.add('hidden');
            exportToggleBtn.setAttribute('aria-expanded', 'false');
        }

        if (hasData && exportDisabledTooltip) {
            exportDisabledTooltip.classList.add('hidden');
        }
    }

    // Toggle dropdown
    const exportToggleBtn = document.getElementById('exportToggleBtn');
    const exportMenu = document.getElementById('exportMenu');
    const exportContainer = exportToggleBtn ? exportToggleBtn.closest('.relative') : null;
    let exportDisabledTooltip = null;

    if (exportContainer) {
        exportDisabledTooltip = document.createElement('div');
        exportDisabledTooltip.id = 'exportDisabledTooltip';
        exportDisabledTooltip.className = 'hidden absolute top-full right-0 mt-2 px-3 py-1.5 rounded-md border border-slate-200 bg-slate-800 text-white text-[11px] font-mono shadow-lg pointer-events-none z-50 whitespace-nowrap';
        exportDisabledTooltip.textContent = 'Table is empty.';
        exportContainer.appendChild(exportDisabledTooltip);
    }

    function toggleExportDisabledTooltip(show) {
        if (!exportDisabledTooltip || !exportToggleBtn || !exportToggleBtn.disabled) return;
        exportDisabledTooltip.classList.toggle('hidden', !show);
    }

    if (exportToggleBtn && exportMenu) {
        exportToggleBtn.addEventListener('click', function(e) {
            if (exportToggleBtn.disabled) return;
            const hidden = exportMenu.classList.contains('hidden');
            exportMenu.classList.toggle('hidden', !hidden);
            exportToggleBtn.setAttribute('aria-expanded', String(hidden));
        });

        exportToggleBtn.addEventListener('mouseenter', function() {
            toggleExportDisabledTooltip(true);
        });

        exportToggleBtn.addEventListener('mouseleave', function() {
            if (exportDisabledTooltip) exportDisabledTooltip.classList.add('hidden');
        });

        exportToggleBtn.addEventListener('focus', function() {
            toggleExportDisabledTooltip(true);
        });

        exportToggleBtn.addEventListener('blur', function() {
            if (exportDisabledTooltip) exportDisabledTooltip.classList.add('hidden');
        });

        if (exportContainer) {
            exportContainer.addEventListener('mouseleave', function() {
                if (exportDisabledTooltip) exportDisabledTooltip.classList.add('hidden');
            });
        }

        // close on outside click
        document.addEventListener('click', function(e) {
            if (!exportMenu || !exportToggleBtn) return;
            if (!exportMenu.classList.contains('hidden')) {
                if (!exportMenu.contains(e.target) && !exportToggleBtn.contains(e.target)) {
                    exportMenu.classList.add('hidden');
                    exportToggleBtn.setAttribute('aria-expanded', 'false');
                }
            }
            if (exportDisabledTooltip && !exportContainer?.contains(e.target)) {
                exportDisabledTooltip.classList.add('hidden');
            }
        });
    }

    const excelBtn = document.getElementById('exportExcelBtn');
    if (excelBtn) {
        excelBtn.addEventListener('click', function() {
            if (excelBtn.disabled) return;
            const rawParams   = new URLSearchParams(new FormData(form));
            const cleanParams = new URLSearchParams();
            rawParams.forEach((val, key) => {
                cleanParams.set(key, (val === '__ALL__') ? '' : val);
            });
            // navigate to export URL (existing behavior)
            window.location.href = `${exportUrl}?${cleanParams.toString()}`;
        });
    }

    const printBtn = document.getElementById('exportPrintBtn');
    if (printBtn) {
        printBtn.addEventListener('click', function() {
            if (printBtn.disabled) return;
            // create printable window using current table HTML
            const tableWrapper = document.getElementById('tableWrapper');
            if (!tableWrapper) {
                alert('No data to print.');
                return;
            }

            const printWin = window.open('', '_blank');
            if (!printWin) {
                alert('Popup blocked. Please allow popups to print.');
                return;
            }

            const style = `
                <style>
                    @page { size: A4 landscape; }
                    body { font-family: Arial, Helvetica, sans-serif; padding: 12px; color: #1e293b }
                    table { border-collapse: collapse; width: 100%; }
                    th, td { border: 1px solid #ddd; padding: 8px; font-size: 12px; }
                    th { background: #ce2216; color: #fff; font-weight: 700; }
                    .print-totals { width: 620px; max-width: 100%; margin: 12px auto 0; table-layout: fixed; border-collapse: collapse; }
                    .print-totals td { border: 1px solid #c9cccf; padding: 4px 8px; text-align: right; white-space: nowrap; }
                    .print-totals .amount-wrap { display:flex; justify-content:space-between; align-items:center; }
                    .print-meta {
                        margin-top: 10px;
                        font-size: 11px;
                        color: #475569;
                        line-height: 1.35;
                        page-break-inside: avoid;
                    }
                    [name="logo-container"] {
                        display: flex;
                        width: 100%;
                        align-items: center;
                        justify-content: center;
                        gap: 24px;
                        background: #fff;
                        border-bottom: 1px solid #e2e8f0;
                        padding: 8px 12px;
                        margin-bottom: 10px;
                    }
                    [name="logo-container"] img { height: 28px; }
                    [name="logo-container"] > div { display:flex; align-items:center; justify-content:center; border-right:1px solid #e2e8f0; padding-right:16px; }
                    [name="logo-container"] > span { font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, 'Roboto Mono', 'Courier New', monospace; letter-spacing: .08em; font-size: 18px; color:#94a3b8; }
                </style>`;

            // Clone the visible main table (exclude surrounding totals block)
            const tableEl = tableWrapper.querySelector('table');
            const tableHtml = tableEl ? tableEl.outerHTML : tableWrapper.innerHTML;

            // Read totals values from the page (these are the numeric formatted strings)
            const totCost = document.getElementById('totCost') ? document.getElementById('totCost').innerText : '';
            const totDE   = document.getElementById('totDE')   ? document.getElementById('totDE').innerText   : '';
            const totAD   = document.getElementById('totAD')   ? document.getElementById('totAD').innerText   : '';
            const totBV   = document.getElementById('totBV')   ? document.getElementById('totBV').innerText   : '';
            const generatedAt = new Intl.DateTimeFormat('en-US', {
                month: 'short',
                day: 'numeric',
                year: 'numeric',
                hour: 'numeric',
                minute: '2-digit'
            }).format(new Date());

            const totalsHtml = `
                <table class="print-totals">
                    <colgroup>
                        <col style="width:22%;">
                        <col style="width:28%;">
                        <col style="width:30%;">
                        <col style="width:22%;">
                    </colgroup>
                    <tbody>
                        <tr>
                            <td colspan="4" style="font-weight:700; text-align:center;">Grand Total</td>
                        </tr>
                        <tr>
                            <td style="font-weight:700; text-align:right;">Cost</td>
                            <td style="font-weight:700; text-align:right;">Monthly Depreciation</td>
                            <td style="font-weight:700; text-align:right;">Accumulated Depreciation</td>
                            <td style="font-weight:700; text-align:right;">Book Value</td>
                        </tr>
                        <tr>
                            <td><div class="amount-wrap"><span>₱</span><span>${totCost}</span></div></td>
                            <td><div class="amount-wrap"><span>₱</span><span>${totDE}</span></div></td>
                            <td><div class="amount-wrap"><span>₱</span><span>${totAD}</span></div></td>
                            <td><div class="amount-wrap"><span>₱</span><span>${totBV}</span></div></td>
                        </tr>
                    </tbody>
                </table>`;

            const headerTemplate = document.getElementById('exportHeaderTemplate');
            const headerHtml = headerTemplate ? headerTemplate.innerHTML : '';

            printWin.document.open();
            printWin.document.write(`<!doctype html><html><head><meta charset="utf-8"><title>Print - Assets</title>${style}</head><body>${headerHtml}${tableHtml}${totalsHtml}<div class="print-meta">Generated by: ${generatedBy}<br>Generated on: ${generatedAt}</div></body></html>`);
            printWin.document.close();

            // give time to render then print
            printWin.focus();
            setTimeout(() => {
                try { printWin.print(); } catch (e) { console.error(e); }
            }, 500);
        });
    }

    const initialTbody = document.getElementById('tableBody');
    if (initialTbody) initialTbody.addEventListener('click', assetRowClickHandler);

    // Initial enablement state: export only when data table is visible and has rows.
    const initialHasData = (() => {
        const wrapper = document.getElementById('tableWrapper');
        const tbody = document.getElementById('tableBody');
        if (!wrapper || wrapper.classList.contains('hidden') || !tbody) return false;
        return tbody.querySelectorAll('tr').length > 0;
    })();
    setExportAvailability(initialHasData);

});