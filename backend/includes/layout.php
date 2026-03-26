<?php
declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';

function renderFlash(): void
{
    $flash = getFlash();
    if ($flash === null) {
        return;
    }

    $typeClass = $flash['type'] === 'success' ? 'flash-success' : 'flash-error';
    echo '<div class="flash ' . $typeClass . '">' . htmlspecialchars($flash['message']) . '</div>';
}

function renderPageStart(string $title, string $active = ''): void
{
    $user = currentUser();
    $isAuthPage = $user === null;
    $cssFile = dirname(__DIR__, 2) . '/css/style.css';
    $jsFile = dirname(__DIR__, 2) . '/js/dashboard.js';
    $cssVersion = is_file($cssFile) ? (string) filemtime($cssFile) : '1';
    $jsVersion = is_file($jsFile) ? (string) filemtime($jsFile) : '1';
    $loadCharts = in_array($active, ['dashboard', 'analytics', 'report'], true);
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title><?php echo htmlspecialchars($title); ?> | TrackWise</title>
        <link rel="stylesheet" href="<?php echo htmlspecialchars(appUrl('css/style.css?v=' . $cssVersion)); ?>">
        <?php if ($loadCharts): ?>
        <script defer src="https://cdn.jsdelivr.net/npm/chart.js"></script>
        <?php endif; ?>
        <script defer src="<?php echo htmlspecialchars(appUrl('js/dashboard.js?v=' . $jsVersion)); ?>"></script>
    </head>
    <body>
    <?php if ($isAuthPage): ?>
        <main class="auth-wrapper">
            <section class="auth-card">
                <h1>TrackWise</h1>
                <p class="subtitle">Relational Expense Analytics Platform</p>
                <?php renderFlash(); ?>
    <?php else: ?>
        <div class="app-shell">
            <aside class="sidebar">
                <h2>TrackWise</h2>
                <p class="subtitle">Expense Analytics</p>
                <nav>
                    <a class="<?php echo $active === 'dashboard' ? 'active' : ''; ?>" href="<?php echo htmlspecialchars(appUrl('pages/dashboard.php')); ?>">Dashboard</a>
                    <a class="<?php echo $active === 'expenses' ? 'active' : ''; ?>" href="<?php echo htmlspecialchars(appUrl('pages/expenses.php')); ?>">Expense History</a>
                    <a class="<?php echo $active === 'recurring' ? 'active' : ''; ?>" href="<?php echo htmlspecialchars(appUrl('pages/recurring_expenses.php')); ?>">Recurring Expenses</a>
                    <a class="<?php echo $active === 'report' ? 'active' : ''; ?>" href="<?php echo htmlspecialchars(appUrl('pages/report.php')); ?>">Report</a>
                    <a class="<?php echo $active === 'analytics' ? 'active' : ''; ?>" href="<?php echo htmlspecialchars(appUrl('pages/analytics.php')); ?>">Analytics</a>
                    <a class="<?php echo $active === 'categories' ? 'active' : ''; ?>" href="<?php echo htmlspecialchars(appUrl('pages/categories.php')); ?>">Categories</a>
                    <a href="<?php echo htmlspecialchars(appUrl('backend/actions/logout.php')); ?>">Logout</a>
                </nav>
            </aside>
            <main class="content">
                <?php renderFlash(); ?>
    <?php endif;
}

function renderPageEnd(): void
{
    $isAuthPage = currentUser() === null;
    if ($isAuthPage) {
        echo '        </section>'; 
        echo '    </main>';
        echo '</body></html>';
        return;
    }

    echo '            </main>';
    echo '        </div>';
    echo '    </body>';
    echo '</html>';
}
