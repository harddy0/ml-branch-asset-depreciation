<div id="modal-add-category" class="hidden fixed inset-0 z-50 items-center justify-center bg-black/40 backdrop-blur-sm p-4">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-lg animate-fadeIn">
        <div class="flex items-center justify-between px-7 py-4 border-b border-slate-200">
            <h2 class="text-sm font-black text-slate-800 uppercase tracking-widest">Add Asset Group</h2>
            <button type="button" onclick="closeModal('modal-add-category')" class="p-2 hover:bg-slate-100 rounded-lg transition-colors">
                <svg class="w-5 h-5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>

        <form method="POST" action="<?= BASE_URL ?>/public/actions/category_store.php" class="px-7 py-6 space-y-4">
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-black text-slate-500 uppercase tracking-widest mb-1.5">Actual Months <span class="text-red-600">*</span></label>
                    <input type="number" name="actual_months" id="category-add-actual-months" min="1" required
                        class="w-full border-2 border-slate-200 focus:border-slate-300 rounded-xl px-4 py-2.5 text-sm font-semibold text-slate-800 outline-none bg-slate-50 focus:bg-white transition-all"
                        placeholder="24">
                </div>
                <div>
                    <label class="block text-xs font-black text-slate-500 uppercase tracking-widest mb-1.5">Parent Asset Type <span class="text-red-600">*</span></label>
                    <select name="asset_code" id="category-add-asset-code" required
                        class="js-asset-type-select w-full border-2 border-slate-200 focus:border-slate-300 rounded-xl px-4 py-2.5 text-sm font-semibold text-slate-800 outline-none bg-slate-50 focus:bg-white transition-all"></select>
                </div>
            </div>

            <div>
                <label class="block text-xs font-black text-slate-500 uppercase tracking-widest mb-1.5">Group Code <span class="text-red-600">*</span></label>
                <input type="text" name="group_code" id="category-add-group-code" required maxlength="50"
                    class="js-code-input w-full border-2 border-slate-200 focus:border-slate-300 rounded-xl px-4 py-2.5 text-sm font-black font-mono text-slate-800 outline-none bg-slate-50 focus:bg-white transition-all"
                    placeholder="CD24MOS">
                <p class="text-[10px] text-slate-400 mt-1">Auto-generated as initials + months + MOS (example: CD24MOS). Single-word types use first 3 letters. Editable.</p>
            </div>

            <div>
                <label class="block text-xs font-black text-slate-500 uppercase tracking-widest mb-1.5">Group Name <span class="text-red-600">*</span></label>
                <input type="text" name="group_name" id="category-add-group-name" required maxlength="255"
                    class="w-full border-2 border-slate-200 focus:border-slate-300 rounded-xl px-4 py-2.5 text-sm font-semibold text-slate-800 outline-none bg-slate-50 focus:bg-white transition-all"
                    placeholder="Cash Drawer (24mos)">
                <p class="text-[10px] text-slate-400 mt-1">Auto-generated after months entry, but you can rearrange/edit it.</p>
            </div>

            <div class="flex gap-3 pt-2">
                <button type="button" onclick="closeModal('modal-add-category')" class="flex-1 border-2 border-slate-200 text-slate-600 hover:border-slate-300 hover:bg-slate-100 font-black text-xs uppercase tracking-widest py-3 rounded-xl transition-all">Cancel</button>
                <button type="submit" class="flex-1 bg-[#ce1126] hover:bg-red-700 text-white font-black text-xs uppercase tracking-widest py-3 rounded-xl shadow-lg shadow-red-100 transition-all">Save</button>
            </div>
        </form>
    </div>
</div>

<div id="modal-edit-category" class="hidden fixed inset-0 z-50 items-center justify-center bg-black/40 backdrop-blur-sm p-4">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-lg animate-fadeIn">
        <div class="flex items-center justify-between px-7 py-4 border-b border-slate-200">
            <h2 class="text-sm font-black text-slate-800 uppercase tracking-widest">Edit Asset Group</h2>
            <button type="button" onclick="closeModal('modal-edit-category')" class="p-2 hover:bg-slate-100 rounded-lg transition-colors">
                <svg class="w-5 h-5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>

        <form method="POST" action="<?= BASE_URL ?>/public/actions/category_update.php" class="px-7 py-6 space-y-4">
            <input type="hidden" name="id" id="category-edit-id">

            <div>
                <label class="block text-xs font-black text-slate-500 uppercase tracking-widest mb-1.5">Group Code <span class="text-red-600">*</span></label>
                <input type="text" name="group_code" id="category-edit-group-code" required maxlength="50"
                    class="js-code-input w-full border-2 border-slate-200 focus:border-slate-300 rounded-xl px-4 py-2.5 text-sm font-black font-mono text-slate-800 outline-none bg-slate-50 focus:bg-white transition-all">
            </div>

            <div>
                <label class="block text-xs font-black text-slate-500 uppercase tracking-widest mb-1.5">Group Name <span class="text-red-600">*</span></label>
                <input type="text" name="group_name" id="category-edit-group-name" required maxlength="255"
                    class="w-full border-2 border-slate-200 focus:border-slate-300 rounded-xl px-4 py-2.5 text-sm font-semibold text-slate-800 outline-none bg-slate-50 focus:bg-white transition-all">
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-black text-slate-500 uppercase tracking-widest mb-1.5">Actual Months <span class="text-red-600">*</span></label>
                    <input type="number" name="actual_months" id="category-edit-actual-months" min="1" required
                        class="w-full border-2 border-slate-200 focus:border-slate-300 rounded-xl px-4 py-2.5 text-sm font-semibold text-slate-800 outline-none bg-slate-50 focus:bg-white transition-all">
                </div>
                <div>
                    <label class="block text-xs font-black text-slate-500 uppercase tracking-widest mb-1.5">Parent Asset Type <span class="text-red-600">*</span></label>
                    <select name="asset_code" id="category-edit-asset-code" required
                        class="js-asset-type-select w-full border-2 border-slate-200 focus:border-slate-300 rounded-xl px-4 py-2.5 text-sm font-semibold text-slate-800 outline-none bg-slate-50 focus:bg-white transition-all"></select>
                </div>
            </div>

            <div class="flex gap-3 pt-2">
                <button type="button" onclick="closeModal('modal-edit-category')" class="flex-1 border-2 border-slate-200 text-slate-600 hover:border-slate-300 hover:bg-slate-100 font-black text-xs uppercase tracking-widest py-3 rounded-xl transition-all">Cancel</button>
                <button type="submit" class="flex-1 bg-[#ce1126] hover:bg-red-700 text-white font-black text-xs uppercase tracking-widest py-3 rounded-xl shadow-lg shadow-red-100 transition-all">Save</button>
            </div>
        </form>
    </div>
</div>
