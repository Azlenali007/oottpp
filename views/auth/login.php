<?php
/**
 * User Sign In View
 */
?>
<div class="bg-slate-850 py-8 px-6 sm:px-8 rounded-3xl border border-slate-800 shadow-2xl">
    <div class="mb-6 text-center">
        <h2 class="text-xl font-black text-white">Sign in to your account</h2>
        <p class="text-xs text-slate-400 mt-1">Access temporary numbers and your active order ledger</p>
    </div>

    <?php if (!empty($error)): ?>
        <div class="mb-4 p-3 rounded-xl bg-rose-950/80 border border-rose-800 text-rose-300 text-xs font-semibold flex items-center space-x-2">
            <span>⚠️</span>
            <span><?= \App\Core\View::e($error) ?></span>
        </div>
    <?php endif; ?>

    <?php if (!empty($success)): ?>
        <div class="mb-4 p-3 rounded-xl bg-emerald-950/80 border border-emerald-800 text-emerald-300 text-xs font-semibold flex items-center space-x-2">
            <span>✓</span>
            <span><?= \App\Core\View::e($success) ?></span>
        </div>
    <?php endif; ?>

    <form action="/login" method="POST" class="space-y-4">
        <input type="hidden" name="_csrf_token" value="<?= \App\Core\View::e($csrf_token ?? '') ?>">

        <div>
            <label class="block text-xs font-bold text-slate-300 mb-1">Email Address</label>
            <input type="email" name="email" required placeholder="you@example.com"
                   class="w-full bg-slate-900 border border-slate-700 rounded-xl px-3.5 py-2.5 text-xs text-white placeholder-slate-500 focus:outline-none focus:border-emerald-500 transition">
        </div>

        <div>
            <label class="block text-xs font-bold text-slate-300 mb-1">Password</label>
            <input type="password" name="password" required placeholder="••••••••"
                   class="w-full bg-slate-900 border border-slate-700 rounded-xl px-3.5 py-2.5 text-xs text-white placeholder-slate-500 focus:outline-none focus:border-emerald-500 transition">
        </div>

        <button type="submit" 
                class="w-full bg-emerald-500 hover:bg-emerald-400 text-slate-950 font-black py-2.5 rounded-xl shadow-lg shadow-emerald-500/20 text-xs transition duration-150">
            Sign In &rarr;
        </button>
    </form>

    <div class="mt-6 pt-4 border-t border-slate-800 text-center text-xs text-slate-400 space-y-2">
        <div>Don't have an account yet? <a href="/register" class="text-emerald-400 font-bold hover:underline">Create account</a></div>
        <div>Are you the platform operator? <a href="/admin/login" class="text-slate-500 hover:text-slate-300">Super Admin Portal</a></div>
    </div>
</div>
