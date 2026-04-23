<!-- src/includes/modals/asset-depreciation-details.php
     Import Review — Row Edit Modal
     Mirrors add-asset.php field structure exactly.
     JS in asset-import.js drives all cascades and lifecycle.
-->
<div id="modal-asset-depr-details"
     class="hidden fixed inset-0 z-[60] items-center justify-center bg-slate-900/60 backdrop-blur-sm p-4 sm:p-6">

    <div class="bg-white rounded-xl shadow-2xl w-full max-w-4xl max-h-[92vh] flex flex-col overflow-hidden animate-fadeIn">

        <!-- ── Header ──────────────────────────────────────────────────── -->
        <div class="px-6 py-4 border-b border-slate-200 flex justify-between items-center bg-slate-50 shrink-0">
            <div class="flex items-center gap-3">
                <h2 class="text-base font-black text-slate-800 uppercase tracking-wide">
                    Edit Import Row
                </h2>
                <span id="depr-edit-badge"
                      class="hidden inline-flex items-center gap-1.5 bg-amber-100 text-amber-700
                             text-[10px] font-black px-2 py-0.5 rounded-full uppercase tracking-widest">
                    <svg class="w-3 h-3 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                    </svg>
                    Editing
                </span>
            </div>
            <button type="button" onclick="closeAssetDepreciationDetails()"
                    class="text-slate-400 hover:text-red-600 transition-colors">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>

        <!-- ── Error banner ────────────────────────────────────────────── -->
        <div id="depr-modal-errors"
             class="hidden mx-6 mt-4 p-3 bg-red-50 border border-red-200 rounded-lg text-xs text-red-700 font-semibold shrink-0">
        </div>

        <!-- ── Body (scrollable) ───────────────────────────────────────── -->
        <div class="flex-1 overflow-y-auto p-6 bg-white">
            <div id="depr-view-content"><!-- view mode HTML injected by JS --></div>

            <!-- ════════════════════════════════════════════════════════════
                 EDIT FORM  — hidden until enableDeprEdit() is called
            ═════════════════════════════════════════════════════════════════ -->
            <form id="depr-edit-form" class="hidden space-y-8" autocomplete="off">

                <!-- Hidden state carriers -->
                <input type="hidden" id="depr-f-branchcode"  value="">
                <input type="hidden" id="depr-f-system-code" value="">

                <!-- ── 1. Location Data ──────────────────────────────────── -->
                <section>
                    <h3 class="text-xs font-black text-[#ce1126] uppercase tracking-widest border-b border-slate-200 pb-2 mb-4">
                        1. Location Data
                    </h3>

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
                        <!-- Main Zone -->
                        <div>
                            <label class="block text-xs font-bold text-slate-700 mb-1">
                                Main Zone <span class="text-red-500">*</span>
                            </label>
                            <select id="depr-f-mainzone" name="main_zone_code"
                                class="w-full text-sm border border-slate-300 rounded-lg px-3 py-2.5
                                       focus:ring-2 focus:ring-red-500 outline-none transition-all bg-white">
                                <option value="">Loading…</option>
                            </select>
                        </div>

                        <!-- Sub-Zone -->
                        <div>
                            <label class="block text-xs font-bold text-slate-700 mb-1">
                                Sub-Zone <span class="text-red-500">*</span>
                            </label>
                            <select id="depr-f-zone" name="zone_code"
                                class="w-full text-sm border border-slate-300 rounded-lg px-3 py-2.5
                                       focus:ring-2 focus:ring-red-500 outline-none transition-all bg-white">
                                <option value="">— Select Main Zone first —</option>
                            </select>
                        </div>

                        <!-- Region -->
                        <div>
                            <label class="block text-xs font-bold text-slate-700 mb-1">
                                Region <span class="text-red-500">*</span>
                            </label>
                            <select id="depr-f-region" name="region_code"
                                class="w-full text-sm border border-slate-300 rounded-lg px-3 py-2.5
                                       focus:ring-2 focus:ring-red-500 outline-none transition-all bg-white">
                                <option value="">— Select Sub-Zone first —</option>
                            </select>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <!-- Branch -->
                        <div>
                            <label class="block text-xs font-bold text-slate-700 mb-1">
                                Branch <span class="text-red-500">*</span>
                            </label>
                            <select id="depr-f-branch" name="branch_name"
                                class="w-full text-sm border border-slate-300 rounded-lg px-3 py-2.5
                                       focus:ring-2 focus:ring-red-500 outline-none transition-all bg-white">
                                <option value="">— Select Region first —</option>
                            </select>
                        </div>

                        <!-- Cost Center (read-only, auto-filled) -->
                        <div>
                            <label class="block text-xs font-bold text-slate-700 mb-1">
                                Cost Center
                            </label>
                            <input type="text" id="depr-f-costcenter" name="cost_center_code"
                                readonly
                                class="w-full text-sm border border-slate-200 rounded-lg px-3 py-2.5
                                       bg-slate-50 text-slate-500 outline-none"
                                placeholder="Auto-filled from branch">
                        </div>
                    </div>
                </section>

                <!-- ── 2. Asset Classification ───────────────────────────── -->
                <section>
                    <h3 class="text-xs font-black text-[#ce1126] uppercase tracking-widest border-b border-slate-200 pb-2 mb-4">
                        2. Asset Classification
                    </h3>

                    <div class="grid grid-cols-1 gap-4 mb-4">
                        <!-- GL Asset Group -->
                        <div>
                            <label class="block text-xs font-bold text-slate-700 mb-1">
                                GL Asset Group <span class="text-red-500">*</span>
                            </label>
                            <select id="depr-f-group" name="group_code"
                                class="w-full text-sm border border-slate-300 rounded-lg px-3 py-2.5
                                       focus:ring-2 focus:ring-red-500 outline-none transition-all bg-white">
                                <option value="">— Select group —</option>
                            </select>
                        </div>
                    </div>

                    <!-- GL read-only display boxes -->
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div>
                            <label class="block text-xs font-bold text-slate-500 mb-1">Group Code</label>
                            <input type="text" id="gl-group-code-display" readonly
                                class="w-full text-xs border border-slate-200 rounded-lg px-3 py-2 bg-slate-50 text-slate-500 outline-none"
                                placeholder="—">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-500 mb-1">Asset (Credit GL)</label>
                            <input type="text" id="gl-asset-code-display" readonly
                                class="w-full text-xs border border-slate-200 rounded-lg px-3 py-2 bg-slate-50 text-slate-500 outline-none"
                                placeholder="—">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-500 mb-1">Depreciation (Debit GL)</label>
                            <input type="text" id="gl-dep-code-display" readonly
                                class="w-full text-xs border border-slate-200 rounded-lg px-3 py-2 bg-slate-50 text-slate-500 outline-none"
                                placeholder="—">
                        </div>
                    </div>
                </section>

                <!-- ── 3. Asset Details ──────────────────────────────────── -->
                <section>
                    <h3 class="text-xs font-black text-[#ce1126] uppercase tracking-widest border-b border-slate-200 pb-2 mb-4">
                        3. Asset Details
                    </h3>

                    <div class="mb-4">
                        <label class="block text-xs font-bold text-slate-700 mb-1">
                            Description <span class="text-red-500">*</span>
                        </label>
                        <textarea id="depr-f-description" name="description" rows="2"
                            class="w-full text-sm border border-slate-300 rounded-lg px-3 py-2.5
                                   focus:ring-2 focus:ring-red-500 outline-none transition-all resize-none"
                            placeholder="e.g. Touch Screen Electronic LM Unit"></textarea>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
                        <div>
                            <label class="block text-xs font-bold text-slate-700 mb-1">Serial Number</label>
                            <input type="text" id="depr-f-serial" name="serial_number"
                                class="w-full text-sm border border-slate-300 rounded-lg px-3 py-2.5
                                       focus:ring-2 focus:ring-red-500 outline-none transition-all"
                                placeholder="e.g. SN-2024-001">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-700 mb-1">
                                Reference No.
                                <span class="text-slate-400 font-normal">(Optional)</span>
                            </label>
                            <input type="text" id="depr-f-refno" name="reference_no"
                                class="w-full text-sm border border-slate-300 rounded-lg px-3 py-2.5
                                       focus:ring-2 focus:ring-red-500 outline-none transition-all"
                                placeholder="e.g. IS#10287545">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-700 mb-1">Item Code</label>
                            <input type="text" id="depr-f-itemcode" name="item_code"
                                class="w-full text-sm border border-slate-300 rounded-lg px-3 py-2.5
                                       focus:ring-2 focus:ring-red-500 outline-none transition-all"
                                placeholder="e.g. ITM-001">
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div>
                            <label class="block text-xs font-bold text-slate-700 mb-1">
                                Property Type <span class="text-red-500">*</span>
                            </label>
                            <select id="depr-f-property-type" name="property_type"
                                class="w-full text-sm border border-slate-300 rounded-lg px-3 py-2.5
                                       focus:ring-2 focus:ring-red-500 outline-none transition-all bg-white">
                                <option value="PURCHASED">Purchased</option>
                                <option value="LEASE">Lease</option>
                                <option value="LEASEHOLD">Leasehold</option>
                                <option value="MAINTENANCE">Maintenance</option>
                            </select>
                        </div>
                    </div>
                </section>

                <!-- ── 4. Dates & Depreciation Schedule ─────────────────── -->
                <section>
                    <h3 class="text-xs font-black text-[#ce1126] uppercase tracking-widest border-b border-slate-200 pb-2 mb-4">
                        4. Dates &amp; Depreciation Schedule
                    </h3>

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
                        <div>
                            <label class="block text-xs font-bold text-slate-700 mb-1">
                                Date Received <span class="text-red-500">*</span>
                            </label>
                            <input type="date" id="depr-f-date-received" name="date_received"
                                class="w-full text-sm border border-slate-300 rounded-lg px-3 py-2.5
                                       focus:ring-2 focus:ring-red-500 outline-none transition-all">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-700 mb-1">
                                Depreciation Start
                            </label>
                            <input type="date" id="depr-f-depr-start" name="depreciation_start_date"
                                readonly
                                class="w-full text-sm border border-slate-200 rounded-lg px-3 py-2.5
                                       bg-slate-50 text-slate-500 outline-none"
                                placeholder="Auto-calculated">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-700 mb-1">Quantity</label>
                            <input type="number" id="depr-f-quantity" name="quantity" min="1" value="1"
                                class="w-full text-sm border border-slate-300 rounded-lg px-3 py-2.5
                                       focus:ring-2 focus:ring-red-500 outline-none transition-all">
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <!-- Depreciate On -->
                        <div>
                            <label class="block text-xs font-bold text-slate-700 mb-1">Depreciate On</label>
                            <select id="depr-f-depr-on" name="depreciation_on"
                                class="w-full text-sm border border-slate-300 rounded-lg px-3 py-2.5
                                       focus:ring-2 focus:ring-red-500 outline-none transition-all bg-white">
                                <option value="LAST_DAY">Last Day of Month</option>
                                <option value="FIRST_DAY">First Day of Month</option>
                                <option value="SPECIFIC_DATE">Specific Date</option>
                            </select>
                        </div>
                        <!-- Specific Day -->
                        <div>
                            <label class="block text-xs font-bold text-slate-700 mb-1">Specific Day</label>
                            <input type="number" id="depr-f-depr-day" name="depreciation_day"
                                min="1" max="31" value="1"
                                readonly disabled
                                class="w-full text-sm border border-slate-200 rounded-lg px-3 py-2.5
                                       bg-slate-50 text-slate-400 outline-none transition-all"
                                placeholder="1–31">
                        </div>
                    </div>
                </section>

                <!-- ── 5. Financial ──────────────────────────────────────── -->
                <section>
                    <h3 class="text-xs font-black text-[#ce1126] uppercase tracking-widest border-b border-slate-200 pb-2 mb-4">
                        5. Financial
                    </h3>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-bold text-slate-700 mb-1">
                                Investment <span class="text-red-500">*</span>
                            </label>
                            <input type="number" id="depr-f-acq-cost" name="acquisition_cost"
                                min="0.01" step="0.01"
                                class="w-full text-sm border border-slate-300 rounded-lg px-3 py-2.5
                                       focus:ring-2 focus:ring-red-500 outline-none transition-all"
                                placeholder="0.00">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-700 mb-1">
                                Monthly Depreciation
                            </label>
                            <input type="number" id="depr-f-monthly-dep" name="monthly_depreciation"
                                readonly
                                class="w-full text-sm border border-slate-200 rounded-lg px-3 py-2.5
                                       bg-slate-50 text-slate-500 outline-none"
                                placeholder="Auto-calculated">
                        </div>
                    </div>
                </section>

            </form><!-- /#depr-edit-form -->
        </div><!-- /.overflow-y-auto -->

        <!-- ── Footer ──────────────────────────────────────────────────── -->
        <div class="px-6 py-3 border-t border-slate-200 shrink-0 flex items-center justify-between gap-3 bg-slate-50">
            <p id="depr-unsaved-hint"
               class="hidden text-xs text-[#ce1126] font-semibold flex items-center gap-1.5">
                <svg class="w-3.5 h-3.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M12 9v2m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                Unsaved changes — click Save to apply.
            </p>

            <div class="flex gap-3 ml-auto">
                <!-- View mode: Edit + Close -->
                <button type="button" id="depr-btn-edit"
                        onclick="enableDeprEdit()"
                        class="px-5 py-2.5 bg-slate-100 hover:bg-slate-200 text-slate-700 font-black text-xs
                               uppercase tracking-widest rounded-xl transition-colors flex items-center gap-2">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                    </svg>
                    Edit
                </button>
                <button type="button" onclick="closeAssetDepreciationDetails()"
                        id="depr-btn-close"
                        class="border-2 border-slate-200 text-slate-600 hover:border-slate-300 hover:bg-slate-100
                               font-black text-xs uppercase tracking-widest px-5 py-2.5 rounded-xl transition-all">
                    Close
                </button>

                <!-- Edit mode: Discard + Save -->
                <button type="button" id="depr-btn-cancel-edit"
                        onclick="cancelDeprEdit()"
                        class="hidden border-2 border-slate-200 text-slate-600 hover:bg-slate-100
                               font-black text-xs uppercase tracking-widest px-5 py-2.5 rounded-xl transition-all">
                    Discard
                </button>
                <button type="button" id="depr-btn-save"
                        onclick="saveDeprEdit()"
                        class="hidden bg-[#ce1126] hover:bg-red-700 text-white font-black text-xs
                               uppercase tracking-widest px-6 py-2.5 rounded-xl shadow-lg shadow-red-100
                               hover:-translate-y-0.5 transition-all flex items-center gap-2">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                    </svg>
                    Save
                </button>
            </div>
        </div>

    </div>
</div>