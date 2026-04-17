// public/assets/js/gl-codes-list.js
// Handles GL codes list, search, and pagination

document.addEventListener('DOMContentLoaded', function () {
    const tbody = document.getElementById('gl-codes-tbody');
    const searchInput = document.getElementById('search-input');
    const paginationContainer = document.getElementById('pagination-container');

    let currentPage = 0;
    let currentSearch = '';
    const limit = 20;

    function parseJsonPayload(rawText) {
        const text = (rawText || '').replace(/^\uFEFF/, '').trim();
        if (!text) {
            throw new Error('Empty response from server.');
        }

        try {
            return JSON.parse(text);
        } catch (_e) {
            const jsonStart = text.indexOf('{');
            const jsonEnd = text.lastIndexOf('}');
            if (jsonStart !== -1 && jsonEnd > jsonStart) {
                return JSON.parse(text.slice(jsonStart, jsonEnd + 1));
            }
            throw new Error('Invalid JSON response from server.');
        }
    }

    // Function to load GL codes
    function loadGlCodes(page = 0, search = '') {
        if (!tbody) return;

        tbody.innerHTML = '<tr><td colspan="4" class="px-6 py-3 text-center text-slate-500">Loading...</td></tr>';

        const params = new URLSearchParams({
            limit: limit,
            offset: page * limit,
            search: search
        });

        fetch(`../api/get_gl_codes.php?${params}`)
            .then(async (res) => {
                const rawText = await res.text();
                if (!res.ok) {
                    throw new Error(`Failed to load GL codes (${res.status}).`);
                }
                return parseJsonPayload(rawText);
            })
            .then(data => {
                if (data.success) {
                    renderTable(data.data);
                    renderPagination(data.total, page);
                } else {
                    tbody.innerHTML = '<tr><td colspan="4" class="px-6 py-3 text-center text-red-500">Error loading GL codes.</td></tr>';
                }
            })
            .catch(err => {
                console.error('Error loading GL codes:', err);
                tbody.innerHTML = '<tr><td colspan="4" class="px-6 py-3 text-center text-red-500">Error loading GL codes.</td></tr>';
            });
    }

    // Render table rows
    function renderTable(glCodes) {
        tbody.innerHTML = '';
        if (glCodes.length === 0) {
            tbody.innerHTML = '<tr><td colspan="4" class="px-6 py-3 text-center text-slate-500">No GL codes found.</td></tr>';
            return;
        }
        glCodes.forEach(gl => {
            const row = document.createElement('tr');
            row.className = 'hover:bg-slate-50/70 transition-colors';
            row.innerHTML = `
                <td class="px-6 py-3 font-mono text-xs font-bold text-slate-600">${gl.gl_code}</td>
                <td class="px-6 py-3"><p class="font-bold uppercase text-slate-800">${gl.description}</p></td>
                <td class="px-6 py-3">
                    <span class="inline-flex items-center gap-1.5 ${gl.account_type === 'CREDIT' ? 'bg-red-100 text-red-700' : 'bg-slate-100 text-slate-600'} text-[10px] font-black uppercase tracking-widest px-2.5 py-1 rounded-full">
                        ${gl.account_type}
                    </span>
                </td>
                <td class="px-6 py-3">
                    <div class="flex items-center justify-center gap-1">
                        <button class="p-2 text-slate-400 hover:text-blue-600 hover:bg-blue-50 rounded-lg transition-all edit-gl-code-btn" title="Edit" data-gl-code="${gl.gl_code}">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                            </svg>
                        </button>
                        <button class="p-2 text-slate-400 hover:text-red-600 hover:bg-red-50 rounded-lg transition-all delete-gl-code-btn" title="Delete" data-gl-code="${gl.gl_code}">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                            </svg>
                        </button>
                    </div>
                </td>
            `;
            tbody.appendChild(row);
        });
        // Attach listeners to new buttons
        attachDeleteListeners();
        attachEditListeners();
    }

    // Render pagination
    function renderPagination(total, currentPage) {
        if (!paginationContainer) return;

        const totalPages = Math.ceil(total / limit);
        if (totalPages <= 1) {
            paginationContainer.innerHTML = '';
            return;
        }

        let html = '<div class="flex items-center justify-center gap-2 mt-4">';

        // Previous button
        if (currentPage > 0) {
            html += `<button class="px-3 py-1 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded" data-page="${currentPage - 1}">Previous</button>`;
        }

        // Page numbers
        const startPage = Math.max(0, currentPage - 2);
        const endPage = Math.min(totalPages - 1, currentPage + 2);

        for (let i = startPage; i <= endPage; i++) {
            const activeClass = i === currentPage ? 'bg-red-600 text-white' : 'bg-slate-100 hover:bg-slate-200 text-slate-700';
            html += `<button class="px-3 py-1 ${activeClass} rounded" data-page="${i}">${i + 1}</button>`;
        }

        // Next button
        if (currentPage < totalPages - 1) {
            html += `<button class="px-3 py-1 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded" data-page="${currentPage + 1}">Next</button>`;
        }

        html += '</div>';
        paginationContainer.innerHTML = html;

        // Add event listeners
        paginationContainer.querySelectorAll('button[data-page]').forEach(btn => {
            btn.addEventListener('click', function() {
                const page = parseInt(this.getAttribute('data-page'));
                currentPage = page;
                loadGlCodes(currentPage, currentSearch);
            });
        });
    }

    // Search functionality
    if (searchInput) {
        let searchTimeout;
        searchInput.addEventListener('input', function() {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(() => {
                currentSearch = this.value.trim();
                currentPage = 0; // Reset to first page on search
                loadGlCodes(currentPage, currentSearch);
            }, 300);
        });
    }

    // Initial load
    loadGlCodes();

    // Attach delete listeners after initial load
    attachDeleteListeners();

    // Attach edit listeners after initial load
    attachEditListeners();

    // Expose loadGlCodes for external use (e.g., after adding a new GL code)
    window.loadGlCodes = loadGlCodes;

    // Expose modal functions globally
    window.openModal = openModal;
    window.closeModal = closeModal;

    // Handle delete button clicks
    function handleDeleteClick(glCode) {
        const modal = document.getElementById('modal-delete-gl-code');
        const displayEl = document.getElementById('delete-gl-code-display');
        const confirmBtn = document.getElementById('btn-confirm-delete-gl-code');

        if (!modal || !displayEl || !confirmBtn) return;

        displayEl.textContent = glCode;

        // Remove previous event listener
        const newConfirmBtn = confirmBtn.cloneNode(true);
        confirmBtn.parentNode.replaceChild(newConfirmBtn, confirmBtn);

        newConfirmBtn.addEventListener('click', function() {
            newConfirmBtn.disabled = true;
            newConfirmBtn.textContent = 'Deleting...';

            const formData = new FormData();
            formData.append('gl_code', glCode);

            fetch('../api/delete_gl_code.php', {
                method: 'POST',
                body: formData
            })
            .then(async (res) => {
                const rawText = await res.text();
                const payload = parseJsonPayload(rawText);
                if (!res.ok && !payload.success) {
                    throw new Error(payload.error || `Delete failed (${res.status}).`);
                }
                return payload;
            })
            .then(data => {
                if (data.success) {
                    showGlCodeAlert(true, data.message || 'GL code deleted successfully.');
                    closeModal('modal-delete-gl-code');
                    loadGlCodes(currentPage, currentSearch); // Reload the list
                } else {
                    showGlCodeAlert(false, data.error || 'Failed to delete GL code.');
                }
                newConfirmBtn.disabled = false;
                newConfirmBtn.textContent = 'Delete Code';
            })
            .catch(err => {
                console.error('Error deleting GL code:', err);
                showGlCodeAlert(false, 'An error occurred while deleting the GL code.');
                newConfirmBtn.disabled = false;
                newConfirmBtn.textContent = 'Delete Code';
            });
        });

        openModal('modal-delete-gl-code');
    }

    // Show alert function
    function showGlCodeAlert(success, message) {
        let alertDiv = document.getElementById('gl-code-alert');
        if (!alertDiv) {
            alertDiv = document.createElement('div');
            alertDiv.id = 'gl-code-alert';
            alertDiv.className = 'fixed top-6 left-1/2 -translate-x-1/2 z-[200] px-6 py-3 rounded-xl shadow-lg text-sm font-bold';
            document.body.appendChild(alertDiv);
        }
        alertDiv.textContent = message;
        alertDiv.style.display = 'block';
        alertDiv.classList.remove('bg-green-50', 'bg-red-50', 'text-green-800', 'text-red-800', 'border-green-200', 'border-red-200');
        if (success) {
            alertDiv.classList.add('bg-green-50', 'text-green-800', 'border', 'border-green-200');
        } else {
            alertDiv.classList.add('bg-red-50', 'text-red-800', 'border', 'border-red-200');
        }
        setTimeout(() => {
            alertDiv.style.display = 'none';
        }, 3500);
    }

    // Modal functions
    function openModal(modalId) {
        const modal = document.getElementById(modalId);
        if (!modal) return;
        modal.classList.remove('hidden');
        setTimeout(() => {
            const backdrop = modal.querySelector('.modal-backdrop');
            const panel = modal.querySelector('.modal-panel');
            if (backdrop) backdrop.classList.remove('opacity-0');
            if (panel) panel.classList.remove('opacity-0', 'scale-95');
        }, 10);
    }

    function closeModal(modalId) {
        const modal = document.getElementById(modalId);
        if (!modal) return;
        const backdrop = modal.querySelector('.modal-backdrop');
        const panel = modal.querySelector('.modal-panel');
        if (backdrop) backdrop.classList.add('opacity-0');
        if (panel) panel.classList.add('opacity-0', 'scale-95');
        setTimeout(() => {
            modal.classList.add('hidden');
        }, 200);
    }

    // Attach delete event listeners to dynamically created buttons
    function attachDeleteListeners() {
        document.querySelectorAll('.delete-gl-code-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                const glCode = this.getAttribute('data-gl-code');
                if (glCode) {
                    handleDeleteClick(glCode);
                }
            });
        });
    }

    // Attach edit event listeners to dynamically created buttons
    function attachEditListeners() {
        document.querySelectorAll('.edit-gl-code-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                const glCode = this.getAttribute('data-gl-code');
                if (glCode && window.handleEditClick) {
                    window.handleEditClick(glCode);
                }
            });
        });
    }
});