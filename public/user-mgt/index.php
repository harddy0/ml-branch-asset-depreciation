<?php
$pageTitle   = 'User Management';
$currentPage = 'user-mgt';
require_once __DIR__ . '/../../src/includes/init.php';

// ADMIN ONLY
if (!$auth->isAdmin()) {
    header('Location: ' . BASE_URL . '/public/dashboard/');
    exit;
}

$success = $_SESSION['flash_success'] ?? null;
$error   = $_SESSION['flash_error']   ?? null;
unset($_SESSION['flash_success'], $_SESSION['flash_error']);

$users = $auth->getAllUsers();
?>

<!-- Flash Messages -->
<?php if ($success): ?>
<div data-flash class="mb-5 flex items-center gap-3 bg-green-50 border border-green-200 text-green-800 text-sm font-bold rounded-xl px-5 py-3.5 shadow-sm">
    <svg class="w-5 h-5 text-green-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
            d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
    </svg>
    <?= htmlspecialchars($success) ?>
</div>
<?php endif; ?>
<?php if ($error): ?>
<div data-flash class="mb-5 flex items-center gap-3 bg-red-50 border border-red-200 text-red-800 text-sm font-bold rounded-xl px-5 py-3.5 shadow-sm">
    <svg class="w-5 h-5 text-red-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
            d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
    </svg>
    <?= htmlspecialchars($error) ?>
</div>
<?php endif; ?>

<!-- Page Header -->
<div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
    <div>
        <h1 class="text-2xl font-black text-slate-800 uppercase tracking-tight">User Management</h1>
        <p class="text-sm text-slate-500 mt-1">
            Manage system users —
            <span class="font-bold text-slate-700"><?= count($users) ?></span>
            user<?= count($users) !== 1 ? 's' : '' ?> registered
        </p>
    </div>
    <button onclick="openModal('modal-add-user')"
        class="inline-flex items-center gap-2 bg-[#ce1126] hover:bg-red-700 active:bg-red-800
               text-white text-xs font-black uppercase tracking-widest
               px-5 py-3 rounded-xl shadow-lg shadow-red-200 hover:shadow-xl hover:-translate-y-0.5 transition-all">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/>
        </svg>
        Add User
    </button>
</div>

<!-- Search Bar -->
<div class="mb-4">
    <div class="relative max-w-sm">
        <svg class="absolute left-3.5 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400 pointer-events-none"
             fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                d="M21 21l-4.35-4.35M17 11A6 6 0 115 11a6 6 0 0112 0z"/>
        </svg>
        <input type="text" id="search-input"
               placeholder="Search by name, username, or ID..."
               class="w-full pl-10 pr-4 py-2.5 border-2 border-slate-200 focus:border-red-500 rounded-xl
                      text-sm font-medium text-slate-700 outline-none bg-white transition-all">
    </div>
</div>

