<?php
$pageTitle   = 'User Management';
$currentPage = 'user-mgt';
require_once __DIR__ . '/../../src/includes/init.php';

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
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
    </svg>
    <?= htmlspecialchars($success) ?>
</div>
<?php endif; ?>
<?php if ($error): ?>
<div data-flash class="mb-5 flex items-center gap-3 bg-red-50 border border-red-200 text-red-800 text-sm font-bold rounded-xl px-5 py-3.5 shadow-sm">
    <svg class="w-5 h-5 text-red-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
    </svg>
    <?= htmlspecialchars($error) ?>
</div>
<?php endif; ?>

<!-- Page Header -->
<div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-2">
    <div>
        <h1 class="text-1xl font-black text-slate-800 uppercase tracking-wide">User Management</h1>
    </div>

    <div class="flex items-center gap-4">
        <p class="text-sm text-slate-500 m-0">
            <span class="font-bold text-slate-700"><?= count($users) ?></span>
            <?= count($users) !== 1 ? '' : '' ?> Users
        </p>

        <button onclick="openModal('modal-add-user')"
            class="inline-flex items-center gap-2 bg-[#ce1126] hover:bg-red-700 active:bg-red-800
                   text-white text-xs font-black uppercase tracking-widest
                   px-4 py-2 rounded-xl shadow-md shadow-slate-200 hover:shadow-md transition-all">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/>
            </svg>
            Add
        </button>
    </div>
</div>

<!-- Search Bar -->
<div class="mb-4">
    <div class="relative max-w-full">
        <svg class="absolute left-3.5 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400 pointer-events-none"
             fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                d="M21 21l-4.35-4.35M17 11A6 6 0 115 11a6 6 0 0112 0z"/>
        </svg>
        <input type="text" id="search-input"
               placeholder="Search by name, username, or ID"
               class="w-full pl-10 pr-4 py-1.5 border-2 border-slate-100 focus:border-slate-300 rounded-xl
                      placeholder:text-slate-300 text-sm font-medium text-slate-700 outline-none bg-white transition-all">
    </div>
</div>

<!-- Users Table -->
<div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead>
                <tr class="bg-[#ce2216] border-b border-slate-200">
                    <th class="text-left text-xs font-black text-white uppercase tracking-widest px-6 py-2">ID</th>
                    <th class="text-left text-xs font-black text-white uppercase tracking-widest px-6 py-2">Name</th>
                    <th class="text-left text-xs font-black text-white uppercase tracking-widest px-6 py-2">Username</th>
                    <th class="text-left text-xs font-black text-white uppercase tracking-widest px-9 py-2">Role</th>
                    <th class="text-left text-xs font-black text-white uppercase tracking-widest px-8 py-2">Status</th>
                    <th class="text-left text-xs font-black text-white uppercase tracking-widest px-6 py-2">Last Login</th>
                    <th class="text-center text-xs font-black text-white uppercase tracking-widest px-6 py-2">Actions</th>
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
                <?php foreach ($users as $u):
                    $isMe     = (int)$u['id'] === (int)$_SESSION['user_id'];
                    $mid      = !empty($u['middle_name']) ? ' ' . $u['middle_name'] . ' ' : ' ';
                    $fullName = htmlspecialchars($u['first_name'] . $mid . $u['last_name']);
                    $initials = strtoupper(substr($u['first_name'], 0, 1) . substr($u['last_name'], 0, 1));
                    $isActive = ($u['status'] ?? 'ACTIVE') === 'ACTIVE';
                ?>
                <tr class="hover:bg-slate-50/70 transition-colors user-row <?= !$isActive ? 'opacity-60' : '' ?>">

                    <td class="px-6 py-0 font-mono text-xs font-bold text-slate-500 user-empid">
                        <?= htmlspecialchars((string)$u['id']) ?>
                    </td>

                    <td class="px-6 py-0">
                        <div class="flex items-center gap-3">
                            <div>
                                <p class="font-bold uppercase text-slate-800 user-name"><?= $fullName ?></p>
                                <?php if ($isMe): ?>
                                <span class="text-[10px] font-bold text-red-500 uppercase tracking-wide">You</span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </td>

                    <td class="px-6 py-0 font-mono text-xs font-bold text-slate-600 user-username">
                        <?= htmlspecialchars($u['username']) ?>
                    </td>

                    <td class="px-6 py-0">
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

                    <td class="px-6 py-0">
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

                    <td class="px-6 py-0 text-slate-500 text-xs font-medium">
                        <?= $u['last_login']
                            ? date('M j, Y g:i A', strtotime($u['last_login']))
                            : '<span class="text-slate-300 font-bold">Never</span>' ?>
                    </td>

                    <td class="px-6 py-0">
                        <div class="flex items-center justify-end gap-1">
                            <button
                                onclick="openEditModal(<?= htmlspecialchars(json_encode([
                                    'id'          => $u['id'],
                                    'first_name'  => $u['first_name'],
                                    'middle_name' => $u['middle_name'] ?? '',
                                    'last_name'   => $u['last_name'],
                                    'user_type'   => $u['user_type'],
                                ]), ENT_QUOTES) ?>)"
                                class="p-2 text-slate-400 hover:text-red-600 hover:bg-red-50 rounded-lg transition-all"
                                title="Edit User">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                </svg>
                            </button>
                            <button
                                onclick="confirmReset(<?= (int)$u['id'] ?>, '<?= htmlspecialchars($u['username'], ENT_QUOTES) ?>')"
                                class="p-2 text-slate-400 hover:text-red-600 hover:bg-amber-50 rounded-lg transition-all"
                                title="Reset Password">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"/>
                                </svg>
                            </button>
                            <?php if (!$isMe): ?>
                            <button
                                onclick="confirmToggleStatus(<?= (int)$u['id'] ?>, '<?= htmlspecialchars($fullName, ENT_QUOTES) ?>', '<?= $u['status'] ?>')"
                                class="p-2 rounded-lg transition-all <?= $isActive
                                    ? 'text-slate-400 hover:text-red-600 hover:bg-red-50'
                                    : 'text-slate-400 hover:text-red-600 hover:bg-red-50' ?>"
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

<?php
require_once __DIR__ . '/../../src/includes/modals/user-add.php';
require_once __DIR__ . '/../../src/includes/modals/user-edit.php';
require_once __DIR__ . '/../../src/includes/modals/user-reset-pw.php';
require_once __DIR__ . '/../../src/includes/modals/user-status.php';
?>

<script src="<?= ASSET_URL ?>js/main.js"></script>
<script src="<?= ASSET_URL ?>js/user-mgt.js"></script>