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

            <form id="add-asset-form" method="POST" action="<?= BASE_URL ?>/public/actions/asset_store.php" class="px-7 py-6">

                <!-- Progress -->
                <div class="mb-4">
                    <div class="w-full bg-slate-100 rounded-full h-2 overflow-hidden">
                        <div id="asset-wizard-progress" class="bg-[#ce1126] h-2 transition-all duration-300" style="width:33.33%"></div>
                    </div>
                    <div class="text-xs text-slate-500 mt-2">Step <span id="asset-wizard-step">1</span> of 3</div>
                </div>

                <!-- ══════════════════════════════════════════════════════
                     STEP 1 — Core Asset Information
                     Fields: description, serial_number, reference_no,
                             date_received, acquisition_cost,
                             property_type, quantity, status
                ════════════════════════════════════════════════════════ -->
                <div data-step="1" class="asset-step">
                    <div class="border border-slate-100 shadow-md rounded-md w-full max-w-[900px] mx-auto min-h-[320px] pt-6 px-6 pb-6">

                        <!-- Description (full width) -->
                        <div class="mb-4">
                            <label class="block text-xs font-black text-slate-500 uppercase tracking-widest mb-1.5">
                                Description <span class="text-red-600">*</span>
                            </label>
                            <textarea name="description" rows="2" required
                                placeholder="e.g. Touch Screen Electronic LM Unit"
                                class="w-full border-2 border-slate-200 focus:border-red-500 rounded-md px-4 py-2.5 text-sm outline-none transition-colors resize-none"></textarea>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                            <!-- Serial Number -->
                            <div>
                                <label class="block text-xs font-black text-slate-500 uppercase tracking-widest mb-1.5">
                                    Serial Number
                                </label>
                                <input type="text" name="serial_number"
                                    placeholder="e.g. 1082018001"
                                    class="w-full border-2 border-slate-200 focus:border-red-500 rounded-md px-4 py-2.5 text-sm outline-none transition-colors" />
                            </div>
                            <!-- Reference No -->
                            <div>
                                <label class="block text-xs font-black text-slate-500 uppercase tracking-widest mb-1.5">
                                    Reference / IS Number
                                </label>
                                <input type="text" name="reference_no"
                                    placeholder="e.g. IS#10287545"
                                    class="w-full border-2 border-slate-200 focus:border-red-500 rounded-md px-4 py-2.5 text-sm outline-none transition-colors" />
                            </div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
                            <!-- Date Received -->
                            <div>
                                <label class="block text-xs font-black text-slate-500 uppercase tracking-widest mb-1.5">
                                    Date Received <span class="text-red-600">*</span>
                                </label>
                                <input type="date" name="date_received" required
                                    class="w-full border-2 border-slate-200 focus:border-red-500 rounded-md px-4 py-2.5 text-sm outline-none transition-colors" />
                            </div>
                            <!-- Acquisition Cost -->
                            <div>
                                <label class="block text-xs font-black text-slate-500 uppercase tracking-widest mb-1.5">
                                    Acquisition Cost <span class="text-red-600">*</span>
                                </label>
                                <div class="relative">
                                    <span class="absolute left-3 top-1/2 -translate-y-1/2 text-slate-500 font-semibold select-none text-sm">₱</span>
                                    <input type="number" name="acquisition_cost" required step="0.01" min="0.01"
                                        placeholder="0.00"
                                        class="w-full border-2 border-slate-200 focus:border-red-500 rounded-md pl-8 pr-4 py-2.5 text-sm outline-none transition-colors" />
                                </div>
                            </div>
                            <!-- Quantity -->
                            <div>
                                <label class="block text-xs font-black text-slate-500 uppercase tracking-widest mb-1.5">
                                    Quantity
                                </label>
                                <input type="number" name="quantity" min="1" value="1"
                                    class="w-full border-2 border-slate-200 focus:border-red-500 rounded-md px-4 py-2.5 text-sm outline-none transition-colors" />
                            </div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <!-- Property Type -->
                            <div>
                                <label class="block text-xs font-black text-slate-500 uppercase tracking-widest mb-1.5">
                                    Property Type <span class="text-red-600">*</span>
                                </label>
                                <select name="property_type" required
                                    class="w-full border-2 border-slate-200 focus:border-red-500 rounded-md px-4 py-2.5 text-sm outline-none transition-colors bg-white">
                                    <option value="PURCHASED" selected>Purchased</option>
                                    <option value="LEASE">Lease</option>
                                    <option value="LEASEHOLD">Leasehold Improvement</option>
                                    <option value="MAINTENANCE">Capitalized Maintenance</option>
                                </select>
                            </div>
                            <!-- Status -->
                            <div>
                                <label class="block text-xs font-black text-slate-500 uppercase tracking-widest mb-1.5">
                                    Status <span class="text-red-600">*</span>
                                </label>
                                <select name="status" required
                                    class="w-full border-2 border-slate-200 focus:border-red-500 rounded-md px-4 py-2.5 text-sm outline-none transition-colors bg-white">
                                    <option value="ACTIVE" selected>Active</option>
                                    <option value="SOLD">Sold</option>
                                    <option value="DISPOSED">Disposed</option>
                                    <option value="DEPRECIATED">Fully Depreciated</option>
                                </select>
                            </div>
                        </div>

                    </div>
                </div>

                <!-- ══════════════════════════════════════════════════════
                     STEP 2 — Depreciation & Ledger Setup
                     Fields: group_code, asset_code, depreciation_code,
                             depreciation_on, depreciation_day,
                             depreciation_start_date, depreciation_end_date,
                             retirement_date
                             (monthly_depreciation = computed, read-only)
                ════════════════════════════════════════════════════════ -->
                <div data-step="2" class="asset-step hidden">
                    <div class="border border-slate-100 shadow-md rounded-md w-full max-w-[900px] mx-auto min-h-[320px] pt-6 px-6 pb-6">

                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
                            <!-- Group Code (FK → asset_groups) -->
                            <div>
                                <label class="block text-xs font-black text-slate-500 uppercase tracking-widest mb-1.5">
                                    Depreciation Group <span class="text-red-600">*</span>
                                </label>
                                <input type="text" name="group_code"
                                    placeholder="e.g. OE24MOS"
                                    class="w-full border-2 border-slate-200 focus:border-red-500 rounded-md px-4 py-2.5 text-sm outline-none transition-colors" />
                                <p class="text-[10px] text-slate-400 mt-1">FK → asset_groups.group_code</p>
                            </div>
                            <!-- Asset Code (FK → assets_lookup) -->
                            <div>
                                <label class="block text-xs font-black text-slate-500 uppercase tracking-widest mb-1.5">
                                    Asset Account Code
                                </label>
                                <input type="text" name="asset_code"
                                    placeholder="e.g. 1231001"
                                    class="w-full border-2 border-slate-200 focus:border-red-500 rounded-md px-4 py-2.5 text-sm outline-none transition-colors" />
                                <p class="text-[10px] text-slate-400 mt-1">FK → assets_lookup.asset_code</p>
                            </div>
                            <!-- Depreciation Code (FK → amortization_depreciation) -->
                            <div>
                                <label class="block text-xs font-black text-slate-500 uppercase tracking-widest mb-1.5">
                                    Depreciation P&amp;L Code
                                </label>
                                <input type="text" name="depreciation_code"
                                    placeholder="e.g. S151005"
                                    class="w-full border-2 border-slate-200 focus:border-red-500 rounded-md px-4 py-2.5 text-sm outline-none transition-colors" />
                                <p class="text-[10px] text-slate-400 mt-1">FK → amortization_depreciation.depreciation_code</p>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-4">
                            <!-- Depreciation Start Date -->
                            <div>
                                <label class="block text-xs font-black text-slate-500 uppercase tracking-widest mb-1.5">
                                    Start Date
                                </label>
                                <input type="date" name="depreciation_start_date" id="aa-depr-start"
                                    class="w-full border-2 border-slate-200 focus:border-red-500 rounded-md px-4 py-2.5 text-sm outline-none transition-colors bg-slate-50" readonly />
                                <p class="text-[10px] text-slate-400 mt-1">Auto: last day of received month</p>
                            </div>
                            <!-- Depreciation End Date -->
                            <div>
                                <label class="block text-xs font-black text-slate-500 uppercase tracking-widest mb-1.5">
                                    End Date
                                </label>
                                <input type="date" name="depreciation_end_date" id="aa-depr-end"
                                    class="w-full border-2 border-slate-200 focus:border-red-500 rounded-md px-4 py-2.5 text-sm outline-none transition-colors bg-slate-50" readonly />
                                <p class="text-[10px] text-slate-400 mt-1">Auto: start + group months − 1</p>
                            </div>
                            <!-- Retirement Date -->
                            <div>
                                <label class="block text-xs font-black text-slate-500 uppercase tracking-widest mb-1.5">
                                    Retirement Date
                                </label>
                                <input type="date" name="retirement_date"
                                    class="w-full border-2 border-slate-200 focus:border-red-500 rounded-md px-4 py-2.5 text-sm outline-none transition-colors" />
                                <p class="text-[10px] text-slate-400 mt-1">Leave blank if still active</p>
                            </div>
                            <!-- Monthly Depreciation (computed, read-only) -->
                            <div>
                                <label class="block text-xs font-black text-slate-500 uppercase tracking-widest mb-1.5">
                                    Monthly Depreciation
                                </label>
                                <div class="relative">
                                    <span class="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 font-semibold select-none text-sm">₱</span>
                                    <input type="text" name="monthly_depreciation" id="aa-monthly-depr" readonly
                                        placeholder="0.00"
                                        class="w-full border-2 border-slate-200 rounded-md pl-8 pr-4 py-2.5 text-sm bg-slate-50 text-slate-600 cursor-default outline-none" />
                                </div>
                                <p class="text-[10px] text-slate-400 mt-1">= Cost ÷ group months</p>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <!-- Depreciation On -->
                            <div>
                                <label class="block text-xs font-black text-slate-500 uppercase tracking-widest mb-1.5">
                                    Depreciation Posting <span class="text-red-600">*</span>
                                </label>
                                <select name="depreciation_on" id="aa-depr-on" required
                                    class="w-full border-2 border-slate-200 focus:border-red-500 rounded-md px-4 py-2.5 text-sm outline-none transition-colors bg-white">
                                    <option value="LAST_DAY" selected>Last day of month</option>
                                    <option value="FIRST_DAY">First day of month</option>
                                    <option value="SPECIFIC_DATE">Specific day of month</option>
                                </select>
                            </div>
                            <!-- Depreciation Day (only shown when SPECIFIC_DATE) -->
                            <div id="aa-depr-day-wrap">
                                <label class="block text-xs font-black text-slate-500 uppercase tracking-widest mb-1.5">
                                    Day of Month
                                </label>
                                <input type="number" name="depreciation_day" id="aa-depr-day"
                                    min="1" max="31" value="1"
                                    class="w-full border-2 border-slate-200 focus:border-red-500 rounded-md px-4 py-2.5 text-sm outline-none transition-colors"
                                    placeholder="1–31" />
                                <p class="text-[10px] text-slate-400 mt-1">Only used when posting = Specific date</p>
                            </div>
                        </div>

                    </div>
                </div>

                <!-- ══════════════════════════════════════════════════════
                     STEP 3 — Location / Branch Assignment
                     Fields: main_zone_code, zone_code, region_code,
                             cost_center_code, branch_name
                ════════════════════════════════════════════════════════ -->
                <div data-step="3" class="asset-step hidden">
                    <div class="border border-slate-100 shadow-md rounded-md w-full max-w-[900px] mx-auto min-h-[320px] pt-6 px-6 pb-6">

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                            <!-- Main Zone Code -->
                            <div>
                                <label class="block text-xs font-black text-slate-500 uppercase tracking-widest mb-1.5">
                                    Main Zone <span class="text-red-600">*</span>
                                </label>
                                <input type="text" name="main_zone_code" required
                                    placeholder="e.g. NCR, VIS, MIN"
                                    class="w-full border-2 border-slate-200 focus:border-red-500 rounded-md px-4 py-2.5 text-sm outline-none transition-colors uppercase" />
                            </div>
                            <!-- Zone Code (sub-zone) -->
                            <div>
                                <label class="block text-xs font-black text-slate-500 uppercase tracking-widest mb-1.5">
                                    Zone (Sub-zone)
                                </label>
                                <input type="text" name="zone_code"
                                    placeholder="e.g. NCR-55, VIS-301"
                                    class="w-full border-2 border-slate-200 focus:border-red-500 rounded-md px-4 py-2.5 text-sm outline-none transition-colors uppercase" />
                            </div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <!-- Region Code -->
                            <div>
                                <label class="block text-xs font-black text-slate-500 uppercase tracking-widest mb-1.5">
                                    Region Code
                                </label>
                                <input type="text" name="region_code"
                                    placeholder="e.g. R03, R07"
                                    class="w-full border-2 border-slate-200 focus:border-red-500 rounded-md px-4 py-2.5 text-sm outline-none transition-colors uppercase" />
                            </div>
                            <!-- Cost Center Code -->
                            <div>
                                <label class="block text-xs font-black text-slate-500 uppercase tracking-widest mb-1.5">
                                    Cost Center <span class="text-red-600">*</span>
                                </label>
                                <input type="text" name="cost_center_code" required
                                    placeholder="e.g. 10287545"
                                    class="w-full border-2 border-slate-200 focus:border-red-500 rounded-md px-4 py-2.5 text-sm outline-none transition-colors" />
                            </div>
                            <!-- Branch Name -->
                            <div>
                                <label class="block text-xs font-black text-slate-500 uppercase tracking-widest mb-1.5">
                                    Branch Name <span class="text-red-600">*</span>
                                </label>
                                <input type="text" name="branch_name" required
                                    placeholder="e.g. ML AMORANTO"
                                    class="w-full border-2 border-slate-200 focus:border-red-500 rounded-md px-4 py-2.5 text-sm outline-none transition-colors uppercase" />
                            </div>
                        </div>

                    </div>
                </div>

            </form>
        </div>

        <!-- Modal footer -->
        <div class="border-t border-slate-100 px-5 py-5 bg-white flex-none">
            <div class="max-w-[900px] mx-auto flex justify-center gap-5 items-center">
                <button type="button" data-action="prev" id="asset-btn-prev"
                    class="hidden border-2 border-slate-200 text-slate-600 font-black text-xs uppercase tracking-widest px-4 py-3 rounded-md">
                    Previous
                </button>
                <button type="button" data-action="next" id="asset-btn-next"
                    class="bg-[#ce1126] hover:bg-red-700 text-white font-black text-xs uppercase tracking-widest px-4 py-3 rounded-md">
                    Next
                </button>
                <button type="submit" form="add-asset-form" id="asset-btn-save"
                    class="hidden bg-[#ce1126] hover:bg-red-700 text-white font-black text-xs uppercase tracking-widest px-4 py-3 rounded-md">
                    Save Asset
                </button>
            </div>
        </div>

    </div>
