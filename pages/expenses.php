<?php
declare(strict_types=1);

require_once __DIR__ . '/../backend/includes/layout.php';
require_once __DIR__ . '/../backend/includes/categories.php';
requireAuth();

$userId = (int) currentUser()['id'];
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

renderPageStart('Expense History', 'expenses');
?>
<header class="content-header">
    <h1>Expense History</h1>
    <button type="button" class="btn" id="openAddExpenseModal">+ Add Expense</button>
</header>

<section class="panel">
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
        <a class="btn ghost" href="<?php echo htmlspecialchars(appUrl('pages/expenses.php')); ?>">Reset</a>
    </form>
</section>

<section class="panel">
    <table>
        <thead>
            <tr>
                <th>Date</th>
                <th>Title</th>
                <th>Category</th>
                <th>Type</th>
                <th>Amount</th>
                <th>Description</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
        <?php if (empty($expenses)): ?>
            <tr><td colspan="7">No expenses found for selected filters.</td></tr>
        <?php else: ?>
            <?php foreach ($expenses as $expense): ?>
            <tr>
                <td><?php echo htmlspecialchars($expense['expense_date']); ?></td>
                <td><?php echo htmlspecialchars($expense['title']); ?></td>
                <td><?php echo htmlspecialchars($expense['category_name']); ?></td>
                <td><?php echo htmlspecialchars($expense['category_type']); ?></td>
                <td>Rs <?php echo number_format((float) $expense['amount'], 2); ?></td>
                <td><?php echo htmlspecialchars($expense['description'] ?: '-'); ?></td>
                <td class="action-group">
                    <button
                        type="button"
                        class="btn small edit-expense-btn"
                        data-expense-id="<?php echo (int) $expense['id']; ?>"
                        data-title="<?php echo htmlspecialchars($expense['title'], ENT_QUOTES); ?>"
                        data-amount="<?php echo htmlspecialchars((string) $expense['amount'], ENT_QUOTES); ?>"
                        data-category-id="<?php echo (int) $expense['category_id']; ?>"
                        data-expense-date="<?php echo htmlspecialchars($expense['expense_date'], ENT_QUOTES); ?>"
                        data-description="<?php echo htmlspecialchars($expense['description'], ENT_QUOTES); ?>"
                    >Edit</button>

                    <form action="<?php echo htmlspecialchars(appUrl('backend/actions/delete_expense.php')); ?>" method="post">
                        <input type="hidden" name="expense_id" value="<?php echo (int) $expense['id']; ?>">
                        <button type="submit" class="btn danger small" onclick="return confirm('Delete this expense?')">Delete</button>
                    </form>
                </td>
            </tr>
            <?php endforeach; ?>
        <?php endif; ?>
        </tbody>
    </table>
</section>

<div id="addExpenseModal" class="modal" aria-hidden="true">
    <div class="modal-content">
        <h2>Add Expense</h2>
        <form action="<?php echo htmlspecialchars(appUrl('backend/actions/add_expense.php')); ?>" method="post" class="form-grid">
            <label for="add_title">Title</label>
            <input id="add_title" name="title" type="text" required>

            <label for="add_amount">Amount</label>
            <input id="add_amount" name="amount" type="number" step="0.01" min="0.01" required>

            <label for="add_category_id">Category</label>
            <select id="add_category_id" name="category_id" required>
                <?php foreach ($categories as $category): ?>
                    <option value="<?php echo (int) $category['id']; ?>"><?php echo htmlspecialchars($category['category_name']); ?></option>
                <?php endforeach; ?>
            </select>

            <label for="add_expense_date">Expense Date</label>
            <input id="add_expense_date" name="expense_date" type="date" value="<?php echo date('Y-m-d'); ?>" required>

            <label for="add_description">Description</label>
            <textarea id="add_description" name="description" rows="3"></textarea>

            <div class="modal-actions">
                <button type="submit" class="btn">Save</button>
                <button type="button" class="btn ghost" id="closeAddModal">Cancel</button>
            </div>
        </form>
    </div>
</div>

<div id="editExpenseModal" class="modal" aria-hidden="true">
    <div class="modal-content">
        <h2>Edit Expense</h2>
        <form action="<?php echo htmlspecialchars(appUrl('backend/actions/update_expense.php')); ?>" method="post" class="form-grid" id="editExpenseForm">
            <input type="hidden" name="expense_id" id="edit_expense_id">

            <label for="edit_title">Title</label>
            <input id="edit_title" name="title" type="text" required>

            <label for="edit_amount">Amount</label>
            <input id="edit_amount" name="amount" type="number" step="0.01" min="0.01" required>

            <label for="edit_category_id">Category</label>
            <select id="edit_category_id" name="category_id" required>
                <?php foreach ($categories as $category): ?>
                    <option value="<?php echo (int) $category['id']; ?>"><?php echo htmlspecialchars($category['category_name']); ?></option>
                <?php endforeach; ?>
            </select>

            <label for="edit_expense_date">Expense Date</label>
            <input id="edit_expense_date" name="expense_date" type="date" required>

            <label for="edit_description">Description</label>
            <textarea id="edit_description" name="description" rows="3"></textarea>

            <div class="modal-actions">
                <button type="submit" class="btn">Update</button>
                <button type="button" class="btn ghost" id="closeEditModal">Cancel</button>
            </div>
        </form>
    </div>
</div>
<?php
renderPageEnd();
