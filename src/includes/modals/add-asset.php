<div id="modal-add-asset" class="fixed inset-0 z-50 hidden bg-slate-900/60 backdrop-blur-sm flex items-center justify-center p-4 sm:p-6">
    <div class="bg-white rounded-xl shadow-2xl w-full max-w-[calc(100%-2rem)] sm:max-w-[calc(100%-3rem)] h-[95vh] max-h-[95vh] flex flex-col overflow-hidden animate-fadeIn">
        
        <!-- Modal Header -->
        <div class="px-6 py-2 border-b border-slate-200 flex justify-between items-center bg-slate-50">
            <h2 class="text-md font-black text-slate-800 uppercase tracking-wide flex items-center gap-2">
                Add New Asset
            </h2>
            <button type="button" onclick="closeModal('modal-add-asset')" class="text-slate-400 hover:text-red-600 transition-colors">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>

        <!-- Modal Body-->
        <div class="flex-1 overflow-hidden p-6 bg-white">
            <form id="addAssetForm" class="space-y-8" action="<?= BASE_URL ?>/public/actions/asset_store.php" data-submit-managed="add-asset-js">

                <!-- Step progress (full-width track with positioned circles) -->
                <div id="step-progress" class="relative mb-6 mr-12 ml-12 px-6 translate-y-0">
                    <div class="relative flex items-center justify-between">
                        <div class="flex flex-col items-center z-10">
                            <div class="w-8 h-8 rounded-full bg-[#ce1126] text-white flex items-center justify-center font-bold" data-step-index="0">1</div>
                            <div class="text-xs font-semibold mt-2">Location</div>
                        </div>

                        <div class="flex-1 mx-4 h-1 bg-slate-200 rounded transform -translate-y-2" data-bar-index="0"></div>

                        <div class="flex flex-col items-center z-10">
                            <div class="w-8 h-8 rounded-full bg-slate-200 text-slate-600 flex items-center justify-center font-bold" data-step-index="1">2</div>
                            <div class="text-xs font-semibold mt-2">Classification</div>
                        </div>

                        <div class="flex-1 mx-4 h-1 bg-slate-200 rounded transform -translate-y-2" data-bar-index="1"></div>

                        <div class="flex flex-col items-center z-10">
                            <div class="w-8 h-8 rounded-full bg-slate-200 text-slate-600 flex items-center justify-center font-bold" data-step-index="2">3</div>
                            <div class="text-xs font-semibold mt-2">Depreciation</div>
                        </div>

                        <div class="flex-1 mx-4 h-1 bg-slate-200 rounded transform -translate-y-2" data-bar-index="2"></div>

                        <div class="flex flex-col items-center z-10">
                            <div class="w-8 h-8 rounded-full bg-slate-200 text-slate-600 flex items-center justify-center font-bold" data-step-index="3">4</div>
                            <div class="text-xs font-semibold mt-2">Finish</div>
                        </div>
                    </div>
                </div>

                <!-- Step 1 -->
                <div class="step translate-y-0 m-[10%] border border-slate-200 shadow-md rounded-md" data-step="1">
                    <?php include __DIR__ . '/Add Asset Section/section-location.php'; ?>
                </div>

                <!-- Step 2 -->
                <div class="step hidden m-[10%] border border-slate-200 shadow-md rounded-md" data-step="2">
                    <?php include __DIR__ . '/Add Asset Section/section-details.php'; ?>
                </div>

                <!-- Step 3 -->
                <div class="step translate-y-12 m-[20%] p-[3%] border border-slate-200 shadow-md rounded-md" data-step="3">
                    <?php include __DIR__ . '/Add Asset Section/section-depreciation.php'; ?>
                </div>

                <!-- Step 4 - Finish/Review -->
                <div class="step hidden m-[5%] p-[0%] pt-2 text-center" data-step="4">
                    <h3 class="text-lg font-bold">Asset Preview</h3>
                    <?php include __DIR__ . '/Add Asset Section/section-finish.php'; ?>
                </div>

            </form>
        </div>

        <!-- Modal Footer -->
        <div class="px-6 py-2 border-t border-slate-200 flex justify-end gap-3 bg-slate-50">
            <button id="btn-step-clear" type="button"
                class="px-5 py-2 text-sm font-bold text-slate-600 bg-white border border-slate-300 rounded-lg hover:bg-slate-50 transition-colors">
                Clear
            </button>

            <button type="button" onclick="closeModal('modal-add-asset')"
                class="px-5 py-2 text-sm font-bold text-slate-600 bg-white border border-slate-300
                       rounded-lg hover:bg-slate-50 transition-colors">
                Cancel
            </button>

            <button id="btn-step-prev" type="button" class="px-4 py-2 text-sm font-bold text-slate-700 bg-white border border-slate-300 rounded-lg hover:bg-slate-50 transition-colors hidden">
                Prev
            </button>

            <button id="btn-step-next" type="button" class="px-4 py-2 text-sm font-bold text-white bg-[#ce1126] rounded-lg hover:bg-red-700 shadow-sm transition-colors tracking-wide">
                Next
            </button>

            <button id="btn-step-save" type="submit" form="addAssetForm" class="px-4 py-2 text-sm font-bold text-white bg-[#ce1126] rounded-lg hover:bg-red-700 shadow-sm transition-colors tracking-wide hidden">
                Save
            </button>
        </div>

        <?php
            // include per-page JS for the Add Asset modal (cache-busted)
            $assetPath = realpath(__DIR__ . '/../../../assets/js/add-asset.js');
            $ver = ($assetPath && file_exists($assetPath)) ? '?v=' . filemtime($assetPath) : '';
        ?>
        <?php include __DIR__ . '/sucess-or-fail-message.php'; ?>
        <script src="<?= ASSET_URL ?>js/add-asset.js<?= $ver ?>"></script>

    </div>
</div>