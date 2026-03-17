<?php
$noLayout  = true;
$pageTitle = 'Forgot Password';
require_once __DIR__ . '/../../src/includes/init.php';
$error   = $_SESSION['error']         ?? null;
$success = $_SESSION['flash_success'] ?? null;
unset($_SESSION['error'], $_SESSION['flash_success']);
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
            <h1 class="text-2xl font-black text-slate-800 uppercase tracking-tight">Reset Password</h1>
            <p class="text-xs text-slate-400 mt-2">Enter your username and your password will be reset.</p>
        </div>

        <?php if ($error): ?>
            <div class="mb-4 bg-red-50 border border-red-200 text-red-700 text-sm font-bold rounded-lg px-4 py-3">
                <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>
        <?php if ($success): ?>
            <div class="mb-4 bg-green-50 border border-green-200 text-green-700 text-sm font-bold rounded-lg px-4 py-3">
                <?= htmlspecialchars($success) ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="<?= BASE_URL ?>/public/actions/reset_password.php" class="space-y-5">
            <div>
                <label class="block text-xs font-bold text-slate-500 uppercase tracking-widest mb-1.5">Username</label>
                <input type="text" name="username" required autofocus
                    class="w-full border-2 border-slate-200 focus:border-red-600 rounded-lg px-4 py-3
                           text-sm font-bold text-slate-800 outline-none bg-slate-50 focus:bg-white transition-all">
            </div>
            <button type="submit"
                class="w-full bg-red-600 hover:bg-red-700 text-white font-black
                       py-3.5 rounded-lg text-sm uppercase tracking-widest transition-all shadow-lg hover:-translate-y-0.5">
                Reset Password
            </button>
        </form>
        <p class="mt-6 text-center text-xs text-slate-400">
            <a href="<?= BASE_URL ?>/public/login/"
               class="hover:text-red-600 font-bold transition-colors">Back to Login</a>
        </p>
    </div>
</body>
</html>
