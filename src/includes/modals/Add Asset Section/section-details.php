<!-- ══════════════════════════════════════════════════ -->
<!-- SECTION 2: Asset Details & Classification         -->
<!-- Complete rewrite: Asset Group selection + GL auto-fill + User inputs -->
<!-- ══════════════════════════════════════════════════ -->
<section>
    <h3 class="text-xs font-black text-[#ce1126] uppercase tracking-widest border-b border-slate-200 pb-2 mb-0">
        Asset Details &amp; Classification
    </h3>

    <!-- ═══ GROUP SELECTION SECTION ═══ -->
    <div class="mb-0 p-4 rounded-lg">

        <div class="grid grid-cols-2 gap-4 mb-4">
            <div>
                <label class="block text-sm font-mono font-bold text-slate-700 mb-2">
                    Expense Type <span class="text-red-500">*</span>
                </label>
                <select name="expense_type_id" id="expense_type_select" required
                    class="w-full text-sm font-mono border border-slate-300 rounded-lg px-3 py-2.5
                           focus:ring-2 focus:ring-red-500 outline-none transition-all bg-white">
                    <option value="" disabled selected>Loading expense types...</option>
                </select>
            </div>

            <div>
                <label class="block text-sm font-mono font-bold text-slate-700 mb-2">
                    Asset Group <span class="text-red-500">*</span>
                </label>
                <select name="asset_group_id" id="asset_group_select" required disabled
                    class="w-full text-sm font-mono border border-slate-300 rounded-lg px-3 py-2.5
                           focus:ring-2 focus:ring-red-500 outline-none transition-all bg-white">
                    <option value="" disabled selected>Select Expense Type first</option>
                </select>
            </div>
        </div>

        <!-- GL ACCOUNT AUTO-FILL GRID (2 columns: Asset GL | Depreciation GL) -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-3">
            <!-- LEFT COLUMN: Asset GL -->
            <div class="border border-slate-200 rounded-lg p-4 bg-white">
                <label class="block text-xs font-mono font-bold text-slate-600 mb-3 uppercase tracking-wide">GL Asset Account</label>
                
                <div class="grid grid-cols-2 gap-4 mb-2">
                    <div>
                        <label class="block text-xs font-mono font-bold text-slate-500 mb-1">Code</label>
                        <input type="text" id="gl_asset_code" readonly
                            placeholder="—"
                            class="w-full text-sm font-mono font-bold border border-slate-200 rounded px-3 py-2
                                bg-slate-100 text-slate-700 outline-none cursor-default">
                    </div>
                    <div>
                        <label class="block text-xs font-mono font-bold text-slate-500 mb-1">Normal balance</label>
                        <input type="text" id="gl_asset_type" readonly
                            placeholder="—"
                            class="w-full text-sm font-mono font-bold border border-slate-200 rounded px-3 py-2
                                bg-slate-100 text-slate-700 outline-none cursor-default">
                    </div>
                </div>
                
                <div>
                    <label class="block text-xs font-mono font-bold text-slate-500 mb-1">Description</label>
                    <textarea id="gl_asset_description" readonly
                        placeholder="—"
                        class="w-full text-sm font-mono border border-slate-200 rounded px-3 py-1
                               bg-slate-100 text-slate-700 outline-none cursor-default resize-none"
                        rows="3"></textarea>
                </div>
            </div>

            <!-- RIGHT COLUMN: Depreciation GL -->
            <div class="border border-slate-200 rounded-lg p-4 bg-white">
                <label class="block text-xs font-mono font-bold text-slate-600 mb-3 uppercase tracking-wide">GL Depreciation (P&L)</label>
                
                <div class="grid grid-cols-2 gap-4 mb-2">
                     <div>
                        <label class="block text-xs font-mono font-bold text-slate-500 mb-1">Code</label>
                        <input type="text" id="gl_depreciation_code" readonly
                            placeholder="—"
                            class="w-full text-sm font-mono font-bold border border-slate-200 rounded px-3 py-2
                                bg-slate-100 text-slate-700 outline-none cursor-default">
                    </div>

                    <div>
                        <label class="block text-xs font-mono font-bold text-slate-500 mb-1">Normal Balance</label>
                        <input type="text" id="gl_depreciation_type" readonly
                            placeholder="—"
                            class="w-full text-sm font-mono font-bold border border-slate-200 rounded px-3 py-2
                                bg-slate-100 text-slate-700 outline-none cursor-default">
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-mono font-bold text-slate-500 mb-1">Description</label>
                    <textarea id="gl_depreciation_description" readonly
                        placeholder="—"
                        class="w-full text-sm font-mono border border-slate-200 rounded px-3 py-1
                               bg-slate-100 text-slate-700 outline-none cursor-default resize-none"
                        rows="3"></textarea>
                </div>
            </div>
        </div>
    </div>

    <div class="border-t border-slate-200 mb-3"></div>

    <!-- ═══ ASSET DETAILS INPUT SECTION ═══ -->
    <div class="p-4 rounded-lg -mb-4">

        <!-- Row 1: Description + Serial Number -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
            <div>
                <label class="block text-sm font-mono font-bold text-slate-700 mb-1">
                    Description <span class="text-red-500">*</span>
                </label>
                <input type="text" name="description" id="asset_description"
                    placeholder="e.g. Touch Screen Electronic LM Unit"
                    required
                    class="w-full text-sm font-mono border border-slate-300 rounded-lg px-3 py-2.5
                           focus:ring-2 focus:ring-red-500 outline-none transition-all">
            </div>
            <div>
                <label class="block text-sm font-mono font-bold text-slate-700 mb-1">
                    Serial Number
                    <span class="text-slate-400 font-mono text-xs">Type N/A if none</span>
                </label>
                <input type="text" name="serial_number" id="serial_number"
                    placeholder="e.g. 1082018001"
                    class="w-full text-sm font-mono uppercase border border-slate-300 rounded-lg px-3 py-2.5
                           focus:ring-2 focus:ring-red-500 outline-none transition-all">
            </div>
        </div>

        <!-- Row 2: Property Type + Status -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
            <div>
                <label class="block text-sm font-mono font-bold text-slate-700 mb-1">
                    Property Type <span class="text-red-500">*</span>
                </label>
                <select name="property_type" id="property_type" required
                    class="w-full text-sm font-mono border border-slate-300 rounded-lg px-3 py-2.5
                           focus:ring-2 focus:ring-red-500 outline-none transition-all bg-white">
                    <option value="PURCHASED" selected>Purchased</option>
                    <option value="LEASE">Lease</option>
                    <option value="LEASEHOLD">Leasehold</option>
                    <option value="MAINTENANCE">Maintenance</option>
                </select>
            </div>
            <div>
                <label class="block text-sm font-mono font-bold text-slate-700 mb-1">
                    Status <span class="text-red-500">*</span>
                </label>
                <select name="status" id="status" required
                    class="w-full text-sm font-mono border border-slate-300 rounded-lg px-3 py-2.5
                           focus:ring-2 focus:ring-red-500 outline-none transition-all bg-white">
                    <option value="ACTIVE" selected>Active</option>
                    <option value="SOLD">Sold</option>
                    <option value="DISPOSED">Disposed</option>
                    <option value="INACTIVE">Inactive</option>
                </select>
            </div>
        </div>

        <!-- Row 3: Reference Number + Quantity -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
            <div>
                <label class="block text-sm font-mono font-bold text-slate-700 mb-1">
                    Reference Number
                    <span class="text-slate-400 font-mono text-xs">Type N/A if none</span>
                </label>
                <input type="text" name="reference_no" id="reference_no"
                    placeholder="e.g. IS#10287545"
                    class="w-full uppercase text-sm font-mono border border-slate-300 rounded-lg px-3 py-2.5
                           focus:ring-2 focus:ring-red-500 outline-none transition-all">
            </div>
            <div>
                <label class="block text-sm font-mono font-bold text-slate-700 mb-1">
                    Quantity <span class="text-red-500">*</span>
                </label>
                <input type="number" name="quantity" id="asset_quantity"
                    value="1" min="1" required
                    class="w-full text-sm font-mono border border-slate-300 rounded-lg px-3 py-2.5
                           focus:ring-2 focus:ring-red-500 outline-none transition-all">
            </div>
        </div>

        <!-- Row 4: Investment Amount + Item Code + Cost Per Unit -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
            <div>
                <label class="block text-sm font-mono font-bold text-slate-700 mb-1">
                    Investment Amount<span class="text-red-500">*</span>
                </label>
                <div class="relative">
                    <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-slate-500 font-black pointer-events-none">₱</span>
                    <input type="text" inputmode="decimal" data-decimals="2" name="acquisition_cost" id="asset_acquisition_cost"
                        placeholder="0.00" required
                        class="w-full text-sm font-mono border border-slate-300 rounded-lg pl-7 pr-3 py-2.5
                               focus:ring-2 focus:ring-red-500 outline-none transition-all currency-input">
                </div>
            </div>
            <div>
                <label class="block text-sm font-mono font-bold text-slate-700 mb-1">
                    Item Code
                    <span class="text-slate-400 font-mono text-xs">Type N/A if none</span>
                </label>
                <input type="text" name="item_code" id="asset_item_code"
                    placeholder="e.g. ITM-00123"
                    class="w-full text-sm font-mono uppercase border border-slate-300 rounded-lg px-3 py-2.5
                           focus:ring-2 focus:ring-red-500 outline-none transition-all">
            </div>
            <div>
                <label class="block text-sm font-mono font-bold text-slate-700 mb-1">
                    Cost Per Unit
                    <span class="text-slate-400 font-mono text-xs">Optional</span>
                </label>
                <div class="relative">
                    <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-slate-400 font-bold text-sm pointer-events-none">₱</span>
                    <input type="text" inputmode="decimal" data-decimals="2" name="cost_unit" id="asset_cost_unit"
                        placeholder="0.00"
                        class="w-full text-sm font-mono border border-slate-300 rounded-lg pl-7 pr-3 py-2.5
                               focus:ring-2 focus:ring-red-500 outline-none transition-all currency-input">
                </div>
            </div>
        </div>
    </div>

    <!-- Hidden fields for backend -->
    <input type="hidden" name="monthly_depreciation" id="monthly_depreciation" value="0.00">
    <input type="hidden" name="months" id="actual_months" value="0">
