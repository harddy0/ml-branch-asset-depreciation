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
    <style>
        /* Background video (professional, subtle) */
        /* Use a dark background so blurred video edges don't fade to white */
        .bg-video{position:absolute;inset:0;z-index:0;pointer-events:none;overflow:hidden;background:#ce2216}
        /* Slightly darker and more transparent video for subtlety */
        .bg-video video{position:absolute;left:50%;top:50%;width:100%;height:100%;object-fit:cover;transform:translate(-50%,-50%);filter:blur(10px) brightness(1.0);opacity:0.3}
        .bg-overlay{position:absolute;inset:0;z-index:1;background:linear-gradient(180deg, rgba(255,255,255,0.02), rgba(0, 0, 0, 0.02));pointer-events:none}
    </style>
 </head>
 <body class="min-h-screen bg-slate-100 flex flex-col items-center m-0">
    <div class="bg-video" aria-hidden="true">
        <video autoplay muted loop playsinline preload="auto">
            <source src="<?= BASE_URL ?>/public/assets/vid/moving2.mp4?v=2" type="video/mp4">
        </video>
        <div class="bg-overlay"></div>
    </div>

    <div name="logo-container" class="relative z-10 flex w-full items-center justify-start bg-white shadow-slate-100 shadow-sm border-b border-slate-200 -mt-6">
        <img src="<?= BASE_URL ?>/public/assets/img/ml-logo.png" alt="ML Logo" class="h-[0.3in] mx-6 my-4">
    </div>

    <div name="main-container" class="relative z-10 flex-1 flex items-center justify-center w-full">
        <div name="center-container" class="flex flex-col items-center p-20 pl-5 pr-5 bg-white rounded-2xl shadow-xl border border-slate-200 max-w-250px w-1/2">
            <div name="title" class="flex w-full items-center justify-center">
                <h1 class="text-3xl text-center font-black text-slate-800 uppercase tracking-wide mb-10 whitespace-nowrap">
                    ML Asset Management System
                </h1>
            </div>
            
            <div name="login-btn" class="flex w-1/2">
                <?php if ($auth->isLoggedIn()): ?>
                <a href="<?= BASE_URL ?>/public/dashboard/" 
                class="block w-full bg-red-600 hover:bg-red-700 active:bg-red-800 text-white font-black py-4 rounded-lg text-sm uppercase tracking-widest transition-all shadow-lg shadow-red-200 hover:shadow-xl hover:-translate-y-0.5">
                    Go to Dashboard
                </a>

                <?php else: ?>
                    <a href="<?= BASE_URL ?>/public/login/" 
                    class="block w-full bg-red-600 hover:bg-red-700 active:bg-red-800 text-white font-black py-4 rounded-lg text-sm uppercase tracking-widest transition-all shadow-lg shadow-slate-200 text-center hover:shadow-xl hover:-translate-y-0.5">
                        Login
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>