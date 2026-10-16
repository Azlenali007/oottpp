<?php
/**
 * 404 Page Not Found View
 */
?>
<div class="max-w-md mx-auto my-16 p-8 bg-slate-850 rounded-3xl border border-slate-800 text-center space-y-5 shadow-2xl">
    <div class="w-16 h-16 rounded-2xl bg-rose-950/60 text-rose-400 flex items-center justify-center text-3xl mx-auto border border-rose-800/80">
        404
    </div>

    <div>
        <h1 class="text-xl font-black text-white">Page Not Found</h1>
        <p class="text-xs text-slate-400 mt-2 leading-relaxed">
            The page you requested <code class="text-rose-400 font-mono"><?= \App\Core\View::e($path ?? '') ?></code> does not exist on this server.
        </p>
    </div>

    <div class="pt-2 flex justify-center space-x-3">
        <a href="/" class="bg-emerald-500 hover:bg-emerald-400 text-slate-950 font-bold text-xs px-5 py-2.5 rounded-xl shadow transition">
            Go to Homepage
        </a>
        <a href="/login" class="bg-slate-800 hover:bg-slate-700 text-slate-200 font-semibold text-xs px-5 py-2.5 rounded-xl border border-slate-700 transition">
            Sign In
        </a>
    </div>
</div>
