(function() {
    'use strict';

    document.querySelectorAll('[data-modal-close="modal-edit-gl-code"]').forEach((el) => {
        el.addEventListener('click', () => closeModal('modal-edit-gl-code'));
    });

    // Handle edit button clicks
    function handleEditClick(glCode) {
        // Fetch current GL code data
        fetch(`../api/get_gl_codes.php?gl_code=${encodeURIComponent(glCode)}`)
            .then(res => res.text())
            .then(rawText => {
                const text = rawText.replace(/^\uFEFF/, '');
                return JSON.parse(text);
            })
            .then(data => {
                if (data.success && data.data) {
                    populateEditModal(data.data);
                    openModal('modal-edit-gl-code');
                } else {
                    showGlCodeAlert(false, data.error || 'Failed to load GL code data.');
                }
            })
            .catch(err => {
                console.error('Error fetching GL code:', err);
                showGlCodeAlert(false, 'An error occurred while loading the GL code.');
            });
    }

    // Populate the edit modal with data
    function populateEditModal(glCodeData) {
        document.getElementById('edit-gl-code').value = glCodeData.gl_code;
        document.getElementById('edit-description').value = glCodeData.description;
        document.getElementById('edit-account-type').value = glCodeData.account_type;
    }

    // Handle update button click
    const updateBtn = document.getElementById('btn-update-gl-code');
    if (updateBtn) {
        updateBtn.addEventListener('click', function() {
        const glCode = document.getElementById('edit-gl-code').value;
        const description = document.getElementById('edit-description').value.trim();
        const accountType = document.getElementById('edit-account-type').value;

        if (!description || !accountType) {
            showGlCodeAlert(false, 'All fields are required.');
            return;
        }

        this.disabled = true;
        this.textContent = 'Updating...';

        const formData = new FormData();
        formData.append('gl_code', glCode);
        formData.append('description', description);
        formData.append('account_type', accountType);

        fetch('../api/update_gl_code.php', {
            method: 'POST',
            body: formData
        })
        .then(res => {
            if (!res.ok) {
                throw new Error(`HTTP ${res.status}: ${res.statusText}`);
            }
            return res.text();
        })
        .then(rawText => {
            if (!rawText.trim()) {
                throw new Error('Empty response from server');
            }
            const text = rawText.replace(/^\uFEFF/, '');
            try {
                return JSON.parse(text);
            } catch (e) {
                console.error('Raw response:', rawText);
                throw new Error('Invalid JSON response from server');
            }
        })
        .then(data => {
            if (data.success) {
                showGlCodeAlert(true, data.message || 'GL code updated successfully.');
                closeModal('modal-edit-gl-code');
                // Reload the list - assuming loadGlCodes is available from gl-codes-list.js
                if (window.loadGlCodes) {
                    window.loadGlCodes();
                }
            } else {
                showGlCodeAlert(false, data.error || 'Failed to update GL code.');
            }
            this.disabled = false;
            this.textContent = 'Update Code';
        })
        .catch(err => {
            console.error('Error updating GL code:', err);
            showGlCodeAlert(false, err.message || 'An error occurred while updating the GL code.');
            this.disabled = false;
            this.textContent = 'Update Code';
        });
        });
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

    // Expose handleEditClick for external use
    window.handleEditClick = handleEditClick;

    // Expose modal functions globally (if not already exposed by gl-codes-list.js)
    if (!window.openModal) window.openModal = openModal;
    if (!window.closeModal) window.closeModal = closeModal;
})();