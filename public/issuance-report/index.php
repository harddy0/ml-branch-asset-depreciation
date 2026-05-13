<?php
$pageTitle   = 'Issuance Report';
$currentPage = 'issuance-report';
require_once __DIR__ . '/../../src/includes/init.php';
?>

<link href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css" rel="stylesheet">
<style>
    /* Table container with horizontal scroll */
    .table-container {
        overflow-x: auto;
        overflow-y: auto;
        width: 100%;
        max-height: 56vh;
        position: relative;
        -webkit-overflow-scrolling: touch;
    }
    
    .issuance-table {
        min-width: 1400px;
        width: 100%;
        border-collapse: collapse;
        table-layout: fixed;
    }
    
    /* Fixed column widths */
    .issuance-table th,
    .issuance-table td {
        padding: 8px 12px;
        vertical-align: middle;
    }

    .issuance-table th {
        white-space: nowrap;
        position: sticky;
        top: 0;
        z-index: 2;
        background-color: #ce2216;
    }
    
    /* Individual column widths */
    .issuance-table th:nth-child(1),
    .issuance-table td:nth-child(1) { width: 90px; } /* Date Issued */
    
    .issuance-table th:nth-child(2),
    .issuance-table td:nth-child(2) { width: 110px; } /* Issuance # */
    
    .issuance-table th:nth-child(3),
    .issuance-table td:nth-child(3) { width: 90px; } /* Item Code */
    
    .issuance-table th:nth-child(4),
    .issuance-table td:nth-child(4) { width: 220px; } /* Item Description */
    
    .issuance-table th:nth-child(5),
    .issuance-table td:nth-child(5) { width: 60px; text-align: right; } /* Qty */
    
    .issuance-table th:nth-child(6),
    .issuance-table td:nth-child(6) { width: 60px; } /* UoM */
    
    .issuance-table th:nth-child(7),
    .issuance-table td:nth-child(7) { width: 180px; } /* Cost Center */
    
    .issuance-table th:nth-child(8),
    .issuance-table td:nth-child(8) { width: 100px; text-align: right; } /* Unit Cost */
    
    .issuance-table th:nth-child(9),
    .issuance-table td:nth-child(9) { width: 110px; text-align: right; } /* Total Amount */
    
    .issuance-table th:nth-child(10),
    .issuance-table td:nth-child(10) { width: 150px; } /* Remarks */
    
    .issuance-table th:nth-child(11),
    .issuance-table td:nth-child(11) { width: 140px; } /* Category */
    
    .issuance-table th:nth-child(12),
    .issuance-table td:nth-child(12) { width: 80px; } /* Zone */
    
    .issuance-table th:nth-child(13),
    .issuance-table td:nth-child(13) { width: 110px; } /* Region */
    
    .issuance-table th:nth-child(14),
    .issuance-table td:nth-child(14) { width: 140px; } /* Branch */
    
    /* Text truncation for long content */
    .issuance-table td:nth-child(4),
    .issuance-table td:nth-child(7),
    .issuance-table td:nth-child(10),
    .issuance-table td:nth-child(11),
    .issuance-table td:nth-child(13),
    .issuance-table td:nth-child(14) {
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }
    
    /* Right align numeric cells */
    .issuance-table td.text-right,
    .issuance-table th.text-right {
        text-align: right;
    }
    
    /* Center align text cells */
    .issuance-table td.text-center,
    .issuance-table th.text-center {
        text-align: center;
    }
    
    /* Table row hover effect */
    .issuance-table tbody tr:hover {
        background-color: #f1f5f9;
        transition: background-color 0.15s ease;
    }
    
    /* Alternating row colors */
    .issuance-table tbody tr:nth-child(even) {
        background-color: #f8fafc;
    }
    
    /* Sort button styles */
    .issuance-sort {
        cursor: pointer;
        user-select: none;
        background: none;
        border: none;
        color: white;
        font-weight: bold;
        font-size: 0.7rem;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        padding: 0;
        display: inline-flex;
        align-items: center;
        gap: 4px;
    }
    
    .issuance-sort:hover {
        opacity: 0.8;
    }
    
    .sort-indicator {
        display: inline-block;
        font-size: 10px;
        opacity: 0.7;
    }
    
    /* Loading spinner */
    @keyframes spin {
        from { transform: rotate(0deg); }
        to { transform: rotate(360deg); }
    }
    .animate-spin {
        animation: spin 1s linear infinite;
    }
    
    /* Filter row styling */
    .filter-row {
        display: flex;
        flex-wrap: nowrap;
        align-items: center;
        gap: 10px;
        width: 100%;
    }
    
    .filter-input {
        flex: 1 1 0;
        min-width: 110px;
    }
    
    .filter-date {
        flex: 0 1 105px;
        width: 105px;
    }

    /* Wider filters when the sidebar is collapsed */
    #sidebar[style*="width: 64px"] ~ main .filter-input {
        min-width: 140px;
    }

    #sidebar[style*="width: 64px"] ~ main .filter-date {
        width: 120px;
    }
    
    /* Responsive adjustments */
    @media (max-width: 1200px) {
        .filter-input {
            min-width: 105px;
        }
        .filter-date {
            width: 100px;
        }
    }
    
    @media (max-width: 768px) {
        .filter-row {
            flex-direction: column;
            align-items: stretch;
            overflow-x: visible;
        }
        .filter-input,
        .filter-date {
            width: 100%;
        }
    }
