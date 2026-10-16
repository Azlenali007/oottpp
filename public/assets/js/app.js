/**
 * OTPPro - Alpine.js Application Logic
 * 
 * Drives all interactive UI elements from the reference screenshot:
 * - User Dashboard, Quick Actions, Stats
 * - Services Catalog with Categories & Instant Search
 * - 4-Step New Order Stepper
 * - Live Orders Monitoring with OTP Retrieval & Copy-to-clipboard
 * - Wallet Balance, Add Funds Modal, and Transaction Ledger
 * - Super Admin Dashboard with Sales & Status Chart.js visualizers
 * - Admin Services Management with live price updates
 * - Admin Orders Management & Refund interventions
 */

document.addEventListener('alpine:init', () => {
    Alpine.data('otpApp', () => ({
        // View States
        panel: 'user', // 'user', 'admin', or 'install'
        currentPage: 'home', // 'home', 'services', 'new-order', 'orders', 'wallet', 'profile', 'support', 'admin-services', 'admin-orders', 'admin-users', 'admin-settings'
        theme: 'light',
        mobileMenuOpen: false,

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
            admin_password: ''
        },

        // User Data
        user: {
            name: 'Rahul Kumar',
            email: 'rahul@example.com',
            phone: '+91 98765 43210',
            role: 'user',
            avatar: 'R'
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
        categories: ['All', 'Social Media', 'Google', 'Microsoft', 'Email', 'Banking', 'Other'],
        selectedCategory: 'All',
        searchServiceQuery: '',

        // Master Services Catalog (From reference screenshot)
        services: [
            { id: 1, name: 'WhatsApp', slug: 'whatsapp', category: 'Social Media', price: 1.20, stock: '90% Stock', stockQty: 1250, popular: true, icon: 'whatsapp', color: '#25D366' },
            { id: 2, name: 'Instagram', slug: 'instagram', category: 'Social Media', price: 1.50, stock: '90% Stock', stockQty: 980, popular: true, icon: 'instagram', color: '#E1306C' },
            { id: 3, name: 'Facebook', slug: 'facebook', category: 'Social Media', price: 1.30, stock: '97% Stock', stockQty: 1100, popular: false, icon: 'facebook', color: '#1877F2' },
            { id: 4, name: 'Google', slug: 'google', category: 'Google', price: 2.00, stock: '94% Stock', stockQty: 850, popular: false, icon: 'google', color: '#4285F4' },
            { id: 5, name: 'X', slug: 'x', category: 'Social Media', price: 1.80, stock: '90% Stock', stockQty: 600, popular: false, icon: 'x', color: '#000000' },
            { id: 6, name: 'Telegram', slug: 'telegram', category: 'Social Media', price: 1.40, stock: '90% Stock', stockQty: 1400, popular: false, icon: 'telegram', color: '#229ED9' },
            { id: 7, name: 'Microsoft', slug: 'microsoft', category: 'Microsoft', price: 2.50, stock: '90% Stock', stockQty: 430, popular: false, icon: 'microsoft', color: '#00A4EF' },
            { id: 8, name: 'Amazon', slug: 'amazon', category: 'Other', price: 2.20, stock: '93% Stock', stockQty: 520, popular: false, icon: 'amazon', color: '#FF9900' },
        ],

        // Supported Countries
        countries: [
            { id: 1, name: 'India', code: 'IN', flag: '🇮🇳', dial: '+91', rate: 1.0 },
            { id: 2, name: 'USA', code: 'US', flag: '🇺🇸', dial: '+1', rate: 1.2 },
            { id: 3, name: 'UK', code: 'GB', flag: '🇬🇧', dial: '+44', rate: 1.4 },
            { id: 4, name: 'Canada', code: 'CA', flag: '🇨🇦', dial: '+1', rate: 1.3 },
        ],

        // New Order Form State
        newOrder: {
            step: 1,
            serviceId: 1,
            countryId: 1,
            quantity: 1,
            unitPrice: 1.20,
            totalAmount: 1.20,
            deliveryTime: '1 - 10 minutes',
            successRate: '98%',
            stockAvailable: '1000+'
        },

        // Orders List (Matching reference screenshot)
        ordersFilter: 'all',
        ordersSearchQuery: '',
        orders: [
            { id: 1234, number: '#ORD1234', service: 'WhatsApp', icon: 'whatsapp', country: 'India', flag: '🇮🇳', status: 'Completed', date: '10 min ago', otp: '48291', amount: 1.20, phone: '+91 98765 12345' },
            { id: 1233, number: '#ORD1233', service: 'Instagram', icon: 'instagram', country: 'USA', flag: '🇺🇸', status: 'Pending', date: '25 min ago', otp: null, amount: 1.50, phone: '+1 202 555 0192', timer: 840 },
            { id: 1232, number: '#ORD1232', service: 'Google', icon: 'google', country: 'UK', flag: '🇬🇧', status: 'Completed', date: '1 hour ago', otp: '77120', amount: 2.00, phone: '+44 7911 123456' },
            { id: 1231, number: '#ORD1231', service: 'Facebook', icon: 'facebook', country: 'Canada', flag: '🇨🇦', status: 'Failed', date: '2 hours ago', otp: null, amount: 1.30, phone: '+1 416 555 0184' }
        ],

        // Wallet Transactions Ledger
        transactions: [
            { id: 1, type: 'Deposit', amount: 500.00, direction: '+', status: 'Completed', date: '10 Apr 2025, 02:14 PM', ref: 'UPI/98374982' },
            { id: 2, type: 'Order Deduction', amount: 1.20, direction: '-', status: 'Completed', date: '10 Apr 2025, 01:45 PM', ref: '#ORD1234' },
            { id: 3, type: 'Deposit', amount: 300.00, direction: '+', status: 'Completed', date: '9 Apr 2025, 11:22 AM', ref: 'UPI/87213891' },
            { id: 4, type: 'Refund', amount: 1.20, direction: '+', status: 'Completed', date: '8 Apr 2025, 06:12 PM', ref: '#ORD1230' }
        ],

        // Support Tickets
        tickets: [
            { id: 1234, number: '#TKT1234', subject: 'Order not received', date: '10 Apr 2025', status: 'Open' },
            { id: 1233, number: '#TKT1233', subject: 'Payment issue', date: '8 Apr 2025', status: 'Pending' },
            { id: 1232, number: '#TKT1232', subject: 'Service not working', date: '6 Apr 2025', status: 'Resolved' }
        ],

        // Modal States
        addFundsModalOpen: false,
        depositAmount: 300,
        selectedPaymentMethod: 'upi',
        orderDetailsModalOpen: false,
        selectedOrder: null,
        newTicketModalOpen: false,
        ticketSubject: '',
        ticketCategory: 'order_issue',

        // Super Admin Data
        adminKpis: {
            totalUsers: 1248,
            totalOrders: 8532,
            totalRevenue: 18420,
            totalProfit: 12340
        },

        init() {
            this.recalculateNewOrderPrice();

            // Periodic OTP checker simulation for pending orders
            setInterval(() => {
                this.orders.forEach(order => {
                    if (order.status === 'Pending' && !order.otp) {
                        // countdown timer
                        if (order.timer && order.timer > 0) {
                            order.timer -= 1;
                        }
                    }
                });
            }, 1000);

            // Re-render chart when switching to admin dashboard
            this.$watch('currentPage', (val) => {
                if (val === 'admin-dashboard' || this.panel === 'admin') {
                    this.$nextTick(() => this.initAdminCharts());
                }
            });
        },

        // Navigate page
        setPage(page) {
            this.currentPage = page;
            this.mobileMenuOpen = false;
            window.scrollTo({ top: 0, behavior: 'smooth' });
        },

        // Filtered Services List
        get filteredServices() {
            return this.services.filter(s => {
                const matchCat = this.selectedCategory === 'All' || s.category === this.selectedCategory;
                const matchSearch = s.name.toLowerCase().includes(this.searchServiceQuery.toLowerCase());
                return matchCat && matchSearch;
            });
        },

        // Filtered Orders List
        get filteredOrders() {
            return this.orders.filter(o => {
                const matchFilter = this.ordersFilter === 'all' || o.status.toLowerCase() === this.ordersFilter.toLowerCase();
                const matchSearch = o.number.toLowerCase().includes(this.ordersSearchQuery.toLowerCase()) ||
                                    o.service.toLowerCase().includes(this.ordersSearchQuery.toLowerCase());
                return matchFilter && matchSearch;
            });
        },

        // Initiate New Order Selection from Catalog
        selectServiceToOrder(service) {
            this.newOrder.serviceId = service.id;
            this.recalculateNewOrderPrice();
            this.setPage('new-order');
        },

        // Recalculate price in New Order stepper
        recalculateNewOrderPrice() {
            const serv = this.services.find(s => s.id == this.newOrder.serviceId) || this.services[0];
            const country = this.countries.find(c => c.id == this.newOrder.countryId) || this.countries[0];
            const basePrice = serv ? serv.price : 1.20;
            const countryMultiplier = country ? country.rate : 1.0;
            this.newOrder.unitPrice = Number((basePrice * countryMultiplier).toFixed(2));
            this.newOrder.totalAmount = Number((this.newOrder.unitPrice * this.newOrder.quantity).toFixed(2));
        },

        // Execute Order Placement
        placeOrder() {
            if (this.walletBalance < this.newOrder.totalAmount) {
                Swal.fire({
                    icon: 'error',
                    title: 'Insufficient Balance',
                    text: `Your balance is ₹ ${this.walletBalance.toFixed(2)}, but ₹ ${this.newOrder.totalAmount.toFixed(2)} is required. Please add funds.`,
                    confirmButtonText: 'Add Funds',
                    confirmButtonColor: '#2563EB'
                }).then((res) => {
                    if (res.isConfirmed) {
                        this.addFundsModalOpen = true;
                    }
                });
                return;
            }

            // Deduct balance atomically
            this.walletBalance = Number((this.walletBalance - this.newOrder.totalAmount).toFixed(2));

            const serv = this.services.find(s => s.id == this.newOrder.serviceId);
            const country = this.countries.find(c => c.id == this.newOrder.countryId);
            const newOrderNumber = '#ORD' + Math.floor(1000 + Math.random() * 9000);
            const randomPhone = country.dial + ' ' + Math.floor(7000000000 + Math.random() * 2999999999);

            const created = {
                id: Math.floor(Math.random() * 90000),
                number: newOrderNumber,
                service: serv.name,
                icon: serv.icon,
                country: country.name,
                flag: country.flag,
                status: 'Pending',
                date: 'Just now',
                otp: null,
                amount: this.newOrder.totalAmount,
                phone: randomPhone,
                timer: 900 // 15 mins
            };

            this.orders.unshift(created);
            this.stats.totalOrders += 1;
            this.stats.pending += 1;

            // Record transaction
            this.transactions.unshift({
                id: Date.now(),
                type: 'Order Deduction',
                amount: this.newOrder.totalAmount,
                direction: '-',
                status: 'Completed',
                date: new Date().toLocaleDateString('en-GB', { day: 'numeric', month: 'short', year: 'numeric', hour: '2-digit', minute: '2-digit' }),
                ref: newOrderNumber
            });

            Swal.fire({
                icon: 'success',
                title: 'Order Placed Successfully!',
                html: `Phone Number Allocated: <b class="text-blue-600 font-mono">${randomPhone}</b><br><small class="text-gray-500">Waiting for incoming SMS/OTP...</small>`,
                confirmButtonText: 'View Orders',
                confirmButtonColor: '#10B981'
            }).then(() => {
                this.setPage('orders');
            });

            // Simulate OTP arrival in 6 seconds for testing
            setTimeout(() => {
                if (created.status === 'Pending') {
                    const otp = Math.floor(10000 + Math.random() * 90000).toString();
                    created.otp = otp;
                    created.status = 'Completed';
                    this.stats.pending = Math.max(0, this.stats.pending - 1);
                    this.stats.completed += 1;

                    Swal.fire({
                        icon: 'info',
                        title: `OTP Received for ${created.service}!`,
                        html: `<span class="text-3xl font-extrabold tracking-widest text-emerald-600 bg-emerald-50 px-4 py-2 rounded-lg border border-emerald-200">${otp}</span>`,
                        confirmButtonText: 'Copy OTP',
                        confirmButtonColor: '#10B981'
                    }).then(() => {
                        navigator.clipboard.writeText(otp);
                    });
                }
            }, 6000);
        },

        // Cancel order & issue instant refund
        cancelOrder(order) {
            Swal.fire({
                title: 'Cancel this verification?',
                text: 'The full amount will be refunded directly to your wallet.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#EF4444',
                cancelButtonColor: '#6B7280',
                confirmButtonText: 'Yes, Cancel & Refund'
            }).then((result) => {
                if (result.isConfirmed) {
                    order.status = 'Cancelled';
                    this.stats.pending = Math.max(0, this.stats.pending - 1);
                    this.walletBalance = Number((this.walletBalance + order.amount).toFixed(2));

                    this.transactions.unshift({
                        id: Date.now(),
                        type: 'Refund',
                        amount: order.amount,
                        direction: '+',
                        status: 'Completed',
                        date: new Date().toLocaleDateString('en-GB', { day: 'numeric', month: 'short', year: 'numeric', hour: '2-digit', minute: '2-digit' }),
                        ref: order.number
                    });

                    Swal.fire('Refunded!', `₹ ${order.amount.toFixed(2)} has been credited to your wallet.`, 'success');
                }
            });
        },

        // Add Funds Process
        processAddFunds() {
            const amount = Number(this.depositAmount);
            if (amount < 10) {
                Swal.fire('Error', 'Minimum deposit is ₹ 10', 'error');
                return;
            }

            this.walletBalance = Number((this.walletBalance + amount).toFixed(2));
            this.addFundsModalOpen = false;

            this.transactions.unshift({
                id: Date.now(),
                type: 'Deposit',
                amount: amount,
                direction: '+',
                status: 'Completed',
                date: new Date().toLocaleDateString('en-GB', { day: 'numeric', month: 'short', year: 'numeric', hour: '2-digit', minute: '2-digit' }),
                ref: 'UPI/' + Math.floor(10000000 + Math.random() * 90000000)
            });

            Swal.fire({
                icon: 'success',
                title: 'Wallet Recharged!',
                text: `Successfully added ₹ ${amount.toFixed(2)} to your balance.`,
                confirmButtonColor: '#10B981'
            });
        },

        // Copy OTP to clipboard helper
        copyOtp(code) {
            if (!code) return;
            navigator.clipboard.writeText(code);
            Swal.fire({
                toast: true,
                position: 'top-end',
                icon: 'success',
                title: `OTP ${code} copied!`,
                showConfirmButton: false,
                timer: 2000
            });
        },

        // Submit new support ticket
        submitTicket() {
            if (!this.ticketSubject.trim()) {
                Swal.fire('Error', 'Please provide a ticket subject.', 'error');
                return;
            }

            const ticketNumber = '#TKT' + Math.floor(1000 + Math.random() * 9000);
            this.tickets.unshift({
                id: Date.now(),
                number: ticketNumber,
                subject: this.ticketSubject,
                date: 'Just now',
                status: 'Open'
            });

            this.ticketSubject = '';
            this.newTicketModalOpen = false;

            Swal.fire('Ticket Created', `Your support ticket ${ticketNumber} has been logged. Our team will respond shortly.`, 'success');
        },

        // Admin Service Price Editor
        editServicePrice(service) {
            Swal.fire({
                title: `Edit Selling Price for ${service.name}`,
                input: 'number',
                inputLabel: 'Selling Price (₹)',
                inputValue: service.price,
                showCancelButton: true,
                confirmButtonText: 'Update Price',
                confirmButtonColor: '#2563EB',
                inputValidator: (value) => {
                    if (!value || Number(value) <= 0) {
                        return 'Please enter a valid price';
                    }
                }
            }).then((result) => {
                if (result.isConfirmed) {
                    service.price = Number(Number(result.value).toFixed(2));
                    Swal.fire('Updated!', `${service.name} price updated to ₹ ${service.price.toFixed(2)}`, 'success');
                }
            });
        },

        // Admin Trigger Provider Sync
        syncProviderApi() {
            Swal.fire({
                title: 'Synchronizing with SMS Provider...',
                text: 'Refreshing stock, price feeds, and countries.',
                allowOutsideClick: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });

            setTimeout(() => {
                Swal.fire({
                    icon: 'success',
                    title: 'Synchronization Complete',
                    text: 'Successfully refreshed 48 services and 24 country routes.',
                    confirmButtonColor: '#10B981'
                });
            }, 1500);
        },

        // Test Database Connection in Installer
        testDbConnection() {
            this.installTestingDb = true;
            this.installDbStatus = null;
            setTimeout(() => {
                this.installTestingDb = false;
                this.installDbStatus = {
                    success: true,
                    message: `Connection successful! Host ${this.installForm.db_host}:${this.installForm.db_port} reachable, database '${this.installForm.db_name}' ready.`
                };
            }, 600);
        },

        // Run Complete Installation Wizard
        executeInstaller() {
            if (!this.installForm.admin_password || this.installForm.admin_password.length < 8) {
                Swal.fire('Password Length', 'Super Admin master password must be at least 8 characters.', 'warning');
                return;
            }

            this.installRunning = true;
            setTimeout(() => {
                this.installRunning = false;
                this.installStep = 7;
                Swal.fire({
                    icon: 'success',
                    title: 'Installation Complete!',
                    text: `Database tables created, single Super Admin '${this.installForm.admin_email}' seeded, and installation lock engaged.`,
                    confirmButtonColor: '#10B981'
                });
            }, 1200);
        },

        // Chart.js initializers on Admin Dashboard
        initAdminCharts() {
            const salesCtx = document.getElementById('adminSalesChart');
            if (salesCtx && !window.salesChartInstance) {
                window.salesChartInstance = new Chart(salesCtx, {
                    type: 'line',
                    data: {
                        labels: ['10 Apr', '11 Apr', '12 Apr', '13 Apr', '14 Apr', '15 Apr', '16 Apr'],
                        datasets: [
                            {
                                label: 'Sales (₹)',
                                data: [1800, 3100, 2400, 4200, 3900, 5800, 4600],
                                borderColor: '#3B82F6',
                                backgroundColor: 'rgba(59, 130, 246, 0.1)',
                                tension: 0.4,
                                fill: true
                            },
                            {
                                label: 'Orders',
                                data: [1200, 1900, 1500, 2800, 2600, 3900, 3100],
                                borderColor: '#8B5CF6',
                                backgroundColor: 'transparent',
                                tension: 0.4,
                                borderDash: [4, 4]
                            }
                        ]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: { legend: { position: 'top' } }
                    }
                });
            }

            const statusCtx = document.getElementById('adminStatusChart');
            if (statusCtx && !window.statusChartInstance) {
                window.statusChartInstance = new Chart(statusCtx, {
                    type: 'doughnut',
                    data: {
                        labels: ['Completed (78%)', 'Pending (15%)', 'Failed (5%)', 'Cancelled (2%)'],
                        datasets: [{
                            data: [78, 15, 5, 2],
                            backgroundColor: ['#10B981', '#F59E0B', '#EF4444', '#6B7280']
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: { legend: { position: 'bottom' } }
                    }
                });
            }
        }
    }));
});
