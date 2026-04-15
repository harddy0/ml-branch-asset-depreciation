/* add-asset.js
   Multi-step form behaviour for the Add Asset modal.
*/
(function(){
    const form = document.getElementById('addAssetForm');
    if (!form) return; // nothing to do if modal not present on page

    const steps = Array.from(form.querySelectorAll('.step'));
    const btnPrev = document.getElementById('btn-step-prev');
    const btnNext = document.getElementById('btn-step-next');
    const btnSave = document.getElementById('btn-step-save');
    const progressCircles = Array.from(document.querySelectorAll('#step-progress [data-step-index]'));
    const progressBars = Array.from(document.querySelectorAll('#step-progress [data-bar-index]'));
    let current = 0;

    function isStepValid(index){
        const step = steps[index];
        const controls = step.querySelectorAll('input, select, textarea');
        for(const c of controls){
            if(c.hasAttribute('required') && !c.checkValidity()) return false;
        }
        return true;
    }

    function updateButtons(){
        if(btnPrev) btnPrev.classList.toggle('hidden', current === 0);
        const last = current === steps.length - 1;
        if(btnNext) btnNext.classList.toggle('hidden', last);
        if(btnSave) btnSave.classList.toggle('hidden', !last);
    }

    function refreshProgressStates(){
        // Color bars based on whether the previous step is valid
        progressBars.forEach((bar, i) => {
            if(isStepValid(i)){
                bar.classList.remove('bg-slate-200');
                bar.classList.add('bg-[#ce1126]');
                // color next circle
                const next = document.querySelector('[data-step-index="' + (i+1) + '"]');
                if(next){ next.classList.remove('bg-slate-200','text-slate-600'); next.classList.add('bg-[#ce1126]','text-white'); }
            } else {
                bar.classList.remove('bg-[#ce1126]');
                bar.classList.add('bg-slate-200');
                const next = document.querySelector('[data-step-index="' + (i+1) + '"]');
                if(next && (i+1) !== current){ next.classList.remove('bg-[#ce1126]','text-white'); next.classList.add('bg-slate-200','text-slate-600'); }
            }
        });

        // ensure current circle is highlighted
        const curr = document.querySelector('[data-step-index="' + current + '"]');
        if(curr){ curr.classList.remove('bg-slate-200','text-slate-600'); curr.classList.add('bg-[#ce1126]','text-white'); }
    }

    function showStep(n){
        steps.forEach((s, i) => {
            if(i === n) s.classList.remove('hidden'); else s.classList.add('hidden');
        });
        current = n;
        updateButtons();
        refreshProgressStates();
    }

    // attach input listeners to enable Next when valid
    steps.forEach((step, idx) => {
        const controls = step.querySelectorAll('input, select, textarea');
        controls.forEach(c => {
            c.addEventListener('input', () => {
                // whenever a control changes, refresh bar states
                if(typeof refreshProgressStates === 'function') refreshProgressStates();
            });
            c.addEventListener('change', () => {
                if(typeof refreshProgressStates === 'function') refreshProgressStates();
            });
        });
    });

    // --- currency input formatting for form fields (initialized on load) ---
    function formatCurrencyInput(el, blur){
        if(!el) return;
        let v = el.value || '';
        // keep only digits and dot
        let cleaned = String(v).replace(/[^0-9.]/g, '');
        // handle multiple dots
        const parts = cleaned.split('.');
        let int = parts[0] || '';
        let dec = parts[1] || '';
        if(parts.length > 2) dec = parts.slice(1).join('').slice(0,2);
        if(dec.length > 2) dec = dec.slice(0,2);
        // remove leading zeros (but leave single zero)
        int = int.replace(/^0+(?=\d)/, '');
        const intFormatted = int.replace(/\B(?=(\d{3})+(?!\d))/g, ',') || '0';
        let newVal = dec ? intFormatted + '.' + dec : intFormatted;
        if(newVal === '0') newVal = '';
        el.value = newVal;
        if(blur){
            if(el.value === '') return;
            const num = Number(el.value.replace(/,/g, ''));
            if(!isNaN(num)){
                el.value = num.toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2});
            }
        }
        try{ el.setSelectionRange(el.value.length, el.value.length); } catch(e){}
    }

    ['#asset_acquisition_cost', '#asset_cost_unit'].forEach(sel => {
        const el = form.querySelector(sel);
        if(!el) return;
        el.addEventListener('input', () => formatCurrencyInput(el, false));
        el.addEventListener('blur', () => formatCurrencyInput(el, true));
    });

    if(btnPrev) btnPrev.addEventListener('click', () => { showStep(Math.max(0, current - 1)); });

    if(btnNext) btnNext.addEventListener('click', () => {
        // Validate current step; if invalid, show browser validation message and stop
        if(!isStepValid(current)){
            const firstInvalid = steps[current].querySelector('input:invalid, select:invalid, textarea:invalid');
            if(firstInvalid) firstInvalid.reportValidity();
            return;
        }
        // move to next
        const nextIndex = Math.min(steps.length - 1, current + 1);
        showStep(nextIndex);
        // If we've reached the final (Finish) step, build and show a summary preview
        if(nextIndex === steps.length - 1){
            buildSummary();
        }
        if(typeof refreshProgressStates === 'function') refreshProgressStates();
    });

    function buildSummary(){
        // target the dedicated finish summary container added via partial
        const container = document.getElementById('finish-summary');
        if(!container) return;

        // populate existing placeholders (elements with data-key)
        const placeholders = container.querySelectorAll('[data-key]');
        const usedKeys = new Set();
        placeholders.forEach(ph => {
            const key = ph.getAttribute('data-key');
            if(!key) return;
            const input = form.querySelector('[name="' + key + '"]') || form.querySelector('#' + key);
            let value = '';
            if(input){
                if(input.type === 'checkbox') value = input.checked ? 'Yes' : 'No';
                else value = input.value;
            }
            // If the placeholder is a currency container, fill its .amount child instead
            if(ph.classList.contains('currency')){
                const amountEl = ph.querySelector('.amount');
                if(amountEl){
                    // format numbers with thousand separators when numeric
                    const num = Number(String(value).replace(/,/g, ''));
                    if(!isNaN(num)){
                        amountEl.textContent = num.toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2});
                    } else if(value){
                        amountEl.textContent = value;
                    } else {
                        amountEl.textContent = '—';
                    }
                } else {
                    ph.textContent = value ? value : '—';
                }
            } else {
                ph.textContent = value ? value : '—';
            }
            usedKeys.add(key);
        });

        // (moved currency input formatting initialization to module load)

        // append any remaining inputs that don't have placeholders
        const remaining = document.createElement('div');
        remaining.className = 'mt-4';
        const inputs = form.querySelectorAll('input, select, textarea');
        let any = false;
        // keys to hide from the preview (kept in the form but not displayed)
        const excludeKeys = new Set(['depreciation_code']);
        inputs.forEach(i => {
            const key = i.name || i.id;
            if(!key || usedKeys.has(key)) return;
            if(i.type === 'hidden') return; // don't show hidden inputs
            if(excludeKeys.has(key)) return; // explicit exclusions
            any = true;
            const label = i.getAttribute('data-label') || i.getAttribute('aria-label') || key;
            const value = (i.type === 'checkbox') ? (i.checked ? 'Yes' : 'No') : i.value;
            const row = document.createElement('div');
            row.className = 'flex items-start gap-4 py-1';
            row.innerHTML = '<div class="text-xs text-slate-500 w-40">' + escapeHtml(label) + '</div><div class="text-sm text-slate-800">' + (value ? escapeHtml(value) : '&ndash;') + '</div>';
            remaining.appendChild(row);
        });
        // replace previous appended extra if present
        const prevExtra = container.querySelector('.__extra_summary');
        if(prevExtra) prevExtra.remove();
        if(any){
            remaining.classList.add('__extra_summary');
            container.appendChild(remaining);
        }
    }

    function escapeHtml(str){
        return String(str).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
    }

    // result modal helper
    function showResultModal(message, success){
        // remove existing
        const prev = document.getElementById('result-modal-overlay');
        if(prev) prev.remove();
        const overlay = document.createElement('div');
        overlay.id = 'result-modal-overlay';
        overlay.className = 'result-modal-overlay';
        overlay.innerHTML = `
            <div class="result-modal-card ${success ? 'success' : 'fail'}">
                <div class="result-modal-icon">${success ? '<svg width="36" height="36" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6L9 17l-5-5"/></svg>' : '<svg width="36" height="36" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>'}</div>
                <div class="result-modal-message">${escapeHtml(message)}</div>
            </div>`;
        document.body.appendChild(overlay);
        // auto remove after 2s
        setTimeout(() => { overlay.classList.add('visible'); }, 20);
        setTimeout(() => { overlay.remove(); }, 2200);
    }

    if(form) form.addEventListener('submit', function(e){
        e.preventDefault();
        // final validation: ensure last step valid
        if(!isStepValid(steps.length - 1)){
            showStep(steps.length - 1);
            const firstInvalid = steps[steps.length - 1].querySelector('input:invalid, select:invalid, textarea:invalid');
            if(firstInvalid) firstInvalid.reportValidity();
            return;
        }

        // submit via AJAX to keep UX inside modal and show success/fail result
        const submitUrl = form.getAttribute('action') || 'actions/asset_store.php';
        const fd = new FormData(form);

        // disable buttons while saving
        if(btnNext) btnNext.disabled = true;
        if(btnPrev) btnPrev.disabled = true;
        if(btnSave) btnSave.disabled = true;

        fetch(submitUrl, { method: 'POST', body: fd, credentials: 'same-origin' })
            .then(async res => {
                let ok = res.ok;
                let json = null;
                try { json = await res.json(); } catch (e) { /* ignore parse errors */ }
                // determine success: prefer json.success when available, otherwise use HTTP 2xx
                const success = (json && (json.success === true || json.success === 'true')) ? true : (ok && !json ? true : ok && (json && json.success !== false));
                if(success){
                    showResultModal('New asset added successfully!', true);
                    // optionally close add modal after short delay
                    setTimeout(() => { try{ closeModal && closeModal('modal-add-asset'); } catch(e){} }, 900);
                } else {
                    showResultModal('Asset failed to save. Please try again.', false);
                }
            })
            .catch(err => {
                console.error('Save failed', err);
                showResultModal('Asset failed to save. Please try again.', false);
            })
            .finally(() => {
                if(btnNext) btnNext.disabled = false;
                if(btnPrev) btnPrev.disabled = false;
                if(btnSave) btnSave.disabled = false;
            });
    });

    // init
    showStep(0);
})();
