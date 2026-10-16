<?php
/**
 * User Wallet & Ledger View
 */
?>
<div class="space-y-6" x-data="{
    depositAmount: 100,
    depositing: false,
    csrfToken: '<?= \App\Core\Session::getCsrfToken() ?>',

    submitDeposit() {
        if (this.depositAmount < 10) {
            Swal.fire('Minimum Deposit', 'Minimum deposit is ₹10.', 'warning');
            return;
        }

        this.depositing = true;
        fetch('/api/wallet/deposit', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': this.csrfToken
            },
            body: JSON.stringify({
                amount: this.depositAmount,
                gateway: 'upi_gateway'
            })
        })
        .then(r => r.json())
        .then(res => {
            this.depositing = false;
            if (res.success) {
                Swal.fire({
                    icon: 'success',
                    title: 'Payment Initiated',
                    text: 'Payment reference ' + res.data.payment_reference + ' generated. Directing to gateway.',
                    confirmButtonColor: '#10B981'
                }).then(() => {
                    if (res.data.payment_url) {
                        window.location.href = res.data.payment_url;
                    } else {
                        window.location.reload();
                    }
                });
            } else {
                Swal.fire('Failed', res.error || 'Payment initiation failed.', 'error');
            }
        })
        .catch(() => {
            this.depositing = false;
            Swal.fire('Network Error', 'Could not initiate payment.', 'error');
        });
    }
}">

    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-black text-white">Wallet &amp; Transactions</h1>
            <p class="text-xs text-slate-400 mt-1">Immutable financial ledger, online recharge, and balance history.</p>
        </div>
    </div>

    <!-- Balance & Deposit Cards -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">

        <!-- Balance Card -->
        <div class="bg-gradient-to-br from-emerald-950 via-slate-850 to-slate-900 border border-emerald-800/60 p-6 rounded-3xl shadow-xl space-y-4">
            <div class="text-xs font-bold text-emerald-400 uppercase tracking-wider">Available Balance</div>
            <div class="text-3xl font-black text-white">
                <?= \App\Core\View::e($wallet['currency'] ?? 'INR') ?> <?= number_format((float)($wallet['balance'] ?? 0), 2) ?>
            </div>
            <div class="pt-2 border-t border-slate-800 text-xs text-slate-400 space-y-1">
                <div class="flex justify-between">
                    <span>Locked Balance:</span>
                    <span class="font-bold text-slate-300">₹<?= number_format((float)($wallet['locked_balance'] ?? 0), 2) ?></span>
                </div>
                <div class="flex justify-between">
                    <span>Total Deposited:</span>
                    <span class="font-bold text-emerald-400">₹<?= number_format((float)($wallet['total_deposited'] ?? 0), 2) ?></span>
                </div>
                <div class="flex justify-between">
                    <span>Total Spent:</span>
                    <span class="font-bold text-slate-300">₹<?= number_format((float)($wallet['total_spent'] ?? 0), 2) ?></span>
                </div>
            </div>
        </div>

        <!-- Add Funds Recharge Card -->
        <div class="md:col-span-2 bg-slate-850 border border-slate-800 p-6 rounded-3xl shadow-xl space-y-4">
            <h2 class="text-base font-bold text-white flex items-center space-x-2">
                <span>⚡</span>
                <span>Instant Online Recharge</span>
            </h2>

            <div class="space-y-3">
                <label class="block text-xs font-bold text-slate-300">Select or Enter Amount (INR)</label>
                <div class="flex items-center space-x-2">
                    <button type="button" @click="depositAmount = 50" class="px-3 py-1.5 rounded-xl border text-xs font-bold" :class="depositAmount == 50 ? 'bg-emerald-500 text-slate-950 border-emerald-500' : 'bg-slate-900 text-slate-300 border-slate-700'">₹50</button>
                    <button type="button" @click="depositAmount = 100" class="px-3 py-1.5 rounded-xl border text-xs font-bold" :class="depositAmount == 100 ? 'bg-emerald-500 text-slate-950 border-emerald-500' : 'bg-slate-900 text-slate-300 border-slate-700'">₹100</button>
                    <button type="button" @click="depositAmount = 250" class="px-3 py-1.5 rounded-xl border text-xs font-bold" :class="depositAmount == 250 ? 'bg-emerald-500 text-slate-950 border-emerald-500' : 'bg-slate-900 text-slate-300 border-slate-700'">₹250</button>
                    <button type="button" @click="depositAmount = 500" class="px-3 py-1.5 rounded-xl border text-xs font-bold" :class="depositAmount == 500 ? 'bg-emerald-500 text-slate-950 border-emerald-500' : 'bg-slate-900 text-slate-300 border-slate-700'">₹500</button>
                </div>
                <input type="number" x-model="depositAmount" min="10" step="10" class="w-full bg-slate-900 border border-slate-700 rounded-xl px-3.5 py-2.5 text-xs text-white focus:outline-none focus:border-emerald-500">
            </div>

            <button @click="submitDeposit()" :disabled="depositing" class="w-full bg-emerald-500 hover:bg-emerald-400 text-slate-950 font-black py-2.5 rounded-xl text-xs shadow-lg shadow-emerald-500/20 transition">
                <span x-text="depositing ? 'Processing Gateway...' : 'Proceed to Add Funds &rarr;'"></span>
            </button>
        </div>

    </div>

    <!-- Immutable Transaction History -->
    <div class="bg-slate-850 border border-slate-800 rounded-3xl p-6 shadow-xl space-y-4">
        <h2 class="text-base font-bold text-white flex items-center space-x-2">
            <span>📜</span>
            <span>Transaction Ledger History</span>
        </h2>

        <?php if (!empty($transactions)): ?>
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs">
                    <thead>
                        <tr class="text-slate-400 border-b border-slate-800 text-[11px]">
                            <th class="pb-3">Reference</th>
                            <th class="pb-3">Type</th>
                            <th class="pb-3">Amount</th>
                            <th class="pb-3">Balance After</th>
                            <th class="pb-3">Description</th>
                            <th class="pb-3">Timestamp</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-800 text-slate-300">
                        <?php foreach ($transactions as $t): ?>
                            <tr>
                                <td class="py-3 font-mono text-slate-400 text-[11px]"><?= \App\Core\View::e($t['reference'] ?? 'TXN') ?></td>
                                <td class="py-3 font-bold uppercase text-[10px] <?= $t['type'] === 'deposit' || $t['type'] === 'refund' ? 'text-emerald-400' : 'text-rose-400' ?>">
                                    <?= \App\Core\View::e($t['type']) ?>
                                </td>
                                <td class="py-3 font-mono font-bold <?= $t['amount'] > 0 ? 'text-emerald-400' : 'text-rose-400' ?>">
                                    <?= ($t['amount'] > 0 ? '+' : '') . number_format((float)$t['amount'], 2) ?>
                                </td>
                                <td class="py-3 font-mono text-slate-300">₹<?= number_format((float)$t['balance_after'], 2) ?></td>
                                <td class="py-3 text-slate-400"><?= \App\Core\View::e($t['description'] ?? 'Transaction') ?></td>
                                <td class="py-3 text-slate-500"><?= date('d M Y, H:i', strtotime($t['created_at'])) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="text-center py-8 text-slate-500 text-xs">No transactions recorded yet.</div>
        <?php endif; ?>
    </div>

</div>
