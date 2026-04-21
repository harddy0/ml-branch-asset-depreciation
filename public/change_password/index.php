<?php
// Set variable before anything else to prevent undefined variable warnings
$pageTitle = 'Change Password';
$noLayout  = true;

require_once __DIR__ . '/../../src/includes/init.php';

$error = $_SESSION['cp_error'] ?? null;
unset($_SESSION['cp_error']);
?>
<!DOCTYPE html>
<html lang="en" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $pageTitle ?? 'Change Password' ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        /* Video Background Styling */
        #bg-video {
            position: fixed;
            top: 50%;
            left: 50%;
            min-width: 100%;
            min-height: 100%;
            width: auto;
            height: auto;
            z-index: -1;
            transform: translate(-50%, -50%);
            object-fit: cover;
        }

        /* Subtle overlay to help form readability if video is too bright */
        .video-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(15, 23, 42, 0.5); /* dark slate with 30% opacity */
            z-index: 0;
        }

        .req-dot {
            flex: 0 0 auto;
        }
        .req-met { background: #16a34a; border-color: #16a34a; }
        .req-unmet { background: transparent; border-color: #cbd5e1; }
    </style>
</head>
<body class="h-full flex items-center justify-center overflow-hidden">

    <video autoplay muted loop playsinline id="bg-video">
        <source src="<?= BASE_URL ?>/assets/vid/moving2.mp4" type="video/mp4">
        Your browser does not support HTML5 video.
    </video>
    <div class="video-overlay"></div>

    <div class="relative z-10 w-full max-w-md bg-white/90 backdrop-blur-md rounded-2xl shadow-2xl border border-white/20 px-10 py-5">
        <div class="text-center mb-2">
            <h1 class="text-lg font-black text-slate-800 uppercase tracking-tight">Set New Password</h1>
        </div>

        <?php if ($error): ?>
            <div class="mb-6 bg-red-50 border-l-4 border-red-600 text-red-700 text-sm font-bold px-4 py-3 rounded shadow-sm">
                <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="<?= BASE_URL ?>/public/actions/update_password.php" class="space-y-6" id="pwForm">
            <div>
                <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-[0.2em] mb-2">New Password</label>
                <div class="relative">
                    <input type="password" name="new_password" id="new_pw" required minlength="10"
                        placeholder="••••••••"
                        class="w-full pr-12 border-2 border-slate-200 focus:border-[#e11d48] rounded-xl px-4 py-3.5
                               text-sm font-bold text-slate-800 outline-none bg-white transition-all shadow-sm">
                    <button type="button" id="toggle_new_pw" aria-label="Show password"
                        class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-700">
                        <svg id="icon_new_pw" xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.477 0 8.268 2.943 9.542 7-1.274 4.057-5.065 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                        </svg>
                    </button>
                </div>
            </div>

            <div>
                <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-[0.2em] mb-2">Password requirements</label>
                <ul id="pwRequirements" class="text-[10px] space-y-2 text-slate-500 ml-2">
                    <li id="req-length" class="flex items-start gap-2"><span class="req-dot w-3 h-3 rounded-full border req-unmet mt-1"></span>Minimum 10 characters</li>
                    <li id="req-upper" class="flex items-start gap-2"><span class="req-dot w-3 h-3 rounded-full border req-unmet mt-1"></span>One uppercase character</li>
                    <li id="req-lower" class="flex items-start gap-2"><span class="req-dot w-3 h-3 rounded-full border req-unmet mt-1"></span>One lowercase character</li>
                    <li id="req-number" class="flex items-start gap-2"><span class="req-dot w-3 h-3 rounded-full border req-unmet mt-1"></span>One number</li>
                    <li id="req-special" class="flex items-start gap-2"><span class="req-dot w-3 h-3 rounded-full border req-unmet mt-1"></span>One special character (e.g. !@#$%)</li>
                </ul>
            </div>

            <div>
                <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-[0.2em] mb-2">Confirm Password</label>
                <div class="relative">
                    <input type="password" name="confirm_password" id="confirm_pw" required minlength="10"
                        placeholder="••••••••"
                        class="w-full pr-12 border-2 border-slate-200 focus:border-[#e11d48] rounded-xl px-4 py-3.5
                               text-sm font-bold text-slate-800 outline-none bg-white transition-all shadow-sm">
                    <button type="button" id="toggle_confirm_pw" aria-label="Show confirm password"
                        class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-700">
                        <svg id="icon_confirm_pw" xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.477 0 8.268 2.943 9.542 7-1.274 4.057-5.065 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                        </svg>
                    </button>
                </div>
                <p id="matchHint" class="text-[10px] font-black mt-2 hidden"></p>
            </div>

            <button type="submit" id="submitBtn" disabled
                class="w-full bg-[#e11d48] disabled:opacity-50 disabled:cursor-not-allowed hover:bg-[#be123c] text-white font-black
                       py-4 rounded-xl text-xs uppercase tracking-[0.2em] transition-all shadow-xl shadow-red-500/20 hover:-translate-y-0.5 active:scale-[0.98]">
                Change Password
            </button>
        </form>

        <div class="mt-8 text-center">
            <a href="<?= BASE_URL ?>/public/actions/logout.php"
               class="text-[10px] font-bold text-slate-400 hover:text-[#e11d48] uppercase tracking-[0.15em] transition-colors">
                Cancel &amp; Logout
            </a>
        </div>
    </div>

    <script src="<?= ASSET_URL ?>js/change-password.js"></script>
</body>
</html>