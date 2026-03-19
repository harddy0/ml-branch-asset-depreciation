<div id="modal-asset-depr-details"
     class="hidden fixed inset-0 z-50 items-center justify-center bg-black/50 backdrop-blur-sm p-4">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-7xl animate-fadeIn flex flex-col" style="max-height:94vh">

        <div class="flex items-center justify-between px-7 py-3 border-b-2 border-[#ce2216] shrink-0">
            <div>
                <h2 class="text-base font-black text-slate-800 uppercase tracking-tight">Asset Depreciation Details</h2>
            </div>
            <button type="button" onclick="closeAssetDepreciationDetails()"
                    class="p-2 hover:bg-slate-100 rounded-lg transition-colors">
                <svg class="w-5 h-5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>

        <div id="asset-depr-detail-content" class="overflow-auto flex-1 px-7 py-6 space-y-5 bg-slate-50/40">
            <!-- Populated by JS -->
        </div>

        <div class="px-7 py-2 border-t border-slate-100 shrink-0 flex justify-end gap-3 bg-white rounded-b-2xl">
            <button type="button" onclick="closeAssetDepreciationDetails()"
                   class="border-2 border-slate-200 text-slate-600 hover:border-slate-300 hover:bg-slate-300
                        font-black text-xs uppercase tracking-widest px-5 py-3 rounded-xl transition-all">
                Close
            </button>
        </div>
    </div>
</div>
