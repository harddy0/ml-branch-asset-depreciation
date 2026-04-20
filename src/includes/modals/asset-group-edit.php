<div id="asset-group-edit-modal" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-slate-900/50 backdrop-blur-sm p-4">
    <div class="bg-white rounded-2xl shadow-xl w-full max-w-lg overflow-hidden">
         <div class="bg-[#ce2216] flex justify-between items-center px-4 py-3">
            <h3 class="text-lg font-black text-white uppercase tracking-wider">Edit Asset Group</h3>
            <button type="button" onclick="closeModal('asset-group-edit-modal')" class="text-red-100 hover:text-white text-2xl font-bold">
                &times;
            </button>
        </div>
        
        <form id="formEditAssetGroup">
            <input type="hidden" id="edit_id" name="id">
            <div class="p-6 space-y-4">
                <div>
                    <label class="block text-xs font-black text-slate-600 tracking-wide mb-1">Group Name <span class="text-red-500">*</span></label>
                    <input type="text" id="edit_group_name" name="group_name" required class="w-full px-4 py-2 border-2 border-slate-100 focus:border-slate-300 rounded-xl text-sm font-medium outline-none transition-all">
                </div>
                
                <div>
                    <label class="block text-xs font-black text-slate-600 tracking-wide mb-1">Expense Type (Mother Policy) <span class="text-red-500">*</span></label>
                    <select id="edit_expense_type_id" name="expense_type_id" required class="w-full px-4 py-2 border-2 border-slate-100 focus:border-slate-300 rounded-xl text-sm font-medium outline-none transition-all bg-white">
                        <option value="">-- Select Expense Type --</option>
                        <option value="1">Dumb Option: Vaults (120 mos)</option>
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-black text-slate-600 tracking-wide mb-1">Actual Months <span class="text-red-500">*</span></label>
                    <input type="number" id="edit_actual_months" name="actual_months" required class="w-full px-4 py-2 border-2 border-slate-100 focus:border-slate-300 rounded-xl text-sm font-medium outline-none transition-all">
                </div>

                <div>
                    <label class="block text-xs font-black text-slate-600 tracking-wide mb-1">Asset GL Code <span class="text-red-500">*</span></label>
                    <select id="edit_asset_gl_code" name="asset_gl_code" required class="w-full px-4 py-2 border-2 border-slate-100 focus:border-slate-300 rounded-xl text-sm font-medium outline-none transition-all bg-white">
                        <option value="">-- Select GL Code --</option>
                        <option value="1231101">Dumb Option: 1231101 - Asset</option>
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-black text-slate-600 tracking-wide mb-1">Expense GL Code <span class="text-red-500">*</span></label>
                    <select id="edit_expense_gl_code" name="expense_gl_code" required class="w-full px-4 py-2 border-2 border-slate-100 focus:border-slate-300 rounded-xl text-sm font-medium outline-none transition-all bg-white">
                        <option value="">-- Select GL Code --</option>
                        <option value="5678801">Dumb Option: 5678801 - Expense</option>
                    </select>
                </div>
            </div>
            
            <div class="bg-slate-50 px-6 py-4 border-t border-slate-100 flex justify-end gap-3">
                <button type="button" onclick="closeModal('asset-group-edit-modal')" class="px-4 py-2 text-sm font-bold text-slate-500 hover:text-slate-700 transition-colors">Cancel</button>
                <button type="submit" class="px-6 py-2 bg-[#ce1126] hover:bg-red-700 text-white text-sm font-bold rounded-xl shadow-md transition-all">Update</button>
            </div>
        </form>
    </div>
</div>