<?php
/**
 * OTPPro - Real Production Landing Page
 * Green theme design matching the primary visual reference.
 */
?>
<div class="space-y-16 py-8" x-data="{
    search: '',
    selectedCountry: 'all',
    services: <?= json_encode($services ?? []) ?>,
    get filteredServices() {
        return this.services.filter(s => {
            const matchName = !this.search || s.name.toLowerCase().includes(this.search.toLowerCase()) || (s.category && s.category.toLowerCase().includes(this.search.toLowerCase()));
            return matchName;
        });
    }
}">

    <!-- Hero Section -->
    <section class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center pt-8 pb-4">
        <div class="inline-flex items-center space-x-2 px-3 py-1.5 rounded-full bg-emerald-950/80 border border-emerald-800/80 text-emerald-400 text-xs font-semibold mb-6">
            <span class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse"></span>
            <span>Live Temporary SMS Verification Engine</span>
        </div>

        <h1 class="text-4xl sm:text-5xl lg:text-6xl font-black text-white tracking-tight leading-none max-w-4xl mx-auto">
            Instant SMS Verification &amp; <span class="text-transparent bg-clip-text bg-gradient-to-r from-emerald-400 to-teal-300">OTP Numbers</span>
        </h1>

        <p class="mt-5 text-sm sm:text-base text-slate-400 max-w-2xl mx-auto leading-relaxed">
            Receive activation codes in seconds for WhatsApp, Telegram, Google, OpenAI, Discord, and 50+ services worldwide. Guaranteed automatic refund if no SMS arrives.
        </p>

        <div class="mt-8 flex flex-wrap items-center justify-center gap-3">
            <a href="/register" class="bg-emerald-500 hover:bg-emerald-400 text-slate-950 font-black text-xs sm:text-sm px-6 py-3 rounded-2xl shadow-lg shadow-emerald-500/25 transition">
                Create Free Account
            </a>
            <a href="/login" class="bg-slate-800 hover:bg-slate-700 text-slate-200 font-bold text-xs sm:text-sm px-6 py-3 rounded-2xl border border-slate-700 transition">
                Sign In to Portal
            </a>
            <a href="/install" class="bg-slate-900/80 hover:bg-slate-800 text-slate-400 hover:text-emerald-400 font-medium text-xs px-4 py-3 rounded-2xl border border-slate-800 transition">
                🛠️ Web Installer
            </a>
        </div>
    </section>

    <!-- Real Service Search & Live Catalog -->
    <section class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8" id="services">
        <div class="bg-slate-850 border border-slate-800 rounded-3xl p-6 sm:p-8 shadow-2xl space-y-6">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div>
                    <h2 class="text-xl font-bold text-white flex items-center space-x-2">
                        <span>📱</span>
                        <span>Supported Verification Services</span>
                    </h2>
                    <p class="text-xs text-slate-400 mt-1">Real-time prices and availability provided by active routing nodes.</p>
                </div>

                <!-- Instant Search Input -->
                <div class="relative w-full sm:w-72">
                    <input type="text" x-model="search" placeholder="Search service (WhatsApp, Google...)" 
                           class="w-full bg-slate-900 border border-slate-700 rounded-xl px-3.5 py-2 text-xs text-white placeholder-slate-500 focus:outline-none focus:border-emerald-500">
                    <span class="absolute right-3 top-2.5 text-xs text-slate-500">🔍</span>
                </div>
            </div>

            <!-- Service Cards Grid -->
            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4">
                <template x-for="s in filteredServices" :key="s.id">
                    <div class="bg-slate-900/90 border border-slate-800 hover:border-emerald-500/50 rounded-2xl p-4 transition-all hover:shadow-lg hover:shadow-emerald-500/5 group flex flex-col justify-between">
                        <div>
                            <div class="flex items-center justify-between mb-3">
                                <div class="w-10 h-10 rounded-xl bg-slate-800 border border-slate-700 flex items-center justify-center text-xl group-hover:scale-105 transition-transform" 
                                     x-text="s.icon || '📱'"></div>
                                <span class="text-[10px] font-bold px-2 py-0.5 rounded-full" 
                                      :class="s.status === 'active' ? 'bg-emerald-950 text-emerald-400 border border-emerald-800/80' : 'bg-slate-800 text-slate-400'"
                                      x-text="s.status === 'active' ? '✓ Available' : 'Inactive'"></span>
                            </div>
                            <h3 class="text-sm font-bold text-white" x-text="s.name"></h3>
                            <div class="text-[11px] text-slate-400 mt-0.5" x-text="s.category || 'General Activation'"></div>
                        </div>

                        <div class="mt-4 pt-3 border-t border-slate-800/80 flex items-center justify-between">
                            <div>
                                <span class="text-[10px] text-slate-500 uppercase font-semibold">From</span>
                                <div class="text-sm font-extrabold text-emerald-400">
                                    ₹<span x-text="parseFloat(s.selling_price || 15).toFixed(2)"></span>
                                </div>
                            </div>
                            <a :href="'/orders/new?service_id=' + s.id" 
                               class="bg-emerald-500 hover:bg-emerald-400 text-slate-950 text-xs font-bold px-3 py-1.5 rounded-xl transition shadow shadow-emerald-500/20">
                                Get Number
                            </a>
                        </div>
                    </div>
                </template>
            </div>

            <div x-show="filteredServices.length === 0" x-cloak class="text-center py-12 text-slate-500 text-xs font-semibold">
                No matching verification service found for "<span x-text="search"></span>".
            </div>
        </div>
    </section>

    <!-- How It Works Section -->
    <section class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8" id="how-it-works">
        <div class="text-center mb-10">
            <h2 class="text-2xl font-black text-white">How OTPPro Works</h2>
            <p class="text-xs text-slate-400 mt-1">Get your activation code in 3 simple automated steps</p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <div class="bg-slate-850 border border-slate-800 p-6 rounded-2xl text-center space-y-3">
                <div class="w-12 h-12 rounded-2xl bg-emerald-950 border border-emerald-800 text-emerald-400 flex items-center justify-center text-xl font-black mx-auto">
                    1
                </div>
                <h3 class="font-bold text-white text-base">Select Service &amp; Country</h3>
                <p class="text-xs text-slate-400 leading-relaxed">
                    Choose from 50+ supported apps and select your desired country routing node.
                </p>
            </div>

            <div class="bg-slate-850 border border-slate-800 p-6 rounded-2xl text-center space-y-3">
                <div class="w-12 h-12 rounded-2xl bg-emerald-950 border border-emerald-800 text-emerald-400 flex items-center justify-center text-xl font-black mx-auto">
                    2
                </div>
                <h3 class="font-bold text-white text-base">Get Virtual Number</h3>
                <p class="text-xs text-slate-400 leading-relaxed">
                    Instantly copy your dedicated disposable number and enter it in the app's verification form.
                </p>
            </div>

            <div class="bg-slate-850 border border-slate-800 p-6 rounded-2xl text-center space-y-3">
                <div class="w-12 h-12 rounded-2xl bg-emerald-950 border border-emerald-800 text-emerald-400 flex items-center justify-center text-xl font-black mx-auto">
                    3
                </div>
                <h3 class="font-bold text-white text-base">Receive Code in Real Time</h3>
                <p class="text-xs text-slate-400 leading-relaxed">
                    Our live WebSocket listener fetches your OTP instantly. If no SMS arrives, you get a 100% refund.
                </p>
            </div>
        </div>
    </section>

    <!-- Bottom CTA Banner -->
    <section class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 pb-8">
        <div class="rounded-3xl bg-gradient-to-r from-emerald-900/60 via-slate-850 to-slate-900 border border-emerald-800/40 p-8 sm:p-12 text-center space-y-4">
            <h2 class="text-2xl sm:text-3xl font-black text-white">Ready for Instant SMS Verification?</h2>
            <p class="text-xs sm:text-sm text-slate-300 max-w-xl mx-auto">
                Join thousands of verified users. Deposit securely with instant UPI/Gateway options and activate numbers on demand.
            </p>
            <div class="pt-2 flex justify-center space-x-3">
                <a href="/register" class="bg-emerald-500 hover:bg-emerald-400 text-slate-950 font-black text-xs sm:text-sm px-6 py-3 rounded-xl shadow-lg shadow-emerald-500/25 transition">
                    Start Verifying Now &rarr;
                </a>
            </div>
        </div>
    </section>

</div>
