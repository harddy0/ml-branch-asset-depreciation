<div id="modal-add-asset" class="fixed inset-0 z-50 hidden bg-slate-900/60 backdrop-blur-sm flex items-center justify-center p-4 sm:p-6">
    <div class="bg-white rounded-xl shadow-2xl w-full max-w-4xl max-h-[90vh] flex flex-col overflow-hidden animate-fadeIn">
        
        <div class="px-6 py-4 border-b border-slate-200 flex justify-between items-center bg-slate-50">
            <h2 class="text-lg font-black text-slate-800 uppercase tracking-wide flex items-center gap-2">
                <svg class="w-5 h-5 text-[#ce1126]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                Add New Asset
            </h2>
            <button type="button" onclick="closeModal('modal-add-asset')" class="text-slate-400 hover:text-red-600 transition-colors">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
            </button>
        </div>

        <div class="flex-1 overflow-y-auto p-6 bg-white">
            <form id="addAssetForm" class="space-y-8">
                
                <section>
                    <h3 class="text-xs font-black text-[#ce1126] uppercase tracking-widest border-b border-slate-200 pb-2 mb-4">1. Location Data</h3>
                    
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
                        <div>
                            <label class="block text-xs font-bold text-slate-700 mb-1">Main Zone <span class="text-red-500">*</span></label>
                            <select name="main_zone_code" id="main_zone_code" required class="w-full text-sm border border-slate-300 rounded-lg px-3 py-2.5 focus:ring-2 focus:ring-red-500 outline-none transition-all bg-white disabled:bg-slate-100 disabled:text-slate-400">
                                <option value="" disabled selected>Loading...</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-700 mb-1">Sub-Zone <span class="text-red-500">*</span></label>
                            <select name="zone_code" id="zone_code" required class="w-full text-sm border border-slate-300 rounded-lg px-3 py-2.5 focus:ring-2 focus:ring-red-500 outline-none transition-all bg-white disabled:bg-slate-100 disabled:text-slate-400">
                                <option value="" disabled selected>Loading...</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-700 mb-1">Region <span class="text-red-500">*</span></label>
                            <select name="region_code" id="region_code" required class="w-full text-sm border border-slate-300 rounded-lg px-3 py-2.5 focus:ring-2 focus:ring-red-500 outline-none transition-all bg-white disabled:bg-slate-100 disabled:text-slate-400">
                                <option value="" disabled selected>Loading...</option>
                            </select>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                        <div>
                            <label class="block text-xs font-bold text-slate-700 mb-1">Branch Name <span class="text-red-500">*</span></label>
                            <select name="branch_name" id="branch_name" required class="w-full text-sm border border-slate-300 rounded-lg px-3 py-2.5 focus:ring-2 focus:ring-red-500 outline-none transition-all bg-white disabled:bg-slate-100 disabled:text-slate-400">
                                <option value="" disabled selected>Loading...</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-700 mb-1">Cost Center Code <span class="text-red-500">*</span></label>
                            <input type="text" name="cost_center_code" id="cost_center_code" placeholder="Auto-fills upon branch selection" readonly required class="w-full text-sm border border-slate-200 rounded-lg px-3 py-2.5 bg-slate-50 text-slate-600 outline-none transition-all">
                        </div>
                    </div>
                </section>

                <section>
                    <h3 class="text-xs font-black text-[#ce1126] uppercase tracking-widest border-b border-slate-200 pb-2 mb-4">2. Asset Details & Classification</h3>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                        <div>
                            <label class="block text-xs font-bold text-slate-700 mb-1">Asset Description <span class="text-red-500">*</span></label>
                            <input type="text" name="description" placeholder="e.g. Touch Screen Electronic LM Unit" required class="w-full text-sm border border-slate-300 rounded-lg px-3 py-2.5 focus:ring-2 focus:ring-red-500 outline-none transition-all">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-700 mb-1">Reference Number <span class="text-slate-400 font-normal">(Optional)</span></label>
                            <input type="text" name="reference_no" placeholder="e.g. IS#10287545" class="w-full text-sm border border-slate-300 rounded-lg px-3 py-2.5 focus:ring-2 focus:ring-red-500 outline-none transition-all">
                        </div>
                    </div>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                        <div>
                            <label class="block text-xs font-bold text-slate-700 mb-1">Asset Group <span class="text-red-500">*</span></label>
                            <select name="group_code" required class="w-full text-sm border border-slate-300 rounded-lg px-3 py-2.5 focus:ring-2 focus:ring-red-500 outline-none transition-all bg-white">
                                <option value="" disabled selected>Select Group Rule...</option>
                                <option value="OE24MOS">Office Equipment (24 mos)</option>
                                <option value="IT60MOS">IT Equipment (60 mos)</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-700 mb-1">Property Type <span class="text-red-500">*</span></label>
                            <select name="property_type" required class="w-full text-sm border border-slate-300 rounded-lg px-3 py-2.5 focus:ring-2 focus:ring-red-500 outline-none transition-all bg-white">
                                <option value="PURCHASED" selected>Purchased</option>
                                <option value="LEASE">Lease</option>
                                <option value="LEASEHOLD">Leasehold</option>
                                <option value="MAINTENANCE">Maintenance</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div>
                            <label class="block text-xs font-bold text-slate-700 mb-1">Serial Number</label>
                            <input type="text" name="serial_number" placeholder="e.g. 1082018001" class="w-full text-sm border border-slate-300 rounded-lg px-3 py-2.5 focus:ring-2 focus:ring-red-500 outline-none transition-all">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-700 mb-1">Quantity <span class="text-red-500">*</span></label>
                            <input type="number" name="quantity" value="1" min="1" required class="w-full text-sm border border-slate-300 rounded-lg px-3 py-2.5 focus:ring-2 focus:ring-red-500 outline-none transition-all">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-700 mb-1">Acquisition Cost <span class="text-red-500">*</span></label>
                            <div class="relative">
                                <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-slate-500 font-black">₱</span>
                                <input type="number" step="0.01" name="acquisition_cost" placeholder="0.00" required class="w-full text-sm border border-slate-300 rounded-lg pl-8 pr-3 py-2.5 focus:ring-2 focus:ring-red-500 outline-none transition-all">
                            </div>
                        </div>
                    </div>
                </section>

                <section>
                    <h3 class="text-xs font-black text-[#ce1126] uppercase tracking-widest border-b border-slate-200 pb-2 mb-4">3. Depreciation Schedule</h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                        <div>
                            <label class="block text-xs font-bold text-slate-700 mb-1">Date Received <span class="text-red-500">*</span></label>
                            <input type="date" name="date_received" required class="w-full text-sm border border-slate-300 rounded-lg px-3 py-2.5 focus:ring-2 focus:ring-red-500 outline-none transition-all">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-700 mb-1">Depreciation Start Date <span class="text-red-500">*</span></label>
                            <input type="date" name="depreciation_start_date" required class="w-full text-sm border border-slate-300 rounded-lg px-3 py-2.5 focus:ring-2 focus:ring-red-500 outline-none transition-all">
                        </div>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div>
                            <label class="block text-xs font-bold text-slate-700 mb-1">Depreciate On <span class="text-red-500">*</span></label>
                            <select name="depreciation_on" id="depreciation_on" required class="w-full text-sm border border-slate-300 rounded-lg px-3 py-2.5 focus:ring-2 focus:ring-red-500 outline-none transition-all bg-white">
                                <option value="LAST_DAY" selected>Last Day of Month</option>
                                <option value="FIRST_DAY">First Day of Month</option>
                                <option value="SPECIFIC_DATE">Specific Date</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-700 mb-1" id="depreciation_day_label">Specific Day</label>
                            <input type="number" name="depreciation_day" id="depreciation_day" min="1" max="31" disabled class="w-full text-sm border border-slate-200 rounded-lg px-3 py-2.5 bg-slate-100 text-slate-400 outline-none transition-all cursor-not-allowed">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-700 mb-1">Initial Status <span class="text-red-500">*</span></label>
                            <select name="status" required class="w-full text-sm border border-slate-300 rounded-lg px-3 py-2.5 focus:ring-2 focus:ring-red-500 outline-none transition-all bg-white">
                                <option value="ACTIVE" selected>Active</option>
                                <option value="SOLD">Sold</option>
                                <option value="DISPOSED">Disposed</option>
                            </select>
                        </div>
                    </div>
                </section>
                
            </form>
        </div>

        <div class="px-6 py-4 border-t border-slate-200 flex justify-end gap-3 bg-slate-50">
            <button type="button" onclick="closeModal('modal-add-asset')" class="px-5 py-2 text-sm font-bold text-slate-600 bg-white border border-slate-300 rounded-lg hover:bg-slate-50 transition-colors">Cancel</button>
            <button type="submit" form="addAssetForm" class="px-5 py-2 text-sm font-bold text-white bg-[#ce1126] rounded-lg hover:bg-red-700 shadow-sm transition-colors uppercase tracking-wide">Save Asset</button>
        </div>
    </div>
</div>