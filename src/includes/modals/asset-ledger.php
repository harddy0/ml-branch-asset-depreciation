<div id="modal-asset-ledger"
     class="hidden fixed inset-0 z-50 items-center justify-center bg-black/50 backdrop-blur-sm p-4">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-7xl animate-fadeIn flex flex-col" style="max-height:94vh">

        <div class="flex items-center justify-between px-7 py-3 border-b-2 border-[#ce1126] shrink-0">
            <div>
                <h2 class="text-base font-black text-slate-800 uppercase tracking-tight">Asset Ledger</h2>
                <p id="ledger-subtitle" class="text-xs text-slate-500 mt-0.5"></p>
            </div>
            <button type="button" onclick="closeModal('modal-asset-ledger')"
                    class="p-2 hover:bg-slate-100 rounded-lg transition-colors">
                <svg class="w-5 h-5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>

        <div class="px-7 py-3 border-b border-slate-200 bg-slate-50 shrink-0">
            <div class="grid grid-rows-1 md:grid-cols-5 gap-2">
                <div class="flex items-center gap-3 col-span-5 md:col-span-4">
                    <span class="text-sm font-semibold text-slate-600">As of</span>

                    <select id="ledger-period-month" class="border border-slate-300 rounded-md px-2 py-1.5 text-xs text-slate-700">
                        <option value="">All Months</option>
                    </select>

                    <select id="ledger-period-year" class="border border-slate-300 rounded-md px-2 py-1.5 text-xs text-slate-700">
                        <option value="">All Years</option>
                    </select>

                    <select id="ledger-entry-side" class="border border-slate-300 rounded-md px-2 py-1.5 text-xs text-slate-700">
                        <option value="ALL">All Entries</option>
                        <option value="DEBIT">Debit Only</option>
                        <option value="CREDIT">Credit Only</option>
                    </select>

                    <button id="ledger-reset-filter" type="button"
                            class="border border-slate-300 rounded-md px-3 py-1.5 text-xs font-bold uppercase text-slate-700 hover:bg-slate-100">
                        Reset
                    </button>
                </div>
            </div>

            <div class="flex items-center justify-between mt-3">
                <div class="inline-flex rounded-md border border-slate-300 overflow-hidden">
                    <button id="ledger-tab-ledger" type="button" class="px-3 py-1.5 text-xs font-bold uppercase bg-[#ce1126] text-white">Ledger</button>
                    <button id="ledger-tab-fs" type="button" class="px-3 py-1.5 text-xs font-bold uppercase bg-white text-slate-700">Financial Statement</button>
                </div>

                <button id="ledger-print-btn" type="button"
                        class="border border-slate-300 rounded-md px-3 py-1.5 text-xs font-bold uppercase text-slate-700 hover:bg-slate-100">
                    Print
                </button>
            </div>
        </div>

        <div class="px-7 py-2 text-[11px] text-slate-500 border-b border-slate-100 shrink-0">
            <span id="ledger-asset-meta">No asset selected.</span>
        </div>

        <div class="overflow-auto flex-1 px-7 py-4 bg-slate-50/40">
            <div id="ledger-loading" class="hidden text-center py-6 text-sm font-semibold text-slate-500">Loading ledger...</div>
            <div id="ledger-error" class="hidden text-center py-6 text-sm font-semibold text-red-600"></div>

            <div id="ledger-table-wrap" class="overflow-x-auto">
                <table class="w-full text-xs whitespace-nowrap bg-white border border-slate-200">
                    <thead class="bg-[#ce2216]">
                        <tr>
                            <th class="px-3 py-2 text-left font-black text-white uppercase">Date</th>
                            <th class="px-3 py-2 text-left font-black text-white uppercase">Period</th>
                            <th class="px-3 py-2 text-left font-black text-white uppercase">G/L</th>
                            <th class="px-3 py-2 text-left font-black text-white uppercase">Description</th>
                            <th class="px-3 py-2 text-right font-black text-white uppercase">Debit</th>
                            <th class="px-3 py-2 text-right font-black text-white uppercase">Credit</th>
                            <th class="px-3 py-2 text-right font-black text-white uppercase">Period Amount</th>
                            <th class="px-3 py-2 text-right font-black text-white uppercase">Accumulated</th>
                            <th class="px-3 py-2 text-right font-black text-white uppercase">Book Value</th>
                            <th class="px-3 py-2 text-left font-black text-white uppercase">Run Timestamp</th>
                        </tr>
                    </thead>
                    <tbody id="ledger-table-body"></tbody>
                </table>
            </div>

            <div id="fs-table-wrap" class="hidden overflow-x-auto">
                <table class="w-full text-xs whitespace-nowrap bg-white border border-slate-200">
                    <thead class="bg-[#ce2216]">
                        <tr>
                            <th class="px-3 py-2 text-left font-black text-white uppercase">Date</th>
                            <th class="px-3 py-2 text-left font-black text-white uppercase">Period</th>
                            <th class="px-3 py-2 text-left font-black text-white uppercase">Line</th>
                            <th class="px-3 py-2 text-left font-black text-white uppercase">Account</th>
                            <th class="px-3 py-2 text-right font-black text-white uppercase">Debit</th>
                            <th class="px-3 py-2 text-right font-black text-white uppercase">Credit</th>
                            <th class="px-3 py-2 text-right font-black text-white uppercase">Period Amount</th>
                            <th class="px-3 py-2 text-right font-black text-white uppercase">Accumulated</th>
                            <th class="px-3 py-2 text-right font-black text-white uppercase">Book Value</th>
                            <th class="px-3 py-2 text-left font-black text-white uppercase">Description</th>
                            <th class="px-3 py-2 text-left font-black text-white uppercase">Run Timestamp</th>
                        </tr>
                    </thead>
                    <tbody id="fs-table-body"></tbody>
                </table>
            </div>
        </div>

        <div class="px-7 py-3 border-t border-slate-200 bg-white shrink-0 flex items-center justify-between">
            <div id="ledger-footer-summary" class="text-xs font-semibold text-slate-600">Rows: 0</div>
            <div class="flex items-center gap-4 text-xs font-bold text-slate-700">
                <span>Debit: <span id="ledger-total-debit" class="font-mono">0.00</span></span>
                <span>Credit: <span id="ledger-total-credit" class="font-mono">0.00</span></span>
                <span>Latest Accumulated: <span id="ledger-latest-accum" class="font-mono">0.00</span></span>
                <span>Latest Book: <span id="ledger-latest-book" class="font-mono">0.00</span></span>
            </div>
        </div>
    </div>
</div>
