<?php
/**
 * Services Catalog View
 */
?>
<div class="space-y-6" x-data="{
    search: '',
    selectedCategory: 'all',
    services: <?= json_encode($services ?? []) ?>,
    get filtered() {
        return this.services.filter(s => {
            const matchName = !this.search || s.name.toLowerCase().includes(this.search.toLowerCase());
            const matchCat = this.selectedCategory === 'all' || s.category === this.selectedCategory;
            return matchName && matchCat;
        });
    }
}">

    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-black text-white">Services Catalog</h1>
            <p class="text-xs text-slate-400 mt-1">Browse active verification services with live pricing.</p>
        </div>

        <div class="flex items-center space-x-3">
            <input type="text" x-model="search" placeholder="Search service..." 
                   class="bg-slate-850 border border-slate-700 rounded-xl px-3.5 py-2 text-xs text-white placeholder-slate-500 focus:outline-none focus:border-emerald-500 w-64">
        </div>
    </div>

    <!-- Services Grid -->
    <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4">
        <template x-for="s in filtered" :key="s.id">
            <div class="bg-slate-850 border border-slate-800 hover:border-emerald-500/50 rounded-2xl p-4 transition-all flex flex-col justify-between">
                <div>
                    <div class="flex items-center justify-between mb-3">
                        <div class="w-10 h-10 rounded-xl bg-slate-800 border border-slate-700 flex items-center justify-center text-xl" 
                             x-text="s.icon || '📱'"></div>
                        <span class="text-[10px] font-bold px-2 py-0.5 rounded-full" 
                              :class="s.status === 'active' ? 'bg-emerald-950 text-emerald-400 border border-emerald-800/80' : 'bg-slate-800 text-slate-400'"
                              x-text="s.status === 'active' ? 'Available' : 'Unavailable'"></span>
                    </div>
                    <h3 class="text-sm font-bold text-white" x-text="s.name"></h3>
                    <div class="text-[11px] text-slate-400 mt-0.5" x-text="s.category || 'Activation'"></div>
                </div>

                <div class="mt-4 pt-3 border-t border-slate-800 flex items-center justify-between">
                    <div>
                        <span class="text-[10px] text-slate-500">Price</span>
                        <div class="text-sm font-black text-emerald-400">
                            ₹<span x-text="parseFloat(s.selling_price || 15).toFixed(2)"></span>
                        </div>
                    </div>
                    <a :href="'/orders/new?service_id=' + s.id" 
                       class="bg-emerald-500 hover:bg-emerald-400 text-slate-950 text-xs font-bold px-3 py-1.5 rounded-xl transition shadow shadow-emerald-500/20">
                        Order
                    </a>
                </div>
            </div>
        </template>
    </div>

    <div x-show="filtered.length === 0" x-cloak class="text-center py-12 text-slate-500 text-xs">
        No services found matching your search.
    </div>

</div>
