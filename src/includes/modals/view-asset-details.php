<div id="modal-view-asset" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-sm p-4">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-5xl flex flex-col overflow-hidden animate-fadeIn" style="max-height: 94vh;">
        
        <div class="flex items-center justify-between px-6 py-4 border-b-2 border-[#ce1126] shrink-0 bg-white">
            <div class="flex items-center gap-3">
                <h2 class="text-xl font-black text-slate-800 uppercase tracking-tight">
                    <span id="view-system-code">LOADING...</span>
                </h2>
                <span id="view-status-badge" class="px-2.5 py-0.5 rounded-full text-xs font-bold uppercase tracking-wider bg-slate-100 text-slate-700 hidden">
                    STATUS
                </span>
            </div>
            <button type="button" onclick="closeModal('modal-view-asset')" class="p-2 hover:bg-slate-100 rounded-lg transition-colors">
                <svg class="w-5 h-5 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>

        <div id="view-asset-loading" class="p-12 text-center text-slate-500 font-semibold flex-1">
            Fetching asset details...
        </div>

        <div id="view-asset-content" class="hidden flex-1 overflow-y-auto bg-white p-6">
            <div id="finish-summary" class="finish-card finish-card--table finish-card--single">
                <table class="finish-table finish-table--single w-full">
                    <colgroup>
                        <col class="finish-col-label-sm">
                        <col class="finish-col-value-lg">
                        <col class="finish-col-label-sm">
                        <col class="finish-col-value-lg">
                        <col class="finish-col-label-sm">
                        <col class="finish-col-value-lg">
                    </colgroup>
                    <tbody>
                        <tr><th colspan="6" class="finish-title">Location</th></tr>
                        <tr>
                            <td class="finish-label">Branch Name</td>
                            <td class="finish-value" data-key="branch_name"><span id="view-branch">-</span></td>
                            <td class="finish-label">Main Zone</td>
                            <td class="finish-value" colspan="3" data-key="main_zone_code"><span id="view-main-zone">-</span></td>
                        </tr>
                        <tr>
                            <td class="finish-label">BOS Code</td>
                            <td class="finish-value" data-key="cost_center_code"><span id="view-cost-center">-</span></td>
                            <td class="finish-label">Sub-Zone</td>
                            <td class="finish-value" colspan="3" data-key="zone_code"><span id="view-zone">-</span></td>
                        </tr>
                        <tr>
                            <td class="finish-label">KPX Branch ID</td>
                            <td class="finish-value" data-key="kpx_branch_id"><span id="view-kpx-branch-id">-</span></td>
                            <td class="finish-label">Region</td>
                            <td class="finish-value" colspan="3" data-key="region_code"><span id="view-region-dup" style="display:none">-</span></td>
                        </tr>
                        <tr>
                            <td class="finish-label">Corporate Name</td>
                            <td class="finish-value" colspan="5" data-key="corporate_name"><span id="view-corporate-name">-</span></td>
                        </tr>
                        <tr>
                            <td class="finish-value" colspan="6">&nbsp;</td>
                        </tr>

                        <tr><th colspan="6" class="finish-title">Asset Details</th></tr>
                        <tr>
                            <td class="finish-label">Item Code</td>
                            <td class="finish-value" data-key="item_code"><span id="view-item-code">-</span></td>
                            <td class="finish-label">Serial No.</td>
                            <td class="finish-value" data-key="serial_number"><span id="view-serial">-</span></td>
                            <td class="finish-label">Reference No.</td>
                            <td class="finish-value" data-key="reference_no"><span id="view-reference">-</span></td>
                        </tr>
                        <tr>
                            <td class="finish-label">Property Type</td>
                            <td class="finish-value" data-key="property_type"><span id="view-property-type">-</span></td>
                            <td class="finish-label">Status</td>
                            <td class="finish-value" colspan="3" data-key="status"><span id="view-status">-</span></td>
                        </tr>
                        <tr><td class="finish-label">Description</td><td class="finish-value" colspan="5" data-key="description"><span id="view-description">-</span></td></tr>
                        <tr>
                            <td class="finish-value" colspan="6">&nbsp;</td>
                        </tr>

                        <tr><th colspan="6" class="finish-title">Dates</th></tr>
                        <tr>
                            <td class="finish-label" colspan="2">Date Received</td>
                            <td class="finish-label" colspan="2">Depreciation Start</td>
                            <td class="finish-label" colspan="2">Depreciation End</td>
                        </tr>
                        <tr>
                            <td class="finish-value" colspan="2" data-key="date_received"><span id="view-date-received">-</span></td>
                            <td class="finish-value" colspan="2" data-key="depreciation_start_date"><span id="view-start-date">-</span></td>
                            <td class="finish-value" colspan="2" data-key="depreciation_end_date"><span id="view-end-date">-</span></td>
                        </tr>
                        <tr class="hidden">
                            <td class="finish-label">Policy (Months)</td>
                            <td class="finish-value" colspan="5"><span id="view-months">0</span></td>
                        </tr>
                        <tr>
                            <td class="finish-value" colspan="6">&nbsp;</td>
                        </tr>

                        <tr><th colspan="6" class="finish-title">Financial</th></tr>
                        <tr>
                            <td class="finish-label">Quantity</td>
                            <td class="finish-label" colspan="2">Acquisition Cost</td>
                            <td class="finish-label">Debit Amount</td>
                            <td class="finish-label" colspan="2">Credit Amount</td>
                        </tr>
                        <tr>
                            <td class="finish-value" data-key="quantity"><span id="view-quantity">-</span></td>
                            <td class="finish-value currency" colspan="2" data-key="acquisition_cost"><span class="currency-symbol">₱</span><span class="amount" id="view-acq-cost">0.00</span></td>
                            <td class="finish-value currency" data-key="preview_debit"><span class="currency-symbol">₱</span><span class="amount" id="view-debit">0.00</span></td>
                            <td class="finish-value currency" colspan="2" data-key="preview_credit"><span class="currency-symbol">₱</span><span class="amount" id="view-credit">0.00</span></td>
                        </tr>
                        <tr>
                            <td class="finish-value" colspan="6">&nbsp;</td>
                        </tr>

                        <tr><th colspan="6" class="finish-title">Depreciation</th></tr>
                        <tr>
                            <td class="finish-label" colspan="2">Monthly Depreciation</td>
                            <td class="finish-label" colspan="2">Accumulated Depreciation</td>
                            <td class="finish-label" colspan="2">Book Value</td>
                        </tr>
                        <tr>
                            <td class="finish-value currency" colspan="2" data-key="gl_depr_monthly">₱ <span class="amount" id="view-monthly-dep">0.00</span></td>
                            <td class="finish-value currency" colspan="2" data-key="accumulated_depreciation">₱ <span class="amount" id="view-accum-dep">0.00</span></td>
                            <td class="finish-value currency" colspan="2" data-key="book_value">₱ <span class="amount" id="view-book-value">0.00</span></td>
                        </tr>
                        <tr>
                            <td class="finish-value" colspan="6">&nbsp;</td>
                        </tr>
                        
                        <tr><th colspan="6" class="finish-title">Asset Categorization</th></tr>
                        <tr>
                            <td class="finish-label">Category</td>
                            <td class="finish-value" colspan="2" data-key="category_type"><span id="view-category">-</span></td>
                            <td class="finish-label">Expense Type</td>
                            <td class="finish-value" colspan="2" data-key="expense_name"><span id="view-expense-type">-</span></td>
                        </tr>
                        <tr><td class="finish-label">Asset Group</td><td class="finish-value" colspan="5" data-key="group_name"><span id="view-group">-</span></td></tr>
                        <tr>
                            <td class="finish-value" colspan="6">&nbsp;</td>
                        </tr>

                        <tr><th colspan="6" class="finish-title">General Ledger</th></tr>
                        <tr>
                            <td class="finish-label">GL Codes</td>
                            <td class="finish-label">Normal Balance</td>
                            <td class="finish-label" colspan="2">Description</td>
                            <td class="finish-label" colspan="2">Monthly Amount</td>
                        </tr>
                        <tr>
                            <td class="finish-value" data-key="gl_depreciation_code"><span id="view-gl-depr-code">-</span></td>
                            <td class="finish-value" data-key="gl_depreciation_type"><span id="view-gl-depr-type">-</span></td>
                            <td class="finish-value" colspan="2" data-key="gl_depreciation_description"><span id="view-gl-depr-desc">-</span></td>
                            <td class="finish-value currency" colspan="2" data-key="gl_depr_monthly">₱ <span class="amount" id="view-gl-depr-monthly">0.00</span></td>
                        </tr>
                        <tr>
                            <td class="finish-value" data-key="gl_asset_code"><span id="view-gl-asset-code">-</span></td>
                            <td class="finish-value" data-key="gl_asset_type"><span id="view-gl-asset-type">-</span></td>
                            <td class="finish-value" colspan="2" data-key="gl_asset_description"><span id="view-gl-asset-desc">-</span></td>
                            <td class="finish-value currency" colspan="2" data-key="gl_asset_monthly">₱ <span class="amount" id="view-gl-asset-monthly">0.00</span></td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="px-6 py-3 border-t border-slate-200 bg-white shrink-0 flex items-center justify-between text-[12px] text-slate-700">
            <div>Uploaded by: <span id="view-uploaded-by" class="font-semibold uppercase text-slate-700"></span></div>
            <div>Date Added: <span id="view-created-at" class="font-semibold text-slate-700"></span></div>
        </div>

    </div>
</div>