<div id="modal-delete-confirmation" class="hidden fixed inset-0 z-50 items-center justify-center bg-black/40 backdrop-blur-sm p-4">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md animate-fadeIn overflow-hidden">
        <div class="px-6 py-5 border-b border-slate-200">
            <h2 class="text-sm font-black text-slate-800 uppercase tracking-widest">Confirm Deletion</h2>
        </div>

        <div class="px-6 py-5 space-y-4">
            <p id="delete-modal-message" class="text-sm font-medium text-slate-700 leading-relaxed">
                Are you sure you want to delete this record?
            </p>

            <form id="delete-confirm-form" method="POST" action="">
                <div id="delete-hidden-fields"></div>

                <div class="flex gap-3 pt-2">
                    <button type="button" onclick="closeModal('modal-delete-confirmation')" class="flex-1 border-2 border-slate-200 text-slate-600 hover:border-slate-300 hover:bg-slate-100 font-black text-xs uppercase tracking-widest py-3 rounded-xl transition-all">Cancel</button>
                    <button type="submit" class="flex-1 bg-[#ce1126] hover:bg-red-700 text-white font-black text-xs uppercase tracking-widest py-3 rounded-xl shadow-lg shadow-red-100 transition-all">Delete</button>
                </div>
            </form>
        </div>
    </div>
</div>
