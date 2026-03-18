// ============================================================
//  user-mgt.js  —  User Management page scripts
//  Depends on: main.js  (openModal / closeModal / flash auto-dismiss)
// ============================================================

// ── Username Auto-generation (Add Modal) ─────────────────────
//  Rule: first 4 chars of last name (uppercase) + employee ID
(function () {
    function buildUsername() {
        var ln = (document.getElementById('add-last-name')?.value  || '').trim().toUpperCase();
        var id = (document.getElementById('add-emp-id')?.value     || '').trim();
        var prefix = ln.substring(0, 4);
        document.getElementById('add-username-preview').value = prefix + id;
    }
    document.addEventListener('DOMContentLoaded', function () {
        var lnInput = document.getElementById('add-last-name');
        var idInput = document.getElementById('add-emp-id');
        if (lnInput) lnInput.addEventListener('input', buildUsername);
        if (idInput) idInput.addEventListener('input', buildUsername);
    });
})();

// ── Username Auto-generation (Edit Modal) ────────────────────
(function () {
    function buildEditUsername() {
        var ln = (document.getElementById('edit-last-name')?.value || '').trim().toUpperCase();
        var id = (document.getElementById('edit-id')?.value        || '').trim();
        var prefix = ln.substring(0, 4);
        document.getElementById('edit-username-preview').value = prefix + id;
    }
    document.addEventListener('DOMContentLoaded', function () {
        var lnInput = document.getElementById('edit-last-name');
        if (lnInput) lnInput.addEventListener('input', buildEditUsername);
        // id field is hidden/read-only in edit — no listener needed
    });
    window._buildEditUsername = buildEditUsername; // called by openEditModal after pre-fill
})();

// ── Auto-uppercase on type (name fields) ─────────────────────
document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('.input-uppercase').forEach(function (el) {
        el.addEventListener('input', function () {
            var pos = this.selectionStart;
            this.value = this.value.toUpperCase();
            this.setSelectionRange(pos, pos);
        });
    });
});

// ── Edit Modal ────────────────────────────────────────────────
function openEditModal(user) {
    document.getElementById('edit-id').value           = user.id;
    document.getElementById('edit-first-name').value   = user.first_name.toUpperCase();
    document.getElementById('edit-middle-name').value  = (user.middle_name || '').toUpperCase();
    document.getElementById('edit-last-name').value    = user.last_name.toUpperCase();
    document.getElementById('edit-user-type').value    = user.user_type;
    window._buildEditUsername();   // refresh preview with pre-filled values
    openModal('modal-edit-user');
}

// ── Reset Password Confirm Modal ──────────────────────────────
function confirmReset(id, username) {
    document.getElementById('reset-user-id').value               = id;
    document.getElementById('reset-username-display').textContent = username;
    openModal('modal-reset-pw');
}

// ── Restrict / Activate Confirm Modal ────────────────────────
function confirmToggleStatus(id, name, currentStatus) {
    var toRestrict = currentStatus === 'ACTIVE';
    document.getElementById('status-user-id').value               = id;
    document.getElementById('status-target').value                = toRestrict ? 'RESTRICTED' : 'ACTIVE';
    document.getElementById('status-name-display').textContent    = name;
    document.getElementById('status-action-label').textContent    = toRestrict ? 'Restrict' : 'Activate';
    document.getElementById('status-modal-title').textContent     = toRestrict ? 'Restrict User?' : 'Activate User?';
    document.getElementById('status-modal-desc').textContent      = toRestrict
        ? 'This user will no longer be able to log in until reactivated.'
        : 'This user will be able to log in again.';

    var btn = document.getElementById('status-confirm-btn');
    if (toRestrict) {
        btn.className = btn.className.replace(/bg-\w+-\d+/g, '').replace(/shadow-\w+-\d+/g, '').trim();
        btn.classList.add('bg-orange-500', 'hover:bg-orange-600', 'shadow-orange-100');
    } else {
        btn.className = btn.className.replace(/bg-\w+-\d+/g, '').replace(/shadow-\w+-\d+/g, '').trim();
        btn.classList.add('bg-green-600', 'hover:bg-green-700', 'shadow-green-100');
    }
    openModal('modal-status');
}

// ── Live Search ───────────────────────────────────────────────
document.addEventListener('DOMContentLoaded', function () {
    var searchInput = document.getElementById('search-input');
    if (!searchInput) return;
    searchInput.addEventListener('input', function () {
        var q = this.value.toLowerCase().trim();
        document.querySelectorAll('#users-tbody .user-row').forEach(function (row) {
            var name     = (row.querySelector('.user-name')?.textContent     || '').toLowerCase();
            var username = (row.querySelector('.user-username')?.textContent || '').toLowerCase();
            var empid    = (row.querySelector('.user-empid')?.textContent    || '').toLowerCase();
            row.style.display = (!q || name.includes(q) || username.includes(q) || empid.includes(q)) ? '' : 'none';
        });
    });
});