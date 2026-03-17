<header class="h-16 bg-white border-b border-slate-200 flex items-center justify-between px-8 shadow-sm z-20 relative w-full shrink-0">
    <div class="flex items-center gap-3">
        <div class="w-8 h-8 bg-red-600 rounded-lg flex items-center justify-center">
            <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M13 10V3L4 14h7v7l9-11h-7z"/>
            </svg>
        </div>
        <span class="text-slate-700 text-sm font-bold uppercase tracking-widest hidden md:block">My App</span>
    </div>
    <div class="flex items-center gap-4">
        <span class="text-slate-500 text-sm hidden md:block">
            <?= htmlspecialchars($_SESSION['full_name'] ?? '') ?>
        </span>
        <a href="<?= BASE_URL ?>/public/actions/logout.php"
           class="text-xs font-bold text-slate-500 hover:text-red-600 uppercase tracking-wider transition-colors">
            Logout
        </a>
    </div>
</header>
