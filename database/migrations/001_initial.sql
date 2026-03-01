-- offshore banking platform - clean schema
-- generated from rebuild architecture spec

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET NAMES utf8mb4;

-- accounts table
CREATE TABLE IF NOT EXISTS `accounts` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `internet_id` VARCHAR(20) NOT NULL,
    `email` VARCHAR(200) NOT NULL,
    `password_hash` TEXT NOT NULL,
    `pin_hash` VARCHAR(255) NOT NULL,
    `otp_secret` VARCHAR(64) DEFAULT NULL,
    `first_name` VARCHAR(100) NOT NULL,
    `last_name` VARCHAR(100) NOT NULL,
    `phone` VARCHAR(20) DEFAULT NULL,
    `avatar` VARCHAR(255) DEFAULT 'default.png',
    `currency` VARCHAR(5) DEFAULT 'USD',
    `checking_balance` DECIMAL(15,2) DEFAULT 0.00,
    `savings_balance` DECIMAL(15,2) DEFAULT 0.00,
    `loan_balance` DECIMAL(15,2) DEFAULT 0.00,
    `checking_acct_no` VARCHAR(20) DEFAULT NULL,
    `savings_acct_no` VARCHAR(20) DEFAULT NULL,
    `status` ENUM('active','hold','pending','blocked') DEFAULT 'pending',
    `kyc_status` ENUM('none','pending','approved','rejected') DEFAULT 'none',
    `phone_verified` TINYINT(1) DEFAULT 0,
    `two_fa_enabled` TINYINT(1) DEFAULT 0,
    `gender` VARCHAR(10) DEFAULT NULL,
    `dob` DATE DEFAULT NULL,
    `address` TEXT DEFAULT NULL,
    `state` VARCHAR(100) DEFAULT NULL,
    `ssn_hash` VARCHAR(255) DEFAULT NULL,
    `id_front` VARCHAR(255) DEFAULT NULL,
    `id_back` VARCHAR(255) DEFAULT NULL,
    `id_number` VARCHAR(50) DEFAULT NULL,
    `proof_of_address` VARCHAR(255) DEFAULT NULL,
    `manager_name` VARCHAR(200) DEFAULT NULL,
    `manager_email` VARCHAR(200) DEFAULT NULL,
    `reset_token` VARCHAR(64) DEFAULT NULL,
    `reset_token_expires` DATETIME DEFAULT NULL,
    `transfer_enabled` TINYINT(1) DEFAULT 1,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY `uq_internet_id` (`internet_id`),
    UNIQUE KEY `uq_email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- transactions table
CREATE TABLE IF NOT EXISTS `transactions` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `internet_id` VARCHAR(20) NOT NULL,
    `type` ENUM('domestic','wire','self','inter','deposit','withdrawal','loan','card','funding') NOT NULL,
    `amount` DECIMAL(15,2) NOT NULL,
    `fee` DECIMAL(10,2) DEFAULT 0.00,
    `currency` VARCHAR(5) DEFAULT 'USD',
    `status` ENUM('pending','processing','completed','failed') DEFAULT 'pending',
    `reference_id` VARCHAR(50) NOT NULL,
    `description` TEXT DEFAULT NULL,
    `payment_account` VARCHAR(20) DEFAULT NULL,
    `recipient_name` VARCHAR(200) DEFAULT NULL,
    `recipient_account` VARCHAR(50) DEFAULT NULL,
    `recipient_bank` VARCHAR(200) DEFAULT NULL,
    `recipient_country` VARCHAR(100) DEFAULT NULL,
    `swift_code` VARCHAR(20) DEFAULT NULL,
    `routing_number` VARCHAR(20) DEFAULT NULL,
    `bank_address` TEXT DEFAULT NULL,
    `trans_type` ENUM('credit','debit') NOT NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY `uq_reference_id` (`reference_id`),
    INDEX `idx_internet_id` (`internet_id`),
    INDEX `idx_status` (`status`),
    INDEX `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- admin table