<!-- Users Table -->
<div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead>
                <tr class="bg-slate-50 border-b border-slate-200">
                    <th class="text-left text-xs font-black text-slate-500 uppercase tracking-widest px-6 py-4">ID</th>
                    <th class="text-left text-xs font-black text-slate-500 uppercase tracking-widest px-6 py-4">Name</th>
                    <th class="text-left text-xs font-black text-slate-500 uppercase tracking-widest px-6 py-4">Username</th>
                    <th class="text-left text-xs font-black text-slate-500 uppercase tracking-widest px-6 py-4">Role</th>
                    <th class="text-left text-xs font-black text-slate-500 uppercase tracking-widest px-6 py-4">Status</th>
                    <th class="text-left text-xs font-black text-slate-500 uppercase tracking-widest px-6 py-4">Last Login</th>
                    <th class="text-right text-xs font-black text-slate-500 uppercase tracking-widest px-6 py-4">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100" id="users-tbody">
                <?php if (empty($users)): ?>
                <tr>
                    <td colspan="7" class="text-center text-slate-400 font-bold py-16 text-sm">
                        No users found. Add one to get started.
                    </td>
                </tr>
                <?php else: ?>
                <?php foreach ($users as $u): ?>
                <?php
                    $isMe     = (int)$u['id'] === (int)$_SESSION['user_id'];
                    $mid      = !empty($u['middle_name']) ? ' ' . $u['middle_name'] . ' ' : ' ';
                    $fullName = htmlspecialchars($u['first_name'] . $mid . $u['last_name']);
                    $initials = strtoupper(substr($u['first_name'], 0, 1) . substr($u['last_name'], 0, 1));
                    $isActive = ($u['status'] ?? 'ACTIVE') === 'ACTIVE';
                ?>
                <tr class="hover:bg-slate-50/70 transition-colors user-row <?= !$isActive ? 'opacity-60' : '' ?>">

                    <!-- ID -->
                    <td class="px-6 py-4 font-mono text-xs font-bold text-slate-500 user-empid">
                        <?= htmlspecialchars((string)$u['id']) ?>
                    </td>

                    <!-- Name -->
                    <td class="px-6 py-4">
                        <div class="flex items-center gap-3">
                            <div class="w-9 h-9 rounded-full <?= $isActive ? 'bg-red-100 text-red-700' : 'bg-slate-100 text-slate-400' ?> flex items-center justify-center font-black text-xs shrink-0">
                                <?= $initials ?>
                            </div>
                            <div>
                                <p class="font-bold text-slate-800 user-name"><?= $fullName ?></p>
                                <?php if ($isMe): ?>
                                <span class="text-[10px] font-bold text-red-500 uppercase tracking-wide">You</span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </td>

                    <!-- Username -->
                    <td class="px-6 py-4 font-mono text-xs font-bold text-slate-600 user-username">
                        <?= htmlspecialchars($u['username']) ?>
                    </td>

                    <!-- Role -->
                    <td class="px-6 py-4">
                        <?php if ($u['user_type'] === 'ADMIN'): ?>
                        <span class="inline-flex items-center gap-1.5 bg-red-100 text-red-700 text-[10px] font-black uppercase tracking-widest px-2.5 py-1 rounded-full">
                            <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 1.944A11.954 11.954 0 012.166 5C2.056 5.649 2 6.319 2 7c0 5.225 3.34 9.67 8 11.317C14.66 16.67 18 12.225 18 7c0-.682-.057-1.35-.166-2.001A11.954 11.954 0 0110 1.944zM11 14a1 1 0 11-2 0 1 1 0 012 0zm0-7a1 1 0 10-2 0v3a1 1 0 102 0V7z" clip-rule="evenodd"/>
                            </svg>
                            Admin
                        </span>
                        <?php else: ?>
                        <span class="inline-flex items-center gap-1.5 bg-slate-100 text-slate-600 text-[10px] font-black uppercase tracking-widest px-2.5 py-1 rounded-full">
                            <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z" clip-rule="evenodd"/>
                            </svg>
                            User
                        </span>
                        <?php endif; ?>
                    </td>

                    <!-- Status -->
                    <td class="px-6 py-4">
                        <?php if ($isActive): ?>
                        <span class="inline-flex items-center gap-1.5 bg-green-100 text-green-700 text-[10px] font-black uppercase tracking-widest px-2.5 py-1 rounded-full">
                            <span class="w-1.5 h-1.5 rounded-full bg-green-500 inline-block"></span>
                            Active
                        </span>
                        <?php else: ?>
                        <span class="inline-flex items-center gap-1.5 bg-orange-100 text-orange-700 text-[10px] font-black uppercase tracking-widest px-2.5 py-1 rounded-full">
                            <span class="w-1.5 h-1.5 rounded-full bg-orange-500 inline-block"></span>
                            Restricted
                        </span>
                        <?php endif; ?>
                    </td>

                    <!-- Last Login -->
                    <td class="px-6 py-4 text-slate-500 text-xs font-medium">
                        <?= $u['last_login']
                            ? date('M j, Y g:i A', strtotime($u['last_login']))
                            : '<span class="text-slate-300 font-bold">Never</span>' ?>
                    </td>

                    <!-- Actions -->
                    <td class="px-6 py-4">
                        <div class="flex items-center justify-end gap-1">

                            <!-- Edit -->
                            <button
                                onclick="openEditModal(<?= htmlspecialchars(json_encode([
                                    'id'          => $u['id'],
                                    'first_name'  => $u['first_name'],
                                    'middle_name' => $u['middle_name'] ?? '',
                                    'last_name'   => $u['last_name'],
                                    'user_type'   => $u['user_type'],
                                ]), ENT_QUOTES) ?>)"
                                class="p-2 text-slate-400 hover:text-blue-600 hover:bg-blue-50 rounded-lg transition-all"
                                title="Edit User">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                </svg>
                            </button>

                            <!-- Reset Password -->
                            <button
                                onclick="confirmReset(<?= (int)$u['id'] ?>, '<?= htmlspecialchars($u['username'], ENT_QUOTES) ?>')"
                                class="p-2 text-slate-400 hover:text-amber-600 hover:bg-amber-50 rounded-lg transition-all"
                                title="Reset Password">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"/>
                                </svg>
                            </button>

                            <!-- Restrict / Activate (not for self) -->
                            <?php if (!$isMe): ?>
                            <button
                                onclick="confirmToggleStatus(<?= (int)$u['id'] ?>, '<?= htmlspecialchars($fullName, ENT_QUOTES) ?>', '<?= $u['status'] ?>')"
                                class="p-2 rounded-lg transition-all <?= $isActive
                                    ? 'text-slate-400 hover:text-orange-600 hover:bg-orange-50'
                                    : 'text-slate-400 hover:text-green-600 hover:bg-green-50' ?>"
                                title="<?= $isActive ? 'Restrict User' : 'Activate User' ?>">
                                <?php if ($isActive): ?>
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/>
                                </svg>
                                <?php else: ?>
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                                <?php endif; ?>
                            </button>
                            <?php endif; ?>

                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>


