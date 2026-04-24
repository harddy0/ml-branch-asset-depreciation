<!-- ══════════════════════════════════════════════════ -->
<!-- SECTION 1: Location Data (already working)        -->
<!-- ══════════════════════════════════════════════════ -->
<section id="location_section" class="">
    <h3 class="text-xs text-center font-black text-[#ce1126] uppercase tracking-widest pb-1 mb-2">
        Location Data
    </h3>

    <div class="space-y-2">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-1" style="grid-template-columns: 22% 78%;">
            <div class="flex items-center justify-end pr-2">
                <label class="block text-sm font-mono font-bold text-slate-700 mb-1">Branch Name <span class="text-red-500">*</span></label>
            </div>
            <div>
                <input id="branch_name_input" class="w-full text-sm font-mono border border-slate-300 rounded-lg px-3 py-2.5 focus:ring-2 focus:ring-red-500 outline-none transition-all bg-white" placeholder="Enter branch name">
                <input type="hidden" name="branch_name" id="branch_name">
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-1" style="grid-template-columns: 22% 78%;">
            <div class="flex items-center justify-end pr-2">
                <label class="block text-sm font-mono font-bold text-slate-700 mb-1">Branch Code <span class="text-red-500">*</span></label>
            </div>
                <div>
                    <input type="text" name="cost_center_code" id="cost_center_code" placeholder="Enter branch code" required class="w-full text-sm font-mono border border-slate-300 rounded-lg px-3 py-2.5 focus:ring-2 focus:ring-red-500 outline-none transition-all bg-white">
            </div>
        </div>
        
        <div class="grid grid-cols-1 md:grid-cols-2 gap-1" style="grid-template-columns: 22% 78%;">
            <div class="flex items-center justify-end pr-2">
                <label class="block text-sm font-mono font-bold text-slate-700 mb-1">Main Zone <span class="text-red-500">*</span></label>
            </div>
            <div>
                <select id="main_zone_code" disabled required class="w-full text-sm font-mono border border-slate-300 rounded-lg px-3 py-2.5 focus:ring-2 focus:ring-red-500 outline-none transition-all bg-white disabled:bg-slate-100 disabled:text-slate-400" style="appearance:none;-webkit-appearance:none;-moz-appearance:none;background-image:none;" aria-hidden="true">
                    <option value="" disabled selected>Auto</option>
                </select>
                <input type="hidden" name="main_zone_code" id="main_zone_code_hidden" required>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-1" style="grid-template-columns: 22% 78%;">
            <div class="flex items-center justify-end pr-2">
                <label class="block text-sm font-mono font-bold text-slate-700 mb-1">Sub-Zone <span class="text-red-500">*</span></label>
            </div>
            <div>
                <select id="zone_code" disabled required class="w-full text-sm font-mono border border-slate-300 rounded-lg px-3 py-2.5 focus:ring-2 focus:ring-red-500 outline-none transition-all bg-white disabled:bg-slate-100 disabled:text-slate-400" style="appearance:none;-webkit-appearance:none;-moz-appearance:none;background-image:none;" aria-hidden="true">
                    <option value="" disabled selected>Auto</option>
                </select>
                <input type="hidden" name="zone_code" id="zone_code_hidden" required>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-1" style="grid-template-columns: 22% 78%;">
            <div class="flex items-center justify-end pr-2">
                <label class="block text-sm font-mono font-bold text-slate-700 mb-1">Region <span class="text-red-500">*</span></label>
            </div>
            <div>
                <select id="region_code" disabled required class="w-full text-sm font-mono border border-slate-300 rounded-lg px-3 py-2.5 focus:ring-2 focus:ring-red-500 outline-none transition-all bg-white disabled:bg-slate-100 disabled:text-slate-400" style="appearance:none;-webkit-appearance:none;-moz-appearance:none;background-image:none;" aria-hidden="true">
                    <option value="" disabled selected>Auto</option>
                </select>
                <input type="hidden" name="region_code" id="region_code_hidden" required>
            </div>
        </div>
    </div>
    <div class="space-y-2 mt-2">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-1 hidden" style="grid-template-columns: 22% 78%;">
            <div class="flex items-center justify-end pr-2">
                <label class="block text-sm font-mono font-bold text-slate-700 mb-1">BOS Code <span class="text-red-500">*</span></label>
            </div>
            <div>
                <input type="text" id="bos_branch_code_display" class="w-full text-sm font-mono border border-slate-300 rounded-lg px-3 py-2.5 bg-slate-50" placeholder="Auto" readonly>
                <input type="hidden" name="bos_branch_code" id="bos_branch_code">
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-1" style="grid-template-columns: 22% 78%;">
            <div class="flex items-center justify-end pr-2">
                <label class="block text-sm font-mono font-bold text-slate-700 mb-1">KPX Branch ID <span class="text-red-500">*</span></label>
            </div>
            <div>
                <input type="text" id="kpx_branch_id_display" class="w-full text-sm font-mono border border-slate-300 rounded-lg px-3 py-2.5 bg-slate-50" placeholder="Auto" readonly>
                <input type="hidden" name="kpx_branch_id" id="kpx_branch_id">
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-1" style="grid-template-columns: 22% 78%;">
            <div class="flex items-center justify-end pr-2">
                <label class="block text-sm font-mono font-bold text-slate-700 mb-1">Corporate Name <span class="text-red-500">*</span></label>
            </div>
            <div>
                <input type="text" id="corporate_name_display" class="w-full text-sm font-mono border border-slate-300 rounded-lg px-3 py-2.5 bg-slate-50" placeholder="Auto" readonly>
                <input type="hidden" name="corporate_name" id="corporate_name">
            </div>
        </div>
    </div>

