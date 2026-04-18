<div id="modal-edit-gl-code" class="fixed inset-0 z-[100] hidden">
    <div class="absolute inset-0 bg-slate-900/40 backdrop-blur-sm transition-opacity opacity-0 modal-backdrop" data-modal-close="modal-edit-gl-code"></div>

    <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:p-0">
        <div class="relative bg-white rounded-2xl text-left overflow-hidden shadow-2xl transform transition-all sm:my-8 sm:max-w-md w-full scale-95 opacity-0 modal-panel border border-slate-100">

            <div class="bg-slate-50 px-6 py-4 border-b border-slate-100 flex items-center justify-between">
                <h3 class="text-sm font-black text-slate-800 uppercase tracking-widest">
                    Edit GL Code
                </h3>
                <button type="button" data-modal-close="modal-edit-gl-code" class="text-slate-400 hover:text-slate-500 transition-colors">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>

            <form id="form-edit-gl-code" class="p-6">
                <div class="space-y-4">

                    <div>
                        <label class="block text-[11px] font-black text-slate-500 uppercase tracking-widest mb-1.5">
                            GL Code
                        </label>
                        <input type="text" id="edit-gl-code" readonly
                            class="w-full px-4 py-2 bg-slate-100 border border-slate-200 rounded-xl text-sm font-bold text-slate-500
                                   cursor-not-allowed uppercase">
                    </div>

                    <div>
                        <label class="block text-[11px] font-black text-slate-500 uppercase tracking-widest mb-1.5">
                            Description <span class="text-red-500">*</span>
                        </label>
                        <input type="text" id="edit-description" required
                            class="w-full px-4 py-2 bg-slate-50 border border-slate-200 rounded-xl text-sm font-bold text-slate-700
                                   focus:bg-white focus:border-red-500 focus:ring-2 focus:ring-red-200 outline-none transition-all placeholder:font-medium"
                            placeholder="e.g. A/D - Office Equipment">
                    </div>

                    <div>
                        <label class="block text-[11px] font-black text-slate-500 uppercase tracking-widest mb-1.5">
                            Account Type <span class="text-red-500">*</span>
                        </label>
                        <select id="edit-account-type" required
                            class="w-full px-4 py-2 bg-slate-50 border border-slate-200 rounded-xl text-sm font-bold text-slate-700
                                   focus:bg-white focus:border-red-500 focus:ring-2 focus:ring-red-200 outline-none transition-all">
                            <option value="DEBIT">DEBIT</option>
                            <option value="CREDIT">CREDIT</option>
                        </select>
                    </div>
                </div>

                <div class="mt-8 flex gap-3">
                    <button type="button" data-modal-close="modal-edit-gl-code"
                        class="flex-1 bg-white border-2 border-slate-200 text-slate-600 hover:bg-slate-50 text-xs font-black uppercase tracking-widest py-2.5 rounded-xl transition-colors">
                        Cancel
                    </button>
                    <button type="button" id="btn-update-gl-code"
                        class="flex-1 bg-[#ce1126] hover:bg-red-700 text-white text-xs font-black uppercase tracking-widest py-2.5 rounded-xl shadow-md shadow-red-200 transition-colors">
                        Update Code
                    </button>
                </div>
            </form>

        </div>
    </div>
</div>