<div id="category-modal" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-slate-900/50 backdrop-blur-sm">
    <div class="bg-white rounded-2xl shadow-xl w-full max-w-md overflow-hidden flex flex-col">
        <form id="category-form" action="<?= BASE_URL ?>/public/actions/pl_rule_store.php" method="POST">
            <div class="px-6 py-4 border-b border-slate-100 flex justify-between items-center bg-slate-50">
                <h3 id="category-modal-title" class="text-sm font-black text-slate-800 uppercase tracking-widest">Add Category</h3>
                <button type="button" class="close-modal text-slate-400 hover:text-red-500 transition-colors">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>

            <div class="p-6 space-y-4">
                <input type="hidden" name="original_depreciation_code" id="original_depreciation_code">
                
                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">Category Code</label>
                    <input type="text" name="depreciation_code" id="depreciation_code" required 
                           class="w-full px-4 py-2 bg-slate-50 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-red-500 focus:border-red-500 transition-all uppercase placeholder:normal-case placeholder:text-slate-400" 
                           placeholder="e.g. COMP-EQ">
                    <p id="code-locked-hint" class="hidden mt-1 text-[11px] font-bold uppercase tracking-wide text-amber-700">
                        Category Code is locked in Edit mode.
                    </p>
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">Description</label>
                    <input type="text" name="description" id="description" required 
                           class="w-full px-4 py-2 bg-slate-50 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-red-500 focus:border-red-500 transition-all" 
                           placeholder="e.g. Computer Equipment">
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">Useful Life (Months)</label>
                    <input type="number" id="input_months" name="months" required min="1" step="1"
                           class="w-full px-4 py-2 bg-slate-50 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-red-500 focus:border-red-500 transition-all" 
                           placeholder="e.g. 60">
                    <p id="months_to_years_display" class="mt-1 text-[11px] font-medium text-slate-500">Equivalent: 0.00 years</p>
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">GL Account</label>
                    <div class="relative">
                        <input type="text" id="gl_code_picker" autocomplete="off" required
                               class="w-full px-4 py-2 bg-white border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-red-500 focus:border-red-500 transition-all"
                               placeholder="Search and select GL Account...">
                        <div id="gl_code_dropdown" class="hidden absolute z-20 mt-1 w-full max-h-56 overflow-y-auto rounded-lg border border-slate-200 bg-white shadow-lg"></div>
                    </div>
                    <input type="hidden" name="gl_code" id="gl_code_select" value="">
                    <p class="mt-1 text-[11px] font-medium text-slate-500">Type code, description, or DEBIT/CREDIT then select from the dropdown list.</p>
                </div>
            </div>

            <div class="px-6 py-4 bg-slate-50 border-t border-slate-100 flex justify-end gap-3">
                <button type="button" class="close-modal px-4 py-2 text-sm font-bold text-slate-600 bg-white border border-slate-200 rounded-lg hover:bg-slate-50 transition-colors">Cancel</button>
                <button type="submit" class="px-4 py-2 text-sm font-bold text-white bg-[#ce1126] rounded-lg hover:bg-red-700 transition-colors">Save Category</button>
            </div>
        </form>
    </div>
</div>