# OTPPro - Production Verification Service Marketplace & Super Admin Platform

A production-grade, highly secure, modular OTP verification marketplace built strictly with **Core PHP 8.2+**, **MySQL 8+**, **Tailwind CSS**, **Alpine.js**, **SweetAlert2**, and **Chart.js**.

---

## 1. System Architecture & Tech Stack

* **Backend Engine:** Pure Core PHP 8.2+ (strictly no Laravel, Symfony, or external frameworks).
* **Database:** MySQL 8.0+ / MariaDB 10.5+ (InnoDB Engine, `utf8mb4_unicode_ci`, strict `DECIMAL(12, 4)` for financial balances).
* **Data Access:** Native PHP `PDO` with prepared statements, strict parameter binding, and ACID nested transaction management.
* **Frontend Architecture:** Semantic HTML5, Tailwind CSS, Alpine.js reactive components, SweetAlert2 modal dialogs, and Chart.js analytical charts.
* **Security & Auth:** Native session management with session ID regeneration, CSRF validation middleware, rate-limiting per IP, and password hashing via `password_hash()` (Bcrypt, cost 12).
* **Administration:** Strictly **ONE** Super Admin account enforced at the database and application levels.
* **Provider Integration:** Modular adapter pattern (`modules/Providers/ProviderInterface.php`) with implementations for SMSPool, DaisySMS, and an automated testing adapter.
* **Payment Gateways:** Modular adapter pattern (`modules/Payments/PaymentGatewayInterface.php`) supporting Instant UPI / QR Intent, Razorpay, and cryptographic HMAC-SHA256 signed webhook callbacks.
* **Real-time & Automation:** Native PHP WebSocket server (`websocket/server.php`) with polling fallback and 6 independent CLI cron processing jobs.

---

## 2. Directory Structure

