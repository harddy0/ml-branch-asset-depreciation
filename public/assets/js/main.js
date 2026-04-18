function openModal(id) {
    const el = document.getElementById(id);
    if (!el) return;
    el.classList.remove('hidden');
    el.classList.add('flex');
    // Animate modal panel if present
    const panel = el.querySelector('.modal-panel');
    if (panel) {
        panel.classList.remove('opacity-0', 'scale-95');
        panel.classList.add('opacity-100', 'scale-100');
    }
    // Animate backdrop if present
    const backdrop = el.querySelector('.modal-backdrop');
    if (backdrop) {
        backdrop.classList.remove('opacity-0');
        backdrop.classList.add('opacity-100');
    }
}
function closeModal(id) {
    const el = document.getElementById(id);
    if (!el) return;
    // Animate modal panel if present
    const panel = el.querySelector('.modal-panel');
    if (panel) {
        panel.classList.remove('opacity-100', 'scale-100');
        panel.classList.add('opacity-0', 'scale-95');
    }
    // Animate backdrop if present
    const backdrop = el.querySelector('.modal-backdrop');
    if (backdrop) {
        backdrop.classList.remove('opacity-100');
        backdrop.classList.add('opacity-0');
    }
    // Wait for transition before hiding
    setTimeout(() => {
        el.classList.add('hidden');
        el.classList.remove('flex');
    }, 200);
}
document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('[data-flash]').forEach(function (el) {
        setTimeout(function () {
            el.style.transition = 'opacity 0.5s';
            el.style.opacity    = '0';
            setTimeout(function () { el.remove(); }, 500);
        }, 4000);
    });
});
window.formatCurrency = function (value, symbol) {
    symbol = symbol || '';
    return symbol + parseFloat(value || 0).toLocaleString('en-PH', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
    });
};