CREATE TABLE IF NOT EXISTS `admin` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `email` VARCHAR(200) NOT NULL,
    `password_hash` TEXT NOT NULL,
    `first_name` VARCHAR(100) NOT NULL,
    `last_name` VARCHAR(100) NOT NULL,
    `avatar` VARCHAR(255) DEFAULT NULL,
    `role` ENUM('admin','super_admin') DEFAULT 'admin',
    `last_login` DATETIME DEFAULT NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY `uq_admin_email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- cards table
CREATE TABLE IF NOT EXISTS `cards` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `internet_id` VARCHAR(20) NOT NULL,
    `card_number` VARCHAR(255) NOT NULL,
    `card_name` VARCHAR(200) NOT NULL,
    `card_type` ENUM('visa','mastercard','amex') NOT NULL,
    `expiry_date` VARCHAR(7) NOT NULL,
    `cvv_hash` VARCHAR(255) NOT NULL,
    `status` ENUM('pending','active','frozen','cancelled') DEFAULT 'pending',
    `fee` DECIMAL(10,2) DEFAULT 0.00,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- tickets table
CREATE TABLE IF NOT EXISTS `tickets` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `internet_id` VARCHAR(20) NOT NULL,
    `subject` VARCHAR(255) NOT NULL,
    `message` TEXT NOT NULL,
    `type` ENUM('general','technical','billing','complaint') DEFAULT 'general',
    `status` ENUM('open','processing','closed','resolved') DEFAULT 'open',
    `admin_reply` TEXT DEFAULT NULL,
    `attachment` VARCHAR(255) DEFAULT NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- audit logs table
CREATE TABLE IF NOT EXISTS `audit_logs` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `internet_id` VARCHAR(20) DEFAULT NULL,
    `action` VARCHAR(100) NOT NULL,
    `details` TEXT DEFAULT NULL,
    `ip_address` VARCHAR(45) NOT NULL,
    `user_agent` TEXT DEFAULT NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- settings table (single row)
CREATE TABLE IF NOT EXISTS `settings` (
    `id` INT PRIMARY KEY DEFAULT 1,
    `site_name` VARCHAR(200) NOT NULL,
    `site_email` VARCHAR(200) NOT NULL,
    `site_phone` VARCHAR(20) DEFAULT NULL,
    `site_address` TEXT DEFAULT NULL,
    `site_url` VARCHAR(255) NOT NULL,
    `currency` VARCHAR(5) DEFAULT 'USD',
    `wire_limit` DECIMAL(15,2) DEFAULT 50000.00,
    `domestic_limit` DECIMAL(15,2) DEFAULT 10000.00,
    `maintenance_mode` TINYINT(1) DEFAULT 0,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- smtp settings table (single row)
CREATE TABLE IF NOT EXISTS `smtp_settings` (
    `id` INT PRIMARY KEY DEFAULT 1,
    `host` VARCHAR(255) NOT NULL,
    `port` INT DEFAULT 587,
    `username` VARCHAR(255) NOT NULL,
    `password` VARCHAR(255) NOT NULL,
    `from_email` VARCHAR(255) NOT NULL,
    `from_name` VARCHAR(200) NOT NULL,
    `encryption` ENUM('tls','ssl','none') DEFAULT 'tls'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- digital payments (crypto wallets)
CREATE TABLE IF NOT EXISTS `digital_payments` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(100) NOT NULL,
    `wallet_address` TEXT NOT NULL,
    `icon` VARCHAR(255) DEFAULT NULL,
    `enabled` TINYINT(1) DEFAULT 1,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- rate limits (db-based, not session)
CREATE TABLE IF NOT EXISTS `rate_limits` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `ip_address` VARCHAR(45) NOT NULL,
    `action` VARCHAR(50) NOT NULL,
    `attempts` INT DEFAULT 1,
    `window_start` DATETIME DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY `uq_ip_action` (`ip_address`, `action`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- activities log
CREATE TABLE IF NOT EXISTS `activities` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `internet_id` VARCHAR(20) NOT NULL,
    `details` VARCHAR(255) NOT NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- temp dumps (pending transfer data)
CREATE TABLE IF NOT EXISTS `temp_dumps` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `internet_id` VARCHAR(20) DEFAULT NULL,
    `amount` DECIMAL(15,2) DEFAULT 0.00,
    `account_number` VARCHAR(50) DEFAULT NULL,
    `account_name` VARCHAR(200) DEFAULT NULL,
    `bank_name` VARCHAR(200) DEFAULT NULL,
    `routing_number` VARCHAR(20) DEFAULT NULL,
    `account_type` VARCHAR(50) DEFAULT NULL,
    `payment_account` VARCHAR(20) DEFAULT NULL,
    `bank_country` VARCHAR(100) DEFAULT NULL,
    `description` VARCHAR(255) DEFAULT NULL,
    `trans_type` VARCHAR(20) DEFAULT NULL,
    `transaction_type` VARCHAR(50) DEFAULT NULL,
    `reference_id` VARCHAR(50) DEFAULT NULL,
    `status` VARCHAR(20) DEFAULT NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- saved payment beneficiaries
CREATE TABLE IF NOT EXISTS `list_payment` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `internet_id` VARCHAR(20) NOT NULL,
    `bank_name` VARCHAR(255) NOT NULL,
    `bank_address` VARCHAR(255) NOT NULL,
    `account_name` VARCHAR(255) NOT NULL,
    `reference_id` VARCHAR(255) NOT NULL,
    `iban` VARCHAR(255) NOT NULL,
    `swift_code` VARCHAR(255) NOT NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
