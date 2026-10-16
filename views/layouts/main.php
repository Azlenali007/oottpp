<!DOCTYPE html>
<html lang="en" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= \App\Core\View::e($title ?? 'OTPPro - SMS Verification & OTP Platform') ?></title>
    <meta name="description" content="Instant temporary phone numbers for SMS & OTP verification. Real-time delivery, automated API, and money-back guarantee.">
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
<body class="min-h-full bg-slate-900 text-slate-100 flex flex-col antialiased selection:bg-emerald-500 selection:text-white" x-data="{ mobileMenu: false }">

    <!-- Global Navigation Header -->
    <header class="sticky top-0 z-50 bg-slate-900/90 backdrop-blur-md border-b border-slate-800">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between h-16">
                <!-- Brand Logo -->
                <div class="flex items-center space-x-3">
                    <a href="/" class="flex items-center space-x-2.5">
                        <div class="w-9 h-9 rounded-xl bg-gradient-to-tr from-emerald-600 to-teal-400 flex items-center justify-center shadow-lg shadow-emerald-500/20 text-white font-black text-lg">
                            ⚡
                        </div>
                        <span class="text-xl font-black tracking-tight text-white">OTP<span class="text-emerald-400">Pro</span></span>
                    </a>
                    <span class="hidden md:inline-flex items-center px-2 py-0.5 rounded text-[11px] font-semibold bg-emerald-950 text-emerald-400 border border-emerald-800/60">
                        Live Engine
                    </span>
                </div>

                <!-- Desktop Navigation Links -->
                <nav class="hidden md:flex items-center space-x-8 text-sm font-medium text-slate-300">
                    <a href="/" class="hover:text-emerald-400 transition-colors">Home</a>
                    <a href="/services" class="hover:text-emerald-400 transition-colors">Services & Pricing</a>
                    <a href="#how-it-works" class="hover:text-emerald-400 transition-colors">How It Works</a>
                    <a href="#coverage" class="hover:text-emerald-400 transition-colors">Supported Countries</a>
                </nav>

                <!-- User Action CTAs -->
                <div class="hidden md:flex items-center space-x-3">
                    <?php if (\App\Core\Session::get('user_id')): ?>
                        <a href="/dashboard" class="text-xs font-semibold px-4 py-2 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-200 border border-slate-700 transition">
                            Dashboard
                        </a>
                        <a href="/logout" class="text-xs font-semibold px-3 py-2 text-slate-400 hover:text-rose-400 transition">
                            Sign Out
                        </a>
                    <?php else: ?>
                        <a href="/login" class="text-xs font-semibold px-4 py-2 rounded-xl text-slate-300 hover:text-white transition">
                            Sign In
                        </a>
                        <a href="/register" class="text-xs font-bold px-4 py-2 rounded-xl bg-emerald-500 hover:bg-emerald-400 text-slate-950 shadow-md shadow-emerald-500/20 transition">
                            Get Started
                        </a>
                    <?php endif; ?>
                </div>

                <!-- Mobile Menu Button -->
                <div class="flex md:hidden">
                    <button @click="mobileMenu = !mobileMenu" type="button" class="text-slate-400 hover:text-white p-2">
                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path x-show="!mobileMenu" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                            <path x-show="mobileMenu" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>
            </div>
        </div>

        <!-- Mobile Menu Dropdown -->
        <div x-show="mobileMenu" x-cloak class="md:hidden bg-slate-850 border-b border-slate-800 px-4 pt-2 pb-4 space-y-2 text-sm font-medium">
            <a href="/" class="block px-3 py-2 rounded-lg text-slate-200 hover:bg-slate-800">Home</a>
            <a href="/services" class="block px-3 py-2 rounded-lg text-slate-200 hover:bg-slate-800">Services</a>
            <a href="/login" class="block px-3 py-2 rounded-lg text-slate-200 hover:bg-slate-800">Sign In</a>
            <a href="/register" class="block px-3 py-2 rounded-lg bg-emerald-500 text-slate-950 font-bold text-center">Get Started</a>
            <a href="/install" class="block px-3 py-2 rounded-lg text-xs text-slate-400 hover:text-emerald-400">Web Installer</a>
        </div>
    </header>

    <!-- Main View Content -->
    <main class="flex-1">
        <?= $content ?>
    </main>

    <!-- Global Footer -->
    <footer class="bg-slate-950 border-t border-slate-800/80 py-12 text-slate-400 text-xs">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 grid grid-cols-1 md:grid-cols-4 gap-8">
            <div class="space-y-3">
                <div class="flex items-center space-x-2">
                    <div class="w-6 h-6 rounded bg-emerald-500 flex items-center justify-center text-slate-950 font-bold text-xs">⚡</div>
                    <span class="text-base font-bold text-white">OTP<span class="text-emerald-400">Pro</span></span>
                </div>
                <p class="text-slate-400 text-xs leading-relaxed">
                    Reliable, automated OTP verification services with high-availability provider routing and secure payment processing.
                </p>
            </div>
            <div>
                <h4 class="font-bold text-white mb-3 uppercase tracking-wider text-[11px]">Platform</h4>
                <ul class="space-y-2">
                    <li><a href="/services" class="hover:text-emerald-400 transition">All Services</a></li>
                    <li><a href="/login" class="hover:text-emerald-400 transition">User Login</a></li>
                    <li><a href="/register" class="hover:text-emerald-400 transition">Create Account</a></li>
                    <li><a href="/install" class="hover:text-emerald-400 transition">System Installer</a></li>
                </ul>
            </div>
            <div>
                <h4 class="font-bold text-white mb-3 uppercase tracking-wider text-[11px]">Popular Services</h4>
                <ul class="space-y-2">
                    <li><a href="/services?q=whatsapp" class="hover:text-emerald-400 transition">WhatsApp OTP</a></li>
                    <li><a href="/services?q=telegram" class="hover:text-emerald-400 transition">Telegram OTP</a></li>
                    <li><a href="/services?q=google" class="hover:text-emerald-400 transition">Google / Gmail OTP</a></li>
                    <li><a href="/services?q=openai" class="hover:text-emerald-400 transition">OpenAI / ChatGPT OTP</a></li>
                </ul>
            </div>
            <div>
                <h4 class="font-bold text-white mb-3 uppercase tracking-wider text-[11px]">Administration</h4>
                <ul class="space-y-2">
                    <li><a href="/admin/login" class="hover:text-emerald-400 transition">Super Admin Portal</a></li>
                    <li><span class="text-slate-500">Locked to exactly 1 Super Admin</span></li>
                    <li><span class="text-slate-500">Core PHP 8.2+ &bull; MySQL 8+ Engine</span></li>
                </ul>
            </div>
        </div>
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 mt-8 pt-6 border-t border-slate-900 flex flex-col sm:flex-row items-center justify-between text-slate-500 text-[11px]">
            <div>&copy; <?= date('Y') ?> OTPPro Platform. Strictly authorized verification services only.</div>
            <div class="mt-2 sm:mt-0 flex space-x-4">
                <a href="/install" class="hover:text-emerald-400">Web Installer</a>
                <a href="/admin/login" class="hover:text-emerald-400">Admin</a>
            </div>
        </div>
    </footer>

</body>
</html>
