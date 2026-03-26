<?php
declare(strict_types=1);

require_once __DIR__ . '/../backend/includes/layout.php';
require_once __DIR__ . '/../backend/includes/categories.php';
require_once __DIR__ . '/../backend/includes/recurring.php';
requireAuth();

$userId = (int) currentUser()['id'];
$categories = fetchUserCategories($pdo, $userId);

// Fetch recurring expenses
$stmt = $pdo->prepare(
    'SELECT r.id, r.title, r.amount, r.recurrence_type, r.start_date, r.end_date, 
            r.last_generated_date, r.is_active, r.description, c.category_name
     FROM recurring_expenses r
     JOIN categories c ON c.id = r.category_id
     WHERE r.user_id = :user_id
     ORDER BY r.is_active DESC, r.start_date DESC'
);
$stmt->execute(['user_id' => $userId]);
$recurringExpenses = $stmt->fetchAll();

renderPageStart('Recurring Expenses', 'recurring');
?>
<header class="content-header">
    <h1>Recurring Expenses</h1>
    <button type="button" class="btn" id="openAddRecurringModal">+ Add Recurring</button>
</header>

<section class="panel">
    <p class="info-text">Set up recurring expenses for subscriptions, bills, and other regular payments. Expenses will be automatically generated on your dashboard.</p>
    
    <?php if (empty($recurringExpenses)): ?>
        <div class="empty-state">
            <p>No recurring expenses set up yet.</p>
            <p>Create one to automatically generate regular expenses.</p>
        </div>
    <?php else: ?>
        <table>
            <thead>
                <tr>
                    <th>Title</th>
                    <th>Category</th>
                    <th>Amount</th>
                    <th>Frequency</th>
                    <th>Start Date</th>
                    <th>End Date</th>
                    <th>Status</th>
                    <th>Next Date</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($recurringExpenses as $recurring): ?>
                <?php 
                    $nextDate = calculateNextDate($recurring['last_generated_date'], $recurring['recurrence_type']);
                    $isExpired = $recurring['end_date'] !== null && strtotime($recurring['end_date']) < time();
                    $statusClass = !$recurring['is_active'] ? 'inactive' : ($isExpired ? 'expired' : 'active');
                    $statusText = !$recurring['is_active'] ? 'Inactive' : ($isExpired ? 'Ended' : 'Active');
                ?>
                <tr class="recurring-row <?php echo $statusClass; ?>">
                    <td>
                        <strong><?php echo htmlspecialchars($recurring['title']); ?></strong>
                        <?php if ($recurring['description']): ?>
                            <div class="small-text"><?php echo htmlspecialchars($recurring['description']); ?></div>
                        <?php endif; ?>
                    </td>
                    <td><?php echo htmlspecialchars($recurring['category_name']); ?></td>
                    <td>Rs <?php echo number_format((float) $recurring['amount'], 2); ?></td>
                    <td><?php echo htmlspecialchars($recurring['recurrence_type']); ?></td>
                    <td><?php echo htmlspecialchars($recurring['start_date']); ?></td>
                    <td><?php echo $recurring['end_date'] ? htmlspecialchars($recurring['end_date']) : '-'; ?></td>
                    <td>
                        <span class="status-badge <?php echo $statusClass; ?>">
                            <?php echo $statusText; ?>
                        </span>
                    </td>
                    <td>
                        <?php if ($nextDate): ?>
                            <span class="next-date"><?php echo htmlspecialchars($nextDate); ?></span>
                        <?php else: ?>
                            <span class="error-text">Error</span>
                        <?php endif; ?>
                    </td>
                    <td class="action-group">
                        <form action="<?php echo htmlspecialchars(appUrl('backend/actions/toggle_recurring_expense.php')); ?>" method="post" style="display:inline;">
                            <input type="hidden" name="recurring_id" value="<?php echo (int) $recurring['id']; ?>">
                            <button type="submit" class="btn small <?php echo $recurring['is_active'] ? 'warning' : 'success'; ?>">
                                <?php echo $recurring['is_active'] ? 'Pause' : 'Activate'; ?>
                            </button>
                        </form>

                        <form action="<?php echo htmlspecialchars(appUrl('backend/actions/delete_recurring_expense.php')); ?>" method="post" style="display:inline;">
                            <input type="hidden" name="recurring_id" value="<?php echo (int) $recurring['id']; ?>">
                            <button type="submit" class="btn danger small" onclick="return confirm('Delete this recurring expense?')">Delete</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</section>

<div id="addRecurringModal" class="modal" aria-hidden="true">
    <div class="modal-content">
        <h2>Add Recurring Expense</h2>
        <form action="<?php echo htmlspecialchars(appUrl('backend/actions/add_recurring_expense.php')); ?>" method="post" class="form-grid">
            <label for="add_rec_title">Title</label>
            <input id="add_rec_title" name="title" type="text" required placeholder="e.g., Netflix Subscription">

            <label for="add_rec_amount">Amount</label>
            <input id="add_rec_amount" name="amount" type="number" step="0.01" min="0.01" required>

            <label for="add_rec_category_id">Category</label>
            <select id="add_rec_category_id" name="category_id" required>
                <option value="">Select Category</option>
                <?php foreach ($categories as $category): ?>
                    <option value="<?php echo (int) $category['id']; ?>">
                        <?php echo htmlspecialchars($category['category_name']); ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <label for="add_rec_frequency">Frequency</label>
            <select id="add_rec_frequency" name="recurrence_type" required>
                <option value="Daily">Daily</option>
                <option value="Weekly">Weekly</option>
                <option value="Bi-Weekly">Bi-Weekly</option>
                <option value="Monthly" selected>Monthly</option>
                <option value="Quarterly">Quarterly</option>
                <option value="Yearly">Yearly</option>
            </select>

            <label for="add_rec_start_date">Start Date</label>
            <input id="add_rec_start_date" name="start_date" type="date" value="<?php echo date('Y-m-d'); ?>" required>

            <label for="add_rec_end_date">End Date (Optional)</label>
            <input id="add_rec_end_date" name="end_date" type="date">

            <label for="add_rec_description">Description (Optional)</label>
            <textarea id="add_rec_description" name="description" rows="2" placeholder="Optional notes"></textarea>

            <div class="modal-actions">
                <button type="submit" class="btn">Create Recurring</button>
                <button type="button" class="btn ghost" onclick="closeModal('addRecurringModal')">Cancel</button>
            </div>
        </form>
    </div>
</div>

<script>
document.getElementById('openAddRecurringModal').addEventListener('click', function() {
    document.getElementById('addRecurringModal').setAttribute('aria-hidden', 'false');
});

function closeModal(modalId) {
    document.getElementById(modalId).setAttribute('aria-hidden', 'true');
}

document.getElementById('addRecurringModal').addEventListener('click', function(event) {
    if (event.target === this) {
        closeModal('addRecurringModal');
    }
});
</script>
<?php
renderPageEnd();
