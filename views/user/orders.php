<?php
/**
 * User Orders & OTP Retrieval View
 */
?>
<div class="space-y-6" x-data="{
    orders: <?= json_encode($orders ?? []) ?>,
    checkingId: null,
    pollingInterval: null,

    copyToClipboard(text) {
        navigator.clipboard.writeText(text).then(() => {
            Swal.fire({
                toast: true,
                position: 'top-end',
                icon: 'success',
                title: 'Copied to clipboard!',
                showConfirmButton: false,
                timer: 1500
            });
        });
    },

    checkOtp(orderId) {
        this.checkingId = orderId;
        fetch('/api/orders/' + orderId + '/check')
            .then(r => r.json())
            .then(res => {
                this.checkingId = null;
                if (res.success && res.data.otp_code) {
                    const order = this.orders.find(o => o.id == orderId);
                    if (order) {
                        order.otp_code = res.data.otp_code;
                        order.status = 'completed';
                    }
                    Swal.fire('OTP Received!', 'Your code: ' + res.data.otp_code, 'success');
                } else {
                    Swal.fire('No Code Yet', 'Still waiting for SMS. Please check again in a few seconds.', 'info');
                }
            })
            .catch(() => {
                this.checkingId = null;
            });
    },

    cancelOrder(orderId) {
        Swal.fire({
            title: 'Cancel Order?',
            text: 'Are you sure you want to cancel? If eligible, your wallet will be refunded immediately.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#EF4444',
            cancelButtonColor: '#6B7280',
            confirmButtonText: 'Yes, Cancel'
        }).then((result) => {
            if (result.isConfirmed) {
                fetch('/api/orders/' + orderId + '/cancel', {
                    method: 'POST',
                    headers: { 'X-CSRF-TOKEN': '<?= \App\Core\Session::getCsrfToken() ?>' }
                })
                .then(r => r.json())
                .then(res => {
                    if (res.success) {
                        Swal.fire('Cancelled', 'Order cancelled and balance refunded.', 'success')
                            .then(() => window.location.reload());
                    } else {
                        Swal.fire('Failed', res.error || 'Could not cancel order.', 'error');
                    }
                });
            }
        });
    }
}">

    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-black text-white">Your Orders &amp; OTPs</h1>
            <p class="text-xs text-slate-400 mt-1">Live status, phone numbers, and received SMS codes.</p>
        </div>
        <a href="/orders/new" class="bg-emerald-500 hover:bg-emerald-400 text-slate-950 font-black text-xs px-4 py-2.5 rounded-xl shadow-lg shadow-emerald-500/20 transition self-start sm:self-auto">
            + New Order
        </a>
    </div>

    <!-- Orders Table Card -->
    <div class="bg-slate-850 border border-slate-800 rounded-3xl p-6 shadow-xl">
        <template x-if="orders.length > 0">
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs">
                    <thead>
                        <tr class="text-slate-400 border-b border-slate-800 text-[11px]">
                            <th class="pb-3">Order #</th>
                            <th class="pb-3">Service</th>
                            <th class="pb-3">Phone Number</th>
                            <th class="pb-3">OTP Code</th>
                            <th class="pb-3">Status</th>
                            <th class="pb-3 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-800 text-slate-300">
                        <template x-for="o in orders" :key="o.id">
                            <tr>
                                <td class="py-3 font-mono text-slate-400" x-text="o.order_number || o.id"></td>
                                <td class="py-3 font-bold text-white">
                                    <span x-text="o.service_icon || '📱'"></span>
                                    <span x-text="o.service_name || 'Service'"></span>
                                </td>
                                <td class="py-3">
                                    <div class="flex items-center space-x-2 font-mono font-bold text-slate-200">
                                        <span x-text="o.phone_number || 'Pending'"></span>
                                        <template x-if="o.phone_number">
                                            <button @click="copyToClipboard(o.phone_number)" class="text-slate-500 hover:text-emerald-400 text-[11px]" title="Copy Number">📋</button>
                                        </template>
                                    </div>
                                </td>
                                <td class="py-3">
                                    <template x-if="o.otp_code">
                                        <div class="flex items-center space-x-2">
                                            <span class="px-2.5 py-1 rounded-lg bg-emerald-950 text-emerald-300 font-mono font-black text-sm border border-emerald-800" x-text="o.otp_code"></span>
                                            <button @click="copyToClipboard(o.otp_code)" class="text-emerald-400 hover:text-emerald-300 text-xs">Copy</button>
                                        </div>
                                    </template>
                                    <template x-if="!o.otp_code && o.status === 'pending'">
                                        <span class="text-amber-400 font-mono text-[11px] animate-pulse">Waiting for SMS...</span>
                                    </template>
                                    <template x-if="!o.otp_code && o.status !== 'pending'">
                                        <span class="text-slate-500 font-mono text-[11px]">—</span>
                                    </template>
                                </td>
                                <td class="py-3">
                                    <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase"
                                          :class="o.status === 'completed' ? 'bg-emerald-950 text-emerald-400 border border-emerald-800' : 
                                                 (o.status === 'pending' ? 'bg-amber-950 text-amber-400 border border-amber-800' : 'bg-slate-800 text-slate-400')"
                                          x-text="o.status"></span>
                                </td>
                                <td class="py-3 text-right space-x-2">
                                    <template x-if="o.status === 'pending'">
                                        <button @click="checkOtp(o.id)" :disabled="checkingId == o.id" 
                                                class="px-2.5 py-1 rounded-lg bg-emerald-950 border border-emerald-800 text-emerald-400 hover:bg-emerald-900 font-bold text-[11px]">
                                            <span x-text="checkingId == o.id ? 'Checking...' : '🔄 Check OTP'"></span>
                                        </button>
                                    </template>
                                    <template x-if="o.status === 'pending'">
                                        <button @click="cancelOrder(o.id)" 
                                                class="px-2.5 py-1 rounded-lg bg-rose-950/60 border border-rose-800 text-rose-400 hover:bg-rose-900 font-bold text-[11px]">
                                            Cancel
                                        </button>
                                    </template>
                                </td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>
        </template>

        <template x-if="orders.length === 0">
            <div class="text-center py-12 text-slate-500 text-xs">
                No orders found. <a href="/orders/new" class="text-emerald-400 font-bold hover:underline">Place your first order &rarr;</a>
            </div>
        </template>
    </div>

</div>
