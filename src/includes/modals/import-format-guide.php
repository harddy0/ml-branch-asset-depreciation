<div id="modal-import-guide" class="fixed inset-0 z-[100] hidden">
    <div class="absolute inset-0 bg-slate-900/60 backdrop-blur-sm" onclick="closeModal('modal-import-guide')"></div>
    <div class="absolute inset-4 md:inset-auto md:top-1/2 md:left-1/2 md:-translate-x-1/2 md:-translate-y-1/2 flex items-center justify-center pointer-events-none">
        
        <div class="bg-white rounded-2xl shadow-2xl w-full max-w-3xl max-h-[90vh] flex flex-col pointer-events-auto">
            
            <div class="flex items-center justify-between px-6 py-5 border-b border-slate-100 bg-slate-50 shrink-0">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-full bg-blue-100 flex items-center justify-center text-blue-600">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    </div>
                    <div>
                        <h3 class="text-lg font-bold text-slate-800">Excel Format Guide</h3>
                        <p class="text-sm font-medium text-slate-500">Your file must contain exactly 15 columns in this specific order.</p>
                    </div>
                </div>
                <button type="button" onclick="closeModal('modal-import-guide')" class="p-2 text-slate-400 hover:text-slate-600 hover:bg-slate-200 rounded-lg transition-colors">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>

            <div class="flex-1 overflow-y-auto p-6 text-sm text-slate-600 space-y-6 bg-white">
                
                <div class="bg-blue-50 border border-blue-100 p-4 rounded-xl flex gap-3 text-blue-800">
                    <svg class="w-5 h-5 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                    <div>
                        <p class="font-bold mb-1">Important Rules:</p>
                        <ul class="list-disc list-inside space-y-1 text-xs font-medium text-blue-700/80">
                            <li>Row 1 must be the Header Row (it will be skipped).</li>
                            <li>Data must start on Row 2.</li>
                            <li>Columns must be in the exact order shown below.</li>
                        </ul>
                    </div>
                </div>

                <div class="border border-slate-200 rounded-xl overflow-hidden">
                    <table class="w-full text-left text-xs">
                        <thead class="bg-slate-50 border-b border-slate-200 text-slate-500 font-bold uppercase tracking-wider">
                            <tr>
                                <th class="px-4 py-3 w-16">Col</th>
                                <th class="px-4 py-3">Field Name</th>
                                <th class="px-4 py-3">Requirement</th>
                                <th class="px-4 py-3">Notes</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 font-medium text-slate-700">
                            <tr><td class="px-4 py-2 text-slate-400">A (0)</td><td class="px-4 py-2 font-bold">Serial Number</td><td class="px-4 py-2">Optional</td><td class="px-4 py-2 text-slate-500">Alphanumeric</td></tr>
                            <tr><td class="px-4 py-2 text-slate-400">B (1)</td><td class="px-4 py-2 font-bold">Asset Description</td><td class="px-4 py-2 text-[#ce1126]">Required</td><td class="px-4 py-2 text-slate-500">Full item description</td></tr>
                            <tr><td class="px-4 py-2 text-slate-400">C (2)</td><td class="px-4 py-2 font-bold">Reference Number</td><td class="px-4 py-2">Optional</td><td class="px-4 py-2 text-slate-500">Invoice or receipt no.</td></tr>
                            <tr><td class="px-4 py-2 text-slate-400">D (3)</td><td class="px-4 py-2 font-bold">Quantity</td><td class="px-4 py-2 text-[#ce1126]">Required</td><td class="px-4 py-2 text-slate-500">Numeric (Default: 1)</td></tr>
                            <tr><td class="px-4 py-2 text-slate-400">E (4)</td><td class="px-4 py-2 font-bold">Property Type</td><td class="px-4 py-2 text-[#ce1126]">Required</td><td class="px-4 py-2 text-slate-500">PURCHASED, LEASE, LEASEHOLD</td></tr>
                            <tr><td class="px-4 py-2 text-slate-400">F (5)</td><td class="px-4 py-2 font-bold">Asset Group</td><td class="px-4 py-2 text-[#ce1126]">Required</td><td class="px-4 py-2 text-slate-500">Exact Group Name (e.g. IT Equipment)</td></tr>
                            <tr><td class="px-4 py-2 text-slate-400">G (6)</td><td class="px-4 py-2 font-bold">Acquisition Cost</td><td class="px-4 py-2 text-[#ce1126]">Required</td><td class="px-4 py-2 text-slate-500">Numeric (> 0)</td></tr>
                            <tr><td class="px-4 py-2 text-slate-400">H (7)</td><td class="px-4 py-2 font-bold">Date Received</td><td class="px-4 py-2 text-[#ce1126]">Required</td><td class="px-4 py-2 text-slate-500">YYYY-MM-DD</td></tr>
                            <tr><td class="px-4 py-2 text-slate-400">I (8)</td><td class="px-4 py-2 font-bold">Main Zone</td><td class="px-4 py-2">Optional</td><td class="px-4 py-2 text-slate-500">Extracted from master data if blank</td></tr>
                            <tr><td class="px-4 py-2 text-slate-400">J (9)</td><td class="px-4 py-2 font-bold">Sub-Zone</td><td class="px-4 py-2">Optional</td><td class="px-4 py-2 text-slate-500">Extracted from master data if blank</td></tr>
                            <tr><td class="px-4 py-2 text-slate-400">K (10)</td><td class="px-4 py-2 font-bold">Region</td><td class="px-4 py-2">Optional</td><td class="px-4 py-2 text-slate-500">Used for fallback matching</td></tr>
                            <tr><td class="px-4 py-2 text-slate-400">L (11)</td><td class="px-4 py-2 font-bold">Cost Center</td><td class="px-4 py-2 text-[#ce1126]">Required*</td><td class="px-4 py-2 text-slate-500">0000-000 format. Used to strictly match Branch.</td></tr>
                            <tr><td class="px-4 py-2 text-slate-400">M (12)</td><td class="px-4 py-2 font-bold">Branch Name</td><td class="px-4 py-2">Optional</td><td class="px-4 py-2 text-slate-500">Used for fuzzy matching if Cost Center is missing</td></tr>
                            <tr><td class="px-4 py-2 text-slate-400">N (13)</td><td class="px-4 py-2 font-bold">Item Code</td><td class="px-4 py-2">Optional</td><td class="px-4 py-2 text-slate-500">Internal tracking tag</td></tr>
                            <tr><td class="px-4 py-2 text-slate-400">O (14)</td><td class="px-4 py-2 font-bold">Depreciation Start</td><td class="px-4 py-2">Optional</td><td class="px-4 py-2 text-slate-500">YYYY-MM-DD. Defaults to end of Date Received month.</td></tr>
                        </tbody>
                    </table>
                </div>

            </div>
            
            <div class="px-6 py-4 border-t border-slate-100 bg-slate-50 shrink-0 text-right rounded-b-2xl">
                <button type="button" onclick="closeModal('modal-import-guide')" class="px-6 py-2 bg-white border border-slate-300 text-slate-700 font-bold rounded-lg shadow-sm hover:bg-slate-50 transition-colors">
                    Understood
                </button>
            </div>
            
        </div>
    </div>
</div>