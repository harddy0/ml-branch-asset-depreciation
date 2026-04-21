document.addEventListener('DOMContentLoaded', function () {
    const np = document.getElementById('new_pw');
    const cp = document.getElementById('confirm_pw');
    const hint = document.getElementById('matchHint');
    const submitBtn = document.getElementById('submitBtn');
    const form = document.getElementById('pwForm');

    const toggleNewBtn = document.getElementById('toggle_new_pw');
    const toggleConfirmBtn = document.getElementById('toggle_confirm_pw');
    const iconNew = document.getElementById('icon_new_pw');
    const iconConfirm = document.getElementById('icon_confirm_pw');

    const reqEls = {
        length: document.getElementById('req-length'),
        upper: document.getElementById('req-upper'),
        lower: document.getElementById('req-lower'),
        number: document.getElementById('req-number'),
        special: document.getElementById('req-special')
    };

    function checkPassword(pw) {
        return {
            length: pw.length >= 10,
            upper: /[A-Z]/.test(pw),
            lower: /[a-z]/.test(pw),
            number: /[0-9]/.test(pw),
            special: /[^A-Za-z0-9]/.test(pw)
        };
    }

    function updateRequirementUI(id, met) {
        const el = reqEls[id];
        if (!el) return;
        const dot = el.querySelector('.req-dot');
        if (!dot) return;
        if (met) {
            dot.classList.remove('req-unmet');
            dot.classList.add('req-met');
            el.classList.remove('text-slate-500');
            el.classList.add('text-green-600', 'font-bold');
        } else {
            dot.classList.remove('req-met');
            dot.classList.add('req-unmet');
            el.classList.remove('text-green-600', 'font-bold');
            el.classList.add('text-slate-500');
        }
    }

    function updateMatchHint() {
        if (!cp || !hint) return;
        if (!cp.value) { hint.classList.add('hidden'); return; }
        hint.classList.remove('hidden');
        if (np.value === cp.value) {
            hint.textContent = '✓ Passwords match';
            hint.className = 'text-[10px] font-black mt-2 text-green-600 uppercase tracking-wider';
        } else {
            hint.textContent = '✗ Passwords do not match';
            hint.className = 'text-[10px] font-black mt-2 text-red-500 uppercase tracking-wider';
        }
    }

    function refreshState() {
        if (!np) return;
        const pw = np.value || '';
        const res = checkPassword(pw);
        updateRequirementUI('length', res.length);
        updateRequirementUI('upper', res.upper);
        updateRequirementUI('lower', res.lower);
        updateRequirementUI('number', res.number);
        updateRequirementUI('special', res.special);

        updateMatchHint();

        const allMet = Object.values(res).every(Boolean);
        const match = pw && cp && pw === cp.value;
        if (submitBtn) submitBtn.disabled = !(allMet && match);
    }

    if (np) np.addEventListener('input', refreshState);
    if (cp) cp.addEventListener('input', refreshState);

    if (form) {
        form.addEventListener('submit', function (e) {
            const pw = np.value || '';
            const res = checkPassword(pw);
            const allMet = Object.values(res).every(Boolean);
            if (!allMet || pw !== (cp ? cp.value : '')) {
                e.preventDefault();
                refreshState();
                if (cp && pw !== cp.value) cp.focus(); else if (np) np.focus();
            }
        });
    }

    function setIconVisible(svgEl, visible) {
        if (!svgEl) return;
        // Use simple rotation to indicate state instead of replacing innerHTML to avoid accidental SVG parsing issues
        if (visible) {
            svgEl.style.transform = 'rotate(0deg)';
            svgEl.style.opacity = '1';
        } else {
            svgEl.style.transform = 'rotate(0deg)';
            svgEl.style.opacity = '1';
        }
    }

    function toggleVisibility(inputEl, iconEl) {
        if (!inputEl) return;
        const isPassword = inputEl.type === 'password';
        inputEl.type = isPassword ? 'text' : 'password';
        setIconVisible(iconEl, isPassword);
        // debug log to help diagnose click issues
        try { console.debug('toggleVisibility', inputEl.id, 'to', inputEl.type); } catch (e) {}
    }

    // Attach listeners to both button and svg to ensure clicks on either work
    if (toggleNewBtn) {
        toggleNewBtn.addEventListener('click', () => toggleVisibility(np, iconNew));
        if (iconNew) iconNew.addEventListener('click', (e) => { e.stopPropagation(); toggleVisibility(np, iconNew); });
    }
    if (toggleConfirmBtn) {
        toggleConfirmBtn.addEventListener('click', () => toggleVisibility(cp, iconConfirm));
        if (iconConfirm) iconConfirm.addEventListener('click', (e) => { e.stopPropagation(); toggleVisibility(cp, iconConfirm); });
    }

    // Initialize state
    refreshState();
});
