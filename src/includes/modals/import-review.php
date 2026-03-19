<!-- src/includes/modals/import-review.php-->
<div id="modal-import-review"
     class="hidden fixed inset-0 z-50 items-center justify-center bg-black/50 backdrop-blur-sm p-4">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-7xl animate-fadeIn flex flex-col"
         style="max-height:92vh">

        <!-- Header-->
        <div class="flex items-center justify-between px-7 py-2 border-b border-slate-100 shrink-0">
            <div class="flex items-center gap-4">
                <div>
                    <h2 class="text-md text-slate-800 uppercase tracking-wide">Import Review</h2>
                    <p class="text-xs text-slate-500 mt-0.5">
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
        <div class="flex items-center gap-6 px-7 py-3 bg-white border-b border-slate-100 text-xs font-semibold text-slate-500 shrink-0">
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
                Row has errors — skipped
            </span>
            <span class="flex items-center gap-1.5">
                <span class="w-3 h-3 rounded-full bg-orange-400 inline-block"></span>
                Duplicate — already exists in system
            </span>
        </div>

        <!-- Table-->
        <div class="overflow-auto flex-1 px-2">
            <table class="w-full text-xs border-separate border-spacing-0 min-w-[900px]">
                <thead class="sticky top-0 z-10">
                    <tr class="bg-[#ce2216]">
                        <th class="text-center text-[10px] font-black text-white uppercase tracking-widest px-3 py-3 border-b border-slate-200 whitespace-nowrap">
                            <input type="checkbox" id="review-select-all" class="w-3.5 h-3.5 rounded border-slate-300 text-[#ce1126] focus:ring-red-200">
                        </th>
                        <th class="text-left text-[10px] font-black text-white uppercase tracking-widest px-3 py-3 border-b border-slate-200 whitespace-nowrap">No.</th>
                        <th class="text-left text-[10px] font-black text-white uppercase tracking-widest px-3 py-3 border-b border-slate-200 whitespace-nowrap">Zone</th>
                        <th class="text-left text-[10px] font-black text-white uppercase tracking-widest px-3 py-3 border-b border-slate-200 whitespace-nowrap">Region</th>
                        <th class="text-left text-[10px] font-black text-white uppercase tracking-widest px-3 py-3 border-b border-slate-200 whitespace-nowrap">Cost Center</th>
                        <!-- System-computed column headers get a blue tint-->
                        <th class="text-left text-[10px] font-black text-white uppercase tracking-widest px-3 py-3 border-b border-slate-200 whitespace-nowrap">Branch</th>
                        <th class="text-left text-[10px] font-black text-white uppercase tracking-widest px-3 py-3 border-b border-slate-200 whitespace-nowrap">Reference Number</th>
                        <th class="text-left text-[10px] font-black text-white uppercase tracking-widest px-3 py-3 border-b border-slate-200 whitespace-nowrap">Category</th>
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
    <input type="hidden" name="selected_rows" id="selected-rows" value="">
</form>