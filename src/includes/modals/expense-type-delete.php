<div id="deleteExpenseTypeModal" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-slate-900/50 backdrop-blur-sm">
    <div class="bg-white rounded-2xl shadow-xl w-full max-w-sm overflow-hidden">
        <div class="bg-[#ce2216] flex justify-between items-center px-4 py-3">
            <h3 class="text-lg font-black text-white uppercase tracking-wider">Confirm Delete</h3>
            <button type="button" class="text-red-100 hover:text-white text-2xl font-bold" onclick="closeDeleteModal()">
                &times;
            </button>
        </div>
        <div class="p-6">
            <p class="text-sm text-slate-600 font-medium">Are you sure you want to delete this expense type?</p>
            <input type="hidden" id="delete_id">
        </div>
        <div class="bg-slate-50 px-6 py-4 border-t border-slate-100 flex justify-end gap-3">
            <button type="button" class="px-4 py-2 text-sm font-bold text-slate-500 hover:text-slate-700 transition-colors" onclick="closeDeleteModal()">Cancel</button>
            <button type="button" class="px-6 py-2 bg-red-600 hover:bg-red-700 text-white text-sm font-bold rounded-xl shadow-md transition-all" onclick="confirmDelete()">Delete</button>
        </div>
    </div>
</div>