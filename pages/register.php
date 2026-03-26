<?php
declare(strict_types=1);

require_once __DIR__ . '/../backend/includes/layout.php';
requireGuest();

renderPageStart('Register');
?>
<form action="<?php echo htmlspecialchars(appUrl('backend/actions/register.php')); ?>" method="post" class="form-grid">
    <label for="name">Full Name</label>
    <input id="name" name="name" type="text" required>

    <label for="email">Email</label>
    <input id="email" name="email" type="email" required>

    <label for="password">Password</label>
    <input id="password" name="password" type="password" minlength="6" required>

    <label for="confirm_password">Confirm Password</label>
    <input id="confirm_password" name="confirm_password" type="password" minlength="6" required>

    <button type="submit" class="btn">Create Account</button>
</form>
<p class="auth-link">Already have an account? <a href="<?php echo htmlspecialchars(appUrl('pages/login.php')); ?>">Login</a></p>
<?php
renderPageEnd();
