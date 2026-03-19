<div id="file-display" class="hidden fixed inset-0 z-50 items-center justify-center bg-black/40 backdrop-blur-sm p-4">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-2xl h-1/3.5 animate-fadeIn">
        <div class="px-7 py-6 text-center">
            <div class="w-20 h-20 rounded-full bg-green-50 flex items-center justify-center mx-auto mb-3">
                <svg class="w-10 h-10 text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M12 2a10 10 0 100 20 10 10 0 000-20z" />
                </svg>
            </div>
            <h1 class="text-lg font-black text-slate-800 uppercase mb-2">Upload File?</h1>
            <div class="flex flex-items-row justify-center gap-3 mb-3">
                <p class="text-sm font-bold text-slate-800">File Name: </p> 
                <p id="file-name" class="text-sm font-bold text-slate-800 mb-5"></p>
            </div>
            <div class="flex gap-3 justify-center">
                <button type="button" id="btn-cancel" class="px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-600 text-xs font-black uppercase tracking-widest rounded-lg transition-colors">Cancel</button>
                <button type="submit" id="btn-process" form="import-form" class="px-4 py-2 bg-[#ce1126] hover:bg-red-700 text-white text-xs font-black uppercase tracking-widest rounded-lg shadow-md shadow-slate-200 transition-colors">Upload</button>
            </div>
        </div>
    </div>
</div>
