-- ============================================================================
-- OTPPro Platform - Production Database Schema
-- Technology: MySQL 8.0+ / MariaDB 10.5+
-- Engine: InnoDB
-- Character Set: utf8mb4 / Collation: utf8mb4_unicode_ci
-- Compliant with Financial ACID standards (DECIMAL monetary fields)
-- ============================================================================

SET FOREIGN_KEY_CHECKS = 0;

DROP TABLE IF EXISTS `settings`;
DROP TABLE IF EXISTS `sync_logs`;
DROP TABLE IF EXISTS `admin_audit_logs`;
DROP TABLE IF EXISTS `support_tickets`;
DROP TABLE IF EXISTS `notifications`;
DROP TABLE IF EXISTS `webhook_events`;
DROP TABLE IF EXISTS `refunds`;
DROP TABLE IF EXISTS `payments`;
DROP TABLE IF EXISTS `orders`;
DROP TABLE IF EXISTS `provider_services`;
DROP TABLE IF EXISTS `countries`;
DROP TABLE IF EXISTS `services`;
DROP TABLE IF EXISTS `providers`;
DROP TABLE IF EXISTS `wallet_transactions`;
DROP TABLE IF EXISTS `wallets`;
DROP TABLE IF EXISTS `users`;

SET FOREIGN_KEY_CHECKS = 1;

-- ----------------------------------------------------------------------------
-- 1. USERS TABLE
-- Single Super Admin enforced via role check; regular users register with email
-- ----------------------------------------------------------------------------
CREATE TABLE `users` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(100) NOT NULL,
  `email` VARCHAR(191) NOT NULL,
  `phone` VARCHAR(30) DEFAULT NULL,
  `password_hash` VARCHAR(255) NOT NULL,
  `role` ENUM('user', 'super_admin') NOT NULL DEFAULT 'user',
  `status` ENUM('active', 'blocked', 'suspended') NOT NULL DEFAULT 'active',
  `two_factor_secret` VARCHAR(255) DEFAULT NULL,
  `two_factor_enabled` TINYINT(1) NOT NULL DEFAULT 0,
  `email_verified_at` TIMESTAMP NULL DEFAULT NULL,
  `last_login_at` TIMESTAMP NULL DEFAULT NULL,
  `last_login_ip` VARCHAR(45) DEFAULT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_users_email` (`email`),
  KEY `idx_users_role` (`role`),
  KEY `idx_users_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------------
-- 2. WALLETS TABLE
-- 1-to-1 relationship with users; stores accurate DECIMAL balance
-- ----------------------------------------------------------------------------
CREATE TABLE `wallets` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` BIGINT UNSIGNED NOT NULL,
  `balance` DECIMAL(12, 4) NOT NULL DEFAULT 0.0000,
  `currency` VARCHAR(10) NOT NULL DEFAULT 'INR',
  `locked_balance` DECIMAL(12, 4) NOT NULL DEFAULT 0.0000,
  `total_deposited` DECIMAL(12, 4) NOT NULL DEFAULT 0.0000,
  `total_spent` DECIMAL(12, 4) NOT NULL DEFAULT 0.0000,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_wallets_user_id` (`user_id`),
  CONSTRAINT `fk_wallets_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `chk_wallets_balance_positive` CHECK (`balance` >= 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------------
-- 3. WALLET TRANSACTIONS TABLE
-- Immutable financial ledger of all credit/debit movements with audit trail
-- ----------------------------------------------------------------------------
CREATE TABLE `wallet_transactions` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `wallet_id` BIGINT UNSIGNED NOT NULL,
  `user_id` BIGINT UNSIGNED NOT NULL,
  `transaction_uuid` VARCHAR(64) NOT NULL,
  `type` ENUM('deposit', 'order_deduction', 'refund', 'bonus', 'adjustment') NOT NULL,
  `amount` DECIMAL(12, 4) NOT NULL,
  `balance_before` DECIMAL(12, 4) NOT NULL,
  `balance_after` DECIMAL(12, 4) NOT NULL,
  `reference_id` VARCHAR(100) DEFAULT NULL,
  `reference_type` VARCHAR(50) DEFAULT NULL,
  `description` VARCHAR(255) NOT NULL,
  `status` ENUM('completed', 'pending', 'failed', 'cancelled') NOT NULL DEFAULT 'completed',
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_tx_uuid` (`transaction_uuid`),
  KEY `idx_wallet_tx_user_created` (`user_id`, `created_at`),
  KEY `idx_wallet_tx_wallet_id` (`wallet_id`),
  KEY `idx_wallet_tx_type` (`type`),
  CONSTRAINT `fk_wallet_tx_wallet` FOREIGN KEY (`wallet_id`) REFERENCES `wallets` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_wallet_tx_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------------