</div>

<script>
(function () {
    // ── Wizard state ─────────────────────────────────────────────────
    var currentStep = 1;
    var totalSteps  = 3;

    var btnPrev   = document.getElementById('asset-btn-prev');
    var btnNext   = document.getElementById('asset-btn-next');
    var btnSave   = document.getElementById('asset-btn-save');
    var stepLabel = document.getElementById('asset-wizard-step');
    var progress  = document.getElementById('asset-wizard-progress');

    function showStep(n) {
        document.querySelectorAll('.asset-step').forEach(function (el) {
            el.classList.add('hidden');
        });
        document.querySelector('[data-step="' + n + '"]').classList.remove('hidden');

        stepLabel.textContent = n;
        progress.style.width  = Math.round((n / totalSteps) * 100) + '%';

        btnPrev.classList.toggle('hidden', n === 1);
        btnNext.classList.toggle('hidden', n === totalSteps);
        btnSave.classList.toggle('hidden', n !== totalSteps);
    }

    btnNext && btnNext.addEventListener('click', function () {
        if (currentStep < totalSteps) {
            currentStep++;
            showStep(currentStep);
        }
    });

    btnPrev && btnPrev.addEventListener('click', function () {
        if (currentStep > 1) {
            currentStep--;
            showStep(currentStep);
        }
    });

    // Reset wizard when modal closes
    document.querySelector('[onclick="closeModal(\'modal-add-asset\')"]')
        && document.querySelector('[onclick="closeModal(\'modal-add-asset\')"]')
            .addEventListener('click', function () {
                currentStep = 1;
                showStep(1);
            });

    // ── Auto-compute depreciation fields from Step 1 → Step 2 ───────
    // Reads: date_received (step 1), group_code (step 2), acquisition_cost (step 1)
    // Writes: depreciation_start_date, depreciation_end_date, monthly_depreciation

    function lastDayOfMonth(dateStr) {
        if (!dateStr) return '';
        var d = new Date(dateStr + 'T00:00:00');
        if (isNaN(d)) return '';
        var last = new Date(d.getFullYear(), d.getMonth() + 1, 0);
        return last.toISOString().slice(0, 10);
    }

    function recompute() {
        var dateReceived  = document.querySelector('[name="date_received"]').value;
        var acqCost       = parseFloat(document.querySelector('[name="acquisition_cost"]').value) || 0;
        var groupCode     = document.querySelector('[name="group_code"]').value.trim().toUpperCase();

        // Depreciation start = last day of received month
        var deprStart = lastDayOfMonth(dateReceived);
        var startEl   = document.getElementById('aa-depr-start');
        if (startEl) startEl.value = deprStart;

        // Monthly dep & end date require knowing the period (months).
        // group_code → actual_months must come from an API call or a pre-loaded map.
        // Placeholder: compute if a data attribute is set on the group input.
        var groupInput = document.querySelector('[name="group_code"]');
        var lifeMonths = parseInt(groupInput.getAttribute('data-life-months') || '0', 10);

        var endEl     = document.getElementById('aa-depr-end');
        var monthlyEl = document.getElementById('aa-monthly-depr');

        if (deprStart && lifeMonths > 0) {
            var parts   = deprStart.split('-');
            var rm      = parseInt(parts[1], 10) - 1 + lifeMonths;
            var retYear = parseInt(parts[0], 10) + Math.floor(rm / 12);
            var retMon  = (rm % 12) + 1;
            var endDate = lastDayOfMonth(retYear + '-' + String(retMon).padStart(2, '0') + '-01');
            if (endEl) endEl.value = endDate;
        } else {
            if (endEl) endEl.value = '';
        }

        if (acqCost > 0 && lifeMonths > 0) {
            if (monthlyEl) monthlyEl.value = (acqCost / lifeMonths).toFixed(2);
        } else {
            if (monthlyEl) monthlyEl.value = '';
        }
    }

    // Trigger recompute when relevant fields change
    ['date_received', 'acquisition_cost'].forEach(function (name) {
        var el = document.querySelector('[name="' + name + '"]');
        if (el) el.addEventListener('change', recompute);
    });

    // group_code: recompute when user leaves the field
    // (In production, wire this to an API lookup for data-life-months.)
    var groupEl = document.querySelector('[name="group_code"]');
    if (groupEl) groupEl.addEventListener('blur', recompute);

    // ── Depreciation day field visibility ────────────────────────────
    var deprOnEl      = document.getElementById('aa-depr-on');
    var deprDayWrap   = document.getElementById('aa-depr-day-wrap');
    var deprDayInput  = document.getElementById('aa-depr-day');

    function toggleDeprDay() {
        var isSpecific = deprOnEl && deprOnEl.value === 'SPECIFIC_DATE';
        if (deprDayWrap) {
            deprDayWrap.style.opacity = isSpecific ? '1' : '0.4';
        }
        if (deprDayInput) {
            deprDayInput.disabled = !isSpecific;
        }
    }

    if (deprOnEl) {
        deprOnEl.addEventListener('change', toggleDeprDay);
        toggleDeprDay(); // init
    }

    // Init step display
    showStep(1);
})();
</script>