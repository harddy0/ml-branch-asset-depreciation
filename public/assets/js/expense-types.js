var currentPage = 1;
var currentSearch = '';

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
    
    fetch(url)
        .then(function(response) { return response.json(); })
        .then(function(result) {
            if (result.success) {
                renderTable(result.data);
                renderPagination(result.pagination);
            } else {
                alert('Failed to load data: ' + result.message);
            }
        })
        .catch(function(error) { console.error('Error fetching data:', error); });
}

function renderTable(data) {
    var tbody = document.getElementById('expenseTypeTableBody');
    tbody.innerHTML = '';

    if (data.length === 0) {
        tbody.innerHTML = '<tr><td colspan="5" class="text-center text-slate-400 font-bold py-16 text-sm">No expense types found.</td></tr>';
        return;
    }

    for (var i = 0; i < data.length; i++) {
        var row = data[i];
        var formatType = row.category_type.replace('_', ' '); 
        
        var tr = document.createElement('tr');
        tr.className = 'hover:bg-slate-50/70 transition-colors';
        
        tr.innerHTML = 
            '<td class="px-6 py-3 font-mono text-xs font-bold text-slate-500">' + row.id + '</td>' +
            '<td class="px-6 py-3 font-bold uppercase text-slate-800">' + row.expense_name + '</td>' +
            '<td class="px-6 py-3">' +
                '<span class="inline-flex items-center bg-slate-100 text-slate-600 text-[10px] font-black uppercase tracking-widest px-2.5 py-1 rounded-full">' + formatType + '</span>' +
            '</td>' +
            '<td class="px-6 py-3 text-slate-600 font-medium text-sm">' + row.policy_months + ' Months</td>' +
            '<td class="px-6 py-3">' +
                '<div class="flex items-center justify-end gap-1">' +
                    '<button onclick="openEditModal(' + row.id + ')" class="p-2 text-slate-400 hover:text-red-600 hover:bg-red-50 rounded-lg transition-all" title="Edit">' +
                        '<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>' +
                    '</button>' +
                    '<button onclick="openDeleteModal(' + row.id + ')" class="p-2 text-slate-400 hover:text-red-600 hover:bg-red-50 rounded-lg transition-all" title="Delete">' +
                        '<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>' +
                    '</button>' +
                '</div>' +
            '</td>';
        
        tbody.appendChild(tr);
    }
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
        });
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
    fetch(BASE_URL + '/actions/expense_type_store.php', { method: 'POST', body: formData })
        .then(function(response) { return response.json(); })
        .then(function(result) {
            if (result.success) { 
                alert(result.message); // Show success message
                closeAddModal(); 
                loadExpenseTypes(currentSearch, 1); 
            } else { 
                alert('Error: ' + result.message); 
            }
        })
        .catch(function(error) { 
            console.error('Fetch error:', error);
            alert('A system error occurred. Check the console for details.');
        });
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
                alert(result.message); 
                closeAddModal(); 
                loadExpenseTypes(currentSearch, 1); 
            } else { 
                alert('Error: ' + result.message); 
            }
        })
        .catch(function(error) { 
            console.error('Fetch error:', error);
            alert('A system error occurred. Check the console for details.');
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
                alert(result.message); 
                closeEditModal(); 
                loadExpenseTypes(currentSearch, currentPage); 
            } else { 
                alert('Error: ' + result.message); 
            }
        })
        .catch(function(error) { 
            console.error('Fetch error:', error);
            alert('A system error occurred. Check the console for details.');
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
                alert(result.message); 
                closeDeleteModal(); 
                loadExpenseTypes(currentSearch, currentPage); 
            } else { 
                alert('Error: ' + result.message); 
            }
        })
        .catch(function(error) { 
            console.error('Fetch error:', error);
            alert('A system error occurred. Check the console for details.');
        });
}