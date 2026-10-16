<?php
/**
 * User Profile & Password Settings View
 */
?>
<div class="max-w-2xl mx-auto space-y-6" x-data="{
    name: '<?= \App\Core\View::e($user['name'] ?? '') ?>',
    phone: '<?= \App\Core\View::e($user['phone'] ?? '') ?>',
    currentPass: '',
    newPass: '',
    confirmPass: '',
    updatingProfile: false,
    updatingPass: false,
    csrfToken: '<?= \App\Core\Session::getCsrfToken() ?>',

    updateProfile() {
        this.updatingProfile = true;
        fetch('/api/profile/update', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': this.csrfToken },
            body: JSON.stringify({ name: this.name, phone: this.phone })
        })
        .then(r => r.json())
        .then(res => {
            this.updatingProfile = false;
            if (res.success) {
                Swal.fire('Updated', 'Profile information saved successfully.', 'success');
            } else {
                Swal.fire('Error', res.error || 'Failed to update profile.', 'error');
            }
        });
    },

    changePassword() {
        if (this.newPass.length < 8) {
            Swal.fire('Password Length', 'New password must be at least 8 characters.', 'warning');
            return;
        }
        if (this.newPass !== this.confirmPass) {
            Swal.fire('Mismatch', 'New passwords do not match.', 'warning');
            return;
        }

        this.updatingPass = true;
        fetch('/api/profile/password', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': this.csrfToken },
            body: JSON.stringify({ current_password: this.currentPass, new_password: this.newPass })
        })
        .then(r => r.json())
        .then(res => {
            this.updatingPass = false;
            if (res.success) {
                this.currentPass = '';
                this.newPass = '';
                this.confirmPass = '';
                Swal.fire('Success', 'Password changed successfully.', 'success');
            } else {
                Swal.fire('Error', res.error || 'Current password incorrect.', 'error');
            }
        });
    }
}">

    <div>
        <h1 class="text-2xl font-black text-white">Profile &amp; Security</h1>
        <p class="text-xs text-slate-400 mt-1">Manage your personal details and account credentials.</p>
    </div>

    <!-- Personal Info Card -->
    <div class="bg-slate-850 border border-slate-800 rounded-3xl p-6 sm:p-8 shadow-xl space-y-4">
        <h2 class="text-base font-bold text-white">Personal Information</h2>
        
        <div>
            <label class="block text-xs font-bold text-slate-300 mb-1">Email Address (Read-only)</label>
            <input type="email" value="<?= \App\Core\View::e($user['email'] ?? '') ?>" disabled 
                   class="w-full bg-slate-900/60 border border-slate-800 rounded-xl px-3.5 py-2.5 text-xs text-slate-400 font-mono">
        </div>

        <div>
            <label class="block text-xs font-bold text-slate-300 mb-1">Full Name</label>
            <input type="text" x-model="name" class="w-full bg-slate-900 border border-slate-700 rounded-xl px-3.5 py-2.5 text-xs text-white focus:outline-none focus:border-emerald-500">
        </div>

        <div>
            <label class="block text-xs font-bold text-slate-300 mb-1">Phone Number</label>
            <input type="text" x-model="phone" placeholder="+91 98765 43210" class="w-full bg-slate-900 border border-slate-700 rounded-xl px-3.5 py-2.5 text-xs text-white focus:outline-none focus:border-emerald-500">
        </div>

        <button @click="updateProfile()" :disabled="updatingProfile" class="bg-emerald-500 hover:bg-emerald-400 text-slate-950 font-bold px-4 py-2 rounded-xl text-xs transition">
            <span x-text="updatingProfile ? 'Saving...' : 'Save Profile'"></span>
        </button>
    </div>

    <!-- Password Card -->
    <div class="bg-slate-850 border border-slate-800 rounded-3xl p-6 sm:p-8 shadow-xl space-y-4">
        <h2 class="text-base font-bold text-white">Change Password</h2>

        <div>
            <label class="block text-xs font-bold text-slate-300 mb-1">Current Password</label>
            <input type="password" x-model="currentPass" class="w-full bg-slate-900 border border-slate-700 rounded-xl px-3.5 py-2.5 text-xs text-white focus:outline-none focus:border-emerald-500">
        </div>

        <div>
            <label class="block text-xs font-bold text-slate-300 mb-1">New Password (min 8 chars)</label>
            <input type="password" x-model="newPass" class="w-full bg-slate-900 border border-slate-700 rounded-xl px-3.5 py-2.5 text-xs text-white focus:outline-none focus:border-emerald-500">
        </div>

        <div>
            <label class="block text-xs font-bold text-slate-300 mb-1">Confirm New Password</label>
            <input type="password" x-model="confirmPass" class="w-full bg-slate-900 border border-slate-700 rounded-xl px-3.5 py-2.5 text-xs text-white focus:outline-none focus:border-emerald-500">
        </div>

        <button @click="changePassword()" :disabled="updatingPass" class="bg-slate-800 hover:bg-slate-700 text-white font-bold px-4 py-2 rounded-xl text-xs border border-slate-700 transition">
            <span x-text="updatingPass ? 'Updating...' : 'Update Password'"></span>
        </button>
    </div>

</div>
