<?php
/**
 * New Order Workflow View
 */
?>
<div class="max-w-2xl mx-auto space-y-6" x-data="{
    services: <?= json_encode($services ?? []) ?>,
    countries: <?= json_encode($countries ?? []) ?>,
    selectedServiceId: <?= json_encode($_GET['service_id'] ?? null) ?>,
    selectedCountryId: null,
    submitting: false,
    csrfToken: '<?= $csrf_token ?? '' ?>',

    get selectedService() {
        return this.services.find(s => s.id == this.selectedServiceId) || null;
    },

    init() {
        if (this.countries.length > 0) {
            this.selectedCountryId = this.countries[0].id;
        }
        if (!this.selectedServiceId && this.services.length > 0) {
            this.selectedServiceId = this.services[0].id;
        }
    },

    placeOrder() {
        if (!this.selectedServiceId || !this.selectedCountryId) {
            Swal.fire('Incomplete', 'Please select both a service and country.', 'warning');
            return;
        }

        this.submitting = true;
        fetch('/api/orders', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': this.csrfToken
            },
            body: JSON.stringify({
                service_id: this.selectedServiceId,
                country_id: this.selectedCountryId
            })
        })
        .then(r => r.json())
        .then(res => {
            this.submitting = false;
            if (res.success) {
                Swal.fire({
                    icon: 'success',
                    title: 'Number Assigned!',
                    text: 'Your temporary number: ' + (res.data.phone_number || 'Generated'),
                    confirmButtonColor: '#10B981'
                }).then(() => {
                    window.location.href = '/orders';
                });
            } else {
                Swal.fire('Order Failed', res.error || 'Insufficient wallet balance or provider offline.', 'error');
            }
        })
        .catch(err => {
            this.submitting = false;
            Swal.fire('Network Error', 'Failed to reach ordering service.', 'error');
        });
    }
}">

    <div class="text-center">
        <h1 class="text-2xl font-black text-white">Request Verification Number</h1>
        <p class="text-xs text-slate-400 mt-1">Select your service and country to receive an instant disposable number.</p>
    </div>

    <div class="bg-slate-850 border border-slate-800 rounded-3xl p-6 sm:p-8 shadow-xl space-y-6">

        <!-- Step 1: Select Service -->
        <div>
            <label class="block text-xs font-bold text-slate-300 mb-2">1. Select Service</label>
            <select x-model="selectedServiceId" 
                    class="w-full bg-slate-900 border border-slate-700 rounded-xl px-3.5 py-2.5 text-xs text-white focus:outline-none focus:border-emerald-500">
                <template x-for="s in services" :key="s.id">
                    <option :value="s.id" x-text="s.name + ' (₹' + parseFloat(s.selling_price || 15).toFixed(2) + ')'"></option>
                </template>
            </select>
        </div>

        <!-- Step 2: Select Country -->
        <div>
            <label class="block text-xs font-bold text-slate-300 mb-2">2. Select Country</label>
            <select x-model="selectedCountryId" 
                    class="w-full bg-slate-900 border border-slate-700 rounded-xl px-3.5 py-2.5 text-xs text-white focus:outline-none focus:border-emerald-500">
                <template x-for="c in countries" :key="c.id">
                    <option :value="c.id" x-text="(c.flag_emoji || '🌐') + ' ' + c.name + ' (+' + c.phone_code + ')'"></option>
                </template>
            </select>
        </div>

        <!-- Order Summary Card -->
        <template x-if="selectedService">
            <div class="p-4 rounded-2xl bg-slate-900 border border-slate-800 flex items-center justify-between">
                <div>
                    <div class="text-[11px] text-slate-400">Total Deduction</div>
                    <div class="text-xl font-black text-emerald-400">
                        ₹<span x-text="parseFloat(selectedService.selling_price || 15).toFixed(2)"></span>
                    </div>
                </div>
                <div class="text-right text-[11px] text-slate-400">
                    <div>Money-Back Guarantee</div>
                    <div class="text-emerald-400 font-semibold">100% Refund if no OTP</div>
                </div>
            </div>
        </template>

        <button @click="placeOrder()" :disabled="submitting" 
                class="w-full bg-emerald-500 hover:bg-emerald-400 disabled:opacity-50 text-slate-950 font-black py-3 rounded-xl shadow-lg shadow-emerald-500/20 text-xs transition duration-150">
            <span x-text="submitting ? 'Connecting Provider...' : '⚡ Get Number & Start Waiting for SMS'"></span>
        </button>

    </div>

</div>
