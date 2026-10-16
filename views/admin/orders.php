<?php
/**
 * Admin Orders Management View
 */
?>
<div class="space-y-6" x-data="{
    orders: <?= json_encode($orders ?? []) ?>,
    csrfToken: '<?= \App\Core\Session::getCsrfToken() ?>',

    processRefund(id) {
        Swal.fire({
            title: 'Force Refund Order?',
            text: 'This will cancel the order and credit the user wallet immediately.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Yes, Refund',
            confirmButtonColor: '#10B981'
        }).then((result) => {
            if (result.isConfirmed) {
                fetch('/admin/orders/' + id + '/refund', {
                    method: 'POST',
                    headers: { 'X-CSRF-TOKEN': this.csrfToken }
                })
                .then(r => r.json())
                .then(res => {
                    if (res.success) {
                        Swal.fire('Refunded', 'Wallet credited successfully.', 'success')
                            .then(() => window.location.reload());
                    } else {
                        Swal.fire('Error', res.error || 'Failed to refund.', 'error');
                    }
                });
            }
        });
    }
}">

    <div>
        <h1 class="text-2xl font-black text-white">Live Orders Oversight</h1>
        <p class="text-xs text-slate-400 mt-1">Real-time inspection of customer activations, numbers, and refund management.</p>
    </div>

    <div class="bg-slate-900 border border-slate-800 rounded-3xl p-6 shadow-xl">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead>
                    <tr class="text-slate-400 border-b border-slate-800 text-[11px]">
                        <th class="pb-3">Order #</th>
                        <th class="pb-3">Customer</th>
                        <th class="pb-3">Service</th>
                        <th class="pb-3">Phone Number</th>
                        <th class="pb-3">OTP Code</th>
                        <th class="pb-3">Charge</th>
                        <th class="pb-3">Status</th>
                        <th class="pb-3 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-800 text-slate-300">
                    <template x-for="o in orders" :key="o.id">
                        <tr>
                            <td class="py-3 font-mono text-slate-400" x-text="o.order_number || o.id"></td>
                            <td class="py-3">
                                <div class="font-bold text-white" x-text="o.user_name || 'Customer'"></div>
                                <div class="text-[10px] text-slate-500 font-mono" x-text="o.user_email"></div>
                            </td>
                            <td class="py-3 font-semibold text-white" x-text="o.service_name || 'Service'"></td>
                            <td class="py-3 font-mono font-bold text-slate-200" x-text="o.phone_number || '—'"></td>
                            <td class="py-3">
                                <template x-if="o.otp_code">
                                    <span class="px-2 py-0.5 rounded bg-emerald-950 text-emerald-400 border border-emerald-800 font-mono font-bold text-xs" x-text="o.otp_code"></span>
                                </template>
                                <template x-if="!o.otp_code">
                                    <span class="text-slate-500 font-mono text-[11px]">—</span>
                                </template>
                            </td>
                            <td class="py-3 font-mono text-emerald-400 font-bold">₹<span x-text="parseFloat(o.price || 0).toFixed(2)"></span></td>
                            <td class="py-3">
                                <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase"
                                      :class="o.status === 'completed' ? 'bg-emerald-950 text-emerald-400 border border-emerald-800' : 
                                             (o.status === 'pending' ? 'bg-amber-950 text-amber-400 border border-amber-800' : 'bg-slate-800 text-slate-400')"
                                      x-text="o.status"></span>
                            </td>
                            <td class="py-3 text-right">
                                <template x-if="o.status === 'pending'">
                                    <button @click="processRefund(o.id)" class="px-2.5 py-1 rounded-lg bg-rose-950/60 hover:bg-rose-900 border border-rose-800 text-rose-400 font-bold text-[11px]">
                                        Refund
                                    </button>
                                </template>
                            </td>
                        </tr>
                    </template>
                </tbody>
            </table>
        </div>
    </div>

</div>
