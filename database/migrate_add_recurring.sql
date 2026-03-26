-- Migration script for adding recurring expenses feature to existing TrackWise installations
-- Run this script if your database schema was created before the recurring expenses feature

ALTER TABLE `expenses` ADD CONSTRAINT `fk_expenses_category_restrict` FOREIGN KEY (`category_id`) REFERENCES `categories`(`id`) ON DELETE RESTRICT;

CREATE TABLE `recurring_expenses` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NOT NULL,
    `title` VARCHAR(140) NOT NULL,
    `amount` DECIMAL(10,2) NOT NULL,
    `category_id` INT NOT NULL,
    `recurrence_type` ENUM('Daily', 'Weekly', 'Bi-Weekly', 'Monthly', 'Quarterly', 'Yearly') NOT NULL DEFAULT 'Monthly',
    `start_date` DATE NOT NULL,
    `end_date` DATE,
    `last_generated_date` DATE,
    `is_active` BOOLEAN DEFAULT true,
    `description` VARCHAR(255),
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT `fk_recurring_user` FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_recurring_category` FOREIGN KEY (`category_id`) REFERENCES `categories`(`id`) ON DELETE RESTRICT,
    INDEX `idx_recurring_user` (`user_id`),
    INDEX `idx_recurring_active` (`user_id`, `is_active`),
    INDEX `idx_recurring_next_date` (`user_id`, `start_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
