<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/bootstrap.php';
requireAuth();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('pages/expenses.php');
}

$userId = (int) currentUser()['id'];
$expenseId = (int) ($_POST['expense_id'] ?? 0);

if ($expenseId <= 0) {
    setFlash('error', 'Invalid expense selected.');
    redirect('pages/expenses.php');
}

$deleteStmt = $pdo->prepare(
    'DELETE FROM expenses
     WHERE id = :expense_id AND user_id = :user_id'
);
$deleteStmt->execute([
    'expense_id' => $expenseId,
    'user_id' => $userId,
]);

setFlash('success', 'Expense deleted successfully.');
redirect('pages/expenses.php');
