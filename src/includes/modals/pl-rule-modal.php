<div id="modal-add-pl-rule" class="hidden fixed inset-0 z-50 items-center justify-center bg-black/40 backdrop-blur-sm p-4">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-lg animate-fadeIn">
        <div class="flex items-center justify-between px-7 py-4 border-b border-slate-200">
            <h2 class="text-sm font-black text-slate-800 uppercase tracking-widest">Add P&amp;L Policy</h2>
            <button type="button" onclick="closeModal('modal-add-pl-rule')" class="p-2 hover:bg-slate-100 rounded-lg transition-colors">
                <svg class="w-5 h-5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>

        <form method="POST" action="<?= BASE_URL ?>/public/actions/pl_rule_store.php" class="px-7 py-6 space-y-4">
            <div>
                <label class="block text-xs font-black text-slate-500 uppercase tracking-widest mb-1.5">GL Code <span class="text-red-600">*</span></label>
                <input type="text" name="depreciation_code" id="pl-rule-add-code" required maxlength="20"
                    class="js-code-input w-full border-2 border-slate-200 focus:border-slate-300 rounded-xl px-4 py-2.5 text-sm font-black font-mono text-slate-800 outline-none bg-slate-50 focus:bg-white transition-all"
                    placeholder="5151006">
            </div>

            <div>
                <label class="block text-xs font-black text-slate-500 uppercase tracking-widest mb-1.5">Description <span class="text-red-600">*</span></label>
                <input type="text" name="description" id="pl-rule-add-description" required maxlength="255"
                    class="w-full border-2 border-slate-200 focus:border-slate-300 rounded-xl px-4 py-2.5 text-sm font-semibold text-slate-800 outline-none bg-slate-50 focus:bg-white transition-all"
                    placeholder="D/E - Computers and Peripherals">
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-black text-slate-500 uppercase tracking-widest mb-1.5">Limit Months <span class="text-red-600">*</span></label>
                    <input type="number" name="limit_months" id="pl-rule-add-limit" min="1" required
                        class="w-full border-2 border-slate-200 focus:border-slate-300 rounded-xl px-4 py-2.5 text-sm font-semibold text-slate-800 outline-none bg-slate-50 focus:bg-white transition-all"
                        placeholder="24">
                </div>
                <div>
                    <label class="block text-xs font-black text-slate-500 uppercase tracking-widest mb-1.5">Rule Type <span class="text-red-600">*</span></label>
                    <select name="rule_type" id="pl-rule-add-type" required
                        class="w-full border-2 border-slate-200 focus:border-slate-300 rounded-xl px-4 py-2.5 text-sm font-semibold text-slate-800 outline-none bg-slate-50 focus:bg-white transition-all">
                        <option value="EXACT">EXACT</option>
                        <option value="MAXIMUM">MAXIMUM</option>
                        <option value="MINIMUM">MINIMUM</option>
                    </select>
                </div>
            </div>

            <div class="flex gap-3 pt-2">
                <button type="button" onclick="closeModal('modal-add-pl-rule')" class="flex-1 border-2 border-slate-200 text-slate-600 hover:border-slate-300 hover:bg-slate-100 font-black text-xs uppercase tracking-widest py-3 rounded-xl transition-all">Cancel</button>
                <button type="submit" class="flex-1 bg-[#ce1126] hover:bg-red-700 text-white font-black text-xs uppercase tracking-widest py-3 rounded-xl shadow-lg shadow-red-100 transition-all">Save</button>
            </div>
        </form>
    </div>
</div>

<div id="modal-edit-pl-rule" class="hidden fixed inset-0 z-50 items-center justify-center bg-black/40 backdrop-blur-sm p-4">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-lg animate-fadeIn">
        <div class="flex items-center justify-between px-7 py-4 border-b border-slate-200">
            <h2 class="text-sm font-black text-slate-800 uppercase tracking-widest">Edit P&amp;L Policy</h2>
            <button type="button" onclick="closeModal('modal-edit-pl-rule')" class="p-2 hover:bg-slate-100 rounded-lg transition-colors">
                <svg class="w-5 h-5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>

        <form method="POST" action="<?= BASE_URL ?>/public/actions/pl_rule_update.php" class="px-7 py-6 space-y-4">
            <input type="hidden" name="original_depreciation_code" id="pl-rule-edit-original-code">

            <div>
                <label class="block text-xs font-black text-slate-500 uppercase tracking-widest mb-1.5">GL Code <span class="text-red-600">*</span></label>
                <input type="text" name="depreciation_code" id="pl-rule-edit-code" required maxlength="20"
                    class="js-code-input w-full border-2 border-slate-200 focus:border-slate-300 rounded-xl px-4 py-2.5 text-sm font-black font-mono text-slate-800 outline-none bg-slate-50 focus:bg-white transition-all">
            </div>

            <div>
                <label class="block text-xs font-black text-slate-500 uppercase tracking-widest mb-1.5">Description <span class="text-red-600">*</span></label>
                <input type="text" name="description" id="pl-rule-edit-description" required maxlength="255"
                    class="w-full border-2 border-slate-200 focus:border-slate-300 rounded-xl px-4 py-2.5 text-sm font-semibold text-slate-800 outline-none bg-slate-50 focus:bg-white transition-all">
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-black text-slate-500 uppercase tracking-widest mb-1.5">Limit Months <span class="text-red-600">*</span></label>
                    <input type="number" name="limit_months" id="pl-rule-edit-limit" min="1" required
                        class="w-full border-2 border-slate-200 focus:border-slate-300 rounded-xl px-4 py-2.5 text-sm font-semibold text-slate-800 outline-none bg-slate-50 focus:bg-white transition-all">
                </div>
                <div>
                    <label class="block text-xs font-black text-slate-500 uppercase tracking-widest mb-1.5">Rule Type <span class="text-red-600">*</span></label>
                    <select name="rule_type" id="pl-rule-edit-type" required
                        class="w-full border-2 border-slate-200 focus:border-slate-300 rounded-xl px-4 py-2.5 text-sm font-semibold text-slate-800 outline-none bg-slate-50 focus:bg-white transition-all">
                        <option value="EXACT">EXACT</option>
                        <option value="MAXIMUM">MAXIMUM</option>
                        <option value="MINIMUM">MINIMUM</option>
                    </select>
                </div>
            </div>

            <div class="flex gap-3 pt-2">
                <button type="button" onclick="closeModal('modal-edit-pl-rule')" class="flex-1 border-2 border-slate-200 text-slate-600 hover:border-slate-300 hover:bg-slate-100 font-black text-xs uppercase tracking-widest py-3 rounded-xl transition-all">Cancel</button>
                <button type="submit" class="flex-1 bg-[#ce1126] hover:bg-red-700 text-white font-black text-xs uppercase tracking-widest py-3 rounded-xl shadow-lg shadow-red-100 transition-all">Save</button>
            </div>
        </form>
    </div>
</div>
