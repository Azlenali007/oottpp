<div class="max-w-3xl mx-auto my-8 p-6 bg-white dark:bg-slate-800 rounded-3xl shadow-xl border border-slate-200 dark:border-slate-700" 
     x-data="{
        step: 1,
        csrfToken: '<?= $csrf_token ?>',
        requirements: null,
        reqLoading: false,
        dbTesting: false,
        dbStatus: null,
        installing: false,
        installComplete: false,

        // Form Model
        form: {
            db_host: '127.0.0.1',
            db_port: 3306,
            db_name: 'otppro_db',
            db_user: 'root',
            db_pass: '',
            app_name: 'OTPPro',
            app_url: '<?= $app_url ?>',
            app_timezone: 'UTC',
            app_currency: 'INR',
            app_currency_symbol: '₹',
            admin_name: 'Super Admin',
            admin_email: 'admin@otppro.local',
            admin_password: '',
            confirm_overwrite: true
        },

        checkRequirements() {
            this.reqLoading = true;
            fetch('/install/requirements')
                .then(r => r.json())
                .then(res => {
                    this.reqLoading = false;
                    if (res.success) {
                        this.requirements = res.data;
                    }
                })
                .catch(() => { this.reqLoading = false; });
        },

        testDatabase() {
            this.dbTesting = true;
            this.dbStatus = null;
            fetch('/install/test-db', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': this.csrfToken },
                body: JSON.stringify(this.form)
            })
            .then(r => r.json())
            .then(res => {
                this.dbTesting = false;
                this.dbStatus = res;
            })
            .catch(err => {
                this.dbTesting = false;
                this.dbStatus = { success: false, error: 'Network request error' };
            });
        },

        runInstallation() {
            if (!this.form.admin_password || this.form.admin_password.length < 8) {
                Swal.fire('Password Too Short', 'Super Admin password must be at least 8 characters.', 'error');
                return;
            }

            this.installing = true;
            fetch('/install/run', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': this.csrfToken },
                body: JSON.stringify(this.form)
            })
            .then(r => r.json())
            .then(res => {
                this.installing = false;
                if (res.success) {
                    this.installComplete = true;
                    this.step = 7;
                } else {
                    Swal.fire('Installation Failed', res.error || 'Unknown error occurred.', 'error');
                }
            })
            .catch(err => {
                this.installing = false;
                Swal.fire('Error', 'Server connection failure during install.', 'error');
            });
        }
     }">

  <!-- Header -->
  <div class="flex items-center justify-between pb-6 border-b border-slate-100 dark:border-slate-700">
    <div class="flex items-center space-x-3">
      <div class="w-10 h-10 rounded-xl bg-blue-600 text-white flex items-center justify-center font-bold text-xl shadow">
        🛡️
      </div>
      <div>
        <h1 class="text-xl font-bold text-slate-900 dark:text-white">OTP<span class="text-blue-600">Pro</span> Web Installer</h1>
        <p class="text-xs text-slate-400">Step-by-step production deployment wizard</p>
      </div>
    </div>
    <div class="text-xs font-semibold text-blue-600 bg-blue-50 dark:bg-blue-950 px-3 py-1 rounded-full">
      Step <span x-text="step"></span> of 7
    </div>
  </div>

  <!-- Progress Bar -->
  <div class="w-full bg-slate-100 dark:bg-slate-700 h-1.5 my-4 rounded-full overflow-hidden">
    <div class="bg-blue-600 h-full transition-all duration-300" :style="`width: ${(step / 7) * 100}%`"></div>
  </div>

  <!-- STEP 1: WELCOME -->
  <div x-show="step === 1" class="space-y-6 py-4">
    <div class="text-center py-6">
      <div class="w-16 h-16 rounded-2xl bg-blue-50 dark:bg-blue-950 text-blue-600 flex items-center justify-center text-3xl mx-auto mb-4">
        🚀
      </div>
      <h2 class="text-2xl font-extrabold text-slate-900 dark:text-white">Welcome to OTPPro Setup</h2>
      <p class="text-sm text-slate-500 max-w-md mx-auto mt-2">
        This automated wizard will test your server capabilities, connect your MySQL 8+ database, import production tables, and configure your single Super Admin master account.
      </p>
    </div>

    <div class="bg-slate-50 dark:bg-slate-900/50 p-4 rounded-2xl border border-slate-100 dark:border-slate-700 text-xs text-slate-600 dark:text-slate-300 space-y-2">
      <div class="font-bold text-slate-800 dark:text-white">Before proceeding, ensure you have:</div>
      <ul class="list-disc list-inside space-y-1">
        <li>MySQL 8+ database name and credentials from your hosting / VPS.</li>
        <li>Write permissions enabled on the <code>storage/</code> directory and root <code>.env</code> file.</li>
        <li>A secure password for your exclusive Super Admin account.</li>
      </ul>
    </div>

    <div class="flex justify-end pt-4">
      <button @click="step = 2; checkRequirements()" class="bg-blue-600 hover:bg-blue-700 text-white font-bold px-6 py-2.5 rounded-xl shadow transition flex items-center space-x-2">
        <span>Check Requirements</span>
        <span>&rarr;</span>
      </button>
    </div>
  </div>

  <!-- STEP 2: SYSTEM REQUIREMENTS -->
  <div x-show="step === 2" class="space-y-6 py-4">
    <div>
      <h2 class="text-lg font-bold text-slate-900 dark:text-white">Step 2: System Requirements Audit</h2>
      <p class="text-xs text-slate-500">Checking PHP version, required extensions, and file write permissions.</p>
    </div>

    <template x-if="reqLoading">
      <div class="py-12 text-center text-slate-400 font-semibold text-sm">Auditing server environment...</div>
    </template>

    <template x-if="requirements && !reqLoading">
      <div class="space-y-4 text-xs">
        <!-- PHP Version -->
        <div class="p-3.5 rounded-xl border flex items-center justify-between" :class="requirements.php_satisfied ? 'border-emerald-200 bg-emerald-50/50 dark:bg-emerald-950/20 text-emerald-800 dark:text-emerald-300' : 'border-red-200 bg-red-50 text-red-800'">
          <span class="font-bold">PHP Version (>= 8.2 required)</span>
          <span class="font-mono font-bold" x-text="requirements.php_version + (requirements.php_satisfied ? ' (PASS)' : ' (FAIL)')"></span>
        </div>

        <!-- Extensions -->
        <div class="border border-slate-200 dark:border-slate-700 rounded-xl divide-y divide-slate-100 dark:divide-slate-700 overflow-hidden">
          <div class="p-2.5 bg-slate-50 dark:bg-slate-900 font-bold text-slate-700 dark:text-slate-300">Required PHP Extensions</div>
          <template x-for="ext in requirements.extensions" :key="ext.extension">
            <div class="p-2.5 flex items-center justify-between">
              <span x-text="ext.label + ' (' + ext.extension + ')'"></span>
              <span :class="ext.satisfied ? 'text-emerald-600 font-bold' : 'text-red-500 font-bold'" x-text="ext.satisfied ? '✓ Loaded' : '✗ Missing'"></span>
            </div>
          </template>
        </div>

        <!-- Writable Directories -->
        <div class="border border-slate-200 dark:border-slate-700 rounded-xl divide-y divide-slate-100 dark:divide-slate-700 overflow-hidden">
          <div class="p-2.5 bg-slate-50 dark:bg-slate-900 font-bold text-slate-700 dark:text-slate-300">Directory Permissions</div>
          <template x-for="d in requirements.directories" :key="d.path">
            <div class="p-2.5 flex items-center justify-between">
              <span class="font-mono" x-text="d.path"></span>
              <span :class="d.satisfied ? 'text-emerald-600 font-bold' : 'text-red-500 font-bold'" x-text="d.satisfied ? '✓ Writable' : '✗ Unwritable'"></span>
            </div>
          </template>
        </div>
      </div>
    </template>

    <div class="flex justify-between pt-4">
      <button @click="step = 1" class="text-slate-500 hover:text-slate-700 font-semibold px-4 py-2">Back</button>
      <button @click="step = 3" class="bg-blue-600 hover:bg-blue-700 text-white font-bold px-6 py-2.5 rounded-xl shadow transition">
        Continue to Database &rarr;
      </button>
    </div>
  </div>

  <!-- STEP 3: DATABASE CONFIGURATION -->
  <div x-show="step === 3" class="space-y-4 py-4">
    <div>
      <h2 class="text-lg font-bold text-slate-900 dark:text-white">Step 3: Database Setup</h2>
      <p class="text-xs text-slate-500">Enter your MySQL 8+ database server credentials.</p>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
      <div class="sm:col-span-2">
        <label class="block text-xs font-bold text-slate-600 dark:text-slate-400 mb-1">Database Host</label>
        <input type="text" x-model="form.db_host" class="w-full p-2.5 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 text-xs font-mono">
      </div>
      <div>
        <label class="block text-xs font-bold text-slate-600 dark:text-slate-400 mb-1">Port</label>
        <input type="number" x-model="form.db_port" class="w-full p-2.5 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 text-xs font-mono">
      </div>
    </div>

    <div>
      <label class="block text-xs font-bold text-slate-600 dark:text-slate-400 mb-1">Database Name</label>
      <input type="text" x-model="form.db_name" class="w-full p-2.5 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 text-xs font-mono">
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
      <div>
        <label class="block text-xs font-bold text-slate-600 dark:text-slate-400 mb-1">Database Username</label>
        <input type="text" x-model="form.db_user" class="w-full p-2.5 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 text-xs font-mono">
      </div>
      <div>
        <label class="block text-xs font-bold text-slate-600 dark:text-slate-400 mb-1">Database Password</label>
        <input type="password" x-model="form.db_pass" placeholder="••••••••" class="w-full p-2.5 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 text-xs font-mono">
      </div>
    </div>

    <!-- Test Connection Button -->
    <div class="pt-2">
      <button type="button" @click="testDatabase()" :disabled="dbTesting" class="bg-slate-800 hover:bg-slate-700 text-white font-semibold text-xs px-4 py-2 rounded-xl transition flex items-center space-x-2">
        <span x-text="dbTesting ? 'Testing connection...' : '🔌 Test Database Connection'"></span>
      </button>

      <template x-if="dbStatus">
        <div class="mt-3 p-3 rounded-xl text-xs" :class="dbStatus.success ? 'bg-emerald-50 text-emerald-800 border border-emerald-200' : 'bg-red-50 text-red-800 border border-red-200'">
          <span class="font-bold" x-text="dbStatus.success ? '✓ ' + dbStatus.message : '✗ ' + dbStatus.error"></span>
        </div>
      </template>
    </div>

    <div class="flex justify-between pt-4">
      <button @click="step = 2" class="text-slate-500 hover:text-slate-700 font-semibold px-4 py-2 text-xs">Back</button>
      <button @click="step = 4" class="bg-blue-600 hover:bg-blue-700 text-white font-bold px-6 py-2.5 rounded-xl shadow transition text-xs">
        Next: App Settings &rarr;
      </button>
    </div>
  </div>

  <!-- STEP 4: APPLICATION SETTINGS -->
  <div x-show="step === 4" class="space-y-4 py-4">
    <div>
      <h2 class="text-lg font-bold text-slate-900 dark:text-white">Step 4: Application Settings</h2>
      <p class="text-xs text-slate-500">Configure your website name, base URL, and financial preferences.</p>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
      <div>
        <label class="block text-xs font-bold text-slate-600 dark:text-slate-400 mb-1">Application Name</label>
        <input type="text" x-model="form.app_name" class="w-full p-2.5 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 text-xs">
      </div>
      <div>
        <label class="block text-xs font-bold text-slate-600 dark:text-slate-400 mb-1">Base URL</label>
        <input type="text" x-model="form.app_url" class="w-full p-2.5 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 text-xs font-mono">
      </div>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
      <div>
        <label class="block text-xs font-bold text-slate-600 dark:text-slate-400 mb-1">Timezone</label>
        <select x-model="form.app_timezone" class="w-full p-2.5 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 text-xs">
          <option value="UTC">UTC (Universal Time)</option>
          <option value="Asia/Kolkata">Asia/Kolkata (IST)</option>
          <option value="America/New_York">America/New York (EST)</option>
          <option value="Europe/London">Europe/London (GMT)</option>
        </select>
      </div>
      <div>
        <label class="block text-xs font-bold text-slate-600 dark:text-slate-400 mb-1">Currency Code</label>
        <input type="text" x-model="form.app_currency" class="w-full p-2.5 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 text-xs font-mono">
      </div>
      <div>
        <label class="block text-xs font-bold text-slate-600 dark:text-slate-400 mb-1">Currency Symbol</label>
        <input type="text" x-model="form.app_currency_symbol" class="w-full p-2.5 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 text-xs font-bold">
      </div>
    </div>

    <div class="flex justify-between pt-4">
      <button @click="step = 3" class="text-slate-500 hover:text-slate-700 font-semibold px-4 py-2 text-xs">Back</button>
      <button @click="step = 5" class="bg-blue-600 hover:bg-blue-700 text-white font-bold px-6 py-2.5 rounded-xl shadow transition text-xs">
        Next: Super Admin Setup &rarr;
      </button>
    </div>
  </div>

  <!-- STEP 5: SUPER ADMIN SETUP -->
  <div x-show="step === 5" class="space-y-4 py-4">
    <div>
      <h2 class="text-lg font-bold text-slate-900 dark:text-white">Step 5: Super Admin Account</h2>
      <p class="text-xs text-slate-500">Create the first and only Super Admin master account.</p>
    </div>

    <div class="bg-amber-50 dark:bg-amber-950/30 p-3 rounded-xl border border-amber-200 dark:border-amber-800 text-xs text-amber-800 dark:text-amber-300">
      <b>Security Policy:</b> Exactly ONE Super Admin account is permitted. Memorize or save your credentials securely.
    </div>

    <div>
      <label class="block text-xs font-bold text-slate-600 dark:text-slate-400 mb-1">Super Admin Full Name</label>
      <input type="text" x-model="form.admin_name" class="w-full p-2.5 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 text-xs">
    </div>

    <div>
      <label class="block text-xs font-bold text-slate-600 dark:text-slate-400 mb-1">Super Admin Email Address</label>
      <input type="email" x-model="form.admin_email" class="w-full p-2.5 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 text-xs">
    </div>

    <div>
      <label class="block text-xs font-bold text-slate-600 dark:text-slate-400 mb-1">Master Password (min 8 chars)</label>
      <input type="password" x-model="form.admin_password" placeholder="Create strong master password" class="w-full p-2.5 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 text-xs">
    </div>

    <div class="flex justify-between pt-4">
      <button @click="step = 4" class="text-slate-500 hover:text-slate-700 font-semibold px-4 py-2 text-xs">Back</button>
      <button @click="step = 6" class="bg-blue-600 hover:bg-blue-700 text-white font-bold px-6 py-2.5 rounded-xl shadow transition text-xs">
        Review & Install &rarr;
      </button>
    </div>
  </div>

  <!-- STEP 6: REVIEW & INSTALLATION EXECUTION -->
  <div x-show="step === 6" class="space-y-4 py-4">
    <div>
      <h2 class="text-lg font-bold text-slate-900 dark:text-white">Step 6: Confirm & Execute Installation</h2>
      <p class="text-xs text-slate-500">The installer will now execute schema DDL scripts, create indexes, seed your admin account, and generate <code>.env</code>.</p>
    </div>

    <!-- Non-destructive Database Guarantee Notice -->
    <div class="bg-blue-50/70 dark:bg-blue-950/40 p-3.5 rounded-xl border border-blue-200 dark:border-blue-800 text-xs text-blue-900 dark:text-blue-200 flex items-start space-x-2.5">
      <span class="text-base">🛡️</span>
      <div>
        <span class="font-bold">Non-Destructive Guarantee:</span> All table structures and indexes are generated safely with <code class="bg-blue-100 dark:bg-blue-900 px-1 rounded font-mono">IF NOT EXISTS</code>. Existing database tables, users, orders, and ledger records are strictly preserved and never dropped or truncated.
      </div>
    </div>

    <div class="bg-slate-50 dark:bg-slate-900 p-4 rounded-xl border border-slate-200 dark:border-slate-700 space-y-2 text-xs">
      <div class="flex justify-between"><span class="text-slate-400">Database:</span> <span class="font-mono font-bold" x-text="form.db_name + ' @ ' + form.db_host"></span></div>
      <div class="flex justify-between"><span class="text-slate-400">Application URL:</span> <span class="font-mono font-bold" x-text="form.app_url"></span></div>
      <div class="flex justify-between"><span class="text-slate-400">Super Admin Email:</span> <span class="font-bold text-blue-600" x-text="form.admin_email"></span></div>
    </div>

    <!-- Existing .env Notification & Backup Confirmation -->
    <template x-if="requirements && requirements.env_exists">
      <div class="bg-amber-50 dark:bg-amber-950/30 p-3.5 rounded-xl border border-amber-200 dark:border-amber-800 text-xs text-amber-900 dark:text-amber-200 space-y-2">
        <div class="font-bold flex items-center space-x-1.5">
          <span>⚠️</span>
          <span>Existing .env Configuration File Detected</span>
        </div>
        <p class="text-[11px] leading-relaxed">
          The installer will automatically create a safe timestamped backup in <code class="bg-amber-100 dark:bg-amber-900/60 px-1 rounded font-mono">storage/cache/.env.backup.[timestamp]</code> before saving your updated database and master credentials.
        </p>
        <label class="flex items-center space-x-2 pt-1 cursor-pointer font-semibold text-slate-800 dark:text-slate-200">
          <input type="checkbox" x-model="form.confirm_overwrite" class="rounded border-amber-300 text-blue-600 focus:ring-blue-500">
          <span>Confirm saving new configuration and creating safe backup</span>
        </label>
      </div>
    </template>

    <div class="pt-4 flex justify-between">
      <button @click="step = 5" :disabled="installing" class="text-slate-500 hover:text-slate-700 font-semibold px-4 py-2 text-xs">Back</button>
      <button @click="runInstallation()" :disabled="installing || (requirements && requirements.env_exists && !form.confirm_overwrite)" class="bg-emerald-600 hover:bg-emerald-700 disabled:opacity-50 text-white font-bold px-8 py-3 rounded-xl shadow-lg transition flex items-center space-x-2 text-xs">
        <span x-text="installing ? 'Installing Database & Seeding...' : '⚡ Run Production Installation'"></span>
      </button>
    </div>
  </div>

  <!-- STEP 7: INSTALLATION COMPLETE -->
  <div x-show="step === 7" class="space-y-6 py-6 text-center">
    <div class="w-16 h-16 rounded-full bg-emerald-100 text-emerald-600 flex items-center justify-center text-3xl mx-auto shadow">
      ✓
    </div>
    <h2 class="text-2xl font-black text-slate-900 dark:text-white">Installation Completed!</h2>
    <p class="text-xs text-slate-500 max-w-md mx-auto">
      Your database has been provisioned, Super Admin initialized, and a security installation lock has been created at <code>storage/installed.lock</code>.
    </p>

    <div class="bg-slate-50 dark:bg-slate-900 p-4 rounded-2xl border border-slate-200 dark:border-slate-700 max-w-md mx-auto text-left text-xs space-y-2">
      <div class="font-bold text-slate-800 dark:text-white">Next Operational Steps:</div>
      <ol class="list-decimal list-inside space-y-1 text-slate-600 dark:text-slate-300">
        <li>Log in to your <b>Super Admin Dashboard</b> to configure your SMS provider API keys.</li>
        <li>Set up your server's Cron Jobs as listed in the <code>README.md</code>.</li>
        <li>For maximum security on VPS, remove or rename the <code>public/install/</code> directory.</li>
      </ol>
    </div>

    <div class="pt-4 flex justify-center space-x-3">
      <a href="/admin/login" class="bg-blue-600 hover:bg-blue-700 text-white font-bold px-6 py-3 rounded-xl shadow transition text-xs inline-block">
        Go to Super Admin Portal &rarr;
      </a>
      <a href="/login" class="bg-slate-100 dark:bg-slate-700 text-slate-700 dark:text-slate-200 font-bold px-6 py-3 rounded-xl transition text-xs inline-block">
        User Sign In
      </a>
    </div>
  </div>

</div>
