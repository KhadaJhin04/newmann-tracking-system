<?php
// Ensure BASE_PATH is available, default if not (for safety)
if (!defined('BASE_PATH')) {
    define('BASE_PATH', '/newmann_tracking');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Newmann Tracking - Login</title>
    <link rel="stylesheet" href="<?= BASE_PATH ?>/assets/style.css">
</head>
<body>
    <div class="login-container">
        <div class="login-box">
            <h2>Newmann Tracking System</h2>
            
            <?php if (isset($_SESSION['error_message'])): ?>
                <div class="alert alert-danger">
                    <?= htmlspecialchars($_SESSION['error_message']) ?>
                </div>
                <?php unset($_SESSION['error_message']); ?>
            <?php endif; ?>

            <form action="<?= BASE_PATH ?>/handle_login" method="POST">
                <div class="form-group">
                    <label for="user_type">Login As:</label>
                    <select id="user_type" name="user_type" required>
                        <option value="management">Management/Warehouse</option>
                        <option value="driver">Driver</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="username">Username</label>
                    <input type="text" id="username" name="username" required autofocus>
                </div>
                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" required>
                </div>
                <button type="submit" class="btn btn-primary">Login</button>
            </form>
        </div>
    </div>
</body>
</html>