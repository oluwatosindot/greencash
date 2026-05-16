-- Green Cash Database Schema
-- Run this file once on a fresh MySQL server to create all tables.
-- Database: greencash (utf8mb4_unicode_ci)

CREATE DATABASE IF NOT EXISTS `greencash`
    DEFAULT CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE `greencash`;

SET FOREIGN_KEY_CHECKS=0;

-- ============================================================
-- USERS (Admin accounts)
-- ============================================================
CREATE TABLE IF NOT EXISTS `users` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `first_name` VARCHAR(100) DEFAULT NULL,
    `last_name` VARCHAR(100) DEFAULT NULL,
    `email` VARCHAR(255) NOT NULL,
    `phone` VARCHAR(20) DEFAULT NULL,
    `password` VARCHAR(255) NOT NULL,
    `role` ENUM('admin') DEFAULT 'admin',
    `status` ENUM('active','inactive') DEFAULT 'active',
    `email_verified_at` TIMESTAMP NULL DEFAULT NULL,
    `reset_token` VARCHAR(64) DEFAULT NULL,
    `reset_token_expires` TIMESTAMP NULL DEFAULT NULL,
    `created_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- SALARY ADVANCE APPLICATIONS (all loan applications)
-- ============================================================
CREATE TABLE IF NOT EXISTS `salary_advance_applications` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `reference_number` VARCHAR(50) DEFAULT NULL,
    `source` ENUM('public','broker') DEFAULT 'public',
    `broker_id` INT(11) DEFAULT NULL,

    -- Personal info
    `first_name` VARCHAR(100) NOT NULL,
    `last_name` VARCHAR(100) NOT NULL,
    `other_names` VARCHAR(100) DEFAULT NULL,
    `id_number` VARCHAR(20) NOT NULL,
    `email` VARCHAR(255) NOT NULL,
    `phone` VARCHAR(20) NOT NULL,
    `address` TEXT DEFAULT NULL,
    `city` VARCHAR(100) DEFAULT NULL,
    `province` VARCHAR(100) DEFAULT NULL,
    `zip_code` VARCHAR(10) DEFAULT NULL,

    -- Employment info
    `employment_status` ENUM('employed','self_employed','contract') DEFAULT 'employed',
    `employer_name` VARCHAR(255) DEFAULT NULL,
    `employer_contact` VARCHAR(20) DEFAULT NULL,
    `job_title` VARCHAR(100) DEFAULT NULL,
    `employment_duration` VARCHAR(50) DEFAULT NULL,

    -- Financial info
    `salary_amount` DECIMAL(15,2) DEFAULT NULL,
    `next_payday_date` DATE DEFAULT NULL,
    `loan_amount` DECIMAL(15,2) NOT NULL,
    `repayment_date` DATE DEFAULT NULL,

    -- Monthly expenses
    `rent` DECIMAL(15,2) DEFAULT 0.00,
    `food` DECIMAL(15,2) DEFAULT 0.00,
    `transport` DECIMAL(15,2) DEFAULT 0.00,
    `other_expenses` DECIMAL(15,2) DEFAULT 0.00,

    -- Status & admin
    `status` ENUM('pending','under_review','approved','rejected','disbursed') DEFAULT 'pending',
    `notes` TEXT DEFAULT NULL,
    `reviewed_by` INT(11) DEFAULT NULL,
    `reviewed_at` TIMESTAMP NULL DEFAULT NULL,

    `submitted_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    `created_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    PRIMARY KEY (`id`),
    UNIQUE KEY `reference_number` (`reference_number`),
    KEY `broker_id` (`broker_id`),
    KEY `status` (`status`),
    KEY `source` (`source`),
    KEY `id_number` (`id_number`),
    CONSTRAINT `saa_broker_fk` FOREIGN KEY (`broker_id`) REFERENCES `credit_brokers` (`id`) ON DELETE SET NULL,
    CONSTRAINT `saa_reviewer_fk` FOREIGN KEY (`reviewed_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- APPLICATION DOCUMENTS
-- ============================================================
CREATE TABLE IF NOT EXISTS `application_documents` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `application_id` INT(11) NOT NULL,
    `document_type` VARCHAR(50) NOT NULL COMMENT 'id_document, payslip, bank_statement',
    `file_name` VARCHAR(255) NOT NULL,
    `file_path` VARCHAR(500) NOT NULL,
    `uploaded_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `application_id` (`application_id`),
    CONSTRAINT `ad_application_fk` FOREIGN KEY (`application_id`) REFERENCES `salary_advance_applications` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- CREDIT BROKERS
-- ============================================================
CREATE TABLE IF NOT EXISTS `credit_brokers` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `broker_code` VARCHAR(50) NOT NULL,
    `first_name` VARCHAR(100) NOT NULL,
    `last_name` VARCHAR(100) NOT NULL,
    `email` VARCHAR(255) NOT NULL,
    `phone` VARCHAR(20) NOT NULL,
    `id_number` VARCHAR(20) NOT NULL,
    `company_name` VARCHAR(255) DEFAULT NULL,
    `company_registration` VARCHAR(100) DEFAULT NULL,
    `province` VARCHAR(100) DEFAULT NULL,
    `city` VARCHAR(100) DEFAULT NULL,
    `experience_years` INT(11) DEFAULT NULL,
    `why_join` TEXT DEFAULT NULL,
    `id_document_path` VARCHAR(500) DEFAULT NULL,
    `username` VARCHAR(50) UNIQUE NULL,
    `password_hash` VARCHAR(255) DEFAULT NULL COMMENT 'Set when admin activates account (hash of ID number)',
    `status` ENUM('pending','approved','active','inactive','suspended') DEFAULT 'pending',
    `commission_rate` DECIMAL(5,2) DEFAULT 5.00,
    `total_referrals` INT(11) DEFAULT 0,
    `total_commission` DECIMAL(15,2) DEFAULT 0.00,
    `last_login` TIMESTAMP NULL DEFAULT NULL,
    `created_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `broker_code` (`broker_code`),
    UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- BROKER CLIENTS (clients submitted by brokers)
