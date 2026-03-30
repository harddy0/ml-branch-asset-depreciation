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
    </style>
</head>
<body class="h-full flex items-center justify-center overflow-hidden">

    <video autoplay muted loop playsinline id="bg-video">
        <source src="<?= BASE_URL ?>/assets/vid/moving2.mp4" type="video/mp4">
        Your browser does not support HTML5 video.
    </video>
    <div class="video-overlay"></div>

    <div class="relative z-10 w-full max-w-md bg-white/90 backdrop-blur-md rounded-2xl shadow-2xl border border-white/20 p-10">
        <div class="text-center mb-8">
            <div class="w-14 h-14 bg-[#e11d48] rounded-2xl flex items-center justify-center mx-auto mb-4 shadow-lg shadow-red-500/30">
                <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                </svg>
            </div>
            <h1 class="text-2xl font-black text-slate-800 uppercase tracking-tight">Set New Password</h1>
            <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mt-2">Required Security Update</p>
        </div>

        <?php if ($error): ?>
            <div class="mb-6 bg-red-50 border-l-4 border-red-600 text-red-700 text-sm font-bold px-4 py-3 rounded shadow-sm">
                <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="<?= BASE_URL ?>/public/actions/update_password.php" class="space-y-6">
            <div>
                <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-[0.2em] mb-2">New Password</label>
                <input type="password" name="new_password" id="new_pw" required minlength="8"
                    placeholder="••••••••"
                    class="w-full border-2 border-slate-200 focus:border-[#e11d48] rounded-xl px-4 py-3.5
                           text-sm font-bold text-slate-800 outline-none bg-white transition-all shadow-sm">
            </div>

            <div>
                <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-[0.2em] mb-2">Confirm Password</label>
                <input type="password" name="confirm_password" id="confirm_pw" required minlength="8"
                    placeholder="••••••••"
                    class="w-full border-2 border-slate-200 focus:border-[#e11d48] rounded-xl px-4 py-3.5
                           text-sm font-bold text-slate-800 outline-none bg-white transition-all shadow-sm">
                <p id="matchHint" class="text-[10px] font-black mt-2 hidden"></p>
            </div>

            <button type="submit"
                class="w-full bg-[#e11d48] hover:bg-[#be123c] text-white font-black
                       py-4 rounded-xl text-xs uppercase tracking-[0.2em] transition-all shadow-xl shadow-red-500/20 hover:-translate-y-0.5 active:scale-[0.98]">
                Update Password
            </button>
        </form>

        <div class="mt-8 text-center">
            <a href="<?= BASE_URL ?>/public/actions/logout.php"
               class="text-[10px] font-bold text-slate-400 hover:text-[#e11d48] uppercase tracking-[0.15em] transition-colors">
                Cancel &amp; Logout
            </a>
        </div>
    </div>

    <script>
        const np = document.getElementById('new_pw');
        const cp = document.getElementById('confirm_pw');
        const hint = document.getElementById('matchHint');

        const validatePasswords = () => {
            if (!cp.value) { 
                hint.classList.add('hidden'); 
                return; 
            }
            
            hint.classList.remove('hidden');
            if (np.value === cp.value) {
                hint.textContent = '✓ Passwords match';
                hint.className = 'text-[10px] font-black mt-2 text-green-600 uppercase tracking-wider';
            } else {
                hint.textContent = '✗ Passwords do not match';
                hint.className = 'text-[10px] font-black mt-2 text-red-500 uppercase tracking-wider';
            }
        };

        cp.addEventListener('input', validatePasswords);
        np.addEventListener('input', validatePasswords);
    </script>
</body>
</html>