<?php
declare(strict_types=1);

require_once __DIR__ . '/../backend/includes/layout.php';
require_once __DIR__ . '/../backend/includes/analytics.php';
require_once __DIR__ . '/../backend/includes/recurring.php';
requireAuth();

// Process recurring expenses
processRecurringExpenses($pdo);

$user = currentUser();
$userId = (int) $user['id'];
$stats = fetchUserExpenseStats($pdo, $userId);

$recentExpensesStmt = $pdo->prepare(
    'SELECT e.id, e.title, e.amount, e.expense_date, c.category_name
     FROM expenses e
     JOIN categories c ON c.id = e.category_id
     WHERE e.user_id = :user_id
     ORDER BY e.expense_date DESC, e.id DESC
     LIMIT 5'
);
$recentExpensesStmt->execute(['user_id' => $userId]);
$recentExpenses = $recentExpensesStmt->fetchAll();

renderPageStart('Dashboard', 'dashboard');
?>
<header class="content-header">
    <h1>Welcome, <?php echo htmlspecialchars($user['name']); ?></h1>
    <div class="inline-actions">
        <a class="btn" href="<?php echo htmlspecialchars(appUrl('pages/addExpense.php')); ?>">+ Add Expense</a>
        <a class="btn ghost" href="<?php echo htmlspecialchars(appUrl('pages/report.php')); ?>">View Report</a>
    </div>
</header>

<section class="card-grid">
    <article class="stat-card">
        <h3>Current Month</h3>
        <p>Rs <?php echo number_format($stats['current_month_total'], 2); ?></p>
    </article>
    <article class="stat-card">
        <h3>Lifetime Spend</h3>
        <p>Rs <?php echo number_format($stats['lifetime_total'], 2); ?></p>
    </article>
    <article class="stat-card">
        <h3>Total Entries</h3>
        <p><?php echo number_format($stats['total_entries']); ?></p>
    </article>
    <article class="stat-card">
        <h3>Top Category</h3>
        <p><?php echo htmlspecialchars($stats['top_category']); ?></p>
        <small>Rs <?php echo number_format($stats['top_category_total'], 2); ?></small>
    </article>
</section>

<section class="panel">
    <h2>Monthly Spending Trend</h2>
    <canvas id="monthlyTrendChart"></canvas>
</section>

<section class="panel">
    <h2>Recent Expenses</h2>
    <table>
        <thead>
            <tr>
                <th>Title</th>
                <th>Category</th>
                <th>Date</th>
                <th>Amount</th>
            </tr>
        </thead>
        <tbody>
        <?php if (empty($recentExpenses)): ?>
            <tr><td colspan="4">No expenses found.</td></tr>
        <?php else: ?>
            <?php foreach ($recentExpenses as $expense): ?>
            <tr>
                <td><?php echo htmlspecialchars($expense['title']); ?></td>
                <td><?php echo htmlspecialchars($expense['category_name']); ?></td>
                <td><?php echo htmlspecialchars($expense['expense_date']); ?></td>
                <td>Rs <?php echo number_format((float) $expense['amount'], 2); ?></td>
            </tr>
            <?php endforeach; ?>
        <?php endif; ?>
        </tbody>
    </table>
</section>
<?php
renderPageEnd();
