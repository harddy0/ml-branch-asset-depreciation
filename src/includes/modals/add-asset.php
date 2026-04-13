<div id="modal-add-asset"
    class="hidden fixed inset-0 z-50 items-center justify-center bg-black/40 backdrop-blur-sm px-4 py-0 pt-4 pb-0">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-[calc(100vw-4rem)] max-h-[calc(100vh-2rem)] animate-fadeIn flex flex-col overflow-hidden">

        <div class="flex-1 overflow-y-auto">
            <div class="flex items-center justify-between px-8 py-2 border-b border-slate-100">
                <div>
                    <h2 class="text-base font-black text-slate-800 uppercase tracking-tight">Add Asset</h2>
                </div>
                <button onclick="closeModal('modal-add-asset')"
                    class="p-2 hover:bg-slate-100 rounded-lg transition-colors">
                    <svg class="w-5 h-5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>

            <form id="add-asset-form" method="POST" action="#" class="px-7 py-6">

                <!-- Progress -->
                <div class="mb-4">
                    <div class="w-full bg-slate-100 rounded-full h-2 overflow-hidden">
                        <div id="asset-wizard-progress" class="bg-[#ce1126] h-2 w-0"></div>
                    </div>
                    <div class="text-xs text-slate-500 mt-2">Step <span id="asset-wizard-step">1</span> of 3</div>
                </div>

                <!-- Step 1: Core Information -->
                <div data-step="1" class="asset-step">
                    <div class="border border-slate-100 shadow-md rounded-md w-full max-w-[900px] mx-auto min-h-[320px] pt-6 px-6">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-black text-slate-500 uppercase tracking-widest mb-1.5">Serial number <span class="text-red-600">*</span></label>
                                <input type="text" name="serial_number" required class="w-full border-2 border-slate-200 rounded-md px-4 py-2.5 text-sm" />
                            </div>
                            <div>
                                <label class="block text-xs font-black text-slate-500 uppercase tracking-widest mb-1.5">Status</label>
                                <input type="text" name="status" class="w-full border-2 border-slate-200 rounded-md px-4 py-2.5 text-sm" />
                            </div>
                        </div>

                        <div class="mt-4">
                            <label class="block text-xs font-black text-slate-500 uppercase tracking-widest mb-1.5">Description</label>
                            <textarea name="description" rows="3" class="w-full border-2 border-slate-200 rounded-md px-4 py-2.5 text-sm"></textarea>
                        </div>

                        <div class="grid grid-cols-3 gap-4 mt-4">
                            <div>
                                <label class="block text-xs font-black text-slate-500 uppercase tracking-widest mb-1.5">Amount</label>
                                <input type="number" name="amount" step="0.01" class="w-full border-2 border-slate-200 rounded-md px-4 py-2.5 text-sm" />
                            </div>
                            <div>
                                <label class="block text-xs font-black text-slate-500 uppercase tracking-widest mb-1.5">Purchase Date</label>
                                <input type="date" name="purchase_date" class="w-full border-2 border-slate-200 rounded-md px-4 py-2.5 text-sm" />
                            </div>
                            <div>
                                <label class="block text-xs font-black text-slate-500 uppercase tracking-widest mb-1.5">Property type</label>
                                <input type="text" name="property_type" class="w-full border-2 border-slate-200 rounded-md px-4 py-2.5 text-sm" />
                            </div>
                        </div>
                    </div>
                </div>
                    

                <!-- Step 2: Financial & Ledger Setup -->
                <div data-step="2" class="asset-step hidden">
                    <div class="border border-slate-100 shadow-md rounded-md w-full max-w-[900px] mx-auto min-h-[320px] pt-6 px-6">
                        <div class="grid grid-cols-3 gap-4">
                            <div>
                                <label class="block text-xs font-black text-slate-500 uppercase tracking-widest mb-1.5">Group</label>
                                <input type="text" name="group" class="w-full border-2 border-slate-200 rounded-md px-4 py-2.5 text-sm" />
                            </div>
                            <div>
                                <label class="block text-xs font-black text-slate-500 uppercase tracking-widest mb-1.5">Asset Account</label>
                                <input type="text" name="asset_account" class="w-full border-2 border-slate-200 rounded-md px-4 py-2.5 text-sm" />
                            </div>
                            <div>
                                <label class="block text-xs font-black text-slate-500 uppercase tracking-widest mb-1.5">Depreciation P&amp;L</label>
                                <input type="text" name="depr_pl" class="w-full border-2 border-slate-200 rounded-md px-4 py-2.5 text-sm" />
                            </div>
                        </div>

                        <div class="grid grid-cols-4 gap-4 mt-4">
                            <div>
                                <label class="block text-xs font-black text-slate-500 uppercase tracking-widest mb-1.5">Method</label>
                                <input type="text" name="method" class="w-full border-2 border-slate-200 rounded-md px-4 py-2.5 text-sm" />
                            </div>
                            <div>
                                <label class="block text-xs font-black text-slate-500 uppercase tracking-widest mb-1.5">Basis</label>
                                <input type="text" name="basis" class="w-full border-2 border-slate-200 rounded-md px-4 py-2.5 text-sm" />
                            </div>
                            <div>
                                <label class="block text-xs font-black text-slate-500 uppercase tracking-widest mb-1.5">Period (months)</label>
                                <input type="number" name="period_months" class="w-full border-2 border-slate-200 rounded-md px-4 py-2.5 text-sm" />
                            </div>
                            <div>
                                <label class="block text-xs font-black text-slate-500 uppercase tracking-widest mb-1.5">Cost</label>
                                <input type="number" name="cost" step="0.01" class="w-full border-2 border-slate-200 rounded-md px-4 py-2.5 text-sm" />
                            </div>
                        </div>

                        <div class="grid grid-cols-4 gap-4 mt-4">
                            <div>
                                <label class="block text-xs font-black text-slate-500 uppercase tracking-widest mb-1.5">Start Date</label>
                                <input type="date" name="start_date" class="w-full border-2 border-slate-200 rounded-md px-4 py-2.5 text-sm" />
                            </div>
                            <div>
                                <label class="block text-xs font-black text-slate-500 uppercase tracking-widest mb-1.5">End Date</label>
                                <input type="date" name="end_date" class="w-full border-2 border-slate-200 rounded-md px-4 py-2.5 text-sm" />
                            </div>
                            <div>
                                <label class="block text-xs font-black text-slate-500 uppercase tracking-widest mb-1.5">Depreciated On</label>
                                <input type="text" name="depreciated_on" class="w-full border-2 border-slate-200 rounded-md px-4 py-2.5 text-sm" />
                            </div>
                            <div>
                                <label class="block text-xs font-black text-slate-500 uppercase tracking-widest mb-1.5">Day</label>
                                <input type="text" name="depr_day" class="w-full border-2 border-slate-200 rounded-md px-4 py-2.5 text-sm" />
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Step 3: Placement & Advanced Specs -->
                <div data-step="3" class="asset-step hidden">
                    <div class="grid grid-cols-2 gap-6">
                        <div>
                            <h3 class="text-sm font-black mb-2">Advanced</h3>
                            <label class="block text-xs font-black text-slate-500 uppercase tracking-widest mb-1.5">Item code</label>
                            <input type="text" name="item_code" class="w-full border-2 border-slate-200 rounded-xl px-4 py-2.5 text-sm mb-3" />
                            <label class="block text-xs font-black text-slate-500 uppercase tracking-widest mb-1.5">Quantity</label>
                            <input type="number" name="quantity" class="w-full border-2 border-slate-200 rounded-xl px-4 py-2.5 text-sm" />
                        </div>
                        <div>
                            <h3 class="text-sm font-black mb-2">Current In Use (Location)</h3>
                            <label class="block text-xs font-black text-slate-500 uppercase tracking-widest mb-1.5">Cost Center</label>
                            <input type="text" name="cost_center" class="w-full border-2 border-slate-200 rounded-xl px-4 py-2.5 text-sm mb-2" />
                            <label class="block text-xs font-black text-slate-500 uppercase tracking-widest mb-1.5">Main Zone</label>
                            <input type="text" name="main_zone" class="w-full border-2 border-slate-200 rounded-xl px-4 py-2.5 text-sm mb-2" />
                            <label class="block text-xs font-black text-slate-500 uppercase tracking-widest mb-1.5">Sub Zone</label>
                            <input type="text" name="sub_zone" class="w-full border-2 border-slate-200 rounded-xl px-4 py-2.5 text-sm mb-2" />
                            <label class="block text-xs font-black text-slate-500 uppercase tracking-widest mb-1.5">Region</label>
                            <input type="text" name="region" class="w-full border-2 border-slate-200 rounded-xl px-4 py-2.5 text-sm mb-2" />
                            <label class="block text-xs font-black text-slate-500 uppercase tracking-widest mb-1.5">Branch</label>
                            <input type="text" name="branch" class="w-full border-2 border-slate-200 rounded-xl px-4 py-2.5 text-sm" />
                        </div>
                    </div>

                    <!-- step actions moved to modal footer -->
                </div>
            </form>
        </div>
        <!-- Modal footer: centralized action buttons -->
        <div class="border-t border-slate-100 px-5 py-5 bg-white flex-none">
            <div class="max-w-[900px] mx-auto flex justify-center gap-5 items-center">
                <div>
                    <button type="button" data-action="prev" id="asset-btn-prev" class="hidden border-2 border-slate-200 text-slate-600 font-black text-xs uppercase tracking-widest px-4 py-3 rounded-md">Previous</button>
                </div>
                <div>
                    <button type="button" data-action="next" id="asset-btn-next" class="bg-[#ce1126] hover:bg-red-700 text-white font-black text-xs uppercase tracking-widest px-4 py-3 rounded-md">Next</button>
                    <button type="submit" id="asset-btn-save" class="hidden bg-[#ce1126] hover:bg-red-700 text-white font-black text-xs uppercase tracking-widest px-4 py-3 rounded-md">Save Asset</button>
                </div>
            </div>
        </div>
    </div>
</div>
