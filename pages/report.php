<?php
declare(strict_types=1);

require_once __DIR__ . '/../backend/includes/layout.php';
require_once __DIR__ . '/../backend/includes/categories.php';
require_once __DIR__ . '/../backend/includes/analytics.php';
requireAuth();

$user = currentUser();
$userId = (int) $user['id'];
$stats = fetchUserExpenseStats($pdo, $userId);
$categories = fetchUserCategories($pdo, $userId);

$fromDate = trim($_GET['from_date'] ?? '');
$toDate = trim($_GET['to_date'] ?? '');
$categoryFilter = (int) ($_GET['category_id'] ?? 0);
$search = trim($_GET['search'] ?? '');

$sql = 'SELECT e.id, e.title, e.amount, e.expense_date, e.description, c.category_name, c.category_type, c.id AS category_id
        FROM expenses e
        JOIN categories c ON c.id = e.category_id
        WHERE e.user_id = :user_id';
$params = ['user_id' => $userId];

if ($fromDate !== '') {
    $sql .= ' AND e.expense_date >= :from_date';
    $params['from_date'] = $fromDate;
}

if ($toDate !== '') {
    $sql .= ' AND e.expense_date <= :to_date';
    $params['to_date'] = $toDate;
}

if ($categoryFilter > 0) {
    $sql .= ' AND e.category_id = :category_id';
    $params['category_id'] = $categoryFilter;
}

if ($search !== '') {
    $sql .= ' AND (e.title LIKE :search OR e.description LIKE :search)';
    $params['search'] = '%' . $search . '%';
}

$sql .= ' ORDER BY e.expense_date DESC, e.id DESC';

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$expenses = $stmt->fetchAll();

$topCostlyStmt = $pdo->prepare(
    'SELECT e.title, e.amount, e.expense_date, c.category_name
     FROM expenses e
     JOIN categories c ON c.id = e.category_id
     WHERE e.user_id = :user_id
     ORDER BY e.amount DESC, e.expense_date DESC
     LIMIT 5'
);
$topCostlyStmt->execute(['user_id' => $userId]);
$topCostlyExpenses = $topCostlyStmt->fetchAll();

$nonRequiredStmt = $pdo->prepare(
    'SELECT e.title, e.amount, e.expense_date, c.category_name, c.category_type, e.description
     FROM expenses e
     JOIN categories c ON c.id = e.category_id
     WHERE e.user_id = :user_id
       AND (
            c.category_type = "Variable"
            OR LOWER(c.category_name) IN ("entertainment", "snacks", "dining", "shopping", "leisure", "subscriptions")
            OR LOWER(e.title) REGEXP "movie|netflix|spotify|swiggy|zomato|snack|party|coffee|game|shopping|subscription"
            OR LOWER(COALESCE(e.description, "")) REGEXP "movie|netflix|spotify|swiggy|zomato|snack|party|coffee|game|shopping|subscription"
       )
     ORDER BY e.amount DESC, e.expense_date DESC
     LIMIT 8'
);
$nonRequiredStmt->execute(['user_id' => $userId]);
$nonRequiredExpenses = $nonRequiredStmt->fetchAll();

renderPageStart('Expense Report', 'report');
?>
<header class="content-header">
    <h1>Expense Report</h1>
    <div class="inline-actions">
        <a class="btn" href="<?php echo htmlspecialchars(appUrl('pages/addExpense.php')); ?>">+ Add Expense</a>
        <a class="btn ghost" href="<?php echo htmlspecialchars(appUrl('pages/expenses.php')); ?>">Open Expense History</a>
    </div>
</header>

<section class="card-grid">
    <article class="stat-card">
        <h3>Total Entries</h3>
        <p><?php echo number_format($stats['total_entries']); ?></p>
    </article>
    <article class="stat-card">
        <h3>Current Month</h3>
        <p>Rs <?php echo number_format($stats['current_month_total'], 2); ?></p>
    </article>
    <article class="stat-card">
        <h3>Lifetime Spend</h3>
        <p>Rs <?php echo number_format($stats['lifetime_total'], 2); ?></p>
    </article>
    <article class="stat-card highlight">
        <h3>Top Category</h3>
        <p><?php echo htmlspecialchars($stats['top_category']); ?></p>
        <small>Rs <?php echo number_format($stats['top_category_total'], 2); ?></small>
    </article>