<!-- ============================================================
     MODAL: Add User
============================================================ -->
<div id="modal-add-user"
     class="hidden fixed inset-0 z-50 items-center justify-center bg-black/40 backdrop-blur-sm p-4">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-lg animate-fadeIn">

        <div class="flex items-center justify-between px-7 py-5 border-b border-slate-100">
            <div>
                <h2 class="text-base font-black text-slate-800 uppercase tracking-tight">Add New User</h2>
                <p class="text-xs text-slate-400 mt-0.5">
                    Default password: <code class="bg-slate-100 px-1.5 py-0.5 rounded font-mono text-slate-600 text-[11px]">Mlinc1234@</code>
                </p>
            </div>
            <button onclick="closeModal('modal-add-user')"
                class="p-2 hover:bg-slate-100 rounded-lg transition-colors">
                <svg class="w-5 h-5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>

        <form method="POST" action="<?= BASE_URL ?>/public/actions/user_store.php"
              class="px-7 py-6 space-y-4">

            <!-- Employee ID -->
            <div>
                <label class="block text-xs font-black text-slate-500 uppercase tracking-widest mb-1.5">
                    Employee ID <span class="text-red-500">*</span>
                </label>
                <input type="number" name="id" id="add-emp-id" required min="1"
                    class="w-full border-2 border-slate-200 focus:border-red-500 rounded-xl px-4 py-2.5
                           text-sm font-bold font-mono text-slate-800 outline-none bg-slate-50 focus:bg-white transition-all">
            </div>

            <!-- Name row -->
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-black text-slate-500 uppercase tracking-widest mb-1.5">
                        First Name <span class="text-red-500">*</span>
                    </label>
                    <input type="text" name="first_name" id="add-first-name" required
                        class="input-uppercase w-full border-2 border-slate-200 focus:border-red-500 rounded-xl px-4 py-2.5
                               text-sm font-bold text-slate-800 outline-none bg-slate-50 focus:bg-white transition-all">
                </div>
                <div>
                    <label class="block text-xs font-black text-slate-500 uppercase tracking-widest mb-1.5">
                        Last Name <span class="text-red-500">*</span>
                    </label>
                    <input type="text" name="last_name" id="add-last-name" required
                        class="input-uppercase w-full border-2 border-slate-200 focus:border-red-500 rounded-xl px-4 py-2.5
                               text-sm font-bold text-slate-800 outline-none bg-slate-50 focus:bg-white transition-all">
                </div>
            </div>

            <!-- Middle Name -->
            <div>
                <label class="block text-xs font-black text-slate-500 uppercase tracking-widest mb-1.5">
                    Middle Name
                    <span class="normal-case tracking-normal font-medium text-slate-300">(optional)</span>
                </label>
                <input type="text" name="middle_name" id="add-middle-name"
                    class="input-uppercase w-full border-2 border-slate-200 focus:border-red-500 rounded-xl px-4 py-2.5
                           text-sm font-bold text-slate-800 outline-none bg-slate-50 focus:bg-white transition-all">
            </div>

            <!-- Auto-generated Username preview -->
            <div>
                <label class="block text-xs font-black text-slate-500 uppercase tracking-widest mb-1.5">
                    Username <span class="text-[10px] normal-case font-medium text-slate-400">(auto-generated)</span>
                </label>
                <input type="text" id="add-username-preview" readonly tabindex="-1"
                    placeholder="Fill in Last Name and ID above..."
                    class="w-full border-2 border-slate-100 rounded-xl px-4 py-2.5 bg-slate-50
                           text-sm font-bold font-mono text-slate-500 outline-none cursor-not-allowed">
                <p class="text-[10px] text-slate-400 mt-1">First 4 characters of last name + Employee ID.</p>
            </div>

            <!-- Role -->
            <div>
                <label class="block text-xs font-black text-slate-500 uppercase tracking-widest mb-1.5">
                    Role <span class="text-red-500">*</span>
                </label>
                <select name="user_type" required
                    class="w-full border-2 border-slate-200 focus:border-red-500 rounded-xl px-4 py-2.5
                           text-sm font-bold text-slate-800 outline-none bg-slate-50 focus:bg-white transition-all">
                    <option value="USER">User</option>
                    <option value="ADMIN">Admin</option>
                </select>
            </div>

            <div class="flex gap-3 pt-2">
                <button type="button" onclick="closeModal('modal-add-user')"
                    class="flex-1 border-2 border-slate-200 text-slate-600 hover:border-slate-300 hover:bg-slate-50
                           font-black text-xs uppercase tracking-widest py-3 rounded-xl transition-all">
                    Cancel
                </button>
                <button type="submit"
                    class="flex-1 bg-[#ce1126] hover:bg-red-700 text-white font-black text-xs uppercase tracking-widest
                           py-3 rounded-xl shadow-lg shadow-red-100 hover:-translate-y-0.5 transition-all">
                    Create User
                </button>
            </div>
        </form>
    </div>
