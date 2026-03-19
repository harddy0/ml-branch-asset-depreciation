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
            tsRegion.clear(true); 
            tsBranch.clear(true);
            fetchData('zone');
        }
    });

    tsRegion = new TomSelect('#regionSelect', {
        create: false, maxOptions: null, valueField: 'value', labelField: 'text', searchField: 'text',
        onChange: function(value) {
            tsBranch.clear(true);
            fetchData('region');
        }
    });

    tsBranch = new TomSelect('#branchSelect', {
        create: false, maxOptions: null, valueField: 'value', labelField: 'text', searchField: 'text',
        onChange: function() {
            fetchData('branch');
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
        instance.clearOptions();
        instance.addOption({value: '', text: '-- All ' + labelPlural + ' --'});
        optionsData.forEach(item => {
            instance.addOption({value: item, text: item});
        });
        instance.refreshOptions(false);
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
                html += `<tr class="hover:bg-red-50/40 transition-colors">
                    <td class="py-2 pl-5 pr-3 font-semibold text-slate-900">${r.system_asset_code}</td>
                    <td class="py-2 px-3">${r.branch_name}</td>
                    <td class="py-2 px-3 text-xs">${r.category_name}</td>
                    <td class="py-2 px-3 truncate max-w-[200px]" title="${r.description}">${r.description}</td>
                    <td class="py-2 px-3 text-right font-mono">${currency.format(r.acquisition_cost)}</td>
                    <td class="py-2 px-3 text-right font-mono text-red-600">${currency.format(r.period_depreciation_expense)}</td>
                    <td class="py-2 px-3 text-right font-mono">${currency.format(r.accumulated_depreciation)}</td>
                    <td class="py-2 px-3 text-center font-bold">${r.remaining_life}</td>
                    <td class="py-2 px-3 text-right font-mono font-bold text-slate-900">${currency.format(r.book_value)}</td>
                    <td class="py-2 pl-3 pr-5 text-center text-slate-500 text-xs">${dateFmt.format(new Date(r.period_date))}</td>
                </tr>`;
            });
            tbody.innerHTML = html;

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

});