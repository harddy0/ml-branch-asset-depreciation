/**
 * Asset Groups Management - Tailwind Modals & API Hooks
 */

// Global Window functions so Tailwind HTML triggers can find them
window.openModal = function(modalId) {
    const modal = document.getElementById(modalId);
    if(modal) {
        modal.classList.remove('hidden');
    } else {
        console.error("Modal not found: " + modalId);
    }
};

window.closeModal = function(modalId) {
    const modal = document.getElementById(modalId);
    if(modal) {
        modal.classList.add('hidden');
    }
};

// Small toast utility (reusable) - top center
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
    setTimeout(() => { if (toast.parentNode) toast.parentNode.removeChild(toast); }, 3500);
    toast.addEventListener('click', () => { if (toast.parentNode) toast.parentNode.removeChild(toast); });
}

document.addEventListener('DOMContentLoaded', function() {
    console.log("Asset Groups JS Initialized - Tailwind UI Ready");

    // In-memory cache of asset groups keyed by id for quick lookup when editing
    let assetGroupsCache = {};

    // Fetch asset groups from API and render into the table
    async function loadAssetGroups() {
        const tbody = document.getElementById('assetGroupsTbody');
        if (!tbody) return;

        try {
            const res = await fetch('../api/get_asset_groups.php?page=1&limit=1000');
            const text = await res.text();
            let json;
            try {
                json = JSON.parse(text);
            } catch (err) {
                console.error('PHP error from get_asset_groups.php:', text);
                tbody.innerHTML = '<tr><td colspan="6" class="px-6 py-4 text-sm text-red-600">Server error loading asset groups.</td></tr>';
                return;
            }

            const rows = json.data || [];
            assetGroupsCache = {};
            if (rows.length === 0) {
                tbody.innerHTML = '<tr><td colspan="6" class="px-6 py-4 text-sm text-slate-500">No asset groups found.</td></tr>';
                return;
            }

            // Cache rows and render
            rows.forEach(r => { assetGroupsCache[r.id] = r; });
            renderAssetGroups(rows);
        } catch (err) {
            console.error('Failed to fetch asset groups:', err);
            tbody.innerHTML = '<tr><td colspan="6" class="px-6 py-4 text-sm text-red-600">Network error loading asset groups.</td></tr>';
        }
    }

    // Render function - can be reused for filtered views
    function renderAssetGroups(rows) {
        const tbody = document.getElementById('assetGroupsTbody');
        if (!tbody) return;

        const html = rows.map(r => {
            const expenseName = r.expense_name ? `${r.expense_name}${r.policy_months ? ' (' + r.policy_months + ' months)' : ''}` : '-';
            return `
                    <tr class="hover:bg-slate-50 transition-colors">
                        <td class="px-6 py-0 text-sm font-medium text-slate-700">${escapeHtml(r.group_name || '')}</td>
                        <td class="px-6 py-0 text-sm font-medium text-slate-700">${escapeHtml(expenseName)}</td>
                        <td class="px-6 py-0 text-sm font-medium text-slate-700 text-center">${escapeHtml(r.actual_months ?? '')}</td>
                        <td class="px-6 py-0 text-sm font-medium text-slate-700 text-center">${escapeHtml(r.asset_gl_code || '')}</td>
                        <td class="px-6 py-0 text-sm font-medium text-slate-700 text-center">${escapeHtml(r.expense_gl_code || '')}</td>
                        <td class="px-6 py-0 text-center text-sm">
                            <button data-id="${r.id}" class="edit-btn inline-flex items-center justify-center w-6 h-6 bg-white hover:bg-slate-50 text-slate-700 rounded-sm shadow-sm transition" title="Edit">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5M18.5 2.5a2.121 2.121 0 113 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                            </button>
                            <button data-id="${r.id}" class="delete-btn inline-flex items-center justify-center w-6 h-6 ml-1 bg-white hover:bg-red-50 text-red-600 rounded-sm shadow-sm transition" title="Delete">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6M9 7V4a1 1 0 011-1h4a1 1 0 011 1v3m-7 0h8"/></svg>
                            </button>
                        </td>
                    </tr>
                `;
        }).join('');

        tbody.innerHTML = html;
    }

    // Simple HTML escape for values inserted into templates
    function escapeHtml(str) {
        if (str === null || str === undefined) return '';
        return String(str).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;').replace(/'/g, '&#39;');
    }

    // Helper: set select value, retrying briefly if options are not loaded yet
    function setSelectValueWithRetry(selectEl, value, attempts = 6, delay = 120) {
        if (!selectEl) return;
        const trySet = () => {
            if (value === null || value === undefined) {
                selectEl.value = '';
                return;
            }
            // If option exists, set it
            const opt = Array.from(selectEl.options).find(o => o.value === String(value));
            if (opt) {
                selectEl.value = String(value);
                return;
            }
            // If no options yet and we have attempts left, retry
            attempts -= 1;
            if (attempts > 0) setTimeout(trySet, delay);
            else selectEl.value = '';
        };
        trySet();
    }

    // Setup click handlers for edit/delete using event delegation on tbody
    document.addEventListener('click', function(e) {
        const editBtn = e.target.closest && e.target.closest('.edit-btn');
        if (editBtn) {
            const id = editBtn.getAttribute('data-id');
            const row = assetGroupsCache[id];
            if (row) {
                // Populate edit form
                const editForm = document.getElementById('formEditAssetGroup');
                if (editForm) {
                    document.getElementById('edit_id').value = row.id;
                    document.getElementById('edit_group_name').value = row.group_name ?? '';
                    document.getElementById('edit_actual_months').value = row.actual_months ?? '';
                    const selectExpense = document.getElementById('edit_expense_type_id');
                    setSelectValueWithRetry(selectExpense, row.expense_type_id ?? '');
                    if (typeof dropdownsLoaded?.then === 'function') dropdownsLoaded.then(() => setSelectValueWithRetry(selectExpense, row.expense_type_id ?? ''));

                    const selectAssetGl = document.getElementById('edit_asset_gl_code');
                    setSelectValueWithRetry(selectAssetGl, row.asset_gl_code ?? '');
                    if (typeof dropdownsLoaded?.then === 'function') dropdownsLoaded.then(() => setSelectValueWithRetry(selectAssetGl, row.asset_gl_code ?? ''));

                    const selectExpenseGl = document.getElementById('edit_expense_gl_code');
                    setSelectValueWithRetry(selectExpenseGl, row.expense_gl_code ?? '');
                    if (typeof dropdownsLoaded?.then === 'function') dropdownsLoaded.then(() => setSelectValueWithRetry(selectExpenseGl, row.expense_gl_code ?? ''));
                }
                openModal('asset-group-edit-modal');
            }
            return;
        }

        const deleteBtn = e.target.closest && e.target.closest('.delete-btn');
        if (deleteBtn) {
            const id = deleteBtn.getAttribute('data-id');
            const deleteInput = document.getElementById('delete_id');
            if (deleteInput) deleteInput.value = id;
            openModal('asset-group-delete-modal');
            return;
        }
    });

    // Initial load of asset groups
    loadAssetGroups();

    // --- Search / Filter ---
    const searchInput = document.getElementById('search-input');
    if (searchInput) {
        searchInput.addEventListener('input', function() {
            const q = this.value.trim().toLowerCase();
            // If no query, render all cached rows
            const allRows = Object.values(assetGroupsCache || {});
            if (!q) {
                renderAssetGroups(allRows);
                return;
            }

            // Filter by group_name, asset_gl_code, expense_gl_code
            const filtered = allRows.filter(r => {
                const group = (r.group_name || '').toString().toLowerCase();
                const assetGl = (r.asset_gl_code || '').toString().toLowerCase();
                const expenseGl = (r.expense_gl_code || '').toString().toLowerCase();
                return group.includes(q) || assetGl.includes(q) || expenseGl.includes(q);
            });

            if (filtered.length === 0) {
                const tbody = document.getElementById('assetGroupsTbody');
                tbody.innerHTML = '<tr><td colspan="6" class="px-6 py-4 text-sm text-slate-500">No matching asset groups.</td></tr>';
            } else {
                renderAssetGroups(filtered);
            }
        });
    }

    // --- Fetch and Populate Dropdowns ---
    async function loadDropdownData() {
        try {
            // 1. Fetch Expense Types
            const expenseRes = await fetch('../api/get_expense_types_dropdown.php');
            const expenseText = await expenseRes.text(); // Read as text first to catch PHP errors
            
            try {
                const expenseData = JSON.parse(expenseText);
                if(expenseData.success) populateExpenseDropdowns(expenseData.data);
            } catch(e) {
                console.error("PHP Error in Expense Types API! Here is what PHP outputted:\n\n", expenseText);
            }

            // 2. Fetch GL Codes
            const glRes = await fetch('../api/get_gl_codes_dropdown.php');
            const glText = await glRes.text();
            
            try {
                const glData = JSON.parse(glText);
                if(glData.success) populateGLDropdowns(glData.data);
            } catch(e) {
                console.error("PHP Error in GL Codes API! Here is what PHP outputted:\n\n", glText);
            }

        } catch (error) {
            console.error("Network request failed:", error);
        }
    }

    function populateExpenseDropdowns(expenses) {
        const optionsHTML = `<option value="">-- Select Expense Type --</option>` + 
            expenses.map(ex => `<option value="${ex.id}">${ex.expense_name} (${ex.policy_months} months policy)</option>`).join('');
        
        const addExpenseSelect = document.querySelector('#formAddAssetGroup select[name="expense_type_id"]');
        if (addExpenseSelect) addExpenseSelect.innerHTML = optionsHTML;

        const editExpenseSelect = document.getElementById('edit_expense_type_id');
        if (editExpenseSelect) editExpenseSelect.innerHTML = optionsHTML;
    }

    function populateGLDropdowns(glCodes) {
        const optionsHTML = `<option value="">-- Select GL Code --</option>` + 
            glCodes.map(gl => `<option value="${gl.gl_code}">${gl.gl_code} - ${gl.description}</option>`).join('');

        const addAssetGl = document.querySelector('#formAddAssetGroup select[name="asset_gl_code"]');
        const addExpenseGl = document.querySelector('#formAddAssetGroup select[name="expense_gl_code"]');
        if (addAssetGl) addAssetGl.innerHTML = optionsHTML;
        if (addExpenseGl) addExpenseGl.innerHTML = optionsHTML;

        const editAssetGl = document.getElementById('edit_asset_gl_code');
        const editExpenseGl = document.getElementById('edit_expense_gl_code');
        if (editAssetGl) editAssetGl.innerHTML = optionsHTML;
        if (editExpenseGl) editExpenseGl.innerHTML = optionsHTML;
    }

    // Trigger the load immediately and keep its promise so we can retry setting selects after options load
    const dropdownsLoaded = loadDropdownData();

    // Click outside to close modal
    window.addEventListener('click', function(e) {
        if (e.target.id === 'asset-group-add-modal' ||
            e.target.id === 'asset-group-edit-modal' ||
            e.target.id === 'asset-group-delete-modal') {
            e.target.classList.add('hidden');
        }
    });

    // Press Escape to close any open modal
    window.addEventListener('keydown', function(e) {
        if (e.key !== 'Escape') return;

        ['asset-group-add-modal', 'asset-group-edit-modal', 'asset-group-delete-modal'].forEach(function(id) {
            const modal = document.getElementById(id);
            if (modal && !modal.classList.contains('hidden')) {
                modal.classList.add('hidden');
            }
        });
    });

    // Form Event Listeners 
    // --- ADD ASSET GROUP FORM LOGIC ---
    const addForm = document.getElementById('formAddAssetGroup');
    if(addForm) {
        addForm.addEventListener('submit', async function(e) {
            e.preventDefault(); // Stop the browser from refreshing immediately
            
            // 1. Scoop up all the input values automatically
            const formData = new FormData(this);
            
            // Optional: Change button text to show it's working
            const submitBtn = this.querySelector('button[type="submit"]');
            const originalBtnText = submitBtn.innerText;
            submitBtn.innerText = "Saving...";
            submitBtn.disabled = true;

            try {
                // 2. Send the data to your action endpoint
                const response = await fetch('../actions/asset_group_store.php', {
                    method: 'POST',
                    body: formData
                });

                // 3. Catch PHP errors just like we did with the dropdowns
                const text = await response.text();
                let result;
                try {
                    result = JSON.parse(text);
                } catch (jsonError) {
                    console.error("PHP Error on Save:\n\n", text);
                    showToast('error', 'A backend error occurred. Check console.');
                    return;
                }

                // 4. Handle the success or failure logic
                if (result.success) {
                    // Success: show toast, close modal, clear form, and reload table data
                    showToast('success', result.message || 'Asset group added successfully.');
                    closeModal('asset-group-add-modal');
                    this.reset();
                    await loadAssetGroups();
                } else {
                    // Backend Validation Failed (e.g., Months exceeded policy)
                    showToast('error', result.message || 'Failed to add asset group.');
                }

            } catch (error) {
                console.error("Network submission failed:", error);
                showToast('error', 'Could not connect to the server.');
            } finally {
                // Restore button state
                submitBtn.innerText = originalBtnText;
                submitBtn.disabled = false;
            }
        });
    }

    const editForm = document.getElementById('formEditAssetGroup');
    if(editForm) {
        editForm.addEventListener('submit', async function(e) {
            e.preventDefault();
            const formData = new FormData(this);
            const submitBtn = this.querySelector('button[type="submit"]');
            const orig = submitBtn.innerText;
            submitBtn.innerText = 'Updating...'; submitBtn.disabled = true;
            try {
                const res = await fetch('../actions/asset_group_update.php', { method: 'POST', body: formData });
                const text = await res.text();
                let result;
                try { result = JSON.parse(text); } catch (err) { console.error('PHP Error on Update:', text); showToast('error', 'Server error.'); return; }
                if (result.success) {
                    showToast('success', result.message || 'Updated successfully.');
                    closeModal('asset-group-edit-modal');
                    await loadAssetGroups();
                } else {
                    showToast('error', result.message || 'Update failed.');
                }
            } catch (err) { console.error('Update failed:', err); showToast('error', 'Network error.'); }
            finally { submitBtn.innerText = orig; submitBtn.disabled = false; }
        });
    }

    const deleteForm = document.getElementById('formDeleteAssetGroup');
    if(deleteForm) {
        deleteForm.addEventListener('submit', async function(e) {
            e.preventDefault();
            const formData = new FormData(this);
            const submitBtn = this.querySelector('button[type="submit"]');
            const orig = submitBtn.innerText;
            submitBtn.innerText = 'Deleting...'; submitBtn.disabled = true;
            try {
                const res = await fetch('../actions/asset_group_delete.php', { method: 'POST', body: formData });
                const text = await res.text();
                let result;
                try { result = JSON.parse(text); } catch (err) { console.error('PHP Error on Delete:', text); showToast('error', 'Server error.'); return; }
                if (result.success) {
                    showToast('success', result.message || 'Asset group deleted.');
                    closeModal('asset-group-delete-modal');
                    await loadAssetGroups();
                } else {
                    showToast('error', result.message || 'Delete failed.');
                }
            } catch (err) { console.error('Delete failed:', err); showToast('error', 'Network error.'); }
            finally { submitBtn.innerText = orig; submitBtn.disabled = false; }
        });
    }
});