```
├── app/
│   ├── Core/
│   │   ├── Database.php          # Singleton PDO connection wrapper with nested transaction support
│   │   ├── Request.php           # HTTP request parser (methods, headers, JSON body, IP)
│   │   ├── Response.php          # HTTP response, headers, JSON formatter & redirects
│   │   ├── Router.php            # REST router with parameter matching ({id}) & middleware pipeline
│   │   ├── Session.php           # Secure native session wrapper, flash messages & CSRF generator
│   │   └── View.php              # View template renderer with layout support & XSS escaping
│   ├── Controllers/
│   │   ├── AuthController.php    # User & Super Admin login, registration, logout
│   │   ├── DashboardController.php # User dashboard metrics & recent orders
│   │   ├── ServiceController.php # Catalog browsing, category filtering & pricing lookups
│   │   ├── OrderController.php   # Stepper wizard, order placement, OTP polling & cancellation
│   │   ├── WalletController.php  # Balance dashboard & transaction ledger
│   │   ├── PaymentController.php # Deposit initiation & payment gateway redirect
│   │   ├── ProfileController.php # Profile details & password management
│   │   ├── SupportController.php # Support ticket creation & thread tracking
│   │   ├── WebhookController.php # Inbound payment gateway webhook verification & crediting
│   │   └── Admin/
│   │       ├── AdminDashboardController.php # High-level KPIs, sales charts & order status donut
│   │       ├── AdminServiceController.php   # Catalog import, custom price markups & toggles
│   │       ├── AdminOrderController.php     # Platform order monitoring & refund interventions
│   │       ├── AdminUserController.php      # User management, order inspection & account blocking
│   │       └── AdminSettingController.php   # Provider connection testing & audit logs
│   ├── Services/
│   │   ├── AuthService.php       # User creation & credential verification
│   │   ├── WalletService.php     # Atomic double-entry credits, debits & balance checks
│   │   ├── StockService.php      # Live stock availability & quantity tracking
│   │   ├── PricingService.php    # Dynamic selling price calculation & currency formatting
│   │   ├── ProviderService.php   # Provider catalog synchronization & health monitoring
│   │   ├── OrderService.php      # Order placement, phone number allocation & OTP delivery
│   │   ├── RefundService.php     # Atomic order refund processing with double-refund protection
│   │   ├── PaymentService.php    # Payment gateway orchestration & idempotent finalization
│   │   └── NotificationService.php # In-app notification dispatcher
│   ├── Middleware/
│   │   ├── AuthMiddleware.php    # Protected user session guard
│   │   ├── AdminMiddleware.php   # Super Admin role verification guard (role === 'super_admin')
│   │   ├── CsrfMiddleware.php    # State-changing CSRF token verification
│   │   └── RateLimitMiddleware.php # Cache-based IP rate limiter
│   └── Helpers/
│       ├── Security.php          # UUID generator, order number formatter & AES-256 encryption
│       ├── Validator.php         # Declarative form & JSON payload validation engine
│       ├── Logger.php            # Channel-based file logging to storage/logs/
│       └── ResponseHelper.php    # Standardized API JSON response emitter
├── config/
│   ├── app.php                   # Platform configuration (name, timezone, currency)
│   ├── database.php              # MySQL PDO configuration with SQLite testing fallback
│   └── security.php              # Password policies, rate limits & session security
├── database/
│   ├── schema.sql                # Production MySQL 8+ schema (16 tables with foreign keys)
│   ├── schema_sqlite.sql         # Local testing schema for CLI test runs
│   ├── indexes.sql               # Dedicated performance & composite indexes
│   └── seed_super_admin.php      # CLI setup script enforcing the strictly single Super Admin rule
├── modules/
│   ├── Providers/
│   │   ├── ProviderInterface.php # Contract for OTP verification providers
│   │   ├── SMSPoolProvider.php   # SMSPool REST API implementation
│   │   ├── DaisySMSProvider.php  # DaisySMS API implementation
│   │   └── MockTestingProvider.php # Isolated testing adapter for local verification
│   └── Payments/
│       ├── PaymentGatewayInterface.php # Contract for deposit payment gateways
│       ├── UPIGateway.php        # Dynamic UPI QR & Intent gateway adapter
│       ├── RazorpayGateway.php   # Razorpay Orders & HMAC-SHA256 signature verification
│       └── MockTestingGateway.php# Isolated testing payment gateway
├── routes/
│   ├── web.php                   # Frontend HTML web routes
│   ├── api.php                   # REST API routes with JSON responses
│   └── admin.php                 # Protected Super Admin endpoints
├── public/
│   ├── index.php                 # Public web document root front controller
│   ├── .htaccess                 # Apache rewrite rules & security headers
│   └── assets/
│       ├── js/app.js             # Alpine.js application state, orders, timers, modals, charts
│       └── css/app.css            # Compiled styling
├── websocket/
│   ├── server.php                # Native PHP RFC 6455 WebSocket daemon
│   ├── Auth.php                  # WebSocket channel authorization
│   └── EventHandler.php          # Real-time event JSON encoder
├── cron/
│   ├── sync_services.php         # Hourly provider catalog synchronization
│   ├── sync_stock.php            # Live stock polling every 10 minutes
│   ├── check_orders.php          # Pending orders OTP polling every minute
│   ├── expire_orders.php         # Expired order cancellation & refunding every 2 minutes
│   ├── process_refunds.php       # Safe refund audit reconciliation
│   └── daily_reports.php         # Midnight analytics summary
├── storage/
│   ├── logs/                     # Application, audit, provider, and payment logs
│   ├── cache/                    # Rate-limiting tokens and cron mutex lock files
│   └── database/                 # Testing SQLite databases
├── tests/
│   ├── Phase1Test.php            # Architectural integrity test suite
│   └── FullSystemTest.php        # Comprehensive end-to-end integration test suite
├── .env.example                  # Environment configuration blueprint
└── README.md                     # Complete production guide
```

---

## 3. Installation & Setup Guide

### Prerequisites
* **PHP:** 8.2 or 8.3 with `pdo_mysql`, `pdo_sqlite`, `curl`, `mbstring`, `openssl`
* **MySQL:** 8.0+ / MariaDB 10.5+
* **Web Server:** Apache (with `mod_rewrite`) or Nginx

### Step 1: Clone and Configure Environment
```bash
cp .env.example .env
```
Edit `.env` to configure your MySQL database credentials, app URL, and provider keys:
```env
DB_CONNECTION="mysql"
DB_HOST="127.0.0.1"
DB_PORT=3306
DB_DATABASE="otppro_db"
DB_USERNAME="otppro_user"
DB_PASSWORD="your_database_password"

APP_URL="https://yourdomain.com"
APP_ENV="production"
APP_DEBUG=false
```

### Step 2: Initialize Database Schema & Indexes
Create the database and apply the schema:
```bash
mysql -u otppro_user -p otppro_db < database/schema.sql
mysql -u otppro_user -p otppro_db < database/indexes.sql
```