</section>

<section class="panel">
    <h2>All Expenses (Report View)</h2>
    <form method="get" class="filter-bar">
        <div>
            <label for="from_date">From</label>
            <input id="from_date" type="date" name="from_date" value="<?php echo htmlspecialchars($fromDate); ?>">
        </div>
        <div>
            <label for="to_date">To</label>
            <input id="to_date" type="date" name="to_date" value="<?php echo htmlspecialchars($toDate); ?>">
        </div>
        <div>
            <label for="category_id">Category</label>
            <select id="category_id" name="category_id">
                <option value="0">All</option>
                <?php foreach ($categories as $category): ?>
                    <option value="<?php echo (int) $category['id']; ?>" <?php echo $categoryFilter === (int) $category['id'] ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($category['category_name']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div>
            <label for="search">Search</label>
            <input id="search" type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="Title or description">
        </div>
        <button type="submit" class="btn">Apply</button>
        <a class="btn ghost" href="<?php echo htmlspecialchars(appUrl('pages/report.php')); ?>">Reset</a>
    </form>

    <table>
        <thead>
            <tr>
                <th>Date</th>
                <th>Title</th>
                <th>Category</th>
                <th>Type</th>
                <th>Amount</th>
                <th>Description</th>
            </tr>
        </thead>
        <tbody>
        <?php if (empty($expenses)): ?>
            <tr><td colspan="6">No expenses found for selected filters.</td></tr>
        <?php else: ?>
            <?php foreach ($expenses as $expense): ?>
            <tr>
                <td><?php echo htmlspecialchars($expense['expense_date']); ?></td>
                <td><?php echo htmlspecialchars($expense['title']); ?></td>
                <td><?php echo htmlspecialchars($expense['category_name']); ?></td>
                <td><?php echo htmlspecialchars($expense['category_type']); ?></td>
                <td>Rs <?php echo number_format((float) $expense['amount'], 2); ?></td>
                <td><?php echo htmlspecialchars($expense['description'] ?: '-'); ?></td>
            </tr>
            <?php endforeach; ?>
        <?php endif; ?>
        </tbody>
    </table>
</section>

<section class="card-grid report-two-up">
    <article class="panel">
        <h2>Most Costly Expenses</h2>
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
            <?php if (empty($topCostlyExpenses)): ?>
                <tr><td colspan="4">No expenses available.</td></tr>
            <?php else: ?>
                <?php foreach ($topCostlyExpenses as $row): ?>
                <tr>
                    <td><?php echo htmlspecialchars($row['title']); ?></td>
                    <td><?php echo htmlspecialchars($row['category_name']); ?></td>
                    <td><?php echo htmlspecialchars($row['expense_date']); ?></td>
                    <td>Rs <?php echo number_format((float) $row['amount'], 2); ?></td>
                </tr>
                <?php endforeach; ?>
            <?php endif; ?>
            </tbody>
        </table>
    </article>

    <article class="panel">
        <h2>Potential Non-Required Spend</h2>
        <p class="subtitle">Advisory only: based on variable/discretionary categories and spending keywords.</p>
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
            <?php if (empty($nonRequiredExpenses)): ?>
                <tr><td colspan="4">No discretionary spend candidates found.</td></tr>
            <?php else: ?>
                <?php foreach ($nonRequiredExpenses as $row): ?>
                <tr>
                    <td><?php echo htmlspecialchars($row['title']); ?></td>
                    <td><?php echo htmlspecialchars($row['category_name']); ?></td>
                    <td><?php echo htmlspecialchars($row['expense_date']); ?></td>
                    <td>Rs <?php echo number_format((float) $row['amount'], 2); ?></td>
                </tr>
                <?php endforeach; ?>
            <?php endif; ?>
            </tbody>
        </table>
    </article>
</section>
<?php
renderPageEnd();
