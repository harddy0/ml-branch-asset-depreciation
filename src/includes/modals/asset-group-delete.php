<div id="asset-group-delete-modal" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-slate-900/50 backdrop-blur-sm p-4">
    <div class="bg-white rounded-2xl shadow-xl w-full max-w-sm overflow-hidden">
       <div class="bg-[#ce2216] flex justify-between items-center px-4 py-3">
            <h3 class="text-lg font-black text-white uppercase tracking-wider">Confirm Delete</h3>
            <button type="button" onclick="closeModal('asset-group-delete-modal')" class="text-red-100 hover:text-white text-2xl font-bold">
                &times;
            </button>
        </div>
        
        <form id="formDeleteAssetGroup">
            <input type="hidden" id="delete_id" name="id">
            <div class="p-6">
                <p class="text-sm text-slate-600 font-medium">Are you sure you want to delete this asset group?</p>
                <div class="bg-yellow-50 border-l-4 border-yellow-50 p-3 mt-3 rounded-r-md">
                    <p class="text-sm text-yellow-700"><i class="fas fa-info-circle"></i> <strong>Note:</strong> You cannot delete an asset group if it is currently assigned to active assets in the system.</p>
                </div>
            </div>
            
            <div class="bg-slate-50 px-6 py-4 border-t border-slate-100 flex justify-end gap-3">
                <button type="button" onclick="closeModal('asset-group-delete-modal')" class="px-4 py-2 text-sm font-bold text-slate-500 hover:text-slate-700 transition-colors">Cancel</button>
                <button type="submit" class="px-6 py-2 bg-red-600 hover:bg-red-700 text-white text-sm font-bold rounded-xl shadow-md transition-all">Delete</button>
            </div>
        </form>
    </div>
</div>