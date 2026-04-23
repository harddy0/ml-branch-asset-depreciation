<!-- ══════════════════════════════════════════════════ -->
<!-- SECTION 3: Depreciation Schedule                  -->
<!-- ══════════════════════════════════════════════════ -->
<section>
    <h3 class="text-xs font-black text-[#ce1126] uppercase tracking-widest border-b border-slate-200 pb-2 mb-4">
        Depreciation Schedule
    </h3>

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
            <input type="date" name="depreciation_start_date" id="depreciation_start_date" required
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
            <input type="date" name="depreciation_end_date" id="depreciation_end_date" required
                class="w-full text-sm font-mono border border-slate-300 rounded-lg px-3 py-2.5
                       focus:ring-2 focus:ring-red-500 outline-none transition-all bg-slate-50">
        </div>

        <div>
            <label class="block text-sm font-mono font-bold text-slate-700 mb-1">
                Depreciate On <span class="text-red-500">*</span>
            </label>
            <select name="depreciation_on" id="depreciation_on" required
                class="w-full text-sm font-mono border border-slate-300 rounded-lg px-3 py-2.5
                       focus:ring-2 focus:ring-red-500 outline-none transition-all bg-white">
                <option value="LAST_DAY" selected>Last Day of Month</option>
                <option value="FIRST_DAY">First Day of Month</option>
                <option value="SPECIFIC_DATE">Specific Date</option>
            </select>
        </div>

        <div>
            <label class="block text-sm font-mono font-bold text-slate-700 mb-1" id="depreciation_day_label">
                Specific Day
            </label>
            <input type="number" name="depreciation_day" id="depreciation_day"
                min="1" max="31" disabled
                class="w-full text-sm font-mono border border-slate-200 rounded-lg px-3 py-2.5
                       bg-slate-100 text-slate-400 outline-none transition-all cursor-not-allowed">
        </div>
    </div>

</section>
