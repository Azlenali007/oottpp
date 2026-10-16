<?php
/**
 * User Dashboard View
 */
?>
<div class="space-y-6">

    <!-- Welcome & Quick Stats Banner -->
    <div class="bg-gradient-to-r from-emerald-950/90 via-slate-850 to-slate-900 border border-emerald-800/40 rounded-3xl p-6 sm:p-8 flex flex-col md:flex-row items-start md:items-center justify-between gap-6 shadow-xl">
        <div>
            <div class="text-xs font-bold text-emerald-400 uppercase tracking-wider mb-1">User Workspace</div>
            <h1 class="text-2xl sm:text-3xl font-black text-white">Welcome back, <?= \App\Core\View::e($user['name'] ?? 'User') ?>!</h1>
            <p class="text-xs text-slate-400 mt-1">Ready to receive OTP verification codes on demand.</p>
        </div>

        <div class="flex items-center space-x-3 w-full md:w-auto">
            <a href="/orders/new" class="flex-1 md:flex-initial text-center bg-emerald-500 hover:bg-emerald-400 text-slate-950 font-black text-xs px-5 py-2.5 rounded-xl shadow-lg shadow-emerald-500/20 transition">
                + Get New Number
            </a>
            <a href="/wallet" class="flex-1 md:flex-initial text-center bg-slate-800 hover:bg-slate-700 text-slate-200 font-bold text-xs px-4 py-2.5 rounded-xl border border-slate-700 transition">
                Add Funds
            </a>
        </div>
    </div>

    <!-- Stats Cards -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="bg-slate-850 border border-slate-800 p-5 rounded-2xl">
            <div class="text-[11px] font-bold text-slate-400 uppercase">Wallet Balance</div>
            <div class="text-2xl font-black text-emerald-400 mt-1">
                <?= \App\Core\View::e($wallet['currency'] ?? 'INR') ?> <?= number_format((float)($wallet['balance'] ?? 0), 2) ?>
            </div>
            <div class="text-[10px] text-slate-500 mt-1">Locked: <?= number_format((float)($wallet['locked_balance'] ?? 0), 2) ?></div>
        </div>

        <div class="bg-slate-850 border border-slate-800 p-5 rounded-2xl">
            <div class="text-[11px] font-bold text-slate-400 uppercase">Total Orders</div>
            <div class="text-2xl font-black text-white mt-1"><?= (int)($stats['total_orders'] ?? 0) ?></div>
            <div class="text-[10px] text-slate-500 mt-1">Lifetime requests</div>
        </div>

        <div class="bg-slate-850 border border-slate-800 p-5 rounded-2xl">
            <div class="text-[11px] font-bold text-slate-400 uppercase">Completed</div>
            <div class="text-2xl font-black text-emerald-400 mt-1"><?= (int)($stats['completed_orders'] ?? 0) ?></div>
            <div class="text-[10px] text-emerald-500/80 mt-1">Success rate: <?= (int)($stats['success_rate'] ?? 100) ?>%</div>
        </div>

        <div class="bg-slate-850 border border-slate-800 p-5 rounded-2xl">
            <div class="text-[11px] font-bold text-slate-400 uppercase">Pending / Active</div>
            <div class="text-2xl font-black text-amber-400 mt-1"><?= (int)($stats['pending_orders'] ?? 0) ?></div>
            <div class="text-[10px] text-slate-500 mt-1">Awaiting SMS</div>
        </div>
    </div>

    <!-- Active / Recent Orders Section -->
    <div class="bg-slate-850 border border-slate-800 rounded-3xl p-6 shadow-xl space-y-4">
        <div class="flex items-center justify-between">
            <h2 class="text-base font-bold text-white flex items-center space-x-2">
                <span>📋</span>
                <span>Recent Activations</span>
            </h2>
            <a href="/orders" class="text-xs font-semibold text-emerald-400 hover:underline">View All Orders &rarr;</a>
        </div>

        <?php if (!empty($recentOrders)): ?>
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs">
                    <thead>
                        <tr class="text-slate-400 border-b border-slate-800 text-[11px]">
                            <th class="pb-3">Order</th>
                            <th class="pb-3">Service</th>
                            <th class="pb-3">Phone Number</th>
                            <th class="pb-3">OTP Code</th>
                            <th class="pb-3">Status</th>
                            <th class="pb-3">Date</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-800 text-slate-300">
                        <?php foreach ($recentOrders as $o): ?>
                            <tr>
                                <td class="py-3 font-mono text-slate-400"><?= \App\Core\View::e($o['order_number'] ?? $o['id']) ?></td>
                                <td class="py-3 font-bold text-white flex items-center space-x-2">
                                    <span><?= \App\Core\View::e($o['service_icon'] ?? '📱') ?></span>
                                    <span><?= \App\Core\View::e($o['service_name'] ?? 'Service') ?></span>
                                </td>
                                <td class="py-3 font-mono font-bold text-slate-200"><?= \App\Core\View::e($o['phone_number'] ?? '—') ?></td>
                                <td class="py-3">
                                    <?php if (!empty($o['otp_code'])): ?>
                                        <span class="px-2.5 py-1 rounded-lg bg-emerald-950 text-emerald-300 font-mono font-black text-sm border border-emerald-800">
                                            <?= \App\Core\View::e($o['otp_code']) ?>
                                        </span>
                                    <?php else: ?>
                                        <span class="text-slate-500 font-mono">Waiting...</span>
                                    <?php endif; ?>
                                </td>
                                <td class="py-3">
                                    <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase
                                        <?= ($o['status'] === 'completed') ? 'bg-emerald-950 text-emerald-400 border border-emerald-800' : 
                                            (($o['status'] === 'pending') ? 'bg-amber-950 text-amber-400 border border-amber-800' : 'bg-slate-800 text-slate-400') ?>">
                                        <?= \App\Core\View::e($o['status']) ?>
                                    </span>
                                </td>
                                <td class="py-3 text-slate-500"><?= date('d M, H:i', strtotime($o['created_at'])) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="text-center py-8 text-slate-500 text-xs">
                No orders placed yet. <a href="/orders/new" class="text-emerald-400 font-bold hover:underline">Get your first temporary number &rarr;</a>
            </div>
        <?php endif; ?>
    </div>

</div>