-- 4. PROVIDERS TABLE
-- External OTP/SMS verification API providers
-- ----------------------------------------------------------------------------
CREATE TABLE `providers` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `code` VARCHAR(50) NOT NULL,
  `name` VARCHAR(100) NOT NULL,
  `api_url` VARCHAR(255) NOT NULL,
  `api_key_encrypted` TEXT DEFAULT NULL,
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `priority` INT NOT NULL DEFAULT 1,
  `balance` DECIMAL(12, 4) NOT NULL DEFAULT 0.0000,
  `currency` VARCHAR(10) NOT NULL DEFAULT 'USD',
  `last_synced_at` TIMESTAMP NULL DEFAULT NULL,
  `last_status_check` TIMESTAMP NULL DEFAULT NULL,
  `is_healthy` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_providers_code` (`code`),
  KEY `idx_providers_active` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------------
-- 5. SERVICES TABLE
-- Application-level verification services (WhatsApp, Instagram, Google, etc.)
-- ----------------------------------------------------------------------------
CREATE TABLE `services` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `slug` VARCHAR(64) NOT NULL,
  `name` VARCHAR(100) NOT NULL,
  `category` VARCHAR(50) NOT NULL DEFAULT 'Social Media',
  `icon` VARCHAR(100) DEFAULT NULL,
  `description` TEXT DEFAULT NULL,
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `is_popular` TINYINT(1) NOT NULL DEFAULT 0,
  `default_selling_price` DECIMAL(10, 4) NOT NULL DEFAULT 1.0000,
  `delivery_time_est` VARCHAR(50) NOT NULL DEFAULT '1 - 10 minutes',
  `success_rate_est` DECIMAL(5, 2) NOT NULL DEFAULT 98.00,
  `sort_order` INT NOT NULL DEFAULT 0,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_services_slug` (`slug`),
  KEY `idx_services_category` (`category`),
  KEY `idx_services_active` (`is_active`, `sort_order`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------------
-- 6. COUNTRIES TABLE
-- Supported verification countries with ISO code and dial code
-- ----------------------------------------------------------------------------
CREATE TABLE `countries` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `iso_code` VARCHAR(3) NOT NULL,
  `name` VARCHAR(100) NOT NULL,
  `dial_code` VARCHAR(10) NOT NULL,
  `flag_emoji` VARCHAR(10) DEFAULT NULL,
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_countries_iso` (`iso_code`),
  KEY `idx_countries_active` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------------
-- 7. PROVIDER SERVICES TABLE
-- Pivot between Provider, Service, and Country with live cost and custom selling price
-- ----------------------------------------------------------------------------
CREATE TABLE `provider_services` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `provider_id` BIGINT UNSIGNED NOT NULL,
  `service_id` BIGINT UNSIGNED NOT NULL,
  `country_id` BIGINT UNSIGNED NOT NULL,
  `provider_service_code` VARCHAR(100) NOT NULL,
  `provider_cost` DECIMAL(10, 4) NOT NULL DEFAULT 0.0000,
  `selling_price` DECIMAL(10, 4) NOT NULL DEFAULT 0.0000,
  `stock_status` ENUM('available', 'unavailable', 'checking', 'unknown') NOT NULL DEFAULT 'unknown',
  `stock_quantity` INT NOT NULL DEFAULT 0,
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `last_synced_at` TIMESTAMP NULL DEFAULT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_provider_service_country` (`provider_id`, `service_id`, `country_id`),
  KEY `idx_prov_serv_lookup` (`service_id`, `country_id`, `is_active`, `stock_status`),
  CONSTRAINT `fk_prov_serv_provider` FOREIGN KEY (`provider_id`) REFERENCES `providers` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_prov_serv_service` FOREIGN KEY (`service_id`) REFERENCES `services` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_prov_serv_country` FOREIGN KEY (`country_id`) REFERENCES `countries` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------------
-- 8. ORDERS TABLE
-- Full verification orders lifecycle (pending -> completed / cancelled / expired)
-- ----------------------------------------------------------------------------
CREATE TABLE `orders` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `order_number` VARCHAR(32) NOT NULL,
  `user_id` BIGINT UNSIGNED NOT NULL,
  `service_id` BIGINT UNSIGNED NOT NULL,
  `country_id` BIGINT UNSIGNED NOT NULL,
  `provider_id` BIGINT UNSIGNED NOT NULL,
  `provider_service_id` BIGINT UNSIGNED DEFAULT NULL,
  `provider_order_id` VARCHAR(100) DEFAULT NULL,
  `phone_number` VARCHAR(30) DEFAULT NULL,
  `otp_code` VARCHAR(20) DEFAULT NULL,
  `full_sms_text` TEXT DEFAULT NULL,
  `unit_price` DECIMAL(10, 4) NOT NULL,
  `provider_cost` DECIMAL(10, 4) NOT NULL DEFAULT 0.0000,
  `quantity` INT NOT NULL DEFAULT 1,
  `total_amount` DECIMAL(10, 4) NOT NULL,
  `status` ENUM('pending', 'completed', 'failed', 'cancelled', 'expired', 'refunded') NOT NULL DEFAULT 'pending',
  `expires_at` TIMESTAMP NOT NULL,
  `completed_at` TIMESTAMP NULL DEFAULT NULL,
  `cancelled_at` TIMESTAMP NULL DEFAULT NULL,
  `cancellation_reason` VARCHAR(255) DEFAULT NULL,
  `retry_count` INT NOT NULL DEFAULT 0,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_orders_order_number` (`order_number`),
  KEY `idx_orders_user_created` (`user_id`, `created_at`),
  KEY `idx_orders_status_expires` (`status`, `expires_at`),
  KEY `idx_orders_provider_order_id` (`provider_order_id`),
  CONSTRAINT `fk_orders_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_orders_service` FOREIGN KEY (`service_id`) REFERENCES `services` (`id`),
  CONSTRAINT `fk_orders_country` FOREIGN KEY (`country_id`) REFERENCES `countries` (`id`),
  CONSTRAINT `fk_orders_provider` FOREIGN KEY (`provider_id`) REFERENCES `providers` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------------
-- 9. PAYMENTS TABLE
-- Online wallet top-up transactions with idempotency key
-- ----------------------------------------------------------------------------
CREATE TABLE `payments` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `payment_uuid` VARCHAR(64) NOT NULL,
  `user_id` BIGINT UNSIGNED NOT NULL,
  `gateway` VARCHAR(50) NOT NULL,
  `gateway_transaction_id` VARCHAR(100) DEFAULT NULL,
  `amount` DECIMAL(12, 4) NOT NULL,
  `fee` DECIMAL(12, 4) NOT NULL DEFAULT 0.0000,
  `net_amount` DECIMAL(12, 4) NOT NULL,
  `currency` VARCHAR(10) NOT NULL DEFAULT 'INR',
  `status` ENUM('pending', 'completed', 'failed', 'cancelled') NOT NULL DEFAULT 'pending',
  `idempotency_key` VARCHAR(100) NOT NULL,
  `raw_payload` JSON DEFAULT NULL,
  `verified_at` TIMESTAMP NULL DEFAULT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_payments_uuid` (`payment_uuid`),
  UNIQUE KEY `uniq_payments_idempotency` (`idempotency_key`),
  UNIQUE KEY `uniq_payments_gw_tx` (`gateway_transaction_id`),
  KEY `idx_payments_user_status` (`user_id`, `status`),
  CONSTRAINT `fk_payments_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------------
-- 10. REFUNDS TABLE
-- Financial refund ledger ensuring zero double-refunds
-- ----------------------------------------------------------------------------
CREATE TABLE `refunds` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `refund_uuid` VARCHAR(64) NOT NULL,
  `order_id` BIGINT UNSIGNED NOT NULL,
  `user_id` BIGINT UNSIGNED NOT NULL,
  `wallet_transaction_id` BIGINT UNSIGNED DEFAULT NULL,
  `amount` DECIMAL(12, 4) NOT NULL,
  `reason` VARCHAR(255) NOT NULL,
  `status` ENUM('completed', 'rejected', 'pending') NOT NULL DEFAULT 'completed',
  `processed_by_user_id` BIGINT UNSIGNED DEFAULT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_refunds_uuid` (`refund_uuid`),
  UNIQUE KEY `uniq_refunds_order` (`order_id`),
  KEY `idx_refunds_user` (`user_id`),
  CONSTRAINT `fk_refunds_order` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_refunds_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------------
-- 11. WEBHOOK EVENTS TABLE
-- Inbound webhooks from payment gateways and provider callbacks (idempotent)
-- ----------------------------------------------------------------------------
CREATE TABLE `webhook_events` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `source` VARCHAR(50) NOT NULL,
  `event_id` VARCHAR(100) NOT NULL,
  `event_type` VARCHAR(100) NOT NULL,
  `payload` JSON NOT NULL,
  `is_processed` TINYINT(1) NOT NULL DEFAULT 0,
  `processed_at` TIMESTAMP NULL DEFAULT NULL,
  `error_message` TEXT DEFAULT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_webhook_event_id` (`source`, `event_id`),
  KEY `idx_webhook_processed` (`is_processed`, `created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------------
-- 12. NOTIFICATIONS TABLE
-- Real-time in-app alerts and notifications for users and admin
-- ----------------------------------------------------------------------------
CREATE TABLE `notifications` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` BIGINT UNSIGNED NOT NULL,
  `title` VARCHAR(150) NOT NULL,
  `message` TEXT NOT NULL,
  `type` ENUM('info', 'success', 'warning', 'danger') NOT NULL DEFAULT 'info',
  `is_read` TINYINT(1) NOT NULL DEFAULT 0,
  `action_url` VARCHAR(255) DEFAULT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_notif_user_unread` (`user_id`, `is_read`, `created_at`),
  CONSTRAINT `fk_notifications_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------------
-- 13. SUPPORT TICKETS TABLE
-- Customer support and ticket requests
-- ----------------------------------------------------------------------------
CREATE TABLE `support_tickets` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `ticket_number` VARCHAR(32) NOT NULL,
  `user_id` BIGINT UNSIGNED NOT NULL,
  `subject` VARCHAR(200) NOT NULL,
  `category` ENUM('order_issue', 'payment_issue', 'service_issue', 'account_issue', 'other') NOT NULL,
  `status` ENUM('open', 'pending', 'resolved', 'closed') NOT NULL DEFAULT 'open',
  `priority` ENUM('low', 'medium', 'high', 'urgent') NOT NULL DEFAULT 'medium',
  `last_reply_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_tickets_number` (`ticket_number`),
  KEY `idx_tickets_user` (`user_id`),
  KEY `idx_tickets_status` (`status`),
  CONSTRAINT `fk_support_tickets_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------------
-- 14. ADMIN AUDIT LOGS TABLE
-- Immutable security log of Super Admin actions
-- ----------------------------------------------------------------------------
CREATE TABLE `admin_audit_logs` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `admin_user_id` BIGINT UNSIGNED NOT NULL,
  `action` VARCHAR(100) NOT NULL,
  `target_type` VARCHAR(50) DEFAULT NULL,
  `target_id` VARCHAR(50) DEFAULT NULL,
  `details` JSON DEFAULT NULL,
  `ip_address` VARCHAR(45) NOT NULL,
  `user_agent` VARCHAR(255) DEFAULT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_audit_admin_created` (`admin_user_id`, `created_at`),
  KEY `idx_audit_action` (`action`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------------
-- 15. SYNC LOGS TABLE
-- History of background provider synchronization jobs
-- ----------------------------------------------------------------------------
CREATE TABLE `sync_logs` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `provider_id` BIGINT UNSIGNED NOT NULL,
  `sync_type` ENUM('services', 'countries', 'stock', 'pricing', 'order_status') NOT NULL,
  `items_synced` INT NOT NULL DEFAULT 0,
  `items_updated` INT NOT NULL DEFAULT 0,
  `status` ENUM('success', 'warning', 'failed') NOT NULL,
  `error_message` TEXT DEFAULT NULL,
  `execution_time_ms` INT NOT NULL DEFAULT 0,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_sync_logs_provider` (`provider_id`, `created_at`),
  KEY `idx_sync_logs_type` (`sync_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------------
-- 16. SETTINGS TABLE
-- Key-value platform configurations
-- ----------------------------------------------------------------------------
CREATE TABLE `settings` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `setting_key` VARCHAR(100) NOT NULL,
  `setting_value` TEXT DEFAULT NULL,
  `setting_group` VARCHAR(50) NOT NULL DEFAULT 'general',
  `is_secret` TINYINT(1) NOT NULL DEFAULT 0,
  `description` VARCHAR(255) DEFAULT NULL,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_settings_key` (`setting_key`),
  KEY `idx_settings_group` (`setting_group`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
