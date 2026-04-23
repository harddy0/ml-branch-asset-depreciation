<!-- ══════════════════════════════════════════════════ -->
<!-- SECTION 1: Location Data (already working)        -->
<!-- ══════════════════════════════════════════════════ -->
<section>
    <h3 class="text-xs font-black text-[#ce1126] uppercase tracking-widest border-b border-slate-200 pb-2 mb-4">
        Location Data
    </h3>

    <div class="space-y-2">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-1" style="grid-template-columns: 22% 78%;">
            <div class="flex items-center justify-end pr-2">
                <label class="block text-sm font-mono font-bold text-slate-700 mb-1">Branch Name <span class="text-red-500">*</span></label>
            </div>
            <div>
                <input id="branch_name_input" class="w-full text-sm font-mono border border-slate-300 rounded-lg px-3 py-2.5 focus:ring-2 focus:ring-red-500 outline-none transition-all bg-white" placeholder="Enter branch name">
                <input type="hidden" name="branch_name" id="branch_name">
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-1" style="grid-template-columns: 22% 78%;">
            <div class="flex items-center justify-end pr-2">
                <label class="block text-sm font-mono font-bold text-slate-700 mb-1">Branch Code <span class="text-red-500">*</span></label>
            </div>
                <div>
                    <input type="text" name="cost_center_code" id="cost_center_code" placeholder="Enter branch code" required class="w-full text-sm font-mono border border-slate-300 rounded-lg px-3 py-2.5 focus:ring-2 focus:ring-red-500 outline-none transition-all bg-white">
            </div>
        </div>
        
        <div class="grid grid-cols-1 md:grid-cols-2 gap-1" style="grid-template-columns: 22% 78%;">
            <div class="flex items-center justify-end pr-2">
                <label class="block text-sm font-mono font-bold text-slate-700 mb-1">Main Zone <span class="text-red-500">*</span></label>
            </div>
            <div>
                <select id="main_zone_code" disabled required class="w-full text-sm font-mono border border-slate-300 rounded-lg px-3 py-2.5 focus:ring-2 focus:ring-red-500 outline-none transition-all bg-white disabled:bg-slate-100 disabled:text-slate-400" style="appearance:none;-webkit-appearance:none;-moz-appearance:none;background-image:none;" aria-hidden="true">
                    <option value="" disabled selected>Enter branch name or branch code...</option>
                </select>
                <input type="hidden" name="main_zone_code" id="main_zone_code_hidden" required>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-1" style="grid-template-columns: 22% 78%;">
            <div class="flex items-center justify-end pr-2">
                <label class="block text-sm font-mono font-bold text-slate-700 mb-1">Sub-Zone <span class="text-red-500">*</span></label>
            </div>
            <div>
                <select id="zone_code" disabled required class="w-full text-sm font-mono border border-slate-300 rounded-lg px-3 py-2.5 focus:ring-2 focus:ring-red-500 outline-none transition-all bg-white disabled:bg-slate-100 disabled:text-slate-400" style="appearance:none;-webkit-appearance:none;-moz-appearance:none;background-image:none;" aria-hidden="true">
                    <option value="" disabled selected>Enter branch name or branch code...</option>
                </select>
                <input type="hidden" name="zone_code" id="zone_code_hidden" required>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-1" style="grid-template-columns: 22% 78%;">
            <div class="flex items-center justify-end pr-2">
                <label class="block text-sm font-mono font-bold text-slate-700 mb-1">Region <span class="text-red-500">*</span></label>
            </div>
            <div>
                <select id="region_code" disabled required class="w-full text-sm font-mono border border-slate-300 rounded-lg px-3 py-2.5 focus:ring-2 focus:ring-red-500 outline-none transition-all bg-white disabled:bg-slate-100 disabled:text-slate-400" style="appearance:none;-webkit-appearance:none;-moz-appearance:none;background-image:none;" aria-hidden="true">
                    <option value="" disabled selected>Enter branch name or branch code...</option>
                </select>
                <input type="hidden" name="region_code" id="region_code_hidden" required>
            </div>
        </div>
    </div>
    <div class="space-y-2 mt-4">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-1" style="grid-template-columns: 22% 78%;">
            <div class="flex items-center justify-end pr-2">
                <label class="block text-sm font-mono font-bold text-slate-700 mb-1">BOS Code</label>
            </div>
            <div>
                <input type="text" id="bos_branch_code_display" class="w-full text-sm font-mono border border-slate-300 rounded-lg px-3 py-2.5 bg-slate-50" placeholder="Auto-populated" readonly>
                <input type="hidden" name="bos_branch_code" id="bos_branch_code">
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-1" style="grid-template-columns: 22% 78%;">
            <div class="flex items-center justify-end pr-2">
                <label class="block text-sm font-mono font-bold text-slate-700 mb-1">KPX Branch ID</label>
            </div>
            <div>
                <input type="text" id="kpx_branch_id_display" class="w-full text-sm font-mono border border-slate-300 rounded-lg px-3 py-2.5 bg-slate-50" placeholder="Auto-populated" readonly>
                <input type="hidden" name="kpx_branch_id" id="kpx_branch_id">
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-1" style="grid-template-columns: 22% 78%;">
            <div class="flex items-center justify-end pr-2">
                <label class="block text-sm font-mono font-bold text-slate-700 mb-1">Corporate Name</label>
            </div>
            <div>
                <input type="text" id="corporate_name_display" class="w-full text-sm font-mono border border-slate-300 rounded-lg px-3 py-2.5" placeholder="Auto-populated or enter manually">
                <input type="hidden" name="corporate_name" id="corporate_name">
            </div>
        </div>
    </div>

</section>
