<?php
declare(strict_types=1);

require_once __DIR__ . '/../backend/includes/layout.php';
requireGuest();

renderPageStart('Login');
?>
<form action="<?php echo htmlspecialchars(appUrl('backend/actions/login.php')); ?>" method="post" class="form-grid">
    <label for="email">Email</label>
    <input id="email" name="email" type="email" required>

    <label for="password">Password</label>
    <input id="password" name="password" type="password" required>

    <button type="submit" class="btn">Login</button>
</form>
<p class="auth-link">New user? <a href="<?php echo htmlspecialchars(appUrl('pages/register.php')); ?>">Register here</a></p>
<?php
renderPageEnd();
