<?php
/**
 * Support Tickets View
 */
?>
<div class="space-y-6" x-data="{
    subject: '',
    description: '',
    ticketType: 'order_issue',
    submitting: false,
    csrfToken: '<?= \App\Core\Session::getCsrfToken() ?>',

    createTicket() {
        if (!this.subject || !this.description) {
            Swal.fire('Incomplete', 'Please fill in subject and description.', 'warning');
            return;
        }

        this.submitting = true;
        fetch('/api/support/ticket', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': this.csrfToken },
            body: JSON.stringify({
                subject: this.subject,
                description: this.description,
                ticket_type: this.ticketType
            })
        })
        .then(r => r.json())
        .then(res => {
            this.submitting = false;
            if (res.success) {
                Swal.fire('Ticket Submitted', 'Ticket #' + res.data.ticket_number + ' created.', 'success')
                    .then(() => window.location.reload());
            } else {
                Swal.fire('Error', res.error || 'Failed to submit ticket.', 'error');
            }
        });
    }
}">

    <div>
        <h1 class="text-2xl font-black text-white">Support &amp; Help Desk</h1>
        <p class="text-xs text-slate-400 mt-1">Get fast assistance with your verification numbers and recharge queries.</p>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">

        <!-- Create Ticket Form -->
        <div class="bg-slate-850 border border-slate-800 p-6 rounded-3xl shadow-xl space-y-4">
            <h2 class="text-base font-bold text-white">Open New Ticket</h2>

            <div>
                <label class="block text-xs font-bold text-slate-300 mb-1">Issue Category</label>
                <select x-model="ticketType" class="w-full bg-slate-900 border border-slate-700 rounded-xl px-3 py-2 text-xs text-white">
                    <option value="order_issue">Verification Code Issue</option>
                    <option value="wallet_issue">Payment / Deposit Query</option>
                    <option value="account_issue">Account Assistance</option>
                    <option value="general">Other / Inquiries</option>
                </select>
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-300 mb-1">Subject</label>
                <input type="text" x-model="subject" placeholder="e.g. WhatsApp verification code not received" 
                       class="w-full bg-slate-900 border border-slate-700 rounded-xl px-3 py-2 text-xs text-white focus:outline-none focus:border-emerald-500">
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-300 mb-1">Detailed Description</label>
                <textarea x-model="description" rows="4" placeholder="Include order number or payment reference if applicable..." 
                          class="w-full bg-slate-900 border border-slate-700 rounded-xl p-3 text-xs text-white focus:outline-none focus:border-emerald-500"></textarea>
            </div>

            <button @click="createTicket()" :disabled="submitting" 
                    class="w-full bg-emerald-500 hover:bg-emerald-400 text-slate-950 font-bold py-2.5 rounded-xl text-xs transition">
                <span x-text="submitting ? 'Submitting...' : 'Submit Support Ticket'"></span>
            </button>
        </div>

        <!-- Ticket History -->
        <div class="md:col-span-2 bg-slate-850 border border-slate-800 p-6 rounded-3xl shadow-xl space-y-4">
            <h2 class="text-base font-bold text-white">Your Support Tickets</h2>

            <?php if (!empty($tickets)): ?>
                <div class="space-y-3">
                    <?php foreach ($tickets as $t): ?>
                        <div class="p-4 rounded-2xl bg-slate-900 border border-slate-800 space-y-2">
                            <div class="flex items-center justify-between">
                                <span class="font-mono text-[11px] text-slate-400"><?= \App\Core\View::e($t['ticket_number'] ?? 'TKT') ?></span>
                                <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase <?= $t['status'] === 'open' ? 'bg-amber-950 text-amber-400 border border-amber-800' : 'bg-slate-800 text-slate-400' ?>">
                                    <?= \App\Core\View::e($t['status']) ?>
                                </span>
                            </div>
                            <h3 class="font-bold text-white text-sm"><?= \App\Core\View::e($t['subject']) ?></h3>
                            <p class="text-xs text-slate-400"><?= \App\Core\View::e($t['description']) ?></p>
                            <div class="text-[10px] text-slate-500 pt-1"><?= date('d M Y, H:i', strtotime($t['created_at'])) ?></div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="text-center py-12 text-slate-500 text-xs">No support tickets submitted yet.</div>
            <?php endif; ?>
        </div>

    </div>

</div>
