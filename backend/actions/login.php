<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/bootstrap.php';
requireGuest();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('pages/login.php');
}

$email = trim($_POST['email'] ?? '');
$password = $_POST['password'] ?? '';

if ($email === '' || $password === '') {
    setFlash('error', 'Email and password are required.');
    redirect('pages/login.php');
}

$stmt = $pdo->prepare(
    'SELECT id, name, email, password
     FROM users
     WHERE email = :email
     LIMIT 1'
);
$stmt->execute(['email' => $email]);
$user = $stmt->fetch();

if (!$user || !password_verify($password, $user['password'])) {
    setFlash('error', 'Invalid login credentials.');
    redirect('pages/login.php');
}

$_SESSION['user'] = [
    'id' => (int) $user['id'],
    'name' => $user['name'],
    'email' => $user['email'],
];

setFlash('success', 'Welcome back, ' . $user['name'] . '.');
redirect('pages/dashboard.php');
