<?php
$noLayout = true;
$pageTitle = 'Welcome';
require_once __DIR__ . '/../src/includes/init.php';
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
    <div class="text-center p-10 bg-white rounded-2xl shadow-xl border border-slate-200 max-w-lg w-full mx-4">
        
        <div class="w-16 h-16 bg-red-600 rounded-xl flex items-center justify-center mx-auto mb-6 shadow-lg shadow-red-200">
            <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M13 10V3L4 14h7v7l9-11h-7z"/>
            </svg>
        </div>
        
        <h1 class="text-3xl font-black text-slate-800 uppercase tracking-tight mb-3">
            My App
        </h1>
        <p class="text-slate-500 mb-8 text-sm leading-relaxed">
            Welcome to the Asset Depreciation Management system. Please log in to securely access your dashboard and manage your data.
        </p>
        
        <?php if ($auth->isLoggedIn()): ?>
            <a href="<?= BASE_URL ?>/public/dashboard/" 
               class="block w-full bg-red-600 hover:bg-red-700 active:bg-red-800 text-white font-black py-4 rounded-lg text-sm uppercase tracking-widest transition-all shadow-lg shadow-red-200 hover:shadow-xl hover:-translate-y-0.5">
                Go to Dashboard
            </a>
        <?php else: ?>
            <a href="<?= BASE_URL ?>/public/login/" 
               class="block w-full bg-red-600 hover:bg-red-700 active:bg-red-800 text-white font-black py-4 rounded-lg text-sm uppercase tracking-widest transition-all shadow-lg shadow-red-200 hover:shadow-xl hover:-translate-y-0.5">
                Login to Continue
            </a>
        <?php endif; ?>

    </div>
</body>
</html>