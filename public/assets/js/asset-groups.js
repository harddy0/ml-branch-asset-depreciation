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

document.addEventListener('DOMContentLoaded', function() {
    console.log("Asset Groups JS Initialized - Tailwind UI Ready");

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
        if (e.key !== 'Escape') {
            return;
        }

        ['asset-group-add-modal', 'asset-group-edit-modal', 'asset-group-delete-modal'].forEach(function(id) {
            const modal = document.getElementById(id);
            if (modal && !modal.classList.contains('hidden')) {
                modal.classList.add('hidden');
            }
        });
    });

    // Form Event Listeners (Prepared for Phase 3 API Hookups)
    const addForm = document.getElementById('formAddAssetGroup');
    if(addForm) {
        addForm.addEventListener('submit', function(e) {
            e.preventDefault();
            console.log("Add form submitted - Ready for AJAX POST");
            // API logic will go here
        });
    }

    const editForm = document.getElementById('formEditAssetGroup');
    if(editForm) {
        editForm.addEventListener('submit', function(e) {
            e.preventDefault();
            console.log("Edit form submitted - Ready for AJAX POST");
            // API logic will go here
        });
    }

    const deleteForm = document.getElementById('formDeleteAssetGroup');
    if(deleteForm) {
        deleteForm.addEventListener('submit', function(e) {
            e.preventDefault();
            console.log("Delete form submitted - Ready for AJAX POST");
            // API logic will go here
        });
    }
});