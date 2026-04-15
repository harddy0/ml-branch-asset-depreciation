<!-- ══════════════════════════════════════════════════ -->
<!-- SECTION 2: Asset Details & Classification         -->
<!-- ══════════════════════════════════════════════════ -->
<section>
    <h3 class="text-xs font-black text-[#ce1126] uppercase tracking-widest border-b border-slate-200 pb-2 mb-4">
        Asset Details &amp; Classification
    </h3>

    <!-- Row 1: Description + Serial Number -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
        <div>
            <label class="block text-sm font-mono font-bold text-slate-700 mb-1">
                Asset Description <span class="text-red-500">*</span>
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
                <span class="text-slate-400 font-normal">(Optional)</span>
            </label>
            <input type="text" name="serial_number"
                placeholder="e.g. 1082018001"
                class="w-full text-sm font-mono border border-slate-300 rounded-lg px-3 py-2.5
                       focus:ring-2 focus:ring-red-500 outline-none transition-all">
        </div>
    </div>

    <!-- Row 2: Property Type + Reference Number -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
            <div>
                <label class="block text-sm font-mono font-bold text-slate-700 mb-1">
                    Property Type <span class="text-red-500">*</span>
                </label>
                <select name="property_type" required
                    class="w-full text-sm font-mono border border-slate-300 rounded-lg px-3 py-2.5
                        focus:ring-2 focus:ring-red-500 outline-none transition-all bg-white">
                    <option value="PURCHASED" selected>Purchased</option>
                    <option value="LEASE">Lease</option>
                    <option value="LEASEHOLD">Leasehold</option>
                    <option value="MAINTENANCE">Maintenance</option>
                </select>
            </div>
              
            <!-- Initial Status -->
            <div>
                <label class="block text-sm font-mono font-bold text-slate-700 mb-1">
                    Initial Status <span class="text-red-500">*</span>
                </label>
                <select name="status" required
                    class="w-full text-sm font-mono border border-slate-300 rounded-lg px-3 py-2.5
                        focus:ring-2 focus:ring-red-500 outline-none transition-all bg-white">
                    <option value="ACTIVE" selected>Active</option>
                    <option value="SOLD">Sold</option>
                    <option value="DISPOSED">Disposed</option>
                    <option value="INACTIVE">Inactive</option>
                </select>
            </div>
        </div>
        
        <div>
            <label class="block text-sm font-mono font-bold text-slate-700 mb-1">
                Reference Number
                <span class="text-slate-400 font-normal">(Optional)</span>
            </label>
            <input type="text" name="reference_no"
                placeholder="e.g. IS#10287545"
                class="w-full text-sm font-mono border border-slate-300 rounded-lg px-3 py-2.5
                       focus:ring-2 focus:ring-red-500 outline-none transition-all">
        </div>
    </div>

    <!-- Row 3: Quantity + Investment (acquisition_cost) + Item Code -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-4">
        <div>
            <label class="block text-sm font-mono font-bold text-slate-700 mb-1">
                Quantity <span class="text-red-500">*</span>
            </label>
            <input type="number" name="quantity" id="asset_quantity"
                value="1" min="1" required
                class="w-full text-sm font-mono border border-slate-300 rounded-lg px-3 py-2.5
                       focus:ring-2 focus:ring-red-500 outline-none transition-all">
        </div>
        <div>
            <label class="block text-sm font-mono font-bold text-slate-700 mb-1">
                Investment <span class="text-red-500">*</span>
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
                <span class="text-slate-400 font-normal">(Optional)</span>
            </label>
            <input type="text" name="item_code" id="asset_item_code"
                placeholder="e.g. ITM-00123"
                class="w-full text-sm font-mono border border-slate-300 rounded-lg px-3 py-2.5
                       focus:ring-2 focus:ring-red-500 outline-none transition-all">
        </div>
        
        <div>
            <label class="block text-sm font-mono font-bold text-slate-700 mb-1">
                Cost per Unit
                <span class="text-slate-400 font-normal">(Optional)</span>
            </label>
            <div class="relative">
                <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-slate-400 font-bold text-sm pointer-events-none">₱</span>
                <input type="text" inputmode="decimal" data-decimals="2" name="cost_unit" id="asset_cost_unit"
                    placeholder="0.00"
                    class="w-full text-sm font-mono border border-slate-300 rounded-lg pl-7 pr-3 py-2.5
                           focus:ring-2 focus:ring-red-500 outline-none transition-all currency-input">
            </div>
        </div>
        <input type="hidden" name="monthly_depreciation" id="monthly_depreciation" value="0.00">
    </div>

   
    

    <!-- ─── General Ledger Accounts ────────────────────── -->
    <div class="p-2">

        <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-3">
            General Ledger Accounts
        </p>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-3">
            <!-- Column 1: Group -->
            <div>
                <label class="block text-sm font-mono font-bold text-slate-700 mb-1">Group <span class="text-red-500">*</span></label>
                <select name="group_code" id="gl_group_select" required
                    class="w-full text-sm font-mono border border-slate-300 rounded-lg px-3 py-2.5
                           focus:ring-2 focus:ring-red-500 outline-none transition-all bg-white">
                    <option value="" disabled selected>Loading groups...</option>
                </select>
                <input type="text" id="gl_group_code_display"
                    readonly placeholder="Code auto-fills"
                    class="mt-2 w-full text-sm font-mono font-bold border border-slate-200 rounded-lg px-3 py-2.5
                           bg-slate-100 text-slate-500 outline-none cursor-default">
            </div>

            <!-- Column 2: Asset (auto) -->
            <div>
                <label class="block text-sm font-mono font-bold text-slate-600 mb-1">Asset <span class="text-[9px] font-bold text-slate-400 bg-slate-200 px-1.5 py-0.5 rounded uppercase tracking-wide">Auto</span></label>
                <input type="text" id="gl_asset_code_display" name="asset_code"
                    readonly placeholder="—"
                    class="w-full text-sm font-mono font-bold border border-slate-200 rounded-lg px-3 py-2.5
                           bg-slate-100 text-slate-500 outline-none cursor-default">
                <input type="text" id="gl_asset_name_display"
                    readonly placeholder="—"
                    class="mt-2 w-full text-sm font-mono border border-slate-200 rounded-lg px-3 py-2.5
                           bg-slate-100 text-slate-500 outline-none cursor-default">
            </div>

            <!-- Column 3: Depreciation (auto) -->
            <div>
                <label class="block text-sm font-mono font-bold text-slate-600 mb-1">Depreciation (P&amp;L) <span class="text-[9px] font-bold text-slate-400 bg-slate-200 px-1.5 py-0.5 rounded uppercase tracking-wide">Auto</span></label>
                <input type="text" id="gl_dep_code_display" name="depreciation_code"
                    readonly placeholder="—"
                    class="w-full text-sm font-mono font-bold border border-slate-200 rounded-lg px-3 py-2.5
                           bg-slate-100 text-slate-500 outline-none cursor-default">
                <input type="text" id="gl_dep_name_display"
                    readonly placeholder="—"
                    class="mt-2 w-full text-sm font-mono border border-slate-200 rounded-lg px-3 py-2.5
                           bg-slate-100 text-slate-500 outline-none cursor-default">
            </div>
        </div>
    </div>

</section>
