<div id="modal-issuance-report-details" class="fixed inset-0 z-[110] hidden">
    <div class="absolute inset-0 bg-slate-900/60 backdrop-blur-sm modal-backdrop opacity-0 transition-opacity" onclick="closeModal('modal-issuance-report-details')"></div>
    <div class="absolute inset-4 md:inset-10 flex items-center justify-center pointer-events-none">
        <div class="modal-panel bg-white rounded-2xl shadow-2xl w-full max-w-5xl flex flex-col min-h-0 overflow-hidden pointer-events-auto opacity-0 scale-95 transition-all" style="max-height: 94vh;">
            <div class="flex items-center justify-between px-6 py-2 border-b border-slate-100 bg-slate-50 shrink-0">
                <h3 class="text-lg font-bold text-slate-800">Row Details</h3>
                <button type="button" onclick="closeModal('modal-issuance-report-details')" class="p-2 text-slate-400 hover:text-slate-600 hover:bg-slate-200 rounded-lg transition-colors" aria-label="Close details">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>
            <div class="flex-1 min-h-0 overflow-y-auto p-6 bg-white">
                <div id="issuance-report-view-content"></div>
            </div>
            <div class="px-6 py-2 border-t border-slate-100 bg-white flex items-center justify-end shrink-0">
                <button type="button" onclick="closeModal('modal-issuance-report-details')" class="px-5 py-1 text-sm font-bold text-slate-600 bg-white border border-slate-300 hover:bg-slate-50 rounded-lg transition-colors">Close</button>
            </div>
        </div>
    </div>
</div>
