<div id="modal-edit-category"
     class="hidden fixed inset-0 z-50 items-center justify-center bg-black/40 backdrop-blur-sm p-4">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md animate-fadeIn">

        <div class="flex items-center justify-between px-7 py-5 border-b border-slate-100">
            <div>
                <h2 class="text-base font-black text-slate-800 uppercase tracking-tight">Edit Category</h2>
                <p class="text-xs text-slate-400 mt-0.5">Changes affect future depreciation calculations.</p>
            </div>
            <button onclick="closeModal('modal-edit-category')"
                class="p-2 hover:bg-slate-100 rounded-lg transition-colors">
                <svg class="w-5 h-5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>

        <form method="POST" action="<?= BASE_URL ?>/public/actions/category_update.php"
              class="px-7 py-6 space-y-5">
            <input type="hidden" name="id" id="edit-cat-id">

            <div>
                <label class="block text-xs font-black text-slate-500 uppercase tracking-widest mb-1.5">
                    Category Name <span class="text-red-500">*</span>
                </label>
                <input type="text" name="category_name" id="edit-cat-name" required
                    class="w-full border-2 border-slate-200 focus:border-blue-500 rounded-xl px-4 py-2.5
                           text-sm font-bold text-slate-800 outline-none bg-slate-50 focus:bg-white transition-all">
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-black text-slate-500 uppercase tracking-widest mb-1.5">
                        Category Code <span class="text-red-500">*</span>
                    </label>
                    <input type="text" name="category_code" id="edit-cat-code" required maxlength="10"
                        class="input-uppercase w-full border-2 border-slate-200 focus:border-blue-500 rounded-xl px-4 py-2.5
                               text-sm font-black font-mono text-[#ce1126] outline-none bg-red-50/50 focus:bg-white transition-all">
                    <p class="text-[10px] text-slate-400 mt-1">Changing this updates linked assets.</p>
                </div>
                <div>
                    <label class="block text-xs font-black text-slate-500 uppercase tracking-widest mb-1.5">
                        Asset Life <span class="text-red-500">*</span>
                    </label>
                    <div class="relative">
                        <input type="number" name="asset_life_months" id="edit-cat-life" required min="1" max="999"
                            class="w-full border-2 border-slate-200 focus:border-blue-500 rounded-xl px-4 py-2.5 pr-12
                                   text-sm font-black text-slate-800 outline-none bg-slate-50 focus:bg-white transition-all">
                        <span class="absolute right-3.5 top-1/2 -translate-y-1/2 text-[10px] font-bold text-slate-400 pointer-events-none">mo</span>
                    </div>
                    <p id="edit-years-hint" class="text-[10px] text-blue-500 font-bold mt-1 hidden"></p>
                </div>
            </div>

            <div class="flex items-start gap-2.5 bg-amber-50 border border-amber-200 rounded-xl px-4 py-3">
                <svg class="w-4 h-4 text-amber-500 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/>
                </svg>
                <p class="text-[11px] font-semibold text-amber-700 leading-relaxed">
                    Editing asset life affects future depreciation runs only. Posted records are not retroactively changed.
                </p>
            </div>

            <div class="flex gap-3">
                <button type="button" onclick="closeModal('modal-edit-category')"
                    class="flex-1 border-2 border-slate-200 text-slate-600 font-black text-xs uppercase tracking-widest
                           py-3 rounded-xl transition-all hover:bg-slate-50">
                    Cancel
                </button>
                <button type="submit"
                    class="flex-1 bg-blue-600 hover:bg-blue-700 text-white font-black text-xs uppercase tracking-widest
                           py-3 rounded-xl shadow-lg shadow-blue-100 hover:-translate-y-0.5 transition-all">
                    Save Changes
                </button>
            </div>
        </form>
    </div>
</div>