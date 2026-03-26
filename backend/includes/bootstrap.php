<?php
declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../dbConnection.php';

$documentRoot = str_replace('\\', '/', (string) realpath($_SERVER['DOCUMENT_ROOT'] ?? __DIR__));
$projectRoot = str_replace('\\', '/', (string) realpath(__DIR__ . '/../../'));
$basePath = '';

if ($documentRoot !== '' && str_starts_with($projectRoot, $documentRoot)) {
    $basePath = substr($projectRoot, strlen($documentRoot)) ?: '';
}

// Fallback for alias/virtual-host setups where the project path is outside document root.
if ($basePath === '') {
    $scriptName = str_replace('\\', '/', (string) ($_SERVER['SCRIPT_NAME'] ?? ''));
    $segments = ['/pages/', '/backend/', '/css/', '/js/', '/database/'];

    foreach ($segments as $segment) {
        $segmentPos = strpos($scriptName, $segment);
        if ($segmentPos !== false) {
            $basePath = substr($scriptName, 0, $segmentPos);
            break;
        }
    }

    if ($basePath === '' && $scriptName !== '') {
        $scriptDir = str_replace('\\', '/', dirname($scriptName));
        $basePath = $scriptDir === '/' ? '' : $scriptDir;
    }
}

$basePath = '/' . trim((string) $basePath, '/');
$basePath = $basePath === '/' ? '' : $basePath;

function appUrl(string $path = ''): string
{
    global $basePath;

    $normalizedPath = ltrim($path, '/');
    if ($normalizedPath === '') {
        return $basePath !== '' ? $basePath : '/';
    }

    return ($basePath !== '' ? $basePath : '') . '/' . $normalizedPath;
}

function redirect(string $path): void
{
    header('Location: ' . appUrl($path));
    exit;
}

function setFlash(string $type, string $message): void
{
    $_SESSION['flash'] = [
        'type' => $type,
        'message' => $message,
    ];
}

function getFlash(): ?array
{
    if (!isset($_SESSION['flash'])) {
        return null;
    }

    $flash = $_SESSION['flash'];
    unset($_SESSION['flash']);

    return $flash;
}

function currentUser(): ?array
{
    return $_SESSION['user'] ?? null;
}

function requireGuest(): void
{
    if (currentUser() !== null) {
        redirect('/pages/dashboard.php');
    }
}

function requireAuth(): void
{
    if (currentUser() === null) {
        setFlash('error', 'Please log in to continue.');
        redirect('/pages/login.php');
    }
}

function normalizeAmount(string $value): float
{
    return round((float) $value, 2);
}
