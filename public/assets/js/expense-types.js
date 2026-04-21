var currentPage = 1;
var currentSearch = '';
var currentCategoryFilter = '';

document.addEventListener('DOMContentLoaded', function() {
    var filterSelect = document.getElementById('filter-category-type');
    if (filterSelect) {
        currentCategoryFilter = filterSelect.value || '';
        filterSelect.addEventListener('change', function () {
            currentCategoryFilter = this.value || '';
            loadExpenseTypes(currentSearch, 1);
        });
    }
});

// Simple toast/flash utility (top-center)
function showToast(type, message) {
    if (!message) return;
    let container = document.getElementById('global-toast-container');
    if (!container) {
        container = document.createElement('div');
        container.id = 'global-toast-container';
        container.style.position = 'fixed';
        container.style.top = '1rem';
        container.style.left = '50%';
        container.style.transform = 'translateX(-50%)';
        container.style.zIndex = '9999';
        document.body.appendChild(container);
    }

    const toast = document.createElement('div');
    toast.className = 'mb-3 flex items-center gap-3 text-sm font-bold rounded-xl px-5 py-3.5 shadow-sm';
    if (type === 'success') {
        toast.className += ' bg-green-50 border border-green-200 text-green-800';
        toast.innerHTML = `
            <svg class="w-5 h-5 text-green-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            <div>${message}</div>
        `;
    } else {
        toast.className += ' bg-red-50 border border-red-200 text-red-800';
        toast.innerHTML = `
            <svg class="w-5 h-5 text-red-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            <div>${message}</div>
        `;
    }

    container.appendChild(toast);
    // Auto remove after 3.5s
    setTimeout(() => {
        if (toast && toast.parentNode) toast.parentNode.removeChild(toast);
    }, 3500);

    // Remove on click
    toast.addEventListener('click', () => { if (toast.parentNode) toast.parentNode.removeChild(toast); });
}

document.addEventListener('DOMContentLoaded', function() {
    loadExpenseTypes('', 1);
});

function handleSearch() {
    var searchInput = document.getElementById('searchInput');
    currentSearch = searchInput.value;
    loadExpenseTypes(currentSearch, 1);
}

function loadExpenseTypes(search, page) {
    currentPage = page;
    var url = BASE_URL + '/api/get_expense_types.php?search=' + encodeURIComponent(search) + '&page=' + page;
    if (currentCategoryFilter) {
        url += '&category=' + encodeURIComponent(currentCategoryFilter);
    }
    
    fetch(url)
        .then(function(response) { return response.json(); })
        .then(function(result) {
            if (result.success) {
                renderTable(result.data);
                renderPagination(result.pagination);
            } else {
                showToast('error', 'Failed to load data: ' + (result.message || 'Unknown'));
            }
        })
        .catch(function(error) { console.error('Error fetching data:', error); showToast('error', 'Network error loading expense types.'); });
}

function renderTable(data) {
    var tbody = document.getElementById('expenseTypeTableBody');
    var fragment = document.createDocumentFragment();

    if (data.length === 0) {
        var emptyTr = document.createElement('tr');
        emptyTr.innerHTML = '<td colspan="5" class="text-center text-slate-400 font-bold py-16 text-sm">No expense types found.</td>';
        fragment.appendChild(emptyTr);
        tbody.replaceChildren(fragment);
        return;
    }

    for (var i = 0; i < data.length; i++) {
        var row = data[i];
        var formatType = row.category_type.replace('_', ' '); 
        
        var tr = document.createElement('tr');
        tr.className = 'hover:bg-slate-50/70 transition-colors';
        
        tr.innerHTML = 
            '<td class="px-6 py-0 font-mono text-xs font-bold text-slate-500 text-center">' + row.id + '</td>' +
            '<td class="px-6 py-0 font-bold text-slate-800">' + row.expense_name + '</td>' +
            '<td class="px-6 py-0">' +
                '<span class="inline-flex items-center bg-slate-100 text-slate-600 text-[10px] font-black tracking-widest px-2.5 py-1 rounded-full">' + formatType + '</span>' +
            '</td>' +
            '<td class="px-6 py-0 text-slate-600 font-medium text-xs text-center ">' + row.policy_months + ' Months</td>' +
            '<td class="px-6 py-0 text-center">' +
                '<div class="flex items-center justify-center gap-1">' +
                    '<button onclick="openEditModal(' + row.id + ')" class="p-2 text-slate-400 hover:text-red-600 hover:bg-red-50 rounded-lg transition-all" title="Edit">' +
                        '<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>' +
                    '</button>' +
                    '<button onclick="openDeleteModal(' + row.id + ')" class="p-2 text-slate-400 hover:text-red-600 hover:bg-red-50 rounded-lg transition-all" title="Delete">' +
                        '<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>' +
                    '</button>' +
                '</div>' +
            '</td>';
        
        fragment.appendChild(tr);
    }

    tbody.replaceChildren(fragment);
}

