/* add-asset.js
   Multi-step form behaviour for the Add Asset modal.
*/
(function(){
    const form = document.getElementById('addAssetForm');
    if (!form) return; // nothing to do if modal not present on page

    const steps = Array.from(form.querySelectorAll('.step'));
    const btnClear = document.getElementById('btn-step-clear');
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

    function clearCurrentStep(){
        const step = steps[current];
        if(!step) return;

        const controls = step.querySelectorAll('input, select, textarea');
        controls.forEach((c) => {
            const t = (c.type || '').toLowerCase();
            if(t === 'button' || t === 'submit' || t === 'reset') return;

            if(t === 'checkbox' || t === 'radio') {
                c.checked = false;
            } else if(c.tagName === 'SELECT') {
                if(c.options && c.options.length > 0) c.selectedIndex = 0;
                else c.value = '';
            } else {
                c.value = '';
            }

            // trigger listeners that keep dependent fields in sync
            c.dispatchEvent(new Event('input', { bubbles: true }));
            c.dispatchEvent(new Event('change', { bubbles: true }));
        });

        if(typeof refreshProgressStates === 'function') refreshProgressStates();
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
    if(btnClear) btnClear.addEventListener('click', clearCurrentStep);

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

    function getAssetListUrl(){
        const action = form ? form.getAttribute('action') : '';
        if(action){
            try {
                const actionUrl = new URL(action, window.location.href);
                const publicRoot = actionUrl.pathname.replace(/\/actions\/asset_store\.php$/, '');
                return actionUrl.origin + publicRoot + '/depreciation-list/';
            } catch (e) {}
        }
        return new URL('../depreciation-list/', window.location.href).toString();
    }

    // result modal helper
    function showResultModal(message, success){
        // remove existing
        const prev = document.getElementById('result-modal-overlay');
        if(prev) prev.remove();
        const template = document.getElementById('result-modal-template');
        let overlay = null;

        const handleOk = () => {
            if(overlay) overlay.remove();
            window.location.assign(getAssetListUrl());
        };

        if (template && template.content) {
            overlay = template.content.firstElementChild.cloneNode(true);
            const card = overlay.querySelector('.result-modal-card');
            const successIcon = overlay.querySelector('[data-result-icon="success"]');
            const failIcon = overlay.querySelector('[data-result-icon="fail"]');
            const messageEl = overlay.querySelector('[data-result-message]');
            const okBtn = overlay.querySelector('[data-result-ok]');

            if (card) {
                card.classList.add(success ? 'success' : 'fail');
            }
            if (successIcon) {
                successIcon.classList.toggle('hidden', !success);
            }
            if (failIcon) {
                failIcon.classList.toggle('hidden', success);
            }
            if (messageEl) {
                messageEl.textContent = message;
            }
            if (okBtn) {
                okBtn.addEventListener('click', handleOk);
            }
        } else {
            overlay = document.createElement('div');
            overlay.id = 'result-modal-overlay';
            overlay.className = 'result-modal-overlay';
            overlay.innerHTML = `
                <div class="result-modal-card ${success ? 'success' : 'fail'}">
                    <div class="result-modal-icon">${success ? '<svg width="36" height="36" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6L9 17l-5-5"/></svg>' : '<svg width="36" height="36" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>'}</div>
                    <div class="result-modal-content">
                        <div class="result-modal-message">${escapeHtml(message)}</div>
                        <div class="result-modal-actions"><button type="button" class="result-modal-ok" data-result-ok>OK</button></div>
                    </div>
                </div>`;

            const okBtn = overlay.querySelector('[data-result-ok]');
            if (okBtn) {
                okBtn.addEventListener('click', handleOk);
            }
        }

        document.body.appendChild(overlay);
        setTimeout(() => { overlay.classList.add('visible'); }, 20);
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
        const submitUrl = form.getAttribute('action') || new URL('../actions/asset_store.php', window.location.href).toString();
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
    // initialize location-section behaviour: load branches and autofill dependent fields
    (function initLocationSection(){
        const branchInput = form.querySelector('#branch_name_input'); // visible searchable field
        const hiddenBranch = form.querySelector('#branch_name'); // hidden actual form value
        const costEl = form.querySelector('#cost_center_code');
        const mainZoneEl = form.querySelector('#main_zone_code');
        const zoneEl = form.querySelector('#zone_code');
        const regionEl = form.querySelector('#region_code');
        // hidden inputs used for form submission (visible selects are display-only)
        const mainZoneHidden = form.querySelector('#main_zone_code_hidden');
        const zoneHidden = form.querySelector('#zone_code_hidden');
        const regionHidden = form.querySelector('#region_code_hidden');

        if(!branchInput || !hiddenBranch) return;

        let branchesData = [];

        function clearSelect(sel, placeholder){
            if(!sel) return;
            sel.innerHTML = '';
            const opt = document.createElement('option');
            opt.value = '';
            opt.disabled = true;
            opt.selected = true;
            opt.textContent = placeholder || 'Select...';
            sel.appendChild(opt);
        }

        clearSelect(mainZoneEl, 'Loading...');
        clearSelect(zoneEl, 'Loading...');
        clearSelect(regionEl, 'Loading...');
        if(mainZoneEl) mainZoneEl.disabled = true;
        if(zoneEl) zoneEl.disabled = true;
        if(regionEl) regionEl.disabled = true;

        // fetch branches from API and populate datalist + internal cache
        // compute public base (reuse same logic as other scripts)
        let appBase = '';
        if (typeof BASE_URL !== 'undefined' && BASE_URL !== '') {
            appBase = BASE_URL.replace(/\/+$/, '');
        }
        const publicBase = appBase === ''
            ? '/public'
            : (appBase.endsWith('/public') ? appBase : appBase + '/public');

            const branchList = null;
        fetch(publicBase + '/api/get_locations.php?level=branches', { credentials: 'same-origin' })
            .then(r => r.json())
            .then(json => {
                if(!(json && json.success && Array.isArray(json.data))) {
                    branchInput.placeholder = 'No branches';
                    return;
                }
                branchesData = json.data.slice();
                    // no native datalist used; rely on custom suggestions

                // if a branch is already present in hidden input prefilled (edit), reflect it
                if(hiddenBranch.value){
                    branchInput.value = hiddenBranch.value;
                    const ev = new Event('input', { bubbles: true });
                    branchInput.dispatchEvent(ev);
                }
            })
            .catch(err => {
                console.error('Failed to fetch branches', err);
                branchInput.placeholder = 'Failed to load';
            });

        // when user types or selects from datalist, filter suggestions and find matching branch to autofill
        branchInput.addEventListener('input', function(e){
            const val = String(branchInput.value || '').trim();

            // filter datalist options to show relevant suggestions (match tokens anywhere)
            if(branchList && branchesData.length > 0){
                const tokens = val.toLowerCase().split(/\s+/).filter(Boolean);
                const matches = branchesData.filter(b => {
                    const name = String(b.value || b.label || '').toLowerCase();
                    return tokens.every(t => name.indexOf(t) !== -1);
                }).slice(0, 200);
                branchList.innerHTML = '';
                matches.forEach(item => {
                    const opt = document.createElement('option');
                    opt.value = item.value || '';
                    branchList.appendChild(opt);
                });
            }

            const found = branchesData.find(b => (b.value === val || b.label === val));
            if(found){
                hiddenBranch.value = found.value || '';
                if(costEl) costEl.value = found.branch_code || found.cost_center_code || '';

                function setSingleOption(sel, val, label){
                    if(!sel) return;
                    sel.innerHTML = '';
                    if(val){
                        const o = document.createElement('option');
                        o.value = val;
                        o.textContent = label || val;
                        sel.appendChild(o);
                        sel.value = val;
                        // make select appear enabled (white) but prevent interaction
                        sel.disabled = false;
                        sel.style.pointerEvents = 'none';
                        sel.classList.remove('disabled:bg-slate-100', 'disabled:text-slate-400');
                        sel.style.background = 'white';
                        sel.style.color = '';
                    } else {
                        const ph = document.createElement('option');
                        ph.value = '';
                        ph.disabled = true;
                        ph.selected = true;
                        ph.textContent = 'N/A';
                        sel.appendChild(ph);
                        sel.disabled = true;
                        sel.classList.add('disabled:bg-slate-100', 'disabled:text-slate-400');
                        sel.style.pointerEvents = '';
                    }
                }

                setSingleOption(mainZoneEl, found.main_zone_code, found.main_zone_code);
                setSingleOption(zoneEl,     found.zone_code,      found.zone_code);
                const regionCode = found.region || '';
                const regionLabel = regionCode; // show only branch_profile.region
                setSingleOption(regionEl,   regionCode,    regionLabel);
                // set hidden inputs for submission
                if(mainZoneHidden) mainZoneHidden.value = found.main_zone_code || '';
                if(zoneHidden) zoneHidden.value = found.zone_code || '';
                if(regionHidden) regionHidden.value = found.region || '';
            } else {
                // clear values if input doesn't match a branch
                hiddenBranch.value = '';
                if(costEl) costEl.value = '';
                function clearSel(sel, placeholder){ if(!sel) return; sel.innerHTML = `<option value="" disabled selected>${placeholder || 'N/A'}</option>`; sel.disabled = true; sel.classList.add('disabled:bg-slate-100', 'disabled:text-slate-400'); sel.style.pointerEvents = ''; sel.style.background = ''; }
                clearSel(mainZoneEl, 'Enter branch name or branch code...');
                clearSel(zoneEl, 'Enter branch name or branch code...');
                clearSel(regionEl, 'Enter branch name or branch code...');
                if(mainZoneHidden) mainZoneHidden.value = '';
                if(zoneHidden) zoneHidden.value = '';
                if(regionHidden) regionHidden.value = '';
            }
        });

        // allow entering branch code manually: match branch by branch_code or cost_center_code
        if(costEl){
            costEl.addEventListener('input', function(){
                const v = String(costEl.value || '').trim();
                if(v === '') return;
                const found = branchesData.find(b => String(b.branch_code || b.cost_center_code || '').toLowerCase() === v.toLowerCase());
                if(!found) return;
                // reflect branch name and trigger autofill
                branchInput.value = found.value || found.label || '';
                hiddenBranch.value = found.value || '';
                if(mainZoneHidden) mainZoneHidden.value = found.main_zone_code || '';
                if(zoneHidden) zoneHidden.value = found.zone_code || '';
                if(regionHidden) regionHidden.value = found.region || '';
                function setSingleOptionDisplay(sel, val){ if(!sel) return; sel.innerHTML = ''; if(val){ const o = document.createElement('option'); o.value = val; o.textContent = val; sel.appendChild(o); sel.value = val; sel.disabled = false; sel.style.pointerEvents = 'none'; sel.classList.remove('disabled:bg-slate-100', 'disabled:text-slate-400'); sel.style.background = 'white'; sel.style.color = ''; } }
                setSingleOptionDisplay(mainZoneEl, found.main_zone_code);
                setSingleOptionDisplay(zoneEl, found.zone_code);
                setSingleOptionDisplay(regionEl, found.region || '');
            });
        }
            // Create a custom suggestions popup that we control and position below the input
            const suggestions = document.createElement('div');
            suggestions.className = '__branch_suggestions';
            suggestions.style.position = 'absolute';
            suggestions.style.zIndex = '9999';
            suggestions.style.minWidth = '240px';
            suggestions.style.maxWidth = '520px';
            suggestions.style.maxHeight = '300px';
            suggestions.style.overflow = 'auto';
            suggestions.style.background = '#111827';
            suggestions.style.color = '#fff';
            suggestions.style.borderRadius = '6px';
            suggestions.style.boxShadow = '0 6px 24px rgba(0,0,0,0.2)';
            suggestions.style.display = 'none';
            suggestions.style.padding = '6px 0';
            document.body.appendChild(suggestions);

            let highlightedIndex = -1;

            function positionSuggestions(){
                const rect = branchInput.getBoundingClientRect();
                const scrollTop = window.pageYOffset || document.documentElement.scrollTop;
                const left = rect.left + window.pageXOffset;
                const top = rect.bottom + scrollTop + 6;
                suggestions.style.left = left + 'px';
                suggestions.style.top = top + 'px';
                suggestions.style.minWidth = rect.width + 'px';
            }

            function renderSuggestions(items){
                suggestions.innerHTML = '';
                items.forEach((it, idx) => {
                    const el = document.createElement('div');
                    el.className = '__branch_suggestion_item';
                    el.textContent = it.value || it.label || '';
                    el.style.padding = '8px 12px';
                    el.style.cursor = 'pointer';
                    el.style.whiteSpace = 'nowrap';
                    el.style.overflow = 'hidden';
                    el.style.textOverflow = 'ellipsis';
                    if(idx === highlightedIndex) el.style.background = 'rgba(255,255,255,0.08)';
                    el.addEventListener('mousedown', function(ev){
                        // use mousedown to pick before blur
                        ev.preventDefault();
                        pickSuggestion(it);
                    });
                    suggestions.appendChild(el);
                });
                suggestions.style.display = items.length > 0 ? 'block' : 'none';
                positionSuggestions();
            }

            function pickSuggestion(item){
                branchInput.value = item.value || item.label || '';
                hiddenBranch.value = item.value || item.label || '';
                if(costEl) costEl.value = item.branch_code || item.cost_center_code || '';
                // fill selects
                function setSingleOption(sel, val, label){
                    if(!sel) return;
                    sel.innerHTML = '';
                    if(val){
                        const o = document.createElement('option');
                        o.value = val;
                        o.textContent = label || val;
                        sel.appendChild(o);
                        sel.value = val;
                        // make it appear enabled but non-interactive
                        sel.disabled = false;
                        sel.style.pointerEvents = 'none';
                        sel.classList.remove('disabled:bg-slate-100', 'disabled:text-slate-400');
                        sel.style.background = 'white';
                        sel.style.color = '';
                    } else {
                        const ph = document.createElement('option');
                        ph.value = '';
                        ph.disabled = true;
                        ph.selected = true;
                        ph.textContent = 'N/A';
                        sel.appendChild(ph);
                        sel.disabled = true;
                        sel.classList.add('disabled:bg-slate-100', 'disabled:text-slate-400');
                        sel.style.pointerEvents = '';
                    }
                }
                const regionCode = item.region || '';
                const regionLabel = regionCode; // show only branch_profile.region
                setSingleOption(mainZoneEl, item.main_zone_code, item.main_zone_code);
                setSingleOption(zoneEl,     item.zone_code,      item.zone_code);
                setSingleOption(regionEl,   regionCode,          regionLabel);
                // set hidden inputs for submission as well
                if(typeof mainZoneHidden !== 'undefined' && mainZoneHidden) mainZoneHidden.value = item.main_zone_code || '';
                if(typeof zoneHidden !== 'undefined' && zoneHidden) zoneHidden.value = item.zone_code || '';
                if(typeof regionHidden !== 'undefined' && regionHidden) regionHidden.value = item.region || '';
                // hide
                suggestions.style.display = 'none';
                highlightedIndex = -1;
            }

            function filterAndShow(val){
                const tokens = val.toLowerCase().split(/\s+/).filter(Boolean);
                const matches = branchesData.filter(b => {
                    const name = String(b.value || b.label || '').toLowerCase();
                    return tokens.every(t => name.indexOf(t) !== -1);
                }).slice(0, 200);
                highlightedIndex = -1;
                renderSuggestions(matches);
            }

            branchInput.addEventListener('input', function(){
                const v = (branchInput.value || '').trim();
                if(!v){ suggestions.style.display = 'none'; hiddenBranch.value = ''; return; }
                // update datalist as well for compatibility
                if(branchList){
                    branchList.innerHTML = '';
                    const tokens = v.toLowerCase().split(/\s+/).filter(Boolean);
                    const matches = branchesData.filter(b => {
                        const name = String(b.value || b.label || '').toLowerCase();
                        return tokens.every(t => name.indexOf(t) !== -1);
                    }).slice(0,200);
                    matches.forEach(item => { const o = document.createElement('option'); o.value = item.value || ''; branchList.appendChild(o); });
                }
                filterAndShow(v);
            });

            branchInput.addEventListener('keydown', function(ev){
                if(suggestions.style.display === 'none') return;
                const items = suggestions.querySelectorAll('.__branch_suggestion_item');
                if(ev.key === 'ArrowDown'){
                    ev.preventDefault();
                    highlightedIndex = Math.min(highlightedIndex + 1, items.length - 1);
                    items.forEach((it,i)=> it.style.background = i===highlightedIndex ? 'rgba(255,255,255,0.08)' : '');
                } else if(ev.key === 'ArrowUp'){
                    ev.preventDefault();
                    highlightedIndex = Math.max(highlightedIndex - 1, 0);
                    items.forEach((it,i)=> it.style.background = i===highlightedIndex ? 'rgba(255,255,255,0.08)' : '');
                } else if(ev.key === 'Enter'){
                    ev.preventDefault();
                    const idx = highlightedIndex >= 0 ? highlightedIndex : 0;
                    const match = branchesData.filter(b => {
                        const name = String(b.value || b.label || '').toLowerCase();
                        const tokens = (branchInput.value || '').toLowerCase().split(/\s+/).filter(Boolean);
                        return tokens.every(t => name.indexOf(t) !== -1);
                    })[idx];
                    if(match) pickSuggestion(match);
                } else if(ev.key === 'Escape'){
                    suggestions.style.display = 'none';
                }
            });

            // hide suggestions when clicking outside
            document.addEventListener('click', function(e){
                if(!suggestions.contains(e.target) && e.target !== branchInput){
                    suggestions.style.display = 'none';
                }
            });
    })();

    showStep(0);
})();
