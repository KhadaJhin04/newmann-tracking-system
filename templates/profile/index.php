<?php 
    // Determine which header to use based on user type
    if ($_SESSION['user_type'] === 'management') {
        require ROOT_PATH . '/templates/layout/header.php';
    } else {
        // For drivers, we can create a simpler header or use their dashboard layout
        // For now, let's create a simple inline header for them
        echo '<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"><title>My Profile</title><link rel="stylesheet" href="' . BASE_PATH . '/assets/driver_style.css"></head><body>';
        echo '<header class="driver-header"><h1>My Profile</h1><a href="' . BASE_PATH . '/driver_dashboard">Back to Dashboard</a></header><main class="driver-content">';
    }
?>

<div class="detail-card">
    <h4>My Information</h4>
    <?php if ($user_data): ?>
        <div class="detail-item"><span class="label">Name:</span> <span class="value"><?= htmlspecialchars($user_data['name']) ?></span></div>
        <div class="detail-item"><span class="label">Username:</span> <span class="value"><?= htmlspecialchars($user_data['username']) ?></span></div>
        <?php if(isset($user_data['email'])): ?>
            <div class="detail-item"><span class="label">Email:</span> <span class="value"><?= htmlspecialchars($user_data['email']) ?></span></div>
        <?php endif; ?>
        <?php if(isset($user_data['role'])): ?>
            <div class="detail-item"><span class="label">Role:</span> <span class="value"><?= htmlspecialchars($user_data['role']) ?></span></div>
        <?php endif; ?>
        <?php if(isset($user_data['license_number'])): ?>
            <div class="detail-item"><span class="label">License No:</span> <span class="value"><?= htmlspecialchars($user_data['license_number']) ?></span></div>
        <?php endif; ?>
    <?php else: ?>
        <p>Could not load user data.</p>
    <?php endif; ?>
</div>

<div class="detail-card" style="margin-top: 2rem;">
    <h4>Change Password</h4>
    
    <?php if (isset($_SESSION['success_message'])): ?>
        <div class="alert alert-success">
            <?= htmlspecialchars($_SESSION['success_message']) ?>
        </div>
        <?php unset($_SESSION['success_message']); ?>
    <?php endif; ?>

    <?php if (isset($_SESSION['error_message'])): ?>
        <div class="alert alert-danger">
            <?= $_SESSION['error_message'] /* Use raw output to render <br> tags */ ?>
        </div>
        <?php unset($_SESSION['error_message']); ?>
    <?php endif; ?>

    <form action="<?= BASE_PATH ?>/profile" method="POST">
        <div class="form-group">
            <label for="current_password">Current Password</label>
            <input type="password" name="current_password" id="current_password" required>
        </div>
        <div class="form-group">
            <label for="new_password">New Password (min. 6 characters)</label>
            <input type="password" name="new_password" id="new_password" required>
        </div>
        <div class="form-group">
            <label for="confirm_password">Confirm New Password</label>
            <input type="password" name="confirm_password" id="confirm_password" required>
        </div>
        <button type="submit" class="btn btn-primary">Update Password</button>
    </form>
</div>

<?php 
    // Determine which footer to use
    if ($_SESSION['user_type'] === 'management') {
        require ROOT_PATH . '/templates/layout/footer.php';
    } else {
        echo '</main></body></html>';
    }
?>