function renderPagination(pagination) {
    var container = document.getElementById('paginationControls');
    container.innerHTML = '';
    if (pagination.total_pages <= 1) return;

    for (var i = 1; i <= pagination.total_pages; i++) {
        var btn = document.createElement('button');
        var isActive = (i === pagination.current_page);
        
        btn.className = isActive 
            ? 'px-3 py-1 bg-[#ce1126] text-white text-xs font-black rounded-lg shadow-sm' 
            : 'px-3 py-1 bg-white text-slate-600 hover:bg-slate-50 text-xs font-black border border-slate-200 rounded-lg transition-all';
        btn.innerText = i;
        
        (function(pageNum) {
            btn.onclick = function() { loadExpenseTypes(currentSearch, pageNum); };
        })(i);
        
        container.appendChild(btn);
    }
}

// --- MODAL UTILS (Using Tailwind .hidden) ---
function openAddModal() {
    document.getElementById('addExpenseTypeForm').reset();
    document.getElementById('addExpenseTypeModal').classList.remove('hidden');
}

function closeAddModal() {
    document.getElementById('addExpenseTypeModal').classList.add('hidden');
}

function openEditModal(id) {
    fetch(BASE_URL + '/api/get_expense_type_by_id.php?id=' + id)
        .then(function(response) { return response.json(); })
        .then(function(result) {
            if (result.success) {
                document.getElementById('edit_id').value = result.data.id;
                document.getElementById('edit_expense_name').value = result.data.expense_name;
                document.getElementById('edit_category_type').value = result.data.category_type;
                document.getElementById('edit_policy_months').value = result.data.policy_months;
                document.getElementById('editExpenseTypeModal').classList.remove('hidden');
            }
        }).catch(function(err){ console.error('Failed to fetch expense type by id:', err); showToast('error', 'Failed to load expense type.'); });
}

function closeEditModal() {
    document.getElementById('editExpenseTypeModal').classList.add('hidden');
}

function openDeleteModal(id) {
    document.getElementById('delete_id').value = id;
    document.getElementById('deleteExpenseTypeModal').classList.remove('hidden');
}

function closeDeleteModal() {
    document.getElementById('deleteExpenseTypeModal').classList.add('hidden');
}

// --- API ACTIONS ---
function submitAddForm() {
    var formData = new FormData(document.getElementById('addExpenseTypeForm'));
    fetch(BASE_URL + '/public/actions/expense_type_store.php', { method: 'POST', body: formData })
        .then(function(response) { 
            return response.text(); // 1. Get raw text instead of json
        })
        .then(function(text) {
            // 2. Violently strip the BOM and any accidental whitespace
            var cleanText = text.replace(/^\uFEFF/, '').trim();
            var result = JSON.parse(cleanText); // 3. Parse it safely
            
            if (result.success) { 
                showToast('success', result.message || 'Saved successfully.');
                closeAddModal(); 
                loadExpenseTypes(currentSearch, 1); 
            } else { 
                showToast('error', result.message || 'Save failed.'); 
            }
        })
        .catch(function(error) { 
            console.error('Fetch error:', error);
            showToast('error', 'A system error occurred. Check console.');
        });
}

function submitEditForm() {
    var formData = new FormData(document.getElementById('editExpenseTypeForm'));
    fetch(BASE_URL + '/public/actions/expense_type_update.php', { method: 'POST', body: formData })
        .then(function(response) { return response.text(); })
        .then(function(text) {
            var cleanText = text.replace(/^\uFEFF/, '').trim();
            var result = JSON.parse(cleanText);
            
            if (result.success) { 
                showToast('success', result.message || 'Updated successfully.'); 
                closeEditModal(); 
                loadExpenseTypes(currentSearch, currentPage); 
            } else { 
                showToast('error', result.message || 'Update failed.'); 
            }
        })
        .catch(function(error) { 
            console.error('Fetch error:', error);
            showToast('error', 'A system error occurred. Check console.');
        });
}

function confirmDelete() {
    var formData = new FormData();
    formData.append('id', document.getElementById('delete_id').value);
    fetch(BASE_URL + '/public/actions/expense_type_delete.php', { method: 'POST', body: formData })
        .then(function(response) { return response.text(); })
        .then(function(text) {
            var cleanText = text.replace(/^\uFEFF/, '').trim();
            var result = JSON.parse(cleanText);
            
            if (result.success) { 
                showToast('success', result.message || 'Deleted successfully.'); 
                closeDeleteModal(); 
                loadExpenseTypes(currentSearch, currentPage); 
            } else { 
                showToast('error', result.message || 'Delete failed.'); 
            }
        })
        .catch(function(error) { 
            console.error('Fetch error:', error);
            showToast('error', 'A system error occurred. Check console.');
        });
}