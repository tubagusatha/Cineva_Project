<?php
/**
 * Auto-migration helper for Midtrans tables and columns in Cineva
 * Ensures XAMPP works out-of-the-box without requiring manual SQL queries.
 */
function ensureMidtransSchema($conn) {
    static $checked = false;
    if ($checked) return;
    $checked = true;

    // 1. Create subscription_transactions table if not exists
    $sqlTransactions = "
        CREATE TABLE IF NOT EXISTS `subscription_transactions` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `user_id` INT NOT NULL,
            `order_id` VARCHAR(100) NOT NULL UNIQUE,
            `plan` ENUM('basic', 'standard', 'premium') NOT NULL,
            `amount` INT NOT NULL,
            `snap_token` VARCHAR(255) NULL,
            `payment_type` VARCHAR(50) DEFAULT 'midtrans',
            `transaction_status` ENUM('pending', 'settlement', 'capture', 'expire', 'cancel', 'deny') DEFAULT 'pending',
            `pdf_url` VARCHAR(500) NULL,
            `payment_response` LONGTEXT NULL,
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            `paid_at` DATETIME NULL,
            INDEX (`user_id`),
            INDEX (`order_id`),
            INDEX (`transaction_status`),
            CONSTRAINT `fk_sub_trans_user` FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ";
    $conn->query($sqlTransactions);

    // 2. Add extra columns to customers table if not existing
    $custColumnsRes = $conn->query("SHOW COLUMNS FROM `customers`");
    $existingCols = [];
    if ($custColumnsRes) {
        while ($c = $custColumnsRes->fetch_assoc()) {
            $existingCols[$c['Field']] = true;
        }
    }

    if (!isset($existingCols['subscription_expires_at'])) {
        $conn->query("ALTER TABLE `customers` ADD COLUMN `subscription_expires_at` DATETIME NULL AFTER `subscription`");
    }
    if (!isset($existingCols['active_order_id'])) {
        $conn->query("ALTER TABLE `customers` ADD COLUMN `active_order_id` VARCHAR(100) NULL AFTER `subscription_expires_at`");
    }
    if (!isset($existingCols['payment_method'])) {
        $conn->query("ALTER TABLE `customers` ADD COLUMN `payment_method` VARCHAR(50) NULL AFTER `active_order_id`");
    }
    if (!isset($existingCols['updated_at'])) {
        $conn->query("ALTER TABLE `customers` ADD COLUMN `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP AFTER `payment_method`");
    }
}
