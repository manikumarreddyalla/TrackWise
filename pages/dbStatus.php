<?php
declare(strict_types=1);

require_once __DIR__ . '/../backend/includes/layout.php';
requireAuth();

header('Location: ' . appUrl('pages/dashboard.php'));
exit;