</style>

<div class="mb-2 flex justify-between items-end flex-wrap gap-3">
    <div>
        <h1 class="text-1xl font-black text-slate-800 uppercase tracking-wide">Issuance Report</h1>
    </div>
    
    <div class="relative inline-block text-left">
        <button id="exportToggleBtn" type="button" 
            class="border border-slate-200 text-slate-800 hover:bg-[#ce2216] hover:text-white px-4 py-1.5 rounded-md text-sm font-mono flex items-center gap-2 transition-colors shadow-sm">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v2a2 2 0 002 2h12a2 2 0 002-2v-2M7 10l5 5m0 0l5-5m-5 5V4"/>
            </svg>
            Export
        </button>
        <div id="exportMenu" class="origin-top-right absolute right-0 mt-2 w-35 rounded-md shadow-lg bg-white ring-1 ring-black ring-opacity-5 hidden z-50">
            <div class="py-1">
                <button id="exportExcelBtn" type="button" class="w-full text-left px-6 py-2 text-sm text-slate-700 hover:bg-slate-100 flex items-center gap-2">
                    <svg class="w-4 h-4 text-green-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                        <path d="M3 7v10a2 2 0 002 2h14V5H5a2 2 0 00-2 2z" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                    Excel
                </button>
                <button id="exportPrintBtn" type="button" class="w-full text-left px-6 py-2 text-sm text-slate-700 hover:bg-slate-100 flex items-center gap-2">
                    <svg class="w-4 h-4 text-slate-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                        <path d="M6 9V2h12v7" stroke-linecap="round" stroke-linejoin="round"/>
                        <path d="M6 18H4a2 2 0 01-2-2v-3h20v3a2 2 0 01-2 2h-2" stroke-linecap="round" stroke-linejoin="round"/>
                        <rect x="6" y="14" width="12" height="8" rx="2" ry="2"/>
                    </svg>
                    Print
                </button>
            </div>
        </div>
    </div>
</div>

