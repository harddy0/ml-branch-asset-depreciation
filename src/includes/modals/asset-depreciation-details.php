<div id="modal-asset-depr-details" class="fixed inset-0 z-[110] hidden">
    <div class="absolute inset-0 bg-slate-900/60 backdrop-blur-sm" onclick="closeAssetDepreciationDetails()"></div>
    <div class="absolute inset-4 md:inset-auto md:top-1/2 md:left-1/2 md:-translate-x-1/2 md:-translate-y-1/2 flex items-center justify-center pointer-events-none">
        
        <div class="bg-white rounded-2xl shadow-2xl w-full max-w-4xl max-h-[90vh] flex flex-col pointer-events-auto overflow-hidden">
            
            <div class="flex items-center justify-between px-6 py-4 border-b border-slate-100 bg-slate-50 shrink-0">
                <div class="flex items-center gap-3">
                    <h3 class="text-lg font-bold text-slate-800">Row Details</h3>
                    <span id="depr-edit-badge" class="hidden px-2 py-0.5 rounded bg-orange-100 text-orange-700 text-[10px] font-black uppercase tracking-wider">Edit Mode</span>
                </div>
                <button type="button" onclick="closeAssetDepreciationDetails()" class="p-2 text-slate-400 hover:text-slate-600 hover:bg-slate-200 rounded-lg transition-colors">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>

            <div id="depr-modal-errors" class="hidden shrink-0 bg-red-50 px-6 py-3 border-b border-red-100 text-sm font-semibold text-red-700 space-y-1"></div>

            <div class="flex-1 overflow-y-auto p-6 bg-white">
                
                <div id="depr-view-content"></div>

                <form id="depr-edit-form" class="hidden space-y-6" onsubmit="event.preventDefault();">
                    
                    <div class="space-y-4">
                        <h4 class="text-xs font-black text-slate-400 uppercase tracking-wider border-b border-slate-100 pb-2">Asset Classification</h4>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-bold text-slate-700 mb-1">Description <span class="text-red-500">*</span></label>
                                <input type="text" id="depr-f-description" class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg text-sm focus:bg-white focus:border-[#ce1126] focus:ring-1 focus:ring-[#ce1126] transition-colors">
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-slate-700 mb-1">GL Asset Group <span class="text-red-500">*</span></label>
                                <select id="depr-f-group" class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg text-sm font-medium focus:bg-white focus:border-[#ce1126] focus:ring-1 focus:ring-[#ce1126] transition-colors"></select>
                            </div>
                        </div>
                        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                            <div>
                                <label class="block text-xs font-bold text-slate-700 mb-1">Property Type</label>
                                <select id="depr-f-property-type" class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg text-sm font-medium">
                                    <option value="PURCHASED">PURCHASED</option>
                                    <option value="LEASE">LEASE</option>
                                    <option value="LEASEHOLD">LEASEHOLD</option>
                                    <option value="MAINTENANCE">MAINTENANCE</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-slate-700 mb-1">Reference No.</label>
                                <input type="text" id="depr-f-refno" class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg text-sm">
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-slate-700 mb-1">Serial Number</label>
                                <input type="text" id="depr-f-serial" class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg text-sm">
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-slate-700 mb-1">Item Code</label>
                                <input type="text" id="depr-f-itemcode" class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg text-sm">
                            </div>
                        </div>
                    </div>

                    <div class="space-y-4">
                        <h4 class="text-xs font-black text-slate-400 uppercase tracking-wider border-b border-slate-100 pb-2">Location Assignment</h4>
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <div>
                                <label class="block text-xs font-bold text-slate-700 mb-1">Main Zone</label>
                                <select id="depr-f-mainzone" class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg text-sm"></select>
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-slate-700 mb-1">Sub-Zone</label>
                                <select id="depr-f-zone" class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg text-sm"></select>
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-slate-700 mb-1">Region</label>
                                <select id="depr-f-region" class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg text-sm"></select>
                            </div>
                        </div>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-bold text-slate-700 mb-1">Branch <span class="text-red-500">*</span></label>
                                <select id="depr-f-branch" class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg text-sm focus:bg-white focus:border-[#ce1126] focus:ring-1 focus:ring-[#ce1126] transition-colors"></select>
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-slate-700 mb-1">Cost Center (Auto)</label>
                                <input type="text" id="depr-f-costcenter" readonly class="w-full px-3 py-2 bg-slate-100 border border-slate-200 rounded-lg text-sm font-mono text-slate-500 pointer-events-none">
                            </div>
                            <input type="hidden" id="depr-f-branchcode">
                        </div>
                    </div>

                    <div class="space-y-4">
                        <h4 class="text-xs font-black text-slate-400 uppercase tracking-wider border-b border-slate-100 pb-2">Financial Details</h4>
                        <div class="grid grid-cols-2 md:grid-cols-5 gap-4">
                            <div>
                                <label class="block text-xs font-bold text-slate-700 mb-1">Quantity</label>
                                <input type="number" min="1" id="depr-f-quantity" class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg text-sm">
                            </div>
                            <div class="col-span-2">
                                <label class="block text-xs font-bold text-slate-700 mb-1">Acquisition Cost <span class="text-red-500">*</span></label>
                                <div class="relative">
                                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                        <span class="text-slate-400 sm:text-sm font-semibold">₱</span>
                                    </div>
                                    <input type="number" step="0.01" min="0" id="depr-f-acq-cost" class="w-full pl-8 pr-3 py-2 bg-slate-50 border border-slate-200 rounded-lg text-sm font-semibold text-slate-800">
                                </div>
                            </div>
                            <div class="col-span-2">
                                <label class="block text-xs font-bold text-slate-700 mb-1">Monthly Depr. (Auto)</label>
                                <div class="relative">
                                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                        <span class="text-slate-400 sm:text-sm font-semibold">₱</span>
                                    </div>
                                    <input type="text" id="depr-f-monthly-dep" readonly class="w-full pl-8 pr-3 py-2 bg-slate-100 border border-slate-200 rounded-lg text-sm font-semibold text-slate-500 pointer-events-none">
                                </div>
                            </div>
                        </div>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-bold text-slate-700 mb-1">Date Received <span class="text-red-500">*</span></label>
                                <input type="date" id="depr-f-date-received" class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg text-sm">
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-slate-700 mb-1">Depreciation Start</label>
                                <input type="date" id="depr-f-depr-start" class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg text-sm">
                            </div>
                        </div>
                    </div>
                    
                </form>
            </div>

            <div class="px-6 py-4 border-t border-slate-100 bg-white flex items-center justify-between shrink-0">
                <span id="depr-unsaved-hint" class="hidden text-xs font-semibold text-orange-600">Unsaved changes</span>
                <span class="text-xs"></span>
                <div class="flex gap-3">
                    <button type="button" id="depr-btn-close" onclick="closeAssetDepreciationDetails()" class="px-5 py-2.5 text-sm font-bold text-slate-600 bg-white border border-slate-300 hover:bg-slate-50 rounded-lg transition-colors">Close</button>
                    <button type="button" id="depr-btn-edit" onclick="enableDeprEdit()" class="px-6 py-2.5 text-sm font-bold text-white bg-slate-800 hover:bg-slate-900 rounded-lg transition-colors flex items-center gap-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/></svg>
                        <span>Edit Row</span>
                    </button>
                    
                    <button type="button" id="depr-btn-cancel-edit" onclick="cancelDeprEdit()" class="hidden px-5 py-2.5 text-sm font-bold text-slate-600 bg-white border border-slate-300 hover:bg-slate-50 rounded-lg transition-colors">Cancel Edit</button>
                    <button type="button" id="depr-btn-save" onclick="saveDeprEdit()" class="hidden px-6 py-2.5 text-sm font-bold text-white bg-[#ce1126] hover:bg-[#a80e1f] rounded-lg transition-colors">Apply Changes</button>
                </div>
            </div>

        </div>
    </div>
</div>