<!DOCTYPE html>
<html lang="en" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= \App\Core\View::e($title ?? 'User Dashboard - OTPPro') ?></title>
    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    colors: {
                        emerald: {
                            50: '#ecfdf5',
                            100: '#d1fae5',
                            500: '#10b981',
                            600: '#059669',
                            700: '#047857',
                            800: '#065f46',
                            900: '#064e3b',
                            950: '#022c22'
                        }
                    }
                }
            }
        }
    </script>
    <!-- Alpine.js -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.14.8/dist/cdn.min.js"></script>
    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        [x-cloak] { display: none !important; }
        body { font-family: system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; }
    </style>
</head>
<body class="min-h-full bg-slate-900 text-slate-100 flex flex-col antialiased selection:bg-emerald-500 selection:text-white" x-data="{ userMenu: false }">

    <!-- Top Navigation Bar -->
    <header class="sticky top-0 z-40 bg-slate-900/95 backdrop-blur border-b border-slate-800">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between h-16">
                <!-- Brand & Nav Links -->
                <div class="flex items-center space-x-6">
                    <a href="/dashboard" class="flex items-center space-x-2">
                        <div class="w-8 h-8 rounded-lg bg-emerald-500 flex items-center justify-center text-slate-950 font-black text-base">⚡</div>
                        <span class="text-lg font-black text-white">OTP<span class="text-emerald-400">Pro</span></span>
                    </a>
                    <nav class="hidden md:flex items-center space-x-1 text-xs font-semibold">
                        <a href="/dashboard" class="px-3 py-2 rounded-xl text-slate-300 hover:text-white hover:bg-slate-800 transition">Dashboard</a>
                        <a href="/services" class="px-3 py-2 rounded-xl text-slate-300 hover:text-white hover:bg-slate-800 transition">Services Catalog</a>
                        <a href="/orders/new" class="px-3 py-2 rounded-xl text-emerald-400 hover:text-emerald-300 hover:bg-slate-800 transition font-bold">+ New Order</a>
                        <a href="/orders" class="px-3 py-2 rounded-xl text-slate-300 hover:text-white hover:bg-slate-800 transition">Order History</a>
                        <a href="/wallet" class="px-3 py-2 rounded-xl text-slate-300 hover:text-white hover:bg-slate-800 transition">Wallet & Ledger</a>
                        <a href="/support" class="px-3 py-2 rounded-xl text-slate-300 hover:text-white hover:bg-slate-800 transition">Support</a>
                    </nav>
                </div>

                <!-- Wallet Balance Badge & Profile -->
                <div class="flex items-center space-x-3">
                    <?php 
                        $user = \App\Services\AuthService::user();
                        $wallet = $user ? \App\Services\WalletService::getWallet((int)$user['id']) : null;
                    ?>
                    <?php if ($wallet): ?>
                        <a href="/wallet" class="flex items-center space-x-1.5 px-3 py-1.5 rounded-xl bg-emerald-950/80 border border-emerald-800/80 text-emerald-400 text-xs font-bold hover:bg-emerald-900/60 transition">
                            <span>💳</span>
                            <span><?= \App\Core\View::e($wallet['currency'] ?? 'INR') ?> <?= number_format((float)($wallet['balance'] ?? 0), 2) ?></span>
                        </a>
                    <?php endif; ?>

                    <div class="relative" x-data="{ open: false }">
                        <button @click="open = !open" class="flex items-center space-x-2 text-xs font-semibold text-slate-300 hover:text-white p-1 rounded-xl">
                            <div class="w-8 h-8 rounded-full bg-slate-800 border border-slate-700 flex items-center justify-center font-bold text-emerald-400 uppercase">
                                <?= substr($user['name'] ?? 'U', 0, 1) ?>
                            </div>
                        </button>
                        <div x-show="open" @click.away="open = false" x-cloak class="absolute right-0 mt-2 w-48 rounded-2xl bg-slate-850 border border-slate-800 shadow-xl py-1 text-xs text-slate-300">
                            <div class="px-4 py-2 border-b border-slate-800">
                                <div class="font-bold text-white"><?= \App\Core\View::e($user['name'] ?? 'User') ?></div>
                                <div class="text-[11px] text-slate-500 truncate"><?= \App\Core\View::e($user['email'] ?? '') ?></div>
                            </div>
                            <a href="/profile" class="block px-4 py-2 hover:bg-slate-800 hover:text-white">Profile & Password</a>
                            <a href="/wallet" class="block px-4 py-2 hover:bg-slate-800 hover:text-white">Wallet & Recharge</a>
                            <a href="/support" class="block px-4 py-2 hover:bg-slate-800 hover:text-white">Help Desk</a>
                            <div class="border-t border-slate-800 my-1"></div>
                            <a href="/logout" class="block px-4 py-2 text-rose-400 hover:bg-slate-800">Sign Out</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </header>

    <!-- Main Container -->
    <main class="flex-1 max-w-7xl w-full mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <?= $content ?>
    </main>

    <!-- Mobile Bottom Navigation Bar -->
    <nav class="md:hidden fixed bottom-0 left-0 right-0 z-40 bg-slate-900/95 backdrop-blur border-t border-slate-800 flex items-center justify-around py-2.5 text-[11px] font-semibold text-slate-400">
        <a href="/dashboard" class="flex flex-col items-center hover:text-emerald-400">
            <span>🏠</span>
            <span>Home</span>
        </a>
        <a href="/services" class="flex flex-col items-center hover:text-emerald-400">
            <span>📱</span>
            <span>Services</span>
        </a>
        <a href="/orders/new" class="flex flex-col items-center text-emerald-400 font-bold">
            <span class="w-7 h-7 rounded-full bg-emerald-500 text-slate-950 flex items-center justify-center -mt-3 shadow-md shadow-emerald-500/30 text-xs">+</span>
            <span>Order</span>
        </a>
        <a href="/orders" class="flex flex-col items-center hover:text-emerald-400">
            <span>📋</span>
            <span>Orders</span>
        </a>
        <a href="/wallet" class="flex flex-col items-center hover:text-emerald-400">
            <span>💳</span>
            <span>Wallet</span>
        </a>
    </nav>

</body>
</html>
