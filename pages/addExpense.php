<?php
declare(strict_types=1);

require_once __DIR__ . '/../backend/includes/layout.php';
require_once __DIR__ . '/../backend/includes/categories.php';
requireAuth();

$userId = (int) currentUser()['id'];
$categories = fetchUserCategories($pdo, $userId);

renderPageStart('Add Expense', 'expenses');
?>
<header class="content-header">
    <h1>Add Expense</h1>
</header>

<section class="panel panel-compact">
    <form action="<?php echo htmlspecialchars(appUrl('backend/actions/add_expense.php')); ?>" method="post" class="form-grid">
        <label for="title">Title</label>
        <input id="title" name="title" type="text" required>

        <label for="amount">Amount</label>
        <input id="amount" name="amount" type="number" min="0.01" step="0.01" required>

        <label for="category_id">Category</label>
        <select id="category_id" name="category_id" required>
            <option value="">Select Category</option>
            <?php foreach ($categories as $category): ?>
                <option value="<?php echo (int) $category['id']; ?>">
                    <?php echo htmlspecialchars($category['category_name']); ?> (<?php echo htmlspecialchars($category['category_type']); ?>)
                </option>
            <?php endforeach; ?>
        </select>

        <label for="expense_date">Expense Date</label>
        <input id="expense_date" name="expense_date" type="date" value="<?php echo date('Y-m-d'); ?>" required>

        <label for="description">Description</label>
        <textarea id="description" name="description" rows="4" placeholder="Optional notes"></textarea>

        <div class="inline-actions">
            <button type="submit" class="btn">Save Expense</button>
            <a class="btn ghost" href="<?php echo htmlspecialchars(appUrl('pages/expenses.php')); ?>">Back</a>
        </div>
    </form>
    
    <div class="info-text" style="margin-top: 20px;">
        <p style="margin: 0;">💡 Want to set up recurring expenses like subscriptions or bills?</p>
        <a href="<?php echo htmlspecialchars(appUrl('pages/recurring_expenses.php')); ?>" class="btn" style="margin-top: 10px; display: inline-block;">Go to Recurring Expenses</a>
    </div>
</section>
<?php
renderPageEnd();
