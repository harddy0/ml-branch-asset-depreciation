<div id="deleteExpenseTypeModal" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-slate-900/50 backdrop-blur-sm">
    <div class="bg-white rounded-2xl shadow-xl w-full max-w-sm overflow-hidden">
        <div class="bg-red-50 px-6 py-4 border-b border-red-100 flex justify-between items-center">
            <h5 class="font-black text-red-700 uppercase tracking-wide">Confirm Deletion</h5>
            <button type="button" class="text-red-400 hover:text-red-700 transition-colors" onclick="closeDeleteModal()">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>
        <div class="p-6">
            <p class="text-sm text-slate-600 font-medium">Are you sure you want to delete this expense type? This action cannot be undone.</p>
            <input type="hidden" id="delete_id">
        </div>
        <div class="bg-slate-50 px-6 py-4 border-t border-slate-100 flex justify-end gap-3">
            <button type="button" class="px-4 py-2 text-sm font-bold text-slate-500 hover:text-slate-700 transition-colors" onclick="closeDeleteModal()">Cancel</button>
            <button type="button" class="px-6 py-2 bg-red-600 hover:bg-red-700 text-white text-sm font-bold rounded-xl shadow-md transition-all" onclick="confirmDelete()">Delete</button>
        </div>
    </div>
</div>