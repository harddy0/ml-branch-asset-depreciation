<div id="addExpenseTypeModal" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-slate-900/50 backdrop-blur-sm">
    <div class="bg-white rounded-2xl shadow-xl w-full max-w-md overflow-hidden">
        <div class="bg-slate-50 px-6 py-4 border-b border-slate-100 flex justify-between items-center">
            <h5 class="font-black text-slate-800 uppercase tracking-wide">Add Expense Type</h5>
            <button type="button" class="text-slate-400 hover:text-red-600 transition-colors" onclick="closeAddModal()">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>
        <div class="p-6">
            <form id="addExpenseTypeForm">
                <div class="mb-4">
                    <label class="block text-xs font-black text-slate-600 uppercase tracking-wide mb-1">Expense Name</label>
                    <input type="text" id="add_expense_name" name="expense_name" class="w-full px-4 py-2 border-2 border-slate-100 focus:border-slate-300 rounded-xl text-sm font-medium outline-none transition-all" placeholder="e.g., Laptop, Signage" required>
                </div>
                <div class="mb-4">
                    <label class="block text-xs font-black text-slate-600 uppercase tracking-wide mb-1">Category Type</label>
                    <select id="add_category_type" name="category_type" class="w-full px-4 py-2 border-2 border-slate-100 focus:border-slate-300 rounded-xl text-sm font-medium outline-none transition-all bg-white" required>
                        <option value="">Select Category</option>
                        <option value="MAINTENANCE_REPAIR">Maintenance & Repair</option>
                        <option value="INVENTORY_ITEM">Inventory Item</option>
                        <option value="JOB_ORDER">Job Order</option>
                    </select>
                </div>
                <div class="mb-4">
                    <label class="block text-xs font-black text-slate-600 uppercase tracking-wide mb-1">Policy Duration (Months)</label>
                    <input type="number" id="add_policy_months" name="policy_months" class="w-full px-4 py-2 border-2 border-slate-100 focus:border-slate-300 rounded-xl text-sm font-medium outline-none transition-all" required min="1">
                </div>
            </form>
        </div>
        <div class="bg-slate-50 px-6 py-4 border-t border-slate-100 flex justify-end gap-3">
            <button type="button" class="px-4 py-2 text-sm font-bold text-slate-500 hover:text-slate-700 transition-colors" onclick="closeAddModal()">Cancel</button>
            <button type="button" class="px-6 py-2 bg-[#ce1126] hover:bg-red-700 text-white text-sm font-bold rounded-xl shadow-md transition-all" onclick="submitAddForm()">Save</button>
        </div>
    </div>
</div>