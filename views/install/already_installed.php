<div class="max-w-md mx-auto my-12 p-8 bg-white dark:bg-slate-800 rounded-3xl shadow-xl border border-slate-200 dark:border-slate-700 text-center space-y-5">
  <div class="w-16 h-16 rounded-2xl bg-amber-50 dark:bg-amber-950/40 text-amber-500 flex items-center justify-center text-3xl mx-auto">
    🔒
  </div>

  <div>
    <h1 class="text-xl font-bold text-slate-900 dark:text-white">Application Already Installed</h1>
    <p class="text-xs text-slate-500 dark:text-slate-400 mt-2 leading-relaxed">
      OTPPro is already installed and protected by an installation lock file.
    </p>
  </div>

  <div class="bg-slate-50 dark:bg-slate-900/60 p-4 rounded-2xl border border-slate-100 dark:border-slate-700 text-xs text-slate-600 dark:text-slate-300 text-left space-y-2">
    <div class="font-bold text-slate-800 dark:text-white">To re-run the installer:</div>
    <ol class="list-decimal list-inside space-y-1">
      <li>Access your server via SSH, FTP, or cPanel File Manager.</li>
      <li>Manually delete the lock file: <code class="bg-slate-200 dark:bg-slate-800 px-1.5 py-0.5 rounded text-[11px] font-mono">storage/installed.lock</code></li>
      <li>Refresh this page to restart the installation wizard.</li>
    </ol>
  </div>

  <div class="pt-2 flex justify-center space-x-3">
    <a href="/login" class="bg-blue-600 hover:bg-blue-700 text-white font-bold text-xs px-6 py-2.5 rounded-xl shadow transition">
      Go to Sign In
    </a>
    <a href="/admin/login" class="bg-slate-800 hover:bg-slate-700 text-slate-200 font-bold text-xs px-6 py-2.5 rounded-xl transition">
      Super Admin Portal
    </a>
  </div>
</div>
