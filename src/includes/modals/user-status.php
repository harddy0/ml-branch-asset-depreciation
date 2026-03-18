<div id="modal-status"
     class="hidden fixed inset-0 z-50 items-center justify-center bg-black/40 backdrop-blur-sm p-4">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md animate-fadeIn">

        <div class="px-7 py-6 text-center">
            <div class="w-14 h-14 bg-orange-100 rounded-full flex items-center justify-center mx-auto mb-4">
                <svg class="w-7 h-7 text-orange-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/>
                </svg>
            </div>
            <h3 class="text-base font-black text-slate-800 uppercase tracking-tight mb-2" id="status-modal-title">Change Status?</h3>
            <p class="text-sm text-slate-500 mb-1" id="status-modal-desc"></p>
            <p class="text-sm font-black text-slate-800 mt-1" id="status-name-display"></p>
        </div>

        <form method="POST" action="<?= BASE_URL ?>/public/actions/user_set_status.php"
              class="px-7 pb-7 flex gap-3">
            <input type="hidden" name="id"     id="status-user-id">
            <input type="hidden" name="status" id="status-target">
            <button type="button" onclick="closeModal('modal-status')"
                    class="flex-1 border-2 border-slate-200 text-slate-600 hover:border-slate-300 hover:bg-slate-300
                           font-black text-xs uppercase tracking-widest py-3 rounded-xl transition-all">
                Cancel
            </button>
            <button type="submit" id="status-confirm-btn"
                class="flex-1 bg-[#ce2216] hover:bg-red-700 text-white font-black text-xs uppercase tracking-widest
                           py-3 rounded-xl shadow-lg shadow-slate-100 transition-all">
                <span id="status-action-label">Confirm</span>
            </button>
        </form>
    </div>
</div>