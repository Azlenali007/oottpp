<?php
/**
 * Admin Services Management View
 */
?>
<div class="space-y-6" x-data="{
    services: <?= json_encode($services ?? []) ?>,
    syncing: false,
    csrfToken: '<?= \App\Core\Session::getCsrfToken() ?>',

    syncCatalog() {
        this.syncing = true;
        fetch('/admin/services/sync', {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': this.csrfToken }
        })
        .then(r => r.json())
        .then(res => {
            this.syncing = false;
            if (res.success) {
                Swal.fire('Synced', res.message || 'Provider catalog refreshed successfully.', 'success')
                    .then(() => window.location.reload());
            } else {
                Swal.fire('Sync Error', res.error || 'Provider synchronization failed.', 'error');
            }
        })
        .catch(() => {
            this.syncing = false;
            Swal.fire('Network Error', 'Could not sync provider catalog.', 'error');
        });
    },

    toggleService(id) {
        fetch('/admin/services/' + id + '/toggle', {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': this.csrfToken }
        })
        .then(r => r.json())
        .then(res => {
            if (res.success) {
                const s = this.services.find(item => item.id == id);
                if (s) s.status = res.data.new_status;
            }
        });
    },

    updatePrice(id, currentPrice) {
        Swal.fire({
            title: 'Update Selling Price (INR)',
            input: 'number',
            inputValue: currentPrice,
            inputAttributes: { step: '0.5', min: '1' },
            showCancelButton: true,
            confirmButtonText: 'Save Price',
            confirmButtonColor: '#10B981'
        }).then((result) => {
            if (result.isConfirmed && result.value) {
                fetch('/admin/services/' + id + '/price', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': this.csrfToken },
                    body: JSON.stringify({ price: result.value })
                })
                .then(r => r.json())
                .then(res => {
                    if (res.success) {
                        const s = this.services.find(item => item.id == id);
                        if (s) s.selling_price = result.value;
                        Swal.fire('Saved', 'Price updated to ₹' + result.value, 'success');
                    }
                });
            }
        });
    }
}">

    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-black text-white">Services &amp; Pricing Management</h1>
            <p class="text-xs text-slate-400 mt-1">Configure retail markup and enable/disable services from provider feeds.</p>
        </div>
        <button @click="syncCatalog()" :disabled="syncing" 
                class="bg-emerald-500 hover:bg-emerald-400 disabled:opacity-50 text-slate-950 font-black text-xs px-4 py-2.5 rounded-xl shadow-lg shadow-emerald-500/20 transition self-start sm:self-auto">
            <span x-text="syncing ? 'Syncing...' : '🔄 Sync from Provider API'"></span>
        </button>
    </div>

    <div class="bg-slate-900 border border-slate-800 rounded-3xl p-6 shadow-xl">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead>
                    <tr class="text-slate-400 border-b border-slate-800 text-[11px]">
                        <th class="pb-3">Service</th>
                        <th class="pb-3">Category</th>
                        <th class="pb-3">Cost Price</th>
                        <th class="pb-3">Selling Price</th>
                        <th class="pb-3">Status</th>
                        <th class="pb-3 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-800 text-slate-300">
                    <template x-for="s in services" :key="s.id">
                        <tr>
                            <td class="py-3 font-bold text-white flex items-center space-x-2">
                                <span x-text="s.icon || '📱'"></span>
                                <span x-text="s.name"></span>
                            </td>
                            <td class="py-3 text-slate-400" x-text="s.category || 'Activation'"></td>
                            <td class="py-3 font-mono text-slate-400">₹<span x-text="parseFloat(s.cost_price || 10).toFixed(2)"></span></td>
                            <td class="py-3 font-mono font-bold text-emerald-400">
                                ₹<span x-text="parseFloat(s.selling_price || 15).toFixed(2)"></span>
                            </td>
                            <td class="py-3">
                                <button @click="toggleService(s.id)" class="px-2 py-0.5 rounded text-[10px] font-bold uppercase transition"
                                        :class="s.status === 'active' ? 'bg-emerald-950 text-emerald-400 border border-emerald-800' : 'bg-slate-800 text-slate-400'"
                                        x-text="s.status">
                                </button>
                            </td>
                            <td class="py-3 text-right space-x-2">
                                <button @click="updatePrice(s.id, s.selling_price)" class="px-2.5 py-1 rounded-lg bg-slate-800 hover:bg-slate-700 text-slate-300 font-semibold text-[11px]">
                                    Edit Price
                                </button>
                            </td>
                        </tr>
                    </template>
                </tbody>
            </table>
        </div>
    </div>

</div>
