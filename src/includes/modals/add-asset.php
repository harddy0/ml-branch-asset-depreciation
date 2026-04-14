<div id="modal-add-asset" class="fixed inset-0 z-50 hidden bg-slate-900/60 backdrop-blur-sm flex items-center justify-center p-4 sm:p-6">
    <div class="bg-white rounded-xl shadow-2xl w-full max-w-4xl max-h-[90vh] flex flex-col overflow-hidden animate-fadeIn">
        
        <!-- Modal Header -->
        <div class="px-6 py-4 border-b border-slate-200 flex justify-between items-center bg-slate-50">
            <h2 class="text-lg font-black text-slate-800 uppercase tracking-wide flex items-center gap-2">
                <svg class="w-5 h-5 text-[#ce1126]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                Add New Asset
            </h2>
            <button type="button" onclick="closeModal('modal-add-asset')" class="text-slate-400 hover:text-red-600 transition-colors">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>

        <!-- Modal Body -->
        <div class="flex-1 overflow-y-auto p-6 bg-white">
            <form id="addAssetForm" class="space-y-8">

                <!-- ══════════════════════════════════════════════════ -->
                <!-- SECTION 1: Location Data (already working)        -->
                <!-- ══════════════════════════════════════════════════ -->
                <section>
                    <h3 class="text-xs font-black text-[#ce1126] uppercase tracking-widest border-b border-slate-200 pb-2 mb-4">
                        1. Location Data
                    </h3>

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
                        <div>
                            <label class="block text-xs font-bold text-slate-700 mb-1">
                                Main Zone <span class="text-red-500">*</span>
                            </label>
                            <select name="main_zone_code" id="main_zone_code" required
                                class="w-full text-sm border border-slate-300 rounded-lg px-3 py-2.5
                                       focus:ring-2 focus:ring-red-500 outline-none transition-all bg-white
                                       disabled:bg-slate-100 disabled:text-slate-400">
                                <option value="" disabled selected>Loading...</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-700 mb-1">
                                Sub-Zone <span class="text-red-500">*</span>
                            </label>
                            <select name="zone_code" id="zone_code" required
                                class="w-full text-sm border border-slate-300 rounded-lg px-3 py-2.5
                                       focus:ring-2 focus:ring-red-500 outline-none transition-all bg-white
                                       disabled:bg-slate-100 disabled:text-slate-400">
                                <option value="" disabled selected>Loading...</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-700 mb-1">
                                Region <span class="text-red-500">*</span>
                            </label>
                            <select name="region_code" id="region_code" required
                                class="w-full text-sm border border-slate-300 rounded-lg px-3 py-2.5
                                       focus:ring-2 focus:ring-red-500 outline-none transition-all bg-white
                                       disabled:bg-slate-100 disabled:text-slate-400">
                                <option value="" disabled selected>Loading...</option>
                            </select>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                        <div>
                            <label class="block text-xs font-bold text-slate-700 mb-1">
                                Branch Name <span class="text-red-500">*</span>
                            </label>
                            <select name="branch_name" id="branch_name" required
                                class="w-full text-sm border border-slate-300 rounded-lg px-3 py-2.5
                                       focus:ring-2 focus:ring-red-500 outline-none transition-all bg-white
                                       disabled:bg-slate-100 disabled:text-slate-400">
                                <option value="" disabled selected>Loading...</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-700 mb-1">
                                Cost Center Code <span class="text-red-500">*</span>
                            </label>
                            <input type="text" name="cost_center_code" id="cost_center_code"
                                placeholder="Auto-fills upon branch selection"
                                readonly required
                                class="w-full text-sm border border-slate-200 rounded-lg px-3 py-2.5
                                       bg-slate-50 text-slate-600 outline-none transition-all">
                        </div>
                    </div>
                </section>

                <!-- ══════════════════════════════════════════════════ -->
                <!-- SECTION 2: Asset Details & Classification         -->
                <!-- ══════════════════════════════════════════════════ -->
                <section>
                    <h3 class="text-xs font-black text-[#ce1126] uppercase tracking-widest border-b border-slate-200 pb-2 mb-4">
                        2. Asset Details &amp; Classification
                    </h3>

                    <!-- Row 1: Description + Serial Number -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                        <div>
                            <label class="block text-xs font-bold text-slate-700 mb-1">
                                Asset Description <span class="text-red-500">*</span>
                            </label>
                            <input type="text" name="description" id="asset_description"
                                placeholder="e.g. Touch Screen Electronic LM Unit"
                                required
                                class="w-full text-sm border border-slate-300 rounded-lg px-3 py-2.5
                                       focus:ring-2 focus:ring-red-500 outline-none transition-all">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-700 mb-1">
                                Serial Number
                                <span class="text-slate-400 font-normal">(Optional)</span>
                            </label>
                            <input type="text" name="serial_number"
                                placeholder="e.g. 1082018001"
                                class="w-full text-sm border border-slate-300 rounded-lg px-3 py-2.5
                                       focus:ring-2 focus:ring-red-500 outline-none transition-all">
                        </div>
                    </div>

                    <!-- Row 2: Property Type + Reference Number -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                        <div>
                            <label class="block text-xs font-bold text-slate-700 mb-1">
                                Property Type <span class="text-red-500">*</span>
                            </label>
                            <select name="property_type" required
                                class="w-full text-sm border border-slate-300 rounded-lg px-3 py-2.5
                                       focus:ring-2 focus:ring-red-500 outline-none transition-all bg-white">
                                <option value="PURCHASED" selected>Purchased</option>
                                <option value="LEASE">Lease</option>
                                <option value="LEASEHOLD">Leasehold</option>
                                <option value="MAINTENANCE">Maintenance</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-700 mb-1">
                                Reference Number
                                <span class="text-slate-400 font-normal">(Optional)</span>
                            </label>
                            <input type="text" name="reference_no"
                                placeholder="e.g. IS#10287545"
                                class="w-full text-sm border border-slate-300 rounded-lg px-3 py-2.5
                                       focus:ring-2 focus:ring-red-500 outline-none transition-all">
                        </div>
                    </div>

                    <!-- Row 3: Quantity + Investment (acquisition_cost) + Item Code -->
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
                        <div>
                            <label class="block text-xs font-bold text-slate-700 mb-1">
                                Quantity <span class="text-red-500">*</span>
                            </label>
                            <input type="number" name="quantity" id="asset_quantity"
                                value="1" min="1" required
                                class="w-full text-sm border border-slate-300 rounded-lg px-3 py-2.5
                                       focus:ring-2 focus:ring-red-500 outline-none transition-all">
                            <p class="text-[10px] text-slate-400 mt-1">Use 1 for services or one-off items</p>
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-700 mb-1">
                                Investment <span class="text-red-500">*</span>
                            </label>
                            <div class="relative">
                                <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-slate-500 font-black pointer-events-none">₱</span>
                                <input type="number" step="0.01" min="0" name="acquisition_cost" id="asset_acquisition_cost"
                                    placeholder="0.00" required
                                    class="w-full text-sm border border-slate-300 rounded-lg pl-7 pr-3 py-2.5
                                           focus:ring-2 focus:ring-red-500 outline-none transition-all">
                            </div>
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-700 mb-1">
                                Item Code
                                <span class="text-slate-400 font-normal">(Optional)</span>
                            </label>
                            <input type="text" name="item_code" id="asset_item_code"
                                placeholder="e.g. ITM-00123"
                                class="w-full text-sm border border-slate-300 rounded-lg px-3 py-2.5
                                       focus:ring-2 focus:ring-red-500 outline-none transition-all">
                        </div>
                    </div>

                    <!-- Row 4: Cost per Unit (standalone, lower) -->
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
                        <div>
                            <label class="block text-xs font-bold text-slate-700 mb-1">
                                Cost per Unit
                                <span class="text-slate-400 font-normal">(Optional)</span>
                            </label>
                            <div class="relative">
                                <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-slate-400 font-bold text-sm pointer-events-none">₱</span>
                                <input type="number" step="0.01" min="0" name="cost_unit" id="asset_cost_unit"
                                    placeholder="0.00"
                                    class="w-full text-sm border border-slate-300 rounded-lg pl-7 pr-3 py-2.5
                                           focus:ring-2 focus:ring-red-500 outline-none transition-all">
                            </div>
                        </div>
                    </div>

                    <input type="hidden" name="monthly_depreciation" id="monthly_depreciation" value="0.00">

                    <!-- ─── General Ledger Accounts ────────────────────── -->
                    <div class="bg-slate-50 border border-slate-200 rounded-xl p-4">

                        <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-3">
                            General Ledger Accounts
                        </p>

                        <!-- GL Row 1: Group (user selects) -->
                        <div class="mb-3">
                            <label class="block text-xs font-bold text-slate-700 mb-1">
                                Group <span class="text-red-500">*</span>
                            </label>
                            <div class="grid grid-cols-2 gap-3">
                                <!-- LEFT: User-facing dropdown — this IS the submitted value -->
                                <select name="group_code" id="gl_group_select" required
                                    class="text-sm border border-slate-300 rounded-lg px-3 py-2.5
                                           focus:ring-2 focus:ring-red-500 outline-none transition-all bg-white">
                                    <option value="" disabled selected>Loading groups...</option>
                                </select>
                                <!-- RIGHT: Auto-filled code display (read-only) -->
                                <input type="text" id="gl_group_code_display"
                                    readonly placeholder="Code auto-fills"
                                    class="text-sm font-mono font-bold border border-slate-200 rounded-lg px-3 py-2.5
                                           bg-slate-100 text-slate-500 outline-none cursor-default">
                            </div>
                            <p class="text-[10px] text-slate-400 mt-1">Select a group — Asset and Depreciation fields will auto-fill.</p>
                        </div>

                        <!-- GL Row 2: Asset (auto-filled, read-only) -->
                        <div class="mb-3">
                            <label class="block text-xs font-bold text-slate-600 mb-1 flex items-center gap-1.5">
                                Asset
                                <span class="text-[9px] font-bold text-slate-400 bg-slate-200 px-1.5 py-0.5 rounded uppercase tracking-wide">Auto</span>
                            </label>
                            <div class="grid grid-cols-2 gap-3">
                                <input type="text" id="gl_asset_code_display" name="asset_code"
                                    readonly placeholder="—"
                                    class="text-sm font-mono font-bold border border-slate-200 rounded-lg px-3 py-2.5
                                           bg-slate-100 text-slate-500 outline-none cursor-default">
                                <input type="text" id="gl_asset_name_display"
                                    readonly placeholder="—"
                                    class="text-sm border border-slate-200 rounded-lg px-3 py-2.5
                                           bg-slate-100 text-slate-500 outline-none cursor-default">
                            </div>
                        </div>

                        <!-- GL Row 3: Depreciation P&L (auto-filled, read-only) -->
                        <div>
                            <label class="block text-xs font-bold text-slate-600 mb-1 flex items-center gap-1.5">
                                Depreciation (P&amp;L)
                                <span class="text-[9px] font-bold text-slate-400 bg-slate-200 px-1.5 py-0.5 rounded uppercase tracking-wide">Auto</span>
                            </label>
                            <div class="grid grid-cols-2 gap-3">
                                <input type="text" id="gl_dep_code_display" name="depreciation_code"
                                    readonly placeholder="—"
                                    class="text-sm font-mono font-bold border border-slate-200 rounded-lg px-3 py-2.5
                                           bg-slate-100 text-slate-500 outline-none cursor-default">
                                <input type="text" id="gl_dep_name_display"
                                    readonly placeholder="—"
                                    class="text-sm border border-slate-200 rounded-lg px-3 py-2.5
                                           bg-slate-100 text-slate-500 outline-none cursor-default">
                            </div>
                        </div>

                    </div>
                    <!-- ─── /General Ledger Accounts ──────────────────── -->

                    <!-- Initial Status -->
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mt-4">
                        <div>
                            <label class="block text-xs font-bold text-slate-700 mb-1">
                                Initial Status <span class="text-red-500">*</span>
                            </label>
                            <select name="status" required
                                class="w-full text-sm border border-slate-300 rounded-lg px-3 py-2.5
                                       focus:ring-2 focus:ring-red-500 outline-none transition-all bg-white">
                                <option value="ACTIVE" selected>Active</option>
                                <option value="SOLD">Sold</option>
                                <option value="DISPOSED">Disposed</option>
                                <option value="INACTIVE">Inactive</option>
                            </select>
                        </div>
                    </div>

                </section>

                <!-- ══════════════════════════════════════════════════ -->
                <!-- SECTION 3: Depreciation Schedule                  -->
                <!-- ══════════════════════════════════════════════════ -->
                <section>
                    <h3 class="text-xs font-black text-[#ce1126] uppercase tracking-widest border-b border-slate-200 pb-2 mb-4">
                        3. Depreciation Schedule
                    </h3>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                        <div>
                            <label class="block text-xs font-bold text-slate-700 mb-1">
                                Date Received <span class="text-red-500">*</span>
                            </label>
                            <input type="date" name="date_received" id="date_received" required
                                class="w-full text-sm border border-slate-300 rounded-lg px-3 py-2.5
                                       focus:ring-2 focus:ring-red-500 outline-none transition-all">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-700 mb-1 flex items-center gap-1.5">
                                Depreciation End Date <span class="text-red-500">*</span>
                                <span id="end_date_auto_badge"
                                    class="text-[9px] font-bold text-slate-400 bg-slate-200 px-1.5 py-0.5 rounded uppercase tracking-wide">
                                    Auto
                                </span>
                            </label>
                            <input type="date" name="depreciation_end_date" id="depreciation_end_date" required
                                class="w-full text-sm border border-slate-300 rounded-lg px-3 py-2.5
                                       focus:ring-2 focus:ring-red-500 outline-none transition-all bg-slate-50">
                            <p id="end_date_hint" class="text-[10px] text-slate-400 mt-1">
                                Auto-computed based on Date Received and Schedule setting. You can override.
                            </p>
                        </div>
                    </div>
                    
                    <input type="hidden" name="depreciation_start_date" id="depreciation_start_date">

                    <!-- Row 3: Depreciate On + Specific Day -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-bold text-slate-700 mb-1">
                                Depreciate On <span class="text-red-500">*</span>
                            </label>
                            <select name="depreciation_on" id="depreciation_on" required
                                class="w-full text-sm border border-slate-300 rounded-lg px-3 py-2.5
                                       focus:ring-2 focus:ring-red-500 outline-none transition-all bg-white">
                                <option value="LAST_DAY" selected>Last Day of Month</option>
                                <option value="FIRST_DAY">First Day of Month</option>
                                <option value="SPECIFIC_DATE">Specific Date</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-700 mb-1" id="depreciation_day_label">
                                Specific Day
                            </label>
                            <input type="number" name="depreciation_day" id="depreciation_day"
                                min="1" max="31" disabled
                                class="w-full text-sm border border-slate-200 rounded-lg px-3 py-2.5
                                       bg-slate-100 text-slate-400 outline-none transition-all cursor-not-allowed">
                        </div>
                    </div>

                </section>

            </form>
        </div>

        <!-- Modal Footer -->
        <div class="px-6 py-4 border-t border-slate-200 flex justify-end gap-3 bg-slate-50">
            <button type="button" onclick="closeModal('modal-add-asset')"
                class="px-5 py-2 text-sm font-bold text-slate-600 bg-white border border-slate-300
                       rounded-lg hover:bg-slate-50 transition-colors">
                Cancel
            </button>
            <button type="submit" form="addAssetForm"
                class="px-5 py-2 text-sm font-bold text-white bg-[#ce1126] rounded-lg
                       hover:bg-red-700 shadow-sm transition-colors uppercase tracking-wide">
                Save Asset
            </button>
        </div>

    </div>
</div>