<div id="modal-asset-depr-details"
     class="hidden fixed inset-0 z-50 items-center justify-center bg-black/50 backdrop-blur-sm p-4">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-7xl animate-fadeIn flex flex-col" style="max-height:94vh">

        <div class="flex items-center justify-between px-7 py-3 border-b-2 border-[#ce2216] shrink-0">
            <div class="flex items-center gap-3">
                <div>
                    <h2 class="text-base font-black text-slate-800 uppercase tracking-tight">Asset Details</h2>
                    <p id="depr-details-subtitle" class="text-xs text-slate-400 mt-0.5"></p>
                </div>
                <span id="depr-edit-badge"
                      class="hidden inline-flex items-center gap-1 bg-red-100 text-[#ce1126] text-[10px] font-black px-2.5 py-1 rounded-full uppercase tracking-wider">
                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                    </svg>
                    Editing
                </span>
            </div>
            <button type="button" onclick="closeAssetDepreciationDetails()"
                    class="p-2 hover:bg-slate-100 rounded-lg transition-colors">
                <svg class="w-5 h-5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>

        <div id="asset-depr-detail-content" class="overflow-auto flex-1 px-7 py-6 space-y-6 bg-slate-50/40">
        </div>

        <div class="px-7 py-3 border-t border-slate-100 shrink-0 flex items-center justify-between gap-3 bg-white rounded-b-2xl">
            <p id="depr-unsaved-hint" class="hidden text-xs text-[#ce1126] font-semibold flex items-center gap-1.5">
                <svg class="w-3.5 h-3.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M12 9v2m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                Unsaved changes — click Save to apply.
            </p>
            <div class="flex gap-3 ml-auto">
                <button type="button" id="depr-btn-edit"
                        onclick="enableDeprEdit()"
                        class="px-5 py-2.5 bg-slate-100 hover:bg-slate-200 text-slate-700 font-black text-xs
                               uppercase tracking-widest rounded-xl transition-colors flex items-center gap-2">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                    </svg>
                    Edit
                </button>
                <button type="button" onclick="closeAssetDepreciationDetails()"
                       id="depr-btn-close"
                       class="border-2 border-slate-200 text-slate-600 hover:border-slate-300 hover:bg-slate-100
                            font-black text-xs uppercase tracking-widest px-5 py-2.5 rounded-xl transition-all">
                    Close
                </button>
                <button type="button" id="depr-btn-cancel-edit"
                        onclick="cancelDeprEdit()"
                        class="hidden border-2 border-slate-200 text-slate-600 hover:bg-slate-100
                               font-black text-xs uppercase tracking-widest px-5 py-2.5 rounded-xl transition-all">
                    Discard
                </button>
                <button type="button" id="depr-btn-save"
                        onclick="saveDeprEdit()"
                        class="hidden bg-[#ce1126] hover:bg-red-700 text-white font-black text-xs
                               uppercase tracking-widest px-6 py-2.5 rounded-xl shadow-lg shadow-red-100
                               hover:-translate-y-0.5 transition-all flex items-center gap-2">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                    </svg>
                    Save Changes
                </button>
            </div>
        </div>
    </div>
</div>