CREATE DATABASE IF NOT EXISTS trackwise;
USE trackwise;

DROP TABLE IF EXISTS recurring_expenses;
DROP TABLE IF EXISTS expenses;
DROP TABLE IF EXISTS categories;
DROP TABLE IF EXISTS users;

CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(120) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    category_name VARCHAR(80) NOT NULL,
    category_type ENUM('Fixed', 'Variable') NOT NULL DEFAULT 'Variable',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_user_category (user_id, category_name),
    CONSTRAINT fk_categories_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE expenses (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    title VARCHAR(140) NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    category_id INT NOT NULL,
    expense_date DATE NOT NULL,
    description VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_expenses_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_expenses_category FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE RESTRICT,
    INDEX idx_expenses_user (user_id),
    INDEX idx_expenses_user_date (user_id, expense_date),
    INDEX idx_expense_date (expense_date),
    INDEX idx_user_category (user_id, category_id)
);

CREATE TABLE recurring_expenses (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    title VARCHAR(140) NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    category_id INT NOT NULL,
    recurrence_type ENUM('Daily', 'Weekly', 'Bi-Weekly', 'Monthly', 'Quarterly', 'Yearly') NOT NULL DEFAULT 'Monthly',
    start_date DATE NOT NULL,
    end_date DATE,
    last_generated_date DATE,
    is_active BOOLEAN DEFAULT true,
    description VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_recurring_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_recurring_category FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE RESTRICT,
    INDEX idx_recurring_user (user_id),
    INDEX idx_recurring_active (user_id, is_active),
    INDEX idx_recurring_next_date (user_id, start_date)
);

-- Relational SQL analytics examples
-- Category spending analysis
SELECT c.category_name, SUM(e.amount) AS total_spent
FROM expenses e
JOIN categories c ON e.category_id = c.id
GROUP BY c.category_name;

-- Monthly expense trend
SELECT MONTH(expense_date) AS month_no, SUM(amount) AS monthly_total
FROM expenses
GROUP BY MONTH(expense_date)
ORDER BY month_no;

-- Top expense category
SELECT c.category_name, SUM(e.amount) AS total_spent
FROM expenses e
JOIN categories c ON e.category_id = c.id
GROUP BY c.category_name
ORDER BY total_spent DESC
LIMIT 1;
