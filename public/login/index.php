<?php
$noLayout  = true;
$pageTitle = 'Login';
require_once __DIR__ . '/../../src/includes/init.php';
if ($auth->isLoggedIn()) { header('Location: ' . BASE_URL . '/public/dashboard/'); exit; }
$error = $_SESSION['error'] ?? null;
unset($_SESSION['error']);
?>
<!DOCTYPE html>
<html lang="en" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $pageTitle ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        /* Background video (match landing page) */
        .bg-video{position:absolute;inset:0;z-index:0;pointer-events:none;overflow:hidden;background:#ce2216}
        .bg-video video{position:absolute;left:50%;top:50%;width:100%;height:100%;object-fit:cover;transform:translate(-50%,-50%);filter:blur(10px) brightness(1.0);opacity:0.3}
        .bg-overlay{position:absolute;inset:0;z-index:1;background:linear-gradient(180deg, rgba(255,255,255,0.02), rgba(0,0,0,0.02));pointer-events:none}
    </style>
</head>
<body class="h-full bg-slate-100 flex items-center justify-center m-0">
    <div class="bg-video" aria-hidden="true">
        <video autoplay muted loop playsinline preload="auto">
            <source src="<?= BASE_URL ?>/public/assets/vid/moving2.mp4?v=2" type="video/mp4">
        </video>
        <div class="bg-overlay"></div>
    </div>

    <div class="w-full max-w-md bg-white rounded-2xl shadow-xl border border-slate-200 p-10 relative z-10">
        <div class="text-center mb-8">
            <div class="w-12 h-12 bg-red-600 rounded-xl flex items-center justify-center mx-auto mb-4">
                <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                </svg>
            </div>
            <h1 class="text-2xl font-black text-slate-800 uppercase tracking-tight">Sign In</h1>
        </div>

        <?php if ($error): ?>
            <div class="mb-6 bg-red-50 border border-red-200 text-red-700 text-sm font-bold rounded-lg px-4 py-3">
                <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="<?= BASE_URL ?>/public/actions/login.php" class="space-y-5">
            <div>
                <label class="block text-xs font-bold text-slate-500 uppercase tracking-widest mb-1.5">Username</label>
                <input type="text" name="username" required autofocus
                    class="w-full border-2 border-slate-200 focus:border-red-600 rounded-lg px-4 py-3
                           text-sm font-bold text-slate-800 outline-none bg-slate-50 focus:bg-white transition-all">
            </div>
            <div>
                <label class="block text-xs font-bold text-slate-500 uppercase tracking-widest mb-1.5">Password</label>
                <input type="password" name="password" required
                    class="w-full border-2 border-slate-200 focus:border-red-600 rounded-lg px-4 py-3
                           text-sm font-bold text-slate-800 outline-none bg-slate-50 focus:bg-white transition-all">
            </div>
            <button type="submit"
                class="w-full bg-red-600 hover:bg-red-700 active:bg-red-800 text-white font-black
                       py-3.5 rounded-lg text-sm uppercase tracking-widest transition-all
                       shadow-lg shadow-red-200 hover:shadow-xl hover:-translate-y-0.5">
                Login
            </button>
        </form>

        <p class="mt-6 text-center text-xs text-slate-400">
            <a href="<?= BASE_URL ?>/public/forgot_password/"
               class="hover:text-red-600 font-bold transition-colors">Forgot password?</a>
        </p>
    </div>
</body>
</html>
