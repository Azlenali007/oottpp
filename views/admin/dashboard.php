<?php
/**
 * Super Admin Dashboard View
 */
?>
<div class="space-y-6">

    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-black text-white">Super Admin Command Center</h1>
            <p class="text-xs text-slate-400 mt-1">Platform-wide financial metrics, provider routing, and orders.</p>
        </div>
        <div class="flex space-x-2">
            <a href="/admin/services" class="bg-emerald-500 hover:bg-emerald-400 text-slate-950 font-bold text-xs px-4 py-2 rounded-xl transition">
                Manage Services
            </a>
            <a href="/admin/settings" class="bg-slate-800 hover:bg-slate-700 text-slate-200 font-semibold text-xs px-4 py-2 rounded-xl border border-slate-700 transition">
                Provider API
            </a>
        </div>
    </div>

    <!-- Metric Stats Cards -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="bg-slate-900 border border-slate-800 p-5 rounded-2xl">
            <div class="text-[11px] font-bold text-slate-400 uppercase">Gross Revenue</div>
            <div class="text-2xl font-black text-emerald-400 mt-1">
                ₹<?= number_format((float)($revenue['total_revenue'] ?? 0), 2) ?>
            </div>
            <div class="text-[10px] text-slate-500 mt-1">From completed orders</div>
        </div>

        <div class="bg-slate-900 border border-slate-800 p-5 rounded-2xl">
            <div class="text-[11px] font-bold text-slate-400 uppercase">Estimated Profit</div>
            <div class="text-2xl font-black text-white mt-1">
                ₹<?= number_format((float)($revenue['estimated_profit'] ?? 0), 2) ?>
            </div>
            <div class="text-[10px] text-emerald-500/80 mt-1">Selling margin</div>
        </div>

        <div class="bg-slate-900 border border-slate-800 p-5 rounded-2xl">
            <div class="text-[11px] font-bold text-slate-400 uppercase">Total Users</div>
            <div class="text-2xl font-black text-white mt-1"><?= (int)($users['total_users'] ?? 1) ?></div>
            <div class="text-[10px] text-slate-500 mt-1">Active customer accounts</div>
        </div>

        <div class="bg-slate-900 border border-slate-800 p-5 rounded-2xl">
            <div class="text-[11px] font-bold text-slate-400 uppercase">Active Orders Today</div>
            <div class="text-2xl font-black text-amber-400 mt-1"><?= (int)($orders['pending_orders'] ?? 0) ?></div>
            <div class="text-[10px] text-slate-500 mt-1">Live routing</div>
        </div>
    </div>

    <!-- Management Quick Action Links -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <a href="/admin/services" class="p-5 rounded-2xl bg-slate-900 border border-slate-800 hover:border-emerald-500/50 transition group space-y-2">
            <div class="text-xl">📱</div>
            <h3 class="font-bold text-white text-sm group-hover:text-emerald-400 transition">Services &amp; Pricing</h3>
            <p class="text-xs text-slate-400">Sync with provider catalog, toggle services, and update retail markup.</p>
        </a>

        <a href="/admin/orders" class="p-5 rounded-2xl bg-slate-900 border border-slate-800 hover:border-emerald-500/50 transition group space-y-2">
            <div class="text-xl">📋</div>
            <h3 class="font-bold text-white text-sm group-hover:text-emerald-400 transition">Orders &amp; Refunds</h3>
            <p class="text-xs text-slate-400">Monitor live verification orders and process immediate customer refunds.</p>
        </a>

        <a href="/admin/settings" class="p-5 rounded-2xl bg-slate-900 border border-slate-800 hover:border-emerald-500/50 transition group space-y-2">
            <div class="text-xl">⚙️</div>
            <h3 class="font-bold text-white text-sm group-hover:text-emerald-400 transition">Gateway &amp; API Settings</h3>
            <p class="text-xs text-slate-400">Configure provider keys, test connection ping, and manage platform controls.</p>
        </a>
    </div>

    <!-- Recent Audit Logs -->
    <div class="bg-slate-900 border border-slate-800 rounded-3xl p-6 shadow-xl space-y-4">
        <h2 class="text-base font-bold text-white flex items-center space-x-2">
            <span>🛡️</span>
            <span>Security Audit Log</span>
        </h2>

        <?php if (!empty($auditLogs)): ?>
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs">
                    <thead>
                        <tr class="text-slate-400 border-b border-slate-800 text-[11px]">
                            <th class="pb-3">Action</th>
                            <th class="pb-3">Target</th>
                            <th class="pb-3">IP Address</th>
                            <th class="pb-3">Timestamp</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-800 text-slate-300 font-mono text-[11px]">
                        <?php foreach ($auditLogs as $log): ?>
                            <tr>
                                <td class="py-2.5 font-bold text-emerald-400"><?= \App\Core\View::e($log['action']) ?></td>
                                <td class="py-2.5 text-slate-400"><?= \App\Core\View::e(($log['target_type'] ?? '') . ' #' . ($log['target_id'] ?? '')) ?></td>
                                <td class="py-2.5 text-slate-500"><?= \App\Core\View::e($log['ip_address'] ?? '127.0.0.1') ?></td>
                                <td class="py-2.5 text-slate-500"><?= date('d M Y, H:i', strtotime($log['created_at'])) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="text-center py-6 text-slate-500 text-xs">No audit events recorded yet.</div>
        <?php endif; ?>
    </div>

</div>
