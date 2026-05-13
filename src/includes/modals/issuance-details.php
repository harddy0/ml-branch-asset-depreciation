<div id="modal-issuance-details" class="fixed inset-0 z-[110] hidden">
    <div class="absolute inset-0 bg-slate-900/60 backdrop-blur-sm" onclick="closeIssuanceDetails()"></div>
    <div class="absolute inset-4 md:inset-10 flex items-center justify-center pointer-events-none">

        <div class="bg-white rounded-2xl shadow-2xl w-full max-w-5xl flex flex-col min-h-0 overflow-hidden pointer-events-auto animate-fadeIn" style="max-height: 94vh;">

            <div class="flex items-center justify-between px-6 py-2 border-b border-slate-100 bg-slate-50 shrink-0">
                <div class="flex items-center gap-3">
                    <h3 class="text-lg font-bold text-slate-800">Row Details</h3>
                    <span id="issuance-edit-badge" class="hidden px-2 py-0.5 rounded bg-orange-100 text-orange-700 text-[10px] font-black uppercase tracking-wider">Edit Mode</span>
                </div>
                <button type="button" onclick="closeIssuanceDetails()" class="p-2 text-slate-400 hover:text-slate-600 hover:bg-slate-200 rounded-lg transition-colors">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>

            <div id="issuance-modal-errors" class="hidden shrink-0 bg-red-50 px-6 py-1 border-b border-red-100 text-sm font-semibold text-red-700 space-y-0"></div>

            <div class="flex-1 min-h-0 overflow-y-auto p-6 bg-white">

                <div id="issuance-view-content"></div>

                <form id="issuance-edit-form" class="hidden space-y-6" onsubmit="event.preventDefault();">

                    <div class="space-y-4">
                        <h4 class="text-xs font-black text-slate-400 uppercase tracking-wider border-b border-slate-100 pb-2">Issuance</h4>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-bold text-slate-700 mb-1">Date Issued <span class="text-red-500">*</span></label>
                                <input type="date" id="iss-f-date" class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg text-sm">
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-slate-700 mb-1">Issuance Number <span class="text-red-500">*</span></label>
                                <input type="text" id="iss-f-number" class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg text-sm">
                            </div>
                        </div>
                    </div>

                    <div class="space-y-4">
                        <h4 class="text-xs font-black text-slate-400 uppercase tracking-wider border-b border-slate-100 pb-2">Item Details</h4>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-bold text-slate-700 mb-1">Item Code</label>
                                <input type="text" id="iss-f-item-code" class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg text-sm">
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-slate-700 mb-1">Item Description <span class="text-red-500">*</span></label>
                                <input type="text" id="iss-f-item-desc" class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg text-sm">
                            </div>
                        </div>
                        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                            <div>
                                <label class="block text-xs font-bold text-slate-700 mb-1">Quantity <span class="text-red-500">*</span></label>
                                <input type="number" min="1" id="iss-f-qty" class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg text-sm">
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-slate-700 mb-1">UoM</label>
                                <input type="text" id="iss-f-uom" class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg text-sm">
                            </div>
                            <div class="col-span-2">
                                <label class="block text-xs font-bold text-slate-700 mb-1">Remarks</label>
                                <input type="text" id="iss-f-remarks" class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg text-sm">
                            </div>
                        </div>
                    </div>

                    <div class="space-y-4">
                        <h4 class="text-xs font-black text-slate-400 uppercase tracking-wider border-b border-slate-100 pb-2">Cost</h4>
                        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                            <div>
                                <label class="block text-xs font-bold text-slate-700 mb-1">Unit Cost</label>
                                <input type="number" step="0.01" min="0" id="iss-f-unit-cost" class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg text-sm">
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-slate-700 mb-1">Total Amount</label>
                                <input type="number" step="0.01" min="0" id="iss-f-total-amount" class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg text-sm">
                            </div>
                            <div class="col-span-2">
                                <label class="block text-xs font-bold text-slate-700 mb-1">Cost Center <span class="text-red-500">*</span></label>
                                <input type="text" id="iss-f-cost-center" class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg text-sm">
                            </div>
                        </div>
                    </div>

                    <div class="space-y-4">
                        <h4 class="text-xs font-black text-slate-400 uppercase tracking-wider border-b border-slate-100 pb-2">Location and Category</h4>
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <div>
                                <label class="block text-xs font-bold text-slate-700 mb-1">Product Category <span class="text-red-500">*</span></label>
                                <input type="text" id="iss-f-category" class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg text-sm">
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-slate-700 mb-1">Zone</label>
                                <input type="text" id="iss-f-zone" class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg text-sm">
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-slate-700 mb-1">Region</label>
                                <input type="text" id="iss-f-region" class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg text-sm">
                            </div>
                        </div>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-bold text-slate-700 mb-1">Branch Name</label>
                                <input type="text" id="iss-f-branch" class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg text-sm">
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-slate-700 mb-1">Status</label>
                                <input type="text" id="iss-f-status" class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg text-sm">
                            </div>
                        </div>
                    </div>

                </form>
            </div>

            <div class="px-6 py-2 border-t border-slate-100 bg-white flex items-center justify-between shrink-0">
                <span id="issuance-unsaved-hint" class="hidden text-xs font-semibold text-orange-600">Unsaved changes</span>
                <span class="text-xs"></span>
                <div class="flex gap-3">
                    <button type="button" id="issuance-btn-close" onclick="closeIssuanceDetails()" class="px-5 py-1 text-sm font-bold text-slate-600 bg-white border border-slate-300 hover:bg-slate-50 rounded-lg transition-colors">Close</button>
                    <button type="button" id="issuance-btn-edit" onclick="enableIssuanceEdit()" class="px-6 py-1 text-sm font-bold text-white bg-slate-800 hover:bg-slate-900 rounded-lg transition-colors flex items-center gap-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/></svg>
                        <span>Edit</span>
                    </button>

                    <button type="button" id="issuance-btn-cancel-edit" onclick="cancelIssuanceEdit()" class="hidden px-5 py-1 text-sm font-bold text-slate-600 bg-white border border-slate-300 hover:bg-slate-50 rounded-lg transition-colors">Cancel</button>
                    <button type="button" id="issuance-btn-save" onclick="saveIssuanceEdit()" class="hidden px-6 py-1 text-sm font-bold text-white bg-[#ce1126] hover:bg-[#a80e1f] rounded-lg transition-colors">Save</button>
                </div>
            </div>

        </div>
    </div>
</div>
