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
            expenses.map(ex => `<option value="${ex.id}">${ex.expense_name} (${ex.policy_months} mos policy)</option>`).join('');
        
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

    // Trigger the load immediately
    loadDropdownData();

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
    const addForm = document.getElementById('formAddAssetGroup');
    if(addForm) {
        addForm.addEventListener('submit', function(e) {
            e.preventDefault();
            console.log("Add form submitted - Ready for AJAX POST");
        });
    }

    const editForm = document.getElementById('formEditAssetGroup');
    if(editForm) {
        editForm.addEventListener('submit', function(e) {
            e.preventDefault();
            console.log("Edit form submitted - Ready for AJAX POST");
        });
    }

    const deleteForm = document.getElementById('formDeleteAssetGroup');
    if(deleteForm) {
        deleteForm.addEventListener('submit', function(e) {
            e.preventDefault();
            console.log("Delete form submitted - Ready for AJAX POST");
        });
    }
});