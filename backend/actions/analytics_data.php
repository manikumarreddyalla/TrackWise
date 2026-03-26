<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/analytics.php';
requireAuth();

header('Content-Type: application/json');

$userId = (int) currentUser()['id'];

$monthlyTrendStmt = $pdo->prepare(
    'SELECT DATE_FORMAT(expense_date, "%Y-%m") AS month_label, COALESCE(SUM(amount), 0) AS total
     FROM expenses
     WHERE user_id = :user_id
     GROUP BY DATE_FORMAT(expense_date, "%Y-%m")
     ORDER BY month_label ASC'
);
$monthlyTrendStmt->execute(['user_id' => $userId]);
$monthlyTrend = $monthlyTrendStmt->fetchAll();

$categoryDistributionStmt = $pdo->prepare(
    'SELECT c.category_name, COALESCE(SUM(e.amount), 0) AS total
     FROM expenses e
     JOIN categories c ON e.category_id = c.id
     WHERE e.user_id = :user_id
     GROUP BY c.id, c.category_name
     ORDER BY total DESC'
);
$categoryDistributionStmt->execute(['user_id' => $userId]);
$categoryDistribution = $categoryDistributionStmt->fetchAll();

$comparisonStmt = $pdo->prepare(
    'SELECT
        DATE_FORMAT(expense_date, "%M") AS month_name,
        MONTH(expense_date) AS month_number,
        YEAR(expense_date) AS year_number,
        COALESCE(SUM(amount), 0) AS total
     FROM expenses
     WHERE user_id = :user_id
       AND YEAR(expense_date) = YEAR(CURRENT_DATE())
     GROUP BY year_number, month_number, month_name
     ORDER BY year_number ASC, month_number ASC'
);
$comparisonStmt->execute(['user_id' => $userId]);
$comparison = $comparisonStmt->fetchAll();

$stats = fetchUserExpenseStats($pdo, $userId);

echo json_encode([
    'monthlyTrend' => $monthlyTrend,
    'categoryDistribution' => $categoryDistribution,
    'comparison' => $comparison,
    'currentMonthTotal' => $stats['current_month_total'],
    'topCategory' => [
        'category_name' => $stats['top_category'],
        'total' => $stats['top_category_total'],
    ],
], JSON_THROW_ON_ERROR);
