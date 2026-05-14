// issuance-report.js - Dynamic loading with proper pagination

document.addEventListener('DOMContentLoaded', function() {
    
    // ==========================================
    // STATE MANAGEMENT
    // ==========================================
    let currentPage = 1;
    let totalPages = 1;
    let isLoading = false;
    let currentFilters = {
        search: '',
        zone: '',
        region: '',
        branch_name: '',
        product_category: '',
        date_from: '',
        date_to: ''
    };
    let currentSort = { by: 'date_issued', dir: 'DESC' };
    let filterOptionsLoaded = false;
    let currentRows = [];
    
    // API URL
    const apiUrl = '/ml-branch-asset-depreciation/public/api/get_issuance_report.php';
    const exportUrl = '/ml-branch-asset-depreciation/public/actions/export_issuance_report.php';
    
    // DOM Elements
    const tableBody = document.getElementById('issuance-table-body');
    const tableWrapper = document.getElementById('tableWrapper');
    const initialStateWrapper = document.getElementById('initialStateWrapper');
    const noDataWrapper = document.getElementById('noDataWrapper');
    const paginationContainer = document.getElementById('pagination-container');
    const totalRecordsEl = document.getElementById('total-records');
    const totalQuantityEl = document.getElementById('total-quantity');
    const totalAmountEl = document.getElementById('total-amount');
    
    // Filter Elements
    const searchInput = document.getElementById('search-input');
    const zoneSelect = document.getElementById('zoneSelect');
    const regionSelect = document.getElementById('regionSelect');
    const branchSelect = document.getElementById('branchSelect');
    const categorySelect = document.getElementById('categorySelect');
    const zoneSuggestions = document.getElementById('zoneSuggestions');
    const regionSuggestions = document.getElementById('regionSuggestions');
    const branchSuggestions = document.getElementById('branchSuggestions');
    const categorySuggestions = document.getElementById('categorySuggestions');
    const dateFromInput = document.getElementById('issuance-date-from');
    const dateToInput = document.getElementById('issuance-date-to');
    const clearBtn = document.getElementById('clearFiltersBtn');
    
    // Sort buttons
    const sortButtons = document.querySelectorAll('.issuance-sort');

    if (tableBody) {
        tableBody.addEventListener('click', function (e) {
            const rowEl = e.target && e.target.closest ? e.target.closest('tr.issuance-row') : null;
            if (!rowEl) return;
            const idx = parseInt(rowEl.dataset.index || '', 10);
            if (Number.isNaN(idx) || !currentRows[idx]) return;
            openIssuanceReportDetails(currentRows[idx]);
        });
    }
    
    // ==========================================
    // UTILITY FUNCTIONS
    // ==========================================
    
    function showLoading() {
        isLoading = true;
        if (tableBody) {
            tableBody.innerHTML = `
                <tr>
                    <td colspan="14" class="text-center py-8">
                        <div class="inline-flex items-center gap-2">
                            <svg class="animate-spin h-5 w-5 text-red-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            <span class="text-sm text-slate-500">Loading...</span>
                        </div>
                    </td>
                </tr>
            `;
        }
    }
    
    function hideLoading() {
        isLoading = false;
    }
    
    function formatCurrency(value) {
        return new Intl.NumberFormat('en-US', {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2
        }).format(parseFloat(value) || 0);
    }
    
    function formatDate(value) {
        if (!value) return '-';
        try {
            const d = new Date(value);
            if (isNaN(d.getTime())) return value;
            return d.toLocaleDateString('en-US', {
                month: 'short',
                day: 'numeric',
                year: 'numeric'
            });
        } catch(e) {
            return value;
        }
    }
    
    function escapeHtml(str) {
        if (!str) return '';
        return String(str)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#39;');
    }
    
    // ==========================================
    // DATA FETCHING
    // ==========================================
    
    function buildQueryString() {
        const params = new URLSearchParams();
        params.set('page', currentPage);
        params.set('per_page', 50);
        params.set('sort_by', currentSort.by);
        params.set('sort_dir', currentSort.dir);
        
        if (currentFilters.search) params.set('search', currentFilters.search);
        if (currentFilters.zone) params.set('zone', currentFilters.zone);
        if (currentFilters.region) params.set('region', currentFilters.region);
        if (currentFilters.branch_name) params.set('branch_name', currentFilters.branch_name);
        if (currentFilters.product_category) params.set('product_category', currentFilters.product_category);
        if (currentFilters.date_from) params.set('date_from', currentFilters.date_from);
        if (currentFilters.date_to) params.set('date_to', currentFilters.date_to);
        
        return params.toString();
    }
    
    async function loadFilterOptions() {
        if (filterOptionsLoaded) return;
        
        try {
            // Fetch just one record to get options
            const response = await fetch(`${apiUrl}?page=1&per_page=1`);
            const data = await response.json();
            
            if (data.success && data.options) {
                const buildOptions = (items, allLabel) => {
                    const list = Array.isArray(items) ? [...items] : [];
                    if (allLabel && !list.some((opt) => String(opt).toLowerCase() === allLabel.toLowerCase())) {
                        list.unshift(allLabel);
                    }
                    return list;
                };

                const setSuggestions = (target, items, allLabel) => {
                    if (!target) return;
                    target.dataset.options = JSON.stringify(buildOptions(items, allLabel));
                };

                setSuggestions(zoneSuggestions, data.options.zones, 'All Zones');
                setSuggestions(regionSuggestions, data.options.regions, 'All Regions');
                setSuggestions(branchSuggestions, data.options.branches, null);
                setSuggestions(categorySuggestions, data.options.categories, 'All Categories');
                
                filterOptionsLoaded = true;
            }
        } catch (err) {
            console.error('Failed to load filter options:', err);
        }
    }
    
    async function fetchData() {
        if (isLoading) return;
        
        showLoading();
        
        // Hide initial state if we have filters
        const hasFilters = Object.values(currentFilters).some(v => v && v !== '');
        
        try {
            const response = await fetch(`${apiUrl}?${buildQueryString()}`);
            const data = await response.json();
            
            if (!data.success) {
                throw new Error(data.error || 'Failed to fetch data');
            }
            
            // Update UI states
            if (initialStateWrapper) {
                initialStateWrapper.classList.add('hidden');
            }
            
            const hasData = data.data && data.data.length > 0;
            
            if (tableWrapper) {
                tableWrapper.classList.remove('hidden');
            }
            
            if (noDataWrapper) {
                if (!hasData && hasFilters) {
                    noDataWrapper.classList.remove('hidden');
                } else {
                    noDataWrapper.classList.add('hidden');
                }
            }
            
            if (hasData) {
                renderTable(data.data);
                renderPagination(data.pagination);
                updateTotals(data.totals);
            } else {
                if (tableBody) {
                    tableBody.innerHTML = `
                        <tr>
                            <td colspan="14" class="text-center py-20 text-slate-400">
                                <div class="flex flex-col items-center justify-center">
                                    <svg class="w-16 h-16 text-slate-300 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"/>
                                    </svg>
                                    <p class="text-slate-500 font-bold text-base mb-1">No records found</p>
                                    <p class="text-slate-400 text-sm">Try adjusting your filters</p>
                                </div>
                            </td>
                        </tr>
                    `;
                }
                if (paginationContainer) paginationContainer.innerHTML = '';
                resetTotals();
            }
            
            updateSortIndicators();
            
        } catch (err) {
            console.error('Fetch error:', err);
            if (tableBody) {
                tableBody.innerHTML = `
                    <tr>
                        <td colspan="14" class="text-center py-8 text-red-600">
                            Error loading data: ${err.message}
                        </td>
                    </tr>
                `;
            }
        } finally {
            hideLoading();
        }
    }
    
    // ==========================================
    // TABLE RENDERING
    // ==========================================
    
    function renderTable(data) {
    if (!tableBody) return;
    
    if (!data || data.length === 0) {
        currentRows = [];
        tableBody.innerHTML = `
            <tr>
                <td colspan="14" class="text-center py-20 text-slate-400">
                    <div class="flex flex-col items-center justify-center">
                        <svg class="w-16 h-16 text-slate-300 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"/>
                        </svg>
                        <p class="text-slate-500 font-bold text-base mb-1">No records found</p>
                        <p class="text-slate-400 text-sm">Try adjusting your filters</p>
                    </div>
                </td>
            </tr>
        `;
        return;
    }
    
    currentRows = Array.isArray(data) ? data : [];

    const html = currentRows.map((row, index) => {
        // Format date safely
        let formattedDate = '-';
        if (row.date_issued) {
            try {
                const d = new Date(row.date_issued);
                if (!isNaN(d.getTime())) {
                    formattedDate = d.toLocaleDateString('en-US', {
                        month: 'short', day: 'numeric', year: 'numeric'
                    });
                }
            } catch(e) { formattedDate = row.date_issued; }
        }
        
        // Format currency
        const formatMoney = (val) => {
            const num = parseFloat(val) || 0;
            return num.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        };
        
        // Escape and truncate long text
        const truncate = (str, maxLen = 60) => {
            if (!str) return '-';
            const escaped = String(str).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
            return escaped.length > maxLen ? escaped.substring(0, maxLen) + '...' : escaped;
        };
        
        return `
            <tr class="issuance-row hover:bg-slate-50 transition-colors cursor-pointer" data-index="${index}">
                <td class="px-3 py-2 text-xs text-slate-700 whitespace-nowrap">${escapeHtml(formattedDate)}</td>
                <td class="px-3 py-2 text-xs font-mono font-semibold text-slate-800 whitespace-nowrap">${escapeHtml(row.issuance_number || '-')}</td>
                <td class="px-3 py-2 text-xs font-mono uppercase text-slate-600 whitespace-nowrap">${escapeHtml(row.item_code || '-')}</td>
                <td class="px-3 py-2 text-xs text-slate-700 truncate-cell">${escapeHtml(row.item_description || '-')}</td>
                <td class="px-3 py-2 text-xs font-mono text-right text-slate-700 whitespace-nowrap">${parseInt(row.quantity || 0).toLocaleString()}</td>
                <td class="px-3 py-2 text-xs font-mono text-slate-600 whitespace-nowrap">${escapeHtml(row.uom || '-')}</td>
                <td class="px-3 py-2 text-xs font-mono text-slate-700 whitespace-nowrap">${escapeHtml(row.cost_center_raw || '-')}</td>
                <td class="px-3 py-2 text-xs font-mono text-right text-slate-700 whitespace-nowrap">₱ ${formatMoney(row.unit_cost)}</td>
                <td class="px-3 py-2 text-xs font-mono text-right font-semibold text-slate-800 whitespace-nowrap">₱ ${formatMoney(row.total_amount)}</td>
                <td class="px-3 py-2 text-xs text-slate-600 truncate-cell">${escapeHtml(row.description_remarks || '-')}</td>
                <td class="px-3 py-2 text-xs font-mono text-slate-700 whitespace-nowrap">${escapeHtml(row.product_category || '-')}</td>
                <td class="px-3 py-2 text-xs font-mono text-slate-600 whitespace-nowrap">${escapeHtml(row.zone || '-')}</td>
                <td class="px-3 py-2 text-xs font-mono text-slate-600 whitespace-nowrap">${escapeHtml(row.region || '-')}</td>
                <td class="px-3 py-2 text-xs font-mono text-slate-700 whitespace-nowrap">${escapeHtml(row.branch_name || '-')}</td>
            </tr>
        `;
    }).join('');
    
    tableBody.innerHTML = html;
}

    function openIssuanceReportDetails(row) {
        const container = document.getElementById('issuance-report-view-content');
        if (!container) return;

        const val = (v) => escapeHtml(String(v === null || v === undefined || v === '' ? '-' : v));
        const money = (v) => `₱ ${formatCurrency(v)}`;

        const titleCls = 'finish-title text-left text-[0.78rem] font-black uppercase tracking-[0.08em] text-red-700 bg-slate-50 border border-slate-200 px-2.5 py-1';
        const labelCls = 'finish-label text-slate-700 text-[0.8rem] font-bold font-mono bg-slate-50 border border-slate-300 px-2.5 py-1 whitespace-nowrap';
        const valueCls = 'finish-value text-slate-900 text-[0.84rem] font-bold font-mono bg-white border border-slate-200 px-2.5 py-1';
        const gapCls   = 'finish-value bg-white border border-slate-200 px-2.5 py-1';

        container.innerHTML =
            '<div class="finish-card finish-card--table finish-card--single rounded-xl border border-slate-200 overflow-hidden">'
            + '<table class="finish-table finish-table--single w-full">'
            + '<colgroup>'
            + '<col class="finish-col-label-sm"><col class="finish-col-value-lg">'
            + '<col class="finish-col-label-sm"><col class="finish-col-value-lg">'
            + '</colgroup>'
            + '<tbody>'

            + '<tr><th colspan="4" class="' + titleCls + '">Issuance</th></tr>'
            + '<tr>'
            + '<td class="' + labelCls + '">Date Issued</td><td class="' + valueCls + '">' + val(formatDate(row.date_issued)) + '</td>'
            + '<td class="' + labelCls + '">Issuance Number</td><td class="' + valueCls + '">' + val(row.issuance_number) + '</td>'
            + '</tr>'
            + '<tr><td class="' + labelCls + '">Status</td><td class="' + valueCls + '" colspan="3">' + val(row.status || row.source_status || '-') + '</td></tr>'
            + '<tr><td class="' + gapCls + '" colspan="4">&nbsp;</td></tr>'

            + '<tr><th colspan="4" class="' + titleCls + '">Item</th></tr>'
            + '<tr>'
            + '<td class="' + labelCls + '">Item Code</td><td class="' + valueCls + '">' + val(row.item_code) + '</td>'
            + '<td class="' + labelCls + '">Quantity</td><td class="' + valueCls + '">' + val(row.quantity) + '</td>'
            + '</tr>'
            + '<tr>'
            + '<td class="' + labelCls + '">UoM</td><td class="' + valueCls + '">' + val(row.uom) + '</td>'
            + '<td class="' + labelCls + '">Description</td><td class="' + valueCls + '">' + val(row.item_description) + '</td>'
            + '</tr>'
            + '<tr><td class="' + labelCls + '">Remarks</td><td class="' + valueCls + '" colspan="3">' + val(row.description_remarks) + '</td></tr>'
            + '<tr><td class="' + gapCls + '" colspan="4">&nbsp;</td></tr>'

            + '<tr><th colspan="4" class="' + titleCls + '">Cost</th></tr>'
            + '<tr>'
            + '<td class="' + labelCls + '">Unit Cost</td><td class="' + valueCls + '">' + money(row.unit_cost) + '</td>'
            + '<td class="' + labelCls + '">Total Amount</td><td class="' + valueCls + '">' + money(row.total_amount) + '</td>'
            + '</tr>'
            + '<tr><td class="' + labelCls + '">Cost Center</td><td class="' + valueCls + '" colspan="3">' + val(row.cost_center_raw) + '</td></tr>'
            + '<tr><td class="' + gapCls + '" colspan="4">&nbsp;</td></tr>'

            + '<tr><th colspan="4" class="' + titleCls + '">Location</th></tr>'
            + '<tr>'
            + '<td class="' + labelCls + '">Product Category</td><td class="' + valueCls + '">' + val(row.product_category) + '</td>'
            + '<td class="' + labelCls + '">Zone</td><td class="' + valueCls + '">' + val(row.zone) + '</td>'
            + '</tr>'
            + '<tr>'
            + '<td class="' + labelCls + '">Region</td><td class="' + valueCls + '">' + val(row.region) + '</td>'
            + '<td class="' + labelCls + '">Branch Name</td><td class="' + valueCls + '">' + val(row.branch_name) + '</td>'
            + '</tr>'

            + '</tbody></table></div>';

        openModal('modal-issuance-report-details');
    }
    
    // ==========================================
    // PAGINATION
    // ==========================================
    
    function renderPagination(pagination) {
        if (!paginationContainer) return;
        
        if (pagination.total_pages <= 1) {
            paginationContainer.innerHTML = '';
            return;
        }
        
        let html = '<div class="flex items-center justify-center gap-2 mt-4">';
        
        // Previous button
        if (pagination.has_prev) {
            html += `<button class="page-btn px-3 py-1 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded text-sm font-medium" data-page="${pagination.page - 1}">« Prev</button>`;
        }
        
        // Page numbers
        const currentPageNum = pagination.page;
        const totalPagesNum = pagination.total_pages;
        let startPage = Math.max(1, currentPageNum - 2);
        let endPage = Math.min(totalPagesNum, currentPageNum + 2);
        
        if (startPage > 1) {
            html += `<button class="page-btn px-3 py-1 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded text-sm font-medium" data-page="1">1</button>`;
            if (startPage > 2) html += '<span class="px-2 text-slate-400">...</span>';
        }
        
        for (let i = startPage; i <= endPage; i++) {
            const activeClass = i === currentPageNum 
                ? 'bg-red-600 text-white' 
                : 'bg-slate-100 hover:bg-slate-200 text-slate-700';
            html += `<button class="page-btn px-3 py-1 rounded text-sm font-medium ${activeClass}" data-page="${i}">${i}</button>`;
        }
        
        if (endPage < totalPagesNum) {
            if (endPage < totalPagesNum - 1) html += '<span class="px-2 text-slate-400">...</span>';
            html += `<button class="page-btn px-3 py-1 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded text-sm font-medium" data-page="${totalPagesNum}">${totalPagesNum}</button>`;
        }
        
        // Next button
        if (pagination.has_next) {
            html += `<button class="page-btn px-3 py-1 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded text-sm font-medium" data-page="${pagination.page + 1}">Next »</button>`;
        }
        
        html += '</div>';
        paginationContainer.innerHTML = html;
        
        // Attach event listeners
        document.querySelectorAll('.page-btn').forEach(btn => {
            btn.addEventListener('click', (e) => {
                e.preventDefault();
                const page = parseInt(btn.dataset.page);
                if (!isNaN(page) && page !== currentPage) {
                    currentPage = page;
                    fetchData();
                    window.scrollTo({ top: 0, behavior: 'smooth' });
                }
            });
        });
    }
    
    // ==========================================
    // TOTALS
    // ==========================================
    
    function updateTotals(totals) {
        if (totalRecordsEl && totals.record_count !== undefined) {
            totalRecordsEl.textContent = totals.record_count.toLocaleString();
        } else if (totalRecordsEl && window.currentTotalRecords) {
            totalRecordsEl.textContent = window.currentTotalRecords.toLocaleString();
        }
        
        if (totalQuantityEl && totals.total_quantity !== undefined) {
            totalQuantityEl.textContent = totals.total_quantity.toLocaleString();
        }
        
        if (totalAmountEl && totals.total_amount !== undefined) {
            totalAmountEl.textContent = `₱ ${formatCurrency(totals.total_amount)}`;
        }
    }
    
    function resetTotals() {
        if (totalRecordsEl) totalRecordsEl.textContent = '0';
        if (totalQuantityEl) totalQuantityEl.textContent = '0';
        if (totalAmountEl) totalAmountEl.textContent = '₱ 0.00';
    }
    
    // ==========================================
    // SORTING
    // ==========================================
    
    function updateSortIndicators() {
        sortButtons.forEach(btn => {
            const indicator = btn.querySelector('.sort-indicator');
            const sortField = btn.dataset.sort;
            if (indicator) {
                if (sortField === currentSort.by) {
                    indicator.textContent = currentSort.dir === 'ASC' ? '↑' : '↓';
                    indicator.classList.remove('opacity-50');
                } else {
                    indicator.textContent = '↕';
                    indicator.classList.add('opacity-50');
                }
            }
        });
    }
    
    function handleSort(sortField) {
        if (currentSort.by === sortField) {
            currentSort.dir = currentSort.dir === 'ASC' ? 'DESC' : 'ASC';
        } else {
            currentSort.by = sortField;
            currentSort.dir = 'DESC';
        }
        currentPage = 1;
        fetchData();
    }
    
    // Attach sort handlers
    sortButtons.forEach(btn => {
        btn.addEventListener('click', (e) => {
            e.preventDefault();
            const sortField = btn.dataset.sort;
            if (sortField) handleSort(sortField);
        });
    });
    
    // ==========================================
    // FILTERS
    // ==========================================

    function normalizeFilterValue(value) {
        const trimmed = (value || '').trim();
        if (!trimmed) return '';
        if (trimmed.toLowerCase().startsWith('all')) return '';
        return trimmed;
    }

    function getRawFilterValue(inputEl) {
        return inputEl ? inputEl.value.trim() : '';
    }

    function collectFilters() {
        currentFilters = {
            search: searchInput ? searchInput.value.trim() : '',
            zone: normalizeFilterValue(getRawFilterValue(zoneSelect)),
            region: normalizeFilterValue(getRawFilterValue(regionSelect)),
            branch_name: normalizeFilterValue(getRawFilterValue(branchSelect)),
            product_category: normalizeFilterValue(getRawFilterValue(categorySelect)),
            date_from: dateFromInput ? dateFromInput.value : '',
            date_to: dateToInput ? dateToInput.value : ''
        };
        currentPage = 1;
        if (shouldFetchData()) {
            fetchData();
        } else {
            renderEmptyState();
        }
    }

    function shouldFetchData() {
        const zoneValue = getRawFilterValue(zoneSelect);
        const regionValue = getRawFilterValue(regionSelect);
        const branchValue = getRawFilterValue(branchSelect);
        const categoryValue = getRawFilterValue(categorySelect);
        return !!(
            zoneValue &&
            regionValue &&
            branchValue &&
            categoryValue &&
            currentFilters.date_from &&
            currentFilters.date_to
        );
    }

    function renderEmptyState() {
        if (initialStateWrapper) initialStateWrapper.classList.remove('hidden');
        if (tableWrapper) tableWrapper.classList.add('hidden');
        if (noDataWrapper) noDataWrapper.classList.add('hidden');
        if (tableBody) tableBody.innerHTML = '';
        if (paginationContainer) paginationContainer.innerHTML = '';
        resetTotals();
    }

    function setupFilterSuggestions(inputEl, listEl) {
        if (!inputEl || !listEl) return;

        const getOptions = () => {
            try {
                return JSON.parse(listEl.dataset.options || '[]');
            } catch (err) {
                return [];
            }
        };

        const renderSuggestions = (matches) => {
            listEl.innerHTML = '';
            if (!matches.length) {
                const emptyEl = document.createElement('div');
                emptyEl.className = 'filter-suggestion is-empty';
                emptyEl.textContent = 'No matches';
                listEl.appendChild(emptyEl);
                listEl.classList.add('is-open');
                return;
            }

            matches.slice(0, 20).forEach((item) => {
                const optionEl = document.createElement('div');
                optionEl.className = 'filter-suggestion';
                optionEl.textContent = item;
                optionEl.dataset.value = item;
                listEl.appendChild(optionEl);
            });

            listEl.classList.add('is-open');
        };

        const closeSuggestions = () => {
            listEl.classList.remove('is-open');
        };

        const filterSuggestions = () => {
            const value = inputEl.value.trim().toLowerCase();
            const options = getOptions();
            if (!value) {
                renderSuggestions(options);
                return;
            }

            const matches = options.filter((opt) => opt.toLowerCase().startsWith(value));
            renderSuggestions(matches);
        };

        inputEl.addEventListener('focus', filterSuggestions);
        inputEl.addEventListener('input', () => {
            filterSuggestions();
            collectFilters();
        });

        inputEl.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') {
                closeSuggestions();
            }
        });

        listEl.addEventListener('mousedown', (e) => {
            const target = e.target.closest('.filter-suggestion');
            if (!target || target.classList.contains('is-empty')) return;
            e.preventDefault();
            inputEl.value = target.dataset.value || target.textContent || '';
            closeSuggestions();
            collectFilters();
        });

        document.addEventListener('click', (e) => {
            if (!listEl.contains(e.target) && e.target !== inputEl) {
                closeSuggestions();
            }
        });
    }

    function syncClearButtonState(inputEl) {
        if (!inputEl) return;
        const wrapper = inputEl.closest('.filter-search');
        if (!wrapper) return;
        wrapper.classList.add('has-clear');
        if (inputEl.value.trim()) {
            wrapper.classList.add('has-value');
        } else {
            wrapper.classList.remove('has-value');
        }
    }

    function setupClearButtons() {
        document.querySelectorAll('.filter-clear-btn').forEach((btn) => {
            const targetId = btn.getAttribute('data-clear-target');
            const inputEl = targetId ? document.getElementById(targetId) : null;
            if (!inputEl) return;

            syncClearButtonState(inputEl);

            btn.addEventListener('click', () => {
                inputEl.value = '';
                syncClearButtonState(inputEl);
                collectFilters();
            });

            inputEl.addEventListener('input', () => {
                syncClearButtonState(inputEl);
            });

            inputEl.addEventListener('focus', () => {
                syncClearButtonState(inputEl);
            });
        });
    }
    
    // Debounced search
    let searchTimeout;
    if (searchInput) {
        searchInput.addEventListener('input', () => {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(() => {
                collectFilters();
            }, 500);
        });
    }
    
    // Filter change handlers
    if (zoneSelect) zoneSelect.addEventListener('input', collectFilters);
    if (regionSelect) regionSelect.addEventListener('input', collectFilters);
    if (branchSelect) branchSelect.addEventListener('input', collectFilters);
    if (categorySelect) categorySelect.addEventListener('input', collectFilters);
    
    // Date range
    if (dateFromInput) dateFromInput.addEventListener('change', collectFilters);
    if (dateToInput) dateToInput.addEventListener('change', collectFilters);
    
    // ==========================================
    // CLEAR FILTERS
    // ==========================================
    
    function clearFilters() {
        if (searchInput) searchInput.value = '';
        if (zoneSelect) zoneSelect.value = '';
        if (regionSelect) regionSelect.value = '';
        if (branchSelect) branchSelect.value = '';
        if (categorySelect) categorySelect.value = '';
        if (dateFromInput) dateFromInput.value = '';
        if (dateToInput) dateToInput.value = '';
        
        currentFilters = {
            search: '', zone: '', region: '', branch_name: '',
            product_category: '', date_from: '', date_to: ''
        };
        currentPage = 1;
        renderEmptyState();
    }
    
    if (clearBtn) {
        clearBtn.addEventListener('click', clearFilters);
    }
    
    // ==========================================
    // EXPORT
    // ==========================================
    
    const exportToggleBtn = document.getElementById('exportToggleBtn');
    const exportMenu = document.getElementById('exportMenu');
    const exportExcelBtn = document.getElementById('exportExcelBtn');
    const exportPrintBtn = document.getElementById('exportPrintBtn');
    
    if (exportToggleBtn && exportMenu) {
        exportToggleBtn.addEventListener('click', (e) => {
            e.stopPropagation();
            exportMenu.classList.toggle('hidden');
        });
        
        document.addEventListener('click', (e) => {
            if (exportMenu && exportToggleBtn && 
                !exportMenu.contains(e.target) && 
                !exportToggleBtn.contains(e.target)) {
                exportMenu.classList.add('hidden');
            }
        });
    }
    
    if (exportExcelBtn) {
        exportExcelBtn.addEventListener('click', () => {
            const params = new URLSearchParams();
            if (currentFilters.search) params.set('search', currentFilters.search);
            if (currentFilters.zone) params.set('zone', currentFilters.zone);
            if (currentFilters.region) params.set('region', currentFilters.region);
            if (currentFilters.branch_name) params.set('branch_name', currentFilters.branch_name);
            if (currentFilters.product_category) params.set('product_category', currentFilters.product_category);
            if (currentFilters.date_from) params.set('date_from', currentFilters.date_from);
            if (currentFilters.date_to) params.set('date_to', currentFilters.date_to);
            
            window.location.href = `${exportUrl}?${params.toString()}`;
            if (exportMenu) exportMenu.classList.add('hidden');
        });
    }
    
    if (exportPrintBtn) {
        exportPrintBtn.addEventListener('click', () => {
            const printWindow = window.open('', '_blank');
            if (!printWindow) {
                alert('Please allow popups to print.');
                return;
            }
            
            const tableContent = document.querySelector('#tableWrapper table');
            if (!tableContent) {
                alert('No data to print.');
                return;
            }
            
            const headerHtml = document.getElementById('exportHeaderTemplate')?.innerHTML || '';
            const generatedBy = 'USER';
            const generatedAt = new Date().toLocaleString();
            const currentDateRange = currentFilters.date_from && currentFilters.date_to 
                ? `Period: ${currentFilters.date_from} to ${currentFilters.date_to}`
                : '';
            
            printWindow.document.write(`
                <!DOCTYPE html>
                <html>
                <head>
                    <title>Issuance Report</title>
                    <style>
                        body { font-family: Arial, sans-serif; padding: 20px; }
                        table { border-collapse: collapse; width: 100%; font-size: 10px; }
                        th, td { border: 1px solid #ddd; padding: 6px; text-align: left; }
                        th { background: #ce2216; color: white; }
                        .print-meta { margin-top: 20px; font-size: 10px; color: #666; }
                        @media print {
                            body { margin: 0; padding: 10px; }
                        }
                    </style>
                </head>
                <body>
                    ${headerHtml}
                    ${currentDateRange ? `<p style="margin: 10px 0; font-size: 11px;">${currentDateRange}</p>` : ''}
                    ${tableContent.outerHTML}
                    <div class="print-meta">
                        Generated by: ${generatedBy}<br>
                        Generated on: ${generatedAt}
                    </div>
                </body>
                </html>
            `);
            printWindow.document.close();
            printWindow.print();
            if (exportMenu) exportMenu.classList.add('hidden');
        });
    }
    
    // ==========================================
    // INITIALIZATION
    // ==========================================
    
    async function init() {
        // Show initial state
        if (initialStateWrapper) initialStateWrapper.classList.remove('hidden');
        if (tableWrapper) tableWrapper.classList.add('hidden');
        if (noDataWrapper) noDataWrapper.classList.add('hidden');
        
        // Load filter options in background
        await loadFilterOptions();

        setupFilterSuggestions(zoneSelect, zoneSuggestions);
        setupFilterSuggestions(regionSelect, regionSuggestions);
        setupFilterSuggestions(branchSelect, branchSuggestions);
        setupFilterSuggestions(categorySelect, categorySuggestions);

        setupClearButtons();

        if (zoneSelect) syncClearButtonState(zoneSelect);
        if (regionSelect) syncClearButtonState(regionSelect);
        if (branchSelect) syncClearButtonState(branchSelect);
        if (categorySelect) syncClearButtonState(categorySelect);

        if (zoneSelect) zoneSelect.value = '';
        if (regionSelect) regionSelect.value = '';
        if (branchSelect) branchSelect.value = '';
        if (categorySelect) categorySelect.value = '';
        if (zoneSelect) syncClearButtonState(zoneSelect);
        if (regionSelect) syncClearButtonState(regionSelect);
        if (branchSelect) syncClearButtonState(branchSelect);
        if (categorySelect) syncClearButtonState(categorySelect);
        
        // Don't fetch data automatically - wait for user to apply filters
        console.log('Issuance Report ready - apply filters to load data');
    }
    
    init();
});