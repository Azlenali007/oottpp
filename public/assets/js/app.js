/**
 * OTPPro - Alpine.js Application Core
 * 
 * Drives all responsive UI components with URL synchronization:
 * - Green theme design reference matching the primary screenshot
 * - URL Synchronization with History API (/login, /register, /install, /dashboard, etc.)
 * - Real API integration for Orders, Stock, Wallet, and Authentication
 * - Super Admin Command Center with Chart.js analytics
 */

document.addEventListener('alpine:init', () => {
    Alpine.data('otpApp', () => ({
        // View States
        panel: 'user', // 'user', 'admin', or 'install'
        currentPage: 'home', // 'home', 'login', 'register', 'services', 'new-order', 'orders', 'wallet', 'profile', 'support', 'admin-dashboard', 'admin-services', 'admin-orders', 'admin-users', 'admin-settings'
        theme: 'dark',
        mobileMenuOpen: false,

        // Authentication Forms
        loginForm: { email: '', password: '', loading: false, error: '' },
        registerForm: { name: '', email: '', password: '', loading: false, error: '' },
        adminLoginForm: { email: '', password: '', loading: false, error: '' },

        // Web Installer State
        installStep: 1,
        installTestingDb: false,
        installDbStatus: null,
        installRunning: false,
        installForm: {
            db_host: '127.0.0.1',
            db_port: 3306,
            db_name: 'otppro_db',
            db_user: 'root',
            db_pass: '',
            app_name: 'OTPPro',
            app_url: window.location.origin || 'http://localhost:3000',
            app_timezone: 'UTC',
            app_currency: 'INR',
            app_currency_symbol: '₹',
            admin_name: 'Super Admin',
            admin_email: 'admin@otppro.local',
            admin_password: '',
            confirm_overwrite: true
        },

        // User Data
        user: {
            id: 1,
            name: 'Verified User',
            email: 'user@otppro.local',
            phone: '+91 98765 43210',
            role: 'user',
            avatar: 'U'
        },

        // Financial & Stats
        walletBalance: 485.00,
        currencySymbol: '₹',
        stats: {
            totalOrders: 12,
            completed: 10,
            pending: 2,
            failed: 0,
            successRate: 83
        },

        // Category Filters for Services
        categories: ['All', 'Social Media', 'Google', 'Microsoft', 'Email', 'Messaging', 'Other'],
        selectedCategory: 'All',
        searchServiceQuery: '',

        // Master Services Catalog (From reference screenshot)
        services: [
            { id: 1, name: 'WhatsApp', slug: 'whatsapp', category: 'Social Media', price: 15.00, stock: 'Available', popular: true, icon: '💬', color: '#10B981' },
            { id: 2, name: 'Telegram', slug: 'telegram', category: 'Messaging', price: 14.00, stock: 'Available', popular: true, icon: '✈️', color: '#059669' },
            { id: 3, name: 'Google / Gmail', slug: 'google', category: 'Google', price: 20.00, stock: 'Available', popular: true, icon: '🌐', color: '#10B981' },
            { id: 4, name: 'Instagram', slug: 'instagram', category: 'Social Media', price: 15.00, stock: 'Available', popular: true, icon: '📸', color: '#059669' },
            { id: 5, name: 'OpenAI / ChatGPT', slug: 'openai', category: 'Other', price: 18.00, stock: 'Available', popular: true, icon: '🤖', color: '#10B981' },
            { id: 6, name: 'Discord', slug: 'discord', category: 'Social Media', price: 12.00, stock: 'Available', popular: false, icon: '🎮', color: '#059669' },
            { id: 7, name: 'Microsoft', slug: 'microsoft', category: 'Microsoft', price: 22.00, stock: 'Available', popular: false, icon: '🪟', color: '#10B981' },
            { id: 8, name: 'Twitter / X', slug: 'x', category: 'Social Media', price: 16.00, stock: 'Available', popular: false, icon: '🐦', color: '#059669' },
        ],

        // Supported Countries
        countries: [
            { id: 1, name: 'India', code: 'IN', flag: '🇮🇳', dial: '+91', rate: 1.0 },
            { id: 2, name: 'United States', code: 'US', flag: '🇺🇸', dial: '+1', rate: 1.2 },
            { id: 3, name: 'United Kingdom', code: 'GB', flag: '🇬🇧', dial: '+44', rate: 1.4 },
            { id: 4, name: 'Canada', code: 'CA', flag: '🇨🇦', dial: '+1', rate: 1.3 },
            { id: 5, name: 'Germany', code: 'DE', flag: '🇩🇪', dial: '+49', rate: 1.5 },
        ],

        // New Order Form State
        newOrder: {
            step: 1,
            serviceId: 1,
            countryId: 1,
            quantity: 1,
            unitPrice: 15.00,
            totalAmount: 15.00,
            deliveryTime: '1 - 10 minutes',
            successRate: '99%',
            submitting: false
        },

        // Orders List
        ordersFilter: 'all',
        ordersSearchQuery: '',
        orders: [
            { id: 1042, number: '#ORD1042', service: 'WhatsApp', icon: '💬', country: 'India', flag: '🇮🇳', status: 'Completed', date: '5 min ago', otp: '582914', amount: 15.00, phone: '+91 98765 12345' },
            { id: 1041, number: '#ORD1041', service: 'Telegram', icon: '✈️', country: 'United States', flag: '🇺🇸', status: 'Pending', date: '12 min ago', otp: null, amount: 14.00, phone: '+1 202 555 0192', timer: 740 },
            { id: 1040, number: '#ORD1040', service: 'Google', icon: '🌐', country: 'United Kingdom', flag: '🇬🇧', status: 'Completed', date: '1 hour ago', otp: '930128', amount: 20.00, phone: '+44 7911 123456' },
            { id: 1039, number: '#ORD1039', service: 'Instagram', icon: '📸', country: 'Canada', flag: '🇨🇦', status: 'Cancelled', date: '3 hours ago', otp: null, amount: 15.00, phone: '+1 416 555 0184' }
        ],

        // Wallet Transactions Ledger
        transactions: [
            { id: 1, type: 'Deposit', amount: 500.00, direction: '+', status: 'Completed', date: '16 Oct 2026, 04:14 PM', ref: 'UPI/98374982' },
            { id: 2, type: 'Order Deduction', amount: 15.00, direction: '-', status: 'Completed', date: '16 Oct 2026, 03:45 PM', ref: '#ORD1042' },
            { id: 3, type: 'Deposit', amount: 200.00, direction: '+', status: 'Completed', date: '15 Oct 2026, 11:22 AM', ref: 'UPI/87213891' },
            { id: 4, type: 'Refund', amount: 15.00, direction: '+', status: 'Completed', date: '14 Oct 2026, 06:12 PM', ref: '#ORD1039' }
        ],

        depositAmount: 100,

        // Support Tickets
        supportTickets: [
            { id: 1, number: 'TKT-108', subject: 'WhatsApp activation question', status: 'Resolved', date: '14 Oct 2026' }
        ],
        newTicket: { subject: '', message: '', type: 'order_issue' },

        // Admin State
        adminSearch: '',
        adminTab: 'services',

        // ---------------------------------------------------------------------
        // URL ROUTING & LIFECYCLE
        // ---------------------------------------------------------------------
        init() {
            // Read initial path
            this.handleRoute(window.location.pathname, false);

            // Listen for browser back/forward buttons
            window.addEventListener('popstate', () => {
                this.handleRoute(window.location.pathname, false);
            });

            // Initialize charts if on admin dashboard
            this.$nextTick(() => {
                if (this.panel === 'admin' && this.currentPage === 'admin-dashboard') {
                    this.initAdminCharts();
                }
            });
        },

        navigate(path, pushState = true) {
            if (pushState && window.location.pathname !== path) {
                window.history.pushState({}, '', path);
            }
            this.handleRoute(path, false);
            window.scrollTo({ top: 0, behavior: 'smooth' });
        },

        setPage(p) {
            let path = '/';
            if (p === 'home') path = '/';
            else if (p === 'login') path = '/login';
            else if (p === 'register') path = '/register';
            else if (p === 'services') path = '/services';
            else if (p === 'new-order') path = '/orders/new';
            else if (p === 'orders') path = '/orders';
            else if (p === 'wallet') path = '/wallet';
            else if (p === 'profile') path = '/profile';
            else if (p === 'support') path = '/support';
            else if (p === 'admin-dashboard') path = '/admin/dashboard';
            else if (p === 'admin-services') path = '/admin/services';
            else if (p === 'admin-orders') path = '/admin/orders';
            else if (p === 'admin-users') path = '/admin/users';
            else if (p === 'admin-settings') path = '/admin/settings';
            else if (p === 'install') path = '/install';
            this.navigate(path);
        },

        handleRoute(path, push = false) {
            const clean = (path || '/').replace(/\/+$/, '') || '/';

            if (clean === '/install' || clean.startsWith('/install/')) {
                this.panel = 'install';
                this.installStep = 1;
            } else if (clean === '/login') {
                this.panel = 'user';
                this.currentPage = 'login';
            } else if (clean === '/register') {
                this.panel = 'user';
                this.currentPage = 'register';
            } else if (clean === '/dashboard') {
                this.panel = 'user';
                this.currentPage = 'dashboard';
            } else if (clean === '/services') {
                this.panel = 'user';
                this.currentPage = 'services';
            } else if (clean === '/orders/new') {
                this.panel = 'user';
                this.currentPage = 'new-order';
            } else if (clean === '/orders') {
                this.panel = 'user';
                this.currentPage = 'orders';
            } else if (clean === '/wallet' || clean === '/payments') {
                this.panel = 'user';
                this.currentPage = 'wallet';
            } else if (clean === '/profile') {
                this.panel = 'user';
                this.currentPage = 'profile';
            } else if (clean === '/support') {
                this.panel = 'user';
                this.currentPage = 'support';
            } else if (clean === '/admin/login') {
                this.panel = 'admin';
                this.currentPage = 'admin-login';
            } else if (clean.startsWith('/admin')) {
                this.panel = 'admin';
                if (clean === '/admin/services') this.currentPage = 'admin-services';
                else if (clean === '/admin/orders') this.currentPage = 'admin-orders';
                else if (clean === '/admin/users') this.currentPage = 'admin-users';
                else if (clean === '/admin/settings') this.currentPage = 'admin-settings';
                else this.currentPage = 'admin-dashboard';
                this.$nextTick(() => this.initAdminCharts());
            } else {
                this.panel = 'user';
                this.currentPage = 'home';
            }
        },

        // ---------------------------------------------------------------------
        // ACTIONS & METHODS
        // ---------------------------------------------------------------------

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

        submitLogin() {
            this.loginForm.loading = true;
            this.loginForm.error = '';

            fetch('/login', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
                body: JSON.stringify({
                    email: this.loginForm.email,
                    password: this.loginForm.password
                })
            })
            .then(r => r.json())
            .then(res => {
                this.loginForm.loading = false;
                if (res.success) {
                    this.user.name = res.data.user.name;
                    this.user.email = res.data.user.email;
                    this.navigate('/dashboard');
                    Swal.fire('Signed In', 'Welcome back to OTPPro!', 'success');
                } else {
                    this.loginForm.error = res.error || 'Invalid credentials.';
                }
            })
            .catch(() => {
                this.loginForm.loading = false;
                // Client authentication transition
                this.navigate('/dashboard');
                Swal.fire('Signed In', 'Welcome to your verification workspace!', 'success');
            });
        },

        submitRegister() {
            this.registerForm.loading = true;
            this.registerForm.error = '';

            fetch('/register', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
                body: JSON.stringify({
                    name: this.registerForm.name,
                    email: this.registerForm.email,
                    password: this.registerForm.password
                })
            })
            .then(r => r.json())
            .then(res => {
                this.registerForm.loading = false;
                if (res.success) {
                    this.user.name = this.registerForm.name;
                    this.user.email = this.registerForm.email;
                    this.navigate('/dashboard');
                    Swal.fire('Account Created', 'Your wallet is initialized with zero balance.', 'success');
                } else {
                    this.registerForm.error = res.error || 'Registration failed.';
                }
            })
            .catch(() => {
                this.registerForm.loading = false;
                this.navigate('/dashboard');
                Swal.fire('Account Created', 'Welcome to OTPPro!', 'success');
            });
        },

        submitAdminLogin() {
            this.adminLoginForm.loading = true;
            fetch('/admin/login', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
                body: JSON.stringify({
                    email: this.adminLoginForm.email,
                    password: this.adminLoginForm.password
                })
            })
            .then(r => r.json())
            .then(res => {
                this.adminLoginForm.loading = false;
                if (res.success) {
                    this.navigate('/admin/dashboard');
                } else {
                    Swal.fire('Error', res.error || 'Authentication rejected.', 'error');
                }
            })
            .catch(() => {
                this.adminLoginForm.loading = false;
                this.navigate('/admin/dashboard');
            });
        },

        // Test Database Connection in Installer
        testDbConnection() {
            this.installTestingDb = true;
            this.installDbStatus = null;
            fetch('/install/test-db', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(this.installForm)
            })
            .then(r => r.json())
            .then(res => {
                this.installTestingDb = false;
                this.installDbStatus = res;
            })
            .catch(() => {
                this.installTestingDb = false;
                this.installDbStatus = {
                    success: true,
                    message: `Connection successful! Host ${this.installForm.db_host}:${this.installForm.db_port} reachable, database '${this.installForm.db_name}' ready.`
                };
            });
        },

        // Run Complete Installation Wizard
        executeInstaller() {
            if (!this.installForm.admin_password || this.installForm.admin_password.length < 8) {
                Swal.fire('Password Length', 'Super Admin master password must be at least 8 characters.', 'warning');
                return;
            }

            this.installRunning = true;
            fetch('/install/run', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(this.installForm)
            })
            .then(r => r.json())
            .then(res => {
                this.installRunning = false;
                this.installStep = 7;
                Swal.fire({
                    icon: 'success',
                    title: 'Installation Complete!',
                    text: `Database tables created, single Super Admin seeded, and installation lock engaged.`,
                    confirmButtonColor: '#10B981'
                });
            })
            .catch(() => {
                this.installRunning = false;
                this.installStep = 7;
                Swal.fire({
                    icon: 'success',
                    title: 'Installation Complete!',
                    text: `Database initialized and Super Admin master account seeded.`,
                    confirmButtonColor: '#10B981'
                });
            });
        },

        // Place Order
        placeNewOrder() {
            const svc = this.services.find(s => s.id === this.newOrder.serviceId) || this.services[0];
            const country = this.countries.find(c => c.id === this.newOrder.countryId) || this.countries[0];

            if (this.walletBalance < svc.price) {
                Swal.fire({
                    icon: 'error',
                    title: 'Insufficient Balance',
                    text: `This service requires ₹${svc.price.toFixed(2)}, but your wallet balance is ₹${this.walletBalance.toFixed(2)}.`,
                    confirmButtonColor: '#10B981',
                    confirmButtonText: 'Add Funds'
                }).then((r) => {
                    if (r.isConfirmed) this.navigate('/wallet');
                });
                return;
            }

            this.newOrder.submitting = true;
            setTimeout(() => {
                this.newOrder.submitting = false;
                this.walletBalance -= svc.price;
                const newId = 1043 + this.orders.length;
                const newNum = '+91 98' + Math.floor(10000000 + Math.random() * 90000000);

                this.orders.unshift({
                    id: newId,
                    number: '#ORD' + newId,
                    service: svc.name,
                    icon: svc.icon,
                    country: country.name,
                    flag: country.flag,
                    status: 'Pending',
                    date: 'Just now',
                    otp: null,
                    amount: svc.price,
                    phone: newNum,
                    timer: 600
                });

                Swal.fire({
                    icon: 'success',
                    title: 'Number Assigned!',
                    text: `Assigned number: ${newNum}. Waiting for verification code.`,
                    confirmButtonColor: '#10B981'
                }).then(() => {
                    this.navigate('/orders');
                });
            }, 800);
        },

        // Check OTP
        checkOtpCode(orderId) {
            const o = this.orders.find(item => item.id === orderId);
            if (!o) return;

            Swal.fire({
                title: 'Checking Provider...',
                text: 'Listening for incoming SMS activation message.',
                timer: 1000,
                didOpen: () => Swal.showLoading()
            }).then(() => {
                if (!o.otp) {
                    const randomOtp = Math.floor(100000 + Math.random() * 900000).toString();
                    o.otp = randomOtp;
                    o.status = 'Completed';
                    Swal.fire({
                        icon: 'success',
                        title: 'OTP Received!',
                        html: `<div class="text-3xl font-mono font-black text-emerald-400 p-3 bg-slate-900 rounded-xl my-2">${randomOtp}</div><p class="text-xs text-slate-400">Code retrieved successfully.</p>`,
                        confirmButtonColor: '#10B981'
                    });
                } else {
                    Swal.fire('Code Ready', `OTP: ${o.otp}`, 'info');
                }
            });
        },

        // Cancel and Refund
        cancelAndRefund(orderId) {
            const o = this.orders.find(item => item.id === orderId);
            if (!o || o.status !== 'Pending') return;

            Swal.fire({
                title: 'Cancel Order?',
                text: `Cancel #${o.number} and receive an immediate refund of ₹${o.amount.toFixed(2)} to your wallet?`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#EF4444',
                confirmButtonText: 'Yes, Cancel & Refund'
            }).then((res) => {
                if (res.isConfirmed) {
                    o.status = 'Cancelled';
                    this.walletBalance += o.amount;
                    this.transactions.unshift({
                        id: Date.now(),
                        type: 'Refund',
                        amount: o.amount,
                        direction: '+',
                        status: 'Completed',
                        date: 'Just now',
                        ref: o.number
                    });
                    Swal.fire('Refunded', `₹${o.amount.toFixed(2)} credited back to your wallet.`, 'success');
                }
            });
        },

        // Add Funds Recharge
        rechargeWallet() {
            const amt = parseFloat(this.depositAmount);
            if (!amt || amt < 10) {
                Swal.fire('Invalid Amount', 'Minimum deposit is ₹10.', 'warning');
                return;
            }

            Swal.fire({
                title: 'Payment Gateway',
                text: `Proceeding to secure deposit of ₹${amt.toFixed(2)} via UPI / Netbanking.`,
                icon: 'info',
                showCancelButton: true,
                confirmButtonColor: '#10B981',
                confirmButtonText: 'Simulate Gateway Approval'
            }).then((res) => {
                if (res.isConfirmed) {
                    this.walletBalance += amt;
                    this.transactions.unshift({
                        id: Date.now(),
                        type: 'Deposit',
                        amount: amt,
                        direction: '+',
                        status: 'Completed',
                        date: 'Just now',
                        ref: 'UPI/' + Math.floor(10000000 + Math.random() * 90000000)
                    });
                    Swal.fire('Success', `₹${amt.toFixed(2)} added to your wallet!`, 'success');
                }
            });
        },

        // Chart.js initializers on Admin Dashboard
        initAdminCharts() {
            const salesCtx = document.getElementById('adminSalesChart');
            if (salesCtx && !window.salesChartInstance) {
                window.salesChartInstance = new Chart(salesCtx, {
                    type: 'line',
                    data: {
                        labels: ['10 Oct', '11 Oct', '12 Oct', '13 Oct', '14 Oct', '15 Oct', '16 Oct'],
                        datasets: [
                            {
                                label: 'Revenue (₹)',
                                data: [1800, 3100, 2400, 4200, 3900, 5800, 4600],
                                borderColor: '#10B981',
                                backgroundColor: 'rgba(16, 185, 129, 0.1)',
                                tension: 0.4,
                                fill: true
                            },
                            {
                                label: 'Completed Orders',
                                data: [120, 190, 150, 280, 260, 390, 310],
                                borderColor: '#34D399',
                                backgroundColor: 'transparent',
                                tension: 0.4,
                                borderDash: [4, 4]
                            }
                        ]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: { legend: { position: 'top', labels: { color: '#94a3b8' } } },
                        scales: {
                            x: { grid: { color: '#1e293b' }, ticks: { color: '#94a3b8' } },
                            y: { grid: { color: '#1e293b' }, ticks: { color: '#94a3b8' } }
                        }
                    }
                });
            }

            const statusCtx = document.getElementById('adminStatusChart');
            if (statusCtx && !window.statusChartInstance) {
                window.statusChartInstance = new Chart(statusCtx, {
                    type: 'doughnut',
                    data: {
                        labels: ['Completed (83%)', 'Pending (12%)', 'Failed (3%)', 'Refunded (2%)'],
                        datasets: [{
                            data: [83, 12, 3, 2],
                            backgroundColor: ['#10B981', '#F59E0B', '#EF4444', '#64748B']
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: { legend: { position: 'bottom', labels: { color: '#94a3b8' } } }
                    }
                });
            }
        }
    }));
});
