-- ============================================================================
-- OTPPro Platform - Production Database Indexes & Constraints
-- Optimizes high-throughput queries, live stock polling, and financial idempotency
-- ============================================================================

-- Fast lookup for active services by category and priority
CREATE INDEX `idx_services_category_sort` ON `services` (`category`, `is_active`, `sort_order`);

-- Fast lookup for country code and status
CREATE INDEX `idx_countries_code_active` ON `countries` (`iso_code`, `is_active`);

-- Composite index for real-time stock and price queries
CREATE INDEX `idx_prov_serv_stock_query` ON `provider_services` (`service_id`, `country_id`, `stock_status`, `is_active`, `selling_price`);

-- Composite index for user dashboard orders table with status filter and descending timestamp
CREATE INDEX `idx_orders_user_status_date` ON `orders` (`user_id`, `status`, `created_at` DESC);

-- Index for cron job: finding pending orders that need status check or expiration
CREATE INDEX `idx_orders_pending_expires` ON `orders` (`status`, `expires_at`, `retry_count`);

-- Composite index for wallet transaction ledger with pagination
CREATE INDEX `idx_wallet_tx_ledger` ON `wallet_transactions` (`user_id`, `type`, `created_at` DESC);

-- Fast lookup for payments by gateway transaction reference
CREATE INDEX `idx_payments_gw_lookup` ON `payments` (`gateway`, `gateway_transaction_id`, `status`);

-- Index for cron job: pending payments verification
CREATE INDEX `idx_payments_pending_verify` ON `payments` (`status`, `created_at`);

-- Index for unread notifications count query
CREATE INDEX `idx_notifications_unread_count` ON `notifications` (`user_id`, `is_read`);

-- Index for open support tickets by status and updated time
CREATE INDEX `idx_tickets_open_queue` ON `support_tickets` (`status`, `priority`, `last_reply_at` DESC);

-- Index for admin audit trails by date range
CREATE INDEX `idx_audit_date_range` ON `admin_audit_logs` (`created_at` DESC);

-- Index for provider sync health monitoring
CREATE INDEX `idx_sync_logs_recent_status` ON `sync_logs` (`provider_id`, `status`, `created_at` DESC);
