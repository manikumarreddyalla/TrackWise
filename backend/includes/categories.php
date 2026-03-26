<?php
declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';

function fetchUserCategories(PDO $pdo, int $userId): array
{
    $stmt = $pdo->prepare(
        'SELECT id, category_name, category_type
         FROM categories
         WHERE user_id = :user_id
         ORDER BY category_name ASC'
    );
    $stmt->execute(['user_id' => $userId]);

    return $stmt->fetchAll();
}

function ensureDefaultCategories(PDO $pdo, int $userId): void
{
    $countStmt = $pdo->prepare('SELECT COUNT(*) FROM categories WHERE user_id = :user_id');
    $countStmt->execute(['user_id' => $userId]);

    if ((int) $countStmt->fetchColumn() > 0) {
        return;
    }

    $defaults = [
        ['Food', 'Variable'],
        ['Travel', 'Variable'],
        ['Bills', 'Fixed'],
        ['Groceries', 'Variable'],
        ['Entertainment', 'Variable'],
        ['Rent', 'Fixed'],
    ];

    $insertStmt = $pdo->prepare(
        'INSERT INTO categories (user_id, category_name, category_type)
         VALUES (:user_id, :category_name, :category_type)'
    );

    foreach ($defaults as [$name, $type]) {
        $insertStmt->execute([
            'user_id' => $userId,
            'category_name' => $name,
            'category_type' => $type,
        ]);
    }
}
