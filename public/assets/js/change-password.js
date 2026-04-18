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
    const pw = np.value || '';
    const res = checkPassword(pw);
    updateRequirementUI('length', res.length);
    updateRequirementUI('upper', res.upper);
    updateRequirementUI('lower', res.lower);
    updateRequirementUI('number', res.number);
    updateRequirementUI('special', res.special);

    updateMatchHint();

    const allMet = Object.values(res).every(Boolean);
    const match = pw && pw === cp.value;
    submitBtn.disabled = !(allMet && match);
}

if (np) np.addEventListener('input', refreshState);
if (cp) cp.addEventListener('input', refreshState);

if (form) {
    form.addEventListener('submit', function (e) {
        const pw = np.value || '';
        const res = checkPassword(pw);
        const allMet = Object.values(res).every(Boolean);
        if (!allMet || pw !== cp.value) {
            e.preventDefault();
            refreshState();
            if (pw !== cp.value) cp.focus(); else np.focus();
        }
    });
}

function setIconVisible(svgEl, visible) {
    if (!svgEl) return;
    if (visible) {
        svgEl.innerHTML = '\n            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.477 0-8.268-2.943-9.542-7a9.965 9.965 0 012.223-3.488m3.06-2.46A9.957 9.957 0 0112 5c4.477 0 8.268 2.943 9.542 7-.162.517-.378 1.02-.645 1.494M15 12a3 3 0 11-6 0 3 3 0 016 0z" />\n            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3l18 18" />';
    } else {
        svgEl.innerHTML = '\n            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />\n            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.477 0 8.268 2.943 9.542 7-1.274 4.057-5.065 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />';
    }
}

function toggleVisibility(inputEl, iconEl) {
    if (!inputEl) return;
    const isPassword = inputEl.type === 'password';
    inputEl.type = isPassword ? 'text' : 'password';
    setIconVisible(iconEl, isPassword);
}

if (toggleNewBtn) toggleNewBtn.addEventListener('click', () => toggleVisibility(np, iconNew));
if (toggleConfirmBtn) toggleConfirmBtn.addEventListener('click', () => toggleVisibility(cp, iconConfirm));
