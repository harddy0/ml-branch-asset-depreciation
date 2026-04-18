<div id="modal-delete-gl-code" class="fixed inset-0 z-[100] hidden flex items-center justify-center">
    <div class="absolute inset-0 bg-slate-900/40 backdrop-blur-sm transition-opacity opacity-0 modal-backdrop" onclick="closeModal('modal-delete-gl-code')"></div>

    <div class="relative w-full max-w-md px-4 z-10 flex items-center justify-center">
        <div class="bg-white rounded-2xl text-left overflow-hidden shadow-2xl transform transition-all w-full scale-95 opacity-0 modal-panel border border-slate-100">

            <div class="bg-slate-50 px-6 py-4 border-b border-slate-100 flex items-center justify-between">
                <h3 class="text-sm font-black text-slate-800 uppercase tracking-widest">
                    Delete GL Code
                </h3>
                <button type="button" onclick="closeModal('modal-delete-gl-code')" class="text-slate-400 hover:text-slate-500 transition-colors">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>

            <div class="p-6">
                <div class="flex items-start gap-3">
                    <div class="flex-shrink-0">
                        <svg class="w-6 h-6 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z"/>
                        </svg>
                    </div>
                    <div class="flex-1">
                        <h4 class="text-sm font-bold text-slate-800 mb-2">Are you sure you want to delete this GL code?</h4>
                        <p class="text-sm text-slate-600 mb-4">
                            This action cannot be undone. The GL code <strong id="delete-gl-code-display"></strong> will be permanently removed from the system.
                        </p>
                    </div>
                </div>

                <div class="mt-8 flex gap-3">
                    <button type="button" onclick="closeModal('modal-delete-gl-code')"
                        class="flex-1 bg-white border-2 border-slate-200 text-slate-600 hover:bg-slate-50 text-xs font-black uppercase tracking-widest py-2.5 rounded-xl transition-colors">
                        Cancel
                    </button>
                    <button type="button" id="btn-confirm-delete-gl-code"
                        class="flex-1 bg-red-600 hover:bg-red-700 text-white text-xs font-black uppercase tracking-widest py-2.5 rounded-xl shadow-md shadow-red-200 transition-colors">
                        Delete Code
                    </button>
                </div>
            </div>

        </div>
    </div>
</div>