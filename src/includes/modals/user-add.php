<div id="modal-add-user"
     class="hidden fixed inset-0 z-50 items-center justify-center bg-black/40 backdrop-blur-sm p-4">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-5xl max-h-3xl animate-fadeIn">

        <div class="flex items-center justify-between px-7 py-2 border-b border-slate-100">
            <div>
                <h2 class="text-sm font-black text-slate-800 uppercase tracking-tight">Add New User</h2>
            </div>
            <button onclick="closeModal('modal-add-user')"
                class="p-2 hover:bg-slate-100 rounded-lg transition-colors">
                <svg class="w-5 h-5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>

        <form method="POST" action="<?= BASE_URL ?>/public/actions/user_store.php"
            class="px-7 py-2 space-y-2">

            <div class="mb-4 mt-2">
                <label class="block text-xs font-black text-Red-500 uppercase tracking-widest mb-1.5">
                    Default password: Mlinc1234@
                </label>
            </div>

            <div>
                <label class="block text-xs font-black text-slate-500 uppercase tracking-widest mb-1.5">
                    Employee ID <span class="text-red-500">*</span>
                </label>
                <input type="number" name="id" id="add-emp-id" required min="1"
                    class="w-full border-2 border-slate-200 focus:border-slate-300 rounded-md px-4 py-1
                        text-sm font-bold font-mono text-slate-800 outline-none bg-slate-50 focus:bg-white transition-all">
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-black text-slate-500 uppercase tracking-widest mb-1.5">
                        First Name <span class="text-red-500">*</span>
                    </label>
                    <input type="text" name="first_name" id="add-first-name" required
                    class="w-full border-2 uppercase border-slate-200 focus:border-slate-300 rounded-md px-4 py-1
                        text-sm font-bold font-mono text-slate-800 outline-none bg-slate-50 focus:bg-white transition-all">
                </div>
                <div>
                    <label class="block text-xs font-black text-slate-500 uppercase tracking-widest mb-1.5">
                        Last Name <span class="text-red-500">*</span>
                    </label>
                    <input type="text" name="last_name" id="add-last-name" required
                      class="w-full uppercase border-2 border-slate-200 focus:border-slate-300 rounded-md px-4 py-1
                        text-sm font-bold font-mono text-slate-800 outline-none bg-slate-50 focus:bg-white transition-all">
                </div>
            </div>

            <div>
                <label class="block text-xs font-black text-slate-500 uppercase tracking-widest mb-1.5">
                    Middle Name <span class="normal-case tracking-normal font-medium text-slate-300">(optional)</span>
                </label>
                <input type="text" name="middle_name" id="add-middle-name"
                   class="w-full uppercase border-2 border-slate-200 focus:border-slate-300 rounded-md px-4 py-1
                        text-sm font-bold font-mono text-slate-800 outline-none bg-slate-50 focus:bg-white transition-all">
            </div>

            <div>
                <label class="block text-xs font-black text-slate-500 uppercase tracking-widest mb-1.5">
                    Username <span class="text-[10px] normal-case font-medium text-slate-400">(auto-generated)</span>
                </label>
                <input type="text" id="add-username-preview" readonly tabindex="-1"
                    placeholder="Fill in Last Name and ID above..."
                    class="w-full border-2 border-slate-100 rounded-md px-4 py-1 bg-slate-50 placeholder:text-slate-500 placeholder:font-mono focus:border-slate-300 transition-all
                        text-sm font-bold font-mono text-slate-500 outline-none cursor-not-allowed">
                <p class="text-[10px] text-slate-400 mt-1">First 4 characters of last name + Employee ID.</p>
            </div>

            <div>
                <label class="block text-xs font-black text-slate-500 uppercase tracking-widest mb-1.5">
                    Role <span class="text-red-500">*</span>
                </label>
                <select name="user_type" required
                    class="w-full border-2 border-slate-200 focus:border-slate-300 rounded-md px-4 py-1
                        text-sm font-bold font-mono text-slate-800 outline-none bg-slate-50 focus:bg-white transition-all">
                </div>
                    <option value="USER">User</option>
                    <option value="ADMIN">Admin</option>
                </select>
            </div>

            <div class="flex gap-3 pt-2 justify-between">
                <button type="button" onclick="closeModal('modal-add-user')"
                    class="w-1/4 border-2 border-slate-200 text-slate-600 hover:border-slate-300 hover:bg-slate-300 
                        font-black text-xs uppercase tracking-widest py-2 rounded-lg transition-all">
                    Cancel
                </button>
                <button type="submit"
                    class="w-1/4 bg-[#ce1126] hover:bg-red-700 text-white font-black text-xs uppercase tracking-widest
                        py-2 rounded-lg shadow-lg shadow-red-100 transition-all">
                    Create User
                </button>
            </div>
        </form>
    </div>
</div>