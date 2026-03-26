# TrackWise: Relational Expense Analytics Platform

Dynamic expense management and analytics web application built with PHP, MySQL, HTML5/CSS3, ES6, and Chart.js.

## Features

- User registration, login, session-based access control, logout
- Expense CRUD (add, edit, delete)
- Filters by date range, category, and search text
- Category management (fixed/variable categories)
- Recurring expenses support
- SQL-powered analytics dashboard with Chart.js visualizations
- Power BI-like report view with all expenses, highest-cost spending, and discretionary spend hints
- Responsive UI with sidebar navigation and modal-based forms

## Tech Stack

- Frontend: HTML5, CSS3, JavaScript (ES6)
- Backend: PHP (PDO)
- Database: MySQL
- Charts: Chart.js

## Project Structure

```
TrackWise/
  css/style.css
  js/dashboard.js
  pages/
    login.php
    register.php
    dashboard.php
    report.php
    addExpense.php
    expenses.php
    analytics.php
    categories.php
  backend/
    dbConnection.php
    login.php
    addExpense.php
    includes/
    actions/
  database/schema.sql
  index.php
```

## Setup (XAMPP)

1. Place the project in `C:/xampp/htdocs/TrackWise`.
2. Start Apache and MySQL from XAMPP control panel.
3. Create DB and tables by running `database/schema.sql` in phpMyAdmin.
4. Update DB credentials in `backend/dbConnection.php` if needed.
5. Open `http://localhost/TrackWise/`.

### Optional environment overrides

You can override DB connection defaults using environment variables:

- `TRACKWISE_DB_HOST` (default: `127.0.0.1`)
- `TRACKWISE_DB_NAME` (default: `trackwise`)
- `TRACKWISE_DB_USER` (default: `root`)
- `TRACKWISE_DB_PASS` (default: empty)
- `TRACKWISE_DB_PORT` (single port)
- `TRACKWISE_DB_PORTS` (comma-separated fallback ports, default: `3306,3307`)

The app attempts each port in `TRACKWISE_DB_PORTS` until one succeeds.

### Performance indexes (existing databases)

If your `expenses` table was created before recent optimizations, run:

```sql
ALTER TABLE expenses
  ADD INDEX idx_expenses_user (user_id),
  ADD INDEX idx_expenses_user_date (user_id, expense_date);
```

## Notes

- `expenses` and `categories` are user-scoped through `user_id` so each user sees only their own data.
- SQL analytics use `JOIN`, `GROUP BY`, `SUM`, `COUNT`, and `ORDER BY` as required.

## SQL -> PHP -> HTML Demo (Students)

This repo now includes a direct 3-layer demo:

- `student-form.html` (HTML form)
- `insert.php` (PHP insert handler using prepared statements)
- `display.php` (PHP read + HTML output)
- `database/student_db.sql` (DB/table script)

Run these via Apache:

- `http://localhost/TrackWise/student-form.html`
- `http://localhost/TrackWise/display.php`
