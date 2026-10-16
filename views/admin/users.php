<?php
/**
 * Admin User Management View
 */
?>
<div class="space-y-6" x-data="{
    users: <?= json_encode($users ?? []) ?>,
    csrfToken: '<?= \App\Core\Session::getCsrfToken() ?>',

    toggleUser(id) {
        fetch('/admin/users/' + id + '/toggle', {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': this.csrfToken }
        })
        .then(r => r.json())
        .then(res => {
            if (res.success) {
                const u = this.users.find(item => item.id == id);
                if (u) u.status = res.data.new_status;
            }
        });
    }
}">

    <div>
        <h1 class="text-2xl font-black text-white">Registered Users</h1>
        <p class="text-xs text-slate-400 mt-1">Manage accounts, status suspensions, and customer wallets.</p>
    </div>

    <div class="bg-slate-900 border border-slate-800 rounded-3xl p-6 shadow-xl">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead>
                    <tr class="text-slate-400 border-b border-slate-800 text-[11px]">
                        <th class="pb-3">User</th>
                        <th class="pb-3">Role</th>
                        <th class="pb-3">Wallet Balance</th>
                        <th class="pb-3">Status</th>
                        <th class="pb-3">Joined</th>
                        <th class="pb-3 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-800 text-slate-300">
                    <template x-for="u in users" :key="u.id">
                        <tr>
                            <td class="py-3">
                                <div class="font-bold text-white" x-text="u.name"></div>
                                <div class="text-[11px] text-slate-500 font-mono" x-text="u.email"></div>
                            </td>
                            <td class="py-3">
                                <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase"
                                      :class="u.role === 'super_admin' ? 'bg-purple-950 text-purple-400 border border-purple-800' : 'bg-slate-800 text-slate-400'"
                                      x-text="u.role"></span>
                            </td>
                            <td class="py-3 font-mono font-bold text-emerald-400">
                                ₹<span x-text="parseFloat(u.balance || 0).toFixed(2)"></span>
                            </td>
                            <td class="py-3">
                                <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase"
                                      :class="u.status === 'active' ? 'bg-emerald-950 text-emerald-400 border border-emerald-800' : 'bg-rose-950 text-rose-400 border border-rose-800'"
                                      x-text="u.status"></span>
                            </td>
                            <td class="py-3 text-slate-500 font-mono text-[11px]" x-text="u.created_at"></td>
                            <td class="py-3 text-right">
                                <template x-if="u.role !== 'super_admin'">
                                    <button @click="toggleUser(u.id)" class="px-2.5 py-1 rounded-lg bg-slate-800 hover:bg-slate-700 text-slate-300 font-semibold text-[11px]">
                                        <span x-text="u.status === 'active' ? 'Suspend' : 'Activate'"></span>
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
