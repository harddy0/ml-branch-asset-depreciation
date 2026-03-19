// ============================================================
//  category-mgt.js  —  Category Management page scripts
//  Depends on: main.js  (openModal / closeModal / flash auto-dismiss)
// ============================================================

// ── Category Code Auto-generation (Add Modal) ────────────────
//  Rule: uppercase initials of each word in the category name
(function () {
    function buildCategoryCode() {
        var name = (document.getElementById('add-cat-name')?.value || '').trim();
        if (!name) {
            document.getElementById('add-cat-code').value = '';
            return;
        }
        // Take first letter of each word, uppercase, max 10 chars
        var code = name
            .split(/\s+/)
            .filter(Boolean)
            .map(function (w) { return w[0].toUpperCase(); })
            .join('')
            .substring(0, 10);
        document.getElementById('add-cat-code').value = code;
    }

    document.addEventListener('DOMContentLoaded', function () {
        var nameInput = document.getElementById('add-cat-name');
        if (nameInput) nameInput.addEventListener('input', buildCategoryCode);
    });
})();

// ── Auto-uppercase on type (code fields) ─────────────────────
document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('.input-uppercase').forEach(function (el) {
        el.addEventListener('input', function () {
            var pos = this.selectionStart;
            this.value = this.value.toUpperCase().replace(/[^A-Z0-9]/g, '');
            this.setSelectionRange(pos, pos);
        });
    });
});

// ── Years hint under asset life inputs ───────────────────────
function attachYearsHint(inputId, hintId) {
    document.addEventListener('DOMContentLoaded', function () {
        var inp  = document.getElementById(inputId);
        var hint = document.getElementById(hintId);
        if (!inp || !hint) return;

        function updateHint() {
            var months = parseInt(inp.value, 10);
            if (!months || months < 1) { hint.classList.add('hidden'); return; }
            hint.classList.remove('hidden');
            if (months % 12 === 0) {
                hint.textContent = '= ' + (months / 12) + ' Year' + (months / 12 !== 1 ? 's' : ' ');
            } else {
                var yrs = Math.floor(months / 12);
                var mo  = months % 12;
                hint.textContent = yrs > 0
                    ? '= ' + yrs + ' Year' + (yrs !== 1 ? 's' : ' ') + ' and ' + mo + ' Month' + (mo !== 1 ? 's' : '')
                    : '= ' + mo + ' Month' + (mo !== 1 ? 's' : '');
            }
        }

        inp.addEventListener('input', updateHint);
        updateHint();
    });
}
attachYearsHint('add-cat-life',  'add-years-hint');
attachYearsHint('edit-cat-life', 'edit-years-hint');

// ── Edit Modal ────────────────────────────────────────────────
function openEditModal(cat) {
    document.getElementById('edit-cat-id').value   = cat.id;
    document.getElementById('edit-cat-name').value = cat.category_name;
    document.getElementById('edit-cat-code').value = cat.category_code;
    document.getElementById('edit-cat-life').value = cat.asset_life_months;

    // Trigger the years hint update manually
    var evt = new Event('input', { bubbles: true });
    document.getElementById('edit-cat-life').dispatchEvent(evt);

    openModal('modal-edit-category');
}

// ── Live Search ───────────────────────────────────────────────
document.addEventListener('DOMContentLoaded', function () {
    var searchInput = document.getElementById('search-input');
    if (!searchInput) return;
    searchInput.addEventListener('input', function () {
        var q = this.value.toLowerCase().trim();
        document.querySelectorAll('#categories-tbody .category-row').forEach(function (row) {
            var code = (row.querySelector('.cat-code')?.textContent || '').toLowerCase();
            var name = (row.querySelector('.cat-name')?.textContent || '').toLowerCase();
            row.style.display = (!q || code.includes(q) || name.includes(q)) ? '' : 'none';
        });
    });
});