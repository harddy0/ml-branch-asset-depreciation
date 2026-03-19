<!-- src/includes/modals/import-review.php-->
<div id="modal-import-review"
     class="hidden fixed inset-0 z-50 items-center justify-center bg-black/50 backdrop-blur-sm p-4">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-7xl animate-fadeIn flex flex-col"
         style="max-height:92vh">

        <!-- Header-->
        <div class="flex items-center justify-between px-7 py-5 border-b border-slate-100 shrink-0">
            <div class="flex items-center gap-4">
                <div class="w-12 h-12 bg-blue-100 rounded-full flex items-center justify-center shrink-0">
                    <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                    </svg>
                </div>
                <div>
                    <h2 class="text-lg font-black text-slate-800 uppercase tracking-tight">Import Review</h2>
                    <p class="text-xs text-slate-500 mt-0.5">
                        Review parsed data before committing.
                        <span id="review-summary-ok"
                              class="font-bold text-green-600"></span>
                        <span id="review-summary-err"
                              class="font-bold text-red-600 ml-1"></span>
                    </p>
                </div>
            </div>
            <button onclick="closeImportReview()"
                    class="p-2 hover:bg-slate-100 rounded-lg transition-colors shrink-0">
                <svg class="w-5 h-5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>

        <!-- Legend-->
        <div class="flex items-center gap-6 px-7 py-3 bg-slate-50 border-b border-slate-100 text-xs font-semibold text-slate-500 shrink-0">
            <span class="flex items-center gap-1.5">
                <span class="w-3 h-3 rounded-full bg-blue-400 inline-block"></span>
                System-computed value
            </span>
            <span class="flex items-center gap-1.5">
                <span class="w-3 h-3 rounded-full bg-green-400 inline-block"></span>
                Valid row
            </span>
            <span class="flex items-center gap-1.5">
                <span class="w-3 h-3 rounded-full bg-red-400 inline-block"></span>
                Row has errors — will be skipped
            </span>
            <span class="flex items-center gap-1.5">
                <span class="w-3 h-3 rounded-full bg-orange-400 inline-block"></span>
                Duplicate — already exists in system
            </span>
        </div>

        <!-- Table-->
        <div class="overflow-auto flex-1 px-2">
            <table class="w-full text-xs border-separate border-spacing-0 min-w-[1200px]">
                <thead class="sticky top-0 z-10">
                    <tr class="bg-slate-100">
                        <th class="text-left text-[10px] font-black text-slate-500 uppercase tracking-widest px-3 py-3 border-b border-slate-200 whitespace-nowrap">#</th>
                        <th class="text-left text-[10px] font-black text-slate-500 uppercase tracking-widest px-3 py-3 border-b border-slate-200 whitespace-nowrap">Zone</th>
                        <th class="text-left text-[10px] font-black text-slate-500 uppercase tracking-widest px-3 py-3 border-b border-slate-200 whitespace-nowrap">Region</th>
                        <th class="text-left text-[10px] font-black text-slate-500 uppercase tracking-widest px-3 py-3 border-b border-slate-200 whitespace-nowrap">Cost Center</th>
                        <!-- System-computed column headers get a blue tint-->
                        <th class="text-left text-[10px] font-black text-blue-500 uppercase tracking-widest px-3 py-3 border-b border-blue-200 bg-blue-50 whitespace-nowrap">Branch ⚙</th>
                        <th class="text-left text-[10px] font-black text-slate-500 uppercase tracking-widest px-3 py-3 border-b border-slate-200 whitespace-nowrap">Ref #</th>
                        <th class="text-left text-[10px] font-black text-slate-500 uppercase tracking-widest px-3 py-3 border-b border-slate-200 whitespace-nowrap">Category</th>
                        <th class="text-left text-[10px] font-black text-blue-500 uppercase tracking-widest px-3 py-3 border-b border-blue-200 bg-blue-50 whitespace-nowrap">Code ⚙</th>
                        <th class="text-left text-[10px] font-black text-blue-500 uppercase tracking-widest px-3 py-3 border-b border-blue-200 bg-blue-50 whitespace-nowrap">Life (mo) ⚙</th>
                        <th class="text-left text-[10px] font-black text-slate-500 uppercase tracking-widest px-3 py-3 border-b border-slate-200 whitespace-nowrap">Date Received</th>
                        <th class="text-left text-[10px] font-black text-blue-500 uppercase tracking-widest px-3 py-3 border-b border-blue-200 bg-blue-50 whitespace-nowrap">Dep. Start ⚙</th>
                        <th class="text-right text-[10px] font-black text-slate-500 uppercase tracking-widest px-3 py-3 border-b border-slate-200 whitespace-nowrap">Acq. Cost</th>
                        <th class="text-right text-[10px] font-black text-blue-500 uppercase tracking-widest px-3 py-3 border-b border-blue-200 bg-blue-50 whitespace-nowrap">Monthly Dep. ⚙</th>
                        <th class="text-left text-[10px] font-black text-slate-500 uppercase tracking-widest px-3 py-3 border-b border-slate-200 whitespace-nowrap">Description</th>
                        <th class="text-left text-[10px] font-black text-blue-500 uppercase tracking-widest px-3 py-3 border-b border-blue-200 bg-blue-50 whitespace-nowrap">System Code ⚙</th>
                    </tr>
                </thead>
                <tbody id="review-tbody">
                    <!-- Populated by JS-->
                </tbody>
            </table>
        </div>

        <!-- Footer-->
        <div class="px-7 py-5 border-t border-slate-100 shrink-0 flex items-center justify-between bg-white rounded-b-2xl">
            <div id="review-error-note" class="hidden text-xs font-semibold text-red-600 flex items-center gap-2">
                <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M12 9v2m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <span id="review-error-note-text"></span>
            </div>
            <div class="flex gap-3 ml-auto">
                <button onclick="closeImportReview()"
                        class="px-6 py-2.5 border-2 border-slate-200 text-slate-600 font-black text-xs
                               uppercase tracking-widest rounded-xl hover:bg-slate-50 transition-colors">
                    Cancel
                </button>
                <button id="btn-confirm-import"
                        onclick="confirmImport()"
                        class="px-8 py-2.5 bg-[#ce1126] hover:bg-red-700 text-white font-black text-xs
                               uppercase tracking-widest rounded-xl shadow-lg shadow-red-200
                               hover:-translate-y-0.5 transition-all disabled:opacity-40 disabled:cursor-not-allowed disabled:translate-y-0">
                    Confirm Import
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Hidden form for the commit phase-->
<form id="import-commit-form"
      method="POST"
      action="<?= BASE_URL ?>/public/actions/asset_import_process.php"
      class="hidden">
    <input type="hidden" name="action" value="commit">
</form>