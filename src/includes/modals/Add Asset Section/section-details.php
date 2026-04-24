<!-- ══════════════════════════════════════════════════ -->
<!-- SECTION 2: Asset Details & Classification         -->
<!-- Complete rewrite: Asset Group selection + GL auto-fill + User inputs -->
<!-- ══════════════════════════════════════════════════ -->
<section>
    <h3 class="text-xs font-black text-[#ce1126] uppercase tracking-widest border-b border-slate-200 pb-2 mb-0">
        Asset Details &amp; Classification
    </h3>

    <!-- ═══ GROUP SELECTION SECTION (moved to bottom of section) ═══ -->
    <!-- placeholder — will be rendered after input fields -->

    <div class="border-t border-slate-200 mb-3"></div>

    <!-- ═══ ASSET DETAILS INPUT SECTION ═══ -->
    <div class="p-4 rounded-lg -mb-4">

        <!-- Row 0: Item Code (first) -->
        <div class="grid grid-cols-1 gap-4 mb-4">
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
        </div>

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

        <!-- Row 4: Investment Amount -->
        <div class="grid grid-cols-1 gap-4 mb-4">
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
        </div>
    </div>

    <!-- ═══ GROUP SELECTION & GL (moved to bottom) ═══ -->
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

        <!-- Combined GL Accounts Card: show both GLs in a compact table with amounts -->
        <div class="border border-slate-200 rounded-lg p-4 bg-white mb-3">
            <label class="block text-xs font-mono font-bold text-slate-600 mb-3 uppercase tracking-wide">GL Accounts</label>

            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <colgroup>
                        <col style="width:22%" />
                        <col style="width:48%" />
                        <col style="width:15%" />
                        <col style="width:15%" />
                    </colgroup>
                    <thead>
                        <tr class="text-xs text-slate-500 uppercase">
                            <th class="px-2 py-1 text-left">GL Code</th>
                            <th class="px-2 py-1 text-left">Description / Normal Balance</th>
                            <th class="px-2 py-1 text-right">Type</th>
                            <th class="px-2 py-1 text-right">Monthly Amount</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr class="border-t border-slate-100">
                            <td class="px-2 py-2 font-mono font-bold" id="gl-table-asset-code">—</td>
                            <td class="px-2 py-2 text-xs text-slate-700" id="gl-table-asset-desc">—</td>
                            <td class="px-2 py-2 text-right text-xs uppercase" id="gl-table-asset-type">—</td>
                            <td class="px-2 py-2 text-right font-mono font-bold" id="gl-table-asset-amount">—</td>
                        </tr>
                        <tr class="border-t border-slate-100">
                            <td class="px-2 py-2 font-mono font-bold" id="gl-table-depr-code">—</td>
                            <td class="px-2 py-2 text-xs text-slate-700" id="gl-table-depr-desc">—</td>
                            <td class="px-2 py-2 text-right text-xs uppercase" id="gl-table-depr-type">—</td>
                            <td class="px-2 py-2 text-right font-mono font-bold" id="gl-table-depr-amount">—</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Hidden fields for backend -->
    <input type="hidden" name="monthly_depreciation" id="monthly_depreciation" value="0.00">
    <input type="hidden" name="months" id="actual_months" value="0">
    <!-- Hidden GL fields (kept for JS and form compatibility) -->
    <input type="hidden" id="gl_asset_code" name="gl_asset_code" value="">
    <input type="hidden" id="gl_asset_type" name="gl_asset_type" value="">
    <input type="hidden" id="gl_asset_description" name="gl_asset_description" value="">
    <input type="hidden" id="gl_depreciation_code" name="gl_depreciation_code" value="">
    <input type="hidden" id="gl_depreciation_type" name="gl_depreciation_type" value="">
    <input type="hidden" id="gl_depreciation_description" name="gl_depreciation_description" value="">
