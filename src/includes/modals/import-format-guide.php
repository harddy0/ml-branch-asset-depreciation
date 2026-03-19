<!-- Format Guide Modal -->
<div id="modal-format-guide"
     class="hidden fixed inset-0 z-50 items-center justify-center bg-black/50 backdrop-blur-sm p-4">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-3xl animate-fadeIn flex flex-col" style="max-height:88vh">

        <!-- Header -->
        <div class="flex items-center justify-between px-7 py-5 border-b border-red-100 shrink-0 bg-[#ce1126] rounded-t-2xl">
            <div class="flex items-center gap-3">
                <div class="w-9 h-9 bg-white/20 rounded-xl flex items-center justify-center shrink-0">
                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                    </svg>
                </div>
                <div>
                    <h2 class="text-sm font-black text-white uppercase tracking-tight">Expected Column Format</h2>
                    <p class="text-[11px] text-red-200 mt-0.5">Sheet1 · 9 columns · Row 1 is the header</p>
                </div>
            </div>
            <button onclick="closeModal('modal-format-guide')"
                    class="p-2 hover:bg-white/20 rounded-lg transition-colors">
                <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>

        <!-- Body -->
        <div class="overflow-auto flex-1 px-7 py-5">

            <!-- Legend -->
            <div class="flex items-center gap-5 mb-4 text-[11px] font-semibold text-slate-500">
                <span class="flex items-center gap-1.5">
                    <span class="w-2.5 h-2.5 rounded-full bg-slate-300 inline-block"></span>
                    User-supplied
                </span>
                <span class="flex items-center gap-1.5">
                    <span class="w-2.5 h-2.5 rounded-full bg-red-300 inline-block"></span>
                    System-computed — do not include in file
                </span>
            </div>

            <table class="w-full text-xs">
                <thead>
                    <tr class="bg-red-50 border-b border-red-100">
                        <th class="text-left font-black text-[#ce1126] uppercase tracking-wider px-4 py-2.5 w-8">Col</th>
                        <th class="text-left font-black text-[#ce1126] uppercase tracking-wider px-4 py-2.5">Column Name</th>
                        <th class="text-left font-black text-[#ce1126] uppercase tracking-wider px-4 py-2.5">Example</th>
                        <th class="text-left font-black text-[#ce1126] uppercase tracking-wider px-4 py-2.5">Notes</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-red-50">
                    <?php
                    $cols = [
                        ['A', 'Zone',             'NCR',                               'Required — used for branch lookup'],
                        ['B', 'Region',           'NCR Batanes Region',                'Required — used for branch lookup'],
                        ['C', 'Cost Center',      '0184-006',                          'Required — format 0000-000'],
                        ['D', 'Branch',           'ML PULANGLUPA DOS',                 'For reference only; authoritative value fetched from Master Data'],
                        ['E', 'Reference Number', '342',                               'Optional — external document reference'],
                        ['F', 'Asset Category',   'Computer Equipment and Peripherals','Must match a category name exactly'],
                        ['G', 'Date Received',    '2026-01-31',                        'Date format or Excel date serial'],
                        ['H', 'Acquisition Cost', '35000',                             'Numeric — must be greater than 0'],
                        ['I', 'Description',      'Inverter Split Type 1.5HP',         'Item description — required'],
                    ];
                    foreach ($cols as $c): ?>
                    <tr class="hover:bg-red-50/50 transition-colors">
                        <td class="px-4 py-2.5 font-black font-mono text-[#ce1126]"><?= $c[0] ?></td>
                        <td class="px-4 py-2.5 font-bold text-slate-800"><?= htmlspecialchars($c[1]) ?></td>
                        <td class="px-4 py-2.5 font-mono text-slate-500"><?= htmlspecialchars($c[2]) ?></td>
                        <td class="px-4 py-2.5 text-slate-400"><?= htmlspecialchars($c[3]) ?></td>
                    </tr>
                    <?php endforeach; ?>

                    <!-- System-computed divider -->
                    <tr>
                        <td colspan="4" class="px-4 pt-5 pb-1.5">
                            <div class="flex items-center gap-2">
                                <span class="text-[10px] font-black text-[#ce1126] uppercase tracking-widest">⚙ System-computed</span>
                                <span class="flex-1 h-px bg-red-100"></span>
                                <span class="text-[10px] text-red-300 font-semibold">resolved automatically — do not add to file</span>
                            </div>
                        </td>
                    </tr>

                    <?php
                    $computed = [
                        ['Branch Name (authoritative)', 'Fetched from Master Data using Zone + Region + Cost Center'],
                        ['Category Code',               'Derived from the Asset Category name'],
                        ['Asset Life (months)',          'Pulled from the category blueprint — single source of truth'],
                        ['Depreciation Start Date',     'Last day of the Date Received month'],
                        ['Monthly Depreciation',        'Acquisition Cost ÷ Asset Life (months)'],
                        ['System Asset Code',           'Auto-generated: {Code}-{Zone}-{BranchCode}-{RefNo}'],
                    ];
                    foreach ($computed as $c): ?>
                    <tr class="bg-red-50/60 hover:bg-red-50 transition-colors">
                        <td class="px-4 py-2 text-[#ce1126] font-black text-center text-base">⚙</td>
                        <td class="px-4 py-2 font-bold text-red-700"><?= htmlspecialchars($c[0]) ?></td>
                        <td class="px-4 py-2 font-mono text-red-300 text-[10px] italic">auto</td>
                        <td class="px-4 py-2 text-red-400 text-[11px]"><?= htmlspecialchars($c[1]) ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <!-- Footer -->
        <div class="px-7 py-4 border-t border-red-100 shrink-0 flex justify-end bg-red-50 rounded-b-2xl">
            <button onclick="closeModal('modal-format-guide')"
                    class="px-6 py-2.5 bg-[#ce1126] hover:bg-red-700 text-white font-black
                           text-xs uppercase tracking-widest rounded-xl shadow-sm transition-colors">
                Got it
            </button>
        </div>
    </div>
</div>