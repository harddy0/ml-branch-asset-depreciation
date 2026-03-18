<div id="modal-import-errors" class="hidden fixed inset-0 z-50 items-center justify-center bg-black/40 backdrop-blur-sm p-4">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-2xl animate-fadeIn flex flex-col max-h-[85vh]">
        
        <div class="flex items-center justify-between px-7 py-5 border-b border-slate-100 shrink-0">
            <div class="flex items-center gap-4">
                <div class="w-12 h-12 bg-red-100 rounded-full flex items-center justify-center shrink-0">
                    <svg class="w-6 h-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                              d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                    </svg>
                </div>
                <div>
                    <h2 class="text-lg font-black text-slate-800 uppercase tracking-tight">Import Rejected</h2>
                    <p class="text-xs text-slate-500 mt-0.5">
                        Please fix the <span class="font-bold text-red-600"><?= count($importErrors) ?> error(s)</span> below and re-upload the file.
                    </p>
                </div>
            </div>
            <button onclick="closeModal('modal-import-errors')" class="p-2 hover:bg-slate-100 rounded-lg transition-colors">
                <svg class="w-5 h-5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>

        <div class="px-7 py-6 overflow-y-auto bg-slate-50 border-b border-slate-100 flex-1">
            <ul class="space-y-2">
                <?php foreach ($importErrors as $err): ?>
                    <li class="text-[13px] font-medium text-slate-700 bg-white border border-red-200/60 shadow-sm px-4 py-3 rounded-xl flex items-start gap-3">
                        <svg class="w-4 h-4 text-red-500 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                        <span><?= $err ?></span>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>

        <div class="px-7 py-5 shrink-0 flex justify-end bg-white rounded-b-2xl">
            <button onclick="closeModal('modal-import-errors')" 
                class="px-6 py-2.5 bg-slate-100 hover:bg-slate-200 text-slate-600 font-black text-xs uppercase tracking-widest rounded-xl transition-colors">
                Close & Fix Data
            </button>
        </div>
    </div>
</div>