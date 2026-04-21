<div id="modal-view-asset" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-sm p-4">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-5xl flex flex-col overflow-hidden animate-fadeIn" style="max-height: 94vh;">
        
        <div class="flex items-center justify-between px-6 py-4 border-b-2 border-[#ce1126] shrink-0 bg-white">
            <div class="flex items-center gap-3">
                <h2 class="text-xl font-black text-slate-800 uppercase tracking-tight">
                    <span id="view-system-code">LOADING...</span>
                </h2>
                <span id="view-status-badge" class="px-2.5 py-0.5 rounded-full text-xs font-bold uppercase tracking-wider bg-slate-100 text-slate-700 hidden">
                    STATUS
                </span>
            </div>
            <button type="button" onclick="closeModal('modal-view-asset')" class="p-2 hover:bg-slate-100 rounded-lg transition-colors">
                <svg class="w-5 h-5 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>

        <div id="view-asset-loading" class="p-12 text-center text-slate-500 font-semibold flex-1">
            Fetching asset details...
        </div>

        <div id="view-asset-content" class="hidden flex-1 overflow-y-auto bg-white p-6">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                
                <div class="bg-white rounded-xl border border-slate-200 p-5 shadow-sm">
                    <h3 class="text-sm font-black text-slate-800 uppercase tracking-wide mb-4 border-b border-slate-100 pb-2">Identity & Classification</h3>
                    <div class="space-y-3">
                        <div><span class="text-[12px] text-slate-700 block uppercase font-semibold">Description</span><span id="view-description" class="text-sm font-bold text-slate-900"></span></div>
                        <div class="grid grid-cols-2 gap-4">
                            <div><span class="text-[12px] text-slate-700 block uppercase font-semibold">Serial Number</span><span id="view-serial" class="text-sm font-mono text-slate-900"></span></div>
                            <div><span class="text-[12px] text-slate-700 block uppercase font-semibold">Item Code</span><span id="view-item-code" class="text-sm font-mono text-slate-900"></span></div>
                        </div>
                        <div class="grid grid-cols-2 gap-4">
                            <div><span class="text-[12px] text-slate-700 block uppercase font-semibold">Asset Group</span><span id="view-group" class="text-sm text-slate-900"></span></div>
                            <div><span class="text-[12px] text-slate-700 block uppercase font-semibold">Property Type</span><span id="view-property-type" class="text-sm text-slate-900"></span></div>
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-xl border border-slate-200 p-5 shadow-sm">
                    <h3 class="text-sm font-black text-slate-800 uppercase tracking-wide mb-4 border-b border-slate-100 pb-2">Location Information</h3>
                    <div class="space-y-3">
                        <div><span class="text-[12px] text-slate-700 block uppercase font-semibold">Branch Name</span><span id="view-branch" class="text-sm font-bold text-slate-900"></span></div>
                        <div><span class="text-[12px] text-slate-700 block uppercase font-semibold">Cost Center Code</span><span id="view-cost-center" class="text-sm font-mono text-slate-900"></span></div>
                        <div class="grid grid-cols-3 gap-2">
                            <div><span class="text-[12px] text-slate-700 block uppercase font-semibold">Region</span><span id="view-region" class="text-sm text-slate-900"></span></div>
                            <div><span class="text-[12px] text-slate-700 block uppercase font-semibold">Sub-Zone</span><span id="view-zone" class="text-sm text-slate-900"></span></div>
                            <div><span class="text-[12px] text-slate-700 block uppercase font-semibold">Main Zone</span><span id="view-main-zone" class="text-sm text-slate-900"></span></div>
                        </div>
                    </div>
                </div>

                <div class="md:col-span-2 bg-white rounded-xl border border-slate-200 p-5 shadow-sm">
                    <h3 class="text-sm font-black text-slate-800 uppercase tracking-wide mb-4 border-b border-slate-100 pb-2">Financial & Schedule</h3>
                    
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-6 mb-6">
                        <div class="bg-white p-4 rounded-xl border border-slate-200 shadow-sm">
                            <span class="text-[12px] text-slate-700 block uppercase tracking-wider font-semibold">Acquisition Cost</span>
                            <span class="text-lg font-mono font-black text-slate-900">₱ <span id="view-acq-cost">0.00</span></span>
                        </div>
                        <div class="bg-white p-4 rounded-xl border border-slate-200 shadow-sm">
                            <span class="text-[12px] text-slate-700 block uppercase tracking-wider font-semibold">Monthly Depr.</span>
                            <span class="text-lg font-mono font-black text-red-700">₱ <span id="view-monthly-dep">0.00</span></span>
                        </div>
                        <div class="bg-white p-4 rounded-xl border border-slate-200 shadow-sm">
                            <span class="text-[12px] text-slate-700 block uppercase tracking-wider font-semibold">Accumulated Depr.</span>
                            <span class="text-lg font-mono font-black text-slate-900">₱ <span id="view-accum-dep">0.00</span></span>
                        </div>
                        <div class="bg-white p-4 rounded-xl border border-slate-200 shadow-sm">
                            <span class="text-[12px] text-slate-700 block uppercase tracking-wider font-semibold">Book Value</span>
                            <span class="text-lg font-mono font-black text-red-700">₱ <span id="view-book-value">0.00</span></span>
                        </div>
                    </div>

                    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                        <div><span class="text-[12px] text-slate-700 block uppercase font-semibold">Date Received</span><span id="view-date-received" class="text-sm font-mono text-slate-900"></span></div>
                        <div><span class="text-[12px] text-slate-700 block uppercase font-semibold">Start Date</span><span id="view-start-date" class="text-sm font-mono text-slate-900"></span></div>
                        <div><span class="text-[12px] text-slate-700 block uppercase font-semibold">End Date</span><span id="view-end-date" class="text-sm font-mono text-slate-900"></span></div>
                        <div><span class="text-[12px] text-slate-700 block uppercase font-semibold">Policy (Months)</span><span id="view-months" class="text-sm font-bold text-slate-900"></span></div>
                    </div>
                </div>

            </div>
        </div>

        <div class="px-6 py-3 border-t border-slate-200 bg-white shrink-0 flex items-center justify-between text-[12px] text-slate-700">
            <div>Uploaded by: <span id="view-uploaded-by" class="font-semibold text-slate-900"></span></div>
            <div>Date Added: <span id="view-created-at" class="font-semibold text-slate-900"></span></div>
        </div>

    </div>
</div>