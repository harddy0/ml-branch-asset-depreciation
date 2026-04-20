<div id="asset-group-add-modal" class="hidden fixed inset-0 z-50 overflow-y-auto bg-black/60 flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-lg overflow-hidden">
        <div class="bg-[#ce2216] flex justify-between items-center px-4 py-3">
            <h3 class="text-lg font-black text-white uppercase tracking-wider">Add New Asset Group</h3>
            <button type="button" onclick="closeModal('asset-group-add-modal')" class="text-red-100 hover:text-white text-2xl font-bold">&times;</button>
        </div>

        <form id="formAddAssetGroup">
            <div class="p-4 space-y-4">
                <div>
                    <label class="block text-xs font-black text-slate-600 tracking-wide mb-1">Group Name <span class="text-red-500">*</span></label>
                    <input type="text" name="group_name" placeholder="e.g., Branch Vaults" required class="w-full px-4 py-2 border-2 border-slate-100 focus:border-slate-300 rounded-xl text-sm font-medium outline-none transition-all">
                </div>
                
                <div>
                    <label class="block text-xs font-black text-slate-600 tracking-wide mb-1">Expense Type (Mother Policy) <span class="text-red-500">*</span></label>
                    <select name="expense_type_id" required class="w-full px-4 py-2 border-2 border-slate-100 focus:border-slate-300 rounded-xl text-sm font-medium outline-none transition-all bg-white">
                        <option value="">-- Select Expense Type --</option>
                        <option value="1">Dumb Option: Vaults (120 mos)</option>
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-black text-slate-600 tracking-wide mb-1">Actual Months <span class="text-red-500">*</span></label>
                    <input type="number" name="actual_months" placeholder="Must not exceed policy months" required class="w-full px-4 py-2 border-2 border-slate-100 focus:border-slate-300 rounded-xl text-sm font-medium outline-none transition-all">
                </div>

                <div>
                    <label class="block text-xs font-black text-slate-600 tracking-wide mb-1">Asset GL Code (Balance Sheet Side) <span class="text-red-500">*</span></label>
                    <select name="asset_gl_code" required class="w-full px-4 py-2 border-2 border-slate-100 focus:border-slate-300 rounded-xl text-sm font-medium outline-none transition-all bg-white">
                        <option value="">-- Select GL Code --</option>
                        <option value="1231101">Dumb Option: 1231101 - Asset</option>
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-black text-slate-600 tracking-wide mb-1">Expense GL Code (P&L Side) <span class="text-red-500">*</span></label>
                    <select name="expense_gl_code" required class="w-full px-4 py-2 border-2 border-slate-100 focus:border-slate-300 rounded-xl text-sm font-medium outline-none transition-all bg-white">
                        <option value="">-- Select GL Code --</option>
                        <option value="5678801">Dumb Option: 5678801 - Expense</option>
                    </select>
                </div>
            </div>
            
            <div class="bg-slate-50 px-4 py-3 border-t border-slate-200 flex justify-end gap-2">
                <button type="button" onclick="closeModal('asset-group-add-modal')" class="px-4 py-2 bg-slate-200 text-slate-800 rounded-xl hover:bg-slate-300 text-xs font-black tracking-widest transition">Cancel</button>
                <button type="submit" class="px-4 py-2 bg-[#ce2216] text-white rounded-xl hover:bg-red-700 text-xs font-black tracking-widest transition">Save</button>
            </div>
        </form>
    </div>
</div>