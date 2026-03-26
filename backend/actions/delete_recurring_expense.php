<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/bootstrap.php';
requireAuth();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('pages/recurring_expenses.php');
}

$userId = (int) currentUser()['id'];
$recurringId = (int) ($_POST['recurring_id'] ?? 0);

if ($recurringId <= 0) {
    setFlash('error', 'Invalid recurring expense.');
    redirect('pages/recurring_expenses.php');
}

// Verify ownership
$verifyStmt = $pdo->prepare(
    'SELECT id FROM recurring_expenses WHERE id = :recurring_id AND user_id = :user_id'
);
$verifyStmt->execute([
    'recurring_id' => $recurringId,
    'user_id' => $userId,
]);

if (!$verifyStmt->fetch()) {
    setFlash('error', 'Recurring expense not found.');
    redirect('pages/recurring_expenses.php');
}

// Delete recurring expense
$deleteStmt = $pdo->prepare(
    'DELETE FROM recurring_expenses WHERE id = :recurring_id AND user_id = :user_id'
);

try {
    $deleteStmt->execute([
        'recurring_id' => $recurringId,
        'user_id' => $userId,
    ]);
    setFlash('success', 'Recurring expense deleted successfully.');
} catch (PDOException $e) {
    error_log('Failed to delete recurring expense: ' . $e->getMessage());
    setFlash('error', 'Failed to delete recurring expense.');
}

redirect('pages/recurring_expenses.php');
