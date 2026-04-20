<div id="modal-delete-gl-code" class="fixed inset-0 z-[100] hidden flex items-center justify-center">
    <div class="absolute inset-0 bg-slate-900/40 backdrop-blur-sm transition-opacity opacity-0 modal-backdrop" onclick="closeModal('modal-delete-gl-code')"></div>

    <div class="relative w-full max-w-md px-4 z-10 flex items-center justify-center">
        <div class="bg-white rounded-2xl text-left overflow-hidden shadow-2xl transform transition-all w-full scale-95 opacity-0 modal-panel ">

             <div class="bg-[#ce2216] flex justify-between items-center px-4 py-3">
                <h3 class="text-lg font-black text-white uppercase tracking-wider">
                    Delete GL Code
                </h3>
                <button type="button" onclick="closeModal('modal-delete-gl-code')"  class="text-red-100 hover:text-white text-2xl font-bold">
                      &times;
                </button>
            </div>

            <div class="p-6">
                <div class="flex items-start gap-3">
                    <div class="flex-shrink-0">
                       
                    </div>
                    <div class="flex-1">
                        <h4 class="text-sm font-bold text-slate-800 mb-2">Are you sure you want to delete this GL code?</h4>
                        <p class="text-sm text-slate-600 mb-4">
                            The GL code <strong id="delete-gl-code-display"></strong> will be permanently removed.
                        </p>
                    </div>
                </div>

                <div class="mt-4 flex gap-3">
                    <button type="button" onclick="closeModal('modal-delete-gl-code')"
                        class="flex-1 bg-white border-2 border-slate-200 text-slate-600 hover:bg-slate-50 text-xs font-black tracking-widest py-2.5 rounded-xl transition-colors">
                        Cancel
                    </button>
                    <button type="button" id="btn-confirm-delete-gl-code"
                        class="flex-1 bg-red-600 hover:bg-red-700 text-white text-xs font-black tracking-widest py-2.5 rounded-xl shadow-md shadow-red-200 transition-colors">
                        Delete
                    </button>
                </div>
            </div>

        </div>
    </div>
</div>