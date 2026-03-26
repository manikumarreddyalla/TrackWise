<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/bootstrap.php';
requireAuth();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('pages/expenses.php');
}

$userId = (int) currentUser()['id'];
$expenseId = (int) ($_POST['expense_id'] ?? 0);
$title = trim($_POST['title'] ?? '');
$amountRaw = $_POST['amount'] ?? '';
$categoryId = (int) ($_POST['category_id'] ?? 0);
$expenseDate = $_POST['expense_date'] ?? '';
$description = trim($_POST['description'] ?? '');

if ($expenseId <= 0 || $title === '' || $amountRaw === '' || $categoryId <= 0 || $expenseDate === '') {
    setFlash('error', 'Please provide all required expense details.');
    redirect('pages/expenses.php');
}

$amount = normalizeAmount($amountRaw);
if ($amount <= 0) {
    setFlash('error', 'Amount must be greater than zero.');
    redirect('pages/expenses.php');
}

$categoryStmt = $pdo->prepare(
    'SELECT id FROM categories
     WHERE id = :category_id AND user_id = :user_id'
);
$categoryStmt->execute([
    'category_id' => $categoryId,
    'user_id' => $userId,
]);

if (!$categoryStmt->fetch()) {
    setFlash('error', 'Invalid category selected.');
    redirect('pages/expenses.php');
}

$updateStmt = $pdo->prepare(
    'UPDATE expenses
     SET title = :title,
         amount = :amount,
         category_id = :category_id,
         expense_date = :expense_date,
         description = :description
     WHERE id = :expense_id AND user_id = :user_id'
);
$updateStmt->execute([
    'title' => $title,
    'amount' => $amount,
    'category_id' => $categoryId,
    'expense_date' => $expenseDate,
    'description' => $description,
    'expense_id' => $expenseId,
    'user_id' => $userId,
]);

setFlash('success', 'Expense updated successfully.');
redirect('pages/expenses.php');
