<div id="modal-reset-pw"
     class="hidden fixed inset-0 z-50 items-center justify-center bg-black/40 backdrop-blur-sm p-4">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md animate-fadeIn">

        <div class="px-7 py-6 text-center">
            <div class="w-14 h-14 bg-amber-100 rounded-full flex items-center justify-center mx-auto mb-4">
                <svg class="w-7 h-7 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"/>
                </svg>
            </div>
            <h3 class="text-base font-black text-slate-800 uppercase tracking-tight mb-2">Reset Password For</h3>
            <p class="text-sm font-black text-slate-800 mb-3" id="reset-username-display"></p>
            <p class="text-xs text-slate-400 leading-relaxed">
               Default passoword is
                <code class="bg-slate-100 px-1.5 py-0.5 rounded font-mono text-slate-600">Mlinc1234</code>.
                The user will be required to update.
            </p>
        </div>

        <form method="POST" action="<?= BASE_URL ?>/public/actions/user_reset_password.php"
              class="px-7 pb-7 flex gap-3">
            <input type="hidden" name="id" id="reset-user-id">
            <button type="button" onclick="closeModal('modal-reset-pw')"
                class="flex-1 border-2 border-slate-200 text-slate-600 hover:border-slate-300 hover:bg-slate-300
                           font-black text-xs uppercase tracking-widest py-3 rounded-xl transition-all">
                Cancel
            </button>
            <button type="submit"
                class="flex-1 bg-[#ce2216] hover:bg-red-700 text-white font-black text-xs uppercase tracking-widest
                           py-3 rounded-xl shadow-lg shadow-slate-100 transition-all">
                Reset
            </button>
        </form>
    </div>
</div>