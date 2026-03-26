<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/bootstrap.php';
requireAuth();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('pages/categories.php');
}

$userId = (int) currentUser()['id'];
$categoryId = (int) ($_POST['category_id'] ?? 0);

if ($categoryId <= 0) {
    setFlash('error', 'Invalid category selected.');
    redirect('pages/categories.php');
}

$usageStmt = $pdo->prepare(
    'SELECT COUNT(*) FROM expenses
     WHERE user_id = :user_id AND category_id = :category_id'
);
$usageStmt->execute([
    'user_id' => $userId,
    'category_id' => $categoryId,
]);

if ((int) $usageStmt->fetchColumn() > 0) {
    setFlash('error', 'Cannot delete category with existing expenses.');
    redirect('pages/categories.php');
}

$deleteStmt = $pdo->prepare(
    'DELETE FROM categories
     WHERE id = :category_id AND user_id = :user_id'
);
$deleteStmt->execute([
    'category_id' => $categoryId,
    'user_id' => $userId,
]);

setFlash('success', 'Category deleted successfully.');
redirect('pages/categories.php');
