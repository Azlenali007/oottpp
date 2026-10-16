<?php
/**
 * User Registration View
 */
?>
<div class="bg-slate-850 py-8 px-6 sm:px-8 rounded-3xl border border-slate-800 shadow-2xl">
    <div class="mb-6 text-center">
        <h2 class="text-xl font-black text-white">Create your account</h2>
        <p class="text-xs text-slate-400 mt-1">Get instant access to virtual SMS numbers</p>
    </div>

    <?php if (!empty($error)): ?>
        <div class="mb-4 p-3 rounded-xl bg-rose-950/80 border border-rose-800 text-rose-300 text-xs font-semibold flex items-center space-x-2">
            <span>⚠️</span>
            <span><?= \App\Core\View::e($error) ?></span>
        </div>
    <?php endif; ?>

    <form action="/register" method="POST" class="space-y-4">
        <input type="hidden" name="_csrf_token" value="<?= \App\Core\View::e($csrf_token ?? '') ?>">

        <div>
            <label class="block text-xs font-bold text-slate-300 mb-1">Full Name</label>
            <input type="text" name="name" required placeholder="Alex Smith"
                   class="w-full bg-slate-900 border border-slate-700 rounded-xl px-3.5 py-2.5 text-xs text-white placeholder-slate-500 focus:outline-none focus:border-emerald-500 transition">
        </div>

        <div>
            <label class="block text-xs font-bold text-slate-300 mb-1">Email Address</label>
            <input type="email" name="email" required placeholder="you@example.com"
                   class="w-full bg-slate-900 border border-slate-700 rounded-xl px-3.5 py-2.5 text-xs text-white placeholder-slate-500 focus:outline-none focus:border-emerald-500 transition">
        </div>

        <div>
            <label class="block text-xs font-bold text-slate-300 mb-1">Password (min 8 characters)</label>
            <input type="password" name="password" required placeholder="••••••••" minlength="8"
                   class="w-full bg-slate-900 border border-slate-700 rounded-xl px-3.5 py-2.5 text-xs text-white placeholder-slate-500 focus:outline-none focus:border-emerald-500 transition">
        </div>

        <button type="submit" 
                class="w-full bg-emerald-500 hover:bg-emerald-400 text-slate-950 font-black py-2.5 rounded-xl shadow-lg shadow-emerald-500/20 text-xs transition duration-150">
            Create Account &rarr;
        </button>
    </form>

    <div class="mt-6 pt-4 border-t border-slate-800 text-center text-xs text-slate-400">
        Already have an account? <a href="/login" class="text-emerald-400 font-bold hover:underline">Sign in</a>
    </div>
</div>