-- ============================================================
CREATE TABLE IF NOT EXISTS `broker_clients` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `broker_id` INT(11) NOT NULL,
    `first_name` VARCHAR(100) NOT NULL,
    `last_name` VARCHAR(100) NOT NULL,
    `email` VARCHAR(255) DEFAULT NULL,
    `phone` VARCHAR(20) DEFAULT NULL,
    `id_number` VARCHAR(20) DEFAULT NULL,
    `address` TEXT DEFAULT NULL,
    `city` VARCHAR(100) DEFAULT NULL,
    `province` VARCHAR(100) DEFAULT NULL,
    `status` ENUM('active','inactive') DEFAULT 'active',
    `created_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `broker_id` (`broker_id`),
    CONSTRAINT `bc_broker_fk` FOREIGN KEY (`broker_id`) REFERENCES `credit_brokers` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- OTP VERIFICATIONS (Admin 2FA)
-- ============================================================
CREATE TABLE IF NOT EXISTS `otp_verifications` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `user_id` INT(11) NOT NULL,
    `otp_code` VARCHAR(6) NOT NULL,
    `expires_at` TIMESTAMP NOT NULL,
    `used` TINYINT(1) DEFAULT 0,
    `created_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `user_id` (`user_id`),
    CONSTRAINT `otp_user_fk` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- RATE LIMITS (lockout — IP-based and account-based)
-- ============================================================
CREATE TABLE IF NOT EXISTS `rate_limits` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `identifier` VARCHAR(255) NOT NULL COMMENT 'IP address or broker email',
    `attempt_type` VARCHAR(50) NOT NULL COMMENT 'admin_login, broker_login, ip_login',
    `attempts` INT(11) DEFAULT 0,
    `locked_until` TIMESTAMP NULL DEFAULT NULL,
    `last_attempt_at` TIMESTAMP NULL DEFAULT NULL,
    `created_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `identifier_type` (`identifier`, `attempt_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- BROKER ACTIVITY LOG
-- ============================================================
CREATE TABLE IF NOT EXISTS `broker_activity_log` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `broker_id` INT(11) DEFAULT NULL,
    `action` VARCHAR(100) NOT NULL,
    `details` TEXT DEFAULT NULL,
    `ip_address` VARCHAR(45) DEFAULT NULL,
    `created_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `broker_id` (`broker_id`),
    CONSTRAINT `bal_broker_fk` FOREIGN KEY (`broker_id`) REFERENCES `credit_brokers` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- ADMIN ACTIVITY LOG
-- ============================================================
CREATE TABLE IF NOT EXISTS `admin_activity_log` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `admin_id` INT(11) DEFAULT NULL,
    `action` VARCHAR(100) NOT NULL,
    `details` TEXT DEFAULT NULL,
    `ip_address` VARCHAR(45) DEFAULT NULL,
    `created_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `admin_id` (`admin_id`),
    CONSTRAINT `aal_admin_fk` FOREIGN KEY (`admin_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- BROKER NOTIFICATIONS
-- ============================================================
CREATE TABLE IF NOT EXISTS `broker_notifications` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `broker_id` INT(11) NOT NULL,
    `title` VARCHAR(255) NOT NULL,
    `message` TEXT DEFAULT NULL,
    `is_read` TINYINT(1) DEFAULT 0,
    `application_id` INT(11) DEFAULT NULL,
    `created_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `broker_id` (`broker_id`),
    KEY `application_id` (`application_id`),
    CONSTRAINT `bn_broker_fk` FOREIGN KEY (`broker_id`) REFERENCES `credit_brokers` (`id`) ON DELETE CASCADE,
    CONSTRAINT `bn_application_fk` FOREIGN KEY (`application_id`) REFERENCES `salary_advance_applications` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- DEFAULT ADMIN USER
-- Password: changeme123 (CHANGE THIS IMMEDIATELY)
-- ============================================================
INSERT INTO `users` (`first_name`, `last_name`, `email`, `password`, `role`, `status`)
VALUES ('Admin', 'User', 'admin@greencash.co.za', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', 'active');

SET FOREIGN_KEY_CHECKS=1;