<div class="bg-white rounded-lg shadow-sm border border-slate-200 overflow-hidden">
    <div class="bg-slate-50 border-b border-slate-200 px-4 py-3">
        <div class="filter-row">
            <input type="text" id="search-input" placeholder="Search issuance # or description..."
                class="filter-input border border-slate-300 rounded-md px-3 py-1.5 text-sm font-mono text-slate-700 focus:outline-none focus:ring-1 focus:ring-red-500 focus:border-red-500">
            
            <select id="zoneSelect" class="filter-input border border-slate-300 rounded-md px-3 py-1.5 text-sm focus:outline-none focus:ring-1 focus:ring-red-500 focus:border-red-500">
                <option value="">All Zones</option>
            </select>
            
            <select id="regionSelect" class="filter-input border border-slate-300 rounded-md px-3 py-1.5 text-sm focus:outline-none focus:ring-1 focus:ring-red-500 focus:border-red-500">
                <option value="">All Regions</option>
            </select>
            
            <select id="branchSelect" class="filter-input border border-slate-300 rounded-md px-3 py-1.5 text-sm focus:outline-none focus:ring-1 focus:ring-red-500 focus:border-red-500">
                <option value="">All Branches</option>
            </select>
            
            <select id="categorySelect" class="filter-input border border-slate-300 rounded-md px-3 py-1.5 text-sm focus:outline-none focus:ring-1 focus:ring-red-500 focus:border-red-500">
                <option value="">All Categories</option>
            </select>
            
            <input type="date" id="issuance-date-from" class="filter-date border border-slate-300 rounded-md px-2 py-1.5 text-sm font-mono focus:outline-none focus:ring-1 focus:ring-red-500 focus:border-red-500" placeholder="From">
            
            <input type="date" id="issuance-date-to" class="filter-date border border-slate-300 rounded-md px-2 py-1.5 text-sm font-mono focus:outline-none focus:ring-1 focus:ring-red-500 focus:border-red-500" placeholder="To">
            
            <button id="clearFiltersBtn" type="button"
                class="px-4 py-1.5 text-xs border border-slate-300 rounded-md bg-white hover:bg-slate-100 text-slate-600 font-mono font-semibold transition-colors">
                Clear
            </button>
        </div>
    </div>
    
    <!-- Initial State (No Filters) -->
    <div id="initialStateWrapper" class="flex flex-col items-center justify-center py-20 bg-white">
        <svg class="w-16 h-16 text-slate-300 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"/>
        </svg>
        <p class="text-slate-600 font-bold text-lg mb-1">No Filters Applied</p>
        <p class="text-slate-400 text-sm">Select filters above and click outside to load data</p>
    </div>
    
    <!-- No Data State is rendered inside the table now when applicable -->
    
    <!-- Table Container -->
    <div id="tableWrapper" class="hidden">
        <div class="table-container">
            <table class="issuance-table">
                <thead>
                    <tr class="bg-[#ce2216]">
                        <th class="text-left text-white text-xs font-black uppercase tracking-wider">Date Issued</th>
                        <th class="text-left text-white text-xs font-black uppercase tracking-wider">Issuance No.</th>
                        <th class="text-left text-white text-xs font-black uppercase tracking-wider">Item Code</th>
                        <th class="text-left text-white text-xs font-black uppercase tracking-wider">Item Description</th>
                        <th class="text-right text-white text-xs font-black uppercase tracking-wider">Qty</th>
                        <th class="text-left text-white text-xs font-black uppercase tracking-wider">UoM</th>
                        <th class="text-left text-white text-xs font-black uppercase tracking-wider">Cost Center</th>
                        <th class="text-right text-white text-xs font-black uppercase tracking-wider">Unit Cost</th>
                        <th class="text-right text-white text-xs font-black uppercase tracking-wider">Total Amount</th>
                        <th class="text-left text-white text-xs font-black uppercase tracking-wider">Remarks</th>
                        <th class="text-left text-white text-xs font-black uppercase tracking-wider">Category</th>
                        <th class="text-left text-white text-xs font-black uppercase tracking-wider">Zone</th>
                        <th class="text-left text-white text-xs font-black uppercase tracking-wider">Region</th>
                        <th class="text-left text-white text-xs font-black uppercase tracking-wider">Branch</th>
                    </tr>
                </thead>
                <tbody id="issuance-table-body">
                    <!-- Dynamic content -->
                </tbody>
            </table>
        </div>
        
        <!-- Totals Row -->
        <div class="border-t border-slate-200 bg-slate-50 px-5 py-1.5 flex items-center justify-between flex-wrap gap-3">
            <span class="text-xs font-mono text-slate-500 tracking-wider">Summary</span>
            <div class="flex gap-6 text-sm font-black text-slate-800 flex-wrap">
                <span class="flex items-center gap-2">
                    <span class="text-xs text-slate-400 font-mono">Records</span>
                    <span id="total-records" class="text-xs font-mono">0</span>
                </span>
                <span class="flex items-center gap-2">
                    <span class="text-xs text-slate-400 font-mono">Total Qty</span>
                    <span id="total-quantity" class="text-xs font-mono">0</span>
                </span>
                <span class="flex items-center gap-2">
                    <span class="text-xs text-slate-400 font-mono">Total Amount</span>
                    <span id="total-amount" class="text-xs font-mono">₱ 0.00</span>
                </span>
            </div>
        </div>
    </div>
</div>

<!-- Pagination -->
<div id="pagination-container" class="mt-0"></div>

<!-- Hidden Header Template for Print -->
<div id="exportHeaderTemplate" class="hidden">
    <div class="flex items-center justify-center gap-6 bg-white border-b border-slate-200 px-4 py-3 mb-4">
        <div class="flex items-center justify-center border-r border-slate-200 pr-6">
            <img src="<?= BASE_URL ?>/public/assets/img/ml-logo.png" alt="ML Logo" style="height: 32px;">
        </div>
        <span class="font-mono tracking-wide text-lg text-slate-400">ML ASSET MANAGEMENT SYSTEM</span>
        <span class="font-mono tracking-wide text-sm text-slate-400">Issuance Report</span>
    </div>
</div>

<?php include __DIR__ . '/../../src/includes/modals/issuance-report-details.php'; ?>

<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
<script src="<?= ASSET_URL ?>js/main.js"></script>
<script src="<?= ASSET_URL ?>js/issuance-report.js"></script>