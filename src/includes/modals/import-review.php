<div id="modal-import-review" class="fixed inset-0 z-[100] hidden">
    <div class="absolute inset-0 bg-slate-900/60 backdrop-blur-sm" onclick="closeImportReview()"></div>
    <div class="absolute inset-4 md:inset-10 flex items-center justify-center pointer-events-none">
        
        <div class="bg-white rounded-2xl shadow-2xl w-full h-full max-w-screen-2xl flex flex-col pointer-events-auto overflow-hidden">
            
            <div class="flex items-center justify-between px-6 py-4 border-b border-slate-100 bg-slate-50 shrink-0">
                <div>
                    <h3 class="text-lg font-bold text-slate-800">Review Import Data</h3>
                    <p class="text-sm font-medium text-slate-500 mt-0.5">Please review the parsed rows before confirming insertion.</p>
                </div>
                <button type="button" onclick="closeImportReview()" class="p-2 text-slate-400 hover:text-slate-600 hover:bg-slate-200 rounded-lg transition-colors">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>

            <div id="review-error-note" class="hidden shrink-0 bg-red-50 px-6 py-3 border-b border-red-100 flex items-center gap-3">
                <div class="w-8 h-8 rounded-full bg-red-100 flex items-center justify-center shrink-0">
                    <svg class="w-4 h-4 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                </div>
                <p id="review-error-note-text" class="text-sm font-semibold text-red-800"></p>
            </div>

            <div class="flex-1 overflow-auto bg-slate-50/50">
                <table class="w-full text-left border-collapse min-w-max">
                    <thead class="sticky top-0 z-10 bg-white shadow-sm ring-1 ring-slate-100">
                        <tr>
                            <th class="px-3 py-3 w-10 text-center border-b border-slate-200">
                                <input type="checkbox" id="review-select-all" class="w-3.5 h-3.5 rounded border-slate-300 text-[#ce1126] focus:ring-red-200">
                            </th>
                            <th class="px-3 py-3 text-[10px] font-bold uppercase tracking-wider text-slate-500 border-b border-slate-200">Status</th>
                            <th class="px-3 py-3 text-[10px] font-bold uppercase tracking-wider text-slate-500 border-b border-slate-200">Serial No</th>
                            <th class="px-3 py-3 text-[10px] font-bold uppercase tracking-wider text-slate-500 border-b border-slate-200">Description</th>
                            <th class="px-3 py-3 text-[10px] font-bold uppercase tracking-wider text-slate-500 border-b border-slate-200">Ref No</th>
                            <th class="px-3 py-3 text-[10px] font-bold uppercase tracking-wider text-slate-500 border-b border-slate-200">Qty</th>
                            <th class="px-3 py-3 text-[10px] font-bold uppercase tracking-wider text-slate-500 border-b border-slate-200">Property</th>
                            <th class="px-3 py-3 text-[10px] font-bold uppercase tracking-wider text-slate-500 border-b border-slate-200">GL Group</th>
                            <th class="px-3 py-3 text-[10px] font-bold uppercase tracking-wider text-slate-500 border-b border-slate-200 text-right">Acq. Cost</th>
                            <th class="px-3 py-3 text-[10px] font-bold uppercase tracking-wider text-slate-500 border-b border-slate-200">Date Rec.</th>
                            <th class="px-3 py-3 text-[10px] font-bold uppercase tracking-wider text-slate-500 border-b border-slate-200">Main Zone</th>
                            <th class="px-3 py-3 text-[10px] font-bold uppercase tracking-wider text-slate-500 border-b border-slate-200">Sub-Zone</th>
                            <th class="px-3 py-3 text-[10px] font-bold uppercase tracking-wider text-slate-500 border-b border-slate-200">Region</th>
                            <th class="px-3 py-3 text-[10px] font-bold uppercase tracking-wider text-slate-500 border-b border-slate-200">Cost Center</th>
                            <th class="px-3 py-3 text-[10px] font-bold uppercase tracking-wider text-slate-500 border-b border-slate-200">Branch</th>
                            <th class="px-3 py-3 text-[10px] font-bold uppercase tracking-wider text-slate-500 border-b border-slate-200">Item Code</th>
                            <th class="px-3 py-3 text-[10px] font-bold uppercase tracking-wider text-slate-500 border-b border-slate-200">Depr. Start</th>
                        </tr>
                    </thead>
                    <tbody id="review-tbody" class="text-sm divide-y divide-slate-100">
                        </tbody>
                </table>
            </div>

            <div class="px-6 py-4 border-t border-slate-100 bg-white flex items-center justify-between shrink-0">
                <div class="flex flex-col">
                    <span id="review-summary-ok" class="text-sm font-bold text-green-600">0 row(s) ready</span>
                    <span id="review-summary-err" class="text-[11px] font-semibold text-slate-400 mt-0.5"></span>
                </div>
                <div class="flex gap-3">
                    <button type="button" onclick="closeImportReview()" class="px-5 py-2.5 text-sm font-bold text-slate-600 bg-white border border-slate-300 hover:bg-slate-50 rounded-lg transition-colors">
                        Cancel
                    </button>
                    <button type="button" id="btn-confirm-import" onclick="confirmImport()" disabled class="px-6 py-2.5 text-sm font-bold text-white bg-[#ce1126] hover:bg-[#a80e1f] disabled:opacity-50 disabled:cursor-not-allowed rounded-lg shadow-sm transition-colors flex items-center gap-2">
                        <span>Confirm Import</span>
                    </button>
                </div>
            </div>

        </div>
    </div>
</div>