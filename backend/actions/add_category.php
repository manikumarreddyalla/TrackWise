<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/categories.php';
requireAuth();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('pages/categories.php');
}

$userId = (int) currentUser()['id'];
$categoryName = trim($_POST['category_name'] ?? '');
$categoryType = trim($_POST['category_type'] ?? 'Variable');

if ($categoryName === '') {
    setFlash('error', 'Category name is required.');
    redirect('pages/categories.php');
}

if (!in_array($categoryType, ['Fixed', 'Variable'], true)) {
    $categoryType = 'Variable';
}

$existsStmt = $pdo->prepare(
    'SELECT id FROM categories
     WHERE user_id = :user_id AND LOWER(category_name) = LOWER(:category_name)
     LIMIT 1'
);
$existsStmt->execute([
    'user_id' => $userId,
    'category_name' => $categoryName,
]);

if ($existsStmt->fetch()) {
    setFlash('error', 'Category already exists.');
    redirect('pages/categories.php');
}

$insertStmt = $pdo->prepare(
    'INSERT INTO categories (user_id, category_name, category_type)
     VALUES (:user_id, :category_name, :category_type)'
);
$insertStmt->execute([
    'user_id' => $userId,
    'category_name' => $categoryName,
    'category_type' => $categoryType,
]);

setFlash('success', 'Category added successfully.');
redirect('pages/categories.php');
