<?php
declare(strict_types=1);

require_once __DIR__ . '/../backend/includes/layout.php';
requireAuth();

renderPageStart('Analytics Dashboard', 'analytics');
?>
<header class="content-header">
    <h1>Expense Analytics Dashboard</h1>
</header>

<section class="card-grid analytics-grid">
    <article class="stat-card highlight">
        <h3>Current Month Total</h3>
        <p id="currentMonthTotal">Rs 0.00</p>
    </article>
    <article class="stat-card highlight">
        <h3>Top Category</h3>
        <p id="topCategoryName">N/A</p>
        <small id="topCategoryTotal">Rs 0.00</small>
    </article>
</section>

<section class="panel chart-panel">
    <h2>Category-wise Spending Distribution</h2>
    <canvas id="categoryDistributionChart"></canvas>
</section>

<section class="panel chart-panel">
    <h2>Expense Comparison Across Months (Current Year)</h2>
    <canvas id="monthComparisonChart"></canvas>
</section>

<section class="panel query-panel">
    <h2>Relational SQL Queries Used</h2>
    <pre>
SELECT c.category_name, SUM(e.amount) AS total
FROM expenses e
JOIN categories c ON e.category_id = c.id
WHERE e.user_id = ?
GROUP BY c.category_name
ORDER BY total DESC;

SELECT DATE_FORMAT(e.expense_date, '%Y-%m') AS month_label, SUM(e.amount) AS total
FROM expenses e
WHERE e.user_id = ?
GROUP BY DATE_FORMAT(e.expense_date, '%Y-%m')
ORDER BY month_label ASC;
    </pre>
</section>
<?php
renderPageEnd();
