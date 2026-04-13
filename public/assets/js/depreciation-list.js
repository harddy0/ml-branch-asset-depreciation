document.addEventListener('DOMContentLoaded', function () {
    // Existing table/col controls initialization (if present)
    try {
        var table = document.getElementById('depr-table');
        if (table) {
            var thead = table.querySelector('thead tr');
            var headers = Array.from(thead.children);
            var controls = document.getElementById('col-controls');
            var colCount = headers.length;
            var colgroup = document.getElementById('depr-colgroup');
            var cols = colgroup ? Array.from(colgroup.children) : [];

            if (cols.length !== colCount) {
                colgroup.innerHTML = '';
                cols = [];
                for (var c = 0; c < colCount; c++) {
                    var col = document.createElement('col');
                    col.style.width = (Math.floor(100 / colCount)) + '%';
                    colgroup.appendChild(col);
                    cols.push(col);
                }
            }

            var base = Math.floor(100 / colCount);
            var remainder = 100 - base * colCount;
            var defaults = new Array(colCount).fill(base);
            for (var i = 0; i < remainder; i++) defaults[i]++;

            function createControl(i, val) {
                var wrap = document.createElement('div');
                wrap.className = 'flex items-center gap-1';
                var label = document.createElement('span');
                label.className = 'text-xs';
                label.textContent = headers[i].textContent.trim().slice(0, 8);
                var range = document.createElement('input');
                range.type = 'range'; range.min = 1; range.max = 100; range.value = val;
                range.dataset.index = i; range.className = 'w-28';
                var number = document.createElement('input');
                number.type = 'number'; number.min = 1; number.max = 100; number.value = val;
                number.dataset.index = i; number.className = 'w-16 px-1 border rounded text-sm';
                wrap.appendChild(label); wrap.appendChild(range); wrap.appendChild(number); wrap.appendChild(document.createTextNode('%'));
                range.addEventListener('input', function () { var idx = parseInt(this.dataset.index, 10); number.value = this.value; applyWidth(idx, this.value); });
                number.addEventListener('change', function () { var v = Math.min(100, Math.max(1, parseInt(this.value, 10) || 1)); var idx = parseInt(this.dataset.index, 10); this.value = v; var r = controls.querySelector('input[type=range][data-index="' + idx + '"]'); if (r) r.value = v; applyWidth(idx, v); });
                return wrap;
            }

            function applyWidth(idx, percent) { if (!cols[idx]) return; cols[idx].style.width = percent + '%'; }
            for (var i = 0; i < colCount; i++) { var ctrl = createControl(i, defaults[i]); controls.appendChild(ctrl); }
            for (var i = 0; i < colCount; i++) applyWidth(i, defaults[i]);
        }
    } catch (e) { console.error(e); }

    // --- Wizard logic for modal-add-asset ---
    var modal = document.getElementById('modal-add-asset');
    if (!modal) return;
    var form = document.getElementById('add-asset-form');
    var steps = Array.from(modal.querySelectorAll('.asset-step'));
    var total = steps.length;
    var stepEl = document.getElementById('asset-wizard-step');
    var progress = document.getElementById('asset-wizard-progress');
    var current = 1;

    function showStep(n) {
        current = Math.max(1, Math.min(total, n));
        steps.forEach(function (s) { s.classList.add('hidden'); });
        var el = modal.querySelector('[data-step="' + current + '"]');
        if (el) el.classList.remove('hidden');
        // progress
        var pct = Math.round((current - 1) / (total - 1) * 100);
        progress.style.width = pct + '%';
        if (stepEl) stepEl.textContent = current;
        // toggle footer buttons
        var prevBtn = modal.querySelector('#asset-btn-prev');
        var nextBtn = modal.querySelector('#asset-btn-next');
        var saveBtn = modal.querySelector('#asset-btn-save');
        if (prevBtn) prevBtn.classList.toggle('hidden', current === 1);
        if (nextBtn) nextBtn.classList.toggle('hidden', current === total);
        if (saveBtn) saveBtn.classList.toggle('hidden', current !== total);
        // update next button label for clarity
        if (nextBtn) {
            if (current === 1) nextBtn.textContent = 'Next: Accounting Details';
            else if (current === 2) nextBtn.textContent = 'Next: Advanced & Location';
            else nextBtn.textContent = 'Next';
        }
    }

    // basic validation: required inputs in visible step
    function validateStep(n) {
        var el = modal.querySelector('[data-step="' + n + '"]');
        if (!el) return true;
        var required = Array.from(el.querySelectorAll('[required]'));
        for (var i = 0; i < required.length; i++) {
            if (!required[i].value || String(required[i].value).trim() === '') {
                required[i].focus();
                return false;
            }
        }
        return true;
    }

    modal.addEventListener('click', function (e) {
        // close on background click
        if (e.target === modal) closeModal('modal-add-asset');
    });

    // delegate next/prev buttons
    modal.addEventListener('click', function (e) {
        var btn = e.target.closest('button[data-action], button[data-action] *');
        if (!btn) return;
        btn = btn.closest('button');
        var action = btn.getAttribute('data-action');
        if (action === 'next') {
            if (!validateStep(current)) return;
            showStep(current + 1);
        } else if (action === 'prev') {
            showStep(current - 1);
        }
    });

    // intercept form submit to validate all steps (simple)
    form.addEventListener('submit', function (e) {
        e.preventDefault();
        // validate current step
        if (!validateStep(current)) return;
        // optionally validate all required fields across steps
        var allReq = Array.from(form.querySelectorAll('[required]'));
        for (var i = 0; i < allReq.length; i++) { if (!allReq[i].value || String(allReq[i].value).trim() === '') { allReq[i].focus(); return; } }
        // submit via fetch or default — currently simply close modal
        // TODO: implement real save endpoint
        closeModal('modal-add-asset');
        alert('Asset saved (demo). Implement server-side save as needed.');
    });

    // initialize
    showStep(1);
});
