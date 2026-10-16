<?php
/**
 * Admin API & System Settings View
 */
?>
<div class="space-y-6" x-data="{
    testingProvider: false,
    providerResult: null,
    csrfToken: '<?= \App\Core\Session::getCsrfToken() ?>',

    testConnection() {
        this.testingProvider = true;
        this.providerResult = null;
        fetch('/admin/settings/test-provider', {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': this.csrfToken }
        })
        .then(r => r.json())
        .then(res => {
            this.testingProvider = false;
            this.providerResult = res;
        })
        .catch(() => {
            this.testingProvider = false;
            this.providerResult = { success: false, error: 'Connection failed' };
        });
    }
}">

    <div>
        <h1 class="text-2xl font-black text-white">System &amp; Gateway Configuration</h1>
        <p class="text-xs text-slate-400 mt-1">Manage provider API credentials, webhooks, and cron job diagnostics.</p>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">

        <!-- Provider API Settings Card -->
        <div class="bg-slate-900 border border-slate-800 p-6 rounded-3xl shadow-xl space-y-4">
            <h2 class="text-base font-bold text-white flex items-center space-x-2">
                <span>🔌</span>
                <span>Active SMS Provider (SMSPool / 5SIM)</span>
            </h2>

            <div class="space-y-3 text-xs">
                <div>
                    <label class="block text-slate-400 mb-1">Provider Adapter</label>
                    <input type="text" value="<?= \App\Core\View::e(env('OTP_PROVIDER_DEFAULT', 'smspool')) ?>" disabled
                           class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3 py-2 text-slate-300 font-mono">
                </div>
                <div>
                    <label class="block text-slate-400 mb-1">API Base URL</label>
                    <input type="text" value="<?= \App\Core\View::e(env('OTP_PROVIDER_BASE_URL', 'https://api.smspool.net')) ?>" disabled
                           class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3 py-2 text-slate-300 font-mono">
                </div>
                <div>
                    <label class="block text-slate-400 mb-1">API Key Status</label>
                    <div class="text-slate-300 font-mono">
                        <?= !empty(env('OTP_PROVIDER_API_KEY')) ? '•••••••••••••••• (Configured in .env)' : 'Not set (Requires key in .env to place live orders)' ?>
                    </div>
                </div>
            </div>

            <button @click="testConnection()" :disabled="testingProvider"
                    class="bg-emerald-500 hover:bg-emerald-400 text-slate-950 font-bold px-4 py-2 rounded-xl text-xs transition">
                <span x-text="testingProvider ? 'Testing API...' : 'Test Provider Ping'"></span>
            </button>

            <template x-if="providerResult">
                <div class="p-3 rounded-xl text-xs" :class="providerResult.success ? 'bg-emerald-950/80 text-emerald-300 border border-emerald-800' : 'bg-rose-950/80 text-rose-300 border border-rose-800'">
                    <span x-text="providerResult.message || providerResult.error"></span>
                </div>
            </template>
        </div>

        <!-- Cron Jobs & WebSocket Status -->
        <div class="bg-slate-900 border border-slate-800 p-6 rounded-3xl shadow-xl space-y-4">
            <h2 class="text-base font-bold text-white flex items-center space-x-2">
                <span>⏱️</span>
                <span>Scheduled Background Processing</span>
            </h2>

            <div class="space-y-3 text-xs text-slate-300">
                <div class="p-3 rounded-xl bg-slate-950 border border-slate-800 space-y-1">
                    <div class="font-bold text-white">Cron Schedule</div>
                    <code class="text-[11px] text-emerald-400 font-mono block">*/1 * * * * php /path/to/cron/check_orders.php</code>
                    <code class="text-[11px] text-emerald-400 font-mono block">*/5 * * * * php /path/to/cron/expire_orders.php</code>
                    <code class="text-[11px] text-emerald-400 font-mono block">0 */6 * * * php /path/to/cron/sync_services.php</code>
                </div>

                <div class="p-3 rounded-xl bg-slate-950 border border-slate-800 space-y-1">
                    <div class="font-bold text-white">WebSocket Service</div>
                    <p class="text-slate-400 text-[11px]">Server: <code class="text-slate-300 font-mono">php websocket/server.php</code> (Port 8080). Polling fallback active.</p>
                </div>
            </div>
        </div>

    </div>

</div>