</section>

<style>
    /* Scoped compact layout for Location section */
    #location_section{padding:3% 5%;box-sizing:border-box;font-size:0.88rem;max-height:calc(100vh - 40px);overflow:hidden;}
    #location_section h3{font-size:0.92rem;padding-bottom:6px;margin-bottom:8px}
    #location_section .space-y-2{row-gap:6px;}
    #location_section .grid{column-gap:10px;row-gap:6px}
    #location_section .flex{align-items:center}
    #location_section label{font-size:0.82rem}
    #location_section input[type="text"],
    #location_section select{padding:8px 10px;font-size:0.86rem;line-height:1;}
    #location_section .rounded-lg{border-radius:6px}
    /* Reduce internal vertical spacing for compactness */
    #location_section .space-y-2 > div{margin-bottom:4px}
    /* Ensure fields wrap and don't force horizontal scroll */
    #location_section input, #location_section select{white-space:normal;word-break:break-word}
    /* Hide scrollbars visually inside the section and prevent inner overflow */
    #location_section{ -ms-overflow-style: none; scrollbar-width: none; }
    #location_section::-webkit-scrollbar{ display: none; }
    #location_section .space-y-2, #location_section .space-y-2 > div, #location_section .grid{ overflow: visible; }
</style>

<script>
    (function(){
        var fields = [
            { displayId: 'corporate_name_display', hiddenId: 'corporate_name' },
            { displayId: 'kpx_branch_id_display', hiddenId: 'kpx_branch_id' }
        ];

        function updateFieldBg(displayEl, hiddenEl){
            if(!displayEl) return;
            var val = (displayEl.value || (hiddenEl && hiddenEl.value) || '').toString().trim();
            if(val.length > 0){
                displayEl.classList.remove('bg-slate-50');
                displayEl.classList.add('bg-white');
            } else {
                displayEl.classList.remove('bg-white');
                displayEl.classList.add('bg-slate-50');
            }
        }

        function updateAll(){
            fields.forEach(function(f){
                var d = document.getElementById(f.displayId);
                var h = document.getElementById(f.hiddenId);
                updateFieldBg(d, h);
            });
        }

        document.addEventListener('DOMContentLoaded', updateAll);

        // Attach input listeners and observers
        fields.forEach(function(f){
            var d = document.getElementById(f.displayId);
            var h = document.getElementById(f.hiddenId);
            if(d) d.addEventListener('input', function(){ updateFieldBg(d, h); });
            if(h && window.MutationObserver){
                try{
                    var mo = new MutationObserver(function(){ updateFieldBg(d, h); });
                    mo.observe(h, { attributes: true, attributeFilter: ['value'] });
                }catch(e){}
            }
        });
    })();
</script>
