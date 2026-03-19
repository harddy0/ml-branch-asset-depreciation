<?php
$baseUrl = BASE_URL . '/public';
if (!isset($currentPage)) {
    $uri = $_SERVER['REQUEST_URI'];
    if     (str_contains($uri, '/dashboard'))     $currentPage = 'dashboard';
    elseif (str_contains($uri, '/manage-assets')) $currentPage = 'manage-assets'; // ADD THIS LINE
    elseif (str_contains($uri, '/asset-import'))  $currentPage = 'asset-import';
    elseif (str_contains($uri, '/category-mgt'))  $currentPage = 'category-mgt';
    elseif (str_contains($uri, '/user-mgt'))      $currentPage = 'user-mgt';
    else                                          $currentPage = '';
}
?>
<aside id="sidebar"
    class="w-64 bg-[#ce1126] text-white flex flex-col z-10 h-full sticky top-0 overflow-x-hidden shadow-xl shadow-red-900/20 -pb-50"
    style="transition: width 300ms cubic-bezier(0.4,0,0.2,1);"
    onmouseenter="if(!sidebarPinned) this.style.width='256px'"
    onmouseleave="if(!sidebarPinned) this.style.width='64px'">

    <div class="px-5 py-2 flex items-center border-b border-white/10 shrink-0">
        <span class="sidebar-text text-xs font-bold tracking-widest text-white/80 uppercase">Menu</span>
        <div class="controls ml-auto flex items-center gap-2">
            <button onclick="toggleSidebarPin()" class="p-1.5 hover:bg-white/20 rounded-lg transition-colors focus:outline-none" aria-label="Toggle sidebar">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
                </svg>
            </button>
        </div>
    </div>

    <nav class="flex-1 overflow-y-auto overflow-x-hidden">
        <ul class="py-1 space-y-0.5">

            <li class="<?= $currentPage === 'dashboard'
                ? 'bg-black/25 border-l-4 border-white'
                : 'border-l-4 border-transparent hover:border-white/30' ?> transition-colors">
                <a href="<?= $baseUrl ?>/dashboard/"
                   class="flex items-center gap-4 px-5 py-2 hover:bg-black/10 transition-all">
                    <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
                    </svg>
                    <span class="sidebar-text text-[13px] font-bold tracking-wider uppercase whitespace-nowrap">
                        Dashboard
                    </span>
                </a>
            </li>

            <li class="<?= $currentPage === 'asset-import'
                ? 'bg-black/25 border-l-4 border-white'
                : 'border-l-4 border-transparent hover:border-white/30' ?> transition-colors">
                <a href="<?= $baseUrl ?>/asset-import/"
                   class="flex items-center gap-4 px-5 py-2 hover:bg-black/10 transition-all">
                    <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                            d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/>
                    </svg>
                    <span class="sidebar-text text-[13px] font-bold tracking-wider uppercase whitespace-nowrap">
                        Import Asset
                    </span>
                </a>
            </li>

            <li class="<?= $currentPage === 'manage-assets'
                ? 'bg-black/25 border-l-4 border-white'
                : 'border-l-4 border-transparent hover:border-white/30' ?> transition-colors">
                <a href="<?= $baseUrl ?>/manage-assets/"
                   class="flex items-center gap-4 px-5 py-2 hover:bg-black/10 transition-all">
                    <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                            d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                    </svg>
                    <span class="sidebar-text text-[13px] font-bold tracking-wider uppercase whitespace-nowrap">
                        Asset Overview
                    </span>
                </a>
            </li>

            <?php if (($_SESSION['user_type'] ?? '') === 'ADMIN'): ?>

            <li class="<?= $currentPage === 'category-mgt'
                ? 'bg-black/25 border-l-4 border-white'
                : 'border-l-4 border-transparent hover:border-white/30' ?> transition-colors">
                <a href="<?= $baseUrl ?>/category-mgt/"
                   class="flex items-center gap-4 px-5 py-2 hover:bg-black/10 transition-all">
                    <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/>
                    </svg>
                    <span class="sidebar-text text-[13px] font-bold tracking-wider uppercase whitespace-nowrap">
                        Category
                    </span>
                </a>
            </li>

            <li class="<?= $currentPage === 'user-mgt'
                ? 'bg-black/25 border-l-4 border-white'
                : 'border-l-4 border-transparent hover:border-white/30' ?> transition-colors">
                <a href="<?= $baseUrl ?>/user-mgt/"
                   class="flex items-center gap-4 px-5 py-2 hover:bg-black/10 transition-all">
                    <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>
                    </svg>
                    <span class="sidebar-text text-[13px] font-bold tracking-wider uppercase whitespace-nowrap">
                        User Management
                    </span>
                </a>
            </li>

            <?php endif; ?>

        </ul>

        <ul class="py-1">
            <div class="border-t border-white/10">
                <li class="border-l-4 border-transparent hover:border-white/30 transition-colors">
                    <a href="<?= BASE_URL ?>/public/actions/logout.php"
                    class="flex items-center gap-4 px-5 py-2 hover:bg-black/10 transition-all">
                        <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a2 2 0 01-2 2H5a2 2 0 01-2-2V7a2 2 0 012-2h6a2 2 0 012 2v1"/>
                        </svg>
                        <span class="sidebar-text text-[13px] font-bold tracking-wider uppercase whitespace-nowrap">Logout</span>
                    </a>
                </li>
            </div>
        </ul>
    </nav>

    <div class="px-4 py-3 border-t border-white/10 shrink-0">
        <div class="flex items-center justify-between w-full">
            <div class="flex flex-col">
                <p class="sidebar-text text-xs font-bold uppercase text-white truncate block">
                    <?= htmlspecialchars($_SESSION['full_name'] ?? 'User') ?>
                </p>
                <p class="sidebar-text text-[10px] text-white/50 uppercase tracking-wide block mt-1">
                    <?= htmlspecialchars($_SESSION['user_type'] ?? '') ?>
                </p>
            </div>
            <div class="footer-account">
                <a href="<?= BASE_URL ?>/public/profile/" title="Account" class="p-1.5 hover:bg-white/20 rounded-lg transition-colors focus:outline-none">
                    <img src="<?= BASE_URL ?>/public/assets/img/account.png" alt="Account" class="w-6 h-6 object-contain">
                </a>
            </div>
        </div>
    </div>
</aside>

<script>
let sidebarPinned = false;
function toggleSidebarPin() {
    sidebarPinned = !sidebarPinned;
    document.getElementById('sidebar').style.width = sidebarPinned ? '256px' : '64px';
}
</script>