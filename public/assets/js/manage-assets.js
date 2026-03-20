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
        create: false, maxOptions: null, valueField: 'value', labelField: 'text', searchField: 'text',
        onChange: function(value) {
            fetchData('zone');
            setTomSelectTitleAndScroll(tsZone, '-- All Zones --');
        }
    });

    tsRegion = new TomSelect('#regionSelect', {
        create: false, maxOptions: null, valueField: 'value', labelField: 'text', searchField: 'text',
        onChange: function(value) {
            fetchData('region');
            setTomSelectTitleAndScroll(tsRegion, '-- All Regions --');
        }
    });

    tsBranch = new TomSelect('#branchSelect', {
        create: false, maxOptions: null, valueField: 'value', labelField: 'text', searchField: 'text',
        onChange: function() {
            fetchData('branch');
            setTomSelectTitleAndScroll(tsBranch, '-- All Branches --');
        }
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
        const defaultLabel = '-- All ' + labelPlural + ' --';
        instance.addOption({value: '', text: defaultLabel});
        optionsData.forEach(item => {
            instance.addOption({value: item, text: item});
        });
        instance.refreshOptions(false);

        // restore previous selection when still available, otherwise reset to default
        if (current && optionsData.includes(current)) {
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
                // allow horizontal scroll to reveal full text
                ctrl.scrollLeft = ctrl.scrollWidth;
            }
        }
    }

    // Click handler for table rows — parses embedded row data and opens modal
    function assetRowClickHandler(e) {
        const tr = e.target.closest && e.target.closest('tr.asset-row');
        if (!tr) return;

        // Prefer dataset (browser-decoded) value when available
        let raw = tr.dataset && tr.dataset.asset ? tr.dataset.asset : tr.getAttribute('data-asset');
        if (!raw) return;
        try {
            // First attempt: parse directly (dataset usually provides decoded JSON)
            const parsed = JSON.parse(raw);
            const code = parsed.system_asset_code || parsed.system_asset_code?.toString();
            if (code) {
                // prefer fetching by numeric id if available on parsed payload
                const id = parsed.id || parsed.asset_id || null;
                const endpoint = id
                    ? `${BASE_URL}/public/api/get_asset_by_id.php?id=${encodeURIComponent(id)}`
                    : `${BASE_URL}/public/api/get_asset_details.php?code=${encodeURIComponent(code)}`;

                fetch(endpoint)
                    .then(r => r.text())
                    .then(text => {
                        let res;
                        try {
                            res = JSON.parse(text);
                        } catch (e) {
                            console.error('Asset details fetch returned invalid JSON:', text);
                            // fallback to parsed payload
                            res = null;
                        }

                        if (res && res.success && res.row) {
                            const full = res.row;
                            if (typeof renderDeprDetails === 'function') {
                                renderDeprDetails(full, false);
                                if (typeof setDeprEditMode === 'function') setDeprEditMode(false);
                                openModal('modal-asset-depr-details');
                            } else {
                                openAssetDepreciationDetails(full);
                            }
                        } else {
                            // fallback to parsed payload
                            if (typeof renderDeprDetails === 'function') {
                                renderDeprDetails(parsed, false);
                                if (typeof setDeprEditMode === 'function') setDeprEditMode(false);
                                openModal('modal-asset-depr-details');
                            } else {
                                openAssetDepreciationDetails(parsed);
                            }
                        }
                    })
                    .catch(err => {
                        console.error('Asset details fetch error', err);
                        if (typeof renderDeprDetails === 'function') {
                            renderDeprDetails(parsed, false);
                            if (typeof setDeprEditMode === 'function') setDeprEditMode(false);
                            openModal('modal-asset-depr-details');
                        } else {
                            openAssetDepreciationDetails(parsed);
                        }
                    });
                return;
            } else {
                // no code — use parsed
                const row = parsed;
                if (typeof renderDeprDetails === 'function') {
                    renderDeprDetails(row, false);
                    if (typeof setDeprEditMode === 'function') setDeprEditMode(false);
                    openModal('modal-asset-depr-details');
                } else {
                    openAssetDepreciationDetails(row);
                }
                return;
            }
        } catch (errDirect) {
            try {
                // Fallback: decode common HTML entities then parse
                const decoded = raw.replace(/&quot;/g, '"').replace(/&#039;/g, "'").replace(/&amp;/g, '&').replace(/&lt;/g, '<').replace(/&gt;/g, '>');
                const parsed = JSON.parse(decoded);
                const code = parsed.system_asset_code || parsed.system_asset_code?.toString();
                    if (code) {
                    const id = parsed.id || parsed.asset_id || null;
                    const endpoint = id
                        ? `${BASE_URL}/public/api/get_asset_by_id.php?id=${encodeURIComponent(id)}`
                        : `${BASE_URL}/public/api/get_asset_details.php?code=${encodeURIComponent(code)}`;

                    fetch(endpoint)
                        .then(r => r.text())
                        .then(text => {
                            let res;
                            try { res = JSON.parse(text); } catch (e) {
                                console.error('Asset details fetch returned invalid JSON:', text);
                                res = null;
                            }
                            const full = (res && res.success && res.row) ? res.row : parsed;
                            if (typeof renderDeprDetails === 'function') {
                                renderDeprDetails(full, false);
                                if (typeof setDeprEditMode === 'function') setDeprEditMode(false);
                                openModal('modal-asset-depr-details');
                            } else {
                                openAssetDepreciationDetails(full);
                            }
                        })
                        .catch(err => {
                            console.error('Asset details fetch error', err);
                            if (typeof renderDeprDetails === 'function') {
                                renderDeprDetails(parsed, false);
                                if (typeof setDeprEditMode === 'function') setDeprEditMode(false);
                                openModal('modal-asset-depr-details');
                            } else {
                                openAssetDepreciationDetails(parsed);
                            }
                        });
                    return;
                }
                return;
            } catch (errFallback) {
                console.error('Failed to parse asset row payload (direct):', errDirect, ' fallback:', errFallback, 'raw:', raw);
            }
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

        subtitle.textContent = (row.branch_name ? row.branch_name + ' — ' : '') + (row.category_name || '');

        const html = `
            <div class="grid grid-cols-2 gap-6">
                <div class="space-y-2">
                    <div class="text-xs text-slate-500">Code</div>
                    <div class="text-sm font-black text-slate-800">${row.system_asset_code || '—'}</div>
                </div>
                <div class="space-y-2">
                    <div class="text-xs text-slate-500">Date Generated</div>
                    <div class="text-sm font-black text-slate-800">${row.period_date ? dateFmt.format(new Date(row.period_date)) : '—'}</div>
                </div>
                <div class="space-y-2">
                    <div class="text-xs text-slate-500">Branch</div>
                    <div class="text-sm text-slate-800">${row.branch_name || '—'}</div>
                </div>
                <div class="space-y-2">
                    <div class="text-xs text-slate-500">Category</div>
                    <div class="text-sm text-slate-800">${row.category_name || '—'}</div>
                </div>
                <div class="col-span-2">
                    <div class="text-xs text-slate-500">Description</div>
                    <div class="text-sm text-slate-800">${row.description || '—'}</div>
                </div>
                <div class="space-y-2">
                    <div class="text-xs text-slate-500">Acquisition Cost</div>
                    <div class="text-sm font-mono text-slate-800">${currency.format(row.acquisition_cost)}</div>
                </div>
                <div class="space-y-2">
                    <div class="text-xs text-slate-500">Period Depreciation</div>
                    <div class="text-sm font-mono text-red-600">${currency.format(row.period_depreciation_expense)}</div>
                </div>
                <div class="space-y-2">
                    <div class="text-xs text-slate-500">Accumulated Depreciation</div>
                    <div class="text-sm font-mono text-slate-800">${currency.format(row.accumulated_depreciation)}</div>
                </div>
                <div class="space-y-2">
                    <div class="text-xs text-slate-500">Remaining Life (months)</div>
                    <div class="text-sm font-black text-slate-800">${row.remaining_life != null ? row.remaining_life : '—'}</div>
                </div>
                <div class="space-y-2">
                    <div class="text-xs text-slate-500">Book Value</div>
                    <div class="text-sm font-mono text-slate-800">${currency.format(row.book_value)}</div>
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