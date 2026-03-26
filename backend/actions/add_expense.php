<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/bootstrap.php';
requireAuth();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('pages/expenses.php');
}

$userId = (int) currentUser()['id'];
$title = trim($_POST['title'] ?? '');
$amountRaw = $_POST['amount'] ?? '';
$categoryId = (int) ($_POST['category_id'] ?? 0);
$expenseDate = $_POST['expense_date'] ?? '';
$description = trim($_POST['description'] ?? '');

if ($title === '' || $amountRaw === '' || $categoryId <= 0 || $expenseDate === '') {
    setFlash('error', 'Title, amount, category, and date are required.');
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
    setFlash('error', 'Selected category is not valid.');
    redirect('pages/expenses.php');
}

$insertStmt = $pdo->prepare(
    'INSERT INTO expenses (user_id, title, amount, category_id, expense_date, description)
     VALUES (:user_id, :title, :amount, :category_id, :expense_date, :description)'
);
$insertStmt->execute([
    'user_id' => $userId,
    'title' => $title,
    'amount' => $amount,
    'category_id' => $categoryId,
    'expense_date' => $expenseDate,
    'description' => $description,
]);

setFlash('success', 'Expense added successfully.');
redirect('pages/expenses.php');