</div>


<!-- ============================================================
     MODAL: Edit User
============================================================ -->
<div id="modal-edit-user"
     class="hidden fixed inset-0 z-50 items-center justify-center bg-black/40 backdrop-blur-sm p-4">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-lg animate-fadeIn">

        <div class="flex items-center justify-between px-7 py-5 border-b border-slate-100">
            <h2 class="text-base font-black text-slate-800 uppercase tracking-tight">Edit User</h2>
            <button onclick="closeModal('modal-edit-user')"
                class="p-2 hover:bg-slate-100 rounded-lg transition-colors">
                <svg class="w-5 h-5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>

        <form method="POST" action="<?= BASE_URL ?>/public/actions/user_update.php"
              class="px-7 py-6 space-y-4">
            <input type="hidden" name="id" id="edit-id">

            <!-- Name row -->
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-black text-slate-500 uppercase tracking-widest mb-1.5">
                        First Name <span class="text-red-500">*</span>
                    </label>
                    <input type="text" name="first_name" id="edit-first-name" required
                        class="input-uppercase w-full border-2 border-slate-200 focus:border-red-500 rounded-xl px-4 py-2.5
                               text-sm font-bold text-slate-800 outline-none bg-slate-50 focus:bg-white transition-all">
                </div>
                <div>
                    <label class="block text-xs font-black text-slate-500 uppercase tracking-widest mb-1.5">
                        Last Name <span class="text-red-500">*</span>
                    </label>
                    <input type="text" name="last_name" id="edit-last-name" required
                        class="input-uppercase w-full border-2 border-slate-200 focus:border-red-500 rounded-xl px-4 py-2.5
                               text-sm font-bold text-slate-800 outline-none bg-slate-50 focus:bg-white transition-all">
                </div>
            </div>

            <!-- Middle Name -->
            <div>
                <label class="block text-xs font-black text-slate-500 uppercase tracking-widest mb-1.5">
                    Middle Name
                    <span class="normal-case tracking-normal font-medium text-slate-300">(optional)</span>
                </label>
                <input type="text" name="middle_name" id="edit-middle-name"
                    class="input-uppercase w-full border-2 border-slate-200 focus:border-red-500 rounded-xl px-4 py-2.5
                           text-sm font-bold text-slate-800 outline-none bg-slate-50 focus:bg-white transition-all">
            </div>

            <!-- Username Preview (read-only) -->
            <div>
                <label class="block text-xs font-black text-slate-500 uppercase tracking-widest mb-1.5">
                    Username <span class="text-[10px] normal-case font-medium text-slate-400">(auto-generated)</span>
                </label>
                <input type="text" id="edit-username-preview" readonly tabindex="-1"
                    class="w-full border-2 border-slate-100 rounded-xl px-4 py-2.5 bg-slate-50
                           text-sm font-bold font-mono text-slate-500 outline-none cursor-not-allowed">
            </div>

            <!-- Role -->
            <div>
                <label class="block text-xs font-black text-slate-500 uppercase tracking-widest mb-1.5">
                    Role <span class="text-red-500">*</span>
                </label>
                <select name="user_type" id="edit-user-type" required
                    class="w-full border-2 border-slate-200 focus:border-red-500 rounded-xl px-4 py-2.5
                           text-sm font-bold text-slate-800 outline-none bg-slate-50 focus:bg-white transition-all">
                    <option value="USER">User</option>
                    <option value="ADMIN">Admin</option>
                </select>
            </div>

            <div class="flex gap-3 pt-2">
                <button type="button" onclick="closeModal('modal-edit-user')"
                    class="flex-1 border-2 border-slate-200 text-slate-600 hover:border-slate-300 hover:bg-slate-50
                           font-black text-xs uppercase tracking-widest py-3 rounded-xl transition-all">
                    Cancel
                </button>
                <button type="submit"
                    class="flex-1 bg-blue-600 hover:bg-blue-700 text-white font-black text-xs uppercase tracking-widest
                           py-3 rounded-xl shadow-lg shadow-blue-100 hover:-translate-y-0.5 transition-all">
                    Save Changes
                </button>
            </div>
        </form>
    </div>
