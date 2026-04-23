<div id="finish-summary" class="mt-0 text-left">
    <div class="finish-layout-grid">

        <div class="finish-card finish-card--table">
            <table class="finish-table">
                <colgroup>
                    <col class="finish-col-label">
                    <col class="finish-col-value">
                </colgroup>
                <thead>
                    <tr><th colspan="2" class="finish-title">Location</th></tr>
                </thead>
                <tbody>
                    <tr><td class="finish-label">Branch</td><td class="finish-value" data-key="branch_name">-</td></tr>
                    <tr><td class="finish-label">Branch ID</td><td class="finish-value" data-key="cost_center_code">-</td></tr>
                    <tr><td class="finish-label">Main Zone</td><td class="finish-value" data-key="main_zone_code">-</td></tr>
                    <tr><td class="finish-label">Sub-Zone</td><td class="finish-value" data-key="zone_code">-</td></tr>
                    <tr><td class="finish-label">Region</td><td class="finish-value" data-key="region_code">-</td></tr>
                    
                </tbody>
            </table>
        </div>

        <div class="finish-card finish-card--table">
            <table class="finish-table">
                <colgroup>
                    <col class="finish-col-label">
                    <col class="finish-col-value">
                </colgroup>
                <thead>
                    <tr><th colspan="2" class="finish-title">Asset Details</th></tr>
                </thead>
                <tbody>
                    <tr><td class="finish-label">Description</td><td class="finish-value" data-key="description">-</td></tr>
                    <tr><td class="finish-label">Serial No.</td><td class="finish-value" data-key="serial_number">-</td></tr>
                    <tr><td class="finish-label">Reference No.</td><td class="finish-value" data-key="reference_no">-</td></tr>
                    <tr><td class="finish-label">Property Type</td><td class="finish-value" data-key="property_type">-</td></tr>
                    <tr><td class="finish-label">Status</td><td class="finish-value" data-key="status">-</td></tr>
                </tbody>
            </table>
        </div>

        <div class="finish-card finish-card--table">
            <table class="finish-table finish-table--depreciation">
                <colgroup>
                    <col class="finish-col-label">
                    <col class="finish-col-value">
                </colgroup>
                <thead>
                    <tr><th colspan="2" class="finish-title">Dates</th></tr>
                </thead>
                <tbody>
                    <tr class="py-1"><td class="finish-label">Date Received</td><td class="finish-value" data-key="date_received">-</td></tr>
                    <tr class="py-1"><td class="finish-label">Depreciation Start</td><td class="finish-value" data-key="depreciation_start_date">-</td></tr>
                    <tr class="py-1"><td class="finish-label">Depreciation End</td><td class="finish-value" data-key="depreciation_end_date">-</td></tr>
                    <tr class="py-1"><td class="finish-label">Depreciation On</td><td class="finish-value" data-key="depreciation_on">-</td></tr>
                    <tr class="py-1"><td class="finish-label">Depreciation Day</td><td class="finish-value" data-key="depreciation_day">-</td></tr>
                </tbody>
            </table>
        </div>

        <div class="finish-card finish-card--table">
            <table class="finish-table">
                <colgroup>
                    <col class="finish-col-label">
                    <col class="finish-col-value">
                </colgroup>
                <thead>
                    <tr><th colspan="2" class="finish-title">Financial</th></tr>
                </thead>
                <tbody>
                    <tr><td class="finish-label">Quantity</td><td class="finish-value" data-key="quantity">-</td></tr>
                    <tr><td class="finish-label">Acquisition Cost</td><td class="finish-value currency" data-key="acquisition_cost"><span class="currency-symbol">₱</span><span class="amount">-</span></td></tr>
                    <!-- monthly depreciation moved to General Ledger Accounts card -->
                    <tr><td class="finish-label">Debit Amount</td><td class="finish-value currency"><span class="currency-symbol">₱</span><span class="amount" data-key="preview_debit">-</span></td></tr>
                    <tr><td class="finish-label">Credit Amount</td><td class="finish-value currency"><span class="currency-symbol">₱</span><span class="amount" data-key="preview_credit">-</span></td></tr>
                </tbody>
            </table>
        </div>

        <div class="finish-card finish-card--table finish-card--center">
            <table class="finish-table">
                <colgroup>
                    <col class="finish-col-label">
                    <col class="finish-col-value">
                </colgroup>
                <thead>
                    <tr><th colspan="2" class="finish-title">General Ledger Accounts</th></tr>
                </thead>
                <tbody>
                    <tr><td class="finish-label">Asset Group</td><td class="finish-value" data-key="asset_group_id">-</td></tr>
                </tbody>
            </table>

            <!-- GL Account 1 -->
            <div class="mt-3">
                <table class="finish-table">
                    <thead><tr><th class="finish-title" colspan="2">GL Account 1</th></tr></thead>
                    <tbody>
                        <tr><td class="finish-label">Code</td><td class="finish-value" data-key="gl_asset_code">-</td></tr>
                        <tr><td class="finish-label">Type</td><td class="finish-value" data-key="gl_asset_type">-</td></tr>
                        <tr><td class="finish-label">Description</td><td class="finish-value" data-key="gl_asset_description">-</td></tr>
                        <tr><td class="finish-label">Monthly Amount</td><td class="finish-value currency"><span class="currency-symbol">₱</span><span class="amount" data-key="gl_asset_monthly">-</span></td></tr>
                    </tbody>
                </table>
            </div>

            <!-- GL Account 2 -->
            <div class="mt-3">
                <table class="finish-table">
                    <thead><tr><th class="finish-title" colspan="2">GL Account 2</th></tr></thead>
                    <tbody>
                        <tr><td class="finish-label">Code</td><td class="finish-value" data-key="gl_depreciation_code">-</td></tr>
                        <tr><td class="finish-label">Type</td><td class="finish-value" data-key="gl_depreciation_type">-</td></tr>
                        <tr><td class="finish-label">Description</td><td class="finish-value" data-key="gl_depreciation_description">-</td></tr>
                        <tr><td class="finish-label">Monthly Amount</td><td class="finish-value currency"><span class="currency-symbol">₱</span><span class="amount" data-key="gl_depr_monthly">-</span></td></tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

