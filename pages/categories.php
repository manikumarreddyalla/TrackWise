<?php
declare(strict_types=1);

require_once __DIR__ . '/../backend/includes/layout.php';
require_once __DIR__ . '/../backend/includes/categories.php';
requireAuth();

$userId = (int) currentUser()['id'];

$categoriesStmt = $pdo->prepare(
    'SELECT c.id, c.category_name, c.category_type,
            COUNT(e.id) AS expense_count,
            COALESCE(SUM(e.amount), 0) AS total_amount
     FROM categories c
     LEFT JOIN expenses e ON e.category_id = c.id AND e.user_id = c.user_id
     WHERE c.user_id = :user_id
     GROUP BY c.id, c.category_name, c.category_type
     ORDER BY c.category_name ASC'
);
$categoriesStmt->execute(['user_id' => $userId]);
$categories = $categoriesStmt->fetchAll();

renderPageStart('Category Management', 'categories');
?>
<header class="content-header">
    <h1>Category Management</h1>
</header>

<section class="panel panel-compact">
    <h2>Create Category</h2>
    <form action="<?php echo htmlspecialchars(appUrl('backend/actions/add_category.php')); ?>" method="post" class="form-inline">
        <input type="text" name="category_name" placeholder="Category name" required>
        <select name="category_type">
            <option value="Variable">Variable</option>
            <option value="Fixed">Fixed</option>
        </select>
        <button class="btn" type="submit">Add</button>
    </form>
</section>

<section class="panel">
    <table>
        <thead>
            <tr>
                <th>Name</th>
                <th>Type</th>
                <th># Expenses</th>
                <th>Total Spend</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
        <?php if (empty($categories)): ?>
            <tr><td colspan="5">No categories found.</td></tr>
        <?php else: ?>
            <?php foreach ($categories as $category): ?>
            <tr>
                <td><?php echo htmlspecialchars($category['category_name']); ?></td>
                <td><?php echo htmlspecialchars($category['category_type']); ?></td>
                <td><?php echo number_format((int) $category['expense_count']); ?></td>
                <td>Rs <?php echo number_format((float) $category['total_amount'], 2); ?></td>
                <td>
                    <form action="<?php echo htmlspecialchars(appUrl('backend/actions/delete_category.php')); ?>" method="post">
                        <input type="hidden" name="category_id" value="<?php echo (int) $category['id']; ?>">
                        <button type="submit" class="btn danger small" onclick="return confirm('Delete this category?')">Delete</button>
                    </form>
                </td>
            </tr>
            <?php endforeach; ?>
        <?php endif; ?>
        </tbody>
    </table>
</section>
<?php
renderPageEnd();