</div>


<!-- ============================================================
     MODAL: Confirm Reset Password
============================================================ -->
<div id="modal-reset-pw"
     class="hidden fixed inset-0 z-50 items-center justify-center bg-black/40 backdrop-blur-sm p-4">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md animate-fadeIn">

        <div class="px-7 py-6 text-center">
            <div class="w-14 h-14 bg-amber-100 rounded-full flex items-center justify-center mx-auto mb-4">
                <svg class="w-7 h-7 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"/>
                </svg>
            </div>
            <h3 class="text-base font-black text-slate-800 uppercase tracking-tight mb-2">Reset Password?</h3>
            <p class="text-sm text-slate-500 mb-1">This will reset the password for</p>
            <p class="text-sm font-black text-slate-800 mb-3" id="reset-username-display"></p>
            <p class="text-xs text-slate-400 leading-relaxed">
                Password will be reset to
                <code class="bg-slate-100 px-1.5 py-0.5 rounded font-mono text-slate-600">Mlinc1234@</code>.
                The user will be required to change it on next login.
            </p>
        </div>

        <form method="POST" action="<?= BASE_URL ?>/public/actions/user_reset_password.php"
              class="px-7 pb-7 flex gap-3">
            <input type="hidden" name="id" id="reset-user-id">
            <button type="button" onclick="closeModal('modal-reset-pw')"
                class="flex-1 border-2 border-slate-200 text-slate-600 font-black text-xs uppercase tracking-widest
                       py-3 rounded-xl transition-all hover:bg-slate-50">
                Cancel
            </button>
            <button type="submit"
                class="flex-1 bg-amber-500 hover:bg-amber-600 text-white font-black text-xs uppercase tracking-widest
                       py-3 rounded-xl shadow-lg shadow-amber-100 hover:-translate-y-0.5 transition-all">
                Yes, Reset
            </button>
        </form>
    </div>
</div>


<!-- ============================================================
     MODAL: Confirm Restrict / Activate
============================================================ -->
<div id="modal-status"
     class="hidden fixed inset-0 z-50 items-center justify-center bg-black/40 backdrop-blur-sm p-4">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md animate-fadeIn">

        <div class="px-7 py-6 text-center">
            <div class="w-14 h-14 bg-orange-100 rounded-full flex items-center justify-center mx-auto mb-4">
                <svg class="w-7 h-7 text-orange-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/>
                </svg>
            </div>
            <h3 class="text-base font-black text-slate-800 uppercase tracking-tight mb-2" id="status-modal-title">Change Status?</h3>
            <p class="text-sm text-slate-500 mb-1" id="status-modal-desc"></p>
            <p class="text-sm font-black text-slate-800 mt-1" id="status-name-display"></p>
        </div>

        <form method="POST" action="<?= BASE_URL ?>/public/actions/user_set_status.php"
              class="px-7 pb-7 flex gap-3">
            <input type="hidden" name="id"     id="status-user-id">
            <input type="hidden" name="status" id="status-target">
            <button type="button" onclick="closeModal('modal-status')"
                class="flex-1 border-2 border-slate-200 text-slate-600 font-black text-xs uppercase tracking-widest
                       py-3 rounded-xl transition-all hover:bg-slate-50">
                Cancel
            </button>
            <button type="submit" id="status-confirm-btn"
                class="flex-1 text-white font-black text-xs uppercase tracking-widest
                       py-3 rounded-xl shadow-lg hover:-translate-y-0.5 transition-all bg-orange-500 hover:bg-orange-600 shadow-orange-100">
                <span id="status-action-label">Confirm</span>
            </button>
        </form>
    </div>
</div>


<!-- main.js first, then page-specific JS -->
<script src="<?= ASSET_URL ?>js/main.js"></script>
<script src="<?= ASSET_URL ?>js/user-mgt.js"></script>