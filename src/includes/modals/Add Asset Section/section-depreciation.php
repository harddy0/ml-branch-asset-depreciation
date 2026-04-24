<section>
    <h3 class="text-xs text-center font-black text-[#ce1126] uppercase tracking-widest pb-2 mb-2">
        Depreciation Schedule
    </h3>

    <input type="hidden" name="depreciation_on" id="depreciation_on" value="SPECIFIC_DATE">
    <input type="hidden" name="depreciation_day" id="depreciation_day" value="1">

    <div class="grid grid-cols-1 gap-4 mb-4">
        <div>
            <label class="block text-sm font-mono font-bold text-slate-700 mb-1">
                Date Received <span class="text-red-500">*</span>
            </label>
            <input type="date" name="date_received" id="date_received" required
                class="w-full text-sm font-mono border border-slate-300 rounded-lg px-3 py-2.5
                       focus:ring-2 focus:ring-red-500 outline-none transition-all">
        </div>

        <div>
            <label class="block text-sm font-mono font-bold text-slate-700 mb-1">
                Depreciation Start Date <span class="text-red-500">*</span>
            </label>
            <input type="date" name="depreciation_start_date" id="depreciation_start_date" 
                required autocomplete="off" value=""
                class="w-full text-sm font-mono border border-slate-300 rounded-lg px-3 py-2.5
                       focus:ring-2 focus:ring-red-500 outline-none transition-all">
        </div>

        <div>
            <label class="block text-sm font-mono font-bold text-slate-700 mb-1 flex items-center gap-1.5">
                Depreciation End Date <span class="text-red-500">*</span>
                <span id="end_date_auto_badge"
                    class="text-[9px] font-bold text-slate-400 bg-slate-200 px-1.5 py-0.5 rounded uppercase tracking-wide">
                    Auto
                </span>
            </label>
            <input type="date" name="depreciation_end_date" id="depreciation_end_date" required readonly tabindex="-1" aria-readonly="true" title="Auto-calculated - not editable"
                class="w-full text-sm font-mono border border-slate-300 rounded-lg px-3 py-2.5
                       focus:ring-2 focus:ring-red-500 outline-none transition-all bg-slate-50 cursor-not-allowed">
        </div>
    </div>
</section>