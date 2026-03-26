<?php
declare(strict_types=1);

require_once __DIR__ . '/backend/includes/bootstrap.php';

if (currentUser() === null) {
    redirect('pages/login.php');
}

redirect('pages/dashboard.php');
