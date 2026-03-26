<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/bootstrap.php';
requireGuest();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('pages/register.php');
}

$name = trim($_POST['name'] ?? '');
$email = trim($_POST['email'] ?? '');
$password = $_POST['password'] ?? '';
$confirmPassword = $_POST['confirm_password'] ?? '';

if ($name === '' || $email === '' || $password === '') {
    setFlash('error', 'All fields are required.');
    redirect('pages/register.php');
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    setFlash('error', 'Please enter a valid email address.');
    redirect('pages/register.php');
}

if (strlen($password) < 6) {
    setFlash('error', 'Password must be at least 6 characters long.');
    redirect('pages/register.php');
}

if ($password !== $confirmPassword) {
    setFlash('error', 'Passwords do not match.');
    redirect('pages/register.php');
}

$existsStmt = $pdo->prepare('SELECT id FROM users WHERE email = :email LIMIT 1');
$existsStmt->execute(['email' => $email]);
if ($existsStmt->fetch()) {
    setFlash('error', 'This email is already registered.');
    redirect('pages/register.php');
}

$insertStmt = $pdo->prepare(
    'INSERT INTO users (name, email, password)
     VALUES (:name, :email, :password)'
);
$insertStmt->execute([
    'name' => $name,
    'email' => $email,
    'password' => password_hash($password, PASSWORD_DEFAULT),
]);

$userId = (int) $pdo->lastInsertId();

require_once __DIR__ . '/../includes/categories.php';
ensureDefaultCategories($pdo, $userId);

$_SESSION['user'] = [
    'id' => $userId,
    'name' => $name,
    'email' => $email,
];

setFlash('success', 'Registration successful. Welcome to TrackWise.');
redirect('pages/dashboard.php');
