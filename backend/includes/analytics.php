<?php
declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';

function fetchUserExpenseStats(PDO $pdo, int $userId): array
{
    $totalsStmt = $pdo->prepare(
        'SELECT
            COALESCE(SUM(CASE WHEN MONTH(expense_date) = MONTH(CURRENT_DATE()) AND YEAR(expense_date) = YEAR(CURRENT_DATE()) THEN amount ELSE 0 END), 0) AS current_month_total,
            COALESCE(SUM(amount), 0) AS lifetime_total,
            COUNT(*) AS total_entries
         FROM expenses
         WHERE user_id = :user_id'
    );
    $totalsStmt->execute(['user_id' => $userId]);
    $totals = $totalsStmt->fetch() ?: ['current_month_total' => 0, 'lifetime_total' => 0, 'total_entries' => 0];

    $topCategoryStmt = $pdo->prepare(
        'SELECT c.category_name, COALESCE(SUM(e.amount), 0) AS total
         FROM expenses e
         JOIN categories c ON c.id = e.category_id
         WHERE e.user_id = :user_id
         GROUP BY c.id, c.category_name
         ORDER BY total DESC
         LIMIT 1'
    );
    $topCategoryStmt->execute(['user_id' => $userId]);
    $topCategory = $topCategoryStmt->fetch();

    return [
        'current_month_total' => (float) $totals['current_month_total'],
        'lifetime_total' => (float) $totals['lifetime_total'],
        'total_entries' => (int) $totals['total_entries'],
        'top_category' => $topCategory['category_name'] ?? 'N/A',
        'top_category_total' => isset($topCategory['total']) ? (float) $topCategory['total'] : 0,
    ];
}
