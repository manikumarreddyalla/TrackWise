<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/bootstrap.php';
requireAuth();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('pages/recurring_expenses.php');
}

$userId = (int) currentUser()['id'];
$title = trim($_POST['title'] ?? '');
$amountRaw = $_POST['amount'] ?? '';
$categoryId = (int) ($_POST['category_id'] ?? 0);
$recurrenceType = trim($_POST['recurrence_type'] ?? 'Monthly');
$startDate = $_POST['start_date'] ?? '';
$endDate = trim($_POST['end_date'] ?? '');
$description = trim($_POST['description'] ?? '');

// Validate required fields
if ($title === '' || $amountRaw === '' || $categoryId <= 0 || $startDate === '') {
    setFlash('error', 'Title, amount, category, and start date are required.');
    redirect('pages/recurring_expenses.php');
}

// Validate amount
$amount = normalizeAmount($amountRaw);
if ($amount <= 0) {
    setFlash('error', 'Amount must be greater than zero.');
    redirect('pages/recurring_expenses.php');
}

// Validate recurrence type
$validRecurrenceTypes = ['Daily', 'Weekly', 'Bi-Weekly', 'Monthly', 'Quarterly', 'Yearly'];
if (!in_array($recurrenceType, $validRecurrenceTypes, true)) {
    setFlash('error', 'Invalid recurrence type.');
    redirect('pages/recurring_expenses.php');
}

// Validate category belongs to user
$categoryStmt = $pdo->prepare(
    'SELECT id FROM categories WHERE id = :category_id AND user_id = :user_id'
);
$categoryStmt->execute([
    'category_id' => $categoryId,
    'user_id' => $userId,
]);
if (!$categoryStmt->fetch()) {
    setFlash('error', 'Selected category is not valid.');
    redirect('pages/recurring_expenses.php');
}

// Validate dates
$startDateObj = DateTime::createFromFormat('Y-m-d', $startDate);
if (!$startDateObj) {
    setFlash('error', 'Invalid start date format.');
    redirect('pages/recurring_expenses.php');
}

$endDateForDb = null;
if ($endDate !== '') {
    $endDateObj = DateTime::createFromFormat('Y-m-d', $endDate);
    if (!$endDateObj) {
        setFlash('error', 'Invalid end date format.');
        redirect('pages/recurring_expenses.php');
    }
    if ($endDateObj <= $startDateObj) {
        setFlash('error', 'End date must be after start date.');
        redirect('pages/recurring_expenses.php');
    }
    $endDateForDb = $endDate;
}

// Insert recurring expense
$insertStmt = $pdo->prepare(
    'INSERT INTO recurring_expenses (user_id, title, amount, category_id, recurrence_type, start_date, end_date, description, last_generated_date, is_active)
     VALUES (:user_id, :title, :amount, :category_id, :recurrence_type, :start_date, :end_date, :description, :last_generated_date, 1)'
);

try {
    $insertStmt->execute([
        'user_id' => $userId,
        'title' => $title,
        'amount' => $amount,
        'category_id' => $categoryId,
        'recurrence_type' => $recurrenceType,
        'start_date' => $startDate,
        'end_date' => $endDateForDb,
        'description' => $description,
        'last_generated_date' => $startDate,
    ]);
    setFlash('success', 'Recurring expense created successfully.');
} catch (PDOException $e) {
    error_log('Failed to add recurring expense: ' . $e->getMessage());
    setFlash('error', 'Failed to create recurring expense.');
}

redirect('pages/recurring_expenses.php');