### Step 3: Seed the Exclusive Super Admin Account
The system enforces **strictly ONE Super Admin account**. Run the CLI setup script:
```bash
php database/seed_super_admin.php "admin@otppro.local" "YourSecureMasterPassword123!" "Master Super Admin"
```
*Note: Any subsequent attempts to create another Super Admin will be automatically rejected.*

### Step 4: Web Server Configuration
Configure your web server's document root to point to the `public/` directory.

#### Apache VirtualHost Example
```apache
<VirtualHost *:80>
    ServerName otppro.local
    DocumentRoot /var/www/otp-platform/public

    <Directory /var/www/otp-platform/public>
        AllowOverride All
        Require all granted
    </Directory>
</VirtualHost>
```

#### Nginx Configuration Example
```nginx
server {
    listen 80;
    server_name otppro.local;
    root /var/www/otp-platform/public;
    index index.php index.html;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        include fastcgi_params;
        fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
    }
}
```

### Step 5: Configure Cron Jobs (Scheduled Background Tasks)
Add the following entries to your server's crontab (`crontab -e`):
```cron
# Poll pending verification orders for incoming OTPs every minute
* * * * * php /var/www/otp-platform/cron/check_orders.php >> /dev/null 2>&1

# Expire pending orders older than 15 minutes and issue automated refunds every 2 minutes
*/2 * * * * php /var/www/otp-platform/cron/expire_orders.php >> /dev/null 2>&1

# Refresh provider stock counts every 10 minutes
*/10 * * * * php /var/www/otp-platform/cron/sync_stock.php >> /dev/null 2>&1

# Sync provider service catalog and country routes every hour
0 * * * * php /var/www/otp-platform/cron/sync_services.php >> /dev/null 2>&1

# Reconcile pending refunds every 15 minutes
*/15 * * * * php /var/www/otp-platform/cron/process_refunds.php >> /dev/null 2>&1

# Compile daily analytics and profit reports at midnight
0 0 * * * php /var/www/otp-platform/cron/daily_reports.php >> /dev/null 2>&1
```

### Step 6: Start Real-Time WebSocket Server (Optional)
If your hosting environment supports persistent processes, run:
```bash
php websocket/server.php
```
If persistent WebSockets are not available on shared hosting, the frontend automatically falls back to secure polling without interrupting the user experience.

---

## 4. Verification & Automated Test Suites

Run the architectural integrity test suite:
```bash
php tests/Phase1Test.php
```
Expected output:
```
Results: 10/10 tests passed.
PHASE 1 ARCHITECTURAL INTEGRITY VERIFIED SUCCESSFULLY.
```

Run the full end-to-end integration test suite:
```bash
php tests/FullSystemTest.php
```
Expected output:
```
========================================================
       OTPPro Full System Integration Test Suite        
========================================================
  [PASS] initializes testing database and applies schema
  [PASS] registers a user and initializes wallet with 0.0000 balance
  [PASS] credits wallet atomically and records ledger entry
  [PASS] prevents overdraft/negative balance when debiting more than available
  [PASS] synchronizes provider catalog and seeds services
  [PASS] places an OTP verification order and debits wallet
  [PASS] polls provider and receives OTP verification code
  [PASS] prevents double-refund on the same order
  [PASS] verifies payment gateway initiation and success callback
--------------------------------------------------------
Results: 9 passed, 0 failed.
[SUCCESS] ALL SYSTEM INTEGRATION TESTS PASSED PERFECTLY!
```

---

## 5. Security Architecture & Guarantees

1. **Financial Immutability:** All wallet modifications occur within atomic database transactions with row-level locks. Balances are calculated and stored using `DECIMAL(12, 4)`. Floating-point math is strictly forbidden.
2. **Double-Refund Prevention:** Guaranteed by unique database constraints on `refunds.order_id` and transactional status checks.
3. **Idempotent Webhooks:** Payment webhooks verify cryptographic signatures (HMAC-SHA256) and record unique event keys in `webhook_events`, rejecting duplicate deliveries.
4. **Credential Isolation:** API keys and payment secrets are stored outside the public web root in `.env` and can be encrypted in the database using `AES-256-CBC` via `App\Helpers\Security::encrypt()`.
5. **Single Super Admin Policy:** Hard-coded security checks prevent regular registration from creating admin accounts and block multiple Super Admin accounts from existing.
