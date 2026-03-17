<?php
$noLayout  = true;
$pageTitle = 'Change Password';
require_once __DIR__ . '/../../src/includes/init.php';
$error = $_SESSION['cp_error'] ?? null;
unset($_SESSION['cp_error']);
?>
<!DOCTYPE html>
<html lang="en" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $pageTitle ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="h-full bg-slate-100 flex items-center justify-center">
    <div class="w-full max-w-md bg-white rounded-2xl shadow-xl border border-slate-200 p-10">
        <div class="text-center mb-8">
            <div class="w-12 h-12 bg-red-600 rounded-xl flex items-center justify-center mx-auto mb-4">
                <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                </svg>
            </div>
            <h1 class="text-2xl font-black text-slate-800 uppercase tracking-tight">Set New Password</h1>
            <p class="text-xs text-slate-400 mt-2">You must change your password before continuing.</p>
        </div>

        <?php if ($error): ?>
            <div class="mb-6 bg-red-50 border border-red-200 text-red-700 text-sm font-bold rounded-lg px-4 py-3">
                <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="<?= BASE_URL ?>/public/actions/update_password.php" class="space-y-5">
            <div>
                <label class="block text-xs font-bold text-slate-500 uppercase tracking-widest mb-1.5">New Password</label>
                <input type="password" name="new_password" id="new_pw" required minlength="8"
                    class="w-full border-2 border-slate-200 focus:border-red-600 rounded-lg px-4 py-3
                           text-sm font-bold text-slate-800 outline-none bg-slate-50 focus:bg-white transition-all">
            </div>
            <div>
                <label class="block text-xs font-bold text-slate-500 uppercase tracking-widest mb-1.5">Confirm Password</label>
                <input type="password" name="confirm_password" id="confirm_pw" required minlength="8"
                    class="w-full border-2 border-slate-200 focus:border-red-600 rounded-lg px-4 py-3
                           text-sm font-bold text-slate-800 outline-none bg-slate-50 focus:bg-white transition-all">
                <p id="matchHint" class="text-xs font-bold mt-1 hidden"></p>
            </div>
            <button type="submit"
                class="w-full bg-red-600 hover:bg-red-700 text-white font-black
                       py-3.5 rounded-lg text-sm uppercase tracking-widest transition-all shadow-lg hover:-translate-y-0.5">
                Update Password
            </button>
        </form>
        <div class="mt-6 text-center">
            <a href="<?= BASE_URL ?>/public/actions/logout.php"
               class="text-xs font-bold text-slate-400 hover:text-red-600 uppercase tracking-wider transition-colors">
                Cancel &amp; Logout
            </a>
        </div>
    </div>
    <script>
    const np = document.getElementById('new_pw');
    const cp = document.getElementById('confirm_pw');
    const hint = document.getElementById('matchHint');
    cp.addEventListener('input', () => {
        if (!cp.value) { hint.classList.add('hidden'); return; }
        hint.classList.remove('hidden');
        if (np.value === cp.value) {
            hint.textContent = '✓ Passwords match';
            hint.className = 'text-xs font-bold mt-1 text-green-600';
        } else {
            hint.textContent = '✗ Passwords do not match';
            hint.className = 'text-xs font-bold mt-1 text-red-500';
        }
    });
    </script>
</body>
</html>
